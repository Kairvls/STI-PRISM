<div class="topbar">
    <!-- LEFT -->

    <div class="topbar-left">
        <button onclick="toggleSidebar()" class="mobile-sidebar-btn">
            <i data-lucide="menu"></i>
        </button>

        <div class="mb-1 flex items-center gap-2 text-sm text-gray-500">
            <span>Maintenance</span>

            <i data-lucide="chevron-right" class="h-4 w-4"></i>

            <span class="font-medium text-gray-700">
                {{
                    ucwords(
                        str_replace("-", " ", request()->segment(3) ?? "Dashboard"),
                    )
                }}
            </span>
        </div>
    </div>

    <!-- RIGHT -->

    <div class="topbar-right">
        <!-- TIME -->

        <!-- NOTIFICATION -->

        <div class="relative">
            <button onclick="toggleNotifications()" class="icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="black" class="bi bi-bell-fill" viewBox="0 0 16 16">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2m.995-14.901a1 1 0 1 0-1.99 0A5 5 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901" />
                </svg>

                <span class="notification-dot"></span>
            </button>

            <!-- NOTIFICATION DROPDOWN -->

            <div id="notificationDropdown" class="dropdown-panel hidden">
                <div class="dropdown-header">Notifications</div>

                <div class="dropdown-content">
                    <div class="notification-item">
                        <div class="notification-icon danger">
                            <i data-lucide="triangle-alert"></i>
                        </div>

                        <div>
                            <h4>Urgent Report Submitted</h4>

                            <p>Aircon malfunction at Room 204</p>

                            <span> 2 minutes ago </span>
                        </div>
                    </div>

                    <div class="notification-item">
                        <div class="notification-icon success">
                            <i data-lucide="badge-check"></i>
                        </div>

                        <div>
                            <h4>Equipment Repaired</h4>

                            <p>Projector repaired at AVR Room</p>

                            <span> 15 minutes ago </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PROFILE -->

        <div class="relative">
            <button
                onclick="toggleProfileDropdown()"
                class="profile-btn bg-gray-100 hover:bg-gray-200"
            >
                <div
                    class="profile-avatar h-9 w-9 shrink-0 overflow-hidden rounded-full bg-gray-200 shadow-lg"
                >
                    K
                </div>

                <div
                    class="profile-info mt-0.5 flex flex-col items-start justify-start"
                >
                    <h4>Kenn Mehares</h4>

                    <p>Maintenance Personnel</p>
                </div>

                <i data-lucide="chevron-down" class="profile-arrow"></i>
            </button>

            <!-- PROFILE DROPDOWN -->

            <div id="profileDropdown" class="profile-dropdown hidden">
                <div class="profile-header">
                    <h4>Kenn Mehares</h4>

                    <p>kenn@gmail.com</p>
                </div>

                <div class="profile-links">
                    <a href="#" class="topbar-link">
                        <i data-lucide="user-cog"></i>

                        Profile Settings
                    </a>

                    <a href="#" class="topbar-link">
                        <i data-lucide="shield-check"></i>

                        Security Settings
                    </a>
                </div>

                <div class="logout-area">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="logout-btn">
                            <i data-lucide="log-out"></i>

                            Logout
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
