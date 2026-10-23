<div id="sidebar">
    <div class="sidebar-header px-5 pt-5 pb-2">
        <div class="logo-icon">
            <img src="{{ asset('image/Paayo_Sidebar.png') }}" alt="" />
        </div>
        <div>   
            <h2>PaAyo</h2>
            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('president') }}</span>
        </div>
    </div>

    <div class="sidebar-content">
        <div class="menu-title" id="dashboard-section">DASHBOARD</div>
        <a
            href="/president/dashboard"
            class="menu-item {{ request()->is('president/dashboard') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-title" id="approvals-section">APPROVALS</div>
        <a
            href="/president/approvals"
            class="menu-item {{ request()->is('president/approvals') && !request()->is('president/approvals/history*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="clipboard-check"></i>
            <span>RIS Approvals</span>
        </a>
        
        <a
            href="/president/direct-approvals"
            class="menu-item {{ request()->is('president/direct-approvals*') ? 'active' : '' }} mt-1"
            id="direct-approvals-section"
        >
            <i class="h-5 w-5" data-lucide="shield-check"></i>
            <span>Admin Direct Approvals</span>
        </a>

        <a
            href="{{ route('president.dv.index') }}"
            class="menu-item {{ request()->is('president/disbursement-vouchers*') ? 'active' : '' }} mt-1"
            id="dv-approvals-section"
        >
            <i class="h-5 w-5" data-lucide="file-text"></i>
            <span>Disbursement Vouchers</span>
        </a>

        <div class="menu-title" id="records-section">RECORDS & ARCHIVES</div>
        <a
            href="/president/procurement-records"
            class="menu-item {{ request()->is('president/procurement-records*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="folder-archive"></i>
            <span>Compiled Records</span>
        </a>

        @php
            $reportsMenuActive = request()->is('president/reports/approved*')
                || request()->is('president/reports/monthly-summary*')
                || request()->is('president/approvals/history*');
        @endphp
        <div class="menu-title" id="reports-section">ANALYTICS & INSIGHTS</div>
        <div class="menu-group {{ $reportsMenuActive ? 'is-open' : '' }}" data-menu-group @if($reportsMenuActive) data-force-open="1" @endif>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $reportsMenuActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $reportsMenuActive ? 'true' : 'false' }}"
                aria-controls="reports-section-panel"
            >
                <i data-lucide="bar-chart-3"></i>
                <span>Decision Reports</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" id="reports-section-panel" @if(! $reportsMenuActive) hidden @endif>
                <a
                    href="/president/reports/approved"
                    class="menu-sub-item {{ request()->is('president/reports/approved*') ? 'active' : '' }}"
                >
                    <i data-lucide="badge-check"></i>
                    <span>History</span>
                </a>
                <a
                    href="/president/reports/monthly-summary"
                    class="menu-sub-item {{ request()->is('president/reports/monthly-summary*') ? 'active' : '' }}"
                >
                    <i data-lucide="pie-chart"></i>
                    <span>Reports & Summary</span>
                </a>
                <a
                    href="/president/approvals/history"
                    class="menu-sub-item {{ request()->is('president/approvals/history*') ? 'active' : '' }}"
                >
                    <i data-lucide="history"></i>
                    <span>Approval History</span>
                </a>
            </div>
        </div>

        <!--<div class="menu-title" id="account-section">ACCOUNT</div>
        <a
            href="{{ route('president.profile') }}"
            class="menu-item {{ request()->is('president/profile*') || request()->is('president/security*') ? 'active' : '' }} mt-1"
        >
            <i class="h-5 w-5" data-lucide="user-cog"></i>
            <span>Account settings</span>
        </a>-->
    </div>
</div>

<style>
    #sidebar {
        width: 280px;
        height: 100vh;
        max-height: 100vh;
        min-height: 0;
        overflow: hidden;
        background: #0d1120;
        color: white;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }

    .sidebar-content {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0 20px 32px 20px;
        overscroll-behavior: contain;
    }

    .sidebar-content::-webkit-scrollbar {
        width: 6px;
    }
    .sidebar-content::-webkit-scrollbar-thumb {
        background: #2d3748;
        border-radius: 999px;
    }
    .sidebar-content::-webkit-scrollbar-thumb:hover {
        background: #4a5568;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .logo-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        background: #FFF200;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .logo-icon img {
        width: 100%;
        height: 100%;
        border-radius: 14px;
    }
    .sidebar-header h2 {
        font-size: 20px;
        font-weight: 700;
    }
    .sidebar-header span {
        font-size: 13px;
        color: #94a3b8;
    }

    .sidebar-content > .menu-title:first-child {
        margin-top: 0;
    }

    .menu-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #64748b;
        margin-top: 28px;
        margin-bottom: 10px;
        padding-left: 0;
        transition: all 0.3s ease;
    }

    .menu-group {
        margin-top: 0;
    }

    .menu-group-toggle {
        width: 100%;
        border: 0;
        background: transparent;
        cursor: pointer;
        text-align: left;
        font-family: inherit;
    }

    .menu-group-toggle .menu-group-chevron {
        margin-left: auto;
        width: 16px;
        height: 16px;
        color: #64748b;
        transition: transform 0.2s ease, color 0.2s ease;
    }

    .menu-group.is-open > .menu-group-toggle .menu-group-chevron {
        transform: rotate(180deg);
        color: #94a3b8;
    }

    .menu-group-toggle.active-parent,
    .menu-group-toggle.active-parent span {
        color: #ffffff;
        font-weight: 600;
    }

    .menu-group-toggle.active-parent svg:not(.menu-group-chevron) {
        color: #60a5fa;
        stroke: #60a5fa;
    }

    .menu-sub {
        display: grid;
        gap: 2px;
        margin: 0 0 6px 8px;
        padding: 2px 0 4px 12px;
        border-left: 1px solid rgba(148, 163, 184, 0.22);
    }

    .menu-sub[hidden] {
        display: none;
    }

    .menu-sub-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 36px;
        padding: 0 4px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 13px;
    }

    .menu-sub-item svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
        color: inherit;
    }

    .menu-sub-item:hover {
        color: #ffffff;
    }

    .menu-sub-item.active {
        color: #ffffff;
        font-weight: 600;
    }

    .menu-sub-item.active svg {
        color: #60a5fa;
        stroke: #60a5fa;
    }

    .menu-item {
        position: relative;
        height: 48px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0;
        border-radius: 0;
        color: #94a3b8;
        text-decoration: none;
        font-size: 14px;
        font-weight: 400;
        margin-bottom: 2px;
        transition: color 0.2s ease, opacity 0.2s ease;
    }

    .menu-item svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        color: #94a3b8;
        stroke: currentColor;
        transition: color 0.2s ease;
    }

    .menu-item span {
        color: inherit;
    }

    .menu-item:hover {
        color: #ffffff;
        background: transparent;
    }

    .menu-item:hover svg {
        color: #ffffff;
        stroke: #ffffff;
    }

    .menu-item.active {
        background: transparent;
        color: #ffffff;
        font-weight: 600;
    }

    .menu-item.active::before {
        content: "";
        position: absolute;
        left: -20px;
        top: 50%;
        transform: translateY(-50%);
        width: 5px;
        height: 32px;
        background: #2563eb;
        border-radius: 0 5px 5px 0;
    }

    .menu-item.active svg {
        color: #60a5fa !important;
        stroke: #60a5fa !important;
    }

    .menu-item.active span {
        color: #ffffff;
    }

</style>

<script>
    const sidebarContent = document.querySelector('#sidebar .sidebar-content');
    const sidebarScrollKey = 'presidentSidebarScrollPosition';

    if (sidebarContent) {
        sidebarContent.addEventListener('scroll', () => {
            sessionStorage.setItem(sidebarScrollKey, sidebarContent.scrollTop);
        });
    }

    document.querySelectorAll('[data-menu-group]').forEach((group) => {
        const toggle = group.querySelector('[data-menu-group-toggle]');
        const panel = group.querySelector('.menu-sub');
        if (!toggle || !panel) return;

        const savedOpen = sessionStorage.getItem('presidentSidebarReportsOpen');
        if (group.dataset.forceOpen === '1' || savedOpen === '1') {
            group.classList.add('is-open');
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        } else if (savedOpen === '0') {
            group.classList.remove('is-open');
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', () => {
            const willOpen = !group.classList.contains('is-open');
            group.classList.toggle('is-open', willOpen);
            panel.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            sessionStorage.setItem('presidentSidebarReportsOpen', willOpen ? '1' : '0');
        });
    });

    window.addEventListener('DOMContentLoaded', () => {
        if (!sidebarContent) return;

        const savedScrollPosition = sessionStorage.getItem(sidebarScrollKey);
        if (savedScrollPosition !== null) {
            sidebarContent.scrollTop = Number(savedScrollPosition);
        }

        const activeMenuItem = document.querySelector('.menu-item.active, .menu-sub-item.active');
        if (!activeMenuItem) return;

        requestAnimationFrame(() => {
            const sidebarRect = sidebarContent.getBoundingClientRect();
            const itemRect = activeMenuItem.getBoundingClientRect();
            const topPadding = 20;
            const bottomPadding = 20;

            if (itemRect.top < sidebarRect.top + topPadding) {
                sidebarContent.scrollTop -= sidebarRect.top + topPadding - itemRect.top;
            } else if (itemRect.bottom > sidebarRect.bottom - bottomPadding) {
                sidebarContent.scrollTop += itemRect.bottom - sidebarRect.bottom + bottomPadding;
            }

            sessionStorage.setItem(sidebarScrollKey, sidebarContent.scrollTop);
        });
    });
</script>
