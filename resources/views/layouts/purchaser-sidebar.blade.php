{{-- ===================================================== --}}
{{-- PURCHASER SIDEBAR --}}
{{-- ===================================================== --}}

<div id="sidebar">


    {{-- ===================================================== --}}
    {{-- SIDEBAR HEADER --}}
    {{-- ===================================================== --}}

    <div class="sidebar-header p-5">

        {{-- ================================================= --}}
        {{-- STI LOGO --}}
        {{-- ================================================= --}}

        <div class="logo-icon">

            <img
                src="{{ asset('image/STI.png') }}"
                alt="STI Logo"
            >

        </div>


        {{-- ================================================= --}}
        {{-- SYSTEM INFORMATION --}}
        {{-- ================================================= --}}

        <div class="min-w-0">

            <h2>
                PRISM
            </h2>

            <span>
                Purchaser System
            </span>

        </div>

    </div>



    {{-- ===================================================== --}}
    {{-- SCROLLABLE SIDEBAR CONTENT --}}
    {{-- ===================================================== --}}

    <div class="sidebar-content">


        {{-- ===================================================== --}}
        {{-- QUICK ACTIONS --}}
        {{-- ===================================================== --}}

        <div class="quick-actions">


            {{-- ================================================= --}}
            {{-- REPLACEMENT REQUESTS --}}
            {{-- ================================================= --}}

            <a
                href="{{ route('purchaser.procurement.replacement-requests') }}"

                class="
                    quick-card

                    {{
                        request()->routeIs(
                            'purchaser.procurement.replacement-requests'
                        )
                            ? 'active'
                            : ''
                    }}
                "
            >

                <i data-lucide="inbox"></i>

                <span>
                    Requests
                </span>

            </a>



            {{-- ================================================= --}}
            {{-- SUPPLIERS --}}
            {{-- ================================================= --}}

            <a
                href="#"
                class="quick-card"
            >

                <i data-lucide="truck"></i>

                <span>
                    Suppliers
                </span>

            </a>



            {{-- ================================================= --}}
            {{-- RECEIVING REPORTS --}}
            {{-- ================================================= --}}

            <a
                href="#"
                class="quick-card"
            >

                <i data-lucide="package-check"></i>

                <span>
                    Receiving
                </span>

            </a>



            {{-- ================================================= --}}
            {{-- NOTIFICATIONS --}}
            {{-- ================================================= --}}

            <a
                href="#"
                class="quick-card"
            >

                <i data-lucide="bell-ring"></i>

                <span>
                    Alerts
                </span>

            </a>

        </div>



        {{-- ===================================================== --}}
        {{-- DASHBOARD SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title">
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

        <div class="menu-title">
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

        </a>



        {{-- ===================================================== --}}
        {{-- PROCUREMENT SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title">
            PROCUREMENT
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
        {{-- SUPPLIERS --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.suppliers.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.suppliers.*') ? 'active' : '' }}"
        >

            <i
                data-lucide="truck"
                class="h-5 w-5"
            ></i>

            <span>
                Suppliers
            </span>

        </a>



        {{-- ===================================================== --}}
        {{-- PURCHASING WORKFLOW SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title">
            PURCHASING WORKFLOW
        </div>


        {{-- ===================================================== --}}
        {{-- REQUEST CHECK --}}
        {{-- ===================================================== --}}

        <a
            href="#"
            class="menu-item"
        >

            <i
                data-lucide="clipboard-check"
                class="h-5 w-5"
            ></i>

            <span>
                Request Check
            </span>

        </a>



        {{-- ===================================================== --}}
        {{-- AUTHORITY TO PURCHASE --}}
        {{-- ===================================================== --}}

        <a
            href="{{ route('purchaser.atp.index') }}"
            class="menu-item mt-1 {{ request()->routeIs('purchaser.atp*') ? 'active' : '' }}"
        >

            <i
                data-lucide="file-check-2"
                class="h-5 w-5"
            ></i>

            <span>
                Authority to Purchase
            </span>

        </a>



        {{-- ===================================================== --}}
        {{-- RECEIVING REPORTS --}}
        {{-- ===================================================== --}}

        <a
            href="#"
            class="menu-item mt-1"
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
        {{-- LIQUIDATION REPORTS --}}
        {{-- ===================================================== --}}

        <a
            href="#"
            class="menu-item mt-1"
        >

            <i
                data-lucide="receipt-text"
                class="h-5 w-5"
            ></i>

            <span>
                Liquidation Reports
            </span>

        </a>



        {{-- ===================================================== --}}
        {{-- SYSTEM SECTION --}}
        {{-- ===================================================== --}}

        <div class="menu-title">
            SYSTEM
        </div>


        {{-- ===================================================== --}}
        {{-- NOTIFICATIONS --}}
        {{-- ===================================================== --}}

        <a
            href="#"
            class="menu-item"
        >

            <i
                data-lucide="bell"
                class="h-5 w-5"
            ></i>

            <span>
                Notifications
            </span>

        </a>


    </div>



    {{-- ===================================================== --}}
    {{-- SIDEBAR FOOTER --}}
    {{-- ===================================================== --}}

    <div class="sidebar-footer">

        <div class="footer-avatar">

            {{
                strtoupper(
                    substr(
                        Auth::user()->user_full_name
                            ?? 'P',
                        0,
                        1
                    )
                )
            }}

        </div>


        <div class="min-w-0 flex-1">

            <p class="footer-name">

                {{
                    Auth::user()->user_full_name
                        ?? 'Purchaser'
                }}

            </p>

            <p class="footer-role">
                Purchaser
            </p>

        </div>

    </div>

</div>



<style>

    /* ========================================================= */
    /* PURCHASER SIDEBAR */
    /* SAME LAYOUT STRUCTURE AS MAINTENANCE SIDEBAR */
    /* ========================================================= */

    #sidebar {

        width: 280px;

        height: 100vh;

        flex-shrink: 0;

        background: #0d1120;

        color: white;

        display: flex;

        flex-direction: column;

        border-right:
            1px solid rgba(255, 255, 255, 0.05);

    }



    /* ========================================================= */
    /* SIDEBAR HEADER */
    /* ========================================================= */

    .sidebar-header {

        display: flex;

        align-items: center;

        gap: 14px;

        flex-shrink: 0;

    }


    .logo-icon {

        width: 50px;

        height: 50px;

        flex-shrink: 0;

        border-radius: 14px;

        background:
            linear-gradient(
                135deg,
                #8b5cf6,
                #6366f1
            );

        display: flex;

        align-items: center;

        justify-content: center;

    }


    .logo-icon img {

        width: 100%;

        height: 100%;

        border-radius: 14px;

        object-fit: cover;

    }


    .sidebar-header h2 {

        font-size: 20px;

        font-weight: 700;

        line-height: 1.2;

    }


    .sidebar-header span {

        display: block;

        margin-top: 2px;

        font-size: 13px;

        color: #94a3b8;

    }



    /* ========================================================= */
    /* SCROLLABLE SIDEBAR CONTENT */
    /* ========================================================= */

    .sidebar-content {

        flex: 1;

        min-height: 0;

        overflow-y: auto;

        overflow-x: hidden;

        padding: 20px;

    }



    /* ========================================================= */
    /* SIDEBAR SCROLLBAR */
    /* ========================================================= */

    .sidebar-content::-webkit-scrollbar {

        width: 6px;

    }


    .sidebar-content::-webkit-scrollbar-track {

        background: transparent;

    }


    .sidebar-content::-webkit-scrollbar-thumb {

        background: #2d3748;

        border-radius: 999px;

    }


    .sidebar-content::-webkit-scrollbar-thumb:hover {

        background: #4a5568;

    }



    /* ========================================================= */
    /* QUICK ACTIONS */
    /* ========================================================= */

    .quick-actions {

        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 8px;

        margin-bottom: 20px;

    }


    .quick-card {

        height: 70px;

        min-width: 0;

        background: #111827;

        border:
            1px solid rgba(255, 255, 255, 0.05);

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

        border-color: #2563eb;

        transform: translateY(-2px);

    }


    .quick-card i {

        width: 16px;

        height: 16px;

        color: #60a5fa;

    }


    .quick-card span {

        max-width: 100%;

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

        padding: 0 5px;

        font-size: 11px;

        font-weight: 500;

    }


    .quick-card.active {

        border: 1.5px solid #2563eb;

        color: #cbd5e1;

        font-weight: 600;

        box-shadow:
            0 0 12px rgba(37, 99, 235, 0.2);

    }


    .quick-card.active i {

        color: #3b82f6;

    }



    /* ========================================================= */
    /* SECTION TITLE */
    /* ========================================================= */

    .menu-title {

        margin-top: 28px;

        margin-bottom: 10px;

        padding-left: 14px;

        font-size: 11px;

        font-weight: 700;

        letter-spacing: 1.5px;

        color: #64748b;

    }


    .menu-title:first-of-type {

        margin-top: 0;

    }



    /* ========================================================= */
    /* MENU ITEMS */
    /* ========================================================= */

    .menu-item {

        height: 48px;

        display: flex;

        align-items: center;

        gap: 14px;

        padding: 0 14px;

        border-radius: 10px;

        color: #94a3b8;

        text-decoration: none;

        font-size: 14px;

        font-weight: 500;

        transition: all 0.2s ease;

    }


    .menu-item:hover {

        background: #182235;

        color: white;

        transform: translateX(2px);

    }


    .menu-item i {

        width: 18px;

        height: 18px;

        flex-shrink: 0;

        color: #64748b;

        transition: all 0.2s ease;

    }


    .menu-item:hover i {

        color: #60a5fa;

    }



    /* ========================================================= */
    /* ACTIVE MENU ITEM */
    /* ========================================================= */

    .menu-item.active {

        background: #182235;

        color: white;

        font-weight: 600;

        box-shadow:
            inset 3px 0 0 #2563eb;

    }


    .menu-item.active i {

        color: #3b82f6;

    }



    /* ========================================================= */
    /* SIDEBAR FOOTER */
    /* ========================================================= */

    .sidebar-footer {

        flex-shrink: 0;

        display: flex;

        align-items: center;

        gap: 12px;

        padding: 14px 20px;

        background: #0d1120;

        border-top:
            1px solid rgba(255, 255, 255, 0.06);

    }


    .footer-avatar {

        width: 38px;

        height: 38px;

        flex-shrink: 0;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 10px;

        background: #182235;

        color: white;

        font-size: 13px;

        font-weight: 700;

    }


    .footer-name {

        overflow: hidden;

        text-overflow: ellipsis;

        white-space: nowrap;

        font-size: 13px;

        font-weight: 600;

        color: #f8fafc;

    }


    .footer-role {

        margin-top: 2px;

        font-size: 11px;

        color: #64748b;

    }



    /* ========================================================= */
    /* MOBILE SIDEBAR */
    /* app.blade.php ALREADY CONTROLS POSITION AND TRANSFORM */
    /* ========================================================= */

    @media (max-width: 1279px) {

        #sidebar {

            flex-shrink: 0;

        }

    }

</style>