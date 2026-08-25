<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaAyo | Campus Maintenance Platform</title>

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
        html {
            scroll-behavior: smooth;
        }

        #top, #product, #process, #features {
            scroll-margin-top: 96px;
        }

        html, body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #fff;
            color: var(--ink);
        }

        body {
            overflow-x: clip;
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
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
            transition: background .22s ease, box-shadow .22s ease, transform .22s cubic-bezier(.22,1,.36,1);
            will-change: transform;
            position: relative;
            overflow: hidden;
        }
        .btn-blue::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: rgba(255,255,255,0);
            transition: background .22s ease;
        }
        .btn-blue:hover::after { background: rgba(255,255,255,.08); }
        .btn-blue:hover {
            background: var(--blue-dark);
            box-shadow: 0 12px 28px rgba(0,37,204,.28);
            transform: translateY(-2px);
        }
        .btn-blue:active { transform: scale(.97); }

        .btn-yellow {
            background: var(--yellow);
            color: var(--ink);
            font-weight: 700;
            border-radius: 999px;
            transition: filter .22s ease, box-shadow .22s ease, transform .22s cubic-bezier(.22,1,.36,1);
            will-change: transform;
        }
        .btn-yellow:hover {
            filter: brightness(.97);
            box-shadow: 0 10px 24px rgba(255,242,0,.4);
            transform: translateY(-2px);
        }
        .btn-yellow:active { transform: scale(.97); }

        .btn-report {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #eceef2;
            color: #374151;
            font-weight: 600;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            padding: 5px 16px 5px 5px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            transition: background .2s, box-shadow .2s, transform .2s;
        }
        .btn-report:hover {
            background: #e4e6eb;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
        }
        .btn-report-icon {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            background: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08);
            color: #111827;
            flex-shrink: 0;
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
        .pixel-overlay {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            background-image:
                linear-gradient(to right, rgba(216, 226, 248, .22) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(216, 226, 248, .22) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(ellipse 95% 70% at 50% 0%, #000 12%, transparent 78%);
            -webkit-mask-image: radial-gradient(ellipse 95% 70% at 50% 0%, #000 12%, transparent 78%);
        }

        .pixel-mosaic {
            position: absolute;
            inset: -8% 0 auto 0;
            width: 100%;
            height: min(720px, 92vh);
            opacity: .72;
            mask-image: radial-gradient(ellipse 92% 78% at 50% 8%, #000 0%, transparent 78%);
            -webkit-mask-image: radial-gradient(ellipse 92% 78% at 50% 8%, #000 0%, transparent 78%);
        }

        .pixel-mosaic.soft {
            opacity: .48;
            height: 420px;
            mask-image: linear-gradient(180deg, transparent 0%, #000 18%, #000 58%, transparent 100%);
            -webkit-mask-image: linear-gradient(180deg, transparent 0%, #000 18%, #000 58%, transparent 100%);
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

        .month-carousel {
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
            cursor: grab;
            scrollbar-width: none;
            -ms-overflow-style: none;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x mandatory;
        }

        .month-carousel::-webkit-scrollbar {
            display: none;
        }

        .month-carousel.is-dragging {
            cursor: grabbing;
            scroll-snap-type: none;
        }

        .month-track {
            display: flex;
            width: max-content;
        }

        .month-item {
            flex: 0 0 calc((min(1180px, 100vw) - 2.5rem) / 7);
            text-align: center;
            scroll-snap-align: start;
        }

        .month-item.is-current {
            color: var(--ink);
            opacity: 1;
        }

        @media (max-width: 640px) {
            .month-item {
                flex-basis: calc((100vw - 2.5rem) / 3.5);
            }
        }

        .process-num {
            font-size: clamp(5.5rem, 12vw, 8.5rem);
            font-weight: 800;
            line-height: .75;
            color: #eef1f8;
            letter-spacing: -.06em;
            user-select: none;
            pointer-events: none;
        }

        .process-track {
            position: relative;
            min-height: 340px;
        }

        .process-wave {
            filter: drop-shadow(0 10px 10px rgba(0, 37, 204, .12));
        }

        .process-orb {
            position: absolute;
            right: -80px;
            top: 40%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(196,214,255,.55) 0%, rgba(196,214,255,0) 70%);
            pointer-events: none;
        }

        .process-marker {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #eef0f5;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .10);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            position: relative;
            z-index: 2;
        }

        .process-step {
            position: relative;
        }

        .features-wrap {
            position: relative;
            background:
                radial-gradient(ellipse 70% 40% at 50% 0%, rgba(196, 214, 255, .45), transparent 70%),
                #ffffff;
            overflow: visible;
        }

        .feature-card {
            background: #fff;
            border-radius: 16px;
            border: 0;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .06);
            transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s ease;
            transform-style: preserve-3d;
            perspective: 800px;
        }
        .feature-card:hover {
            box-shadow: 0 24px 50px rgba(0, 37, 204, .12);
        }

        .feature-icon {
            color: var(--blue);
            margin-bottom: 18px;
        }

        .feature-link {
            color: #0d9488;
            font-weight: 600;
            font-size: 14px;
            background: none;
            border: 0;
            padding: 0;
            cursor: pointer;
        }
        .feature-link:hover { color: #0f766e; }

        .promo-banner {
            background:
                linear-gradient(180deg, rgba(12, 18, 48, .72) 0%, rgba(0, 37, 204, .55) 100%),
                url({{ asset('image/landing_image_asset.png') }}) center/cover;
            border-radius: 22px;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .btn-banner {
            background: linear-gradient(90deg, #ffb703, #fff200);
            color: #1a1a2e;
            font-weight: 700;
            border-radius: 999px;
            border: 0;
            padding: 12px 28px;
            transition: filter .2s, box-shadow .2s;
        }
        .btn-banner:hover {
            filter: brightness(.97);
            box-shadow: 0 10px 24px rgba(255, 183, 3, .35);
        }

        .btn-platform {
            background: var(--blue);
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            border: 0;
            padding: 14px 36px;
            transition: background .2s, box-shadow .2s;
        }
        .btn-platform:hover {
            background: var(--blue-dark);
            box-shadow: 0 12px 28px rgba(0, 37, 204, .28);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #fff;
            color: var(--blue);
            font-weight: 700;
            border-radius: 999px;
            border: 1px solid rgba(0, 37, 204, .16);
            padding: 12px 26px;
            text-decoration: none;
            transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .btn-outline:hover {
            background: #f6f8ff;
            box-shadow: 0 10px 24px rgba(0, 37, 204, .12);
            transform: translateY(-2px);
        }

        .btn-soft {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f8faff;
            color: var(--ink);
            font-weight: 700;
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: 12px 24px;
            text-decoration: none;
            transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
        }
        .btn-soft:hover {
            background: #eef3ff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            transform: translateY(-2px);
        }

        .product-stage {
            display: grid;
            align-items: center;
            max-width: 1040px;
            margin: 0 auto;
            gap: 28px;
        }

        @media (min-width: 1024px) {
            .product-stage {
                grid-template-columns: minmax(280px, 390px) minmax(0, 1fr);
                gap: 0;
            }
        }

        .product-visual {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
        }

        .product-panel {
            position: relative;
            z-index: 3;
            background: rgba(255, 255, 255, .86);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, .95);
            border-radius: 28px;
            padding: 28px 24px;
            box-shadow:
                0 24px 60px rgba(15, 23, 42, .10),
                0 2px 0 rgba(255, 255, 255, .7) inset;
        }

        @media (min-width: 1024px) {
            .product-panel {
                margin-left: -48px;
                padding: 36px 36px 32px;
            }
        }

        .product-kicker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--yellow);
            color: var(--ink);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 5px 10px;
            box-shadow: 0 8px 18px rgba(255, 242, 0, .28);
        }

        .product-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #eef2ff;
            color: var(--blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .product-status {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .product-status-step {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f3f6ff;
            color: var(--blue);
            border: 1px solid #e0e7ff;
        }

        .product-status-step.is-next {
            background: #fff;
            color: #4b5563;
            border-color: var(--line);
        }

        .product-status-step.is-done {
            background: var(--yellow);
            color: var(--ink);
            border-color: transparent;
        }

        .product-status-arrow {
            color: #c5cdd8;
            flex-shrink: 0;
        }

        .product-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            border-top: 1px solid rgba(232, 236, 244, .9);
            padding-top: 18px;
        }

        @media (max-width: 520px) {
            .product-split { grid-template-columns: 1fr; }
        }

        .product-mini {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
        }

        .stories-wrap {
            position: relative;
            isolation: isolate;
            background: transparent;
        }

        .stories-wrap::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(205, 216, 232, .42) 0%, rgba(243, 246, 255, 0) 46%),
                linear-gradient(180deg, var(--soft) 0%, var(--soft) 58%, rgba(243, 246, 255, .35) 82%, rgba(255, 255, 255, 0) 100%);
            clip-path: polygon(0 0, 50% 88px, 100% 0, 100% calc(100% - 88px), 50% 100%, 0 calc(100% - 88px));
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, .28) 8%, #000 22%, #000 64%, rgba(0, 0, 0, .55) 82%, transparent 100%);
                    mask-image: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, .28) 8%, #000 22%, #000 64%, rgba(0, 0, 0, .55) 82%, transparent 100%);
        }

        .stories-v-fade {
            position: absolute;
            left: -10%;
            right: -10%;
            top: -12px;
            height: 170px;
            z-index: -1;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(205, 216, 232, .55) 0%, rgba(243, 246, 255, .9) 48%, rgba(238, 243, 250, .45) 100%);
            clip-path: polygon(0 0, 50% 100%, 100% 0);
            filter: blur(20px);
            opacity: .8;
            -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .15) 0%, rgba(0, 0, 0, .55) 42%, transparent 88%);
                    mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .15) 0%, rgba(0, 0, 0, .55) 42%, transparent 88%);
        }

        .stories-wrap::after {
            content: '';
            position: absolute;
            left: -8%;
            right: -8%;
            bottom: -8px;
            height: 150px;
            z-index: -1;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(205, 216, 232, .5) 0%, rgba(238, 243, 250, .85) 48%, rgba(247, 250, 253, .4) 100%);
            clip-path: polygon(0 0, 100% 0, 50% 100%);
            filter: blur(22px);
            opacity: .9;
            -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .55) 0%, transparent 88%);
                    mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .55) 0%, transparent 88%);
        }

        @media (max-width: 767px) {
            .stories-wrap::before {
                clip-path: polygon(0 0, 50% 56px, 100% 0, 100% calc(100% - 56px), 50% 100%, 0 calc(100% - 56px));
            }
            .stories-v-fade {
                height: 120px;
            }
            .stories-wrap::after {
                height: 110px;
            }
        }

        .frame-photo {
            position: relative;
            width: min(100%, 390px);
        }
        .frame-photo .photo {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            border-radius: 28px;
            position: relative;
            z-index: 2;
            border: 3px solid #fff;
            box-shadow: 0 28px 60px rgba(0, 37, 204, .16);
        }
        .frame-photo .shape-blue {
            position: absolute;
            width: 68%;
            height: 42%;
            right: -11%;
            top: -9%;
            background: var(--blue);
            border-radius: 28px 28px 8px 80px;
            z-index: 1;
            box-shadow: 0 18px 40px rgba(0, 37, 204, .22);
        }
        .frame-photo .shape-yellow {
            position: absolute;
            width: 52%;
            height: 34%;
            left: -10%;
            bottom: -8%;
            background: var(--yellow);
            border-radius: 80px 20px 28px 12px;
            z-index: 1;
            box-shadow: 0 14px 28px rgba(255, 242, 0, .28);
        }

        /* ── Reveal / entrance animations ── */
        .reveal {
            opacity: 0;
            transform: translateY(32px);
            transition: opacity 1.1s cubic-bezier(.22,1,.36,1), transform 1.1s cubic-bezier(.22,1,.36,1);
        }
        .reveal.visible {
            opacity: 1;
            transform: none;
        }

        /* Slide-in from left / right */
        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
            transition: opacity 1.1s cubic-bezier(.22,1,.36,1), transform 1.1s cubic-bezier(.22,1,.36,1);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
            transition: opacity 1.1s cubic-bezier(.22,1,.36,1), transform 1.1s cubic-bezier(.22,1,.36,1);
        }
        .reveal-left.visible, .reveal-right.visible {
            opacity: 1;
            transform: none;
        }

        /* Scale-in for cards */
        .reveal-scale {
            opacity: 0;
            transform: scale(.92) translateY(20px);
            transition: opacity 1s cubic-bezier(.22,1,.36,1), transform 1s cubic-bezier(.22,1,.36,1);
        }
        .reveal-scale.visible {
            opacity: 1;
            transform: none;
        }

        /* ── Scroll-progress bar ── */
        #scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, var(--blue), #6688ff);
            z-index: 9999;
            transition: width .08s linear;
            border-radius: 0 3px 3px 0;
        }

        /* ── Hero heading gradient shimmer ── */
        .shimmer-text {
            background: linear-gradient(
                90deg,
                var(--ink) 0%,
                var(--ink) 35%,
                #4466ee 50%,
                var(--ink) 65%,
                var(--ink) 100%
            );
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmerMove 4s linear infinite 1.2s;
        }
        @keyframes shimmerMove {
            0%   { background-position: 200% center; }
            100% { background-position: -200% center; }
        }

        /* ── Magnetic / lift on interactive elements ── */
        .magnetic {
            transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s ease;
            will-change: transform;
        }
        .magnetic:hover { transform: translateY(-3px) scale(1.025); }
        .magnetic:active { transform: scale(.97); }

        /* ── Floating particle dots (hero) ── */
        .hero-particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            animation: particleDrift linear infinite;
            opacity: 0;
        }
        @keyframes particleDrift {
            0%   { transform: translateY(0) rotate(0deg);   opacity: 0; }
            10%  { opacity: .6; }
            90%  { opacity: .4; }
            100% { transform: translateY(-120px) rotate(360deg); opacity: 0; }
        }

        /* ── Stagger helpers (JS sets --delay via inline style) ── */
        .stagger { transition-delay: var(--delay, 0s); }

        /* ── Number counter pulse ── */
        @keyframes counterPop {
            0%   { transform: scale(1); }
            40%  { transform: scale(1.18); }
            100% { transform: scale(1); }
        }
        .counter-pop { animation: counterPop .55s cubic-bezier(.22,1,.36,1) forwards; }

        /* ── Smooth underline hover on nav links ── */
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -3px;
            width: 0;
            height: 2px;
            background: var(--blue);
            border-radius: 2px;
            transition: width .25s cubic-bezier(.22,1,.36,1);
        }
        .nav-link:hover::after,
        .nav-link.active::after { width: 100%; }

        /* Modals */
        .modal-animation { animation: modalShow .25s ease; }
        @keyframes modalShow {
            from { opacity: 0; transform: translateY(18px) scale(.97); }
            to { opacity: 1; transform: none; }
        }
        .modal-panel {
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: 0 20px 48px rgba(15,23,42,.10);
        }
        .modal-input {
            width: 100%;
            background: #f7f8fb;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            color: var(--ink);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .modal-input::placeholder { color: #9aa1b5; }
        .modal-input:focus {
            border-color: #b8c8ff;
            box-shadow: 0 0 0 4px rgba(0,37,204,.08);
            background: #fff;
        }
        .modal-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #5b6472;
            margin-bottom: 8px;
            letter-spacing: 0;
            text-transform: none;
        }
        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            background: #f4f5f8;
            color: #6b7280;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }
        .modal-close:hover {
            background: #eef1f6;
            color: var(--ink);
        }
        .login-title {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--ink);
            margin: 0 40px 4px 0;
        }
        .login-subtitle {
            font-size: .875rem;
            color: var(--muted);
            margin: 0 0 22px;
        }
        .login-choice {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 13px 16px;
            border-radius: 14px;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: background .2s ease, border-color .2s ease, color .2s ease;
        }
        .login-choice--primary {
            background: var(--blue);
            color: #fff;
            border-color: var(--blue);
        }
        .login-choice--primary:hover { background: var(--blue-dark); }
        .login-choice--accent {
            background: #fffce8;
            color: var(--ink);
            border-color: #efe7a8;
        }
        .login-choice--accent:hover { background: #fff8c6; }
        .login-choice--ghost {
            background: #fff;
            color: var(--blue);
            border-color: #d7def8;
        }
        .login-choice--ghost:hover { background: #f7f9ff; }
        .login-role {
            height: 108px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 16px;
            border: 1px solid var(--line);
            background: #fff;
            cursor: pointer;
            color: var(--ink);
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .login-role:hover {
            border-color: #c9d4f5;
            background: #f8faff;
            box-shadow: 0 8px 20px rgba(15,23,42,.05);
        }
        .login-role-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .login-role--blue .login-role-icon {
            background: #eef3ff;
            color: var(--blue);
        }
        .login-role--yellow .login-role-icon {
            background: #fff8c6;
            color: #7c6400;
        }
        .login-back {
            width: 100%;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: #fff;
            color: #5b6472;
            font-weight: 600;
            font-size: .9rem;
            cursor: pointer;
            transition: background .2s ease, border-color .2s ease;
        }
        .login-back:hover {
            background: #f7f8fb;
            border-color: #d7deee;
        }
        .login-microsoft {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            font-weight: 600;
            font-size: .9rem;
            text-decoration: none;
            margin-bottom: 10px;
            transition: background .2s ease, border-color .2s ease;
        }
        .login-microsoft:hover {
            background: #f7f8fb;
            border-color: #d7deee;
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

        .swal2-container .swal2-popup.paayo-swal {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            width: 400px !important;
            max-width: calc(100vw - 32px) !important;
            border-radius: 28px !important;
            border: 1px solid #e8ecf4 !important;
            background:
                radial-gradient(ellipse 80% 50% at 50% 0%, rgba(199, 216, 255, .45), transparent 62%),
                #ffffff !important;
            box-shadow: 0 32px 80px rgba(15, 23, 42, .18) !important;
            padding: 2.1rem 1.75rem 1.5rem !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-title,
        .paayo-swal-heading {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.4rem !important;
            font-weight: 800 !important;
            letter-spacing: -.035em !important;
            color: #1a1a2e !important;
            line-height: 1.2 !important;
            margin: 0 0 8px !important;
            padding: 0 !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-html-container,
        .paayo-swal-text {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            color: #6b7280 !important;
            font-size: .95rem !important;
            line-height: 1.55 !important;
            margin: 0 auto !important;
            max-width: 300px;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-actions {
            margin: 22px 0 0 !important;
            width: 100% !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-confirm,
        .paayo-swal-btn {
            background: #0025cc !important;
            color: #fff !important;
            border: 0 !important;
            border-radius: 999px !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-weight: 600 !important;
            font-size: .95rem !important;
            padding: 12px 32px !important;
            min-width: 140px !important;
            box-shadow: 0 12px 28px rgba(0, 37, 204, .24) !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-confirm:hover,
        .paayo-swal-btn:hover {
            background: #001ca3 !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-icon {
            border: 0 !important;
            width: 58px !important;
            height: 58px !important;
            margin: 0 auto 1.05rem !important;
            border-radius: 18px !important;
            box-shadow: 0 12px 24px rgba(0, 37, 204, .18);
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-success,
        .swal2-container .swal2-popup.paayo-swal .paayo-icon-success {
            background: #0025cc !important;
            color: #fff !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-info,
        .swal2-container .swal2-popup.paayo-swal .paayo-icon-info {
            background: #eef2ff !important;
            color: #0025cc !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-warning,
        .swal2-container .swal2-popup.paayo-swal .paayo-icon-warning {
            background: #fff200 !important;
            color: #1a1a2e !important;
            box-shadow: 0 10px 22px rgba(255, 242, 0, .28);
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-error,
        .swal2-container .swal2-popup.paayo-swal .paayo-icon-error {
            background: #fef2f2 !important;
            color: #dc2626 !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-success-ring,
        .swal2-container .swal2-popup.paayo-swal .swal2-success-circular-line-left,
        .swal2-container .swal2-popup.paayo-swal .swal2-success-circular-line-right,
        .swal2-container .swal2-popup.paayo-swal .swal2-success-fix {
            display: none !important;
        }
        .swal2-container .swal2-popup.paayo-swal .swal2-icon svg {
            width: 26px !important;
            height: 26px !important;
        }
        .paayo-swal-in {
            animation: paayoSwalIn .28s ease;
        }
        @keyframes paayoSwalIn {
            from { opacity: 0; transform: translateY(18px) scale(.97); }
            to { opacity: 1; transform: none; }
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

        /* ── Lower-page modern system ── */
        .proof-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid var(--line);
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            box-shadow: 0 8px 24px rgba(15,23,42,.04);
        }

        .stat-bento {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 28px 24px;
            position: relative;
            overflow: hidden;
            transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
        }
        .stat-bento:hover {
            transform: translateY(-4px);
            border-color: rgba(0,37,204,.18);
            box-shadow: 0 20px 48px rgba(0,37,204,.08);
        }
        .stat-bento .stat-num {
            font-size: clamp(2rem, 4vw, 2.75rem);
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1;
            color: var(--ink);
        }

        .step-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 28px 24px;
            height: 100%;
            position: relative;
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .step-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 50px rgba(15,23,42,.08);
        }
        .step-index {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--blue);
            color: #fff;
            font-weight: 800;
            font-size: 15px;
        }

        .bento-feature {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 32px 28px;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            transition: transform .3s ease, box-shadow .3s ease;
        }
        .bento-feature:hover {
            transform: translateY(-8px);
            box-shadow: 0 28px 60px rgba(0,37,204,.1);
        }
        .bento-feature.accent {
            background: linear-gradient(165deg, #0025cc 0%, #00187a 100%);
            color: #fff;
            border: 0;
        }
        .bento-feature.accent p { color: rgba(255,255,255,.78) !important; }
        .bento-feature.accent h3 { color: #fff !important; }
        .bento-feature.soft-yellow {
            background: linear-gradient(165deg, #fffce6 0%, #fff 70%);
        }

        .snapshot-panel {
            background: #0b1020;
            border-radius: 32px;
            overflow: hidden;
            color: #fff;
        }

        .cta-dark {
            background: #0b1020;
            color: #fff;
            border-radius: 28px;
        }
        .cta-light {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: 0 18px 40px rgba(15,23,42,.05);
        }

        .site-footer {
            background: #ffffff;
            color: #6b7280;
        }

        .month-campus-row {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: stretch;
            position: relative;
            z-index: 1;
        }

        @media (min-width: 1024px) {
            .month-campus-row {
                flex-direction: row;
            }
        }

        .month-chart-card,
        .campus-analysis-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 12px 36px rgba(15,23,42,.05);
            display: flex;
            flex-direction: column;
        }

        .campus-analysis-card {
            background: #eef1ec;
            border: 0;
        }

        @media (min-width: 1024px) {
            .month-chart-card,
            .campus-analysis-card {
                flex: 1 1 0;
                min-width: 0;
                align-self: stretch;
            }
        }

        .month-bars {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            flex: 1 1 auto;
            min-height: 168px;
        }

        .month-bar {
            flex: 1;
            min-width: 0;
            border-radius: 6px 6px 2px 2px;
            background: #dbe3ff;
            position: relative;
            transition: background .2s ease, transform .2s ease;
        }

        .month-bar.is-peak {
            background: var(--blue);
        }

        .month-bar:hover {
            transform: translateY(-2px);
            background: var(--blue);
        }

        .month-bar-tip {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 8px);
            transform: translateX(-50%);
            background: #111827;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 7px;
            border-radius: 8px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
        }

        .month-bar:hover .month-bar-tip { opacity: 1; }

        .analysis-body {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            flex: 1;
        }

        @media (min-width: 640px) {
            .analysis-body {
                grid-template-columns: 132px 1fr;
                gap: 14px;
                align-items: stretch;
            }
        }

        .analysis-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media (min-width: 640px) {
            .analysis-metrics {
                grid-template-columns: 1fr;
            }
        }

        .analysis-metric {
            background: #fff;
            border-radius: 18px;
            padding: 16px 14px 14px;
            box-shadow: 0 8px 20px rgba(15,23,42,.04);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .analysis-metric-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: var(--ink);
            margin-bottom: 12px;
        }

        .analysis-flow {
            background: #d7e4cf;
            border-radius: 22px;
            padding: 16px 16px 14px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            position: relative;
        }

        .analysis-gauge {
            position: relative;
            width: min(100%, 240px);
            margin: 4px auto 0;
        }

        .analysis-gauge svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .analysis-gauge-center {
            position: absolute;
            left: 50%;
            top: 56%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .analysis-tooltip {
            position: absolute;
            top: 18%;
            right: 4%;
            background: #215c3a;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            line-height: 1;
            padding: 8px 12px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(33,92,58,.25);
        }

        .analysis-tooltip::after {
            content: "";
            position: absolute;
            left: -5px;
            bottom: 8px;
            width: 10px;
            height: 10px;
            background: #215c3a;
            transform: rotate(45deg);
            border-radius: 2px;
        }

        .analysis-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: auto;
            padding-top: 8px;
        }

        .analysis-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
        }

        .analysis-legend-swatch {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        .month-analysis-section {
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }

        .month-analysis-wave {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .month-analysis-wave .edge {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 58%;
            min-height: 210px;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #c9cfde; border-radius: 6px; }
    </style>
</head>

<body>

    <!-- Scroll progress bar -->
    <!--<div id="scroll-progress" aria-hidden="true"></div>-->

    <!-- NAV -->
    <nav id="navbar" class="fixed top-0 inset-x-0 z-50 transition-all duration-300 bg-transparent">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 h-[72px] flex items-center justify-between gap-4">
            <a href="#top" class="text-[1.15rem] font-extrabold tracking-tight no-underline" style="color:var(--ink);">
                Pa<span style="color:var(--blue);">Ayo</span>
            </a>

            <div class="hidden lg:flex items-center gap-9">
                <a href="#top" class="nav-link active">Home</a>

                <a href="#process" class="nav-link">Process</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#product" class="nav-link">System</a>
                <!--<button type="button" onclick="openReportModal()"
                        class="nav-link bg-transparent border-0 cursor-pointer p-0">
                    Make Report
                </button>-->
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                <button type="button" onclick="openReportModal()"
                        class="lg:hidden text-xs sm:text-sm font-semibold bg-transparent border-0 cursor-pointer p-0"
                        style="color:var(--ink);">
                    Report
                </button>
                @guest
                    <button type="button" onclick="openReportModal()"
                            class="hidden sm:inline text-sm font-semibold bg-transparent border-0 cursor-pointer"
                            style="color:var(--ink);">
                            Make Report
                    </button>
                    <button type="button" onclick="openLoginModal()"
                            class="btn-blue px-5 py-2.5 text-sm border-0 cursor-pointer">
                        Sign In
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

                <div class="reveal-left relative">
                    <p class="text-sm font-semibold mb-4" style="color:var(--blue);">
                        Campus growth solution in a single platform.
                    </p>

                    <h1 class="shimmer-text font-extrabold tracking-tight leading-[1.12] mb-5"
                        style="font-size:clamp(2.35rem,5vw,3.6rem);">
                        We are here to make easy your campus asset ops
                    </h1>

                    <p class="text-base leading-relaxed max-w-md mb-8" style="color:var(--muted);">
                        PaAyo centralizes procurement, inventory, and maintenance monitoring with mobile damage reporting and QR tracking for STI College Ormoc.
                    </p>

                    <form id="reporterRegisterForm" method="POST" action="{{ route('reporter.register.start') }}" class="flex flex-col sm:flex-row gap-3 mb-2 max-w-lg">
                        @csrf
                        <input type="email"
                               id="reporterRegisterEmail"
                               name="email"
                               required
                               maxlength="255"
                               placeholder="Enter your email to start reporting"
                               class="flex-1 min-w-0 px-5 py-3.5 rounded-[10px] text-sm outline-none"
                               style="background:#fff; border:1px solid var(--line); box-shadow:0 8px 24px rgba(15,23,42,.06); color:var(--ink);">
                        <button type="submit" id="reporterRegisterSubmit"
                                class="btn-dark px-8 py-3.5 text-sm shrink-0 border-0 cursor-pointer inline-flex items-center justify-center gap-2 min-w-[118px]">
                            <svg id="reporterRegisterSpinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"></circle>
                                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                            </svg>
                            <span id="reporterRegisterSubmitLabel">Submit</span>
                        </button>
                    </form>
                    <p id="reporterRegisterLockMsg" class="hidden text-xs mb-3 max-w-lg font-semibold" style="color:#dc2626;"></p>
                    <p class="text-xs mb-4 max-w-lg" style="color:var(--muted);">
                        First time reporting? Enter a real email you can open. We’ll send a form for you to fill up your employee ID, name, type, and contact. This is not a login account.
                    </p>

                    <div class="flex flex-wrap gap-3 mb-6">
                        
                        <button type="button" onclick="openLoginModal()"
                                    class="btn-blue magnetic inline-flex items-center gap-2 px-7 py-3.5 text-sm border-0 cursor-pointer">
                                System Login
                            </button>
                        @guest
                        <button type="button" onclick="openReportModal()"
                                class="btn-report text-sm cursor-pointer">
                            <span class="btn-report-icon">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            </span>
                            Make Report
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
                <div class="relative h-[420px] md:h-[500px] reveal-right" style="transition-delay:.1s;">
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
            <p class="text-lg md:text-2xl font-semibold mb-10" style="color:var(--ink);">
                Over <span data-count="{{ $yearlyReportTotal }}" style="color:var(--blue);">{{ $yearlyReportTotal }}</span>
                {{ $yearlyReportTotal === 1 ? 'report' : 'reports' }} submitted in
                <span class="font-extrabold">PaAyo</span>
                {{ (int) $yearlyReportYear === (int) now()->year ? 'this year' : 'in '.$yearlyReportYear }}
            </p>
            <div class="month-carousel" aria-label="Months">
                <div class="month-track">
                    @php
                        $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                    @endphp
                    @foreach ($months as $index => $month)
                        <span class="logo-muted month-item{{ ($index + 1) === (int) now()->month ? ' is-current' : '' }}"
                              @if (($index + 1) === (int) now()->month) aria-current="date" @endif>{{ $month }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- PROCESS -->
    <section id="process" class="py-20 md:py-28 overflow-hidden relative">
        <div class="process-orb hidden lg:block" aria-hidden="true"></div>
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-10 items-center">
                <div class="lg:col-span-4 reveal-left">
                    <p class="text-xs font-bold tracking-[0.16em] uppercase mb-4" style="color:var(--blue);">
                        PaAyo operation across campus
                    </p>
                    <h2 class="text-3xl md:text-[2.4rem] font-extrabold leading-tight mb-5" style="color:var(--ink);">
                        One reporting flow for the actual campus process
                    </h2>
                    <p class="text-sm leading-relaxed mb-8 max-w-sm" style="color:var(--muted);">
                        The system follows the same steps your offices already do manually, but records and passes each report through the platform.
                    </p>
                    <a href="{{ route('reporter.faq') }}" class="btn-outline text-sm">
                        <i data-lucide="circle-help" class="w-4 h-4"></i>
                        Read FAQ
                    </a>
                </div>

                <div class="lg:col-span-8 process-track reveal-right" style="transition-delay:.08s;">
                    <svg class="process-wave hidden md:block absolute left-0 right-0 top-[92px] w-full h-[120px] pointer-events-none z-[1]"
                         viewBox="0 0 760 120" fill="none" aria-hidden="true" preserveAspectRatio="none">
                        <path d="M36 38 C140 38, 170 98, 250 98 S360 38, 454 38 S560 98, 640 98 S710 38, 724 38"
                              stroke="#0025cc" stroke-width="3" stroke-linecap="round"/>
                    </svg>

                    <div class="grid md:grid-cols-3 gap-10 md:gap-6 relative z-10">
                        <div class="process-step md:pt-2">
                            <div class="process-num">1</div>
                            <div class="process-marker -mt-8 mb-4">
                                <i data-lucide="phone" class="w-4 h-4"></i>
                            </div>
                            <h3 class="font-bold text-[15px] mb-2" style="color:var(--ink);">Report Submission</h3>
                            <p class="text-[13px] leading-relaxed max-w-[200px]" style="color:var(--muted);">
                                Teachers or reporters submit equipment concerns online instead of going room to room or reporting verbally in the office.
                            </p>
                        </div>

                        <div class="process-step md:pt-16">
                            <div class="process-num">2</div>
                            <div class="process-marker -mt-8 mb-4">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                            </div>
                            <h3 class="font-bold text-[15px] mb-2" style="color:var(--ink);">Inspection and Decision</h3>
                            <p class="text-[13px] leading-relaxed max-w-[200px]" style="color:var(--muted);">
                                Maintenance personnel inspect the item, update the report, and decide whether it can be resolved or must be replaced.
                            </p>
                        </div>

                        <div class="process-step md:pt-2">
                            <div class="process-num">3</div>
                            <div class="process-marker -mt-8 mb-4">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </div>
                            <h3 class="font-bold text-[15px] mb-2" style="color:var(--ink);">Procurement and Completion</h3>
                            <p class="text-[13px] leading-relaxed max-w-[200px]" style="color:var(--muted);">
                                Replacement requests move to the purchaser, then through approval, funding, receiving, liquidation, and inventory recording.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PROMO + FEATURES -->
    <section id="features" class="features-wrap py-16 md:py-24">
        <div class="pixel-overlay" aria-hidden="true">
            <svg class="pixel-mosaic soft" viewBox="0 0 24 10" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMin slice">
                <rect width="24" height="10" fill="transparent"/>
                <g fill="#e7eeff">
                    <rect x="8" y="0" width="1" height="1"/><rect x="15" y="0" width="1" height="1"/>
                    <rect x="7" y="1" width="1" height="1"/><rect x="16" y="1" width="1" height="1"/>
                    <rect x="6" y="2" width="1" height="1"/><rect x="17" y="2" width="1" height="1"/>
                    <rect x="5" y="3" width="1" height="1"/><rect x="18" y="3" width="1" height="1"/>
                </g>
                <g fill="#dce6ff">
                    <rect x="9" y="1" width="1" height="1"/><rect x="14" y="1" width="1" height="1"/>
                    <rect x="10" y="0" width="1" height="1"/><rect x="13" y="0" width="1" height="1"/>
                    <rect x="8" y="2" width="1" height="1"/><rect x="15" y="2" width="1" height="1"/>
                </g>
                <g fill="#f1f3f8">
                    <rect x="11" y="0" width="1" height="1"/><rect x="12" y="0" width="1" height="1"/>
                    <rect x="4" y="4" width="1" height="1"/><rect x="19" y="4" width="1" height="1"/>
                    <rect x="9" y="3" width="1" height="1"/><rect x="14" y="3" width="1" height="1"/>
                </g>
            </svg>
        </div>
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 relative z-10">

            <div class="promo-banner reveal relative overflow-hidden px-8 py-16 md:py-20 mb-16 text-center text-white">
                <h2 class="text-3xl md:text-[2.6rem] font-extrabold mb-4 leading-tight">
                    Digitalize the reporting and replacement process.
                </h2>
                <p class="text-sm md:text-base max-w-xl mx-auto mb-8 leading-relaxed" style="color:rgba(255,255,255,.85);">
                    The platform records the report from submission up to inspection, approval, receiving, liquidation, and inventory update.
                </p>
                <a href="{{ route('reporter.helpdesk') }}" target="_blank" rel="noopener noreferrer" class="btn-banner inline-flex text-sm no-underline items-center gap-2">
                    <i data-lucide="life-buoy" class="w-4 h-4"></i>
                    Campus Helpdesk
                </a>
            </div>

            <div class="text-center max-w-2xl mx-auto mb-14 reveal">
                <h2 class="text-3xl md:text-[2.4rem] font-extrabold mb-4" style="color:var(--ink);">
                    The system records each step in one platform.
                </h2>
                <p class="text-sm md:text-base leading-relaxed" style="color:var(--muted);">
                    Reports, maintenance updates, urgent purchaser requests, QR edits, and inventory records stay in the same workflow.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mb-12">
                @foreach ([
                    ['icon' => 'package', 'title' => 'Procurement and Inventory Recording', 'copy' => 'If inspection shows the item needs replacement, the request is forwarded to the purchaser and later recorded back into inventory after completion.'],
                    ['icon' => 'smartphone', 'title' => 'Digital Report Submission', 'copy' => 'Teachers and reporters can submit concerns online with details and photos instead of verbally reporting in the maintenance office.'],
                    ['icon' => 'qr-code', 'title' => 'QR Equipment Updating', 'copy' => 'Audio visual equipment and computer sets can be checked through QR so maintenance personnel can edit details and update status directly in the system.'],
                ] as $i => $card)
                    <article class="feature-card p-8 reveal-scale" style="transition-delay: {{ $i * 0.1 }}s;">
                        <i data-lucide="{{ $card['icon'] }}" class="feature-icon w-7 h-7"></i>
                        <h3 class="font-bold text-lg mb-3" style="color:#1e1b4b;">{{ $card['title'] }}</h3>
                        <p class="text-sm leading-relaxed mb-6" style="color:var(--muted);">{{ $card['copy'] }}</p>
                        <button type="button" onclick="openReportModal()" class="feature-link inline-flex items-center gap-1.5">
                            View Flow
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </article>
                @endforeach
            </div>

            <div class="text-center reveal">
                <button type="button" onclick="openFaqChat()" class="btn-platform inline-flex text-sm items-center gap-2 cursor-pointer">
                    <i data-lucide="message-circle-more" class="w-4 h-4"></i>
                    Ask PaAyo
                </button>
            </div>
        </div>
    </section>

    <!-- PRODUCT -->
    <section id="product" class="stories-wrap pt-24 md:pt-32 pb-28 md:pb-36">
        <div class="stories-v-fade" aria-hidden="true"></div>
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12 md:mb-16 reveal">
                <p class="text-xs font-bold tracking-[0.16em] uppercase mb-3" style="color:var(--blue);">
                    The platform
                </p>
                <h2 class="text-3xl md:text-[2.4rem] font-extrabold leading-tight" style="color:var(--ink);">
                    Built around digital reporting
                </h2>
                <p class="text-sm md:text-[15px] leading-relaxed mt-4" style="color:var(--muted);">
                    The report starts online, then continues through inspection, replacement decision, purchaser handling for urgent needs, approval coordination, receiving, liquidation, and inventory updating.
                </p>
            </div>

            <div class="product-stage">
                <div class="product-visual reveal-left">
                    <div class="frame-photo">
                        <div class="shape-blue" aria-hidden="true"></div>
                        <div class="shape-yellow" aria-hidden="true"></div>
                        <img src="{{ asset('image/report_issue_full_bleed.png') }}"
                             alt="Faculty reporting a campus issue from a phone"
                             class="photo">
                    </div>
                </div>

                <div class="product-panel reveal-right" style="transition-delay:.1s;">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="product-icon" aria-hidden="true">
                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0 pt-0.5">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h3 class="font-extrabold text-lg m-0" style="color:var(--ink);">Mobile reporting</h3>
                                <span class="product-kicker">Primary</span>
                            </div>
                            <p class="text-sm leading-relaxed m-0" style="color:var(--muted);">
                                Reporters can file classroom, facility, AV, or computer concerns online so maintenance personnel receive the concern in the system instead of by walk-in or verbal reporting.
                            </p>
                        </div>
                    </div>

                    <div class="product-status mb-6" aria-label="Report status flow">
                        <span class="product-status-step">Submitted</span>
                        <i data-lucide="chevron-right" class="product-status-arrow w-4 h-4"></i>
                        <span class="product-status-step is-next">Inspected</span>
                        <i data-lucide="chevron-right" class="product-status-arrow w-4 h-4"></i>
                        <span class="product-status-step is-done">Resolved / Replaced</span>
                    </div>

                    <div class="product-split">
                        <article class="product-mini">
                            <div class="product-icon mb-3" aria-hidden="true">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </div>
                            <h4 class="font-extrabold text-[14px] mb-1.5" style="color:var(--ink);">Purchaser workflow</h4>
                            <p class="text-[12px] leading-relaxed m-0" style="color:var(--muted);">
                                Urgent replacement cases can be passed by maintenance to the purchaser, then followed through approval, funding, receiving, and liquidation.
                            </p>
                        </article>
                        <article class="product-mini">
                            <div class="product-icon mb-3" aria-hidden="true">
                                <i data-lucide="qr-code" class="w-4 h-4"></i>
                            </div>
                            <h4 class="font-extrabold text-[14px] mb-1.5" style="color:var(--ink);">QR equipment update</h4>
                            <p class="text-[12px] leading-relaxed m-0" style="color:var(--muted);">
                                Scanning a QR code opens the equipment record so maintenance personnel can edit details and update its current status.
                            </p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MONTHLY REPORTS + ANALYSIS -->
    <section class="month-analysis-section py-16 md:py-20">
        <div class="month-analysis-wave" aria-hidden="true">
            <svg class="edge" viewBox="0 0 1440 400" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="monthPeakFade" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#e6edf8" stop-opacity="0"/>
                        <stop offset="32%" stop-color="#e6edf8" stop-opacity=".4"/>
                        <stop offset="72%" stop-color="#e6edf8" stop-opacity=".18"/>
                        <stop offset="100%" stop-color="#e6edf8" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <path fill="url(#monthPeakFade)"
                      d="M0,168 L560,36 L1440,92 L1440,400 L0,400 Z"/>
            </svg>
        </div>
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 relative z-10">
            <div class="month-campus-row">
                <div class="month-chart-card p-8 reveal-scale">
                    <div class="inline-flex self-start items-center gap-2 mb-4 px-3 py-1.5 rounded-full text-xs font-bold tracking-[0.12em] uppercase" style="background:#eef3ff; color:var(--blue);">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        This Month
                    </div>
                    <div class="flex items-end justify-between gap-4 mb-6">
                        <div>
                            <h3 class="font-extrabold text-xl mb-1" style="color:var(--ink);">Reports in {{ $monthlyReportLabel }}</h3>
                            <p class="text-sm m-0" style="color:var(--muted);">Daily report volume for {{ $daysInMonth }} calendar days in {{ $monthlyReportLabel }}.</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-extrabold leading-none" style="color:var(--blue);">{{ $monthlyReportTotal }}</div>
                            <div class="text-xs font-semibold mt-1" style="color:var(--muted);">total reports</div>
                        </div>
                    </div>

                    <div class="month-bars" aria-label="Daily reports this month">
                        @foreach ($monthlyReportDays as $day)
                            <div class="month-bar {{ $day->isPeak ? 'is-peak' : '' }}" style="height: {{ $day->height }}%;">
                                <span class="month-bar-tip">Day {{ $day->day }} · {{ $day->count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="campus-analysis-card p-6 md:p-7 reveal-scale" style="transition-delay:.1s;">
                    @php
                        $openThisMonth = (int) ($monthlyStatusCounts['Pending'] ?? 0) + (int) ($monthlyStatusCounts['Processing'] ?? 0);
                        $resolvedThisMonth = (int) ($monthlyStatusCounts['Resolved'] ?? 0);
                        $flowTotal = max((int) $monthlyReportTotal, 1);
                        $resolvedShare = min(1, $resolvedThisMonth / $flowTotal);
                        $openShare = min(1, $openThisMonth / $flowTotal);
                        $resolvedPercent = (int) $monthlyReportTotal > 0 ? (int) round(($resolvedThisMonth / (int) $monthlyReportTotal) * 100) : 0;
                        $reportsToday = (int) optional($weeklyReports->last())->count;
                        $gaugeLength = 367.6;
                        $outerLength = 433.5;
                        $resolvedDash = round($gaugeLength * $resolvedShare, 1);
                        $openDash = round($outerLength * $openShare, 1);
                    @endphp
                    <h3 class="font-extrabold text-xl mb-4" style="color:var(--ink);">Campus Report Analysis</h3>
                    <div class="analysis-body">
                        <div class="analysis-metrics">
                            <article class="analysis-metric">
                                <span class="analysis-metric-icon" aria-hidden="true">
                                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                                </span>
                                <div class="text-[13px] font-bold" style="color:var(--ink);">Open reports</div>
                                <div class="text-[28px] font-extrabold leading-none mt-1" style="color:var(--ink);">{{ $openThisMonth }}</div>
                                <div class="text-[12px] mt-1" style="color:var(--muted);">
                                    {{ $reportsToday > 0 ? '+'.$reportsToday.' new today' : 'this month' }}
                                </div>
                            </article>
                            <article class="analysis-metric">
                                <span class="analysis-metric-icon" aria-hidden="true">
                                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                </span>
                                <div class="text-[13px] font-bold" style="color:var(--ink);">Resolved</div>
                                <div class="text-[28px] font-extrabold leading-none mt-1" style="color:var(--ink);">{{ $resolvedThisMonth }}</div>
                                <div class="text-[12px] mt-1" style="color:var(--muted);">this month</div>
                            </article>
                        </div>

                        <article class="analysis-flow">
                            <h4 class="font-extrabold text-[15px] m-0" style="color:var(--ink);">Report Flow</h4>
                            <div class="analysis-gauge" aria-label="Report flow this month">
                                <svg viewBox="0 0 240 188" fill="none" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="reportFlowInner" x1="28" y1="150" x2="212" y2="40" gradientUnits="userSpaceOnUse">
                                            <stop offset="0%" stop-color="#4f9a57"/>
                                            <stop offset="55%" stop-color="#d6b23a"/>
                                            <stop offset="100%" stop-color="#f0c94a"/>
                                        </linearGradient>
                                    </defs>
                                    <path d="M52.5 164 A 78 78 0 1 1 187.5 164"
                                          stroke="#c9d8c4"
                                          stroke-width="22"
                                          stroke-linecap="round"/>
                                    <path d="M52.5 164 A 78 78 0 1 1 187.5 164"
                                          stroke="url(#reportFlowInner)"
                                          stroke-width="22"
                                          stroke-linecap="round"
                                          stroke-dasharray="{{ $resolvedDash }} {{ $gaugeLength }}"
                                          pathLength="{{ $gaugeLength }}"/>
                                    <path d="M40.4 170 A 92 92 0 1 1 199.6 170"
                                          stroke="#cfc6e6"
                                          stroke-width="10"
                                          stroke-linecap="round"
                                          opacity=".45"/>
                                    <path d="M40.4 170 A 92 92 0 1 1 199.6 170"
                                          stroke="#8f7cc0"
                                          stroke-width="10"
                                          stroke-linecap="round"
                                          stroke-dasharray="{{ $openDash }} {{ $outerLength }}"
                                          pathLength="{{ $outerLength }}"/>
                                    <path d="M52.5 164 A 78 78 0 0 0 187.5 164"
                                          stroke="#5ea45f"
                                          stroke-width="16"
                                          stroke-linecap="round"
                                          opacity=".9"/>
                                </svg>
                                <div class="analysis-gauge-center">
                                    <div class="text-[34px] font-extrabold leading-none" style="color:var(--ink);">{{ $monthlyReportTotal }}</div>
                                    <div class="text-[11px] font-semibold mt-1" style="color:#5b6472;">{{ $resolvedPercent }}% resolved</div>
                                </div>
                                <div class="analysis-tooltip">{{ $openThisMonth }}</div>
                            </div>
                            <div class="analysis-legend">
                                <span class="analysis-legend-item">
                                    <span class="analysis-legend-swatch" style="background:#215c3a;"></span>
                                    Open reports
                                </span>
                                <span class="analysis-legend-item">
                                    <span class="analysis-legend-swatch" style="background:#cfe3c8;"></span>
                                    Resolved
                                </span>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 py-10 flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-2">
                <div class="w-12 h-12 overflow-hidden flex-shrink-0">
                    <img src="{{ asset('image/paayo_logo_original.png') }}"
                         alt="PaAyo"
                         class="w-full h-full object-cover"
                         style="object-position: 22% 50%; transform: scale(1.7); transform-origin: 22% 50%;">
                </div>
                <div>
                    <div class="font-extrabold tracking-[0.08em] text-[.95rem]" style="color:var(--ink);">PAAYO</div>
                    <div class="text-[.7rem]" style="color:#6b7280;">Procurement &amp; Maintenance System</div>
                </div>
            </div>

            <p class="text-[.8rem] text-center" style="color:#6b7280;">
                © 1997 STI College Ormoc. All rights reserved.
            </p>

            <p class="text-[.8rem] text-center" style="color:#6b7280;">
                Empowering institutions through technology.
            </p>
        </div>
    </footer>


    <!-- MODALS -->
    @guest
    <div id="loginChooserModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/30 p-4 backdrop-blur-[3px]">
        <div class="modal-animation modal-panel w-full max-w-[400px] rounded-[22px] p-6 md:p-7 relative">
            <button type="button" onclick="closeLoginChooser()" class="modal-close" aria-label="Close">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <h2 class="login-title">Log in</h2>
            <p class="login-subtitle">Select your access level to continue.</p>

            <div class="space-y-2.5">
                <button type="button" onclick="openStaffLoginChooser()" class="login-choice login-choice--primary">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    Log in as Staff
                </button>
                <button type="button" onclick="openPresidentLogin()" class="login-choice login-choice--accent">
                    <i data-lucide="crown" class="w-4 h-4"></i>
                    President log in
                </button>
                <button type="button" onclick="openAdminLogin()" class="login-choice login-choice--ghost">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    Admin log in
                </button>
            </div>
        </div>
    </div>

    <div id="staffChooserModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/30 p-4 backdrop-blur-[3px]">
        <div class="modal-animation modal-panel w-full max-w-[400px] rounded-[22px] p-6 md:p-7 relative">
            <button type="button" onclick="closeStaffChooser()" class="modal-close" aria-label="Close">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <h2 class="login-title">Staff login</h2>
            <p class="login-subtitle">Select your role to continue.</p>

            <div class="grid grid-cols-2 gap-2.5 mb-3">
                <button type="button" onclick="openRoleLogin('Maintenance Personnel', 2)" class="login-role login-role--blue">
                    <span class="login-role-icon"><i data-lucide="wrench" class="w-5 h-5"></i></span>
                    <span class="text-[13px] font-semibold text-center leading-snug">Maintenance<br>Personnel</span>
                </button>
                <button type="button" onclick="openRoleLogin('Purchaser', 3)" class="login-role login-role--yellow">
                    <span class="login-role-icon"><i data-lucide="shopping-cart" class="w-5 h-5"></i></span>
                    <span class="text-[13px] font-semibold">Purchaser</span>
                </button>
                <button type="button" onclick="openRoleLogin('Accounting', 5)" class="login-role login-role--blue">
                    <span class="login-role-icon"><i data-lucide="calculator" class="w-5 h-5"></i></span>
                    <span class="text-[13px] font-semibold">Accounting</span>
                </button>
                <button type="button" onclick="openRoleLogin('Receiving Officer', 6)" class="login-role login-role--yellow">
                    <span class="login-role-icon"><i data-lucide="clipboard-list" class="w-5 h-5"></i></span>
                    <span class="text-[13px] font-semibold text-center leading-snug">Receiving<br>Officer</span>
                </button>
            </div>

            <button type="button" onclick="showModal(loginChooserModal)" class="login-back">Back</button>
        </div>
    </div>

    <div id="roleLoginModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/30 p-4 backdrop-blur-[3px]">
        <div class="modal-animation modal-panel w-full max-w-[400px] rounded-[22px] p-6 md:p-7 relative">
            <button type="button" onclick="closeRoleLogin()" class="modal-close" aria-label="Close">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            <h2 id="roleLoginTitle" class="login-title mb-6 pr-8 leading-snug">
                Maintenance Personnel Login
            </h2>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="login_role_id" id="login_role_id">
                <input type="hidden" name="login_modal" id="login_modal">

                <div class="mb-4">
                    <label class="modal-label">User ID</label>
                    <input type="text" name="user_employee_id" value="{{ old('user_employee_id') }}"
                           placeholder="Enter your user ID" class="modal-input" required>
                </div>

                <div class="mb-2">
                    <label class="modal-label">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" placeholder="Enter your password"
                               class="modal-input pr-12" required>
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 border-0 bg-transparent cursor-pointer"
                            style="color:#8892a4;"
                            onmouseover="this.style.color='#0025cc'" onmouseout="this.style.color='#8892a4'">
                            <i data-lucide="eye" id="eyeIcon" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <p id="loginErrorMessage" class="hidden mt-2 mb-1" style="color:#dc2626; font-size:.8rem; font-weight:500;">
                    Incorrect User ID or Password.
                </p>

                <div class="flex items-center justify-between mt-4 mb-5">
                    <label class="flex items-center gap-2" style="font-size:.8rem; color:#717171; cursor:pointer;">
                        <input type="checkbox" name="remember" style="accent-color:#0025cc;">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="no-underline font-medium" style="font-size:.8rem; color:var(--blue);">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="login-choice login-choice--primary mb-2.5">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    Log in
                </button>

                <a href="{{ route('auth.microsoft.redirect') }}" class="login-microsoft">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" class="w-4 h-4" alt="Microsoft">
                    Log in with Office 365
                </a>

                <button type="button" onclick="goBackRoleLogin()" class="login-back">
                    Back to SSO
                </button>
            </form>
        </div>
    </div>
    @endguest

    <div id="reportModal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70"
         style="background: rgba(11, 18, 32, 0.7);">
        <div class="w-full max-w-[1080px] relative modal-animation">
            @include('reporter.partials.report-form')
        </div>
    </div>


    <script>
        lucide.createIcons();

        (function () {
            const carousel = document.querySelector('.month-carousel');
            if (!carousel) return;

            const currentMonth = carousel.querySelector('.month-item.is-current');
            if (currentMonth) {
                const offset = currentMonth.offsetLeft - (carousel.clientWidth / 2) + (currentMonth.offsetWidth / 2);
                carousel.scrollLeft = Math.max(0, offset);
            }

            let isDragging = false;
            let startX = 0;
            let startScroll = 0;

            carousel.addEventListener('pointerdown', function (event) {
                isDragging = true;
                startX = event.clientX;
                startScroll = carousel.scrollLeft;
                carousel.classList.add('is-dragging');
                carousel.setPointerCapture(event.pointerId);
            });

            carousel.addEventListener('pointermove', function (event) {
                if (!isDragging) return;
                carousel.scrollLeft = startScroll - (event.clientX - startX);
            });

            const stopDrag = function () {
                isDragging = false;
                carousel.classList.remove('is-dragging');
            };

            carousel.addEventListener('pointerup', stopDrag);
            carousel.addEventListener('pointercancel', stopDrag);

            carousel.addEventListener('wheel', function (event) {
                if (Math.abs(event.deltaY) < Math.abs(event.deltaX)) return;
                event.preventDefault();
                carousel.scrollLeft += event.deltaY;
            }, { passive: false });
        })();

        /* ── Scroll-progress bar ── */
        const scrollProgress = document.getElementById('scroll-progress');
        const updateProgress = () => {
            if (!scrollProgress) return;
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            scrollProgress.style.width = (docHeight > 0 ? (scrollTop / docHeight) * 100 : 0) + '%';
        };
        window.addEventListener('scroll', updateProgress, { passive: true });

        /* ── Navbar scroll style ── */
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('nav-scrolled', window.scrollY > 20);
        }, { passive: true });

        /* ── Universal reveal observer ── */
        const allReveal = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    revealObserver.unobserve(e.target);
                }
            });
        }, { threshold: 0.10, rootMargin: '0px 0px -40px 0px' });
        allReveal.forEach((el) => revealObserver.observe(el));

        /* ── Auto-stagger children of [data-stagger] ── */
        document.querySelectorAll('[data-stagger]').forEach((parent) => {
            const base = parseFloat(parent.dataset.stagger) || 0.08;
            Array.from(parent.children).forEach((child, i) => {
                child.style.setProperty('--delay', (i * base) + 's');
                child.classList.add('stagger');
            });
        });

        /* ── Animated number counters ── */
        const counters = document.querySelectorAll('[data-count]');
        const countObserver = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (!e.isIntersecting) return;
                countObserver.unobserve(e.target);
                const target = parseFloat(e.target.dataset.count);
                const suffix = e.target.dataset.suffix || '';
                const decimals = (String(target).split('.')[1] || '').length;
                const duration = 1400;
                const start = performance.now();
                const animate = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    e.target.textContent = (target * eased).toFixed(decimals) + suffix;
                    if (progress < 1) requestAnimationFrame(animate);
                    else {
                        e.target.textContent = target + suffix;
                        e.target.classList.add('counter-pop');
                    }
                };
                requestAnimationFrame(animate);
            });
        }, { threshold: 0.5 });
        counters.forEach((c) => countObserver.observe(c));

        /* ── Hero floating particles ── */
        (function () {
            const hero = document.getElementById('top');
            if (!hero) return;
            const colors = ['#c8d9ff','#dde8ff','#fff200','#e0eaff'];
            for (let i = 0; i < 18; i++) {
                const p = document.createElement('span');
                p.className = 'hero-particle';
                const size = 4 + Math.random() * 8;
                p.style.cssText = [
                    'width:' + size + 'px',
                    'height:' + size + 'px',
                    'left:' + (5 + Math.random() * 55) + '%',
                    'top:' + (20 + Math.random() * 65) + '%',
                    'background:' + colors[Math.floor(Math.random() * colors.length)],
                    'animation-duration:' + (4 + Math.random() * 7) + 's',
                    'animation-delay:' + (Math.random() * 6) + 's',
                ].join(';');
                hero.appendChild(p);
            }
        })();

        /* ── Subtle parallax on hero collage (desktop only) ── */
        (function () {
            if (window.innerWidth < 768) return;
            const floaters = document.querySelectorAll('.float-a, .float-b, .float-c');
            window.addEventListener('scroll', () => {
                const y = window.scrollY;
                floaters.forEach((el, i) => {
                    const depth = (i % 3 === 0) ? 0.06 : (i % 3 === 1 ? 0.04 : 0.08);
                    el.style.transform = 'translateY(' + (-y * depth) + 'px)';
                });
            }, { passive: true });
        })();

        /* ── Tilt on feature cards (desktop only) ── */
        if (window.matchMedia('(pointer:fine)').matches) {
            document.querySelectorAll('.feature-card, .stat-bento, .bento-feature').forEach((card) => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const cx = rect.left + rect.width / 2;
                    const cy = rect.top + rect.height / 2;
                    const rx = ((e.clientY - cy) / rect.height) * 8;
                    const ry = -((e.clientX - cx) / rect.width) * 8;
                    card.style.transform = 'translateY(-6px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg)';
                    card.style.transition = 'transform .05s ease';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = '';
                    card.style.transition = 'transform .55s cubic-bezier(.22,1,.36,1), box-shadow .55s ease';
                });
            });
        }

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

        function paayoSwal(options) {
            const tone = options.tone || 'success';
            const swalIcon = tone === 'warning' ? 'warning' : (tone === 'error' ? 'error' : (tone === 'info' ? 'info' : 'success'));
            const icons = {
                success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
                info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.2 4.4-5 8-5s6.5 1.8 8 5"/></svg>',
                warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><circle cx="12" cy="16" r=".8" fill="currentColor"/></svg>',
                error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>',
            };

            return Swal.fire({
                icon: swalIcon,
                iconHtml: icons[tone] || icons.success,
                title: options.title || '',
                text: options.text || '',
                showConfirmButton: options.showConfirmButton !== false,
                confirmButtonText: options.confirmText || 'OK',
                buttonsStyling: false,
                backdrop: 'rgba(11, 18, 32, 0.7)',
                width: 400,
                timer: options.timer,
                timerProgressBar: !!options.timer,
                showClass: { popup: 'swal2-show paayo-swal-in' },
                customClass: {
                    popup: 'paayo-swal',
                    title: 'paayo-swal-heading',
                    htmlContainer: 'paayo-swal-text',
                    confirmButton: 'paayo-swal-btn',
                    icon: 'paayo-icon-' + tone,
                    timerProgressBar: 'modern-success-progress',
                },
            });
        }

        const reporterRegisterForm = document.getElementById('reporterRegisterForm');
        if (reporterRegisterForm) {
            const submitBtn = document.getElementById('reporterRegisterSubmit');
            const submitLabel = document.getElementById('reporterRegisterSubmitLabel');
            const spinner = document.getElementById('reporterRegisterSpinner');
            const emailInput = document.getElementById('reporterRegisterEmail');
            const lockMsg = document.getElementById('reporterRegisterLockMsg');
            const lockStoreKey = 'paayo-reg-lock';
            let lockTimer = null;

            const formatWait = (seconds) => {
                const total = Math.max(0, seconds);
                const m = Math.floor(total / 60);
                const s = total % 60;
                return m + ':' + String(s).padStart(2, '0');
            };

            const readLock = () => {
                try {
                    return JSON.parse(sessionStorage.getItem(lockStoreKey) || 'null');
                } catch (err) {
                    return null;
                }
            };

            const writeLock = (email, retryAfter) => {
                sessionStorage.setItem(lockStoreKey, JSON.stringify({
                    email: (email || '').trim().toLowerCase(),
                    until: Date.now() + (Math.max(1, retryAfter) * 1000),
                }));
            };

            const clearLock = () => sessionStorage.removeItem(lockStoreKey);

            const setLoading = (loading) => {
                submitBtn.disabled = loading;
                spinner.classList.toggle('hidden', !loading);
                submitLabel.textContent = loading ? 'Sending' : 'Submit';
            };

            const applyLockUi = () => {
                const lock = readLock();
                const email = (emailInput.value || '').trim().toLowerCase();
                const remaining = lock ? Math.ceil((lock.until - Date.now()) / 1000) : 0;
                const active = lock && lock.email === email && remaining > 0;

                if (lockTimer) {
                    clearInterval(lockTimer);
                    lockTimer = null;
                }

                if (!active) {
                    if (lock && remaining <= 0) clearLock();
                    submitBtn.classList.remove('hidden');
                    lockMsg.classList.add('hidden');
                    lockMsg.textContent = '';
                    return false;
                }

                submitBtn.classList.add('hidden');
                lockMsg.classList.remove('hidden');
                const tick = () => {
                    const current = readLock();
                    if (!current) {
                        applyLockUi();
                        return;
                    }
                    const left = Math.ceil((current.until - Date.now()) / 1000);
                    if (left <= 0) {
                        clearLock();
                        applyLockUi();
                        return;
                    }
                    lockMsg.textContent = 'Too many attempts for this email. Submit is paused. Try again in ' + formatWait(left) + '.';
                };
                tick();
                lockTimer = setInterval(tick, 1000);
                return true;
            };

            emailInput.addEventListener('input', applyLockUi);
            applyLockUi();

            reporterRegisterForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (applyLockUi()) return;

                setLoading(true);
                try {
                    const response = await fetch(reporterRegisterForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(reporterRegisterForm),
                    });
                    const data = await response.json().catch(() => ({}));
                    const email = (emailInput.value || '').trim().toLowerCase();

                    if (data.locked) {
                        writeLock(email, data.retry_after || 15 * 60);
                        applyLockUi();
                    }

                    if (response.status === 429) {
                        paayoSwal({
                            title: 'Too many attempts',
                            text: data.lock_message || data.message || 'Please wait before trying this email again.',
                            tone: 'warning',
                        });
                        return;
                    }

                    const message = (data.errors && data.errors.email && data.errors.email[0])
                        || data.message
                        || (response.ok
                            ? 'Check your inbox for the registration form.'
                            : 'Please enter a valid email address.');
                    const title = response.ok
                        ? (data.already_registered
                            ? 'Already registered'
                            : (data.pending_approval
                                ? 'Waiting for approval'
                                : (data.register_url ? 'Continue registration' : 'Check your email')))
                        : 'Check your details';
                    paayoSwal({
                        title: title,
                        text: message,
                        confirmText: data.register_url
                            ? 'Open form'
                            : (response.ok && data.already_registered ? 'Make Report' : 'OK'),
                        tone: response.ok
                            ? ((data.already_registered || data.pending_approval) ? 'info' : 'success')
                            : 'error',
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        if (data.register_url) {
                            window.location.href = data.register_url;
                            return;
                        }
                        if (data.already_registered) {
                            openReportModal();
                        }
                    });
                    if (response.ok && !data.already_registered && !data.pending_approval && !data.locked) {
                        reporterRegisterForm.reset();
                        applyLockUi();
                    }
                } catch (err) {
                    paayoSwal({
                        title: 'Could not send',
                        text: 'We could not send the email right now. Please try again.',
                        tone: 'error',
                    });
                } finally {
                    setLoading(false);
                }
            });
        }
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        document.querySelectorAll('a[href^="#"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                const id = link.getAttribute('href');
                if (!id || id === '#') return;
                const target = document.querySelector(id);
                if (!target) return;
                event.preventDefault();
                target.scrollIntoView({
                    behavior: prefersReducedMotion ? 'auto' : 'smooth',
                    block: 'start',
                });
                history.pushState(null, '', id);
            });
        });

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
        paayoSwal({
            title: @json(session('success_title', 'Report submitted')),
            text: @json(session('success')),
            confirmText: 'Make Report',
            tone: 'success',
            showConfirmButton: {{ session('open_report') ? 'true' : 'false' }},
            @unless(session('open_report'))
            timer: 3000,
            @endunless
        }).then(function (result) {
            @if(session('open_report'))
            if (result.isConfirmed) {
                openReportModal();
            }
            @endif
        });
    });
    </script>
    @endif


    @include('landing.partials.faq-chatbot', ['showChatFab' => false])

</body>
</html>
