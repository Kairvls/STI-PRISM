<div class="topbar">
    <div class="topbar-left">
        <button onclick="toggleSidebar()" class="mobile-sidebar-btn" type="button" aria-label="Open navigation menu" aria-controls="sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        @php
            $moduleHeading = match (true) {
                request()->is('school-admin/dashboard') => ['Dashboard', ''],
                request()->is('school-admin/operations/equipment*') => ['All Equipment', ''],
                request()->is('school-admin/operations/schedules*') => ['Schedules Monitor', ''],
                request()->is('school-admin/operations/borrowing*') => ['Borrowed Equipment', ''],
                request()->is('school-admin/operations/movements*') && request('tab') === 'disposal' => ['Disposal', ''],
                request()->is('school-admin/operations/movements*') => ['Equipment Movements', ''],
                request()->is('school-admin/semester-inspections/*') => [View::yieldContent('title', 'Semester Inspection'), 'View only.'],
                request()->is('school-admin/semester-inspections') => ['Semester Inspections', ''],
                request()->is('school-admin/equipment/history*') => ['Equipment History', 'Reports, transfers and maintenance for each item.'],
                request()->is('school-admin/disposal*') => ['Disposed Equipment', 'View only. Equipment that has been permanently disposed.'],
                request()->is('school-admin/operations/procurement*', 'school-admin/operations/documents*') => ['Procurement Monitor', 'Track each Request No. (PR) through the procurement pipeline.'],
                request()->is('school-admin/procurement-review*') => ['RIS', 'Pending Accept — then Ready to Sign for Direct Approve or Pass to President.'],
                request()->is('school-admin/authority-to-purchase*') => ['Authority to Purchase', ''],
                request()->is('school-admin/purchase-orders*') => ['Purchase Orders', ''],
                request()->is('school-admin/request-check*') => ['Request Checks', ''],
                request()->is('school-admin/digital-signatures/sign-ris*') => ['RIS', 'Ready to Sign — Direct Approve, Pass to President, or Issued by.'],
                request()->is('school-admin/digital-signatures/history*') => ['Signature History', ''],
                request()->is('school-admin/notifications*') => ['Notifications', ''],
                request()->is('school-admin/profile*') => ['Profile Settings', ''],
                request()->is('school-admin/security*') => ['Security Settings', ''],
                request()->is('school-admin/*') => [View::yieldContent('title', 'PaAyo'), ''],
                request()->is('admin/procurement-review*') => ['RIS', 'View only. The School Administrator accepts and decides.'],
                request()->is('admin/digital-signatures/sign-ris*') => ['RIS', 'View only. The School Administrator signs and decides.'],
                request()->is('admin/dashboard') => ['Dashboard', ''],
                request()->is('admin/operations') && ! request()->is('admin/operations/*') => ['Command Center', ''],
                request()->is('admin/operations/equipment*') => ['Equipment Monitor', ''],
                request()->is('admin/operations/schedules*') => ['Maintenance Schedules', ''],
                request()->is('admin/operations/reports*') => ['Equipment Reports', 'Monitor reports and override when needed.'],
                request()->is('admin/operations/procurement*') => ['Procurement Monitor', 'Track each Request No. (PR) through the procurement pipeline.'],
                request()->is('admin/operations/back-orders*') && request('payment') === 'rfc' => ['Back Order Monitor · Request for Check', 'Items delivered short or damaged.'],
                request()->is('admin/operations/back-orders*') && request('payment') === 'ca' => ['Back Order Monitor · Cash Advance', 'Items delivered short or damaged.'],
                request()->is('admin/operations/back-orders*') => ['Back Order Monitor', 'Items delivered short or damaged.'],
                request()->is('admin/borrowing*') => ['Borrowing', 'Lend equipment and record returns.'],
                request()->is('admin/qr-tools*') => ['Property Tagging', 'Generate, print and download equipment QR code & labels.'],
                request()->is('admin/equipment/categories*') => ['Classification', 'Equipment classification used across inventory and reports.'],
                request()->is('admin/equipment/suggested-issues/*/checks*') => ['Quick Checks', 'Steps reporters try before sending a report.'],
                request()->is('admin/equipment/suggested-issues*') => ['Suggested Issues', 'Common issues used when filing reports.'],
                request()->is('admin/equipment/history*') => ['Equipment History', 'Reports, transfers and maintenance for each item.'],
                request()->is('admin/schedules/rooms*') => ['Maintenance Schedule', 'Room inspections: recurring checks of a whole room.'],
                request()->is('admin/schedules*') => ['Maintenance Schedule', 'Equipment maintenance: per-asset schedules.'],
                request()->is('admin/semester-inspections/*') => [View::yieldContent('title', 'Campaign'), 'Maintenance Schedule · Semestral inspection walkthrough.'],
                request()->is('admin/semester-inspections') => ['Maintenance Schedule', 'Semestral inspections: campus-wide equipment campaigns.'],
                request()->is('admin/operations/borrowing*') => ['Borrowed Equipment', ''],
                request()->is('admin/operations/movements*') && request('tab') === 'disposal' => ['Disposal', ''],
                request()->is('admin/operations/movements*') => ['Transfers', ''],
                request()->is('admin/request-check*') => ['Request Checks', ''],
                request()->is('admin/liquidation-reports*') => ['Liquidation Reports', ''],
                request()->is('admin/digital-signatures/history*') => ['Signature History', ''],
                request()->is('admin/users*') => ['User Management', 'Assign primary and additional roles.'],
                request()->is('admin/reporters*') => ['Equipment Reporters', 'Faculty and staff who can report equipment issues.'],
                request()->is('admin/infrastructure*') => ['Building Rooms Layout', ''],
                request()->is('admin/rooms*') => ['Rooms', ''],
                request()->is('admin/departments*') => ['Departments', 'Offices that own equipment and group people.'],
                request()->is('admin/persons*') => ['Faculty & Staff', 'Campus faculty and staff — identity source for other modules.'],
                request()->is('school-admin/persons*') => ['Faculty & Staff', 'View-only campus identity details.'],
                request()->is('admin/reports/make*') => ['Make Report', ''],
                request()->is('admin/reports*') => ['System Reports', 'Maintenance, procurement, receiving, and approval history.'],
                request()->is('admin/notifications*') => ['Notifications', ''],
                request()->is('admin/profile*') => ['Profile Settings', ''],
                request()->is('admin/security*') => ['Security Settings', ''],
                request()->is('admin/settings*') => ['System Settings', ''],
                request()->is('purchaser/procurement/replacement-requests*') => ['Replacement Requests', ''],
                request()->is('purchaser/ris*') => ['RIS', 'Requisition and Issue Slips.'],
                request()->is('purchaser/authority-to-purchase*') => ['Authority to Purchase', ''],
                request()->is('purchaser/purchase-orders*') => ['Purchase Orders', 'Group draft ATPs and submit to the School Administrator.'],
                request()->is('purchaser/request-check*') && request('fund') === 'cash_advance' => ['Cash Advance', ''],
                request()->is('purchaser/request-check*') && request('fund') === 'request_for_check' => ['Request for Check', ''],
                request()->is('purchaser/request-check*') => ['Request Fund', ''],
                request()->is('purchaser/receiving-reports*') => ['Receiving Reports', ''],
                request()->is('purchaser/back-orders*') && request('payment') === 'rfc' => ['Back Orders · Request for Check', 'Items delivered short or damaged.'],
                request()->is('purchaser/back-orders*') && request('payment') === 'ca' => ['Back Orders · Cash Advance', 'Items delivered short or damaged.'],
                request()->is('purchaser/back-orders*') => ['Back Orders', 'Items delivered short or damaged.'],
                request()->is('purchaser/liquidation-reports*') => ['Liquidation Reports', ''],
                request()->is('purchaser/procurement-records*') => ['Procurement Records', ''],
                request()->is('purchaser/suppliers*') => ['Suppliers', ''],
                request()->is('purchaser/file-maintenance*', 'purchaser/brands*', 'purchaser/items*', 'purchaser/uom*', 'purchaser/categories*', 'purchaser/subcategories*') => ['File Maintenance', 'Brands, items, units of measure and item categories.'],
                request()->is('maintenance/personnel-directory*') => ['Reporters Directory', 'Faculty and staff reporters and their report records.'],
                request()->is('maintenance/property-assignments', 'admin/property-assignments') => ['Property Assignment', ''],
                request()->is('maintenance/property-assignments/*', 'admin/property-assignments/*') => [View::yieldContent('title', 'Property Assignment'), 'Items this person or office is accountable for.'],
                request()->is('maintenance/equipment/deployed*', 'admin/inventory/deployed*') => ['Deployed Equipment', 'View, transfer and track equipment in classrooms, labs and offices.'],
                request()->is('admin/equipment/all*') => ['All Equipment', 'View, edit and dispose storage and deployed equipment.'],
                request()->is('maintenance/equipment/all*') => ['All Equipment', 'Storage and deployed equipment.'],
                request()->is('admin/inventory/transfer-requests*') => ['Transfer Approvals', 'Approve or reject equipment transfers requested by Maintenance.'],
                request()->is('admin/inventory*') => ['Inventory', 'Add, edit, dispose and transfer equipment in storage rooms.'],
                request()->is('maintenance/equipment/view/*') => [View::yieldContent('title', 'Equipment'), ''],
                request()->is('admin/equipment/replacements*') => ['Replacement Log', 'How each item sent for replacement was fulfilled: stock swap or procurement.'],
                request()->is('admin/disposal/requests*') => ['Disposal Approvals', 'Approve or reject equipment disposals requested by Maintenance.'],
                request()->is('maintenance/disposal/requests*') => ['Disposal Approvals', 'Disposals waiting for, or decided by, the Administrator.'],
                request()->is('maintenance/disposal*') => ['Disposed Equipment', 'View only. Disposal is managed under Administrator > Disposal.'],
                request()->is('admin/disposal*') => ['Equipment Disposal', 'Dispose, restore, finalize and archive disposed equipment.'],
                request()->is('maintenance/semester-inspections') => ['Semester Inspections', ''],
                request()->is('maintenance/schedules/rooms*') => ['Maintenance Schedule', 'Room inspections: recurring checks of a whole room.'],
                request()->is('maintenance/schedules') => ['Maintenance Schedule', 'Equipment maintenance: per-asset schedules.'],
                request()->is('maintenance/semester-inspections/*') => [View::yieldContent('title', 'Campaign'), 'Semester inspection progress.'],
                request()->is('maintenance/replacement-suggestions*') => ['Replacement Suggestions', 'Assets near or past their useful lifespan.'],
                default => [View::yieldContent('title', 'PaAyo'), ''],
            };

            $unreadCount = 0;
            $recentNotes = collect();
            $attentionTotal = (int) ($attentionTotal ?? 0);
            $topbarNotificationRole = \App\Support\AdminPortal::notificationRole();
            $topbarNotificationsUrl = \App\Support\AdminPortal::route('notifications');
            try {
                $unreadCount = \App\Support\WorkflowNotifier::scopeVisibleTo(\DB::table('notifications_table'), auth()->id(), $topbarNotificationRole)
                    ->count();

                $recentNotes = \App\Support\WorkflowNotifier::scopeVisibleTo(\DB::table('notifications_table'), auth()->id(), $topbarNotificationRole)
                    ->orderByDesc('notification_created_at')
                    ->limit(8)
                    ->get();
            } catch (\Throwable $e) {
                $unreadCount = 0;
                $recentNotes = collect();
            }

            $topbarUser = Auth::user();
            $topbarInitial = strtoupper(substr($topbarUser->user_full_name ?? 'U', 0, 1));
            $topbarPictureUrl = $topbarUser->profile_picture_url ?? null;
        @endphp

        <div class="min-w-0">
            <h1 class="truncate text-[22px] font-semibold leading-tight tracking-tight text-slate-900">
                {{ $moduleHeading[0] }}
            </h1>
            @if ($moduleHeading[1] !== '')
                <p class="mt-0.5 truncate text-sm text-slate-500">
                    {{ $moduleHeading[1] }}
                </p>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-2">
        @include('partials.handover-inbox-chip')

        <a
            href="javascript:void(0)"
            onclick="openMessagingModal()"
            class="dashboard-icon-action"
            aria-label="PaAyo messages"
            data-tooltip="Messages"
        >
            <i data-lucide="messages-square" class="h-[18px] w-[18px]"></i>

            <span
                id="topbarMessageBadge"
                class="hidden absolute -right-1 -top-1 min-w-[18px] h-[18px]
                    items-center justify-center rounded-full
                    bg-rose-500 px-1 text-[10px] font-bold text-white
                    border-2 border-white"
            >
                0
            </span>
        </a>

        <div class="relative">
            <button
                type="button"
                data-topbar-toggle="notifications"
                onclick="event.stopPropagation(); toggleNotifications()"
                class="relative flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-950"
                aria-label="Notifications"
                data-tooltip="Notifications"
            >
                <i data-lucide="bell" class="h-5 w-5"></i>

                @if ($attentionTotal > 0)
                    <span
                        class="absolute -right-0.5 -top-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
                    >
                        {{ $attentionTotal > 99 ? '99+' : $attentionTotal }}
                    </span>
                @elseif ($unreadCount > 0)
                    <span
                        class="absolute right-[6px] top-[6px] h-2 w-2 rounded-full border-2 border-white bg-rose-500"
                    ></span>
                @endif
            </button>

            <div
                id="notificationDropdown"
                class="absolute right-0 top-[calc(100%+2px)] z-50 hidden
                    w-[340px]
                    overflow-hidden
                    rounded-xl
                    border border-black/5
                    bg-white
                    shadow-[0_16px_45px_rgba(0,0,0,0.13)]"
            >
                <div
                    class="flex items-center justify-between
                        border-b border-slate-100
                        px-4 py-3"
                >
                    <div class="min-w-0">
                        <h3 class="text-[13px] font-semibold tracking-tight text-slate-950">
                            Notifications
                        </h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            Recent activity requiring your attention
                        </p>
                    </div>

                    <span
                        class="ml-3 shrink-0 rounded-full
                            bg-slate-100
                            px-2 py-1
                            text-[10px] font-medium
                            text-slate-600"
                    >
                        {{ $unreadCount }} new
                    </span>
                </div>

                <div class="max-h-[290px] overflow-y-auto">
                    @include('layouts.partials.module-importance-mute-panel')
                    @if ($attentionTotal > 0)
                        <button
                            type="button"
                            onclick="typeof openAdminDailyReminder === 'function' && openAdminDailyReminder()"
                            class="flex w-full items-start gap-2.5 border-b border-slate-100 bg-slate-50/80 px-4 py-2.5 text-left transition hover:bg-rose-50/60"
                        >
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="truncate text-[12px] font-semibold text-slate-900">
                                        Attention needed today
                                    </h4>
                                    <span class="shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        {{ $attentionTotal }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[11px] leading-4 text-slate-500">
                                    {{ \App\Support\AdminPortal::canActOnRis()
                                        ? 'Open the daily summary of RIS review, cosign, and amendments.'
                                        : 'Open the daily summary of overdue schedules and borrows.' }}
                                </p>
                            </div>
                        </button>
                    @endif

                    @forelse ($recentNotes as $note)
                        @php
                            $icon = match ($note->notification_type ?? null) {
                                'ris', 'procurement' => 'clipboard-check',
                                'signature' => 'pen-tool',
                                'user' => 'users',
                                'report' => 'triangle-alert',
                                'maintenance_overdue' => 'calendar-x-2',
                                'maintenance_due_today' => 'calendar-clock',
                                'maintenance_upcoming' => 'calendar',
                                default => 'bell',
                            };
                            $iconStyle = match ($note->notification_category ?? null) {
                                'Procurement' => 'bg-amber-50 text-amber-600',
                                'Workflow' => 'bg-slate-100 text-slate-600',
                                'Reports' => 'bg-rose-50 text-rose-600',
                                'Maintenance' => ($note->notification_type ?? null) === 'maintenance_overdue'
                                    ? 'bg-rose-50 text-rose-600'
                                    : 'bg-amber-50 text-amber-600',
                                default => 'bg-slate-100 text-slate-500',
                            };
                        @endphp
                        <a
                            href="{{ \App\Support\NotificationLinks::openUrl($note) }}"
                            class="flex items-start gap-2.5 border-b border-slate-100 px-4 py-2.5 transition hover:bg-slate-50"
                        >
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $iconStyle }}">
                                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate text-[12px] font-semibold text-slate-900">
                                    {{ $note->notification_title ?: 'Notification' }}
                                </h4>
                                <p class="mt-0.5 line-clamp-2 text-[11px] leading-4 text-slate-500">
                                    {{ $note->notification_message ?: 'System update' }}
                                </p>
                                @if(!empty($note->notification_created_at))
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ \Carbon\Carbon::parse($note->notification_created_at)->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="flex min-h-[180px] flex-col items-center justify-center px-6 text-center">
                            @if ($attentionTotal > 0)
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                                    <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                                </div>
                                <h4 class="mt-3 text-sm font-medium text-slate-700">No new notifications</h4>
                                <p class="mt-1 text-xs text-slate-500">Open Attention needed today above for RIS items.</p>
                            @else
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="bell-off" class="h-4 w-4"></i>
                                </div>
                                <h4 class="mt-3 text-sm font-medium text-slate-700">No notifications</h4>
                                <p class="mt-1 text-xs text-slate-400">New system activity will appear here.</p>
                            @endif
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 px-3 py-1.5">
                    <a
                        href="{{ $topbarNotificationsUrl }}"
                        class="block w-full
                            rounded-lg
                            px-3 py-2
                            text-center
                            text-[11px]
                            font-medium
                            text-slate-600
                            transition
                            hover:bg-slate-100
                            hover:text-slate-950"
                    >
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        @include('partials.portal-switcher')

        <div class="relative">
            <button
                type="button"
                data-topbar-toggle="profile"
                onclick="event.stopPropagation(); toggleProfileDropdown()"
                class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left transition hover:bg-slate-100"
            >
                <div
                    data-user-avatar
                    class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-sm font-medium text-white"
                >
                    @if ($topbarPictureUrl)
                        <img
                            src="{{ $topbarPictureUrl }}?v={{ time() }}"
                            alt="{{ $topbarUser->user_full_name }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        {{ $topbarInitial }}
                    @endif
                </div>

                {{-- Do not use Tailwind "hidden sm:block" here.
                     accounting-modern.css / president-modern.css force
                     .hidden { display:none !important }, which permanently
                     hides the name/role/chevron on ATP & RFC pages. --}}
                <div class="admin-topbar-profile-meta min-w-0">
                    <p class="max-w-[150px] truncate text-sm font-medium text-slate-900">
                        {{ $topbarUser->user_full_name }}
                    </p>
                    <p class="mt-0.5 max-w-[150px] truncate text-xs text-slate-500">
                        {{ \App\Support\RoleAccess::primaryRoleLabel($topbarUser) }}
                    </p>
                </div>

                <i
                    data-lucide="chevron-down"
                    class="admin-topbar-profile-chevron h-4 w-4 shrink-0 text-slate-400"
                ></i>
            </button>

            <div
                id="profileDropdown"
                class="absolute right-0 top-[calc(100%+10px)] z-50 hidden w-[260px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.14)]"
            >
                <div class="border-b border-slate-100 px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div
                            data-user-avatar
                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-900 text-sm font-medium text-white"
                        >
                            @if ($topbarPictureUrl)
                                <img
                                    src="{{ $topbarPictureUrl }}?v={{ time() }}"
                                    alt="{{ $topbarUser->user_full_name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                {{ $topbarInitial }}
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-950">
                                {{ $topbarUser->user_full_name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $topbarUser->user_email_address }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-2">
                    <a
                        href="{{ \App\Support\AdminPortal::route('profile') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i data-lucide="user-cog" class="h-4 w-4 text-slate-400"></i>
                        Profile settings
                    </a>
                    <a
                        href="{{ \App\Support\AdminPortal::route('security') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i data-lucide="shield" class="h-4 w-4 text-slate-400"></i>
                        Security settings
                    </a>
                </div>

                <div class="border-t border-slate-100 p-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-rose-50 hover:text-rose-600"
                        >
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .topbar{
        height:80px;
        background:#ffffff;
        border-bottom: none;
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:0 28px;
        position:sticky;
        top:0;
        z-index:40;
    }

    .topbar-left{
        display:flex;
        align-items:center;
        gap:16px;
        min-width:0;
        flex:1;
    }

    .mobile-sidebar-btn{
        display:none;
        border:none;
        background:#F8FAFC;
        width:42px;
        height:42px;
        border-radius:14px;
        align-items:center;
        justify-content:center;
        cursor:pointer;
    }

    @media (max-width: 1024px){
        .mobile-sidebar-btn{
            display:flex;
        }
    }

    .dashboard-icon-action{
        position:relative;
        display:flex;
        height:40px;
        width:40px;
        align-items:center;
        justify-content:center;
        border-radius:9999px;
        color:#64748B;
        transition:.2s;
    }

    .dashboard-icon-action:hover{
        background:#F1F5F9;
        color:#0F172A;
    }

    /* accounting/president CSS set .hidden { display:none !important },
       so Tailwind "hidden sm:block" never shows on ATP/RFC. */
    .admin-topbar-profile-meta,
    .admin-topbar-profile-chevron,
    svg.admin-topbar-profile-chevron {
        display: none !important;
    }

    @media (min-width: 640px) {
        .admin-topbar-profile-meta {
            display: block !important;
        }

        .admin-topbar-profile-chevron,
        svg.admin-topbar-profile-chevron {
            display: block !important;
        }
    }
</style>

<script>
    function toggleNotifications() {
        const dropdown = document.getElementById("notificationDropdown");
        const profile = document.getElementById("profileDropdown");
        if (profile) profile.classList.add("hidden");
        if (dropdown) dropdown.classList.toggle("hidden");
    }

    function toggleProfileDropdown() {
        const dropdown = document.getElementById("profileDropdown");
        const notif = document.getElementById("notificationDropdown");
        if (notif) notif.classList.add("hidden");
        if (dropdown) dropdown.classList.toggle("hidden");
    }

    window.addEventListener("click", function (e) {
        const notif = document.getElementById("notificationDropdown");
        const profile = document.getElementById("profileDropdown");

        if (
            notif &&
            !e.target.closest("#notificationDropdown") &&
            !e.target.closest('[data-topbar-toggle="notifications"]')
        ) {
            notif.classList.add("hidden");
        }

        if (
            profile &&
            !e.target.closest("#profileDropdown") &&
            !e.target.closest('[data-topbar-toggle="profile"]')
        ) {
            profile.classList.add("hidden");
        }
    });
</script>
