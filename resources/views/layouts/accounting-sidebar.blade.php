<div id="sidebar">
    <div class="sidebar-header px-5 pt-5 pb-2">
        <div class="logo-icon">
            <img src="{{ asset('image/Paayo_Sidebar.png') }}" alt="" />
        </div>
        <div>
            <h2>PaAyo</h2>
            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('accounting') }}</span>
        </div>
    </div>

    <div class="sidebar-content">
        <div class="menu-title" id="dashboard-section">DASHBOARD</div>
        <a
            href="/accounting/dashboard"
            class="menu-item {{ request()->is('accounting/dashboard') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-title" id="transactions-section">TRANSACTIONS</div>
        <a
            href="{{ route('accounting.dv.index', ['status' => 'incoming']) }}"
            class="menu-item {{ request()->is('accounting/disbursement-vouchers*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="file-spreadsheet"></i>
            <span>Disbursement Vouchers</span>
        </a>
        <a
            href="/accounting/liquidation-reports?status=incoming"
            class="menu-item {{ request()->is('accounting/liquidation-reports*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="receipt"></i>
            <span>Liquidation Reports</span>
        </a>
        <a
            href="/accounting/procurement-records"
            class="menu-item {{ request()->is('accounting/procurement-records*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="folder-archive"></i>
            <span>Compiled Records</span>
        </a>

        <div class="menu-title" id="history-section">HISTORY</div>
        <a
            href="/accounting/history"
            class="menu-item {{ request()->is('accounting/history*') || request()->is('accounting/financial-records*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="history"></i>
            <span>History</span>
        </a>
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

    .sidebar-content::-webkit-scrollbar { width: 6px; }
    .sidebar-content::-webkit-scrollbar-thumb {
        background: #2d3748;
        border-radius: 999px;
    }
    .sidebar-content::-webkit-scrollbar-thumb:hover { background: #4a5568; }

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
    .sidebar-content > .menu-title:first-child {
        margin-top: 0;
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
    .menu-item span { color: inherit; }
    .menu-item:hover { color: #ffffff; background: transparent; }
    .menu-item:hover svg { color: #ffffff; stroke: #ffffff; }
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
        background: #fff200;
        border-radius: 0 5px 5px 0;
    }
    .menu-item.active svg {
        color: #fff200 !important;
        stroke: #fff200 !important;
    }
    .menu-item.active span { color: #ffffff; }

    .menu-group {
        margin-bottom: 2px;
    }
    .menu-group-toggle {
        width: 100%;
        border: 0;
        background: transparent;
        cursor: pointer;
        text-align: left;
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
    .menu-group-toggle.active-parent::before {
        content: "";
        position: absolute;
        left: -20px;
        top: 50%;
        transform: translateY(-50%);
        width: 5px;
        height: 32px;
        background: #fff200;
        border-radius: 0 5px 5px 0;
    }
    .menu-group-toggle.active-parent svg:not(.menu-group-chevron) {
        color: #fff200 !important;
        stroke: #fff200 !important;
    }
    .menu-sub {
        display: grid;
        gap: 2px;
        padding: 2px 0 8px 18px;
        border-left: 1px solid rgba(148, 163, 184, 0.18);
        margin: 0 0 4px 8px;
    }
    .menu-sub[hidden] {
        display: none;
    }
    .menu-sub-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 38px;
        padding: 0 8px 0 4px;
        border-radius: 8px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 13px;
        font-weight: 400;
        transition: color 0.2s ease, background 0.2s ease;
    }
    .menu-sub-item svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
        color: inherit;
    }
    .menu-sub-item:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.04);
    }
    .menu-sub-item.active {
        color: #fff200;
        font-weight: 600;
        background: rgba(255, 242, 0, 0.1);
    }
    .menu-sub-item.active span {
        color: #fff200;
    }
    .menu-sub-item.active svg {
        color: #fff200;
        stroke: #fff200;
    }

    .section-highlight {
        color: #fff200 !important;
        text-shadow: 0 0 10px rgba(255, 242, 0, 0.35);
    }
</style>

<script>
    const sidebarContent = document.querySelector('#sidebar .sidebar-content');
    const sidebarScrollKey = 'accountingSidebarScrollPosition';

    document.querySelectorAll('[data-menu-group]').forEach((group) => {
        const toggle = group.querySelector('[data-menu-group-toggle]');
        const panel = group.querySelector('.menu-sub');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', () => {
            const willOpen = !group.classList.contains('is-open');
            group.classList.toggle('is-open', willOpen);
            panel.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    });

    if (sidebarContent) {
        sidebarContent.addEventListener('scroll', () => {
            sessionStorage.setItem(sidebarScrollKey, sidebarContent.scrollTop);
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (!sidebarContent) return;
        const savedScrollPosition = sessionStorage.getItem(sidebarScrollKey);
        if (savedScrollPosition !== null) {
            sidebarContent.scrollTop = Number(savedScrollPosition);
        }
        const activeMenuItem = document.querySelector('.menu-item.active');
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
