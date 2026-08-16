<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        @yield('title', 'PRISM')

    </title>

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- LUCIDE -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- LOCAL APP JS / ALPINE -->
    @vite(['resources/js/app.js'])

    <!-- FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>

        /* ======================================
           ALPINE CLOAK
           Keeps Alpine elements hidden until Alpine initializes
        ====================================== */
        [x-cloak] {
            display: none !important;
        }

        *{

            font-family:'Poppins',sans-serif;

        }

        body{

            overflow-x:hidden;
            background:#0B1120;

        }

        /* SCROLLBAR */

        ::-webkit-scrollbar{

            width:8px;

        }

        ::-webkit-scrollbar-track{

            background:#0F172A;

        }

        ::-webkit-scrollbar-thumb{

            background:#323c4d;
            border-radius:20px;

        }

        ::-webkit-scrollbar-thumb:hover{

            background:#4A5568;
            border-radius:20px;

        }

        /* SIDEBAR */

        #sidebar{

            transition:.3s;

        }

        /* MOBILE SIDEBAR */

        @media(max-width:1279px){

            #sidebar{

                position:fixed;
                left:0;
                top:0;
                z-index:999;
                transform:translateX(-100%);

            }

            #sidebar.active{

                transform:translateX(0);

            }

        }

        /* DROPDOWN CONTENT */

        .sidebar-dropdown-content{

            display:none;

        }

        /* CONTENT */

        .content-wrapper{

            width:100%;
            min-width:0;

        }

    </style>

</head>

<body class="text-black h-screen overflow-hidden">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR -->
        @yield('sidebar')

        <!-- MAIN -->
        <div class="flex-1 flex flex-col content-wrapper overflow-hidden">

            <!-- TOPBAR -->
            @yield('topbar')

            <!-- PAGE CONTENT -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden p-8 bg-gray-100">

                @yield('content')

            </main>

        </div>

    </div>

    <!-- SIDEBAR OVERLAY -->
    <div id="sidebarOverlay"
        onclick="toggleSidebar()"
        class="hidden fixed inset-0 bg-black/70 z-[998] xl:hidden">

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
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
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
        border-color: #2563eb;
        transform: translateY(-2px);
    }
    .quick-card i {
        width: 16px;
        height: 16px;
        color: #60a5fa;
        transition: all 0.2s ease;
    }
    .quick-card span {
        font-size: 11px;
        font-weight: 500;
    }

    /* NEW BLUE ACTIVE STATE STATE FOR QUICK ACTIONS */
    .quick-card.active {
        border: 1.5px solid #2563eb !important;
        color: #cbd5e1;
        font-weight: 600;
        box-shadow: 0 0 12px rgba(37, 99, 235, 0.2);
    }
    .quick-card.active i {
        color: #3b82f6;
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
    PRISM logo
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


    background: #fff200;


    border-radius: 0 5px 5px 0;
}


/* ======================================
   ACTIVE ICON

   USE SVG BECAUSE OF LUCIDE
====================================== */

.menu-item.active svg {
    color: #fff200 !important;

    stroke: #fff200 !important;
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
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
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
        color: #fff200 !important;
        text-shadow: 0 0 10px rgba(255, 242, 0, 0.5);
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
        color: #fff200;
    }
</style>

{{-- <script>
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
    // SIDEBAR STORAGE KEY
    // =====================================================

    const sidebarScrollKey =
        "maintenanceSidebarScrollPosition";


    // =====================================================
    // SEARCH DROPDOWN OPEN / CLOSE
    // =====================================================

    if (trigger && menu) {

        trigger.addEventListener("click", () => {

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
</script> --}}


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

        lucide.createIcons();

        /*
        |--------------------------------------------------------------------------
        | MOBILE SIDEBAR
        |--------------------------------------------------------------------------
        */

        function toggleSidebar(){

            const sidebar =
                document.getElementById('sidebar');

            const overlay =
                document.getElementById('sidebarOverlay');

            sidebar.classList.toggle('active');

            overlay.classList.toggle('hidden');

        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR DROPDOWN
        |--------------------------------------------------------------------------
        */

        function toggleDropdown(id){

            const dropdown =
                document.getElementById(id);

            if(dropdown.style.display === 'block'){

                dropdown.style.display = 'none';

            }else{

                dropdown.style.display = 'block';

            }

        }

        /*
        |--------------------------------------------------------------------------
        | CLOSE DROPDOWN WHEN CLICK OUTSIDE
        |--------------------------------------------------------------------------
        */

        window.onclick = function(event){

            if(!event.target.closest('.sidebar-dropdown')){

                const dropdowns =
                    document.querySelectorAll(
                        '.sidebar-dropdown-content'
                    );

                dropdowns.forEach(dropdown => {

                    dropdown.style.display = 'none';

                });

            }

        }

    </script>

    @stack('scripts')

    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

    @include('layouts.partials.messaging-modal')

</body>

</html>
