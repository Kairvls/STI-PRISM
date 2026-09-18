{{-- Admin design tokens (Inter/Outfit + STI blue) — matches Maintenance Personnel --}}
@include('layouts.partials.admin-design')
@include('layouts.partials.admin-grayscale-theme')

<div id="sidebar">

    {{-- ====================================== --}}
    {{-- HEADER --}}
    {{-- ====================================== --}}

    <div class="sidebar-header p-5">

        <div class="logo-icon">

            <img src="{{ asset('image/STI.png') }}" alt="">

        </div>

        <div>

            <h2>PaAyo</h2>

            <span>Administrator Panel</span>

        </div>

    </div>

    <div class="sidebar-content">

        {{-- ====================================== --}}
        {{-- SEARCH --}}
        {{-- ====================================== --}}

        <div class="sidebar-search">

            <div class="sidebar-dropdown">

                <div id="dropdownTrigger" class="dropdown-trigger">

                    <div class="flex items-center gap-2">

                        <i data-lucide="search"></i>

                        <span id="selectedSection">Search...</span>

                    </div>

                </div>

                <div id="dropdownMenu" class="dropdown-menu">

                    <div class="dropdown-item" data-target="dashboard-section">
                        Dashboard
                    </div>

                    <div class="dropdown-item" data-target="procurement-section">
                        Procurement
                    </div>

                    <div class="dropdown-item" data-target="signature-section">
                        Digital Signatures
                    </div>

                    <div class="dropdown-item" data-target="operations-section">
                        Operations
                    </div>

                    

                    <div class="dropdown-item" data-target="users-section">
                        User Management
                    </div>

                    <div class="dropdown-item" data-target="reports-section">
                        Reports
                    </div>

                    <div class="dropdown-item" data-target="settings-section">
                        System
                    </div>

                </div>

            </div>

        </div>

        {{-- ====================================== --}}
        {{-- QUICK ACTIONS --}}
        {{-- ====================================== --}}

        <div class="quick-actions">

            <a
                href="/admin/procurement-review"
                class="quick-card {{ request()->is('admin/procurement-review*') ? 'active' : '' }}"
            >

                <i data-lucide="clipboard-check"></i>

                <span>Review RIS</span>

            </a>

            <a
                href="/admin/digital-signatures/sign-ris"
                class="quick-card {{ request()->is('admin/digital-signatures/sign-ris*') ? 'active' : '' }}"
            >

                <i data-lucide="pen-tool"></i>

                <span>Sign RIS</span>

            </a>

            <a
                href="/admin/users"
                class="quick-card {{ request()->is('admin/users*') ? 'active' : '' }}"
            >

                <i data-lucide="users"></i>

                <span>Users</span>

            </a>

            <a
                href="/admin/settings/campus-setup-pin"
                class="quick-card {{ request()->is('admin/settings*') ? 'active' : '' }}"
            >

                <i data-lucide="settings"></i>

                <span>Settings</span>

            </a>

        </div>

        {{-- ====================================== --}}
        {{-- DASHBOARD --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="dashboard-section">

            DASHBOARD

        </div>

        <a
            href="/admin/dashboard"
            class="menu-item {{ request()->is('admin/dashboard') ? 'active' : '' }}"
        >

            <i data-lucide="layout-dashboard"></i>

            <span>Dashboard</span>

        </a>

        {{-- ====================================== --}}
        {{-- PROCUREMENT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="procurement-section">

            PROCUREMENT

        </div>

        <a
            href="/admin/procurement-review"
            class="menu-item {{ request()->is('admin/procurement-review*') ? 'active' : '' }}"
        >

            <span class="menu-icon-wrap">
                <i data-lucide="clipboard-check"></i>
                @if(($adminSidebarPendingRis ?? 0) > 0)
                    <span class="menu-notif-dot" title="{{ $adminSidebarPendingRis }} pending accept"></span>
                @endif
            </span>

            <span>Procurement Requests</span>

        </a>

        {{-- ====================================== --}}
        {{-- DIGITAL SIGNATURES --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="signature-section">

            DIGITAL SIGNATURES

        </div>
        <a
            href="/admin/digital-signatures/sign-ris"
            class="menu-item {{ request()->is('admin/digital-signatures/sign-ris') ? 'active' : '' }}"
        >

            <span class="menu-icon-wrap">
                <i data-lucide="pen-tool"></i>
                @if(($adminSidebarAwaitingCosign ?? 0) > 0)
                    <span class="menu-notif-dot" title="{{ $adminSidebarAwaitingCosign }} awaiting Sign RIS action"></span>
                @endif
            </span>

            <span>Sign RIS</span>

        </a>

        <a
            href="/admin/digital-signatures/history"
            class="menu-item {{ request()->is('admin/digital-signatures/history') ? 'active' : '' }}"
        >

            <i data-lucide="history"></i>

            <span>Signature History</span>

        </a>

        {{-- ====================================== --}}
        {{-- OPERATIONS --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="operations-section">

            OPERATIONS

        </div>

        <a
            href="{{ route('admin.operations.overview') }}"
            class="menu-item {{ request()->is('admin/operations') && !request()->is('admin/operations/*') ? 'active' : '' }}"
        >
            <i data-lucide="radar"></i>
            <span>Command Center</span>
        </a>

        <a
            href="{{ route('admin.operations.equipment') }}"
            class="menu-item {{ request()->is('admin/operations/equipment*') ? 'active' : '' }}"
        >
            <i data-lucide="monitor"></i>
            <span>Equipment</span>
        </a>

        <a
            href="{{ route('admin.operations.schedules') }}"
            class="menu-item {{ request()->is('admin/operations/schedules*') ? 'active' : '' }}"
        >
            <i data-lucide="calendar-clock"></i>
            <span>Schedules</span>
        </a>

        <a
            href="{{ route('admin.operations.reports') }}"
            class="menu-item {{ request()->is('admin/operations/reports*') ? 'active' : '' }}"
        >
            <i data-lucide="clipboard-list"></i>
            <span>Equipment Reports</span>
        </a>

        <a
            href="{{ route('admin.operations.procurement') }}"
            class="menu-item {{ request()->is('admin/operations/procurement*') || request()->is('admin/operations/documents*') ? 'active' : '' }}"
        >
            <i data-lucide="git-branch"></i>
            <span>Procurement Monitor</span>
        </a>

        @php
            $movementsSectionActive = request()->is('admin/operations/movements*');
            $movementsTab = request('tab', 'transfers');
            $movementLinks = [
                [
                    'url' => route('admin.operations.movements', ['tab' => 'transfers']),
                    'label' => 'Transfers',
                    'icon' => 'arrow-right-left',
                    'active' => $movementsSectionActive && $movementsTab === 'transfers',
                ],
                [
                    'url' => route('admin.operations.movements', ['tab' => 'borrowing']),
                    'label' => 'Borrowing',
                    'icon' => 'hand',
                    'active' => $movementsSectionActive && $movementsTab === 'borrowing',
                ],
                [
                    'url' => route('admin.operations.movements', ['tab' => 'disposal']),
                    'label' => 'Disposal',
                    'icon' => 'trash-2',
                    'active' => $movementsSectionActive && $movementsTab === 'disposal',
                ],
            ];
        @endphp

        <div class="menu-group {{ $movementsSectionActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $movementsSectionActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $movementsSectionActive ? 'true' : 'false' }}"
            >
                <i data-lucide="arrow-left-right"></i>
                <span>Equipment Movements</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $movementsSectionActive) hidden @endif>
                @foreach($movementLinks as $movementLink)
                    <a
                        href="{{ $movementLink['url'] }}"
                        class="menu-sub-item {{ $movementLink['active'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $movementLink['icon'] }}"></i>
                        <span>{{ $movementLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        

        {{-- ====================================== --}}
        {{-- USER MANAGEMENT --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="users-section">

            USER MANAGEMENT

        </div>

        <a
            href="/admin/users"
            class="menu-item {{ request()->is('admin/users') ? 'active' : '' }}"
        >

            <i data-lucide="users"></i>

            <span>Users</span>

        </a>

        {{-- ====================================== --}}
        {{-- REPORTS --}}
        {{-- ====================================== --}}

        @php
            $reportsSectionActive = request()->is('admin/reports*');
            $reportLinks = [
                'maintenance' => [
                    'url' => url('/admin/reports/maintenance-history'),
                    'label' => 'Maintenance',
                    'icon' => 'wrench',
                    'active' => request()->is('admin/reports/maintenance-history*'),
                ],
                'receiving' => [
                    'url' => url('/admin/reports/receiving'),
                    'label' => 'Receiving',
                    'icon' => 'package-check',
                    'active' => request()->is('admin/reports/receiving*'),
                ],
                'approvals' => [
                    'url' => url('/admin/reports/approval-logs'),
                    'label' => 'Approvals',
                    'icon' => 'stamp',
                    'active' => request()->is('admin/reports/approval-logs*') || request()->is('admin/reports/audit-logs*'),
                ],
                'access' => [
                    'url' => url('/admin/reports/user-login-logs'),
                    'label' => 'User access',
                    'icon' => 'shield',
                    'active' => request()->is('admin/reports/user-login-logs*'),
                ],
            ];
        @endphp

        <div class="menu-title" id="reports-section">

            REPORTS

        </div>

        <div class="menu-group {{ $reportsSectionActive ? 'is-open' : '' }}" data-menu-group>
            <button
                type="button"
                class="menu-item menu-group-toggle {{ $reportsSectionActive ? 'active-parent' : '' }}"
                data-menu-group-toggle
                aria-expanded="{{ $reportsSectionActive ? 'true' : 'false' }}"
            >
                <span class="menu-icon-wrap">
                    <i data-lucide="file-text"></i>
                    @if(($adminSidebarAmendRis ?? 0) > 0)
                        <span class="menu-notif-dot" title="{{ $adminSidebarAmendRis }} amendment(s)"></span>
                    @endif
                </span>
                <span>System Reports</span>
                <i data-lucide="chevron-down" class="menu-group-chevron"></i>
            </button>
            <div class="menu-sub" @if(! $reportsSectionActive) hidden @endif>
                @foreach($reportLinks as $reportLink)
                    <a
                        href="{{ $reportLink['url'] }}"
                        class="menu-sub-item {{ $reportLink['active'] ? 'active' : '' }}"
                    >
                        <i data-lucide="{{ $reportLink['icon'] }}"></i>
                        <span>{{ $reportLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ====================================== --}}
        {{-- SYSTEM --}}
        {{-- ====================================== --}}

        <div class="menu-title" id="settings-section">

            SYSTEM

        </div>

        <a
            href="/admin/profile"
            class="menu-item {{ request()->is('admin/profile') || request()->is('admin/security') ? 'active' : '' }}"
        >

            <i data-lucide="user-cog"></i>

            <span>Account settings</span>

        </a>

        <a
            href="/admin/settings/campus-setup-pin"
            class="menu-item {{ request()->is('admin/settings*') ? 'active' : '' }}"
        >

            <i data-lucide="settings"></i>

            <span>System Settings</span>

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
        z-index: 40;
        background: #111827;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0;
        height: 30px;
        margin-bottom: 14px;
        font-size: 12px;
        overflow: visible;
    }
    .sidebar-search i {
        width: 12px;
        height: 12px;
        color: #64748b;
        flex-shrink: 0;
    }
    .sidebar-search #selectedSection {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 180px;
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
        border-color: #93c5fd;
        transform: translateY(-2px);
    }
    .quick-card i {
        width: 16px;
        height: 16px;
        color: #93c5fd;
        transition: all 0.2s ease;
    }
    .quick-card span {
        font-size: 11px;
        font-weight: 500;
    }

    /* NEW ACTIVE STATE FOR QUICK ACTIONS */
    .quick-card.active {
        border: 1.5px solid #60a5fa !important;
        color: #cbd5e1;
        font-weight: 600;
        box-shadow: 0 0 12px rgba(96, 165, 250, 0.22);
    }
    .quick-card.active i {
        color: #93c5fd;
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


    background: #fbbf24;


    border-radius: 0 5px 5px 0;
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
    font-weight: 600;
}

.menu-group-toggle.active-parent svg:not(.menu-group-chevron) {
    color: #fbbf24;
    stroke: #fbbf24;
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
    color: #fbbf24;
    background: rgba(251, 191, 36, 0.08);
    font-weight: 500;
}

.menu-sub-item.active svg {
    color: #fbbf24;
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
        text-shadow: 0 0 10px rgba(251, 191, 36, 0.35);
    }

    .sidebar-dropdown {
        width: 100%;
        position: relative;
        z-index: 40;
    }
    .dropdown-trigger {
        width: 100%;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        padding: 0 10px;
    }
    .dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        width: 100%;
        background: #111827;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        overflow: hidden;
        z-index: 50;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
    }
    .dropdown-item {
        padding: 12px 14px;
        cursor: pointer;
        transition: 0.2s;
        font-size: 14px;
        color: #cbd5e1;
    }
    .dropdown-item:hover {
        background: #1f2937;
        color: #e2e8f0;
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
    // SEARCH DROPDOWN OPEN / CLOSE
    // =====================================================

    if (trigger && menu) {

        trigger.addEventListener("click", (event) => {

            event.stopPropagation();

            menu.style.display =
                menu.style.display === "block"
                    ? "none"
                    : "block";

        });

    }


    // =====================================================
    // SEARCH DROPDOWN SECTION NAVIGATION
    // =====================================================

    document
        .querySelectorAll(".dropdown-item")
        .forEach((item) => {

            item.addEventListener("click", (event) => {

                event.stopPropagation();

                // Close first so the open menu does not overlap the jumped section.
                if (menu) {
                    menu.style.display = "none";
                }

                if (selected) {
                    selected.textContent =
                        item.textContent.trim();
                }

                const target =
                    document.getElementById(
                        item.dataset.target
                    );

                if (target && sidebarContent) {
                    const containerRect =
                        sidebarContent.getBoundingClientRect();
                    const targetRect =
                        target.getBoundingClientRect();
                    const topPadding = 12;

                    const nextTop =
                        sidebarContent.scrollTop
                        + (targetRect.top - containerRect.top)
                        - topPadding;

                    sidebarContent.scrollTo({
                        top: Math.max(0, nextTop),
                        behavior: "smooth",
                    });

                    target.classList.add(
                        "section-highlight"
                    );

                    setTimeout(() => {
                        target.classList.remove(
                            "section-highlight"
                        );
                    }, 2000);
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
