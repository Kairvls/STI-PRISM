<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaAyo | Campus Asset Platform</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico?v=1') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --blue: #0025cc;
            --blue-dark: #001ca3;
            --yellow: #fff200;
            --ink: #1a1a2e;
            --muted: #6b7280;
            --line: #e8ecf4;
            --soft: #f3f6ff;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        #product, #process, #features, #stories {
            scroll-margin-top: 96px;
        }

        html, body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff;
            color: var(--ink);
            overflow-x: hidden;
        }

        .nav-scrolled {
            background: rgba(255,255,255,.96) !important;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--line);
            box-shadow: 0 6px 24px rgba(0,37,204,.06);
        }

        .nav-link {
            color: var(--ink);
            font-size: .95rem;
            font-weight: 500;
            text-decoration: none;
            transition: color .2s;
        }
        .nav-link:hover,
        .nav-link.active { color: var(--blue); }

        .btn-blue {
            background: var(--blue);
            color: #fff;
            font-weight: 600;
            border-radius: 999px;
            transition: background .2s, box-shadow .2s, transform .2s;
        }
        .btn-blue:hover {
            background: var(--blue-dark);
            box-shadow: 0 12px 28px rgba(0,37,204,.28);
        }

        .btn-yellow {
            background: var(--yellow);
            color: var(--ink);
            font-weight: 700;
            border-radius: 999px;
            transition: filter .2s, box-shadow .2s;
        }
        .btn-yellow:hover {
            filter: brightness(.97);
            box-shadow: 0 10px 24px rgba(255,242,0,.4);
        }

        .btn-report {
            background: #4b5563;
            color: #ffffff;
            font-weight: 600;
            border-radius: 999px;
            border: 1px solid #374151;
            transition: background .2s, border-color .2s, box-shadow .2s;
        }
        .btn-report:hover {
            background: #374151;
            border-color: #1f2937;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .18);
        }

        .btn-dark {
            background: #111827;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            transition: background .2s;
        }
        .btn-dark:hover { background: #000; }

        /* Large soft light-blue organic wave (mockup left background) */
        .hero-wave-wrap {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .hero-wave-svg {
            position: absolute;
            left: -18%;
            top: -6%;
            width: min(92vw, 920px);
            height: auto;
            opacity: 1;
        }

        .hero-wave-svg.back {
            left: -22%;
            top: 4%;
            width: min(80vw, 780px);
            opacity: .55;
            transform: rotate(-6deg);
        }

        .hero-wave-svg.front {
            animation: waveDrift 14s ease-in-out infinite;
        }

        @keyframes waveDrift {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(12px, -10px) rotate(1.5deg); }
        }

        .hero-section {
            background:
                radial-gradient(ellipse 55% 70% at 12% 45%, rgba(196, 214, 255, 0.55) 0%, transparent 70%),
                radial-gradient(ellipse 40% 50% at 88% 20%, rgba(255, 242, 0, 0.08) 0%, transparent 65%),
                #ffffff;
        }

        .ui-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(232,236,244,.9);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .10);
        }

        .float-a { animation: floatA 6s ease-in-out infinite; }
        .float-b { animation: floatB 7s ease-in-out infinite; }
        .float-c { animation: floatC 5.5s ease-in-out infinite; }

        @keyframes floatA {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-14px) rotate(-1deg); }
        }
        @keyframes floatB {
            0%, 100% { transform: translateY(0) rotate(3deg); }
            50% { transform: translateY(-10px) rotate(4deg); }
        }
        @keyframes floatC {
            0%, 100% { transform: translateY(0) rotate(1deg); }
            50% { transform: translateY(-16px) rotate(0deg); }
        }

        .bar {
            border-radius: 6px 6px 0 0;
            background: #dbe3ff;
        }
        .bar.on { background: var(--blue); }

        .logo-muted {
            color: #9aa3b5;
            font-weight: 700;
            font-size: .95rem;
            letter-spacing: .02em;
            opacity: .85;
            user-select: none;
        }

        .process-num {
            font-size: clamp(4.5rem, 10vw, 7rem);
            font-weight: 800;
            line-height: .85;
            color: #eef1f8;
            letter-spacing: -.04em;
        }

        .feature-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--line);
            box-shadow: 0 14px 40px rgba(15,23,42,.06);
            transition: transform .25s, box-shadow .25s;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 22px 50px rgba(0,37,204,.12);
        }

        .icon-circle {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,37,204,.1);
            color: var(--blue);
        }

        .promo-banner {
            background:
                linear-gradient(105deg, rgba(10,16,40,.88) 0%, rgba(0,37,204,.72) 100%),
                url({{ asset('image/sti_logo_building.png') }}) center/cover;
            border-radius: 24px;
        }

        .quote-mark {
            font-size: 6rem;
            line-height: .6;
            color: var(--yellow);
            font-weight: 800;
            font-family: Georgia, serif;
        }

        .star { color: var(--yellow); }

        .frame-photo {
            position: relative;
            width: min(100%, 340px);
        }
        .frame-photo .photo {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            border-radius: 18px;
            position: relative;
            z-index: 2;
        }
        .frame-photo .shape-blue {
            position: absolute;
            width: 70%;
            height: 40%;
            right: -10%;
            top: -8%;
            background: var(--blue);
            border-radius: 0 0 0 120px;
            z-index: 1;
        }
        .frame-photo .shape-yellow {
            position: absolute;
            width: 55%;
            height: 35%;
            left: -8%;
            bottom: -6%;
            background: var(--yellow);
            border-radius: 120px 0 80px 0;
            z-index: 1;
        }

        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s ease, transform .7s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: none;
        }

        /* Modals */
        .modal-animation { animation: modalShow .25s ease; }
        @keyframes modalShow {
            from { opacity: 0; transform: translateY(18px) scale(.97); }
            to { opacity: 1; transform: none; }
        }
        .modal-panel {
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: 0 28px 70px rgba(15,23,42,.18);
        }
        .modal-input {
            width: 100%;
            background: #f8f9fd;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            color: var(--ink);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .modal-input::placeholder { color: #9aa1b5; }
        .modal-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(0,37,204,.12);
            background: #fff;
        }
        .modal-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .modern-success-popup {
            border: 1px solid #e5e7eb !important;
            border-radius: 16px !important;
            box-shadow: 0 24px 60px rgba(15,23,42,.12) !important;
        }
        .modern-success-title {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 700 !important;
            color: #111827 !important;
        }
        .modern-success-content {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            color: #6b7280 !important;
        }
        .modern-success-progress { background: var(--blue) !important; height: 3px !important; }
        .swal-message { max-width: 320px; margin: 0 auto; }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #c9cfde; border-radius: 6px; }
    </style>
</head>

<body>

    <!-- NAV -->
    <nav id="navbar" class="fixed top-0 inset-x-0 z-50 transition-all duration-300 bg-transparent">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 h-[72px] flex items-center justify-between gap-4">
            <a href="#top" class="text-[1.15rem] font-extrabold tracking-tight no-underline" style="color:var(--ink);">
                Pa<span style="color:var(--blue);">Ayo</span>
            </a>

            <div class="hidden lg:flex items-center gap-9">
                <a href="#product" class="nav-link active">Product</a>
                <a href="#process" class="nav-link">Process</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#stories" class="nav-link">Stories</a>
                <button type="button" onclick="openReportModal()"
                        class="nav-link bg-transparent border-0 cursor-pointer p-0">
                    Make Report
                </button>
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                <button type="button" onclick="openReportModal()"
                        class="lg:hidden text-xs sm:text-sm font-semibold bg-transparent border-0 cursor-pointer p-0"
                        style="color:var(--ink);">
                    Report
                </button>
                @guest
                    <button type="button" onclick="openLoginModal()"
                            class="hidden sm:inline text-sm font-semibold bg-transparent border-0 cursor-pointer"
                            style="color:var(--ink);">
                        Sign In
                    </button>
                    <button type="button" onclick="openLoginModal()"
                            class="btn-blue px-5 py-2.5 text-sm border-0 cursor-pointer">
                        Start Free
                    </button>
                @else
                    <a href="{{ route('dashboard') }}"
                       class="btn-blue px-5 py-2.5 text-sm no-underline inline-flex">
                        Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section id="top" class="hero-section relative pt-28 md:pt-36 pb-20 md:pb-28 overflow-hidden min-h-[90vh] flex items-center">

        <!-- Light-blue organic wave background (mockup-style) -->
        <div class="hero-wave-wrap" aria-hidden="true">
            <svg class="hero-wave-svg back" viewBox="0 0 800 700" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <path fill="#d7e4ff"
                      d="M120,-40 C280,-20 340,80 420,160 C520,260 620,220 700,300 C780,380 760,520 640,580 C500,650 360,620 240,560 C80,480 -40,420 -20,280 C0,140 -20,-60 120,-40 Z"/>
            </svg>
            <svg class="hero-wave-svg front" viewBox="0 0 800 700" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <linearGradient id="waveFill" x1="0%" y1="0%" x2="80%" y2="100%">
                        <stop offset="0%" stop-color="#c8d9ff"/>
                        <stop offset="45%" stop-color="#dde8ff"/>
                        <stop offset="100%" stop-color="#eef3ff"/>
                    </linearGradient>
                </defs>
                <path fill="url(#waveFill)"
                      d="M40,40 C180,-30 320,20 400,110 C490,220 560,180 650,250 C760,340 790,450 700,540 C590,650 420,680 280,620 C120,550 20,480 10,340 C0,200 -60,100 40,40 Z"/>
                <!-- Soft inner curve highlight -->
                <path fill="#f4f7ff" opacity=".55"
                      d="M90,180 C200,120 300,150 360,220 C430,300 500,280 560,330 C640,400 620,480 520,520 C400,570 280,540 190,480 C80,400 20,320 90,180 Z"/>
            </svg>
            <!-- Bottom soft wave edge -->
            <svg class="absolute bottom-0 left-0 w-full h-[120px] md:h-[160px]" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="#ffffff"
                      d="M0,80 C240,140 480,20 720,70 C960,120 1200,40 1440,90 L1440,160 L0,160 Z"/>
                <path fill="none" stroke="#c5d6ff" stroke-width="2" opacity=".5"
                      d="M0,78 C240,138 480,18 720,68 C960,118 1200,38 1440,88"/>
            </svg>
        </div>

        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 relative z-10 w-full">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-10 items-center">

                <div class="reveal relative">
                    <p class="text-sm font-semibold mb-4" style="color:var(--blue);">
                        Campus growth solution in a single platform.
                    </p>

                    <h1 class="font-extrabold tracking-tight leading-[1.12] mb-5"
                        style="font-size:clamp(2.35rem,5vw,3.6rem); color:var(--ink);">
                        We are here to make easy your campus asset ops
                    </h1>

                    <p class="text-base leading-relaxed max-w-md mb-8" style="color:var(--muted);">
                        PaAyo centralizes procurement, inventory, and maintenance monitoring with mobile damage reporting and QR tracking for STI College Ormoc.
                    </p>

                    <form class="flex flex-col sm:flex-row gap-3 mb-4 max-w-lg" onsubmit="event.preventDefault(); openLoginModal();">
                        <input type="email"
                               placeholder="Register using email address"
                               class="flex-1 min-w-0 px-5 py-3.5 rounded-[10px] text-sm outline-none"
                               style="background:#fff; border:1px solid var(--line); box-shadow:0 8px 24px rgba(15,23,42,.06); color:var(--ink);">
                        <button type="submit" class="btn-dark px-8 py-3.5 text-sm shrink-0 border-0 cursor-pointer">
                            Submit
                        </button>
                    </form>

                    <div class="flex flex-wrap gap-3 mb-6">
                        <button type="button" onclick="openReportModal()"
                                class="btn-report inline-flex items-center gap-2 px-7 py-3.5 text-sm cursor-pointer">
                            <i data-lucide="clipboard-plus" class="w-4 h-4"></i>
                            Make Report
                        </button>
                        @guest
                            <button type="button" onclick="openLoginModal()"
                                    class="btn-blue inline-flex items-center gap-2 px-7 py-3.5 text-sm border-0 cursor-pointer">
                                System Login
                            </button>
                        @else
                            <a href="{{ route('dashboard') }}"
                               class="btn-blue inline-flex items-center gap-2 px-7 py-3.5 text-sm no-underline">
                                Go to Dashboard
                            </a>
                        @endguest
                    </div>

                    <div class="flex flex-wrap gap-6 text-sm font-medium" style="color:var(--ink);">
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-5 h-5" style="color:var(--blue);"></i>
                            Free Register
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-5 h-5" style="color:var(--blue);"></i>
                            Great Service
                        </span>
                    </div>
                </div>

                <!-- Floating dashboard collage -->
                <div class="relative h-[420px] md:h-[500px] reveal" style="transition-delay:.1s;">
                    <!-- Soft glow behind cards -->
                    <div class="absolute inset-[8%] rounded-full pointer-events-none"
                         style="background:radial-gradient(circle, rgba(0,37,204,.08), transparent 70%); filter:blur(20px);"></div>

                    <!-- Main chart card -->
                    <div class="ui-card absolute left-[4%] top-[8%] w-[58%] p-4 float-a z-20">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold" style="color:var(--muted);">Asset Health</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:rgba(0,37,204,.1); color:var(--blue);">Live</span>
                        </div>
                        <svg viewBox="0 0 220 90" class="w-full h-auto" fill="none" aria-hidden="true">
                            <path d="M0 70 C30 68, 40 40, 70 45 S110 80, 140 50 S180 20, 220 28" stroke="#0025cc" stroke-width="3" stroke-linecap="round"/>
                            <path d="M0 75 C35 72, 55 58, 85 62 S130 40, 160 48 S200 70, 220 55" stroke="#fff200" stroke-width="2.5" stroke-linecap="round" opacity=".9"/>
                            <circle cx="140" cy="50" r="4" fill="#0025cc"/>
                        </svg>
                        <div class="mt-2 flex items-end justify-between">
                            <div>
                                <div class="text-xl font-extrabold">98.4%</div>
                                <div class="text-[11px]" style="color:var(--muted);">Uptime this month</div>
                            </div>
                            <div class="text-xs font-semibold" style="color:#16a34a;">+12.8%</div>
                        </div>
                    </div>

                    <!-- Bar chart card -->
                    <div class="ui-card absolute right-[2%] top-[0%] w-[42%] p-4 float-b z-10">
                        <div class="text-xs font-semibold mb-3" style="color:var(--muted);">Weekly Reports</div>
                        <div class="flex items-end gap-1.5 h-24">
                            <div class="bar flex-1 h-[35%]"></div>
                            <div class="bar flex-1 h-[55%]"></div>
                            <div class="bar on flex-1 h-[78%]"></div>
                            <div class="bar flex-1 h-[48%]"></div>
                            <div class="bar on flex-1 h-[92%]"></div>
                            <div class="bar flex-1 h-[62%]"></div>
                            <div class="bar flex-1 h-[40%]"></div>
                        </div>
                    </div>

                    <!-- Profile / amount card -->
                    <div class="ui-card absolute right-[6%] top-[38%] w-[48%] p-4 float-c z-30">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                 style="background:var(--blue);">JO</div>
                            <div>
                                <div class="text-sm font-bold">J. Ortega</div>
                                <div class="text-[11px]" style="color:var(--muted);">Maintenance Lead</div>
                            </div>
                        </div>
                        <div class="rounded-xl p-3" style="background:var(--soft);">
                            <div class="text-[11px] mb-1" style="color:var(--muted);">Resolved this week</div>
                            <div class="text-2xl font-extrabold tracking-tight">122,637</div>
                        </div>
                    </div>

                    <!-- Transaction list -->
                    <div class="ui-card absolute left-[10%] bottom-[2%] w-[55%] p-4 float-b z-20" style="animation-delay:-1.5s;">
                        <div class="text-xs font-semibold mb-3" style="color:var(--muted);">Recent Activity</div>
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" style="background:var(--blue);"></span>
                                    QR Scan · Room 204
                                </span>
                                <span class="text-xs font-semibold" style="color:#16a34a;">Done</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" style="background:var(--yellow);"></span>
                                    Purchase Request
                                </span>
                                <span class="text-xs font-semibold" style="color:#d97706;">Review</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" style="background:#94a3b8;"></span>
                                    Damage Report
                                </span>
                                <span class="text-xs font-semibold" style="color:var(--blue);">New</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SOCIAL PROOF -->
    <section class="py-14 md:py-16 border-y" style="border-color:var(--line);">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 text-center reveal">
            <p class="text-lg md:text-xl font-semibold mb-10" style="color:var(--ink);">
                Over <span style="color:var(--blue);">32k+</span> campus workflows growing with
                <span class="font-extrabold">PaAyo</span>
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-5">
                @foreach (['Oracle','Morpheus','Samsung','Monday','Segment','Protonet'] as $brand)
                    <span class="logo-muted">{{ $brand }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <!-- PROCESS -->
    <section id="process" class="py-20 md:py-28 overflow-hidden">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-start">
                <div class="lg:col-span-4 reveal">
                    <p class="text-xs font-bold tracking-[0.14em] uppercase mb-4" style="color:var(--blue);">
                        PaAyo operation across campus
                    </p>
                    <h2 class="text-3xl md:text-[2.35rem] font-extrabold leading-tight mb-4" style="color:var(--ink);">
                        We have best team and best process
                    </h2>
                    <p class="text-sm leading-relaxed mb-8" style="color:var(--muted);">
                        From discovery to daily maintenance loops, PaAyo guides every role with a clear, shared workflow.
                    </p>
                    @guest
                        <button type="button" onclick="openLoginModal()" class="btn-blue px-7 py-3 text-sm">
                            Get Started
                        </button>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-blue inline-flex px-7 py-3 text-sm no-underline">
                            Get Started
                        </a>
                    @endguest
                </div>

                <div class="lg:col-span-8 relative reveal pt-6" style="transition-delay:.08s;">
                    <!-- Bold wavy process connector -->
                    <svg class="hidden md:block absolute left-0 right-0 top-[78px] w-full h-[100px] pointer-events-none z-0"
                         viewBox="0 0 720 100" fill="none" aria-hidden="true" preserveAspectRatio="none">
                        <path d="M30 55 C120 10, 200 95, 300 50 S480 5, 560 55 S680 95, 700 45"
                              stroke="#0025cc" stroke-width="3.5" stroke-linecap="round"/>
                        <circle cx="36" cy="52" r="7" fill="#0025cc"/>
                        <circle cx="36" cy="52" r="12" fill="#0025cc" opacity=".18"/>
                        <circle cx="360" cy="48" r="7" fill="#0025cc"/>
                        <circle cx="360" cy="48" r="12" fill="#0025cc" opacity=".18"/>
                        <circle cx="688" cy="48" r="7" fill="#0025cc"/>
                        <circle cx="688" cy="48" r="12" fill="#0025cc" opacity=".18"/>
                    </svg>

                    <div class="grid md:grid-cols-3 gap-8 relative z-10">
                        <div>
                            <div class="process-num">1</div>
                            <h3 class="font-bold text-lg -mt-6 mb-2 relative" style="color:var(--ink);">Discovery Call</h3>
                            <p class="text-sm leading-relaxed" style="color:var(--muted);">
                                Map buildings, rooms, and asset categories with your maintenance and procurement leads.
                            </p>
                        </div>
                        <div>
                            <div class="process-num">2</div>
                            <h3 class="font-bold text-lg -mt-6 mb-2 relative" style="color:var(--ink);">System Setup</h3>
                            <p class="text-sm leading-relaxed" style="color:var(--muted);">
                                Configure roles, QR labels, report flows, and inventory baselines in one shared workspace.
                            </p>
                        </div>
                        <div>
                            <div class="process-num">3</div>
                            <h3 class="font-bold text-lg -mt-6 mb-2 relative" style="color:var(--ink);">Daily Operations</h3>
                            <p class="text-sm leading-relaxed" style="color:var(--muted);">
                                Track requests, scan assets, and close maintenance loops with live campus visibility.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PROMO + FEATURES -->
    <section id="features" class="pb-20 md:pb-28">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">

            <div class="promo-banner reveal relative overflow-hidden px-8 py-14 md:py-16 mb-16 text-center text-white">
                <h2 class="text-3xl md:text-4xl font-extrabold mb-6">Push your campus ops to next level.</h2>
                <button type="button" onclick="openReportModal()" class="btn-report px-8 py-3 text-sm cursor-pointer">
                    Make Report
                </button>
            </div>

            <div class="text-center mb-12 reveal">
                <h2 class="text-3xl md:text-[2.4rem] font-extrabold" style="color:var(--ink);">
                    We help your campus grow faster.
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ([
                    ['icon' => 'package', 'title' => 'Inventory Control', 'copy' => 'Track equipment, facilities, and AV assets with live status across every room and building.'],
                    ['icon' => 'smartphone', 'title' => 'Mobile Reporting', 'copy' => 'Let faculty submit damage reports with photos and priority from any device in seconds.'],
                    ['icon' => 'qr-code', 'title' => 'QR Monitoring', 'copy' => 'Scan labels to open histories, service logs, and maintenance timelines instantly.'],
                ] as $i => $card)
                    <article class="feature-card p-8 reveal" style="transition-delay: {{ $i * 0.08 }}s;">
                        <div class="icon-circle mb-5">
                            <i data-lucide="{{ $card['icon'] }}" class="w-6 h-6"></i>
                        </div>
                        <h3 class="font-bold text-xl mb-3" style="color:var(--ink);">{{ $card['title'] }}</h3>
                        <p class="text-sm leading-relaxed mb-5" style="color:var(--muted);">{{ $card['copy'] }}</p>
                        <button type="button" onclick="openReportModal()"
                                class="inline-flex items-center gap-1.5 text-sm font-bold bg-transparent border-0 cursor-pointer p-0"
                                style="color:var(--blue);">
                            Read More
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- PRODUCT / TESTIMONIAL -->
    <section id="product" class="py-8 md:py-12"></section>
    <section id="stories" class="py-16 md:py-24" style="background:var(--soft);">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <h2 class="text-center text-3xl md:text-[2.4rem] font-extrabold mb-14 reveal" style="color:var(--ink);">
                Check what our clients are saying
            </h2>

            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="reveal flex justify-center lg:justify-start">
                    <div class="frame-photo">
                        <div class="shape-blue" aria-hidden="true"></div>
                        <div class="shape-yellow" aria-hidden="true"></div>
                        <img src="{{ asset('image/sti_logo_building.png') }}"
                             alt="Campus team"
                             class="photo shadow-xl">
                    </div>
                </div>

                <div class="reveal" style="transition-delay:.1s;">
                    <div class="quote-mark mb-2">“</div>
                    <div class="flex gap-1 mb-5">
                        @for ($s = 0; $s < 5; $s++)
                            <i data-lucide="star" class="w-5 h-5 star" fill="#fff200"></i>
                        @endfor
                    </div>
                    <p class="text-lg md:text-xl leading-relaxed italic mb-8" style="color:#374151;">
                        PaAyo made our maintenance queue visible for the first time. QR scans and mobile reports cut our turnaround dramatically—and leadership finally sees the same live picture we do.
                    </p>
                    <div class="font-extrabold text-lg" style="color:var(--ink);">Maria Santos</div>
                    <div class="text-sm mb-6" style="color:var(--muted);">Facilities Coordinator, STI College Ormoc</div>
                    <div class="text-sm font-bold tracking-wide opacity-50" style="color:var(--blue);">STI · ORMOC</div>
                </div>
            </div>
        </div>
    </section>

    <!-- BOTTOM CTA PAIR -->
    <section class="py-16 md:py-20">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="rounded-2xl p-8 border reveal" style="border-color:var(--line); background:#fff; box-shadow:0 12px 36px rgba(15,23,42,.05);">
                    <h3 class="font-extrabold text-xl mb-2" style="color:var(--ink);">Start with a report</h3>
                    <p class="text-sm mb-6" style="color:var(--muted);">Submit a maintenance concern in minutes with photo evidence.</p>
                    <button type="button" onclick="openReportModal()" class="btn-blue px-6 py-2.5 text-sm">Read More</button>
                </div>
                <div class="rounded-2xl p-8 border reveal" style="border-color:var(--line); background:#fff; box-shadow:0 12px 36px rgba(15,23,42,.05); transition-delay:.08s;">
                    <h3 class="font-extrabold text-xl mb-2" style="color:var(--ink);">Access the platform</h3>
                    <p class="text-sm mb-6" style="color:var(--muted);">Sign in to manage inventory, QR labels, and campus workflows.</p>
                    @guest
                        <button type="button" onclick="openLoginModal()" class="btn-blue px-6 py-2.5 text-sm">Read More</button>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-blue inline-flex px-6 py-2.5 text-sm no-underline">Read More</a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t py-10" style="border-color:var(--line);">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="font-extrabold text-lg" style="color:var(--ink);">
                Pa<span style="color:var(--blue);">Ayo</span>
            </div>
            <p class="text-sm text-center" style="color:var(--muted);">
                © {{ date('Y') }} PaAyo · STI College Ormoc. All rights reserved.
            </p>
            <button type="button" onclick="openReportModal()"
                    class="btn-report px-5 py-2 text-sm cursor-pointer">
                Make Report
            </button>
        </div>
    </footer>


    <!-- MODALS -->
    @guest
    <div id="loginChooserModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/40 p-4 backdrop-blur-[2px]">
        <div class="modal-animation modal-panel w-full max-w-[440px] rounded-2xl p-7 relative">
            <button onclick="closeLoginChooser()"
                class="absolute top-4 right-5 w-8 h-8 flex items-center justify-center rounded-lg"
                style="color:#717171; background:#f3f4f8; font-size:1.4rem; line-height:1; border:0; cursor:pointer;"
                onmouseover="this.style.color='#1a1a2e'" onmouseout="this.style.color='#717171'">&times;</button>

            <h2 class="text-[1.75rem] font-extrabold mb-1.5" style="color:var(--ink);">Log In</h2>
            <p class="text-sm mb-7" style="color:var(--muted);">Select your access level to continue.</p>

            <div class="space-y-3">
                <button onclick="openStaffLoginChooser()"
                    class="w-full flex items-center justify-center gap-3 py-4 rounded-full font-semibold btn-blue text-[.95rem] border-0 cursor-pointer">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    Log in as Staff
                </button>
                <button onclick="openPresidentLogin()"
                    class="w-full flex items-center justify-center gap-3 py-4 rounded-full font-semibold btn-yellow text-[.95rem] border-0 cursor-pointer">
                    <i data-lucide="crown" class="w-5 h-5"></i>
                    President Log in
                </button>
                <button onclick="openAdminLogin()"
                    class="w-full flex items-center justify-center gap-3 py-4 rounded-full font-semibold text-[.95rem] cursor-pointer"
                    style="background:transparent; border:1.5px solid var(--blue); color:var(--blue);">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    Admin Log in
                </button>
            </div>
        </div>
    </div>

    <div id="staffChooserModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/40 p-4 backdrop-blur-[2px]">
        <div class="modal-animation modal-panel w-full max-w-[440px] rounded-2xl p-7 relative">
            <button onclick="closeStaffChooser()"
                class="absolute top-4 right-5 w-8 h-8 flex items-center justify-center rounded-lg"
                style="color:#717171; background:#f3f4f8; font-size:1.4rem; line-height:1; border:0; cursor:pointer;"
                onmouseover="this.style.color='#1a1a2e'" onmouseout="this.style.color='#717171'">&times;</button>

            <h2 class="text-[1.75rem] font-extrabold mb-1" style="color:var(--ink);">Staff Login</h2>
            <p class="text-sm mb-6" style="color:var(--muted);">Select your role to continue.</p>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <button onclick="openRoleLogin('Maintenance Personnel', 2)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl btn-blue border-0 cursor-pointer">
                    <i data-lucide="wrench" class="w-7 h-7"></i>
                    <span class="font-semibold text-[.85rem] text-center leading-snug">Maintenance<br>Personnel</span>
                </button>
                <button onclick="openRoleLogin('Purchaser', 3)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl btn-yellow border-0 cursor-pointer">
                    <i data-lucide="shopping-cart" class="w-7 h-7"></i>
                    <span class="font-semibold text-[.85rem]">Purchaser</span>
                </button>
                <button onclick="openRoleLogin('Accounting', 5)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl btn-blue border-0 cursor-pointer">
                    <i data-lucide="calculator" class="w-7 h-7"></i>
                    <span class="font-semibold text-[.85rem]">Accounting</span>
                </button>
                <button onclick="openRoleLogin('Receiving Officer', 6)"
                    class="h-28 flex flex-col items-center justify-center gap-2 rounded-2xl btn-yellow border-0 cursor-pointer">
                    <i data-lucide="clipboard-list" class="w-7 h-7"></i>
                    <span class="font-semibold text-[.85rem] text-center leading-snug">Receiving<br>Officer</span>
                </button>
            </div>

            <button type="button" onclick="showModal(loginChooserModal)"
                class="w-full py-3.5 rounded-xl font-semibold cursor-pointer"
                style="background:#f3f4f8; border:1px solid var(--line); color:#717171;">
                Back
            </button>
        </div>
    </div>

    <div id="roleLoginModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/40 p-4 backdrop-blur-[2px]">
        <div class="modal-animation modal-panel w-full max-w-[440px] rounded-2xl p-7 relative">
            <button onclick="closeRoleLogin()"
                class="absolute top-4 right-5 w-8 h-8 flex items-center justify-center rounded-lg"
                style="color:#717171; background:#f3f4f8; font-size:1.4rem; line-height:1; border:0; cursor:pointer;"
                onmouseover="this.style.color='#1a1a2e'" onmouseout="this.style.color='#717171'">&times;</button>

            <h2 id="roleLoginTitle" class="text-xl font-extrabold mb-6 pr-10 leading-snug" style="color:var(--ink);">
                Maintenance Personnel Login
            </h2>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="login_role_id" id="login_role_id">
                <input type="hidden" name="login_modal" id="login_modal">

                <div class="mb-5">
                    <label class="modal-label">User ID</label>
                    <input type="text" name="user_employee_id" value="{{ old('user_employee_id') }}"
                           placeholder="Enter your user ID" class="modal-input" required>
                </div>

                <div class="mb-2">
                    <label class="modal-label">Password</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="password" placeholder="Enter your password"
                               class="modal-input" style="padding-right:48px;" required>
                        <button type="button" onclick="togglePassword()"
                            style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#8892a4; background:none; border:none; cursor:pointer;"
                            onmouseover="this.style.color='#0025cc'" onmouseout="this.style.color='#8892a4'">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <p id="loginErrorMessage" class="hidden mt-2 mb-1" style="color:#dc2626; font-size:.8rem; font-weight:500;">
                    Incorrect User ID or Password.
                </p>

                <div class="flex items-center justify-between mt-4 mb-6">
                    <label class="flex items-center gap-2" style="font-size:.8rem; color:#717171; cursor:pointer;">
                        <input type="checkbox" name="remember" style="accent-color:#0025cc;">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" style="font-size:.8rem; color:#0025cc; text-decoration:none; font-weight:600;">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="w-full flex items-center justify-center gap-2 py-3.5 rounded-full btn-blue mb-2 text-[.95rem] border-0 cursor-pointer">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    Log In
                </button>

                <a href="{{ route('auth.microsoft.redirect') }}"
                   class="w-full flex items-center justify-center gap-3 py-3.5 rounded-xl mb-3 no-underline"
                   style="background:#f8f9fd; border:1px solid var(--line); color:var(--ink); font-weight:600; font-size:.95rem;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" class="w-5 h-5" alt="Microsoft">
                    Log in with Office 365
                </a>

                <button type="button" onclick="goBackRoleLogin()"
                    class="w-full py-3.5 rounded-xl font-semibold cursor-pointer"
                    style="background:#f3f4f8; border:1px solid var(--line); color:#717171;">
                    Back to SSO
                </button>
            </form>
        </div>
    </div>
    @endguest

    <div id="reportModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 py-10"
         style="backdrop-filter:blur(6px); background:rgba(15,23,42,.35);">
        <div class="w-full max-w-6xl relative modal-animation">
            @include('reporter.partials.report-form')
        </div>
    </div>


    <script>
        lucide.createIcons();

        document.querySelectorAll('.reveal').forEach((el) => {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12 });
            io.observe(el);
        });

        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('nav-scrolled', window.scrollY > 20);
        });

        const loginChooserModal = document.getElementById('loginChooserModal');
        const staffChooserModal = document.getElementById('staffChooserModal');
        const roleLoginModal = document.getElementById('roleLoginModal');
        const reportModal = document.getElementById('reportModal');

        function closeAllModals() {
            if (loginChooserModal) loginChooserModal.classList.add('hidden');
            if (staffChooserModal) staffChooserModal.classList.add('hidden');
            if (roleLoginModal) roleLoginModal.classList.add('hidden');
            if (reportModal) reportModal.classList.add('hidden');
        }

        function showModal(modal) {
            if (!modal) return;
            closeAllModals();
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            document.documentElement.classList.add('overflow-hidden');
        }

        function hideModal(modal) {
            if (!modal) return;
            modal.classList.add('hidden');
            if (!document.querySelector('.fixed.inset-0:not(.hidden)')) {
                document.body.classList.remove('overflow-hidden');
                document.documentElement.classList.remove('overflow-hidden');
            }
        }

        function openLoginModal() { showModal(loginChooserModal); }
        function openLoginChooser() { showModal(loginChooserModal); }
        function openStaffLoginChooser() { showModal(staffChooserModal); }
        function closeLoginChooser() { hideModal(loginChooserModal); }
        function closeStaffChooser() { hideModal(staffChooserModal); }
        function closeRoleLogin() { hideModal(roleLoginModal); }
        function openReportModal() { showModal(reportModal); }
        function closeReportModal() { hideModal(reportModal); }
        function openAdminLogin() { openRoleLogin('Admin', 1); }
        function openPresidentLogin() { openRoleLogin('President', 4); }

        function openRoleLogin(roleName, roleId) {
            showModal(roleLoginModal);
            document.getElementById('roleLoginTitle').innerText = roleName + ' Login';
            document.getElementById('login_role_id').value = roleId;
            document.getElementById('login_modal').value = roleName;

            if (roleName !== "{{ old('login_modal') }}") {
                document.querySelector('input[name="user_employee_id"]').value = '';
                document.querySelector('input[name="password"]').value = '';
            }

            const errorMessage = document.getElementById('loginErrorMessage');
            if ("{{ $errors->has('user_employee_id') }}" && roleName === "{{ old('login_modal') }}") {
                errorMessage.classList.remove('hidden');
            } else {
                errorMessage.classList.add('hidden');
            }
        }

        function goBackRoleLogin() {
            const currentRole = document.getElementById('login_modal').value;
            showModal(currentRole === 'Admin' || currentRole === 'President' ? loginChooserModal : staffChooserModal);
        }

        function togglePassword() {
            const input = document.getElementById('password');
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

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllModals();
                document.body.classList.remove('overflow-hidden');
            }
        });

        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach((section) => {
                const top = section.offsetTop - 200;
                if (pageYOffset >= top && pageYOffset < top + section.clientHeight) {
                    current = section.getAttribute('id');
                }
            });
            navLinks.forEach((link) => {
                link.classList.toggle('active', link.getAttribute('href') === '#' + current);
            });
        });
    </script>

    @if ($errors->any())
    <script>
        window.addEventListener('load', function () {
            const modalName = "{{ old('login_modal') }}";
            const roleId = "{{ old('login_role_id') }}";
            if (modalName !== '') openRoleLogin(modalName, roleId);
        });
    </script>
    @endif

    @if(session('success'))
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'Report submitted',
            html: `<div class="swal-message">Your maintenance report has been submitted successfully and is now awaiting review.</div>`,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#ffffff',
            color: '#111827',
            width: '420px',
            padding: '1.75rem',
            backdrop: 'rgba(15, 23, 42, 0.35)',
            customClass: {
                popup: 'modern-success-popup',
                title: 'modern-success-title',
                htmlContainer: 'modern-success-content',
                timerProgressBar: 'modern-success-progress',
            },
        });
    });
    </script>
    @endif

</body>
</html>
