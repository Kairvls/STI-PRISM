<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>sign in — PaAyo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue: #0025cc;
            --ink: #1a1a2e;
        }

        html, body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--ink);
            margin: 0;
            min-height: 100%;
        }

        .login-shell {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            background:
                radial-gradient(ellipse 55% 70% at 12% 45%, rgba(196, 214, 255, 0.55) 0%, transparent 70%),
                radial-gradient(ellipse 40% 50% at 88% 20%, rgba(255, 242, 0, 0.08) 0%, transparent 65%),
                #ffffff;
        }

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

        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 36rem;
            border-radius: 1.25rem;
            border: 1px solid rgba(232, 236, 244, .9);
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(10px);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .10);
            padding: 3.25rem 3rem;
            text-align: center;
        }

        .login-logo {
            display: block;
            margin: 0 auto 0.75rem;
            width: 9.5rem;
            height: 9.5rem;
            object-fit: contain;
        }

        .login-title {
            margin: 0 0 1.75rem;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: .02em;
            color: #0f172a;
        }

        .login-btn {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 0.95rem 1.25rem;
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            text-decoration: none;
            transition: background .2s ease;
        }

        .login-btn:hover {
            background: #f8fafc;
        }

        .login-btn img {
            width: 1.15rem;
            height: 1.15rem;
        }

        @media (max-width: 640px) {
            .hero-wave-svg {
                width: 100%;
                max-width: 100%;
                left: 0;
            }

            .hero-wave-svg.back {
                width: 90%;
                left: -5%;
            }

            .login-card {
                max-width: 100%;
                padding: 2.5rem 1.5rem;
            }

            .login-logo {
                width: 8rem;
                height: 8rem;
            }

            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="hero-wave-wrap" aria-hidden="true">
            <svg class="hero-wave-svg back" viewBox="0 0 800 700" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <path fill="#d7e4ff"
                      d="M120,-40 C280,-20 340,80 420,160 C520,260 620,220 700,300 C780,380 760,520 640,580 C500,650 360,620 240,560 C80,480 -40,420 -20,280 C0,140 -20,-60 120,-40 Z"/>
            </svg>
            <svg class="hero-wave-svg front" viewBox="0 0 800 700" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <linearGradient id="adminWaveFill" x1="0%" y1="0%" x2="80%" y2="100%">
                        <stop offset="0%" stop-color="#c8d9ff"/>
                        <stop offset="45%" stop-color="#dde8ff"/>
                        <stop offset="100%" stop-color="#eef3ff"/>
                    </linearGradient>
                </defs>
                <path fill="url(#adminWaveFill)"
                      d="M40,40 C180,-30 320,20 400,110 C490,220 560,180 650,250 C760,340 790,450 700,540 C590,650 420,680 280,620 C120,550 20,480 10,340 C0,200 -60,100 40,40 Z"/>
                <path fill="#f4f7ff" opacity=".55"
                      d="M90,180 C200,120 300,150 360,220 C430,300 500,280 560,330 C640,400 620,480 520,520 C400,570 280,540 190,480 C80,400 20,320 90,180 Z"/>
            </svg>
            <svg class="absolute bottom-0 left-0 w-full h-[120px] md:h-[160px]" viewBox="0 0 1440 160" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="#ffffff"
                      d="M0,80 C240,140 480,20 720,70 C960,120 1200,40 1440,90 L1440,160 L0,160 Z"/>
                <path fill="none" stroke="#c5d6ff" stroke-width="2" opacity=".5"
                      d="M0,78 C240,138 480,18 720,68 C960,118 1200,38 1440,88"/>
            </svg>
        </div>

        <div class="login-card">
            <img
                src="{{ asset('image/paayo_logo_original.png') }}"
                alt="PaAyo"
                class="login-logo"
            >
            <p class="login-title">PaAyo</p>

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-left text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-left text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <a
                href="{{ route('auth.microsoft.redirect', ['admin' => 1]) }}"
                class="login-btn"
            >
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="">
                Log in with Office 365
            </a>
        </div>
    </div>
</body>
</html>
