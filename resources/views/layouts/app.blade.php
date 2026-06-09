<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        @yield('title', 'PRISM')

    </title>

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- LUCIDE ICONS -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>

        * {

            font-family: 'Poppins', sans-serif;

        }

        body {

            overflow-x: hidden;

        }

        /* SCROLLBAR */

        ::-webkit-scrollbar {

            width: 8px;

        }

        ::-webkit-scrollbar-track {

            background: #0B1120;

        }

        ::-webkit-scrollbar-thumb {

            background: #1D4ED8;
            border-radius: 20px;

        }

        ::-webkit-scrollbar-thumb:hover {

            background: #2563EB;

        }

        /* Maintenance Sidebar */
        .sidebar-link {

            display: block;
            padding: 10px 14px;
            border-radius: 12px;
            color: #D1D5DB;
            transition: 0.2s;

        }

        .sidebar-link:hover {

            background: #1E3A8A;
            color: white;

        }

    </style>

</head>

<body class="bg-[#0B1120] text-white">

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->
        @yield('sidebar')

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col">

            <!-- TOPBAR -->
            @include('layouts.topbar')

            <!-- PAGE CONTENT -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">

                @yield('content')

            </main>

        </div>

    </div>

</body>

</html>