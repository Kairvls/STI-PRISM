<div id="sidebar"
    class="w-[320px] min-h-screen bg-[#0F172A] border-r border-white/10 text-white overflow-y-auto">

    <!-- LOGO -->
    <div class="h-[85px] px-6 flex items-center border-b border-white/10">

        <div class="flex items-center gap-4">

            <div class="w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center">

                <i data-lucide="shield-check"
                   class="w-7 h-7 text-white"></i>

            </div>

            <div>

                <h1 class="text-2xl font-extrabold">
                    PRISM
                </h1>

                <p class="text-sm text-gray-400">
                    Maintenance Personnel
                </p>

            </div>

        </div>

    </div>

    <!-- SIDEBAR CONTENT -->
    <div class="p-5 space-y-4">

        <!-- DASHBOARD -->
        <a href="/maintenance/dashboard"
            class="sidebar-active">

            <i data-lucide="layout-dashboard"></i>

            Dashboard

        </a>

        <!-- REPORT MANAGEMENT -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('reportManagementDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="clipboard-list"></i>

                    Report Management

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="reportManagementDropdown"
                class="sidebar-dropdown-content">

                <a href="/maintenance/reports/incoming"
                    class="sidebar-link">
                    Incoming Reports
                </a>

                <a href="/maintenance/reports/urgent"
                    class="sidebar-link">
                    Urgent Reports
                </a>

                <a href="/maintenance/reports/pending"
                    class="sidebar-link">
                    Pending Reports
                </a>

                <a href="/maintenance/reports/processing"
                    class="sidebar-link">
                    Processing Reports
                </a>

                <a href="/maintenance/reports/resolved"
                    class="sidebar-link">
                    Resolved Reports
                </a>

                <a href="/maintenance/reports/replacement"
                    class="sidebar-link">
                    Replacement Reports
                </a>

                <a href="/maintenance/reports/rejected"
                    class="sidebar-link">
                    Rejected Reports
                </a>

            </div>

        </div>

        <!-- EQUIPMENT MANAGEMENT -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('equipmentManagementDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="package"></i>

                    Equipment Management

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="equipmentManagementDropdown"
                class="sidebar-dropdown-content">

                <a href="#" class="sidebar-link">
                    All Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Damaged Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Under Maintenance
                </a>

                <a href="#" class="sidebar-link">
                    For Replacement
                </a>

                <a href="#" class="sidebar-link">
                    Disposed Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Inventory Monitoring
                </a>

                <a href="#" class="sidebar-link">
                    QR Code Generator
                </a>

                <a href="#" class="sidebar-link">
                    Equipment Transfer
                </a>

                <a href="#" class="sidebar-link">
                    Equipment Location History
                </a>

            </div>

        </div>

        <!-- QR MONITORING -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('qrMonitoringDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="qr-code"></i>

                    QR Monitoring

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="qrMonitoringDropdown"
                class="sidebar-dropdown-content">

                <a href="#" class="sidebar-link">
                    Scan Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Equipment Information
                </a>

                <a href="#" class="sidebar-link">
                    QR History
                </a>

                <a href="#" class="sidebar-link">
                    Mobile Monitoring
                </a>

            </div>

        </div>

        <!-- BORROWING MANAGEMENT -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('borrowingManagementDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="clipboard-check"></i>

                    Borrowing Management

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="borrowingManagementDropdown"
                class="sidebar-dropdown-content">

                <a href="#" class="sidebar-link">
                    Borrowed Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Returned Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Overdue Equipment
                </a>

                <a href="#" class="sidebar-link">
                    Borrowing History
                </a>

                <a href="#" class="sidebar-link">
                    Return Equipment
                </a>

            </div>

        </div>

        <!-- MAINTENANCE SCHEDULES -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('maintenanceSchedulesDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="calendar-days"></i>

                    Maintenance Schedules

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="maintenanceSchedulesDropdown"
                class="sidebar-dropdown-content">

                <a href="#" class="sidebar-link">
                    Schedule List
                </a>

                <a href="#" class="sidebar-link">
                    Calendar
                </a>

                <a href="#" class="sidebar-link">
                    Overdue Maintenance
                </a>

                <a href="#" class="sidebar-link">
                    Maintenance Notifications
                </a>

            </div>

        </div>

        <!-- BUILDINGS & ROOMS -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('buildingsRoomsDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="building-2"></i>

                    Buildings & Rooms

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="buildingsRoomsDropdown"
                class="sidebar-dropdown-content">

                <a href="/maintenance/buildings"
                    class="sidebar-link">

                    Buildings

                </a>

                <a href="/maintenance/floors"
                    class="sidebar-link">

                    Floors

                </a>

                <a href="/maintenance/rooms"
                    class="sidebar-link">

                    Rooms

                </a>

                <a href="/maintenance/rooms/equipment"
                    class="sidebar-link">

                    Room Equipment

                </a>

                <a href="/maintenance/rooms/history"
                    class="sidebar-link">

                    Room Maintenance History

                </a>

                <a href="/maintenance/rooms/statistics"
                    class="sidebar-link">

                    Room Statistics

                </a>

            </div>

        </div>

        <!-- DISPOSAL MANAGEMENT -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('disposalManagementDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="trash-2"></i>

                    Disposal Management

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="disposalManagementDropdown"
                class="sidebar-dropdown-content">

                <a href="#" class="sidebar-link">
                    Disposal Records
                </a>

                <a href="#" class="sidebar-link">
                    Disposal History
                </a>

                <a href="#" class="sidebar-link">
                    Disposal Remarks
                </a>

            </div>

        </div>

        <!-- REPORTERS -->
        <div class="sidebar-dropdown">

            <button onclick="toggleDropdown('reportersDropdown')"
                class="sidebar-dropdown-button">

                <div class="flex items-center gap-3">

                    <i data-lucide="users"></i>

                    Reporters

                </div>

                <i data-lucide="chevron-down"></i>

            </button>

            <div id="reportersDropdown"
                class="sidebar-dropdown-content">

                <a href="#" class="sidebar-link">
                    Reporters List
                </a>

            </div>

        </div>

    </div>

</div>

<style>

.sidebar-active{

    display:flex;
    align-items:center;
    gap:14px;
    background:#2563EB;
    padding:15px 18px;
    border-radius:18px;
    font-weight:600;

}

.sidebar-dropdown{

    background:#111827;
    border:1px solid rgba(255,255,255,.05);
    border-radius:18px;
    overflow:hidden;

}

.sidebar-dropdown-button{

    width:100%;
    display:flex;
    align-items:center;
    justify-between;
    padding:16px 18px;
    font-weight:600;
    transition:.2s;

}

.sidebar-dropdown-button:hover{

    background:#1E293B;

}

.sidebar-dropdown-content{

    display:none;
    padding:0 10px 12px 10px;

}

.sidebar-link{

    display:block;
    padding:12px 16px;
    border-radius:12px;
    color:#CBD5E1;
    font-size:14px;
    transition:.2s;

}

.sidebar-link:hover{

    background:#1E293B;
    color:white;

}

</style>

