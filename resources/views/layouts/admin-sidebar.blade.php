{{-- Admin design tokens (Inter/Outfit + STI blue) — matches Maintenance Personnel --}}
@include('layouts.partials.admin-design')
@include('layouts.partials.admin-grayscale-theme')

<div id="sidebar">

    {{-- ====================================== --}}
    {{-- HEADER --}}
    {{-- ====================================== --}}

    <div class="sidebar-header p-5">

        <div class="logo-icon">

            <img src="{{ asset('image/STI.png') }}" alt="">

        </div>

        <div>

            <h2>PaAyo</h2>

            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('admin') }}</span>

        </div>

    </div>

    <div class="sidebar-content">

        {{-- ====================================== --}}
        {{-- SEARCH --}}
        {{-- ====================================== --}}

        <div class="sidebar-search">

            <div class="sidebar-dropdown">

                <div id="dropdownTrigger" class="dropdown-trigger">

                    <div class="flex items-center gap-2">

                        <i data-lucide="search"></i>

                        <span id="selectedSection">Search...</span>

                    </div>

                </div>

                <div id="dropdownMenu" class="dropdown-menu">

                    <div class="dropdown-item" data-target="dashboard-section">
                        Dashboard
                    </div>

                    <div class="dropdown-item" data-target="monitoring-section">
                        Monitoring
                    </div>

                    <div class="dropdown-item" data-target="procurement-section">
                        Procurement
                    </div>

                    <div class="dropdown-item" data-target="users-section">
                        User Management
                    </div>

                    <div class="dropdown-item" data-target="reports-section">
                        Reports
                    </div>

                    <div class="dropdown-item" data-target="settings-section">
                        System
                    </div>

                </div>

            </div>

        </div>

        {{-- ====================================== --}}
        {{-- DASHBOARD --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="dashboard-section">

            DASHBOARD

        </div>

        <a
            href="{{ route('admin.dashboard') }}"
            class="menu-item {{ request()->is('admin/dashboard') ? 'active' : '' }}"
        >

            <i data-lucide="layout-dashboard"></i>

            <span>Dashboard</span>

        </a>

        {{-- ====================================== --}}
        {{-- MONITORING --}}
        {{-- ====================================== --}}

        @php
            $monitoringLinks = [
                [
                    'url' => route('admin.operations.building-layout'),
                    'label' => 'Campus Monitoring',
                    'icon' => 'building',
                    'active' => request()->is('admin/operations/building-layout*'),
                ],
                [
                    'url' => route('maintenance.property-assignments.index'),
                    'label' => 'Property Assignment',
                    'icon' => 'clipboard-signature',
                    'active' => request()->is('maintenance/property-assignments*'),
                ],
                [
                    'url' => route('admin.operations.equipment'),
                    'label' => 'All Equipment',
                    'icon' => 'monitor',
                    'active' => request()->is('admin/operations/equipment*'),
                ],
                [
                    'url' => route('admin.operations.borrowing'),
                    'label' => 'Borrowed Equipment',
                    'icon' => 'hand-helping',
                    'active' => request()->is('admin/operations/borrowing*'),
                ],
                [
                    'url' => route('admin.operations.movements'),
                    'label' => 'Equipment Movements',
                    'icon' => 'arrow-left-right',
                    'active' => request()->is('admin/operations/movements*'),
                ],
                [
                    'url' => route('admin.operations.schedules'),
                    'label' => 'Equipment Schedules',
                    'icon' => 'calendar-clock',
                    'active' => request()->is('admin/operations/schedules*'),
                ],
                [
                    'url' => route('maintenance.semester-inspections.index'),
                    'label' => 'Semester Inspections',
                    'icon' => 'clipboard-check',
                    'active' => request()->is('maintenance/semester-inspections*', 'maintenance/replacement-suggestions*'),
                ],
            ];
            $monitoringActive = collect($monitoringLinks)->contains('active', true);

            $boGroupActive = request()->is('admin/operations/back-orders*');
            $activeBoPayment = $boGroupActive ? request('payment') : null;
            $boLinks = [
                ['payment' => null, 'label' => 'All Back Orders', 'icon' => 'layers'],
                ['payment' => 'rfc', 'label' => 'Request for Check', 'icon' => 'clipboard-check'],
                ['payment' => 'ca', 'label' => 'Cash Advance', 'icon' => 'banknote'],
            ];
        @endphp

        <div class="menu-title" id="monitoring-section">

            MONITORING

        </div>

        <div class="menu-group {{ $monitoringActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $monitoringActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $monitoringActive ? 'true' : 'false' }}"
            >
                <i data-lucide="radar"></i>
                <span>Monitoring</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $monitoringActive) hidden @endif>
                @foreach($monitoringLinks as $link)
                    <a href="{{ $link['url'] }}" class="menu-sub-item {{ $link['active'] ? 'active' : '' }}">
                        <i data-lucide="{{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ====================================== --}}
        {{-- PROCUREMENT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="procurement-section">

            PROCUREMENT

        </div>

        <a
            href="{{ route('admin.operations.procurement') }}"
            class="menu-item {{ request()->is('admin/operations/procurement*', 'admin/operations/documents*') ? 'active' : '' }}"
        >
            <i data-lucide="git-branch"></i>
            <span>Procurement Monitor</span>
        </a>

        <div class="menu-group {{ $boGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $boGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $boGroupActive ? 'true' : 'false' }}"
            >
                <i data-lucide="package-x"></i>
                <span>Back Order Monitor</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $boGroupActive) hidden @endif>
                @foreach($boLinks as $boLink)
                    <a
                        href="{{ route('admin.back-orders.index', array_filter(['payment' => $boLink['payment']])) }}"
                        class="menu-sub-item {{ $boGroupActive && $activeBoPayment === $boLink['payment'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $boLink['icon'] }}"></i>
                        <span>{{ $boLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <a
            href="{{ route('purchaser.history.index') }}"
            class="menu-item {{ request()->is('purchaser/history*') ? 'active' : '' }}"
        >
            <i data-lucide="history"></i>
            <span>Purchase History</span>
        </a>

        {{-- ====================================== --}}
        {{-- USER MANAGEMENT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="users-section">

            USER MANAGEMENT

        </div>

        <a
            href="{{ route('admin.users') }}"
            class="menu-item {{ request()->is('admin/users*') ? 'active' : '' }}"
        >

            <i data-lucide="users"></i>

            <span>Users</span>

        </a>

        {{-- ====================================== --}}
        {{-- REPORTS --}}
        {{-- ====================================== --}}

        @php
            $reportsSectionActive = request()->is('admin/reports*');
            $reportLinks = [
                [
                    'url' => url('/admin/reports/maintenance-history'),
                    'label' => 'Maintenance',
                    'icon' => 'wrench',
                    'active' => request()->is('admin/reports/maintenance-history*'),
                ],
                [
                    'url' => url('/admin/reports/receiving'),
                    'label' => 'Receiving',
                    'icon' => 'package-check',
                    'active' => request()->is('admin/reports/receiving*'),
                ],
                [
                    'url' => url('/admin/reports/approval-logs'),
                    'label' => 'Approvals',
                    'icon' => 'stamp',
                    'active' => request()->is('admin/reports/approval-logs*', 'admin/reports/audit-logs*'),
                ],
                [
                    'url' => url('/admin/reports/user-login-logs'),
                    'label' => 'User access',
                    'icon' => 'shield',
                    'active' => request()->is('admin/reports/user-login-logs*'),
                ],
            ];
        @endphp

        <div class="menu-title" id="reports-section">

            REPORTS

        </div>

        <div class="menu-group {{ $reportsSectionActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $reportsSectionActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $reportsSectionActive ? 'true' : 'false' }}"
            >
                <i data-lucide="file-text"></i>
                <span>System Reports</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $reportsSectionActive) hidden @endif>
                @foreach($reportLinks as $reportLink)
                    <a
                        href="{{ $reportLink['url'] }}"
                        class="menu-sub-item {{ $reportLink['active'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $reportLink['icon'] }}"></i>
                        <span>{{ $reportLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ====================================== --}}
        {{-- SYSTEM --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="settings-section">

            SYSTEM

        </div>

        <a
            href="{{ route('admin.profile') }}"
            class="menu-item {{ request()->is('admin/profile*') || request()->is('admin/security*') ? 'active' : '' }}"
        >

            <i data-lucide="user-cog"></i>

            <span>Account Settings</span>

        </a>

        <a
            href="{{ url('/admin/settings/campus-setup-pin') }}"
            class="menu-item {{ request()->is('admin/settings*') ? 'active' : '' }}"
        >

            <i data-lucide="settings"></i>

            <span>System Settings</span>

        </a>

    </div>

</div>

@include('layouts.partials.admin-sidebar-assets')
