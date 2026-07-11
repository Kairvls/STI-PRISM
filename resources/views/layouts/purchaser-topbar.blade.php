{{-- ===================================================== --}}
{{-- PURCHASER TOPBAR --}}
{{-- ===================================================== --}}

<div class="purchaser-topbar">


    {{-- ===================================================== --}}
    {{-- LEFT SIDE --}}
    {{-- ===================================================== --}}

    <div class="purchaser-topbar-left">


        {{-- ================================================= --}}
        {{-- MOBILE SIDEBAR BUTTON --}}
        {{-- USES toggleSidebar() FROM app.blade.php --}}
        {{-- ================================================= --}}

        <button
            type="button"
            onclick="toggleSidebar()"
            class="purchaser-mobile-sidebar-btn"
            aria-label="Open sidebar"
        >

            <i data-lucide="menu"></i>

        </button>



        {{-- ================================================= --}}
        {{-- PAGE BREADCRUMB --}}
        {{-- ================================================= --}}

        <div class="min-w-0">

            <div class="flex min-w-0 items-center gap-2 text-sm text-gray-500">

                <span class="shrink-0">
                    Purchaser
                </span>

                <i
                    data-lucide="chevron-right"
                    class="h-4 w-4 shrink-0"
                ></i>

                <span class="truncate font-medium text-gray-700">

                    @yield(
                        "page-title",
                        "Dashboard"
                    )

                </span>

            </div>


            {{-- ================================================= --}}
            {{-- OPTIONAL PAGE SUBTITLE --}}
            {{-- ================================================= --}}

            <p class="mt-1 hidden max-w-[520px] truncate text-xs text-gray-400 lg:block">

                @yield(
                    "page-subtitle",
                    "Procurement Management"
                )

            </p>

        </div>

    </div>



    {{-- ===================================================== --}}
    {{-- RIGHT SIDE --}}
    {{-- ===================================================== --}}

    <div class="flex items-center gap-2">


        {{-- ===================================================== --}}
        {{-- NOTIFICATIONS --}}
        {{-- NOT CONNECTED YET --}}
        {{-- ===================================================== --}}

        <div class="relative">


            {{-- ================================================= --}}
            {{-- NOTIFICATION BUTTON --}}
            {{-- ================================================= --}}

            <button
                type="button"
                onclick="togglePurchaserNotifications()"

                class="
                    relative
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-full
                    text-slate-500
                    transition
                    hover:bg-slate-100
                    hover:text-slate-950
                "

                aria-label="Notifications"
            >

                <i
                    data-lucide="bell"
                    class="h-5 w-5"
                ></i>

            </button>



            {{-- ================================================= --}}
            {{-- NOTIFICATION DROPDOWN --}}
            {{-- PLACEHOLDER UNTIL BACKEND IS CONNECTED --}}
            {{-- ================================================= --}}

            <div
                id="purchaserNotificationDropdown"

                class="
                    absolute
                    right-0
                    top-[calc(100%+10px)]
                    z-50
                    hidden
                    w-[360px]
                    max-w-[calc(100vw-24px)]
                    overflow-hidden
                    rounded-2xl
                    border
                    border-black/5
                    bg-white
                    shadow-[0_20px_60px_rgba(0,0,0,0.14)]
                "
            >


                {{-- ================================================= --}}
                {{-- DROPDOWN HEADER --}}
                {{-- ================================================= --}}

                <div
                    class="
                        flex
                        items-center
                        justify-between
                        border-b
                        border-slate-100
                        px-5
                        py-4
                    "
                >

                    <div>

                        <h3 class="text-sm font-semibold tracking-tight text-slate-950">
                            Notifications
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500">
                            Procurement activity requiring your attention
                        </p>

                    </div>


                    <span
                        class="
                            rounded-full
                            bg-slate-100
                            px-2
                            py-1
                            text-[11px]
                            font-medium
                            text-slate-600
                        "
                    >
                        0 new
                    </span>

                </div>



                {{-- ================================================= --}}
                {{-- EMPTY STATE --}}
                {{-- ================================================= --}}

                <div
                    class="
                        flex
                        min-h-[220px]
                        flex-col
                        items-center
                        justify-center
                        px-6
                        text-center
                    "
                >

                    <div
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-full
                            bg-slate-100
                            text-slate-400
                        "
                    >

                        <i
                            data-lucide="bell-off"
                            class="h-4 w-4"
                        ></i>

                    </div>


                    <h4 class="mt-3 text-sm font-medium text-slate-700">
                        No notifications
                    </h4>


                    <p class="mt-1 max-w-[240px] text-xs leading-5 text-slate-400">
                        New procurement activity will appear here.
                    </p>

                </div>

            </div>

        </div>



        {{-- ===================================================== --}}
        {{-- PROFILE --}}
        {{-- ===================================================== --}}

        <div class="relative">


            {{-- ================================================= --}}
            {{-- PROFILE BUTTON --}}
            {{-- ================================================= --}}

            <button
                type="button"
                onclick="togglePurchaserProfileDropdown()"

                class="
                    flex
                    items-center
                    gap-3
                    rounded-xl
                    px-2
                    py-1.5
                    text-left
                    transition
                    hover:bg-slate-100
                "
            >


                {{-- ================================================= --}}
                {{-- AVATAR --}}
                {{-- ================================================= --}}

                <div
                    class="
                        flex
                        h-9
                        w-9
                        shrink-0
                        items-center
                        justify-center
                        rounded-full
                        bg-slate-900
                        text-sm
                        font-medium
                        text-white
                    "
                >

                    {{
                        strtoupper(
                            substr(
                                auth()->user()?->user_full_name
                                ?? auth()->user()?->name
                                ?? 'P',
                                0,
                                1
                            )
                        )
                    }}

                </div>



                {{-- ================================================= --}}
                {{-- USER INFORMATION --}}
                {{-- ================================================= --}}

                <div class="hidden min-w-0 sm:block">

                    <p class="max-w-[150px] truncate text-sm font-medium text-slate-900">

                        {{
                            auth()->user()?->user_full_name
                            ?? auth()->user()?->name
                            ?? 'Purchaser'
                        }}

                    </p>

                    <p class="mt-0.5 max-w-[150px] truncate text-xs text-slate-500">
                        Purchaser
                    </p>

                </div>



                {{-- ================================================= --}}
                {{-- CHEVRON --}}
                {{-- ================================================= --}}

                <i
                    data-lucide="chevron-down"
                    class="hidden h-4 w-4 shrink-0 text-slate-400 sm:block"
                ></i>

            </button>



            {{-- ===================================================== --}}
            {{-- PROFILE DROPDOWN --}}
            {{-- ===================================================== --}}

            <div
                id="purchaserProfileDropdown"

                class="
                    absolute
                    right-0
                    top-[calc(100%+10px)]
                    z-50
                    hidden
                    w-[260px]
                    overflow-hidden
                    rounded-2xl
                    border
                    border-black/5
                    bg-white
                    shadow-[0_20px_60px_rgba(0,0,0,0.14)]
                "
            >


                {{-- ================================================= --}}
                {{-- PROFILE HEADER --}}
                {{-- ================================================= --}}

                <div class="border-b border-slate-100 px-4 py-4">

                    <div class="flex items-center gap-3">


                        {{-- ============================================= --}}
                        {{-- AVATAR --}}
                        {{-- ============================================= --}}

                        <div
                            class="
                                flex
                                h-10
                                w-10
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                bg-slate-900
                                text-sm
                                font-medium
                                text-white
                            "
                        >

                            {{
                                strtoupper(
                                    substr(
                                        auth()->user()?->user_full_name
                                        ?? auth()->user()?->name
                                        ?? 'P',
                                        0,
                                        1
                                    )
                                )
                            }}

                        </div>



                        {{-- ============================================= --}}
                        {{-- USER DETAILS --}}
                        {{-- ============================================= --}}

                        <div class="min-w-0">

                            <p class="truncate text-sm font-medium text-slate-950">

                                {{
                                    auth()->user()?->user_full_name
                                    ?? auth()->user()?->name
                                    ?? 'Purchaser'
                                }}

                            </p>


                            <p class="mt-0.5 truncate text-xs text-slate-500">

                                {{
                                    auth()->user()?->email
                                    ?? 'Purchaser Account'
                                }}

                            </p>

                        </div>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- PROFILE LINKS --}}
                {{-- ROUTES NOT CONNECTED YET --}}
                {{-- ================================================= --}}

                <div class="p-2">


                    {{-- ================================================= --}}
                    {{-- PROFILE SETTINGS --}}
                    {{-- ================================================= --}}

                    <a
                        href="#"

                        class="
                            flex
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-sm
                            text-slate-600
                            transition
                            hover:bg-slate-100
                            hover:text-slate-950
                        "
                    >

                        <i
                            data-lucide="user-cog"
                            class="h-4 w-4 text-slate-400"
                        ></i>

                        Profile settings

                    </a>



                    {{-- ================================================= --}}
                    {{-- SECURITY SETTINGS --}}
                    {{-- ================================================= --}}

                    <a
                        href="#"

                        class="
                            flex
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-sm
                            text-slate-600
                            transition
                            hover:bg-slate-100
                            hover:text-slate-950
                        "
                    >

                        <i
                            data-lucide="shield-check"
                            class="h-4 w-4 text-slate-400"
                        ></i>

                        Security settings

                    </a>

                </div>



                {{-- ================================================= --}}
                {{-- LOGOUT --}}
                {{-- ================================================= --}}

                <div class="border-t border-slate-100 p-2">

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >

                        @csrf


                        <button
                            type="submit"

                            class="
                                flex
                                w-full
                                items-center
                                gap-3
                                rounded-lg
                                px-3
                                py-2.5
                                text-sm
                                text-slate-600
                                transition
                                hover:bg-rose-50
                                hover:text-rose-600
                            "
                        >

                            <i
                                data-lucide="log-out"
                                class="h-4 w-4"
                            ></i>

                            Log out

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>



<style>

    /* ========================================================= */
    /* PURCHASER TOPBAR */
    /* SAME STRUCTURE AS MAINTENANCE TOPBAR */
    /* ========================================================= */

    .purchaser-topbar {

        height: 82px;

        flex-shrink: 0;

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 20px;

        padding: 0 28px;

        background: white;

        border-bottom: 1px solid #e5e7eb;

        position: relative;

        z-index: 30;

    }



    /* ========================================================= */
    /* LEFT SIDE */
    /* ========================================================= */

    .purchaser-topbar-left {

        min-width: 0;

        display: flex;

        align-items: center;

        gap: 12px;

    }



    /* ========================================================= */
    /* MOBILE SIDEBAR BUTTON */
    /* ========================================================= */

    .purchaser-mobile-sidebar-btn {

        width: 40px;

        height: 40px;

        flex-shrink: 0;

        display: none;

        align-items: center;

        justify-content: center;

        border: 0;

        border-radius: 10px;

        background: transparent;

        color: #64748b;

        cursor: pointer;

        transition: all 0.2s ease;

    }


    .purchaser-mobile-sidebar-btn:hover {

        background: #f1f5f9;

        color: #0f172a;

    }


    .purchaser-mobile-sidebar-btn i {

        width: 20px;

        height: 20px;

    }



    /* ========================================================= */
    /* RESPONSIVE TOPBAR */
    /* ========================================================= */

    @media (max-width: 1279px) {

        .purchaser-mobile-sidebar-btn {

            display: flex;

        }

    }


    @media (max-width: 639px) {

        .purchaser-topbar {

            height: 72px;

            padding-left: 14px;

            padding-right: 14px;

            gap: 10px;

        }

    }

</style>



<script>

    // =========================================================
    // GET PURCHASER DROPDOWNS
    // =========================================================

    function getPurchaserNotificationDropdown() {

        return document.getElementById(
            'purchaserNotificationDropdown'
        );

    }


    function getPurchaserProfileDropdown() {

        return document.getElementById(
            'purchaserProfileDropdown'
        );

    }



    // =========================================================
    // TOGGLE NOTIFICATIONS
    // =========================================================

    function togglePurchaserNotifications() {

        const notificationDropdown =
            getPurchaserNotificationDropdown();

        const profileDropdown =
            getPurchaserProfileDropdown();


        // =====================================================
        // STOP IF DROPDOWN DOES NOT EXIST
        // =====================================================

        if (!notificationDropdown) {

            return;

        }


        // =====================================================
        // CLOSE PROFILE DROPDOWN
        // =====================================================

        if (profileDropdown) {

            profileDropdown.classList.add('hidden');

        }


        // =====================================================
        // TOGGLE NOTIFICATION DROPDOWN
        // =====================================================

        notificationDropdown.classList.toggle('hidden');

    }



    // =========================================================
    // TOGGLE PROFILE DROPDOWN
    // =========================================================

    function togglePurchaserProfileDropdown() {

        const notificationDropdown =
            getPurchaserNotificationDropdown();

        const profileDropdown =
            getPurchaserProfileDropdown();


        // =====================================================
        // STOP IF DROPDOWN DOES NOT EXIST
        // =====================================================

        if (!profileDropdown) {

            return;

        }


        // =====================================================
        // CLOSE NOTIFICATION DROPDOWN
        // =====================================================

        if (notificationDropdown) {

            notificationDropdown.classList.add('hidden');

        }


        // =====================================================
        // TOGGLE PROFILE DROPDOWN
        // =====================================================

        profileDropdown.classList.toggle('hidden');

    }



    // =========================================================
    // CLOSE DROPDOWNS WHEN CLICKING OUTSIDE
    // =========================================================

    document.addEventListener('click', function (event) {

        const notificationDropdown =
            getPurchaserNotificationDropdown();

        const profileDropdown =
            getPurchaserProfileDropdown();


        // =====================================================
        // CHECK NOTIFICATION AREA
        // =====================================================

        const clickedNotificationArea =
            event.target.closest(
                '#purchaserNotificationDropdown'
            )
            ||
            event.target.closest(
                '[onclick="togglePurchaserNotifications()"]'
            );


        // =====================================================
        // CHECK PROFILE AREA
        // =====================================================

        const clickedProfileArea =
            event.target.closest(
                '#purchaserProfileDropdown'
            )
            ||
            event.target.closest(
                '[onclick="togglePurchaserProfileDropdown()"]'
            );


        // =====================================================
        // CLOSE NOTIFICATIONS
        // =====================================================

        if (
            notificationDropdown
            &&
            !clickedNotificationArea
        ) {

            notificationDropdown.classList.add('hidden');

        }


        // =====================================================
        // CLOSE PROFILE
        // =====================================================

        if (
            profileDropdown
            &&
            !clickedProfileArea
        ) {

            profileDropdown.classList.add('hidden');

        }

    });

</script>