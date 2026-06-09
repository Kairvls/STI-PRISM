<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    

    <title>
        PRISM | STI College Ormoc
    </title>

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- ICONS -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        html,
        body {

            font-family: 'Poppins', sans-serif;
            background: #020617;
            overflow-x: hidden;

        }

        .modal-animation {

            width: 100%;

        }

        @media (max-width: 640px) {

            .hero-section {

                padding-top: 140px;

            }

        }

        .hero-section {

            background:
                linear-gradient(
                    rgba(2, 6, 23, 0.92),
                    rgba(2, 6, 23, 0.96)
                ),

                url({{ asset('image/sti-bg.webp') }});

            background-size: cover;
            background-position: center;

        }

        .glass {

            background: rgba(255,255,255,0.04);

            backdrop-filter: blur(10px);

            border: 1px solid rgba(255,255,255,0.06);

        }

        .feature-divider {

            border-bottom:
                1px solid rgba(255,255,255,0.08);

        }

        .modal-animation {

            animation:
                modalShow .25s ease;

        }

        @keyframes modalShow {

            from {

                opacity: 0;
                transform: translateY(20px) scale(.95);

            }

            to {

                opacity: 1;
                transform: translateY(0) scale(1);

            }

        }

    </style>

</head>

<body>

    <!-- NAVBAR -->
    <nav class="fixed top-0 left-0 w-full z-50 border-b border-white/10 bg-[#020B2D]/90 backdrop-blur-xl">

        <div class="max-w-7xl mx-auto px-5 lg:px-10 py-4 flex items-center justify-between">

            <!-- LOGO -->
            <div class="flex items-center gap-4">

                <div class="bg-[#FFC600] w-14 h-14 rounded-2xl overflow-hidden">

                    <img
                        src="{{ asset('image/prism-logo.png') }}"
                        alt="PRISM Logo"
                        class="w-full h-full p-0.2 object-cover">

                </div>

                <div>

                    <h1 class="text-white text-2xl font-bold">
                        PRISM
                    </h1>

                    <p class="text-gray-300 text-xs">
                        Procurement & Maintenance System
                    </p>

                </div>

            </div>

            <!-- MENU -->
            <div class="hidden lg:flex items-center gap-10">

                <a href="#"
                   class="text-gray-200 hover:text-yellow-400 transition font-medium">

                    Features

                </a>

                <a href="#"
                   class="text-gray-200 hover:text-yellow-400 transition font-medium">

                    Solutions

                </a>

                <a href="#"
                   class="text-gray-200 hover:text-yellow-400 transition font-medium">

                    About

                </a>

                <button
                    onclick="openReportModal()"
                    class="text-yellow-400 font-semibold hover:text-yellow-300">

                    Make Report

                </button>

                <button
                    onclick="openLoginModal()"
                    class="bg-[#0037C7] hover:bg-[#002ea8] text-white px-6 py-3 rounded-2xl font-semibold hover:scale-105 transition">

                    <div class="flex items-center gap-2">

                        <i data-lucide="user"
                           class="w-4 h-4"></i>

                        Log In

                    </div>

                </button>

            </div>

            <!-- MOBILE -->
            <button
                onclick="openLoginModal()"
                class="lg:hidden bg-blue-600 text-white px-5 py-2 rounded-xl">

                Login

            </button>

        </div>

    </nav>

    <!-- HERO -->
    <section class="hero-section min-h-screen flex items-center pt-36 pb-24">

        <div class="max-w-7xl mx-auto px-5 lg:px-10 w-full">

            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <!-- LEFT -->
                <div>

                    <!-- BADGE -->
                    <div class="mb-8">

                        <span class="border border-yellow-400 bg-yellow-400/10 text-yellow-400 px-6 py-3 rounded-full text-sm font-semibold">

                            STI COLLEGE ORMOC

                        </span>

                    </div>

                    <!-- TITLE -->
                    <h1 class="text-white text-5xl md:text-7xl font-extrabold leading-tight mb-8">

                        Procurement
                        <br>

                        and

                        <br>

                        <span class="text-yellow-400">
                            Maintenance
                        </span>

                        <br>

                        Monitoring System

                    </h1>

                    <!-- DESCRIPTION -->
                    <p class="text-gray-300 text-lg leading-relaxed max-w-2xl mb-10">

                        A centralized enterprise platform designed
                        for procurement workflow automation,
                        maintenance reporting, inventory monitoring,
                        accounting validation, and institutional
                        asset management.

                    </p>

                    <!-- BUTTONS -->
                    <div class="flex flex-col sm:flex-row gap-5">

                        <button
                            onclick="openReportModal()"
                            class="bg-gradient-to-r from-yellow-400 to-orange-400 text-black font-bold px-10 py-5 rounded-2xl hover:scale-105 transition">

                            Make Maintenance Report >

                        </button>

                        <button
                            onclick="openLoginModal()"
                            class="bg-[#0037C7] hover:bg-[#002ea8] text-white font-bold px-10 py-5 rounded-2xl hover:scale-105 transition">

                            <div class="flex items-center gap-2">

                                <i data-lucide="user"
                                class="w-4 h-4"></i>

                                System Login

                            </div>

                        </button>

                    </div>

                </div>

                <!-- RIGHT FEATURES -->
                <div class="glass rounded-[35px] p-8 lg:p-10">

                    <!-- FEATURE -->
                    <div class="flex gap-5 pb-8 feature-divider">

                        <div class="bg-yellow-400 min-w-[70px] h-[70px] rounded-3xl flex items-center justify-center">

                            <i data-lucide="box"
                               class="w-8 h-8 text-black"></i>

                        </div>

                        <div>

                            <h1 class="text-white text-2xl font-bold mb-3">

                                Procurement & Inventory

                            </h1>

                            <p class="text-gray-300 leading-relaxed">

                                Manage procurement workflows and
                                track inventory for equipment,
                                facilities, and audio-visual
                                resources efficiently.

                            </p>

                        </div>

                    </div>

                    <!-- FEATURE -->
                    <div class="flex gap-5 py-8 feature-divider">

                        <div class="bg-yellow-400 min-w-[70px] h-[70px] rounded-3xl flex items-center justify-center">

                            <i data-lucide="smartphone"
                               class="w-8 h-8 text-black"></i>

                        </div>

                        <div>

                            <h1 class="text-white text-2xl font-bold mb-3">

                                Mobile Damage Reporting

                            </h1>

                            <p class="text-gray-300 leading-relaxed">

                                Report equipment damage instantly
                                through mobile devices with photo
                                documentation and real-time tracking.

                            </p>

                        </div>

                    </div>

                    <!-- FEATURE -->
                    <div class="flex gap-5 pt-8">

                        <div class="bg-yellow-400 min-w-[70px] h-[70px] rounded-3xl flex items-center justify-center">

                            <i data-lucide="qr-code"
                               class="w-8 h-8 text-black"></i>

                        </div>

                        <div>

                            <h1 class="text-white text-2xl font-bold mb-3">

                                QR Code Monitoring

                            </h1>

                            <p class="text-gray-300 leading-relaxed">

                                Track equipment maintenance history
                                and status using QR code scanning
                                for quick access to records.

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- 1 -->
    <!-- LOGIN CHOOSER MODAL -->
    <div id="loginChooserModal"
        class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center px-4">

        <!-- MODAL BOX -->
        <div class="bg-white rounded-[24px] w-full max-w-[450px] p-6 relative shadow-2xl modal-animation">

            <!-- CLOSE BUTTON -->
            <button
                onclick="closeLoginChooser()"
                class="absolute top-5 right-5 text-[40px] leading-none text-gray-500 hover:text-black transition">

                &times;

            </button>

            <!-- TITLE -->
            <h1 class="text-[34px] font-bold text-[#081238] mb-8 leading-none">

                Log In

            </h1>

            <!-- BUTTONS -->
            <div class="space-y-4">

                <!-- STAFF -->
                <button
                    onclick="openStaffLoginChooser()"
                    class="w-full bg-[#0037C7] hover:bg-[#002ea8] text-white py-5 rounded-2xl font-semibold text-[17px] transition">

                    <div class="flex items-center justify-center gap-3">

                        <i data-lucide="users"
                        class="w-5 h-5"></i>

                        Log in as Staff

                    </div>

                </button>

                <!-- PRESIDENT -->
                <button
                    onclick="openPresidentLogin()"
                    class="w-full bg-[#FFC700] hover:bg-yellow-400 text-black py-5 rounded-2xl font-semibold text-[17px] transition">

                    <div class="flex items-center justify-center gap-3">

                        <i data-lucide="crown"
                        class="w-5 h-5"></i>

                        President Log in

                    </div>

                </button>

                <!-- ADMIN -->
                <button
                    onclick="openAdminLogin()"
                    class="w-full border-2 border-[#0037C7] text-[#0037C7] hover:bg-[#0037C7] hover:text-white py-5 rounded-2xl font-semibold text-[17px] transition">

                    <div class="flex items-center justify-center gap-3">

                        <i data-lucide="shield-check"
                        class="w-5 h-5"></i>

                        Admin Log in

                    </div>

                </button>

            </div>

        </div>

    </div>


    <!-- 2 -->
    <!-- STAFF CHOOSER MODAL -->
    <div id="staffChooserModal"
        class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center px-4">

        <!-- MODAL BOX -->
        <div class="bg-white rounded-[24px] w-full max-w-[450px] p-6 relative shadow-2xl modal-animation">

            <!-- CLOSE BUTTON -->
            <button
                onclick="closeStaffChooser()"
                class="absolute top-5 right-5 text-[40px] leading-none text-gray-500 hover:text-black transition">

                &times;

            </button>

            <!-- TITLE -->
            <h1 class="text-[34px] font-bold text-[#081238] leading-none mb-2">

                Staff Login

            </h1>

            <!-- SUBTITLE -->
            <p class="text-gray-500 text-sm mb-7">

                Select your role to continue

            </p>

            <!-- ROLE GRID -->
            <div class="grid grid-cols-2 gap-4">

                <!-- MAINTENANCE -->
                <button
                    onclick="openRoleLogin('Maintenance Personnel', 2)"
                    class="bg-[#0037C7] hover:bg-[#002ea8] text-white rounded-2xl h-[120px] flex flex-col items-center justify-center transition">

                    <i data-lucide="wrench"
                    class="w-7 h-7 mb-3"></i>

                    <span class="font-semibold text-[15px] leading-tight text-center px-2">

                        Maintenance
                        <br>
                        Personnel

                    </span>

                </button>

                <!-- PURCHASER -->
                <button
                    onclick="openRoleLogin('Purchaser', 3)"
                    class="bg-[#FFC700] hover:bg-yellow-400 text-black rounded-2xl h-[120px] flex flex-col items-center justify-center transition">

                    <i data-lucide="shopping-cart"
                    class="w-7 h-7 mb-3"></i>

                    <span class="font-semibold text-[15px]">

                        Purchaser

                    </span>

                </button>

                <!-- ACCOUNTING -->
                <button
                    onclick="openRoleLogin('Accounting', 5)"
                    class="bg-[#0037C7] hover:bg-[#002ea8] text-white rounded-2xl h-[120px] flex flex-col items-center justify-center transition">

                    <i data-lucide="calculator"
                    class="w-7 h-7 mb-3"></i>

                    <span class="font-semibold text-[15px]">

                        Accounting

                    </span>

                </button>

                <!-- RECEIVING -->
                <button
                    onclick="openRoleLogin('Receiving Officer', 6)"
                    class="bg-[#FFC700] hover:bg-yellow-400 text-black rounded-2xl h-[120px] flex flex-col items-center justify-center transition">

                    <i data-lucide="clipboard-list"
                    class="w-7 h-7 mb-3"></i>

                    <span class="font-semibold text-[15px] leading-tight text-center">

                        Receiving
                        <br>
                        Officer

                    </span>

                </button>

            </div>

            <!-- BACK BUTTON -->
                <button
                    type="button"
                    onclick="showModal(loginChooserModal)"
                    class="w-full bg-gray-800 hover:bg-gray-900 mt-4 text-white py-3.5 rounded-xl text-[16px] font-semibold transition mb-3">

                    Back

                </button>

        </div>

    </div>


    <!-- 3 -->
    <!-- ROLE LOGIN MODAL -->
    <div id="roleLoginModal"
        class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center px-4">

        <!-- MODAL BOX -->
        <div class="bg-white rounded-[24px] w-full max-w-[450px] p-6 sm:p-7 relative shadow-2xl modal-animation">

            <!-- CLOSE BUTTON -->
            <button
                onclick="closeRoleLogin()"
                class="absolute top-4 right-5 text-[38px] leading-none text-gray-500 hover:text-black transition">

                &times;

            </button>

            <!-- TITLE -->
            <h1 id="roleLoginTitle"
                class="text-[20px] sm:text-[24px] font-bold text-[#081238] mb-8 leading-tight pr-10">

                Maintenance Personnel Login

            </h1>

            <!-- FORM -->
            <form method="POST"
                action="{{ route('login') }}">

                @csrf

                <!-- HIDDEN -->
                <input
                    type="hidden"
                    name="login_role_id"
                    id="login_role_id">

                <input
                    type="hidden"
                    name="login_modal"
                    id="login_modal">

                <!-- USER ID -->
                <div class="mb-5">

                    <label class="block text-[14px] font-semibold text-gray-700 mb-2">

                        User ID

                    </label>

                    <input
                        type="text"
                        name="user_employee_id"
                        value="{{ old('user_employee_id') }}"
                        placeholder="Enter your user ID"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-[14px] focus:outline-none focus:ring-2 focus:ring-blue-700"
                        required>

                </div>

                <!-- PASSWORD -->
                <div class="mb-2">

                    <label class="block text-[14px] font-semibold text-gray-700 mb-2">

                        Password

                    </label>

                    <!-- PASSWORD WRAPPER -->
                    <div class="relative">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Enter your password"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 pr-12 text-[14px] focus:outline-none focus:ring-2 focus:ring-blue-700"
                            required>

                        <!-- EYE BUTTON -->
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-700 transition">

                            <i
                                data-lucide="eye"
                                id="eyeIcon"
                                class="w-5 h-5"></i>

                        </button>

                    </div>

                </div>

                <!-- ERROR -->
                <p
                    id="loginErrorMessage"
                    class="text-red-500 text-[13px] mt-2 mb-4 font-medium hidden">

                    Incorrect User ID or Password.

                </p>

                <!-- REMEMBER + FORGOT -->
                <div class="flex items-center justify-between mb-6 mt-3">

                    <!-- REMEMBER -->
                    <label class="flex items-center gap-2 text-[13px] text-gray-600">

                        <input
                            type="checkbox"
                            name="remember"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">

                        Remember me

                    </label>

                    <!-- FORGOT -->
                    <a
                        href="{{ route('password.request') }}"
                        class="text-[13px] text-blue-600 hover:underline font-medium">

                        Forgot password?

                    </a>

                </div>

                <!-- BUTTON -->

                    <!-- NORMAL LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="w-full bg-[#0037C7] hover:bg-[#002ea8] text-white py-3.5 rounded-xl text-[16px] font-semibold transition">

                        <div class="flex items-center justify-center gap-2">

                            <i data-lucide="lock"
                            class="w-4 h-4"></i>

                            Log In

                        </div>

                    </button>

                    <!-- MICROSOFT LOGIN BUTTON -->

                    <a
                        href="{{ route('auth.microsoft.redirect') }}"
                        class="w-full border border-gray-300 hover:bg-gray-100 text-gray-700 py-3.5 mt-2 rounded-xl text-[16px] font-semibold transition flex items-center justify-center gap-3 mb-3">

                        <img
                            src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg"
                            class="w-5 h-5"
                            alt="Microsoft">

                        Log in with Office 365

                    </a>

                <!-- BACK BUTTON -->
                <button
                    type="button"
                    onclick="goBackRoleLogin()"
                    class="w-full bg-gray-800 hover:bg-gray-900 mt-2 text-white py-3.5 rounded-xl text-[16px] font-semibold transition mb-3">

                    Back to SSO

                </button>

            </form>

        </div>

    </div>

    <!-- REPORT MODAL  --------------------------------------------------------------------------------------------->
    <div id="reportModal"
         class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center px-4">

        <div class="bg-white rounded-[30px] w-full max-w-2xl p-6 md:p-10 relative shadow-2xl modal-animation">

            <button
                onclick="closeReportModal()"
                class="absolute top-4 right-4 text-4xl text-gray-400 hover:text-black">

                &times;

            </button>

            <h1 class="text-3xl md:text-4xl font-bold text-[#081238] mb-4">Maintenance Report</h1>
            <p class="text-gray-600 text-lg leading-relaxed">Maintenance reporting form implementation will be added next.</p>

        </div>

    </div>

    <!-- SECOND SECTION -->
    <section class="bg-[#01091F] py-24">

        <div class="max-w-7xl mx-auto px-5 lg:px-10">

            <div class="grid lg:grid-cols-3 gap-8">

                <!-- CARD -->
                <div class="bg-white rounded-[30px] p-8">

                    <div class="flex justify-between items-center mb-8">

                        <h1 class="text-3xl font-bold text-[#081238]">
                            Log In
                        </h1>

                        <button class="text-4xl text-gray-400">
                            ×
                        </button>

                    </div>

                    <div class="space-y-5">

                        <button class="w-full bg-blue-700 text-white py-5 rounded-2xl font-semibold">
                            Log in as Staff
                        </button>

                        <button class="w-full bg-yellow-400 text-black py-5 rounded-2xl font-semibold">
                            President Log in
                        </button>

                        <button class="w-full border-2 border-blue-700 text-blue-700 py-5 rounded-2xl font-semibold">
                            Admin Log in
                        </button>

                    </div>

                </div>

                <!-- CARD -->
                <div class="bg-white rounded-[30px] p-8">

                    <div class="flex justify-between items-center mb-3">

                        <h1 class="text-3xl font-bold text-[#081238]">
                            Staff Login
                        </h1>

                        <button class="text-4xl text-gray-400">
                            ×
                        </button>

                    </div>

                    <p class="text-gray-500 mb-8">
                        Select your role to continue
                    </p>

                    <div class="grid grid-cols-2 gap-5">

                        <button class="bg-blue-700 text-white rounded-2xl py-8 font-bold">
                            Maintenance Personnel
                        </button>

                        <button class="bg-yellow-400 text-black rounded-2xl py-8 font-bold">
                            Purchaser
                        </button>

                        <button class="bg-blue-700 text-white rounded-2xl py-8 font-bold">
                            Accounting
                        </button>

                        <button class="bg-yellow-400 text-black rounded-2xl py-8 font-bold">
                            Receiving Officer
                        </button>

                    </div>

                </div>

                <!-- CARD -->
                <div class="bg-white rounded-[30px] p-8">

                    <div class="flex justify-between items-center mb-8">

                        <h1 class="text-2xl font-bold text-[#081238]">
                            Maintenance Personnel Login
                        </h1>

                        <button class="text-4xl text-gray-400">
                            ×
                        </button>

                    </div>

                    <form class="space-y-6">

                        <div>

                            <label class="block mb-2 font-medium">
                                Email Address
                            </label>

                            <input type="email"
                                   placeholder="Enter your email"
                                   class="w-full border rounded-xl px-4 py-4">

                        </div>

                        <div>

                            <label class="block mb-2 font-medium">
                                Password
                            </label>

                            <input type="password"
                                   placeholder="Enter your password"
                                   class="w-full border rounded-xl px-4 py-4">

                        </div>

                        <button class="w-full bg-blue-700 text-white py-4 rounded-2xl font-semibold">
                            Log In
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </section>

    <!-- FOOTER -->
    <footer class="border-t border-white/10 bg-[#02081F]">

        <div class="max-w-7xl mx-auto px-5 lg:px-10 py-10 flex flex-col lg:flex-row items-center justify-between gap-8">

            <!-- LEFT -->
            <div class="flex items-center gap-4">

                <div class="bg-[#FFC600] w-14 h-14 rounded-2xl overflow-hidden">

                    <img
                        src="{{ asset('image/prism-logo.png') }}"
                        alt="PRISM Logo"
                        class="w-full h-full p-0.2 object-cover">

                </div>

                <div>

                    <h1 class="text-white text-2xl font-bold">
                        PRISM
                    </h1>

                    <p class="text-gray-400 text-sm">
                        Procurement & Maintenance System
                    </p>

                </div>

            </div>

            <!-- CENTER -->
            <p class="text-gray-400 text-center">
                © 2026 STI College Ormoc.
                All rights reserved.
            </p>

            <!-- RIGHT -->
            <p class="text-gray-400 text-center">
                Empowering institutions through technology.
            </p>

        </div>

    </footer>

    <script>

    lucide.createIcons();

    /*
    |--------------------------------------------------------------------------
    | GET MODALS
    |--------------------------------------------------------------------------
    */

    const loginChooserModal =
        document.getElementById('loginChooserModal');

    const staffChooserModal =
        document.getElementById('staffChooserModal');

    const roleLoginModal =
        document.getElementById('roleLoginModal');

    const reportModal =
        document.getElementById('reportModal');

    /*
    |--------------------------------------------------------------------------
    | GENERAL FUNCTIONS
    |--------------------------------------------------------------------------
    */

    function closeAllModals() {

        loginChooserModal.classList.add('hidden');

        staffChooserModal.classList.add('hidden');

        roleLoginModal.classList.add('hidden');

        reportModal.classList.add('hidden');

    }

    function showModal(modal) {

        closeAllModals();

        modal.classList.remove('hidden');

        document.body.classList.add('overflow-hidden');

    }

    function hideModal(modal) {

        modal.classList.add('hidden');

        const visibleModal =
            document.querySelector(
                '.fixed.inset-0:not(.hidden)'
            );

        if (!visibleModal) {

            document.body.classList.remove('overflow-hidden');

        }

    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN CHOOSER MODAL
    |--------------------------------------------------------------------------
    */

    function openLoginModal() {

        showModal(loginChooserModal);

    }

    function openLoginChooser() {

        showModal(loginChooserModal);

    }

   

    /*
    |--------------------------------------------------------------------------
    | STAFF CHOOSER MODAL
    |--------------------------------------------------------------------------
    */

    function openStaffLoginChooser() {

        showModal(staffChooserModal);

    }

    

    /*
    |--------------------------------------------------------------------------
    | ROLE LOGIN MODAL
    |--------------------------------------------------------------------------
    */

    function openRoleLogin(roleName, roleId) {

        showModal(roleLoginModal);

        /*
        |--------------------------------------------------------------------------
        | TITLE
        |--------------------------------------------------------------------------
        */

        document.getElementById(
            'roleLoginTitle'
        ).innerText = roleName + ' Login';

        /*
        |--------------------------------------------------------------------------
        | ROLE ID
        |--------------------------------------------------------------------------
        */

        document.getElementById(
            'login_role_id'
        ).value = roleId;

        /*
        |--------------------------------------------------------------------------
        | SAVE CURRENT MODAL
        |--------------------------------------------------------------------------
        */

        document.getElementById(
            'login_modal'
        ).value = roleName;

        /*
        |--------------------------------------------------------------------------
        | CLEAR INPUTS ONLY WHEN SWITCHING ROLE
        |--------------------------------------------------------------------------
        */

        if (
            roleName !== "{{ old('login_modal') }}"
        ) {

            document.querySelector(
                'input[name="user_employee_id"]'
            ).value = '';

            document.querySelector(
                'input[name="password"]'
            ).value = '';

        }

        /*
        |--------------------------------------------------------------------------
        | ERROR MESSAGE
        |--------------------------------------------------------------------------
        */

        const errorMessage =
            document.getElementById(
                'loginErrorMessage'
            );

        if (
            "{{ $errors->has('user_employee_id') }}"
            &&
            roleName === "{{ old('login_modal') }}"
        ) {

            errorMessage.classList.remove('hidden');

        }

        else {

            errorMessage.classList.add('hidden');

        }

        /*
        |--------------------------------------------------------------------------
        | BACK NAVIGATION SYSTEM
        |--------------------------------------------------------------------------
        */
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE MODALS
    |--------------------------------------------------------------------------
    */

    function closeLoginChooser() {

        hideModal(loginChooserModal);

    }

    function closeStaffChooser() {

        hideModal(staffChooserModal);

    }

    function closeRoleLogin() {

        hideModal(roleLoginModal);

    }

    

    /*
    |--------------------------------------------------------------------------
    | ADMIN LOGIN
    |--------------------------------------------------------------------------
    */

    function openAdminLogin() {

        openRoleLogin('Admin', 1);

    }

    /*
    |--------------------------------------------------------------------------
    | PRESIDENT LOGIN
    |--------------------------------------------------------------------------
    */

    function openPresidentLogin() {

        openRoleLogin('President', 4);

    }

    /*
    |--------------------------------------------------------------------------
    | BACK BUTTON
    |--------------------------------------------------------------------------
    */

    function goBackRoleLogin() {

        const currentRole =
            document.getElementById(
                'login_modal'
            ).value;

        /*
        |--------------------------------------------------------------------------
        | ADMIN AND PRESIDENT
        |--------------------------------------------------------------------------
        */

        if (
            currentRole === 'Admin'
            ||
            currentRole === 'President'
        ) {

            showModal(loginChooserModal);

        }

        /*
        |--------------------------------------------------------------------------
        | STAFF ROLES
        |--------------------------------------------------------------------------
        */

        else {

            showModal(staffChooserModal);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | REPORT MODAL
    |--------------------------------------------------------------------------
    */

    function openReportModal() {

        showModal(reportModal);

    }

    function closeReportModal() {

        hideModal(reportModal);

    }

    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE TO CLOSE
    |--------------------------------------------------------------------------
    */

    /*window.addEventListener('click', function(event) {

        if (event.target === loginChooserModal) {

            closeLoginChooser();

        }

        if (event.target === staffChooserModal) {

            closeStaffChooser();

        }

        if (event.target === roleLoginModal) {

            closeRoleLogin();

        }

        if (event.target === reportModal) {

            closeReportModal();

        }

    });
    */

    /*
    |--------------------------------------------------------------------------
    | ESC KEY CLOSE
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', function(event) {

        if (event.key === 'Escape') {

            closeAllModals();

            document.body.classList.remove(
                'overflow-hidden'
            );

        }

    });

    /*
    |--------------------------------------------------------------------------
    | Password Eye Fill Icon Toggle
    |--------------------------------------------------------------------------
    */


    function togglePassword() {

        const passwordInput =
            document.getElementById('password');

        const eyeIcon =
            document.getElementById('eyeIcon');

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            eyeIcon.setAttribute(
                'data-lucide',
                'eye-off'
            );

        }

        else {

            passwordInput.type = 'password';

            eyeIcon.setAttribute(
                'data-lucide',
                'eye'
            );

        }

        lucide.createIcons();

    }

</script>

<script>
    lucide.createIcons();
</script>

@if ($errors->any())

<script>

    window.addEventListener('load', function() {

        const modalName =
            "{{ old('login_modal') }}";

        const roleId =
            "{{ old('login_role_id') }}";

        if (modalName !== '') {

            openRoleLogin(
                modalName,
                roleId
            );

        }

    });

</script>

@endif

</body>
</html>