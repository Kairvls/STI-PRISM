<div id="sidebar">
    <div class="sidebar-header p-5">
        <div class="logo-icon">
            <img src="{{ asset('image/Paayo_Sidebar.png') }}" alt="PaAyo Logo">
        </div>
        <div class="min-w-0">
            <h2>PaAyo</h2>
            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('supply-officer') }}</span>
        </div>
    </div>

    <div class="sidebar-content">
        <div class="menu-title" id="dashboard-section">DASHBOARD</div>
        <a
            href="{{ route('supply-officer.dashboard') }}"
            class="menu-item {{ request()->routeIs('supply-officer.dashboard') ? 'active' : '' }}"
        >
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-title" id="procurement-section">PROCUREMENT</div>
        <a
            href="{{ route('supply-officer.incoming-ris') }}"
            class="menu-item {{ request()->routeIs('supply-officer.incoming-ris*') ? 'active' : '' }}"
        >
            <i data-lucide="inbox"></i>
            <span>Incoming RIS</span>
        </a>
        <a
            href="{{ route('supply-officer.atp.index') }}"
            class="menu-item {{ request()->routeIs('supply-officer.atp*') ? 'active' : '' }}"
        >
            <i data-lucide="file-check-2"></i>
            <span>Authority to Purchase</span>
        </a>

        <div class="menu-title" id="account-section">ACCOUNT</div>
        <a
            href="{{ route('supply-officer.notifications') }}"
            class="menu-item {{ request()->routeIs('supply-officer.notifications*') ? 'active' : '' }}"
        >
            <i data-lucide="bell"></i>
            <span>Notifications</span>
        </a>
        <a
            href="{{ route('supply-officer.profile') }}"
            class="menu-item {{ request()->is('supply-officer/profile*') || request()->is('supply-officer/security*') ? 'active' : '' }}"
        >
            <i data-lucide="user-cog"></i>
            <span>Account settings</span>
        </a>
    </div>
</div>

@include('layouts.partials.admin-sidebar-assets')
