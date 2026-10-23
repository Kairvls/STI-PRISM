{{-- Shared design tokens (Inter/Outfit) + Admin color theme --}}
@include('layouts.partials.admin-design')
@include('layouts.partials.admin-grayscale-theme')

<style>
    /* Receiving pages render in layouts.app <main>; Poppins is set on * */
    main,
    main * {
        font-family: "Inter", sans-serif !important;
    }

    main h1,
    main .admin-page-title,
    main .dashboard-title,
    main .stat-value,
    main .admin-stat-card-value,
    main [class*="Outfit"] {
        font-family: "Outfit", sans-serif !important;
    }
</style>

<div id="sidebar">

    {{-- ====================================== --}}
    {{-- HEADER --}}
    {{-- ====================================== --}}

    <div class="sidebar-header px-5 pt-5 pb-2">

        <div class="logo-icon">

            <img src="{{ asset('image/Paayo_Sidebar.png') }}" alt="">

        </div>

        <div>

            <h2>PaAyo</h2>

            <span>{{ \App\Support\RoleAccess::sidebarPortalLabel('receiving') }}</span>

        </div>

    </div>

    <div class="sidebar-content">

        {{-- ====================================== --}}
        {{-- DASHBOARD --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="dashboard-section">

            DASHBOARD

        </div>

        <a
            href="/receiving/dashboard"
            class="menu-item {{ request()->is('receiving/dashboard') ? 'active' : '' }}"
        >

            <i data-lucide="layout-dashboard"></i>

            <span>Dashboard</span>

        </a>

        {{-- ====================================== --}}
        {{-- RECEIVING --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="receiving-section">

            RECEIVING

        </div>

        @php
            $recvCounts = \App\Support\ReceivingSidebarCounts::counts();
            $navCount = 'menu-count ml-auto flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#0025cc] px-1.5 text-[10px] font-bold leading-none !text-white';
        @endphp

        <a
            href="/receiving/reports"
            class="menu-item {{ request()->is('receiving/reports*') ? 'active' : '' }}"
        >

            <i data-lucide="clipboard-list"></i>

            <span>Pending Receiving Reports</span>

            @if($recvCounts['pending'] > 0)
                <span class="{{ $navCount }}" title="{{ $recvCounts['pending'] }} not viewed yet">{{ $recvCounts['pending'] }}</span>
            @endif

        </a>

        @php
            $boGroupActive = request()->is('receiving/back-orders*');
            $activeBoPayment = $boGroupActive ? request('payment') : null;
            $boLinks = [
                ['payment' => null, 'label' => 'All Back Orders', 'icon' => 'layers'],
                ['payment' => 'dp', 'label' => 'Direct Payment', 'icon' => 'clipboard-check'],
                ['payment' => 'ci', 'label' => 'Cash Installment', 'icon' => 'calendar-range'],
                ['payment' => 'ca', 'label' => 'Cash Advance', 'icon' => 'banknote'],
            ];
        @endphp

        <div class="menu-group {{ $boGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $boGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $boGroupActive ? 'true' : 'false' }}"
            >
                <span class="menu-icon-wrap">
                    <i data-lucide="package-x"></i>
                </span>
                <span>Back Orders</span>
                @if($recvCounts['back_orders'] > 0)
                    <span class="{{ $navCount }}" title="{{ $recvCounts['back_orders'] }} not viewed yet">{{ $recvCounts['back_orders'] }}</span>
                @endif
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $boGroupActive) hidden @endif>
                @foreach($boLinks as $boLink)
                    <a
                        href="{{ route('receiving.back-orders.index', array_filter(['payment' => $boLink['payment']])) }}"
                        class="menu-sub-item {{ $boGroupActive && $activeBoPayment === $boLink['payment'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $boLink['icon'] }}"></i>
                        <span>{{ $boLink['label'] }}</span>
                        @php
                            $boCount = match ($boLink['payment']) {
                                'dp' => $recvCounts['dp'],
                                'ci' => $recvCounts['ci'],
                                'ca' => $recvCounts['ca'],
                                default => $recvCounts['back_orders'],
                            };
                        @endphp
                        @if($boCount > 0)
                            <span class="{{ $navCount }}" title="{{ $boCount }} not viewed yet">{{ $boCount }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <a
            href="/receiving/delivered-items"
            class="menu-item {{ request()->is('receiving/delivered-items') ? 'active' : '' }}"
        >

            <i data-lucide="truck"></i>

            <span>Delivered Items</span>

        </a>

        <a
            href="/receiving/supplier-records"
            class="menu-item {{ request()->is('receiving/supplier-records') ? 'active' : '' }}"
        >

            <i data-lucide="building-2"></i>

            <span>Supplier lookup</span>

        </a>

        {{-- ====================================== --}}
        {{-- HISTORY --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="history-section">

            HISTORY

        </div>

        <a
            href="/receiving/history"
            class="menu-item {{ request()->is('receiving/history') ? 'active' : '' }}"
        >

            <i data-lucide="history"></i>

            <span>Delivery History</span>

        </a>

        <a
            href="/receiving/logs"
            class="menu-item {{ request()->is('receiving/logs') ? 'active' : '' }}"
        >

            <i data-lucide="scroll-text"></i>

            <span>Receiving Logs</span>

        </a>

        {{-- ====================================== --}}
        {{-- ACCOUNT --}}
        {{-- ====================================== --}}

        <!--<div class="menu-title" id="account-section">

            ACCOUNT

        </div>

        <a
            href="{{ route('receiving.profile') }}"
            class="menu-item {{ request()->is('receiving/profile*') || request()->is('receiving/security*') ? 'active' : '' }}"
        >

            <i data-lucide="user-cog"></i>

            <span>Account settings</span>

        </a>-->

    </div>

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

    /* TOP | RIGHT | BOTTOM | LEFT */
    padding: 0 20px 32px 20px;
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


    /*
    REMOVE THE EXTRA INDENTATION.

    sidebar-content ALREADY PROVIDES 20px.
    */

    padding-left: 0;

    transition: all 0.3s ease;
}

.sidebar-content > .menu-title:first-child {
    margin-top: 0;
}

    

    /* ======================================
   MENU ITEMS
====================================== */

.menu-item {
    position: relative;

    height: 48px;

    display: flex;
    align-items: center;

    gap: 12px;

    /*
    IMPORTANT:

    sidebar-content already has 20px padding.

    DO NOT add another 14px horizontal padding.

    This makes the menu icon align with the PaAyo logo.
    */

    padding: 0;

    border-radius: 0;

    color: #94a3b8;

    text-decoration: none;

    font-size: 14px;

    font-weight: 400;

    margin-bottom: 2px;

    transition:
        color 0.2s ease,
        opacity 0.2s ease;
}


/* ======================================
   MENU ICON

   LUCIDE CONVERTS <i> INTO <svg>
====================================== */

.menu-item svg {
    width: 18px;

    height: 18px;

    flex-shrink: 0;

    color: #94a3b8;

    stroke: currentColor;

    transition: color 0.2s ease;
}


/* ======================================
   MENU TEXT
====================================== */

.menu-item span {
    color: inherit;
}

.menu-icon-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 18px;
    height: 18px;
}

.menu-notif-dot {
    position: absolute;
    top: -3px;
    right: -4px;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #fbbf24;
    border: 1.5px solid #0d1120;
    pointer-events: none;
}

/* ======================================
   MENU HOVER
====================================== */

.menu-item:hover {
    color: #ffffff;

    background: transparent;
}


.menu-item:hover svg {
    color: #ffffff;

    stroke: #ffffff;
}


/* ======================================
   ACTIVE MENU ITEM
====================================== */

.menu-item.active {
    background: transparent;

    color: #ffffff;

    font-weight: 600;
}


/* ======================================
   ACTIVE LEFT INDICATOR
====================================== */

.menu-item.active::before {
    content: "";

    position: absolute;


    /*
    MOVE THROUGH sidebar-content
    20px LEFT PADDING.

    THIS MAKES IT TOUCH THE SIDEBAR EDGE.
    */

    left: -20px;


    top: 50%;

    transform: translateY(-50%);


    width: 5px;

    height: 32px;


    background: #fde68a;


    border-radius: 0 5px 5px 0;
}


/* ======================================
   ACTIVE ICON

   USE SVG BECAUSE OF LUCIDE
====================================== */

.menu-item.active svg {
    color: #fde68a !important;

    stroke: #fde68a !important;
}


/* ======================================
   ACTIVE TEXT
====================================== */

.menu-item.active span {
    color: #ffffff;
}

/* ======================================
   COLLAPSIBLE MENU GROUP
====================================== */

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

.menu-group-toggle .menu-count + .menu-group-chevron {
    margin-left: 8px;
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

.menu-group-toggle.active-parent svg:not(.menu-group-chevron) {
    color: #fde68a;
    stroke: #fde68a;
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
    color: #fde68a;
    background: rgba(253, 230, 138, 0.08);
    font-weight: 500;
}

.menu-sub-item.active svg {
    color: #fde68a;
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
        background: linear-gradient(135deg, #64748b, #475569);
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

</style>

<script>
    // =====================================================
    // SIDEBAR ELEMENTS
    // =====================================================

    const sidebarContent =
        document.querySelector("#sidebar .sidebar-content");


    // =====================================================
    // COLLAPSIBLE MENU GROUPS
    // =====================================================

    document.querySelectorAll("[data-menu-group]").forEach((group) => {
        const toggle = group.querySelector("[data-menu-group-toggle]");
        const panel = group.querySelector(".menu-sub");
        if (!toggle || !panel) return;

        toggle.addEventListener("click", () => {
            const willOpen = !group.classList.contains("is-open");
            group.classList.toggle("is-open", willOpen);
            panel.hidden = !willOpen;
            toggle.setAttribute("aria-expanded", willOpen ? "true" : "false");
            if (window.lucide && typeof window.lucide.createIcons === "function") {
                window.lucide.createIcons();
            }
        });
    });


    // =====================================================
    // SIDEBAR STORAGE KEY
    // =====================================================

    const sidebarScrollKey =
        "maintenanceSidebarScrollPosition";


    // =====================================================
    // SAVE SIDEBAR SCROLL POSITION
    // =====================================================

    if (sidebarContent) {

        sidebarContent.addEventListener("scroll", () => {

            sessionStorage.setItem(

                sidebarScrollKey,

                sidebarContent.scrollTop

            );

        });

    }


    // =====================================================
    // RESTORE SIDEBAR POSITION AFTER PAGE LOAD
    // =====================================================

    window.addEventListener("DOMContentLoaded", () => {

        if (!sidebarContent) {
            return;
        }


        // =====================================================
        // GET PREVIOUS SCROLL POSITION
        // =====================================================

        const savedScrollPosition =
            sessionStorage.getItem(
                sidebarScrollKey
            );


        // =====================================================
        // RESTORE PREVIOUS POSITION
        // =====================================================

        if (savedScrollPosition !== null) {

            sidebarContent.scrollTop =
                Number(savedScrollPosition);

        }


        // =====================================================
        // GET ACTIVE MENU ITEM
        // =====================================================

        const activeMenuItem =
            document.querySelector(
                ".menu-item.active"
            );


        if (!activeMenuItem) {
            return;
        }


        // =====================================================
        // WAIT UNTIL BROWSER FINISHES LAYOUT
        // =====================================================

        requestAnimationFrame(() => {

            // =====================================================
            // GET CURRENT POSITIONS
            // =====================================================

            const sidebarRect =
                sidebarContent.getBoundingClientRect();

            const itemRect =
                activeMenuItem.getBoundingClientRect();


            // =====================================================
            // SAFE VISIBLE AREA
            // =====================================================

            const topPadding = 20;

            const bottomPadding = 20;


            // =====================================================
            // ACTIVE ITEM IS ABOVE VISIBLE AREA
            // =====================================================

            if (
                itemRect.top <
                sidebarRect.top + topPadding
            ) {

                sidebarContent.scrollTop -=

                    sidebarRect.top
                    + topPadding
                    - itemRect.top;

            }


            // =====================================================
            // ACTIVE ITEM IS BELOW VISIBLE AREA
            // =====================================================

            else if (
                itemRect.bottom >
                sidebarRect.bottom - bottomPadding
            ) {

                sidebarContent.scrollTop +=

                    itemRect.bottom
                    - sidebarRect.bottom
                    + bottomPadding;

            }


            // =====================================================
            // SAVE FINAL POSITION
            // =====================================================

            sessionStorage.setItem(

                sidebarScrollKey,

                sidebarContent.scrollTop

            );

        });

    });
</script>