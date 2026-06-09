<div id="sidebar"
    class="w-[300px] min-h-screen bg-[#111827] border-r border-white/10 text-white overflow-y-auto">

    <!-- LOGO -->
    <div class="p-6 border-b border-white/10">

        <h1 class="text-2xl font-extrabold">
            PRISM
        </h1>

        <p class="text-sm text-gray-400 mt-1">
            Maintenance Personnel
        </p>

    </div>

    <!-- SIDEBAR CONTENT -->
    <div class="p-5 space-y-6">

        <!-- DASHBOARD -->
        <div>

            <a href="/maintenance/dashboard"
                class="flex items-center gap-3 bg-blue-600 hover:bg-blue-700 px-4 py-3 rounded-xl transition">

                <i data-lucide="layout-dashboard"></i>

                Dashboard

            </a>

        </div>

        <!-- REPORT MANAGEMENT -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Report Management
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Incoming Reports</a>
                <a href="#" class="sidebar-link">Urgent Reports</a>
                <a href="#" class="sidebar-link">Pending Reports</a>
                <a href="#" class="sidebar-link">Processing Reports</a>
                <a href="#" class="sidebar-link">Resolved Reports</a>
                <a href="#" class="sidebar-link">Replacement Reports</a>
                <a href="#" class="sidebar-link">Rejected Reports</a>

            </div>

        </div>

        <!-- EQUIPMENT MANAGEMENT -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Equipment Management
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">All Equipment</a>
                <a href="#" class="sidebar-link">Damaged Equipment</a>
                <a href="#" class="sidebar-link">Under Maintenance</a>
                <a href="#" class="sidebar-link">For Replacement</a>
                <a href="#" class="sidebar-link">Disposed Equipment</a>
                <a href="#" class="sidebar-link">Inventory Monitoring</a>
                <a href="#" class="sidebar-link">QR Code Generator</a>
                <a href="#" class="sidebar-link">Equipment Transfer</a>
                <a href="#" class="sidebar-link">Equipment Location History</a>

            </div>

        </div>

        <!-- QR MONITORING -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                QR Monitoring
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Scan Equipment</a>
                <a href="#" class="sidebar-link">Equipment Information</a>
                <a href="#" class="sidebar-link">QR History</a>
                <a href="#" class="sidebar-link">Mobile Monitoring</a>

            </div>

        </div>

        <!-- BORROWING -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Borrowing Management
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Borrowed Equipment</a>
                <a href="#" class="sidebar-link">Returned Equipment</a>
                <a href="#" class="sidebar-link">Overdue Equipment</a>
                <a href="#" class="sidebar-link">Borrowing History</a>
                <a href="#" class="sidebar-link">Return Equipment</a>

            </div>

        </div>

        <!-- MAINTENANCE -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Maintenance Schedules
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Schedule List</a>
                <a href="#" class="sidebar-link">Calendar</a>
                <a href="#" class="sidebar-link">Overdue Maintenance</a>
                <a href="#" class="sidebar-link">Maintenance Notifications</a>

            </div>

        </div>

        <!-- BUILDINGS -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Building & Rooms
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Buildings</a>
                <a href="#" class="sidebar-link">Floors</a>
                <a href="#" class="sidebar-link">Rooms</a>
                <a href="#" class="sidebar-link">Room Equipment</a>
                <a href="#" class="sidebar-link">Room Maintenance History</a>

            </div>

        </div>

        <!-- DISPOSAL -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Disposal Management
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Disposal Records</a>
                <a href="#" class="sidebar-link">Disposal History</a>
                <a href="#" class="sidebar-link">Disposal Remarks</a>

            </div>

        </div>

        <!-- REPORTERS -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Reporters
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Reporters List</a>

            </div>

        </div>

        <!-- NOTIFICATIONS -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Notifications
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">All Notifications</a>
                <a href="#" class="sidebar-link">Reminders</a>

            </div>

        </div>

        <!-- SETTINGS -->
        <div>

            <h2 class="text-xs font-bold text-gray-400 mb-3 uppercase">
                Settings
            </h2>

            <div class="space-y-2">

                <a href="#" class="sidebar-link">Profile Settings</a>

                <form method="POST" action="{{ route('logout') }}">

                    @csrf

                    <button type="submit"
                        class="w-full text-left text-red-400 hover:text-red-500 px-3 py-2 rounded-lg transition">

                        Logout

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>