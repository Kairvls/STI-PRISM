{{-- ===================================================== --}}
{{-- PURCHASER SIDEBAR --}}
{{-- ===================================================== --}}

<div id="sidebar">

    <div class="sidebar-header p-5">
        <div class="logo-icon">
            <img src="{{ asset('image/STI.png') }}" alt="STI Logo">
        </div>
        <div class="min-w-0">
            <h2>PaAyo</h2>
            <span>Purchaser</span>
        </div>
    </div>

    <div class="sidebar-content">

        <div class="sidebar-search">
            <div class="sidebar-dropdown">
                <button
                    type="button"
                    id="dropdownTrigger"
                    class="dropdown-trigger"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                    aria-controls="dropdownMenu"
                    aria-label="Jump to sidebar section"
                >
                    <div class="flex items-center gap-2">
                        <i class="h-5 w-5" data-lucide="search" aria-hidden="true"></i>
                        <span id="selectedSection">Search...</span>
                    </div>
                </button>
                <div
                    id="dropdownMenu"
                    class="dropdown-menu"
                    role="listbox"
                    aria-labelledby="dropdownTrigger"
                >
                    <div class="dropdown-item" role="option" data-target="dashboard-section" tabindex="0">
                        Dashboard
                    </div>
                    <div
                        class="dropdown-item"
                        role="option"
                        data-target="emergency-response-section"
                        tabindex="0"
                    >
                        Emergency Response
                    </div>
                    <div class="dropdown-item" role="option" data-target="procurement-section" tabindex="0">
                        Procurement
                    </div>
                    <div class="dropdown-item" role="option" data-target="file-maintenance-section" tabindex="0">
                        File Maintenance
                    </div>
                    <div
                        class="dropdown-item"
                        role="option"
                        data-target="purchasing-workflow-section"
                        tabindex="0"
                    >
                        Purchasing Workflow
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick actions (Receiving-matched) --}}
        <div class="quick-actions">
            <a
                href="{{ route('purchaser.dashboard') }}"
                class="quick-card {{ request()->routeIs('purchaser.dashboard') ? 'active' : '' }}"
            >
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>
            <a
                href="{{ route('purchaser.procurement.replacement-requests') }}"
                class="quick-card {{ request()->routeIs('purchaser.procurement.replacement-requests') ? 'active' : '' }}"
            >
                <i data-lucide="inbox"></i>
                <span>Requests</span>
            </a>
            <a
                href="{{ route('purchaser.ris.index') }}"
                class="quick-card {{ request()->routeIs('purchaser.ris*') ? 'active' : '' }}"
            >
                <i data-lucide="package-open"></i>
                <span>RIS</span>
            </a>
            <a
                href="{{ route('purchaser.purchase-orders.index') }}"
                class="quick-card {{ request()->routeIs('purchaser.purchase-orders*') ? 'active' : '' }}"
            >
                <i data-lucide="shopping-bag"></i>
                <span>Orders</span>
            </a>
        </div>

        <div class="menu-title" id="dashboard-section">
            DASHBOARD
        </div>


        <a
            href="{{ route('purchaser.dashboard') }}"

            class="
                menu-item

                {{
                    request()->routeIs('purchaser.dashboard')
                        ? 'active'
                        : ''
                }}
            "
        >

            <i
                data-lucide="layout-dashboard"
                class="h-5 w-5"
            ></i>

            <span>
                Dashboard
            </span>

        </a>


        {{-- ===================================================== --}}
        {{-- EMERGENCY RESPONSE SECTION --}}
        {{-- ===================================================== --}}

        <!--<div class="menu-title" id="emergency-response-section">
            EMERGENCY RESPONSE
        </div>


        {{-- ===================================================== --}}
        {{-- URGENT REPORTS --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.reports.urgent') }}"

            class="
                menu-item

                {{
                    request()->routeIs('purchaser.reports.urgent*')
                        ? 'active'
                        : ''
                }}
            "
        >

            <i
                data-lucide="triangle-alert"
                class="h-5 w-5"
            ></i>

            <span>
                Urgent Reports
            </span>

        </a>-->

        {{-- ===================================================== --}}
        {{-- FILE MAINTENANCE SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title" id="file-maintenance-section">
            FILE MAINTENANCE
        </div>

        <a
            href="{{ route('purchaser.suppliers.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.suppliers.*') ? 'active' : '' }}"
        >
            <i data-lucide="truck" class="h-5 w-5"></i>
            <span>Suppliers</span>
        </a>

        @php
            $fmActive = request()->routeIs('purchaser.file-maintenance.*')
                || request()->routeIs('purchaser.brands.*')
                || request()->routeIs('purchaser.uom.*')
                || request()->routeIs('purchaser.categories.*')
                || request()->routeIs('purchaser.subcategories.*');
            $fmTab = request('tab', 'brands');
            if (request()->routeIs('purchaser.brands.*')) {
                $fmTab = 'brands';
            } elseif (request()->routeIs('purchaser.uom.*')) {
                $fmTab = 'uom';
            } elseif (request()->routeIs('purchaser.categories.*')) {
                $fmTab = 'categories';
            } elseif (request()->routeIs('purchaser.subcategories.*')) {
                $fmTab = 'subcategories';
            }
            $fmLinks = [
                'brands' => ['title' => 'Brands', 'icon' => 'tag'],
                'uom' => ['title' => 'UOM', 'icon' => 'ruler'],
                'categories' => ['title' => 'Categories', 'icon' => 'folders'],
                'subcategories' => ['title' => 'Sub Categories', 'icon' => 'folder-tree'],
            ];
        @endphp

        <div class="menu-group {{ $fmActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $fmActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $fmActive ? 'true' : 'false' }}"
            >
                <i data-lucide="database" class="h-5 w-5"></i>
                <span>File Maintenance</span>
                <i data-lucide="chevron-down" class="menu-group-chevron h-4 w-4"></i>
            </button>
            <div class="menu-sub" @if(! $fmActive) hidden @endif>
                @foreach($fmLinks as $key => $meta)
                    <a
                        href="{{ route('purchaser.file-maintenance.index', ['tab' => $key]) }}"
                        class="menu-sub-item {{ $fmActive && $fmTab === $key ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $meta['icon'] }}" class="h-4 w-4"></i>
                        <span>{{ $meta['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>



        {{-- ===================================================== --}}
        {{-- PROCUREMENT SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title" id="procurement-section">
            PROCUREMENT REQUESTS
        </div>


        {{-- ===================================================== --}}
        {{-- REPLACEMENT REQUESTS --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.procurement.replacement-requests') }}"

            class="
                menu-item

                {{
                    request()->routeIs(
                        'purchaser.procurement.replacement-requests'
                    )
                        ? 'active'
                        : ''
                }}
            "
        >

            <i
                data-lucide="inbox"
                class="h-5 w-5"
            ></i>

            <span>
                Replacement Requests
            </span>

        </a>



        



        {{-- ===================================================== --}}
        {{-- PURCHASING WORKFLOW SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title" id="purchasing-workflow-section">
            PROCUREMENT WORKFLOW
        </div>

        {{-- ===================================================== --}}
        {{-- RIS --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.ris.index') }}"
            class="
                menu-item mt-1

                {{
                    request()->routeIs('purchaser.ris*')
                        ? 'active'
                        : ''
                }}
            "
        >

            <i
                data-lucide="package-open"
                class="h-5 w-5"
            ></i>

            <span>
                RIS
            </span>

        </a>

        {{-- ===================================================== --}}
        {{-- ATP (Authority to Purchase + Purchase Orders) --}}
        {{-- ===================================================== --}}

        @php
            $atpGroupActive = request()->routeIs('purchaser.atp*')
                || request()->routeIs('purchaser.purchase-orders*');
        @endphp

        <div class="menu-group {{ $atpGroupActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $atpGroupActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $atpGroupActive ? 'true' : 'false' }}"
            >
                <i data-lucide="file-check-2" class="h-5 w-5"></i>
                <span>ATP</span>
                <i data-lucide="chevron-down" class="menu-group-chevron h-4 w-4"></i>
            </button>
            <div class="menu-sub" @if(! $atpGroupActive) hidden @endif>
                <a
                    href="{{ route('purchaser.atp.index') }}"
                    class="menu-sub-item {{ request()->routeIs('purchaser.atp*') ? 'active' : '' }}"
                >
                    <i data-lucide="file-check-2" class="h-4 w-4"></i>
                    <span>Authority to Purchase</span>
                </a>
                <a
                    href="{{ route('purchaser.purchase-orders.index') }}"
                    class="menu-sub-item {{ request()->routeIs('purchaser.purchase-orders*') ? 'active' : '' }}"
                >
                    <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                    <span>Purchase Orders</span>
                </a>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- REQUEST CHECK --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.rfc.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.rfc*') ? 'active' : '' }}"
        >

            <i
                data-lucide="clipboard-check"
                class="h-5 w-5"
            ></i>

            <span>
                RFC / Cash Advance
            </span>

        </a>



        {{-- ===================================================== --}}
        {{-- RECEIVING REPORTS --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.rr.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.rr*') ? 'active' : '' }}"
        >

            <i
                data-lucide="package-check"
                class="h-5 w-5"
            ></i>

            <span>
                Receiving Reports
            </span>

        </a>

        {{-- ===================================================== --}}
        {{-- LIQUIDATION REPORTS --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.liq.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.liq*') ? 'active' : '' }}"
        >

            <i
                data-lucide="receipt-text"
                class="h-5 w-5"
            ></i>

            <span>
                Liquidation Reports
            </span>

        </a>

        <a
            href="{{ route('purchaser.procurement-records.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.procurement-records*') ? 'active' : '' }}"
        >

            <i
                data-lucide="folder-archive"
                class="h-5 w-5"
            ></i>

            <span>
                Compiled Records
            </span>

        </a>

        






        <div class="menu-title" id="account-section">
            ACCOUNT
        </div>

        <a
            href="{{ route('purchaser.profile') }}"
            class="menu-item mt-1 {{ request()->is('purchaser/profile*') || request()->is('purchaser/security*') ? 'active' : '' }}"
        >
            <i data-lucide="user-cog" class="h-5 w-5"></i>
            <span>Account settings</span>
        </a>

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
    padding: 20px 20px 20px 20px;
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
        background: linear-gradient(135deg, #64748b, #475569);
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
        border-color: rgba(255, 255, 255, 0.18);
        transform: translateY(-2px);
    }
    .quick-card i,
    .quick-card svg {
        width: 16px;
        height: 16px;
        color: #ffffff;
        transition: all 0.2s ease;
    }
    .quick-card span {
        font-size: 11px;
        font-weight: 500;
    }
    .quick-card.active {
        border: 1.5px solidrgb(0, 255, 85) !important;
        color: #cbd5e1;
        font-weight: 600;
        box-shadow: 0 0 12px rgba(255, 242, 0, 0.18);
    }
    .quick-card.active i,
    .quick-card.active svg {
        color: #ffffff;
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

    This makes the menu icon align with:
    PaAyo logo
    Search box
    Quick Actions
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

.menu-group.is-open > .menu-group-toggle .menu-group-chevron {
    transform: rotate(180deg);
    color: #94a3b8;
}

.menu-group-toggle.active-parent,
.menu-group-toggle.active-parent span {
    color: #ffffff;
}

.menu-group-toggle.active-parent svg:not(.menu-group-chevron) {
    color: #fde68a !important;
    stroke: #fde68a !important;
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

    .section-highlight {
        color: #fde68a !important;
        text-shadow: 0 0 10px rgba(253, 230, 138, 0.45);
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
        border: none;
        background: transparent;
        color: inherit;
        font: inherit;
        text-align: left;
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
        color: #fde68a;
    }
</style>

<script>
    // =====================================================
    // SIDEBAR ELEMENTS
    // =====================================================

    const trigger =
        document.getElementById("dropdownTrigger");

    const menu =
        document.getElementById("dropdownMenu");

    const selected =
        document.getElementById("selectedSection");

    const sidebarContent =
        document.querySelector(".sidebar-content");

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
            if (window.lucide && window.lucide.createIcons) {
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
    // SEARCH DROPDOWN OPEN / CLOSE
    // =====================================================

    if (trigger && menu) {

        trigger.addEventListener("click", () => {

            const isOpen = menu.style.display === "block";

            menu.style.display = isOpen ? "none" : "block";

            trigger.setAttribute(
                "aria-expanded",
                isOpen ? "false" : "true"
            );

        });

    }


    // =====================================================
    // SEARCH DROPDOWN SECTION NAVIGATION
    // =====================================================

    document
        .querySelectorAll(".dropdown-item")
        .forEach((item) => {

            item.addEventListener("click", () => {

                // =====================================================
                // UPDATE SEARCH LABEL
                // =====================================================

                if (selected) {

                    selected.textContent =
                        item.textContent.trim();

                }


                // =====================================================
                // GET TARGET SECTION
                // =====================================================

                const target =
                    document.getElementById(
                        item.dataset.target
                    );


                if (target && sidebarContent) {

                    // =====================================================
                    // GET SEARCH AREA HEIGHT
                    // =====================================================

                    const searchArea =
                        document.querySelector(
                            ".sidebar-search"
                        );

                    const searchHeight =
                        searchArea?.offsetHeight ?? 0;


                    // =====================================================
                    // SCROLL TO SELECTED SECTION
                    // =====================================================

                    sidebarContent.scrollTo({

                        top:
                            target.offsetTop
                            - searchHeight
                            - 20,

                        behavior: "smooth",

                    });


                    // =====================================================
                    // HIGHLIGHT SELECTED SECTION
                    // =====================================================

                    target.classList.add(
                        "section-highlight"
                    );


                    setTimeout(() => {

                        target.classList.remove(
                            "section-highlight"
                        );

                    }, 2000);

                }


                // =====================================================
                // CLOSE SEARCH DROPDOWN
                // =====================================================

                if (menu) {

                    menu.style.display = "none";

                }

                if (trigger) {

                    trigger.setAttribute("aria-expanded", "false");

                }

            });

        });


    // =====================================================
    // CLOSE SEARCH DROPDOWN WHEN CLICKING OUTSIDE
    // =====================================================

    document.addEventListener("click", (event) => {

        if (!trigger || !menu) {
            return;
        }


        if (
            !trigger.contains(event.target) &&
            !menu.contains(event.target)
        ) {

            menu.style.display = "none";

            trigger.setAttribute("aria-expanded", "false");

        }

    });


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
