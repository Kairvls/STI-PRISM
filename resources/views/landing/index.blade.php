<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRISM | STI College Ormoc</title>

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico?v=1') }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- ICONS -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>

        *, *::before, *::after { box-sizing: border-box; }

        html{

            scroll-behavior:smooth;

        }

        #about,
        #capabilities,
        #features{

            scroll-margin-top:120px;

        }

        html, body {
            font-family: 'Inter', sans-serif;
            background: #080c18;
            overflow-x: hidden;
            color: #f0f2f8;
        }

        /* ── GRID BACKGROUND ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image:
                linear-gradient(rgba(240,180,41,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(240,180,41,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* ── GLOW ORBS ── */
        body::after {
            content: '';
            position: fixed;
            top: -200px;
            left: -100px;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(240,180,41,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .glow-right {
            position: fixed;
            top: 100px;
            right: -150px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(96,165,250,0.06) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .gr {
            background: 
            linear-gradient(rgba(8, 12, 24, 0.88), rgba(8, 12, 24, 0.96)),
            linear-gradient(rgba(6, 10, 20, 0.88), rgba(0, 0, 0, 0.96));

            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* ── HERO ── */
        .hero-section {
            background:
                linear-gradient(rgba(6, 10, 20, 0.88), rgba(0, 0, 0, 0.96)),
                url({{ asset('image/sti-college-ormoc.png') }});
            background-size: cover;
            background-position: center;
        }

        /* ── GLASS CARD ── */
        .glass {
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.07);
        }

        /* ── MODAL ANIMATION ── */
        .modal-animation {
            animation: modalShow .25s ease;
        }

        @keyframes modalShow {
            from { opacity: 0; transform: translateY(20px) scale(.95); }
            to   { opacity: 1; transform: translateY(0)    scale(1);   }
        }

        /* ── NAV SCROLL ── */
        .nav-scrolled {
            background: rgba(8, 12, 24, 0.92) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 1px solid rgba(255,255,255,0.06) !important;
        }

        /* ── FEATURE CARD HOVER ── */
        .feature-card {
            transition: transform .25s ease, border-color .25s ease, background .25s ease;
        }
        .feature-card:hover {
            transform: translateX(5px);
            border-color: rgba(240,180,41,0.25) !important;
            background: rgba(255,255,255,0.06) !important;
        }

        /* ── CAPABILITY CARD HOVER ── */
        .cap-card {
            transition: transform .3s ease, box-shadow .3s ease, background .3s ease;
        }
        .cap-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 48px rgba(0,0,0,0.4);
            background: rgba(255,255,255,0.06) !important;
        }

        /* ── BUTTON HOVER ── */
        .btn-primary {
            background: linear-gradient(135deg, #f0b429, #e8920a);
            color: #080c18;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #d8a225, #c97f08);
        }

        .btn-ghost {
            background:rgba(0,55,199,0.85); 
            border:1px solid rgba(0,55,199,0.4);
            color: #f0f2f8;
            font-weight: 600;
            transition: background .2s ease;
        }
        .btn-ghost:hover {
            background:rgba(0, 44, 155, 0.85); 
        }

        /* ── SCROLLBAR HIDE ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 6px; }

        /* ── MOBILE PADDING ── */
        @media (max-width: 640px) {
            .hero-section { padding-top: 140px; }
        }

        /* ── MODAL INPUT ── */
        .modal-input {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            color: #f0f2f8;
            outline: none;
            transition: border-color .2s ease;
        }
        .modal-input::placeholder { color: #8892a4; }
        .modal-input:focus { border-color: rgba(240,180,41,0.5); }

        .modal-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #8892a4;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

    </style>

    <style>
        .nav-link{

            color:#8892a4;

            font-size:.875rem;

            text-decoration:none;

            transition:all .25s ease;

            position:relative;

        }

        .nav-link:hover{

            color:#f0f2f8;

        }

        .nav-link.active{

            color:#f0f2f8;

            font-weight:600;

        }

        .nav-link.active::after{

            content:'';

            position:absolute;

            left:0;

            bottom:-8px;

            width:100%;

            height:2px;

            background:#f0f2f8;

            border-radius:999px;

        }
    </style>

</head>

<body>

    <div class="glow-right"></div>

    <!-- ═══════════════════════════════════════════════ NAVBAR -->
    <nav id="navbar" class="fixed top-0 left-0 w-full z-50 transition-all duration-300" style="background:transparent;">

        <div class="max-w-[1350px] mx-auto px-5 lg:px-10 py-4 flex items-center justify-between relative z-10">

            <!-- LOGO -->
            <div class="flex items-center gap-3">

                <div class="w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0"
                     style="background: linear-gradient(135deg, #f0b429, #e8920a);">
                    <img
                        src="{{ asset('image/STI.png') }}"
                        alt="PRISM Logo"
                        class="w-full h-full object-cover">
                </div>

                <div>
                    <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; color:#f0f2f8; letter-spacing:0.08em;">
                        PRISM
                    </div>
                    <div style="font-size:0.6rem; color:#8892a4; letter-spacing:0.1em;">
                        PROCUREMENT &amp; MAINTENANCE SYSTEM
                    </div>
                </div>

            </div>

            <!-- DESKTOP MENU -->
            <div class="hidden lg:flex items-center gap-8">

                <a href="#about" class="nav-link active">About</a>

                <a href="#capabilities" class="nav-link">Features</a>

                <a href="#features" class="nav-link">Modules</a>

                <button onclick="openReportModal()"
                    style="color:#f0b429; font-size:.875rem; font-weight:600; background:none; border:none; cursor:pointer; transition:color .2s;"
                    onmouseover="this.style.color='#ffd166'" onmouseout="this.style.color='#f0b429'">
                    Make Report
                </button>

                <button onclick="openLoginModal()"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl btn-ghost"
                    style="font-size:.875rem;">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    Log In
                </button>

            </div>

            <!-- MOBILE LOGIN -->
            <button onclick="openLoginModal()"
                class="lg:hidden btn-primary px-5 py-2 rounded-xl text-sm">
                Login
            </button>

        </div>

    </nav>

    <!-- ═══════════════════════════════════════════════ HERO -->
    <section id="about" class="hero-section min-h-screen flex items-center pt-36 pb-24 relative">

        <div class="max-w-[1350px] mx-auto px-5 lg:px-10 w-full relative z-10">

            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <!-- LEFT -->
                <div>

                    <!-- BADGE -->
                    <div class="mb-8">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold"
                              style="background:rgba(240,180,41,0.1); border:1px solid rgba(240,180,41,0.25); color:#f0b429; letter-spacing:0.08em; font-family:'Outfit',sans-serif;">
                            ⚡ STI COLLEGE ORMOC
                        </span>
                    </div>

                    <!-- TITLE -->
                    <h1 style="font-family:'Outfit',sans-serif; font-size:clamp(2.6rem,6vw,4.2rem); font-weight:900; line-height:1.05; letter-spacing:-0.02em; color:#f0f2f8; margin-bottom:24px;">
                        Procurement<br>
                        and<br>
                        <span style="color:#f0b429;">Maintenance</span><br>
                        Monitoring System
                    </h1>

                    <!-- DESCRIPTION -->
                    <p style="color:#8892a4; font-size:1rem; line-height:1.75; max-width:480px; margin-bottom:36px;">
                        A centralized web-based procurement and inventory management system 
                        for equipment, facilities, and audio-visual resources, 
                        featuring mobile damage reporting and QR code-based 
                        maintenance monitoring for STI College Ormoc.
                    </p>

                    <!-- BUTTONS -->
                    <div class="flex flex-col sm:flex-row gap-4">

                        <button onclick="openReportModal()"
                            class="btn-primary flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-base">
                            <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                            Make Maintenance Report
                        </button>

                        <button onclick="openLoginModal()"
                            class="btn-ghost flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-base">
                            <i data-lucide="user" class="w-5 h-5"></i>
                            System Login
                        </button>

                    </div>

                </div>

                <!-- RIGHT: FEATURE CARDS -->
                <div class="flex flex-col gap-4">

                    <!-- CARD 1 -->
                    <div class="feature-card flex items-start gap-4 p-5 rounded-2xl glass cursor-pointer">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background:rgba(240,180,41,0.15);">
                            <i data-lucide="package" class="w-6 h-6" style="color:#f0b429;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1rem; color:#f0f2f8; margin-bottom:6px;">
                                Procurement &amp; Inventory Management
                            </div>
                            <p style="color:#8892a4; font-size:.825rem; line-height:1.65;">
                                Manage procurement requests, monitor inventory levels, 
                                and maintain accurate records of institutional 
                                equipment, facilities, and audio-visual resources.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 flex-shrink-0 mt-1" style="color:#8892a4;"></i>
                    </div>

                    <!-- CARD 2 -->
                    <div class="feature-card flex items-start gap-4 p-5 rounded-2xl cursor-pointer"
                         style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); backdrop-filter:blur(12px);">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background:rgba(96,165,250,0.15);">
                            <i data-lucide="smartphone" class="w-6 h-6" style="color:#60a5fa;"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">

                                <div style="
                                    font-family:'Outfit',sans-serif;
                                    font-weight:700;
                                    font-size:1rem;
                                    color:#f0f2f8;
                                ">
                                    Mobile Damage Reporting
                                </div>

                                <span class="px-2 py-1 rounded-full text-[10px] font-bold"
                                    style="
                                        background:rgba(96,165,250,.15);
                                        color:#60a5fa;
                                        border:1px solid rgba(96,165,250,.25);
                                    ">
                                    CORE
                                </span>

                            </div>
                            <p style="color:#8892a4; font-size:.825rem; line-height:1.65;">
                                Allow teachers to instantly report damaged equipment 
                                and facilities through mobile devices with photo 
                                evidence and priority-based reporting.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 flex-shrink-0 mt-1" style="color:#8892a4;"></i>
                    </div>

                    <!-- CARD 3 -->
                    <div class="feature-card flex items-start gap-4 p-5 rounded-2xl cursor-pointer"
                         style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); backdrop-filter:blur(12px);">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center"
                             style="background:rgba(52,211,153,0.15);">
                            <i data-lucide="qr-code" class="w-6 h-6" style="color:#34d399;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1rem; color:#f0f2f8; margin-bottom:6px;">
                                QR Code Maintenance Monitoring
                            </div>
                            <p style="color:#8892a4; font-size:.825rem; line-height:1.65;">
                                Access maintenance history, equipment information, 
                                and service records instantly through QR code scanning.
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 flex-shrink-0 mt-1" style="color:#8892a4;"></i>
                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- ═══════════════════════════════════════════════ STATS -->
    <section class="relative z-10 px-5 lg:px-10 py-16">

        <div class="max-w-7xl mx-auto">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 rounded-2xl p-8"
                 style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07);">

                <div class="text-center">
                    <div style="font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:800; color:#f0b429; line-height:1.1;">24/7</div>
                    <div style="color:#8892a4; font-size:.8rem; margin-top:6px; letter-spacing:.05em;">System Availability</div>
                </div>

                <div class="text-center">
                    <div style="font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:800; color:#f0b429; line-height:1.1;">Real-Time</div>
                    <div style="color:#8892a4; font-size:.8rem; margin-top:6px; letter-spacing:.05em;">Inventory Updates</div>
                </div>

                <div class="text-center">
                    <div style="font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:800; color:#f0b429; line-height:1.1;">Mobile</div>
                    <div style="color:#8892a4; font-size:.8rem; margin-top:6px; letter-spacing:.05em;">Damage Reporting</div>
                </div>

                <div class="text-center">
                    <div style="font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:800; color:#f0b429; line-height:1.1;">QR Code</div>
                    <div style="color:#8892a4; font-size:.8rem; margin-top:6px; letter-spacing:.05em;">Maintenance Tracking</div>
                </div>

            </div>

        </div>

    </section>

    <!-- ═══════════════════════════════════════════════ FEATURES -->
<section id="capabilities" class="relative z-10 px-5 lg:px-10 py-20">

    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-14">

            <span class="inline-block px-3 py-1.5 rounded-full mb-4"
                style="
                    background:rgba(240,180,41,0.1);
                    border:1px solid rgba(240,180,41,0.2);
                    color:#f0b429;
                    font-size:.72rem;
                    letter-spacing:.12em;
                    font-weight:600;
                ">

                SYSTEM FEATURES

            </span>

            <h2
                style="
                    font-family:'Outfit',sans-serif;
                    font-size:clamp(1.8rem,3vw,2.6rem);
                    font-weight:700;
                    color:#f0f2f8;
                ">

                Smart Asset Management for Modern Institutions

            </h2>

            <p class="mt-4 max-w-3xl mx-auto text-gray-400">

                PRISM streamlines procurement, inventory management,
                maintenance monitoring, and damage reporting
                through a centralized digital platform.

            </p>

        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- FEATURE 1 -->
            <div class="cap-card p-6 rounded-2xl gr">

                <i data-lucide="database"
                class="w-10 h-10 text-yellow-400 mb-4"></i>

                <h3 class="text-xl font-bold text-white mb-3">

                    Centralized Asset Management

                </h3>

                <p class="text-gray-400 text-sm leading-7">

                    Manage equipment, facilities,
                    and audio visual resources from
                    a single platform.

                </p>

            </div>

            <!-- FEATURE 2 -->
            <div class="cap-card p-6 rounded-2xl gr">

                <i data-lucide="activity"
                class="w-10 h-10 text-blue-400 mb-4"></i>

                <h3 class="text-xl font-bold text-white mb-3">

                    Real Time Monitoring

                </h3>

                <p class="text-gray-400 text-sm leading-7">

                    Track inventory status,
                    maintenance activities,
                    and asset availability instantly.

                </p>

            </div>

            <!-- FEATURE 3 -->
            <div class="cap-card p-6 rounded-2xl gr">

                <i data-lucide="smartphone"
                class="w-10 h-10 text-cyan-400 mb-4"></i>

                <h3 class="text-xl font-bold text-white mb-3">

                    Mobile Accessibility

                </h3>

                <p class="text-gray-400 text-sm leading-7">

                    Submit maintenance concerns
                    using mobile devices with
                    image evidence.

                </p>

            </div>

            <!-- FEATURE 4 -->
            <div class="cap-card p-6 rounded-2xl gr">

                <i data-lucide="qr-code"
                class="w-10 h-10 text-green-400 mb-4"></i>

                <h3 class="text-xl font-bold text-white mb-3">

                    QR Enabled Tracking

                </h3>

                <p class="text-gray-400 text-sm leading-7">

                    Access maintenance records
                    and equipment information
                    through QR code scanning.

                </p>

            </div>

        </div>

    </div>

</section>







    <!-- ═══════════════════════════════════════════════ SYSTEM MODULES -->
    <section id="features" class="relative z-10 px-5 lg:px-10 py-20">

        <div class="max-w-7xl mx-auto">

            <!-- HEADING -->
            <div class="text-center mb-14">

                <span
                    class="inline-block px-3 py-1.5 rounded-full mb-4"
                    style="
                        background:rgba(240,180,41,0.1);
                        border:1px solid rgba(240,180,41,0.2);
                        color:#f0b429;
                        font-size:.72rem;
                        letter-spacing:.12em;
                        font-weight:600;
                    ">

                    CORE MODULES

                </span>

                <h2
                    style="
                        font-family:'Outfit',sans-serif;
                        font-size:clamp(1.8rem,3vw,2.6rem);
                        font-weight:700;
                        color:#f0f2f8;
                    ">

                    Core Functional Modules of PRISM

                </h2>

                <p
                    class="mt-4 max-w-2xl mx-auto"
                    style="
                        color:#8892a4;
                        line-height:1.8;
                    ">

                    Designed to centralize procurement, inventory management,
                    maintenance monitoring, and damage reporting within a
                    single platform.

                </p>

            </div>

            <!-- MODULE GRID -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- PROCUREMENT -->
                <div class="cap-card p-6 rounded-2xl gr">

                    <i data-lucide="shopping-cart"
                    class="w-10 h-10 text-yellow-400 mb-4"></i>

                    <h3 class="text-xl font-bold text-white mb-3">

                        Procurement Requests

                    </h3>

                    <p class="text-gray-400 text-sm leading-7">

                        Manage purchase requests, approvals,
                        quotations, and procurement workflows.

                    </p>

                </div>

                <!-- INVENTORY -->
                <div class="cap-card p-6 rounded-2xl gr">

                    <i data-lucide="package"
                    class="w-10 h-10 text-blue-400 mb-4"></i>

                    <h3 class="text-xl font-bold text-white mb-3">

                        Inventory Management

                    </h3>

                    <p class="text-gray-400 text-sm leading-7">

                        Track equipment, facilities, and
                        audio visual assets in real time.

                    </p>

                </div>

                <!-- SUPPLIER -->
                <div class="cap-card p-6 rounded-2xl gr">

                    <i data-lucide="truck"
                    class="w-10 h-10 text-green-400 mb-4"></i>

                    <h3 class="text-xl font-bold text-white mb-3">

                        Supplier Management

                    </h3>

                    <p class="text-gray-400 text-sm leading-7">

                        Maintain supplier records,
                        accreditation status, and transactions.

                    </p>

                </div>

                <!-- MOBILE REPORTING -->
                <div class="cap-card p-6 rounded-2xl gr">

                    <i data-lucide="smartphone"
                    class="w-10 h-10 text-cyan-400 mb-4"></i>

                    <h3 class="text-xl font-bold text-white mb-3">

                        Mobile Damage Reporting

                    </h3>

                    <p class="text-gray-400 text-sm leading-7">

                        Submit maintenance concerns using
                        mobile devices with image evidence.

                    </p>

                </div>

                <!-- QR -->
                <div class="cap-card p-6 rounded-2xl gr">

                    <i data-lucide="qr-code"
                    class="w-10 h-10 text-emerald-400 mb-4"></i>

                    <h3 class="text-xl font-bold text-white mb-3">

                        QR Code Monitoring

                    </h3>

                    <p class="text-gray-400 text-sm leading-7">

                        Scan QR codes to instantly access
                        equipment information and records.

                    </p>

                </div>

                <!-- MAINTENANCE -->
                <div class="cap-card p-6 rounded-2xl gr">

                    <i data-lucide="wrench"
                    class="w-10 h-10 text-orange-400 mb-4"></i>

                    <h3 class="text-xl font-bold text-white mb-3">

                        Maintenance History Tracking

                    </h3>

                    <p class="text-gray-400 text-sm leading-7">

                        Monitor repair history, maintenance logs,
                        and equipment service activities.

                    </p>

                </div>

            </div>

        </div>

    </section>
















    <!-- ═══════════════════════════════════════════════ CTA BANNER -->
    <section class="relative z-10 px-5 lg:px-10 pb-24">

        <div class="max-w-7xl mx-auto">

            <div class="relative overflow-hidden rounded-3xl p-10 md:p-14 text-center"
                 style="background:linear-gradient(135deg,rgba(240,180,41,0.1) 0%,rgba(96,165,250,0.07) 100%); border:1px solid rgba(240,180,41,0.2);">

                <!-- top glow -->
                <div style="position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 0%,rgba(240,180,41,0.1) 0%,transparent 60%);"></div>

                <span class="inline-block px-3 py-1.5 rounded-full mb-5"
                      style="background:rgba(240,180,41,0.15); border:1px solid rgba(240,180,41,0.25); color:#f0b429; font-size:.72rem; letter-spacing:.12em; font-weight:600; position:relative; z-index:1;">
                    DIGITIZE CAMPUS OPERATIONS
                </span>

                <h2 style="font-family:'Outfit',sans-serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:700; color:#f0f2f8; line-height:1.2; margin-bottom:14px; position:relative; z-index:1;">
                    Improve procurement, inventory, and maintenance management <br>through a centralized digital platform
                </h2>

                <p style="color:#8892a4; font-size:.95rem; max-width:480px; margin:0 auto 32px; line-height:1.7; position:relative; z-index:1;">
                    Monitor equipment, track inventory, submit damage reports, and 
                    manage maintenance activities through a single integrated system.
                </p>

                <div class="flex flex-wrap gap-4 justify-center" style="position:relative;z-index:1;">

                    <button onclick="openReportModal()"
                        class="btn-primary flex items-center gap-2 px-8 py-4 rounded-2xl text-base">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                        Make Maintenance Report
                    </button>

                    <button onclick="openLoginModal()"
                        class="btn-ghost flex items-center gap-2 px-8 py-4 rounded-2xl text-base">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        System Login
                    </button>

                </div>

            </div>

        </div>

    </section>

    <!-- ═══════════════════════════════════════════════ FOOTER -->
    <footer style="border-top:1px solid rgba(255,255,255,0.07); background:#05080f; position:relative; z-index:10;">

        <div class="max-w-[1350px] mx-auto px-5 lg:px-10 py-10 flex flex-col lg:flex-row items-center justify-between gap-6">

            <!-- LEFT -->
            <div class="flex items-center gap-3">

                <div class="w-9 h-9 rounded-xl overflow-hidden flex-shrink-0"
                     style="background:linear-gradient(135deg,#f0b429,#e8920a);">
                    <img src="{{ asset('image/STI.png') }}" alt="PRISM" class="w-full h-full object-cover">
                </div>

                <div>
                    <div style="font-family:'Outfit',sans-serif; font-weight:700; color:#f0f2f8; font-size:.95rem; letter-spacing:.08em;">PRISM</div>
                    <div style="color:#8892a4; font-size:.7rem;">Procurement &amp; Maintenance System</div>
                </div>

            </div>

            <!-- CENTER -->
            <p style="color:#8892a4; font-size:.8rem; text-align:center;">
                © 1997 STI College Ormoc. All rights reserved.
            </p>

            <!-- RIGHT -->
            <p style="color:#8892a4; font-size:.8rem; text-align:center;">
                Empowering institutions through technology.
            </p>

        </div>

    </footer>


    <!-- ═══════════════════════════════════════════════════════════════════
         MODALS
    ═══════════════════════════════════════════════════════════════════ -->

    <!-- ── 1. LOGIN CHOOSER ── -->
    <div id="loginChooserModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4"
         style="background:rgba(0,0,0,0.75);  backdrop-filter:blur(6px);">

        <div class="modal-animation w-full max-w-[440px] rounded-3xl p-7 relative"
             style="background:#0f1628; border:1px solid rgba(255,255,255,0.1); box-shadow:0 32px 80px rgba(0,0,0,0.6);">

            <!-- CLOSE -->
            <button onclick="closeLoginChooser()"
                class="absolute top-4 right-5 w-8 h-8 flex items-center justify-center rounded-lg transition"
                style="color:#8892a4; background:rgba(255,255,255,0.05); font-size:1.4rem; line-height:1;"
                onmouseover="this.style.color='#f0f2f8'" onmouseout="this.style.color='#8892a4'">
                &times;
            </button>

            <!-- TITLE -->
            <h2 style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#f0f2f8; margin-bottom:6px;">Log In</h2>
            <p style="color:#8892a4; font-size:.875rem; margin-bottom:28px;">Select your access level to continue.</p>

            <div class="space-y-3">

                <!-- STAFF -->
                <button onclick="openStaffLoginChooser()"
                    class="w-full flex items-center justify-center gap-3 py-4 rounded-2xl font-semibold transition"
                    style="background:rgba(0,55,199,0.85); color:#fff; border:1px solid rgba(0,55,199,0.4); font-size:.95rem;"
                    onmouseover="this.style.background='rgba(0,46,168,0.95)'" onmouseout="this.style.background='rgba(0,55,199,0.85)'">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    Log in as Staff
                </button>

                <!-- PRESIDENT -->
                <button onclick="openPresidentLogin()"
                    class="w-full flex items-center justify-center gap-3 py-4 rounded-2xl font-semibold transition btn-primary"
                    style="font-size:.95rem;">
                    <i data-lucide="crown" class="w-5 h-5"></i>
                    President Log in
                </button>

                <!-- ADMIN -->
                <button onclick="openAdminLogin()"
                    class="w-full flex items-center justify-center gap-3 py-4 rounded-2xl font-semibold transition"
                    style="background:transparent; border:1px solid rgba(0,55,199,0.5); color:#60a5fa; font-size:.95rem;"
                    onmouseover="this.style.background='rgba(0,55,199,0.2)'" onmouseout="this.style.background='transparent'">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    Admin Log in
                </button>

            </div>

        </div>

    </div>


    <!-- ── 2. STAFF CHOOSER ── -->
    <div id="staffChooserModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4"
         style="background:rgba(0,0,0,0.75); backdrop-filter:blur(6px);">

        <div class="modal-animation w-full max-w-[440px] rounded-3xl p-7 relative"
             style="background:#0f1628; border:1px solid rgba(255,255,255,0.1); box-shadow:0 32px 80px rgba(0,0,0,0.6);">

            <!-- CLOSE -->
            <button onclick="closeStaffChooser()"
                class="absolute top-4 right-5 w-8 h-8 flex items-center justify-center rounded-lg transition"
                style="color:#8892a4; background:rgba(255,255,255,0.05); font-size:1.4rem; line-height:1;"
                onmouseover="this.style.color='#f0f2f8'" onmouseout="this.style.color='#8892a4'">
                &times;
            </button>

            <h2 style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#f0f2f8; margin-bottom:4px;">Staff Login</h2>
            <p style="color:#8892a4; font-size:.875rem; margin-bottom:24px;">Select your role to continue.</p>

            <!-- ROLE GRID -->
            <div class="grid grid-cols-2 gap-3 mb-4">

                <button onclick="openRoleLogin('Maintenance Personnel', 2)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl transition"
                    style="background:rgba(0,55,199,0.7); border:1px solid rgba(0,55,199,0.4); color:#fff;"
                    onmouseover="this.style.background='rgba(0,46,168,0.9)'" onmouseout="this.style.background='rgba(0,55,199,0.7)'">
                    <i data-lucide="wrench" class="w-7 h-7"></i>
                    <span style="font-weight:600; font-size:.85rem; text-align:center; line-height:1.3;">Maintenance<br>Personnel</span>
                </button>

                <button onclick="openRoleLogin('Purchaser', 3)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl transition btn-primary">
                    <i data-lucide="shopping-cart" class="w-7 h-7"></i>
                    <span style="font-weight:600; font-size:.85rem;">Purchaser</span>
                </button>

                <button onclick="openRoleLogin('Accounting', 5)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl transition"
                    style="background:rgba(0,55,199,0.7); border:1px solid rgba(0,55,199,0.4); color:#fff;"
                    onmouseover="this.style.background='rgba(0,46,168,0.9)'" onmouseout="this.style.background='rgba(0,55,199,0.7)'">
                    <i data-lucide="calculator" class="w-7 h-7"></i>
                    <span style="font-weight:600; font-size:.85rem;">Accounting</span>
                </button>

                <button onclick="openRoleLogin('Receiving Officer', 6)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl transition btn-primary">
                    <i data-lucide="clipboard-list" class="w-7 h-7"></i>
                    <span style="font-weight:600; font-size:.85rem; text-align:center; line-height:1.3;">Receiving<br>Officer</span>
                </button>

            </div>

            <!-- BACK -->
            <button type="button" onclick="showModal(loginChooserModal)"
                class="w-full py-3.5 rounded-xl font-semibold transition"
                style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#8892a4;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                Back
            </button>

        </div>

    </div>


    <!-- ── 3. ROLE LOGIN FORM ── -->
    <div id="roleLoginModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4"
         style="backdrop-filter:blur(6px);">

        <div class="modal-animation w-full max-w-[440px] rounded-3xl p-7 relative"
             style="background:#0f1628; border:1px solid rgba(255,255,255,0.1); box-shadow:0 32px 80px rgba(0,0,0,0.6);">

            <!-- CLOSE -->
            <button onclick="closeRoleLogin()"
                class="absolute top-4 right-5 w-8 h-8 flex items-center justify-center rounded-lg"
                style="color:#8892a4; background:rgba(255,255,255,0.05); font-size:1.4rem; line-height:1;"
                onmouseover="this.style.color='#f0f2f8'" onmouseout="this.style.color='#8892a4'">
                &times;
            </button>

            <h2 id="roleLoginTitle"
                style="font-family:'Outfit',sans-serif; font-size:1.5rem; font-weight:800; color:#f0f2f8; margin-bottom:24px; padding-right:40px; line-height:1.2;">
                Maintenance Personnel Login
            </h2>

            <form method="POST" action="{{ route('login') }}">

                @csrf

                <input type="hidden" name="login_role_id" id="login_role_id">
                <input type="hidden" name="login_modal"   id="login_modal">

                <!-- USER ID -->
                <div class="mb-5">
                    <label class="modal-label">User ID</label>
                    <input type="text"
                           name="user_employee_id"
                           value="{{ old('user_employee_id') }}"
                           placeholder="Enter your user ID"
                           class="modal-input"
                           required>
                </div>

                <!-- PASSWORD -->
                <div class="mb-2">
                    <label class="modal-label">Password</label>
                    <div style="position:relative;">
                        <input type="password"
                               name="password"
                               id="password"
                               placeholder="Enter your password"
                               class="modal-input"
                               style="padding-right:48px;"
                               required>
                        <button type="button" onclick="togglePassword()"
                            style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#8892a4; background:none; border:none; cursor:pointer; transition:color .2s;"
                            onmouseover="this.style.color='#f0b429'" onmouseout="this.style.color='#8892a4'">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- ERROR -->
                <p id="loginErrorMessage"
                   class="hidden mt-2 mb-1"
                   style="color:#f87171; font-size:.8rem; font-weight:500;">
                    Incorrect User ID or Password.
                </p>

                <!-- REMEMBER + FORGOT -->
                <div class="flex items-center justify-between mt-4 mb-6">

                    <label class="flex items-center gap-2" style="font-size:.8rem; color:#8892a4; cursor:pointer;">
                        <input type="checkbox" name="remember"
                               style="accent-color:#f0b429; border-radius:4px;">
                        Remember me
                    </label>

                    <a href="{{ route('password.request') }}"
                       style="font-size:.8rem; color:#f0b429; text-decoration:none; font-weight:500;">
                        Forgot password?
                    </a>

                </div>

                <!-- SUBMIT -->
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl btn-primary mb-2"
                    style="font-size:.95rem;">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    Log In
                </button>

                <!-- MICROSOFT -->
                <a href="{{ route('auth.microsoft.redirect') }}"
                   class="w-full flex items-center justify-center gap-3 py-3.5 rounded-xl mb-3 transition"
                   style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#f0f2f8; font-weight:600; font-size:.95rem; text-decoration:none;"
                   onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg"
                         class="w-5 h-5" alt="Microsoft">
                    Log in with Office 365
                </a>

                <!-- BACK -->
                <button type="button" onclick="goBackRoleLogin()"
                    class="w-full py-3.5 rounded-xl font-semibold transition"
                    style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#8892a4;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                    Back to SSO
                </button>

            </form>

        </div>

    </div>


    <!-- ── 4. REPORT MODAL ── -->
    <div id="reportModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 py-10"
         style=" backdrop-filter:blur(6px);">

        <div class="w-full max-w-6xl relative modal-animation">
            @include('reporter.partials.report-form')
        </div>

    </div>


    <!-- ═══════════════════════════════════════════════ SCRIPTS -->
    <script>

        lucide.createIcons();

        /* ── NAV SCROLL ── */
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('nav-scrolled');
            } else {
                navbar.classList.remove('nav-scrolled');
            }
        });

        /* ── MODALS ── */
        const loginChooserModal = document.getElementById('loginChooserModal');
        const staffChooserModal = document.getElementById('staffChooserModal');
        const roleLoginModal    = document.getElementById('roleLoginModal');
        const reportModal       = document.getElementById('reportModal');

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

            document.documentElement.classList.add('overflow-hidden');
        }

        function hideModal(modal) {

            modal.classList.add('hidden');

            const visible =
                document.querySelector('.fixed.inset-0:not(.hidden)');

            if (!visible) {

                document.body.classList.remove('overflow-hidden');

                document.documentElement.classList.remove('overflow-hidden');
            }
        }

        function openLoginModal()       { showModal(loginChooserModal); }
        function openLoginChooser()     { showModal(loginChooserModal); }
        function openStaffLoginChooser(){ showModal(staffChooserModal); }
        function closeLoginChooser()    { hideModal(loginChooserModal); }
        function closeStaffChooser()    { hideModal(staffChooserModal); }
        function closeRoleLogin()       { hideModal(roleLoginModal); }
        function openReportModal()      { showModal(reportModal); }
        function closeReportModal()     { hideModal(reportModal); }
        function openAdminLogin()       { openRoleLogin('Admin', 1); }
        function openPresidentLogin()   { openRoleLogin('President', 4); }

        function openRoleLogin(roleName, roleId) {

            showModal(roleLoginModal);

            document.getElementById('roleLoginTitle').innerText = roleName + ' Login';
            document.getElementById('login_role_id').value = roleId;
            document.getElementById('login_modal').value   = roleName;

            if (roleName !== "{{ old('login_modal') }}") {
                document.querySelector('input[name="user_employee_id"]').value = '';
                document.querySelector('input[name="password"]').value = '';
            }

            const errorMessage = document.getElementById('loginErrorMessage');
            if (
                "{{ $errors->has('user_employee_id') }}"
                && roleName === "{{ old('login_modal') }}"
            ) {
                errorMessage.classList.remove('hidden');
            } else {
                errorMessage.classList.add('hidden');
            }
        }

        function goBackRoleLogin() {
            const currentRole = document.getElementById('login_modal').value;
            if (currentRole === 'Admin' || currentRole === 'President') {
                showModal(loginChooserModal);
            } else {
                showModal(staffChooserModal);
            }
        }

        /* ── PASSWORD TOGGLE ── */
        function togglePassword() {
            const input   = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        /* ── ESC KEY ── */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllModals();
                document.body.classList.remove('overflow-hidden');
            }
        });

    </script>

    <script>

    /*
    |--------------------------------------------------------------------------
    | ACTIVE NAV LINK
    |--------------------------------------------------------------------------
    */

    const sections =
        document.querySelectorAll(
            'section[id]'
        );

    const navLinks =
        document.querySelectorAll(
            '.nav-link'
        );

    window.addEventListener(
        'scroll',
        () => {

            let current = '';

            sections.forEach(section => {

                const sectionTop =
                    section.offsetTop - 200;

                const sectionHeight =
                    section.clientHeight;

                if(
                    pageYOffset >= sectionTop
                    &&
                    pageYOffset <
                    sectionTop + sectionHeight
                ){

                    current =
                        section.getAttribute('id');

                }

            });

            navLinks.forEach(link => {

                link.classList.remove(
                    'active'
                );

                if(
                    link.getAttribute('href')
                    ===
                    '#' + current
                ){

                    link.classList.add(
                        'active'
                    );

                }

            });

        }

    );

    </script>

    @if ($errors->any())
    <script>
        window.addEventListener('load', function() {
            const modalName = "{{ old('login_modal') }}";
            const roleId    = "{{ old('login_role_id') }}";
            if (modalName !== '') {
                openRoleLogin(modalName, roleId);
            }
        });
    </script>
    @endif

    @if(session('success'))

    <script>
    document.addEventListener(
        'DOMContentLoaded',
        function(){

            Swal.fire({

                icon: 'success',

                title: 'Maintenance Report Submitted',

                html: `
                    <div style="color:#6b7280;">
                        Your maintenance request has been successfully submitted and is awaiting review.
                    </div>
                `,

                showConfirmButton: false,

                timer: 3000,

                timerProgressBar: true,

                iconColor: '#2947f0',

                background: '#ffffff',

                color: '#111827',

                backdrop: `
                    rgba(0,0,0,0.45)
                    blur(8px)
                `,

                didOpen: () => {

                    const popup = Swal.getPopup();

                    popup.style.border =
                        '1.5px solid rgba(41,71,240,.15)';

                    popup.style.borderRadius =
                        '20px';

                    popup.style.boxShadow =
                        '0 20px 45px rgba(41,71,240,.15)';

                    popup.style.padding =
                        '1rem';

                    popup.style.overflow =
                        'hidden';

                    popup.insertAdjacentHTML(
                        'afterbegin',
                        `
                        <div
                            style="
                                height:4px;
                                margin:-1rem -1rem .8rem -1rem;
                                background:
                                linear-gradient(
                                    90deg,
                                    #2947f0,
                                    #f0b429
                                );
                            ">
                        </div>
                        `
                    );

                    const title = popup.querySelector('.swal2-title');

                    if(title){

                        title.style.fontFamily =
                            'Poppins, sans-serif';

                        title.style.fontSize =
                            '1.25rem';

                        title.style.fontWeight =
                            '700';

                        title.style.margin =
                            '0 0 .4rem 0';

                    }

                    const htmlContainer =
                        popup.querySelector('.swal2-html-container');

                    if(htmlContainer){

                        htmlContainer.style.fontFamily =
                            'Inter, sans-serif';

                        htmlContainer.style.fontSize =
                            '.9rem';

                        htmlContainer.style.lineHeight =
                            '1.45';

                        htmlContainer.style.margin =
                            '0';

                    }

                }

            });

        }
    );
    </script>

    @endif

</body>
</html>