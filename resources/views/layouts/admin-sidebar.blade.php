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

        <!--<div class="sidebar-search">

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

                    <div class="dropdown-item" data-target="settings-section">
                        System
                    </div>

                </div>

            </div>

        </div>-->

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
                    'url' => route('admin.property-assignments.index'),
                    'label' => 'Property Assignment',
                    'icon' => 'clipboard-signature',
                    'active' => request()->is('admin/property-assignments*', 'maintenance/property-assignments*'),
                ],
                [
                    'url' => route('admin.operations.equipment'),
                    'label' => 'All Equipment',
                    'icon' => 'monitor',
                    'active' => request()->is('admin/operations/equipment*'),
                ],
                [
                    'url' => route('admin.borrowing.index'),
                    'label' => 'Borrowing',
                    'icon' => 'hand-helping',
                    'active' => request()->is('admin/borrowing*', 'admin/operations/borrowing*'),
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
            ];
            $monitoringActive = collect($monitoringLinks)->contains('active', true);

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
                <span>Equipment Monitoring</span>
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
        {{-- FACILITIES --}}
        {{-- ====================================== --}}

        @php
            $facilityLinks = [
                [
                    'url' => route('admin.infrastructure.index'),
                    'label' => 'Rooms Layout',
                    'icon' => 'map',
                    'active' => request()->is('admin/infrastructure*'),
                ],
                [
                    'url' => route('admin.rooms.index'),
                    'label' => 'Rooms',
                    'icon' => 'door-open',
                    'active' => request()->is('admin/rooms*'),
                ],
                [
                    'url' => route('admin.departments.index'),
                    'label' => 'Departments',
                    'icon' => 'network',
                    'active' => request()->is('admin/departments*'),
                ],
            ];
            $facilitiesActive = collect($facilityLinks)->contains('active', true);
        @endphp

        <div class="menu-title" id="facilities-section">

            FACILITIES

        </div>

        <div class="menu-group {{ $facilitiesActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $facilitiesActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $facilitiesActive ? 'true' : 'false' }}"
            >
                <i data-lucide="building-2"></i>
                <span>Location</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $facilitiesActive) hidden @endif>
                @foreach($facilityLinks as $link)
                    <a href="{{ $link['url'] }}" class="menu-sub-item {{ $link['active'] ? 'active' : '' }}">
                        <i data-lucide="{{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <a
            href="{{ route('admin.equipment-all.index') }}"
            class="menu-item {{ request()->is('admin/equipment/all*') ? 'active' : '' }}"
        >

            <i data-lucide="layers"></i>

            <span>All Equipment</span>

        </a>

        <a
            href="{{ route('admin.inventory.index') }}"
            class="menu-item {{ request()->is('admin/inventory') || (request()->is('admin/inventory/*') && ! request()->is('admin/inventory/transfer-requests*', 'admin/inventory/deployed*')) ? 'active' : '' }}"
        >

            <i data-lucide="package"></i>

            <span>Inventory of Equipment</span>

        </a>

        <a
            href="{{ route('admin.inventory.deployed') }}"
            class="menu-item {{ request()->is('admin/inventory/deployed*') ? 'active' : '' }}"
        >

            <i data-lucide="boxes"></i>

            <span>Deployed Equipment</span>

        </a>

        @php
            $pendingTransferApprovals = \App\Support\TransferRequests::pendingCount();
        @endphp
        <a
            href="{{ route('admin.inventory.transfer-requests') }}"
            class="menu-item {{ request()->is('admin/inventory/transfer-requests*') ? 'active' : '' }}"
        >

            <i data-lucide="shield-check"></i>

            <span>Transfer Approvals</span>

            @if ($pendingTransferApprovals > 0)
                <span class="ml-auto rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $pendingTransferApprovals }}</span>
            @endif

        </a>

        @php
            $pendingDisposalApprovals = \App\Support\DisposalRequests::pendingCount();
        @endphp
        <a
            href="{{ route('admin.disposal.index') }}"
            class="menu-item {{ request()->is('admin/disposal*') ? 'active' : '' }}"
            @if ($pendingDisposalApprovals > 0) title="{{ $pendingDisposalApprovals }} disposal request(s) waiting for approval" @endif
        >

            <i data-lucide="trash-2"></i>

            <span>Disposal</span>

            @if ($pendingDisposalApprovals > 0)
                <span class="ml-auto rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $pendingDisposalApprovals }}</span>
            @endif

        </a>

        <a
            href="{{ route('admin.qr-tools.index') }}"
            class="menu-item {{ request()->is('admin/qr-tools*') ? 'active' : '' }}"
        >

            <i data-lucide="qr-code"></i>

            <span>Property Tagging</span>

        </a>

        <a
            href="{{ route('admin.equipment-categories.index') }}"
            class="menu-item {{ request()->is('admin/equipment/categories*') ? 'active' : '' }}"
        >

            <i data-lucide="tags"></i>

            <span>Classification</span>

        </a>

        <a
            href="{{ route('admin.suggested-issues.index') }}"
            class="menu-item {{ request()->is('admin/equipment/suggested-issues*') ? 'active' : '' }}"
        >

            <i data-lucide="list-checks"></i>

            <span>Suggested Issues</span>

        </a>

        <a
            href="{{ route('admin.equipment-history.index') }}"
            class="menu-item {{ request()->is('admin/equipment/history*') ? 'active' : '' }}"
        >

            <i data-lucide="history"></i>

            <span>Equipment History</span>

        </a>

        <a
            href="{{ route('admin.schedules.index') }}"
            class="menu-item {{ request()->is('admin/schedules*', 'admin/semester-inspections*') ? 'active' : '' }}"
        >

            <i data-lucide="calendar-days"></i>

            <span>Maintenance Schedule</span>

        </a>

        

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
            <span>Monitor Procurement</span>
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

        <a
            href="{{ route('admin.reporters.index') }}"
            class="menu-item {{ request()->is('admin/reporters*') ? 'active' : '' }}"
        >

            <i data-lucide="contact"></i>

            <span>Equipment Reporters</span>

        </a>

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
