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

        #top, #product, #process, #features {
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
            transition: transform .25s, box-shadow .25s;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 44px rgba(0, 37, 204, .10);
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
            border-top: 1px solid var(--line);
        }

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
                <a href="#top" class="nav-link active">Home</a>

                <a href="#process" class="nav-link">Process</a>
                <a href="#features" class="nav-link">Features</a>
                <a href="#product" class="nav-link">Product</a>
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
                                    class="btn-blue inline-flex items-center gap-2 px-7 py-3.5 text-sm border-0 cursor-pointer">
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
    <section id="process" class="py-20 md:py-28 overflow-hidden relative">
        <div class="process-orb hidden lg:block" aria-hidden="true"></div>
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <div class="grid lg:grid-cols-12 gap-12 lg:gap-10 items-center">
                <div class="lg:col-span-4 reveal">
                    <p class="text-xs font-bold tracking-[0.16em] uppercase mb-4" style="color:var(--blue);">
                        PaAyo operation across campus
                    </p>
                    <h2 class="text-3xl md:text-[2.4rem] font-extrabold leading-tight mb-5" style="color:var(--ink);">
                        We have best team and best process
                    </h2>
                    <p class="text-sm leading-relaxed mb-8 max-w-sm" style="color:var(--muted);">
                        From discovery to daily maintenance loops, PaAyo guides every role with a clear, shared workflow.
                    </p>
                    @guest
                        <button type="button" onclick="openLoginModal()" class="btn-blue px-8 py-3.5 text-sm">
                            Get Started
                        </button>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-blue inline-flex px-8 py-3.5 text-sm no-underline">
                            Get Started
                        </a>
                    @endguest
                </div>

                <div class="lg:col-span-8 process-track reveal" style="transition-delay:.08s;">
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
                            <h3 class="font-bold text-[15px] mb-2" style="color:var(--ink);">Discovery Call</h3>
                            <p class="text-[13px] leading-relaxed max-w-[200px]" style="color:var(--muted);">
                                Map buildings, rooms, and asset categories with your maintenance and procurement leads.
                            </p>
                        </div>

                        <div class="process-step md:pt-16">
                            <div class="process-num">2</div>
                            <div class="process-marker -mt-8 mb-4">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                            </div>
                            <h3 class="font-bold text-[15px] mb-2" style="color:var(--ink);">System Setup</h3>
                            <p class="text-[13px] leading-relaxed max-w-[200px]" style="color:var(--muted);">
                                Configure roles, QR labels, report flows, and inventory baselines in one shared workspace.
                            </p>
                        </div>

                        <div class="process-step md:pt-2">
                            <div class="process-num">3</div>
                            <div class="process-marker -mt-8 mb-4">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </div>
                            <h3 class="font-bold text-[15px] mb-2" style="color:var(--ink);">Daily Operations</h3>
                            <p class="text-[13px] leading-relaxed max-w-[200px]" style="color:var(--muted);">
                                Track requests, scan assets, and close loops with live campus visibility.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PROMO + FEATURES -->
    <section id="features" class="features-wrap py-16 md:py-24">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">

            <div class="promo-banner reveal relative overflow-hidden px-8 py-16 md:py-20 mb-16 text-center text-white">
                <h2 class="text-3xl md:text-[2.6rem] font-extrabold mb-4 leading-tight">
                    Push your campus ops to next level.
                </h2>
                <p class="text-sm md:text-base max-w-xl mx-auto mb-8 leading-relaxed" style="color:rgba(255,255,255,.85);">
                    Centralize reports, inventory, and QR tracking so maintenance and procurement stay on the same live picture.
                </p>
                @guest
                    <button type="button" onclick="openLoginModal()" class="btn-banner text-sm cursor-pointer">
                        Get Started
                    </button>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-banner inline-flex text-sm no-underline">
                        Get Started
                    </a>
                @endguest
            </div>

            <div class="text-center max-w-2xl mx-auto mb-14 reveal">
                <h2 class="text-3xl md:text-[2.4rem] font-extrabold mb-4" style="color:var(--ink);">
                    We help your campus grow faster.
                </h2>
                <p class="text-sm md:text-base leading-relaxed" style="color:var(--muted);">
                    Inventory, mobile reporting, and QR monitoring — connected in one platform for STI College Ormoc.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 mb-12">
                @foreach ([
                    ['icon' => 'package', 'title' => 'Inventory & Procurement', 'copy' => 'Track equipment, facilities, and AV assets in real time while streamlining purchase requests across every equipment.'],
                    ['icon' => 'smartphone', 'title' => 'Mobile & Web Reporting', 'copy' => 'Let faculty submit damage reports with photos and priority from any device in seconds.'],
                    ['icon' => 'qr-code', 'title' => 'QR Equipment Monitoring', 'copy' => 'Scan qr code to open equipment details & edit, histories, service logs, and maintenance timelines instantly.'],
                ] as $i => $card)
                    <article class="feature-card p-8 reveal" style="transition-delay: {{ $i * 0.08 }}s;">
                        <i data-lucide="{{ $card['icon'] }}" class="feature-icon w-7 h-7"></i>
                        <h3 class="font-bold text-lg mb-3" style="color:#1e1b4b;">{{ $card['title'] }}</h3>
                        <p class="text-sm leading-relaxed mb-6" style="color:var(--muted);">{{ $card['copy'] }}</p>
                        <button type="button" onclick="openReportModal()" class="feature-link inline-flex items-center gap-1.5">
                            Read More
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </article>
                @endforeach
            </div>

            <div class="text-center reveal">
                @guest
                    <button type="button" onclick="openLoginModal()" class="btn-platform text-sm cursor-pointer">
                        More About Platform
                    </button>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-platform inline-flex text-sm no-underline">
                        More About Platform
                    </a>
                @endguest
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
                    Built around mobile reporting
                </h2>
                <p class="text-sm md:text-[15px] leading-relaxed mt-4" style="color:var(--muted);">
                    Faculty report from a phone. Optional web reporting, inventory and procurement control, and QR monitoring keep the rest of campus on the same record.
                </p>
            </div>

            <div class="product-stage">
                <div class="product-visual reveal">
                    <div class="frame-photo">
                        <div class="shape-blue" aria-hidden="true"></div>
                        <div class="shape-yellow" aria-hidden="true"></div>
                        <img src="{{ asset('image/report_issue_full_bleed.png') }}"
                             alt="Faculty reporting a campus issue from a phone"
                             class="photo">
                    </div>
                </div>

                <div class="product-panel reveal" style="transition-delay:.1s;">
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
                                File a classroom or facility issue with photos from your phone. Web reporting is available if you prefer desktop.
                            </p>
                        </div>
                    </div>

                    <div class="product-status mb-5" aria-label="Report status flow">
                        <span class="product-status-step">Pending</span>
                        <i data-lucide="chevron-right" class="product-status-arrow w-4 h-4"></i>
                        <span class="product-status-step is-next">Processing</span>
                        <i data-lucide="chevron-right" class="product-status-arrow w-4 h-4"></i>
                        <span class="product-status-step is-done">Resolved</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <button type="button" onclick="openReportModal()" class="btn-report text-sm cursor-pointer">
                            <span class="btn-report-icon">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            </span>
                            Make Report
                        </button>
                        @guest
                            <button type="button" onclick="openLoginModal()" class="btn-blue px-5 py-2.5 text-sm border-0 cursor-pointer">
                                Sign In
                            </button>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-blue inline-flex px-5 py-2.5 text-sm no-underline">
                                Dashboard
                            </a>
                        @endguest
                    </div>

                    <div class="product-split">
                        <article class="product-mini">
                            <div class="product-icon mb-3" aria-hidden="true">
                                <i data-lucide="package" class="w-4 h-4"></i>
                            </div>
                            <h4 class="font-extrabold text-[14px] mb-1.5" style="color:var(--ink);">Inventory &amp; procurement</h4>
                            <p class="text-[12px] leading-relaxed m-0" style="color:var(--muted);">
                                Track assets and purchasing in one workflow for maintenance and procurement teams.
                            </p>
                        </article>
                        <article class="product-mini">
                            <div class="product-icon mb-3" aria-hidden="true">
                                <i data-lucide="qr-code" class="w-4 h-4"></i>
                            </div>
                            <h4 class="font-extrabold text-[14px] mb-1.5" style="color:var(--ink);">QR monitoring</h4>
                            <p class="text-[12px] leading-relaxed m-0" style="color:var(--muted);">
                                Scan a label for equipment history, service logs, and live status.
                            </p>
                        </article>
                    </div>
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
                    <button type="button" onclick="openReportModal()" class="btn-report text-sm cursor-pointer">
                        <span class="btn-report-icon">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        </span>
                        Make Report
                    </button>
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
         class="hidden fixed inset-0 z-50 flex items-center justify-center px-4 py-10 bg-[#0b1220]/70"
         style="background: rgba(11, 18, 32, 0.7);">
        <div class="w-full max-w-[1080px] relative modal-animation">
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
                            : (data.register_url ? 'Continue registration' : 'Check your email'))
                        : 'Check your details';
                    paayoSwal({
                        title: title,
                        text: message,
                        confirmText: data.register_url
                            ? 'Open form'
                            : (response.ok && data.already_registered ? 'Make Report' : 'OK'),
                        tone: response.ok
                            ? (data.already_registered ? 'info' : 'success')
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
                    if (response.ok && !data.already_registered && !data.locked) {
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

</body>
</html>
