{{-- School Administrator sidebar — same design as the Administrator sidebar --}}
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

            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('school-admin') }}</span>

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

                    <div class="dropdown-item" data-target="procurement-section">
                        Procurement
                    </div>

                    <div class="dropdown-item" data-target="signature-section">
                        Digital Signatures
                    </div>

                    <div class="dropdown-item" data-target="monitoring-section">
                        Monitoring
                    </div>

                    <div class="dropdown-item" data-target="settings-section">
                        Account
                    </div>

                </div>

            </div>

        </div>

        {{-- ====================================== --}}
        {{-- QUICK ACTIONS --}}
        {{-- ====================================== --}}

        <div class="quick-actions">

            <a
                href="{{ route('school-admin.procurement-review') }}"
                class="quick-card {{ request()->is('school-admin/procurement-review*') ? 'active' : '' }}"
            >

                <i data-lucide="clipboard-check"></i>

                <span>Review RIS</span>

            </a>

            <a
                href="{{ route('school-admin.digital-signatures.sign-ris') }}"
                class="quick-card {{ request()->is('school-admin/digital-signatures/sign-ris*') ? 'active' : '' }}"
            >

                <i data-lucide="pen-tool"></i>

                <span>Sign RIS</span>

            </a>

            <a
                href="{{ route('school-admin.operations.equipment') }}"
                class="quick-card {{ request()->is('school-admin/operations/equipment*') ? 'active' : '' }}"
            >

                <i data-lucide="monitor"></i>

                <span>Equipment</span>

            </a>

            <a
                href="{{ route('school-admin.semester-inspections.index') }}"
                class="quick-card {{ request()->is('school-admin/semester-inspections*') ? 'active' : '' }}"
            >

                <i data-lucide="clipboard-list"></i>

                <span>Inspections</span>

            </a>

        </div>

        {{-- ====================================== --}}
        {{-- DASHBOARD --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="dashboard-section">

            DASHBOARD

        </div>

        <a
            href="{{ route('school-admin.dashboard') }}"
            class="menu-item {{ request()->is('school-admin/dashboard') ? 'active' : '' }}"
        >

            <i data-lucide="layout-dashboard"></i>

            <span>Dashboard</span>

        </a>

        {{-- ====================================== --}}
        {{-- PROCUREMENT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="procurement-section">

            PROCUREMENT

        </div>

        <a
            href="{{ route('school-admin.procurement-review') }}"
            class="menu-item {{ request()->is('school-admin/procurement-review*') ? 'active' : '' }}"
        >

            <span class="menu-icon-wrap">
                <i data-lucide="clipboard-check"></i>
                @if(($adminSidebarPendingRis ?? 0) > 0)
                    <span class="menu-notif-dot" title="{{ $adminSidebarPendingRis }} pending accept"></span>
                @endif
            </span>

            <span>Procurement Requests</span>

        </a>

        {{-- ====================================== --}}
        {{-- DIGITAL SIGNATURES --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="signature-section">

            DIGITAL SIGNATURES

        </div>

        <a
            href="{{ route('school-admin.digital-signatures.sign-ris') }}"
            class="menu-item {{ request()->is('school-admin/digital-signatures/sign-ris*') ? 'active' : '' }}"
        >

            <span class="menu-icon-wrap">
                <i data-lucide="pen-tool"></i>
                @if(($adminSidebarAwaitingCosign ?? 0) > 0)
                    <span class="menu-notif-dot" title="{{ $adminSidebarAwaitingCosign }} awaiting Sign RIS action"></span>
                @endif
            </span>

            <span>Sign RIS</span>

        </a>

        <a
            href="{{ route('school-admin.digital-signatures.history') }}"
            class="menu-item {{ request()->is('school-admin/digital-signatures/history*') ? 'active' : '' }}"
        >

            <i data-lucide="history"></i>

            <span>Signature History</span>

        </a>

        {{-- ====================================== --}}
        {{-- MONITORING --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="monitoring-section">

            MONITORING

        </div>

        <a
            href="{{ route('school-admin.operations.procurement') }}"
            class="menu-item {{ request()->is('school-admin/operations/procurement*') || request()->is('school-admin/operations/documents*') ? 'active' : '' }}"
        >
            <i data-lucide="git-branch"></i>
            <span>Procurement Monitoring</span>
        </a>

        @php
            $equipmentLinks = [
                ['url' => route('school-admin.operations.equipment'), 'active' => request()->is('school-admin/operations/equipment*'), 'icon' => 'monitor', 'label' => 'All Equipment'],
                ['url' => route('school-admin.operations.movements'), 'active' => request()->is('school-admin/operations/movements*'), 'icon' => 'arrow-left-right', 'label' => 'Equipment Movements'],
                ['url' => route('school-admin.operations.schedules'), 'active' => request()->is('school-admin/operations/schedules*'), 'icon' => 'calendar-clock', 'label' => 'Equipment Schedules'],
                ['url' => route('school-admin.semester-inspections.index'), 'active' => request()->is('school-admin/semester-inspections*'), 'icon' => 'clipboard-list', 'label' => 'Semester Inspections'],
            ];
            $equipmentGroupActive = collect($equipmentLinks)->contains('active', true);
        @endphp

        <div class="menu-group {{ $equipmentGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $equipmentGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $equipmentGroupActive ? 'true' : 'false' }}"
            >
                <i data-lucide="radar"></i>
                <span>Equipment Monitoring</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $equipmentGroupActive) hidden @endif>
                @foreach($equipmentLinks as $link)
                    <a href="{{ $link['url'] }}" class="menu-sub-item {{ $link['active'] ? 'active' : '' }}">
                        <i data-lucide="{{ $link['icon'] }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ====================================== --}}
        {{-- ACCOUNT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="settings-section">

            ACCOUNT

        </div>

        <a
            href="{{ route('school-admin.profile') }}"
            class="menu-item {{ request()->is('school-admin/profile*') || request()->is('school-admin/security*') ? 'active' : '' }}"
        >

            <i data-lucide="user-cog"></i>

            <span>Account settings</span>

        </a>

    </div>

</div>

@include('layouts.partials.admin-sidebar-assets')
