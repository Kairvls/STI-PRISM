<div id="sidebar">

    <div class="sidebar-header p-5">
        <div class="logo-icon">
            <img src="{{ asset('image/STI.png') }}" alt="" />
        </div>
        <div>
            <h2>PRISM</h2>
            <span>Maintenance System</span>
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
                    <div class="dropdown-item" data-target="dashboard-section">
                        Dashboard
                    </div>
                    <div class="dropdown-item" data-target="reports-section">
                        Reports & Reporters
                    </div>
                    <div
                        class="dropdown-item"
                        data-target="infrastructure-section"
                    >
                        Infrastructure
                    </div>
                    <div class="dropdown-item" data-target="equipment-section">
                        Equipment
                    </div>
                    <div
                        class="dropdown-item"
                        data-target="maintenance-section"
                    >
                        Maintenance
                    </div>
                    <!--<div class="dropdown-item" data-target="users-section">
                        Users
                    </div>-->
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <a
                href="/maintenance/reports/urgent"
                class="quick-card {{ request()->is('maintenance/reports/urgent*') ? 'active' : '' }}"
            >
                <i data-lucide="triangle-alert"></i>
                <span>Urgent Report</span>
            </a>
            <a
                href="/maintenance/schedules/today"
                class="quick-card {{ request()->is('maintenance/schedules/today*') ? 'active' : '' }}"
            >
                <i data-lucide="calendar-days"></i>
                <span>Today's Report</span>
            </a>
            <a
                href="/maintenance/qr-scanner"
                class="quick-card {{ request()->is('maintenance/qr-scanner*') ? 'active' : '' }}"
            >
                <i data-lucide="scan-line"></i>
                <span>Generate QR</span>
            </a>
            <a
                href="/maintenance/notifications"
                class="quick-card {{ request()->is('maintenance/notifications*') ? 'active' : '' }}"
            >
                <i data-lucide="bell"></i>
                <span>Alerts</span>
            </a>
        </div>

        <div class="menu-title" id="dashboard-section">DASHBOARD</div>
        <a
            href="/maintenance/dashboard"
            class="menu-item {{ request()->is('maintenance/dashboard') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-title" id="reports-section">REPORTS & REPORTERS</div>
        <a
            href="/maintenance/reports"
            class="menu-item {{ request()->is('maintenance/reports') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="file-text"></i>
            <span>Reports</span>
        </a>

        <a
            href="/maintenance/reporters"
            class="menu-item {{ request()->is('maintenance/reporters*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="users"></i>
            <span>Reporters</span>
        </a>

        <div class="menu-title" id="infrastructure-section">INFRASTRUCTURE</div>
        <a
            href="/maintenance/infrastructure"
            class="menu-item {{ request()->is('maintenance/infrastructure*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="building-2"></i>
            <span>Buildings & Rooms</span>
        </a>

        <div class="menu-title" id="equipment-section">EQUIPMENT</div>
        <a
            href="/maintenance/equipment/inventory"
            class="menu-item {{ request()->is('maintenance/equipment/inventory*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="package"></i>
            <span>Inventory & Status</span>
        </a>
        <a
            href="/maintenance/equipment/qr-tools"
            class="menu-item {{ request()->is('maintenance/equipment/qr-tools*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="qr-code"></i>
            <span>QR Code Tools</span>
        </a>
        <a
            href="/maintenance/equipment/transfer"
            class="menu-item {{ request()->is('maintenance/equipment/transfer*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="move"></i>
            <span>Transfer & History</span>
        </a>
        <a
            href="/maintenance/borrowing"
            class="menu-item {{ request()->is('maintenance/borrowing*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="clipboard-check"></i>
            <span>Borrowing</span>
        </a>

        <div class="menu-title" id="maintenance-section">MAINTENANCE</div>
        <a
            href="/maintenance/schedules"
            class="menu-item {{ request()->is('maintenance/schedules*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="calendar-days"></i>
            <span>Schedules</span>
        </a>
        <a
            href="/maintenance/disposal"
            class="menu-item {{ request()->is('maintenance/disposal*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="trash-2"></i>
            <span>Disposal</span>
        </a>

        <!--<div class="menu-title" id="users-section">USERS</div>
        <a
            href="/maintenance/reporters"
            class="menu-item {{ request()->is('maintenance/reporters*') ? 'active' : '' }}"
        >
            <i class="h-5 w-5" data-lucide="users"></i>
            <span>Reporters</span>
        </a>-->
    </div>

    <!--<div class="user-card">
        <div class="avatar">AV</div>
        <div>
            <h4>Aljon Vega</h4>
            <p>Maintenance Personnel</p>
        </div>
    </div>-->
</div>

<style>
    /* ======================================
   SIDEBAR
====================================== */
    #sidebar {
        width: 280px;
        height: 100vh;
        background: #0d1120;
        color: white;
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* ======================================
   SCROLLABLE CONTENT
====================================== */
    .sidebar-content {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 20px;
    }
    

    /* ======================================
   SCROLLBAR
====================================== */
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

    /* ======================================
   HEADER
====================================== */
    .sidebar-header {
        display: flex;
        align-items: center;
        gap: 14px;
        /*margin-bottom: 32px;*/
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

    /* SEARCH INPUT */
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

    /* ======================================
   QUICK ACTIONS
====================================== */
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
    }

    /* NEW BLUE ACTIVE STATE STATE FOR QUICK ACTIONS */
    .quick-card.active {
        border: 1.5px solid #2563eb !important;
        color: #cbd5e1;
        font-weight: 600;
        box-shadow: 0 0 12px rgba(37, 99, 235, 0.2);
    }
    .quick-card.active i {
        color: #3b82f6;
    }

    /* ======================================
   SECTION TITLE
====================================== */
    .menu-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #64748b;
        margin-top: 28px;
        margin-bottom: 10px;
        padding-left: 14px;
        transition: all 0.3s ease;
    }

    /* ======================================
   MENU ITEMS
====================================== */
    .menu-item {
        height: 48px;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 0 14px;
        border-radius: 14px;
        color: #cbd5e1;
        text-decoration: none;
        transition: all 0.2s ease;
        margin-bottom: 4px;
        font-size: 14px;
    }
    .menu-item:hover {
        background: #1f2937;
        color: white;
    }
    .menu-item i {
        width: 18px;
        height: 18px;
    }

    /* ======================================
   ACTIVE MENU
====================================== */
    .menu-item.active {
        background: linear-gradient(
            90deg,
            rgba(255, 242, 0, 0.2),
            rgba(255, 242, 0, 0.05)
        );
        border-left: 4px solid #fff200;
        color: #fff200;
        font-weight: 600;
        margin-left: -4px;
    }

    /* ======================================
   USER CARD
====================================== */
    .user-card {
        flex-shrink: 0;
        margin: 16px;
        padding: 14px;
        background: #1a2234;
        border-radius: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .avatar {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
    .user-card h4 {
        font-size: 14px;
        font-weight: 600;
    }
    .user-card p {
        font-size: 12px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .section-highlight {
        color: #fff200 !important;
        text-shadow: 0 0 10px rgba(255, 242, 0, 0.5);
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
        color: #fff200;
    }
</style>

<script>
    const trigger = document.getElementById("dropdownTrigger");
    const menu = document.getElementById("dropdownMenu");
    const selected = document.getElementById("selectedSection");

    trigger.addEventListener("click", () => {
        menu.style.display = menu.style.display === "block" ? "none" : "block";
    });

    document.querySelectorAll(".dropdown-item").forEach((item) => {
        item.addEventListener("click", () => {
            selected.textContent = item.textContent;
            const target = document.getElementById(item.dataset.target);

            if (target) {
                document.querySelector(".sidebar-content").scrollTo({
                    top: target.offsetTop - 20,
                    behavior: "smooth",
                });

                target.classList.add("section-highlight");
                setTimeout(() => {
                    target.classList.remove("section-highlight");
                }, 2000);
            }
            menu.style.display = "none";
        });
    });

    document.addEventListener("click", (e) => {
        if (!trigger.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = "none";
        }
    });
</script>
