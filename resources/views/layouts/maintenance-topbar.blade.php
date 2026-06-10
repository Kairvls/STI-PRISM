<div class="h-[85px] bg-[#0F172A] border-b border-white/10 px-6 flex items-center justify-between">

    <!-- LEFT -->
    <div class="flex items-center gap-4">

        <!-- MOBILE SIDEBAR BUTTON -->
        <button onclick="toggleSidebar()"
            class="xl:hidden w-11 h-11 rounded-xl bg-[#1E293B] flex items-center justify-center">

            <i data-lucide="menu"></i>

        </button>

        <!-- PAGE TITLE -->
        <div>

            <h1 class="text-xl font-bold text-white">

                Maintenance Personnel Panel

            </h1>

            <p class="text-sm text-gray-400">

                PRISM Monitoring System

            </p>

        </div>

    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-5">

        <!-- CURRENT TIME -->
        <div class="hidden md:block bg-[#1E293B] px-5 py-3 rounded-2xl">

            <p class="text-xs text-gray-400">

                Current Time

            </p>

            <h1 class="text-sm font-bold text-white mt-1">

                {{ now()->format('F d, Y h:i A') }}

            </h1>

        </div>

        <!-- NOTIFICATION -->
        <div class="relative">

            <button onclick="toggleNotifications()"
                class="relative w-12 h-12 rounded-2xl bg-[#1E293B] flex items-center justify-center hover:bg-[#2563EB] transition">

                <i data-lucide="bell"></i>

                <!-- NOTIFICATION DOT -->
                <span class="absolute top-3 right-3 w-2.5 h-2.5 bg-red-500 rounded-full"></span>

            </button>

            <!-- DROPDOWN -->
            <div id="notificationDropdown"
                class="hidden absolute right-0 mt-3 w-[360px] bg-[#1E293B] border border-white/10 rounded-3xl shadow-2xl z-50 overflow-hidden">

                <!-- HEADER -->
                <div class="p-5 border-b border-white/10">

                    <h1 class="text-lg font-bold text-white">

                        Notifications

                    </h1>

                </div>

                <!-- CONTENT -->
                <div class="max-h-[400px] overflow-y-auto">

                    <!-- ITEM -->
                    <div class="p-5 border-b border-white/5 hover:bg-[#0F172A] transition cursor-pointer">

                        <div class="flex items-start gap-4">

                            <div class="w-11 h-11 rounded-2xl bg-red-500/20 flex items-center justify-center">

                                <i data-lucide="triangle-alert"
                                class="text-red-400 w-5 h-5"></i>

                            </div>

                            <div>

                                <h1 class="font-semibold text-white">

                                    Urgent Report Submitted

                                </h1>

                                <p class="text-sm text-gray-400 mt-1">

                                    Aircon malfunction at Room 204

                                </p>

                                <p class="text-xs text-gray-500 mt-2">

                                    2 minutes ago

                                </p>

                            </div>

                        </div>

                    </div>

                    <!-- ITEM -->
                    <div class="p-5 hover:bg-[#0F172A] transition cursor-pointer">

                        <div class="flex items-start gap-4">

                            <div class="w-11 h-11 rounded-2xl bg-green-500/20 flex items-center justify-center">

                                <i data-lucide="badge-check"
                                class="text-green-400 w-5 h-5"></i>

                            </div>

                            <div>

                                <h1 class="font-semibold text-white">

                                    Equipment Repaired

                                </h1>

                                <p class="text-sm text-gray-400 mt-1">

                                    Projector repaired at AVR Room

                                </p>

                                <p class="text-xs text-gray-500 mt-2">

                                    15 minutes ago

                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- PROFILE -->
        <div class="relative">

            <button onclick="toggleProfileDropdown()"
                class="flex items-center gap-3 bg-[#1E293B] hover:bg-[#2563EB] transition px-4 py-2 rounded-2xl">

                <!-- AVATAR -->
                <div class="w-11 h-11 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-bold">

                    K

                </div>

                <!-- USER -->
                <div class="hidden md:block text-left">

                    <h1 class="text-sm font-bold text-white">

                        Kenn Mehares

                    </h1>

                    <p class="text-xs text-gray-400">

                        Maintenance Personnel

                    </p>

                </div>

                <i data-lucide="chevron-down"
                class="w-4 h-4 text-gray-300"></i>

            </button>

            <!-- DROPDOWN -->
            <div id="profileDropdown"
                class="hidden absolute right-0 mt-3 w-[260px] bg-[#1E293B] border border-white/10 rounded-3xl shadow-2xl overflow-hidden z-50">

                <!-- PROFILE HEADER -->
                <div class="p-5 border-b border-white/10">

                    <h1 class="font-bold text-white">

                        Kenn Mehares

                    </h1>

                    <p class="text-sm text-gray-400 mt-1">

                        kenn@gmail.com

                    </p>

                </div>

                <!-- LINKS -->
                <div class="p-3 space-y-1">

                    <a href="#"
                        class="topbar-link">

                        <i data-lucide="user-cog"></i>

                        Profile Settings

                    </a>

                    <a href="#"
                        class="topbar-link">

                        <i data-lucide="shield-check"></i>

                        Security Settings

                    </a>

                </div>

                <!-- LOGOUT -->
                <div class="p-3 border-t border-white/10">

                    <form method="POST"
                        action="{{ route('logout') }}">

                        @csrf

                        <button type="submit"
                            class="w-full flex items-center gap-3 bg-red-500 hover:bg-red-600 transition px-4 py-3 rounded-2xl font-semibold">

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

.topbar-link{

    display:flex;
    align-items:center;
    gap:14px;
    padding:13px 16px;
    border-radius:16px;
    color:#CBD5E1;
    transition:.2s;

}

.topbar-link:hover{

    background:#0F172A;
    color:white;

}

</style>

<script>

function toggleNotifications(){

    const dropdown = document.getElementById(
        'notificationDropdown'
    );

    dropdown.classList.toggle('hidden');

}

function toggleProfileDropdown(){

    const dropdown = document.getElementById(
        'profileDropdown'
    );

    dropdown.classList.toggle('hidden');

}

window.addEventListener('click', function(e){

    const notif = document.getElementById(
        'notificationDropdown'
    );

    const profile = document.getElementById(
        'profileDropdown'
    );

    if(!e.target.closest('#notificationDropdown') &&
       !e.target.closest('[onclick="toggleNotifications()"]')){

        notif.classList.add('hidden');

    }

    if(!e.target.closest('#profileDropdown') &&
       !e.target.closest('[onclick="toggleProfileDropdown()"]')){

        profile.classList.add('hidden');

    }

});

</script>

