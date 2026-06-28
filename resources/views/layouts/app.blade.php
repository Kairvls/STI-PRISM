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
</body>

</html>
