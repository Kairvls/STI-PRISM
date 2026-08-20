<div id="sidebar">
    <div class="sidebar-header p-5">
        <div class="logo-icon">
            <img src="{{ asset('image/STI.png') }}" alt="" />
        </div>
        <div>
            <h2>PRISM</h2>
            <span>President Panel</span>
        </div>
    </div>

    <div class="sidebar-content">
        <div class="sidebar-search">
            <div class="sidebar-dropdown">
                <div id="dropdownTrigger" class="dropdown-trigger">
                    <div class="flex items-center gap-2">
                        <i class="h-5 w-5" data-lucide="search"></i>
                        <span id="selectedSection">Search...</span>
                    </div>
                </div>
                <div id="dropdownMenu" class="dropdown-menu">
                    <div class="dropdown-item" data-target="dashboard-section">Dashboard</div>
                    <div class="dropdown-item" data-target="approvals-section">RIS Approvals</div>
                    <div class="dropdown-item" data-target="reports-section">Reports</div>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <a
                href="/president/approvals"
                class="quick-card {{ request()->is('president/approvals') || request()->is('president/approvals?*') ? 'active' : '' }}"
            >
                <i data-lucide="clipboard-check"></i>
                <span>RIS Approvals</span>
            </a>

            <a
                href="/president/reports/monthly-summary"
                class="quick-card {{ request()->is('president/reports/monthly-summary*') ? 'active' : '' }}"
            >
                <i data-lucide="bar-chart-3"></i>
                <span>Monthly Decisions</span>
            </a>

            <a
                href="/president/reports/approved"
                class="quick-card {{ request()->is('president/reports/approved*') ? 'active' : '' }}"
            >
                <i data-lucide="badge-check"></i>
                <span>History</span>
            </a>
        </div>

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
            href="/president/approvals/history"
            class="menu-item {{ request()->is('president/approvals/history*') ? 'active' : '' }} mt-1"
        >
            <i class="h-5 w-5" data-lucide="history"></i>
            <span>Approval History</span>
        </a>

        <div class="menu-title" id="reports-section">DECISION REPORTS</div>
        <a
            href="/president/reports/approved"
            class="menu-item {{ request()->is('president/reports/approved*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="badge-check"></i>
            <span>History</span>
        </a>
        <a
            href="/president/reports/monthly-summary"
            class="menu-item {{ request()->is('president/reports/monthly-summary*') ? 'active' : '' }} mt-1"
        >
            <i class="h-5 w-5" data-lucide="bar-chart-3"></i>
            <span>Reports & Summary</span>
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
        padding: 20px 20px 32px 20px;
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
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
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

    .sidebar-search {
        position: relative;
        background: #111827;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0;
        height: 38px;
        margin-bottom: 18px;
        font-size: 14px;
    }
    .sidebar-search i {
        width: 14px;
        height: 14px;
        color: #64748b;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 20px;
    }
    .quick-card {
        height: 70px;
        background: #111827;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        color: #cbd5e1;
        transition: all 0.2s ease;
    }
    .quick-card:hover {
        background: #182235;
        border-color: #2563eb;
        transform: translateY(-2px);
    }
    .quick-card i {
        width: 16px;
        height: 16px;
        color: #60a5fa;
        transition: all 0.2s ease;
    }
    .quick-card span {
        font-size: 11px;
        font-weight: 500;
        text-align: center;
        padding: 0 4px;
    }
    .quick-card.active {
        border: 1.5px solid #2563eb !important;
        color: #cbd5e1;
        font-weight: 600;
        box-shadow: 0 0 12px rgba(37, 99, 235, 0.2);
    }
    .quick-card.active i {
        color: #3b82f6;
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

    .section-highlight {
        color: #60a5fa !important;
        text-shadow: 0 0 10px rgba(96, 165, 250, 0.35);
    }

    .sidebar-dropdown {
        width: 100%;
        position: relative;
    }
    .dropdown-trigger {
        width: 100%;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        padding: 0 12px;
    }
    .dropdown-menu {
        display: none;
        position: absolute;
        top: 45px;
        left: 0;
        width: 100%;
        background: #111827;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        overflow: hidden;
        z-index: 999;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }
    .dropdown-item {
        padding: 12px 14px;
        cursor: pointer;
        transition: 0.2s;
        font-size: 14px;
    }
    .dropdown-item:hover {
        background: #1f2937;
        color: #93c5fd;
    }
</style>

<script>
    const trigger = document.getElementById('dropdownTrigger');
    const menu = document.getElementById('dropdownMenu');
    const selected = document.getElementById('selectedSection');
    const sidebarContent = document.querySelector('.sidebar-content');
    const sidebarScrollKey = 'presidentSidebarScrollPosition';

    if (trigger && menu) {
        trigger.addEventListener('click', () => {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
    }

    document.querySelectorAll('.dropdown-item').forEach((item) => {
        item.addEventListener('click', () => {
            if (selected) {
                selected.textContent = item.textContent.trim();
            }

            const target = document.getElementById(item.dataset.target);
            if (target && sidebarContent) {
                const searchArea = document.querySelector('.sidebar-search');
                const searchHeight = searchArea?.offsetHeight ?? 0;
                sidebarContent.scrollTo({
                    top: target.offsetTop - searchHeight - 20,
                    behavior: 'smooth',
                });
                target.classList.add('section-highlight');
                setTimeout(() => target.classList.remove('section-highlight'), 2000);
            }

            if (menu) menu.style.display = 'none';
        });
    });

    document.addEventListener('click', (event) => {
        if (!trigger || !menu) return;
        if (!trigger.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
        }
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
