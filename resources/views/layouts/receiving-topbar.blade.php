<div class="topbar">
    <!-- LEFT -->

    <div class="topbar-left">
        {{-- ================================================= --}}
        {{-- MOBILE SIDEBAR BUTTON --}}
        {{-- ================================================= --}}

        <button onclick="toggleSidebar()" class="mobile-sidebar-btn">
            <i data-lucide="menu"></i>
        </button>

        {{-- ================================================= --}}
        {{-- SEARCH BAR --}}
        {{-- ================================================= --}}

        <div class="dashboard-toolbar-search">
            <i data-lucide="search" class="dashboard-toolbar-search-icon"></i>

            <input
                type="search"
                id="dashboard-search"
                placeholder="Search accounts, roles, settings..."
                autocomplete="off"
            />

            <kbd class="dashboard-search-shortcut"> Ctrl K </kbd>
        </div>
    </div>

    <!-- RIGHT -->

    <div class="flex items-center gap-2">
        <!-- ===================================== -->
        <!-- NOTIFICATIONS -->
        <!-- ===================================== -->

        {{-- ================================================= --}}
        {{-- MAILBOX BUTTON --}}
        {{-- BESIDE NOTIFICATION BUTTON --}}
        {{-- ================================================= --}}

        {{-- ================================================= --}}
        {{-- MESSAGE BUTTON WITH UNREAD COUNT --}}
        {{-- REPLACES THE OLD MESSAGE BUTTON --}}
        {{-- ================================================= --}}

        <a
            href="javascript:void(0)"
            onclick="openMessagingModal()"
            class="dashboard-icon-action"
            aria-label="PRISM messages"
            title="Messages"
        >
            <i data-lucide="messages-square" class="h-[18px] w-[18px]"></i>

            {{-- ============================================= --}}
            {{-- REAL MESSAGE UNREAD COUNT --}}
            {{-- UPDATED BY messaging-modal.blade.php --}}
            {{-- ============================================= --}}

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
            <!-- ===================================== -->
            <!-- NOTIFICATION BUTTON -->
            <!-- ===================================== -->

            <button
                type="button"
                onclick="toggleNotifications()"
                class="relative flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-950"
                aria-label="Notifications"
            >
                <i data-lucide="bell" class="h-5 w-5"></i>

                <!-- ===================================== -->
                <!-- UNREAD INDICATOR -->
                <!-- ONLY SHOW WHEN UNREAD EXISTS -->
                <!-- ===================================== -->

                @php
                    $unreadCount = 0;
                    try {
                        $unreadCount = \DB::table('notifications_table')
                            ->where(function ($q) {
                                $q->where('notification_user_id', auth()->id())
                                    ->orWhere('notification_target_role', 'Receiving Officer');
                            })
                            ->count();
                    } catch (\Throwable $e) {
                        $unreadCount = 0;
                    }
                @endphp

                @if ($unreadCount > 0)
                    <span
                        class="absolute right-[6px] top-[6px] h-2 w-2 rounded-full border-2 border-white bg-rose-500"
                    ></span>

                @endif
            </button>

            <!-- ===================================== -->
            <!-- NOTIFICATION DROPDOWN -->
            <!-- ===================================== -->

            <div
                id="notificationDropdown"
                class="absolute right-0 top-[calc(100%+10px)] z-50 hidden w-[360px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.14)]"
            >
                <!-- ===================================== -->
                <!-- DROPDOWN HEADER -->
                <!-- ===================================== -->

                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                >
                    <div>
                        <h3
                            class="text-sm font-semibold tracking-tight text-slate-950"
                        >
                            Notifications
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500">Recent activity requiring your attention</p>
                    </div>

                    <!-- ===================================== -->
                    <!-- UNREAD COUNT -->
                    <!-- ===================================== -->

                    <span
                        class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600"
                    >
                        {{-- $headerUnreadCount --}} new
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- NOTIFICATION LIST -->
                <!-- ===================================== -->

                <div class="max-h-[360px] overflow-y-auto">

                    <div
                        class="flex min-h-[220px] flex-col items-center justify-center px-6 text-center"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                        >
                            <i data-lucide="bell-off" class="h-4 w-4"></i>
                        </div>

                        <h4 class="mt-3 text-sm font-medium text-slate-700">
                            No notifications
                        </h4>

                        <p class="mt-1 text-xs text-slate-400">
                            New system activity will appear here.
                        </p>

                    </div>

                </div>

                <!-- ===================================== -->
                <!-- DROPDOWN FOOTER -->
                <!-- ===================================== -->

                <div class="border-t border-slate-100 px-3 py-2">
                    <a
                        href="{{ url('/receiving/notifications') }}"
                        class="block w-full rounded-lg px-3 py-2 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- ===================================== -->
        <!-- PROFILE -->
        <!-- ===================================== -->
        <div class="relative">
            <!-- PROFILE BUTTON -->
            <button
                type="button"
                onclick="toggleProfileDropdown()"
                class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-left transition hover:bg-slate-100"
            >
                <!-- AVATAR -->
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-medium text-white"
                >
                    {{ strtoupper(substr(Auth::user()->user_full_name, 0, 1)) }}
                </div>

                <!-- PROFILE INFORMATION -->
                <div class="hidden min-w-0 sm:block">
                    <p
                        class="max-w-[150px] truncate text-sm font-medium text-slate-900"
                    >{{ Auth::user()->user_full_name }}</p>

                    <p
                        class="mt-0.5 max-w-[150px] truncate text-xs text-slate-500"
                    >Receiving Officer</p>
                </div>

                <!-- CHEVRON -->
                <i
                    data-lucide="chevron-down"
                    class="hidden h-4 w-4 shrink-0 text-slate-400 sm:block"
                ></i>
            </button>

            <!-- ===================================== -->
            <!-- PROFILE DROPDOWN -->
            <!-- ===================================== -->
            <div
                id="profileDropdown"
                class="absolute right-0 top-[calc(100%+10px)] z-50 hidden w-[260px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.14)]"
            >
                <!-- PROFILE HEADER -->
                <div class="border-b border-slate-100 px-4 py-4">
                    <div class="flex items-center gap-3">
                        <!-- AVATAR -->
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-medium text-white"
                        >
                            {{ strtoupper(substr(Auth::user()->user_full_name, 0, 1)) }}
                        </div>

                        <!-- USER INFORMATION -->
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-slate-950"
                            >{{ Auth::user()->user_full_name }}</p>

                            <p
                                class="mt-0.5 truncate text-xs text-slate-500"
                            >{{ Auth::user()->user_email_address }}</p>
                        </div>
                    </div>
                </div>

                <!-- ===================================== -->
                <!-- PROFILE LINKS -->
                <!-- ===================================== -->
                <div class="p-2">
                    <a
                        href="{{ url('/admin/profile') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i
                            data-lucide="user-cog"
                            class="h-4 w-4 text-slate-400"
                        ></i>

                        Profile settings
                    </a>

                    <a
                        href="{{ url('/admin/security') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        <i
                            data-lucide="shield-check"
                            class="h-4 w-4 text-slate-400"
                        ></i>

                        Security settings
                    </a>
                </div>

                <!-- ===================================== -->
                <!-- LOGOUT -->
                <!-- ===================================== -->
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

        :root{
            --primary:#FFD400;
            --primary-dark:#E6BF00;
            --bg:#F8FAFC;
            --card:#FFFFFF;
            --text:#0F172A;
            --muted:#64748B;
            --border:#E5E7EB;
        }


        .topbar{

            height:82px;

            background:white;

            border-bottom:1px solid #E2E8F0;

            box-shadow:
                0 2px 10px rgba(15,23,42,.03);

            display:flex;

            align-items:center;

            justify-content:space-between;

            padding:0 28px;

            position:sticky;

            top:0;

            z-index:10;

        }

        .topbar-left{
            display:flex;
            align-items:center;
            gap:18px;
        }

        /* ======================================
       TOPBAR SEARCH
       KEEP THIS INSIDE maintenance-topbar.blade.php
    ====================================== */

    .dashboard-toolbar-search {
        width: 320px;
        height: 46px;

        display: flex;
        align-items: center;

        gap: 12px;

        padding: 0 12px 0 16px;

        background: #ffffff;

        border: 1px solid #e2e8f0;

        border-radius: 14px;

        color: #64748b;
    }


    .dashboard-toolbar-search-icon {
        width: 18px;
        height: 18px;

        flex-shrink: 0;

        color: #94a3b8;

        stroke: currentColor;
    }


    .dashboard-toolbar-search input {
        min-width: 0;

        flex: 1;

        border: none;

        outline: none;

        background: transparent;

        font-size: 14px;

        color: #0f172a;
    }


    .dashboard-toolbar-search input::placeholder {
        color: #94a3b8;
    }


    .dashboard-search-shortcut {
        flex-shrink: 0;

        padding: 3px 7px;

        border: 1px solid #e2e8f0;

        border-radius: 6px;

        background: #f8fafc;

        color: #94a3b8;

        font-size: 11px;

        line-height: 1;
    }


    /* ======================================
       MAILBOX BUTTON
       KEEP THIS INSIDE maintenance-topbar.blade.php
    ====================================== */

    .dashboard-icon-action {
        position: relative;

        width: 40px;
        height: 40px;

        display: flex;
        align-items: center;
        justify-content: center;

        flex-shrink: 0;

        border-radius: 999px;

        color: #64748b;

        text-decoration: none;

        transition:
            background 0.2s ease,
            color 0.2s ease;
    }


    .dashboard-icon-action:hover {
        background: #f1f5f9;

        color: #0f172a;
    }


    .dashboard-icon-action svg {
        width: 18px;
        height: 18px;

        stroke: currentColor;
    }


    .dashboard-notification-dot {
        position: absolute;

        top: 8px;
        right: 8px;

        width: 6px;
        height: 6px;

        border-radius: 999px;

        background: #ef4444;

        border: 1px solid #ffffff;
    }

        .mobile-sidebar-btn{
            width:46px;
            height:46px;
            border-radius:16px;
            border:none;
            background:#F8FAFC;
            display:none;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            transition:.25s;
        }

        .mobile-sidebar-btn:hover{
            background:var(--primary);
            transform:translateY(-2px);
        }

        .page-title{
            font-size:22px;
            font-weight:700;
            color:var(--text);
            letter-spacing:-0.5px;
        }

        .page-subtitle{
            margin-top:3px;
            font-size:13px;
            color:var(--muted);
        }

        .topbar-right{
            display:flex;
            align-items:center;
            gap:16px;
        }

        /* TIME */

        .time-card{
            background:#FFFDF3;
            border:1px solid rgba(255,212,0,.25);
            padding:12px 18px;
            border-radius:18px;
            display:flex;
            align-items:center;
            gap:12px;
            transition:.25s;
        }

        .time-card:hover{
            transform:translateY(-2px);
            box-shadow:
            0 10px 25px rgba(255,212,0,.15);
        }

        .time-card i{
            width:18px;
            height:18px;
            color:#B38F00;
        }

        .time-label{
            font-size:11px;
            color:var(--muted);
        }

        .time-value{
            font-size:13px;
            font-weight:700;
            color:var(--text);
        }

        /* ICON BUTTONS */

        .icon-btn{
            width:48px;
            height:48px;
            border:none;
            border-radius:16px;
            background:#F8FAFC;
            display:flex;
            align-items:center;
            justify-content:center;
            position:relative;
            cursor:pointer;
            transition:.25s;
        }

        .icon-btn:hover{
            background:var(--primary);
            transform:translateY(-2px);
            box-shadow:
            0 10px 25px rgba(255,212,0,.25);
        }

        .icon-btn i{
            width:20px;
            height:20px;
            color:#334155;
        }

        .notification-dot{
            width:10px;
            height:10px;
            border-radius:50%;
            background:#EF4444;
            border:2px solid white;
            position:absolute;
            top:10px;
            right:10px;
        }

        /* PROFILE */

        .profile-btn{
            border:none;

            padding:6px 14px;
            border-radius:18px;
            display:flex;
            align-items:center;
            gap:12px;
            cursor:pointer;
            transition:.25s;
            box-shadow:
            0 2px 10px rgba(15,23,42,.04);
        }

        .profile-btn:hover{
            transform:translateY(-2px);
            box-shadow:
            0 12px 30px rgba(15,23,42,.08);
        }

        .profile-avatar{
            width:35px;
            height:35px;
            border-radius:14px;


            color:#111827;
            font-weight:700;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:15px;
        }

        .profile-info h4{
            font-size:14px;
            font-weight:700;
            color:var(--text);
        }

        .profile-info p{
            font-size:12px;
            color:var(--muted);
        }

        .profile-arrow{
            width:18px;
            height:18px;
            color:#94A3B8;
        }

        /* DROPDOWNS */

        .dropdown-panel,
        .profile-dropdown{
            position:absolute;
            top:68px;
            right:0;
            width:360px;
            background:rgba(255,255,255,.98);
            backdrop-filter:blur(16px);
            border:1px solid rgba(15,23,42,.08);
            border-radius:22px;
            overflow:hidden;
            box-shadow:
            0 25px 60px rgba(15,23,42,.12);
            animation:dropdownFade .2s ease;
        }

        @keyframes dropdownFade{
            from{
                opacity:0;
                transform:translateY(-10px);
            }
            to{
                opacity:1;
                transform:translateY(0);
            }
        }

        .dropdown-header{
            padding:20px;
            font-weight:700;
            font-size:15px;
            color:var(--text);
            border-bottom:1px solid #F1F5F9;
        }

        .notification-item{
            display:flex;
            gap:14px;
            padding:18px;
            transition:.2s;
        }

        .notification-item:hover{
            background:#FAFAFA;
        }

        .notification-item h4{
            font-size:14px;
            font-weight:700;
            color:var(--text);
        }

        .notification-item p{
            font-size:12px;
            color:var(--muted);
            margin-top:4px;
        }

        .notification-item span{
            font-size:11px;
            color:#94A3B8;
        }

        .notification-icon{
            width:46px;
            height:46px;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .danger{
            background:#FEE2E2;
            color:#DC2626;
        }

        .success{
            background:#DCFCE7;
            color:#16A34A;
        }

        .profile-header{
            padding:22px;
            background:#FFFDF3;
            border-bottom:1px solid rgba(255,212,0,.15);
        }

        .profile-header h4{
            font-size:15px;
            font-weight:700;
        }

        .profile-header p{
            margin-top:5px;
            font-size:12px;
            color:var(--muted);
        }

        .profile-links{
            padding:10px;
        }

        .topbar-link{
            display:flex;
            align-items:center;
            gap:12px;
            padding:14px;
            border-radius:14px;
            color:#475569;
            text-decoration:none;
            transition:.2s;
        }

        .topbar-link:hover{
            background:#FFFBE6;
            color:#111827;
        }

        .logout-area{
            padding:14px;
            border-top:1px solid #F1F5F9;
        }

        .logout-btn{
            width:100%;
            border:none;
            padding:13px;
            border-radius:14px;
            background:#EF4444;
            color:white;
            font-weight:600;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            cursor:pointer;
            transition:.25s;
        }

        .logout-btn:hover{
            background:#DC2626;
            transform:translateY(-2px);
        }

        @media(max-width:1280px){

            .mobile-sidebar-btn{
                display:flex;
            }

            .time-card{
                display:none;
            }

            .profile-info{
                display:none;
            }

            .profile-dropdown,
            .dropdown-panel{
                width:320px;
            }
        }
</style>

<script>
    function toggleNotifications() {
        const dropdown = document.getElementById("notificationDropdown");

        dropdown.classList.toggle("hidden");
    }

    function toggleProfileDropdown() {
        const dropdown = document.getElementById("profileDropdown");

        dropdown.classList.toggle("hidden");
    }

    window.addEventListener("click", function (e) {
        const notif = document.getElementById("notificationDropdown");

        const profile = document.getElementById("profileDropdown");

        if (
            !e.target.closest("#notificationDropdown") &&
            !e.target.closest('[onclick="toggleNotifications()"]')
        ) {
            notif.classList.add("hidden");
        }

        if (
            !e.target.closest("#profileDropdown") &&
            !e.target.closest('[onclick="toggleProfileDropdown()"]')
        ) {
            profile.classList.add("hidden");
        }
    });
</script>

@include('layouts.partials.receiving-ris-preview')
