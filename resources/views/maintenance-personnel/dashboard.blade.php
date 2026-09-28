@extends ("layouts.maintenance-layout")

@section ("title", "Maintenance Dashboard")

@section ("content")
    {{-- ══════════════════════════════════════════════════════════════
     PaAyo · Maintenance Personnel Dashboard
     resources/views/maintenance/dashboard.blade.php
══════════════════════════════════════════════════════════════ --}}

    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    />
    <script src="https://unpkg.com/lucide@latest"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
                *,
                *::before,
                *::after {
                    box-sizing: border-box;
                }

                body {
                    font-family: "Inter", sans-serif;
                    background: #f1f5f9;
                    margin: 0;
                    overflow-x: hidden;
                }

                body.mp-layout main {
                    -webkit-overflow-scrolling: touch;
                    overscroll-behavior-y: contain;
                }

                /* ── MAIN LAYOUT ─────────────────────────────────────── */

                /* ── STAT CARD ───────────────────────────────────────── */
                /* ===================================================== */
                /* COMPACT MODERN STAT CARD */
                /* ===================================================== */

                .stat-card {
                    min-width: 0;

                    height: 76px;

                    display: flex;

                    align-items: center;

                    gap: 12px;

                    padding: 12px 14px;

                    background: white;

                    border: 1px solid #e5e7eb;

                    border-radius: 18px;

                    box-shadow:
                        0 1px 2px rgba(15, 23, 42, 0.03);

                    transition:
                        border-color 0.18s ease,
                        box-shadow 0.18s ease,
                        transform 0.18s ease;
                }


                .stat-card:hover {
                    transform: translateY(-1px);

                    border-color: #d1d5db;

                    box-shadow:
                        0 6px 18px rgba(15, 23, 42, 0.06);
                }


                /* ===================================================== */
                /* STAT ICON */
                /* ===================================================== */

                .stat-card-icon {
                    width: 42px;

                    height: 42px;

                    flex: 0 0 42px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    border-radius: 14px;
                }


                /* ===================================================== */
                /* STAT INFORMATION */
                /* ===================================================== */

                .stat-card-content {
                    min-width: 0;

                    flex: 1;
                }


                .stat-card-meta {
                    margin-bottom: 2px;

                    color: #94a3b8;

                    font-size: 11px;

                    font-weight: 500;

                    line-height: 1.2;
                }


                .stat-card-title {
                    overflow: hidden;

                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 14px;

                    font-weight: 600;

                    line-height: 1.3;

                    text-overflow: ellipsis;

                    white-space: nowrap;
                }


                /* ===================================================== */
                /* OPTIONAL THREE DOT MENU */
                /* ===================================================== */

                .stat-card-menu {
                    width: 32px;

                    height: 32px;

                    flex: 0 0 32px;

                    display: inline-flex;

                    align-items: center;

                    justify-content: center;

                    border: 0;

                    border-radius: 9px;

                    background: transparent;

                    color: #94a3b8;

                    cursor: pointer;

                    transition:
                        background 0.18s ease,
                        color 0.18s ease;
                }


                .stat-card-menu:hover {
                    background: #f8fafc;

                    color: #334155;
                }

                /* ── PIPELINE (> > > >) ──────────────────────────────── */
                .pipeline-track {
                    display: flex;
                    align-items: stretch;
                    gap: 0;
                }
                .pipeline-step {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 7px 16px 7px 20px;
                    font-size: 11.5px;
                    font-weight: 700;
                    clip-path: polygon(
                        0 0,
                        calc(100% - 10px) 0,
                        100% 50%,
                        calc(100% - 10px) 100%,
                        0 100%,
                        10px 50%
                    );
                    background: #e2e8f0;
                    color: #94a3b8;
                    letter-spacing: 0.03em;
                    min-width: 110px;
                    transition: all 0.2s;
                    position: relative;
                    cursor: default;
                }
                .pipeline-step:first-child {
                    clip-path: polygon(
                        0 0,
                        calc(100% - 10px) 0,
                        100% 50%,
                        calc(100% - 10px) 100%,
                        0 100%
                    );
                    padding-left: 14px;
                }
                .pipeline-step.done {
                    background: #dbeafe;
                    color: #1d4ed8;
                }
                .pipeline-step.active {
                    background: #0037c7;
                    color: #fff;
                }
                .pipeline-step.urgent-active {
                    background: #dc2626;
                    color: #fff;
                }

                /* ── ROOM CARD ───────────────────────────────────────── */
                .room-card {
                    background: #fff;
                    border: 1.5px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 14px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    position: relative;
                    overflow: hidden;
                }
                .room-card:hover {
                    border-color: #0037c7;
                    box-shadow: 0 4px 16px rgba(0, 55, 199, 0.12);
                    transform: translateY(-2px);
                }
                .room-card.available::before {
                    content: "";
                    position: absolute;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    width: 4px;
                    background: #22c55e;
                    border-radius: 4px 0 0 4px;
                }
                .room-card.needs-repair::before {
                    content: "";
                    position: absolute;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    width: 4px;
                    background: #f59e0b;
                    border-radius: 4px 0 0 4px;
                }
                .room-card.critical::before {
                    content: "";
                    position: absolute;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    width: 4px;
                    background: #dc2626;
                    border-radius: 4px 0 0 4px;
                }

                /* ── FLOOR TAB ───────────────────────────────────────── */
                .floor-tab {
                    padding: 8px 20px;
                    border-radius: 10px;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.18s ease;
                    border: 1.5px solid #e2e8f0;
                    background: #fff;
                    color: #64748b;
                }
                .floor-tab.active {
                    background: #0037c7;
                    border-color: #0037c7;
                    color: #fff;
                }

                /* ── URGENT REPORT CARD ──────────────────────────────── */
                /* ===================================================== */
                /* URGENT REPORT CARDS */
                /* ===================================================== */

                .urgent-card {
                    background: #fff;

                    border: 1px solid #e2e8f0;

                    border-radius: 18px;

                    padding: 20px 22px;

                    flex: 0 0 calc((100% - 16px) / 2);

                    min-width: 0;

                    transition: box-shadow 0.2s;
                }


                /* ===================================================== */
                /* VERY LARGE DESKTOP */
                /* SHOW 3 CARDS */
                /* ===================================================== */

                @media (min-width: 1600px) {

                    .urgent-card {
                        flex-basis: calc((100% - 32px) / 3);
                    }

                }


                /* ===================================================== */
                /* MOBILE */
                /* SHOW 1 CARD */
                /* ===================================================== */

                @media (max-width: 640px) {

                    .urgent-card {
                        flex-basis: 100%;
                    }

                }
                .urgent-card:hover {
                    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
                }

                /* ── SCROLL HIDE ─────────────────────────────────────── */
                .scroll-hide::-webkit-scrollbar {
                    display: none;
                }
                .scroll-hide {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }

                /* ── BADGE ───────────────────────────────────────────── */
                .badge {
                    display: inline-flex;
                    align-items: center;
                    padding: 3px 10px;
                    border-radius: 999px;
                    font-size: 11px;
                    font-weight: 700;
                }

                /* ── ACTIVITY ITEM ───────────────────────────────────── */
                .activity-dot {
                    width: 34px;
                    height: 34px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }

                /* ── DROPDOWN ACCORDION ──────────────────────────────── */
                .accordion-content {
                    max-height: 0;
                    overflow: hidden;
                    transition: max-height 0.25s ease;
                }
                .accordion-content.open {
                    max-height: 300px;
                }
                .chevron-icon {
                    transition: transform 0.25s ease;
                }
                .chevron-icon.rotated {
                    transform: rotate(180deg);
                }

                /* ===================================================== */
                /* MAINTENANCE DASHBOARD PAGE */
                /* ===================================================== */

                .maintenance-dashboard {
                    width: 100%;

                    display: flex;

                    flex-direction: column;

                    gap: 24px;
                }


                /* ===================================================== */
                /* MAIN DASHBOARD GRID */
                /* LEFT CONTENT + RIGHT SIDEBAR */
                /* ===================================================== */

                .maintenance-dashboard-grid {
                    display: grid;

                    grid-template-columns:
                        minmax(0, 1fr)
                        340px;

                    gap: 24px;

                    /* Stretch both columns so bottoms always align */
                    align-items: stretch;
                }


                /* ===================================================== */
                /* LEFT MAIN CONTENT */
                /* ===================================================== */

                .maintenance-dashboard-main {
                    min-width: 0;

                    display: flex;

                    flex-direction: column;

                    gap: 24px;

                    height: 100%;
                }


                /* ===================================================== */
                /* RIGHT SIDEBAR */
                /* ===================================================== */

                .maintenance-dashboard-sidebar {
                    min-width: 0;

                    display: flex;

                    flex-direction: column;

                    gap: 20px;

                    height: 100%;
                }


                /* Last left card (workload) fills leftover height */
                .maintenance-dashboard-main > .dashboard-analytics-card:last-child {
                    flex: 1 1 auto;

                    display: flex;

                    flex-direction: column;

                    min-height: 0;
                }

                .maintenance-dashboard-main > .dashboard-analytics-card:last-child .dashboard-report-activity-chart {
                    flex: 1 1 auto;

                    min-height: 320px;

                    height: auto;
                }

                /* Activity card fills leftover sidebar height */
                .maintenance-dashboard-sidebar > .activity-sidebar-card {
                    flex: 1 1 auto;

                    display: flex;

                    flex-direction: column;

                    min-height: 0;
                }

                .maintenance-dashboard-sidebar .activity-list-panel {
                    flex: 1 1 auto;
                }

                .maintenance-dashboard-sidebar .activity-sidebar-footer {
                    margin-top: auto;
                }


                /* ===================================================== */
                /* SIDEBAR CARD */
                /* ===================================================== */

                .dashboard-side-card {
                    background: white;

                    border: 1px solid #e2e8f0;

                    border-radius: 20px;

                    overflow: hidden;
                }


                /* ===================================================== */
                /* STICKY SIDEBAR */
                /* ===================================================== */

                @media (min-width: 1280px) {

                    .maintenance-dashboard-sidebar {
                        position: static;
                    }

                }


                /* ===================================================== */
                /* TABLET */
                /* ===================================================== */

                @media (max-width: 1279px) {

                    .maintenance-dashboard-grid {
                        grid-template-columns: 1fr;
                    }


                    .maintenance-dashboard-sidebar {
                        position: static;
                    }

                }


                /* ===================================================== */
                /* MOBILE */
                /* ===================================================== */

                @media (max-width: 640px) {

                    .maintenance-dashboard-grid {
                        gap: 18px;
                    }


                    .maintenance-dashboard-main {
                        gap: 18px;
                    }

                }



                /* ===================================================== */
                /* DASHBOARD UTILITY TOOLBAR */
                /* ===================================================== */

                .dashboard-toolbar {
                    width: 100%;

                    min-width: 0;

                    display: flex;

                    align-items: center;

                    gap: 10px;
                }


                /* ===================================================== */
                /* SEARCH */
                /* FLEX: 1 MAKES SEARCH CONSUME ALL REMAINING SPACE */
                /* REMOVE max-width COMPLETELY */
                /* ===================================================== */




                /* ===================================================== */
                /* QUICK ACTIONS CONTAINER */
                /* STAYS ON THE RIGHT */
                /* ===================================================== */

                .dashboard-toolbar-actions {
                    display: flex;

                    align-items: center;

                    gap: 10px;

                    flex: 0 0 auto;

                    white-space: nowrap;
                }


                /* ===================================================== */
                /* QUICK ACTION BUTTON */
                /* ===================================================== */

                .dashboard-quick-action {
                    position: relative;

                    min-height: 42px;

                    display: inline-flex;
                    align-items: center;
                    justify-content: center;

                    gap: 4px;

                    padding: 4px;

                    border: 1px solid #e5e7eb;
                    border-radius: 12px;

                    background: white;

                    color: #374151;

                    font-family: "Inter", sans-serif;
                    font-size: 12px;
                    font-weight: 600;

                    text-decoration: none;
                    white-space: nowrap;

                    box-shadow:
                        0 1px 2px rgba(15, 23, 42, 0.03),
                        0 1px 3px rgba(15, 23, 42, 0.04);

                    transition:
                        transform 0.18s ease,
                        background 0.18s ease,
                        border-color 0.18s ease,
                        color 0.18s ease,
                        box-shadow 0.18s ease;
                }


                /* ===================================================== */
                /* QUICK ACTION ICON */
                /* ===================================================== */

                .dashboard-quick-action-icon {
                    width: 16px;
                    height: 16px;

                    display: inline-flex;
                    align-items: center;
                    justify-content: center;

                    flex-shrink: 0;

                    border-radius: 8px;

                    background: #f8fafc;

                    color: #64748b;

                    line-height: 0;

                    transition:
                        background 0.18s ease,
                        color 0.18s ease;
                }


                .dashboard-quick-action-icon svg,
                .dashboard-quick-action-icon i {
                    display: block;
                    width: 16px;
                    height: 16px;
                    stroke-width: 1.8;
                }


                /* ===================================================== */
                /* QUICK ACTION HOVER */
                /* ===================================================== */

                .dashboard-quick-action:hover {
                    transform: translateY(-1px);

                    background: white;

                    border-color: #d1d5db;

                    color: #111827;

                    box-shadow:
                        0 4px 8px rgba(15, 23, 42, 0.05),
                        0 2px 4px rgba(15, 23, 42, 0.04);
                }


                .dashboard-quick-action:hover .dashboard-quick-action-icon {
                    background: #fef9c3;

                    color: #ca8a04;
                }


                /* ===================================================== */
                /* QUICK ACTION ACTIVE */
                /* ===================================================== */

                .dashboard-quick-action:active {
                    transform: translateY(0);

                    box-shadow:
                        0 1px 2px rgba(15, 23, 42, 0.05);
                }


                /* ===================================================== */
                /* QUICK ACTION FOCUS */
                /* ===================================================== */

                .dashboard-quick-action:focus-visible {
                    outline: none;

                    border-color: #eab308;

                    box-shadow:
                        0 0 0 3px rgba(234, 179, 8, 0.12);
                }


                /* ===================================================== */
                /* RESPONSIVE */
                /* ===================================================== */

                @media (max-width: 768px) {

                    .dashboard-toolbar-actions {
                        width: 100%;

                        overflow-x: auto;

                        padding-bottom: 2px;

                        scrollbar-width: none;
                    }


                    .dashboard-toolbar-actions::-webkit-scrollbar {
                        display: none;
                    }


                    .dashboard-quick-action {
                        flex-shrink: 0;
                    }

                }

                /* ===================================================== */
                /* SIDEBAR QUICK ACTIONS — horizontal drag carousel */
                /* Equipment / Schedule / Borrowing / … */
                /* ===================================================== */

                .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions {
                    width: 100%;
                    margin-left: 0;
                    min-width: 0;
                    max-width: 100%;
                    display: flex;
                    flex-wrap: nowrap;
                    align-items: center;
                    gap: 8px;
                    overflow-x: auto;
                    overflow-y: hidden;
                    overscroll-behavior: contain;
                    white-space: nowrap;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                    cursor: grab;
                    user-select: none;
                    -webkit-user-select: none;
                    touch-action: pan-y;
                }

                .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions::-webkit-scrollbar {
                    display: none;
                }

                .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions.is-dragging {
                    cursor: grabbing;
                }

                .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action {
                    flex: 0 0 auto;
                }

                .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions.has-dragged .dashboard-quick-action {
                    pointer-events: none;
                }

                @media (max-width: 768px) {

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions {
                        display: flex;
                        flex-wrap: nowrap;
                        overflow-x: auto;
                        white-space: nowrap;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action {
                        width: auto;
                        min-width: 0;
                        flex: 0 0 auto;
                        padding: 8px 10px;
                        gap: 6px;
                        flex-direction: row;
                        align-items: center;
                        justify-content: center;
                        white-space: nowrap;
                        text-align: center;
                        font-size: 11px;
                        line-height: 1.2;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action > span:not(.dashboard-quick-action-icon) {
                        display: inline;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action-icon {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 28px;
                        height: 28px;
                        flex-shrink: 0;
                        line-height: 0;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action-icon svg,
                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action-icon i {
                        display: block;
                        width: 16px;
                        height: 16px;
                        margin: 0;
                    }

                }

                @media (max-width: 380px) {

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action {
                        flex-direction: row;
                        justify-content: flex-start;
                        padding: 8px 10px;
                        font-size: 12px;
                    }

                }

                .button {
                    --h-button: 48px;
                    --w-button: 102px;
                    --round: 0.75rem;

                    cursor: pointer;
                    position: relative;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;

                    transition: all 0.25s ease;

                    /* CHANGE GRADIENT HERE */
                    background: linear-gradient(
                        to top,
                        #60a5fa 0%,
                        #2563eb 35%,
                        #0622D6 70%,
                        rgba(0,55,199,0.85) 100%
                    );

                    border-radius: var(--round);
                    border: none;
                    outline: none;
                    padding: 10px 12px;
                }
                    linear-gradient(0deg, #7a5af8, #7a5af8);
                .button::before,
                .button::after {
                    content: "";
                    position: absolute;
                    inset: var(--space);
                    transition: all 0.5s ease-in-out;
                    border-radius: calc(var(--round) - var(--space));
                    z-index: 0;
                }

                .button::before {
                    --space: 1px;

                    background: linear-gradient(
                        177.95deg,
                        rgba(255, 255, 255, 0.19) 0%,
                        rgba(255, 255, 255, 0) 100%
                    );
                }

                .button::after {
                    --space: 2px;

                    /* CHANGE: WHITE AT BOTTOM TO BLUE AT TOP */
                    background: linear-gradient(
                        to top,
                        white 0%,
                        #bfdbfe 15%,
                        #3b82f6 45%,
                        #1d4ed8 100%
                    );
                }
                .button:active {
                transform: scale(0.95);
                }

                .fold {
                z-index: 1;
                position: absolute;
                top: 0;
                right: 0;
                height: 1rem;
                width: 1rem;
                display: inline-block;
                transition: all 0.5s ease-in-out;
                background: radial-gradient(
                    100% 75% at 55%,
                    rgba(223, 113, 255, 0.8) 0%,
                    rgba(223, 113, 255, 0) 100%
                );
                box-shadow: 0 0 3px black;
                border-bottom-left-radius: 0.5rem;
                border-top-right-radius: var(--round);
                }
                .fold::after {
                content: "";
                position: absolute;
                top: 0;
                right: 0;
                width: 150%;
                height: 150%;
                transform: rotate(45deg) translateX(0%) translateY(-18px);
                background-color: #e8e8e8;
                pointer-events: none;
                }
                .button:hover .fold {
                margin-top: -1rem;
                margin-right: -1rem;
                }

                .points_wrapper {
                overflow: hidden;
                width: 100%;
                height: 100%;
                pointer-events: none;
                position: absolute;
                z-index: 1;
                }

                .points_wrapper .point {
                bottom: -10px;
                position: absolute;
                animation: floating-points infinite ease-in-out;
                pointer-events: none;
                width: 2px;
                height: 2px;
                background-color: #fff;
                border-radius: 9999px;
                }
                @keyframes floating-points {
                0% {
                    transform: translateY(0);
                }
                85% {
                    opacity: 0;
                }
                100% {
                    transform: translateY(-55px);
                    opacity: 0;
                }
                }
                .points_wrapper .point:nth-child(1) {
                left: 10%;
                opacity: 1;
                animation-duration: 2.35s;
                animation-delay: 0.2s;
                }
                .points_wrapper .point:nth-child(2) {
                left: 30%;
                opacity: 0.7;
                animation-duration: 2.5s;
                animation-delay: 0.5s;
                }
                .points_wrapper .point:nth-child(3) {
                left: 25%;
                opacity: 0.8;
                animation-duration: 2.2s;
                animation-delay: 0.1s;
                }
                .points_wrapper .point:nth-child(4) {
                left: 44%;
                opacity: 0.6;
                animation-duration: 2.05s;
                }
                .points_wrapper .point:nth-child(5) {
                left: 50%;
                opacity: 1;
                animation-duration: 1.9s;
                }
                .points_wrapper .point:nth-child(6) {
                left: 75%;
                opacity: 0.5;
                animation-duration: 1.5s;
                animation-delay: 1.5s;
                }
                .points_wrapper .point:nth-child(7) {
                left: 88%;
                opacity: 0.9;
                animation-duration: 2.2s;
                animation-delay: 0.2s;
                }
                .points_wrapper .point:nth-child(8) {
                left: 58%;
                opacity: 0.8;
                animation-duration: 2.25s;
                animation-delay: 0.2s;
                }
                .points_wrapper .point:nth-child(9) {
                left: 98%;
                opacity: 0.6;
                animation-duration: 2.6s;
                animation-delay: 0.1s;
                }
                .points_wrapper .point:nth-child(10) {
                left: 65%;
                opacity: 1;
                animation-duration: 2.5s;
                animation-delay: 0.2s;
                }

                .inner {
                z-index: 2;
                gap: 6px;
                position: relative;
                width: 100%;
                color: white;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 500;
                line-height: 1.5;
                transition: color 0.2s ease-in-out;
                }

                .inner svg.icon {
                width: 18px;
                height: 18px;
                transition: fill 0.1s linear;
                }

                .button:focus svg.icon {
                fill: white;
                }
                .button:hover svg.icon {
                fill: transparent;
                animation:
                    dasharray 1s linear forwards,
                    filled 0.1s linear forwards 0.95s;
                }
                @keyframes dasharray {
                from {
                    stroke-dasharray: 0 0 0 0;
                }
                to {
                    stroke-dasharray: 68 68 0 0;
                }
                }
                @keyframes filled {
                to {
                    fill: white;
                }
                }


                /* ===================================================== */
                /* QUICK ACTION BUTTON */
                /* ===================================================== */

                .dashboard-quick-action {
                    height: 42px;

                    display: inline-flex;

                    align-items: center;

                    justify-content: center;

                    gap: 7px;

                    padding: 0 10px;

                    border: 1px solid #e2e8f0;

                    border-radius: 10px;

                    background: white;

                    color: #334155;

                    font-family: "Inter", sans-serif;

                    font-size: 12px;

                    font-weight: 600;

                    text-decoration: none;

                    white-space: nowrap;

                    transition:
                        background 0.18s ease,
                        border-color 0.18s ease,
                        color 0.18s ease,
                        box-shadow 0.18s ease;
                }


                .dashboard-quick-action:hover {
                    background: #f8fafc;

                    border-color: #cbd5e1;

                    color: #0f172a;

                    box-shadow:
                        0 3px 10px
                        rgba(
                            15,
                            23,
                            42,
                            0.05
                        );
                }


                /* ===================================================== */
                /* SMALL DESKTOP */
                /* KEEP EVERYTHING ON ONE ROW */
                /* HIDE ACTION TEXT BEFORE BREAKING THE TOOLBAR */
                /* ===================================================== */

                @media (max-width: 1200px) {

                    .dashboard-quick-action span {
                        display: none;
                    }


                    .dashboard-quick-action {
                        width: 42px;

                        padding: 0;
                    }

                    /* Keep sidebar Equipment / Schedule / Borrowing labels */
                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action {
                        width: auto;
                        padding: 4px 10px;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action > span:not(.dashboard-quick-action-icon) {
                        display: inline;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action-icon {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                    }

                }


                /* ===================================================== */
                /* TABLET */
                /* ACTIONS MOVE TO SECOND ROW */
                /* SEARCH AND ICON BUTTONS REMAIN FIRST ROW */
                /* ===================================================== */

                @media (max-width: 768px) {

                    .dashboard-toolbar {
                        flex-wrap: wrap;
                    }


                    .dashboard-toolbar-search {
                        flex:
                            1
                            1
                            calc(100% - 104px);
                    }


                    .dashboard-toolbar-actions {
                        width: 100%;

                        justify-content: flex-end;
                    }


                    .dashboard-quick-action span {
                        display: inline;
                    }


                    .dashboard-quick-action {
                        width: auto;

                        padding: 0 13px;
                    }

                }


                /* ===================================================== */
                /* MOBILE */
                /* ===================================================== */

                @media (max-width: 520px) {

                    .dashboard-search-shortcut {
                        display: none;
                    }


                    .dashboard-toolbar-search input {
                        padding-right: 16px;
                    }


                    .dashboard-toolbar-actions {
                        overflow-x: auto;

                        justify-content: flex-start;
                    }


                    .dashboard-quick-action span {
                        display: none;
                    }


                    .dashboard-quick-action {
                        width: 42px;

                        flex: 0 0 42px;

                        padding: 0;
                    }

                    /* Sidebar quick actions keep full labels / grid layout */
                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action {
                        width: 100%;
                        flex: none;
                        padding: 8px 6px;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action > span:not(.dashboard-quick-action-icon) {
                        display: inline;
                    }

                    .maintenance-dashboard-sidebar > .dashboard-sidebar-quick-actions .dashboard-quick-action-icon {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                    }

                }

                /* ===================================================== */
                /* ACTIVITY SIDEBAR CARD */
                /* ===================================================== */

                .activity-sidebar-card {
                    width: 100%;

                    overflow: hidden;

                    background: white;

                    border: 1px solid #e5e7eb;

                    border-radius: 22px;

                    box-shadow:
                        0 1px 3px rgba(15, 23, 42, 0.04);
                }


                /* ===================================================== */
                /* HEADER */
                /* ===================================================== */

                .activity-sidebar-header {
                    height: 58px;

                    display: flex;

                    align-items: center;

                    justify-content: space-between;

                    padding: 0 20px;
                }


                .activity-sidebar-heading {
                    margin: 0;

                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 16px;

                    font-weight: 700;
                }


                .activity-sidebar-menu {
                    width: 20px;
                    height: 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    border-radius: 50%;
                    background: transparent;
                }


                .activity-sidebar-menu:hover {
                    width: 20px;
                    height: 20px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    border-radius: 50%;
                    background: #f4f6f9;
                    
                }


                /* ===================================================== */
                /* ACTIVITY OVERVIEW */
                /* ===================================================== */

                /* ===================================================== */
                /* RECENT ACTIVITIES GRAY PANEL */
                /* SAME STRUCTURE AS YOUR MENTOR REFERENCE */
                /* ===================================================== */

                .activity-list-panel {
                    margin: 0 14px 14px;

                    padding: 4px 4px 10px;

                    background: #f7f7fb;

                    border-radius: 18px;
                }


                /* ===================================================== */
                /* ACTIVITY LIST */
                /* REMOVE ITS OWN BACKGROUND CARD */
                /* ===================================================== */

                .activity-list {
                    margin: 0;

                    padding: 4px 10px;

                    background: transparent;

                    border: 0;

                    border-radius: 0;
                }


                /* ===================================================== */
                /* ACTIVITY ROW */
                /* ===================================================== */

                .activity-list-item {
                    min-width: 0;

                    display: flex;

                    align-items: center;

                    gap: 10px;

                    padding: 11px 4px;

                    border-bottom: 1px solid #e7e7ef;
                }


                .activity-list-item:last-child {
                    border-bottom: 0;
                }


                /* ===================================================== */
                /* FOOTER */
                /* ===================================================== */

                .activity-sidebar-footer {
                    padding: 8px 10px 0;
                }


                .activity-sidebar-footer a {
                    min-height: 34px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #ece9ff;

                    border-radius: 12px;

                    color: #6d5ce7;

                    font-size: 10px;

                    font-weight: 600;

                    text-decoration: none;
                }

                .activity-overview {
                    margin: 0;

                    padding: 10px 16px 18px;

                    text-align: center;

                    background: transparent;

                    border: 0;

                    border-radius: 0;
                }


                .activity-overview-icon {
                    width: 74px;

                    height: 74px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    margin: 0 auto 10px;

                    border: 2px solid #c7d2fe;

                    border-radius: 999px;
                }


                .activity-overview-icon-inner {
                    width: 58px;

                    height: 58px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #eef2ff;

                    border-radius: 999px;

                    color: #4f46e5;
                }


                .activity-overview-title {
                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 15px;

                    font-weight: 700;
                }


                .activity-overview-description {
                    margin-top: 2px;

                    color: #94a3b8;

                    font-size: 10px;
                }


                /* ===================================================== */
                /* ACTIVITY STATISTICS */
                /* ===================================================== */

                .activity-overview-stats {
                    display: grid;

                    grid-template-columns: repeat(3, 1fr);

                    gap: 6px;

                    margin-top: 16px;

                    padding: 10px;

                    background: white;

                    border: 1px solid #f1f5f9;

                    border-radius: 14px;
                }


                .activity-overview-stat {
                    min-width: 0;

                    display: flex;

                    flex-direction: column;

                    align-items: center;

                    justify-content: center;

                    padding: 4px;
                }


                .activity-overview-number {
                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 16px;

                    font-weight: 700;
                }


                .activity-overview-label {
                    margin-top: 1px;

                    color: #94a3b8;

                    font-size: 9px;

                    font-weight: 500;
                }


                /* ===================================================== */
                /* RECENT ACTIVITY SECTION HEADER */
                /* ===================================================== */

                .activity-list-heading {
                    display: flex;

                    align-items: center;

                    justify-content: space-between;

                    padding: 18px 18px 10px;
                }


                .activity-list-heading h3 {
                    margin: 0;

                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 14px;

                    font-weight: 700;
                }


                .activity-list-heading p {
                    margin: 2px 0 0;

                    color: #94a3b8;

                    font-size: 10px;
                }


                .activity-list-add {
                    width: 30px;

                    height: 30px;

                    display: inline-flex;

                    align-items: center;

                    justify-content: center;

                    border: 1px solid #e2e8f0;

                    border-radius: 999px;

                    background: white;

                    color: #64748b;

                    text-decoration: none;
                }


                .activity-list-add:hover {
                    background: #f8fafc;

                    color: #0f172a;
                }

                /* ===================================================== */
                /* REPORT ACTIVITY CHART */
                /* ===================================================== */

                .activity-chart-card {
                    margin-top: 18px;

                    padding: 16px;

                    background: #f7f7fb;

                    border: 1px solid #f1f5f9;

                    border-radius: 18px;
                }


                /* ===================================================== */
                /* CHART HEADER */
                /* ===================================================== */

                .activity-chart-header {
                    display: flex;

                    align-items: center;

                    justify-content: space-between;

                    gap: 16px;

                    margin-bottom: 14px;
                }


                .activity-chart-title {
                    font-family: "Outfit", sans-serif;

                    font-size: 13px;

                    font-weight: 700;

                    color: #0f172a;
                }


                .activity-chart-subtitle {
                    margin-top: 2px;

                    font-size: 10px;

                    color: #94a3b8;
                }


                .activity-chart-total {
                    font-family: "Outfit", sans-serif;

                    font-size: 18px;

                    font-weight: 800;

                    color: #0f172a;
                }


                .activity-chart-total span {
                    margin-left: 2px;

                    font-family: "Inter", sans-serif;

                    font-size: 9px;

                    font-weight: 500;

                    color: #94a3b8;
                }


                /* ===================================================== */
                /* CHART CONTAINER */
                /* ===================================================== */

                .activity-chart-container {
                    position: relative;

                    width: 100%;

                    height: 150px;
                }


                .activity-chart-container canvas {
                    width: 100% !important;

                    height: 100% !important;
                }


                /* ===================================================== */
                /* ACTIVITY LIST */
                /* ===================================================== */

                .activity-list {
                    margin: 0 14px;

                    padding: 6px 10px;

                    background: #fafafa;

                    border: 1px solid #f1f5f9;

                    border-radius: 18px;
                }


                .activity-list-item {
                    min-width: 0;

                    display: flex;

                    align-items: center;

                    gap: 10px;

                    padding: 11px 4px;

                    border-bottom: 1px solid #eaeef3;
                }


                .activity-list-item:last-child {
                    border-bottom: 0;
                }


                /* ===================================================== */
                /* ACTIVITY ICON */
                /* ===================================================== */

                .activity-list-icon {
                    width: 34px;

                    height: 34px;

                    flex: 0 0 34px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    border-radius: 999px;
                }


                /* ===================================================== */
                /* ACTIVITY INFORMATION */
                /* ===================================================== */

                .activity-list-content {
                    min-width: 0;

                    flex: 1;
                }


                .activity-list-title {
                    overflow: hidden;

                    color: #0f172a;

                    font-size: 11px;

                    font-weight: 600;

                    line-height: 1.3;

                    text-overflow: ellipsis;

                    white-space: nowrap;
                }


                .activity-list-description {
                    overflow: hidden;

                    margin-top: 2px;

                    color: #64748b;

                    font-size: 9px;

                    line-height: 1.3;

                    text-overflow: ellipsis;

                    white-space: nowrap;
                }


                .activity-list-time {
                    margin-top: 2px;

                    color: #94a3b8;

                    font-size: 8px;
                }


                /* ===================================================== */
                /* VIEW BUTTON */
                /* ===================================================== */

                .activity-list-view {
                    flex: 0 0 auto;

                    padding: 5px 10px;

                    border: 1px solid #ddd6fe;

                    border-radius: 999px;

                    background: white;

                    color: #6d5ce7;

                    font-size: 9px;

                    font-weight: 600;

                    text-decoration: none;
                }


                .activity-list-view:hover {
                    background: #f5f3ff;
                }


                /* ===================================================== */
                /* EMPTY STATE */
                /* ===================================================== */

                .activity-empty-state {
                    display: flex;

                    min-height: 150px;

                    flex-direction: column;

                    align-items: center;

                    justify-content: center;

                    gap: 8px;

                    color: #94a3b8;

                    font-size: 11px;
                }


                /* ===================================================== */
                /* FOOTER */
                /* ===================================================== */

                .activity-sidebar-footer {
                    padding: 14px;
                }


                .activity-sidebar-footer a {
                    min-height: 40px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #f5f3ff;

                    border-radius: 13px;

                    color: #6d5ce7;

                    font-size: 11px;

                    font-weight: 600;

                    text-decoration: none;

                    transition:
                        background 0.18s ease,
                        transform 0.18s ease;
                }


                .activity-sidebar-footer a:hover {
                    background: #ede9fe;

                    transform: translateY(-1px);
                }

                /* ===================================================== */
                /* URGENT PIPELINE SECTION */
                /* ===================================================== */

                .urgent-pipeline-section {
                    min-width: 0;
                }


                /* ===================================================== */
                /* SECTION HEADER */
                /* ===================================================== */

                .urgent-pipeline-header {
                    display: flex;

                    align-items: center;

                    justify-content: space-between;

                    gap: 20px;

                    margin-bottom: 14px;
                }


                .urgent-pipeline-title {
                    margin: 0;

                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 17px;

                    font-weight: 700;

                    line-height: 1.2;
                }


                .urgent-pipeline-description {
                    margin: 4px 0 0;

                    color: #94a3b8;

                    font-size: 11px;
                }


                /* ===================================================== */
                /* CAROUSEL CONTROLS */
                /* ===================================================== */

                .urgent-pipeline-controls {
                    display: flex;

                    align-items: center;

                    gap: 8px;
                }


                .urgent-carousel-button {
                    width: 32px;

                    height: 32px;

                    display: inline-flex;

                    align-items: center;

                    justify-content: center;

                    flex-shrink: 0;

                    border: 1px solid #e2e8f0;

                    border-radius: 999px;

                    background: white;

                    color: #94a3b8;

                    cursor: pointer;

                    transition:
                        background 0.18s ease,
                        color 0.18s ease,
                        border-color 0.18s ease,
                        transform 0.18s ease;
                }


                .urgent-carousel-button:hover {
                    color: #334155;

                    border-color: #cbd5e1;

                    transform: translateY(-1px);
                }


                .urgent-carousel-button-active {
                    border-color: #4f46e5;

                    background: #0025cc;

                    color: white;
                }


                .urgent-carousel-button-active:hover {
                    background: #4338ca;

                    color: white;

                    border-color: #4338ca;
                }


                /* ===================================================== */
                /* CAROUSEL */
                /* NO INNER PADDING SO 100% = EXACT VIEWPORT WIDTH */
                /* ===================================================== */

                .urgent-media-carousel {
                    display: flex;
                    align-items: stretch;

                    gap: 0;

                    overflow-x: auto;
                    overflow-y: hidden;

                    scroll-behavior: smooth;
                    scroll-snap-type: x mandatory;

                    padding: 0;

                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 22px;

                    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
                }


                /* ===================================================== */
                /* REPORT CARD */
                /* ONE FULL CARD */
                /* ===================================================== */

                .urgent-media-card {
                    flex: 0 0 100%;
                    width: 100%;
                    min-width: 100%;

                    box-sizing: border-box;

                    /* Card gets the spacing instead */
                    padding: 12px 10px;

                    scroll-snap-align: start;
                    scroll-snap-stop: always;

                    overflow: hidden;
                }


                .urgent-media-card:hover {
                    border-color: transparent;

                    box-shadow: none;

                    transform: none;
                }


                /* ===================================================== */
                /* MEDIA AREA */
                /* ===================================================== */

                .urgent-media-image {
                    position: relative;

                    height: 122px;

                    display: block;

                    overflow: hidden;

                    margin-left: 10px;

                    border-radius: 14px;

                    text-decoration: none;
                }


                .urgent-media-placeholder {
                    width: 100%;

                    height: 100%;

                    display: flex;

                    flex-direction: column;

                    align-items: center;

                    justify-content: center;

                    gap: 7px;

                    background:
                        linear-gradient(
                            135deg,
                            #f8fafc 0%,
                            #eef2ff 100%
                        );

                    color: #64748b;
                }


                .urgent-media-placeholder span {
                    font-size: 10px;

                    font-weight: 600;
                }


                /* ===================================================== */
                /* ALERT BUTTON */
                /* ===================================================== */

                .urgent-media-alert {
                    position: absolute;
                    top: 9px;
                    right: 9px;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;

                    /* 1. Add a semi-transparent white/gray fill to make the blur noticeable */
                    background: rgba(255, 255, 255, 0.15);

                    /* 2. Soften the border to match a clean glassmorphism style */
                    border: 1px solid rgba(255, 255, 255, 0.25);

                    border-radius: 999px;
                    color: white;
                    backdrop-filter: blur(8px);
                    -webkit-backdrop-filter: blur(8px); /* Safari support */

                    /* 3. Add a subtle shadow to give it depth against dark backgrounds */
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

                    /* 4. Smooth transition for hover states */
                    transition: all 0.2s ease-in-out;
                }

                /* Optional: Make it slightly interactive when hovered */
                .urgent-media-alert:hover {
                    background: rgba(255, 255, 255, 0.25);
                    transform: scale(1.05);
                }


                /* ===================================================== */
                /* CONTENT */
                /* ===================================================== */

                .urgent-media-content {
                    padding: 10px 12px 12px;
                }


                /* ===================================================== */
                /* META */
                /* ===================================================== */

                .urgent-media-meta {
                    display: flex;

                    align-items: center;

                    justify-content: space-between;

                    gap: 8px;

                    margin-bottom: 7px;
                }


                .urgent-media-category {
                    display: inline-flex;

                    align-items: center;

                    gap: 4px;

                    padding: 3px 7px;

                    background: #ffffff;

                    border-radius: 999px;

                    color: black;

                    font-size: 9px;

                    font-weight: 700;

                    border: 1px solid gray;
                }


                .urgent-media-time {
                    overflow: hidden;

                    color: #94a3b8;

                    font-size: 9px;

                    text-overflow: ellipsis;

                    white-space: nowrap;
                }


                /* ===================================================== */
                /* TITLE */
                /* ===================================================== */

                .urgent-media-title {
                    display: block;

                    overflow: hidden;

                    min-height: 38px;

                    color: #0f172a;

                    font-family: "Outfit", sans-serif;

                    font-size: 14px;

                    font-weight: 700;

                    line-height: 1.35;

                    text-decoration: none;

                    display: -webkit-box;

                    -webkit-box-orient: vertical;

                    -webkit-line-clamp: 2;
                }


                .urgent-media-title:hover {
                    color: #4f46e5;
                }


                /* ===================================================== */
                /* LOCATION */
                /* ===================================================== */

                .urgent-media-location {
                    min-width: 0;

                    display: flex;

                    align-items: center;

                    gap: 4px;

                    margin-top: 5px;

                    color: #64748b;

                    font-size: 10px;
                }


                .urgent-media-location span {
                    overflow: hidden;

                    text-overflow: ellipsis;

                    white-space: nowrap;
                }


                /* ===================================================== */
                /* STATUS ACCENT LINE */
                /* ===================================================== */

                .urgent-media-progress {
                    width: 44%;

                    height: 2px;

                    margin-top: 11px;

                    border-radius: 999px;
                }


                .urgent-media-progress.pending {
                    background: #f59e0b;
                }


                .urgent-media-progress.processing {
                    background: #4f46e5;
                }


                .urgent-media-progress.replacement {
                    background: #dc2626;
                }


                /* ===================================================== */
                /* FOOTER */
                /* ===================================================== */

                .urgent-media-footer {
                    min-width: 0;

                    display: flex;

                    align-items: center;

                    justify-content: space-between;

                    gap: 10px;

                    margin-top: 10px;
                }


                .urgent-media-reporter {
                    min-width: 0;

                    display: flex;

                    align-items: center;

                    gap: 8px;
                }


                .urgent-media-avatar {
                    width: 28px;

                    height: 28px;

                    flex: 0 0 28px;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    border-radius: 999px;

                    background: #eef2ff;

                    color: #4f46e5;

                    font-size: 9px;

                    font-weight: 700;
                }


                .urgent-media-reporter-info {
                    min-width: 0;

                    display: flex;

                    flex-direction: column;
                }


                .urgent-media-reporter-name {
                    overflow: hidden;

                    color: #334155;

                    font-size: 9px;

                    font-weight: 600;

                    text-overflow: ellipsis;

                    white-space: nowrap;
                }


                .urgent-media-reporter-label {
                    margin-top: 1px;

                    color: #94a3b8;

                    font-size: 8px;
                }


                /* ===================================================== */
                /* VIEW BUTTON */
                /* ===================================================== */

                .urgent-media-view {
                    width: 28px;

                    height: 28px;

                    flex: 0 0 28px;

                    display: inline-flex;

                    align-items: center;

                    justify-content: center;

                    border: 1px solid #e2e8f0;

                    border-radius: 999px;

                    background: white;

                    color: #64748b;

                    text-decoration: none;

                    transition:
                        color 0.18s ease,
                        border-color 0.18s ease,
                        background 0.18s ease;
                }


                .urgent-media-view:hover {
                    border-color: #c7d2fe;

                    background: #eef2ff;

                    color: #4f46e5;
                }


                /* ===================================================== */
                /* EMPTY STATE */
                /* ===================================================== */

                .urgent-media-empty {
                    width: 100%;

                    min-height: 230px;

                    display: flex;

                    flex-direction: column;

                    align-items: center;

                    justify-content: center;

                    gap: 6px;

                    border: 1px dashed #cbd5e1;

                    border-radius: 18px;

                    background: white;

                    color: #94a3b8;

                    text-align: center;
                }


                .urgent-media-empty strong {
                    color: #475569;

                    font-size: 12px;
                }


                .urgent-media-empty span {
                    font-size: 10px;
                }

                /* ===================================================== */
                /* MOBILE */
                /* SHOW 1 CARD */
                /* ===================================================== */

                @media (max-width: 640px) {

                    .urgent-media-card {
                        flex-basis: 100%;
                    }

                }

                /* ===================================================== */
                /* ACTUAL REPORT IMAGE */
                /* ===================================================== */

                .urgent-media-photo {
                    width: 100%;

                    height: 100%;

                    display: block;

                    object-fit: cover;

                    object-position: center;

                    transition: transform 0.25s ease;
                }


                /* ===================================================== */
                /* IMAGE HOVER */
                /* ===================================================== */

                .urgent-media-card:hover .urgent-media-photo {
                    transform: scale(1.025);
                }

                /* ===================================================== */
        /* MAIN ANALYTICS CHARTS */
        /* ADD THIS BEFORE THE CLOSING STYLE TAG */
        /* ===================================================== */

        .dashboard-analytics-card {
            min-width: 0;

            overflow: hidden;

            padding: 22px;

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 22px;

            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.04);
        }


        .dashboard-analytics-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 16px;

            margin-bottom: 20px;
        }


        .dashboard-analytics-title {
            margin: 0;

            color: #0f172a;

            font-family: "Outfit", sans-serif;

            font-size: 16px;

            font-weight: 700;
        }


        .dashboard-analytics-subtitle {
            margin: 3px 0 0;

            color: #94a3b8;

            font-size: 10px;
        }


        .dashboard-report-activity-chart {
            position: relative;

            width: 100%;

            height: 320px;
        }


        .dashboard-bottom-charts {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);

            gap: 24px;
        }


        .dashboard-small-chart {
            position: relative;

            width: 100%;

            height: 270px;
        }


        /* ===================================================== */
        /* EQUIPMENT CONDITION — RADAR / COMPETITOR STYLE */
        /* ===================================================== */

        .equipment-statistic-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .equipment-statistic-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 0;
        }

        .equipment-statistic-heading {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .equipment-statistic-icon {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(0, 37, 204, 0.1);
            color: #0025cc;
        }

        .equipment-statistic-icon svg {
            width: 14px;
            height: 14px;
        }

        .equipment-statistic-title {
            margin: 0;
            color: #1f2937;
            font-family: "Outfit", sans-serif;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
        }

        .equipment-statistic-info {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }

        .equipment-statistic-info svg {
            width: 14px;
            height: 14px;
        }

        .equipment-statistic-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 4px;
            border-radius: 12px;
            background: #f3f4f6;
            flex-shrink: 0;
        }

        .equipment-statistic-tool {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #6b7280;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .equipment-statistic-tool:hover {
            background: #ffffff;
            color: #111827;
        }

        .equipment-statistic-tool svg {
            width: 14px;
            height: 14px;
        }

        .equipment-statistic-panel {
            flex: 1 1 auto;
            min-height: 0;
            padding: 4px 8px 0;
            background: transparent;
            border: none;
        }

        .equipment-statistic-chart {
            position: relative;
            width: 100%;
            height: 260px;
        }

        .equipment-statistic-legend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 22px;
            padding-bottom: 4px;
        }

        .equipment-statistic-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
        }

        .equipment-statistic-swatch {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            flex-shrink: 0;
        }

        .equipment-statistic-swatch.current {
            background: #0025cc;
        }

        .equipment-statistic-swatch.target {
            background: #14b8a6;
        }

        @media (max-width: 640px) {
            .equipment-statistic-chart {
                height: 230px;
            }
        }


        /* ===================================================== */
        /* MAINTENANCE CALENDAR */
        /* RIGHT SIDEBAR ABOVE ACTIVITY */
        /* ===================================================== */

        .dashboard-calendar-card {
            min-width: 0;

            overflow: hidden;

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 22px;

            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.04);
        }


        .dashboard-calendar-header {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            padding: 18px 20px;

            border-bottom: 1px solid #f1f5f9;
        }


        .dashboard-calendar-heading {
            margin: 0;

            color: #0f172a;

            font-family: "Outfit", sans-serif;

            font-size: 16px;

            font-weight: 700;
        }


        .dashboard-calendar-description {
            margin: 3px 0 0;

            color: #94a3b8;

            font-size: 10px;
        }


        .dashboard-calendar-body {
            padding: 18px;
        }


        .calendar-month {
            margin-bottom: 14px;

            color: #0f172a;

            font-family: "Outfit", sans-serif;

            font-size: 13px;

            font-weight: 700;
        }


        .calendar-weekdays,
        .calendar-days {
            display: grid;

            grid-template-columns:
                repeat(7, minmax(0, 1fr));

            gap: 4px;
        }


        .calendar-weekday {
            padding: 5px 0;

            text-align: center;

            color: #94a3b8;

            font-size: 9px;

            font-weight: 700;
        }


        .calendar-day {
            position: relative;

            min-height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 0;

            border-radius: 9px;

            background: transparent;

            color: #475569;

            font-family: "Inter", sans-serif;

            font-size: 10px;

            cursor: pointer;

            transition:
                background 0.18s ease,
                color 0.18s ease;
        }


        .calendar-day:hover {
            background: #f8fafc;
        }


        .calendar-day.today {
            background: #e8ecff;

            color: #0025cc;

            font-weight: 700;
        }


        .calendar-day.has-events::after {
            content: "";

            position: absolute;

            bottom: 3px;

            width: 4px;

            height: 4px;

            border-radius: 999px;

            background: #dc2626;
        }


        .calendar-day.empty {
            cursor: default;
        }


        .calendar-selected-events {
            max-height: 220px;

            overflow-y: auto;

            margin-top: 18px;

            padding-top: 14px;

            border-top: 1px solid #f1f5f9;
        }


        .calendar-event-item {
            padding: 10px;

            margin-bottom: 6px;

            border-radius: 11px;

            background: #f8fafc;

            cursor: pointer;
        }


        .calendar-event-item:last-child {
            margin-bottom: 0;
        }


        .calendar-event-title {
            overflow: hidden;

            color: #0f172a;

            font-size: 10px;

            font-weight: 700;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .calendar-event-description {
            overflow: hidden;

            margin-top: 3px;

            color: #94a3b8;

            font-size: 9px;

            text-overflow: ellipsis;

            white-space: nowrap;
        }


        .calendar-empty-state {
            padding: 24px 8px;

            text-align: center;

            color: #94a3b8;

            font-size: 10px;
        }

        .dashboard-empty-state {
                padding: 30px 10px;

                text-align: center;

                color: #94a3b8;

                font-size: 11px;
            }


        /* ===================================================== */
        /* RESPONSIVE ANALYTICS */
        /* ===================================================== */

        @media (max-width: 850px) {

            .dashboard-bottom-charts {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 640px) {

            .dashboard-report-activity-chart {
                height: 260px;
            }


            .dashboard-small-chart {
                height: 240px;
            }

        }

        /* ===================================================== */
        /* MAINTENANCE CALENDAR CARD */
        /* ===================================================== */

        .dashboard-calendar-card {
            width: 100%;

            overflow: hidden;

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 22px;

            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.04);
        }


        /* ===================================================== */
        /* CALENDAR HEADER */
        /* ===================================================== */

        .dashboard-calendar-header {
            min-height: 64px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 16px;

            padding: 0 20px;

            border-bottom: 1px solid #f1f5f9;
        }


        .dashboard-calendar-title {
            margin: 0;

            color: #0f172a;

            font-family: "Outfit", sans-serif;

            font-size: 16px;

            font-weight: 700;
        }


        .dashboard-calendar-subtitle {
            margin: 3px 0 0;

            color: #94a3b8;

            font-size: 10px;
        }


        .dashboard-calendar-header-icon {
            width: 34px;

            height: 34px;

            flex: 0 0 34px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #f8fafc;

            color: #64748b;
        }


        /* ===================================================== */
        /* CALENDAR BODY */
        /* ===================================================== */

        .dashboard-calendar-body {
            padding: 18px;
        }


        /* ===================================================== */
        /* CURRENT MONTH */
        /* ===================================================== */

        .dashboard-calendar-month-row {
            display: flex;

            align-items: center;

            min-height: 30px;

            margin-bottom: 10px;
        }


        .dashboard-calendar-month {
            color: #0f172a;

            font-family: "Outfit", sans-serif;

            font-size: 13px;

            font-weight: 700;
        }


        /* ===================================================== */
        /* WEEKDAYS */
        /* ===================================================== */

        .calendar-weekdays {
            display: grid;

            grid-template-columns: repeat(7, minmax(0, 1fr));

            gap: 4px;

            margin-bottom: 4px;
        }


        .calendar-weekdays div {
            padding: 5px 0;

            text-align: center;

            color: #94a3b8;

            font-size: 9px;

            font-weight: 700;
        }


        /* ===================================================== */
        /* CALENDAR DAYS */
        /* ===================================================== */

        .calendar-days {
            display: grid;

            grid-template-columns: repeat(7, minmax(0, 1fr));

            gap: 4px;
        }


        .calendar-day {
            position: relative;

            min-width: 0;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 0;

            border: 0;

            border-radius: 9px;

            background: transparent;

            color: #475569;

            font-family: "Inter", sans-serif;

            font-size: 10px;

            cursor: pointer;

            transition:
                background 0.18s ease,
                color 0.18s ease;
        }


        .calendar-day:hover {
            background: #f8fafc;

            color: #0f172a;
        }


        .calendar-day.today {
            background: #e8ecff;

            color: #0025cc;

            font-weight: 700;
        }


        .calendar-day.has-events::after {
            content: "";

            position: absolute;

            left: 50%;

            bottom: 3px;

            width: 4px;

            height: 4px;

            border-radius: 999px;

            background: #dc2626;

            transform: translateX(-50%);
        }


        .calendar-day.today.has-events::after {
            background: #dc2626;
        }


        .calendar-day.empty {
            pointer-events: none;
        }


        /* ===================================================== */
        /* SELECTED DATE EVENTS */
        /* ===================================================== */

        .calendar-selected-events {
            max-height: 210px;

            overflow-y: auto;

            margin-top: 16px;

            padding-top: 14px;

            border-top: 1px solid #f1f5f9;
        }



        /* ===================================================== */
        /* PREMIUM CARD */
        /* ===================================================== */

        .premium-stat-card{

            position:relative;

            overflow:hidden;

            display:flex;

            flex-direction:column;

            justify-content:space-between;

            min-height:285px;

            padding:22px;

            background:white;

            border:1px solid #edf2f7;

            border-radius:24px;

            box-shadow:
                0 10px 35px rgba(15,23,42,.05),
                0 2px 8px rgba(15,23,42,.03);

            transition:.25s ease;

        }

        .premium-stat-card:hover{

            transform:translateY(-4px);

            border-color:#dbe4ee;

            box-shadow:
                0 20px 50px rgba(15,23,42,.08),
                0 6px 20px rgba(15,23,42,.05);

        }

        .premium-card-top{

            display:flex;
            justify-content:space-between;
            align-items:flex-start;

            margin-bottom:22px;

        }

        .premium-card-info{

            display:flex;
            align-items:center;
            gap:14px;

        }

        .premium-icon{

            width:48px;
            height:48px;

            display:flex;
            align-items:center;
            justify-content:center;

            border-radius:15px;

            background:#f8fafc;

            border:1px solid #eef2f7;

            color:#475569;

        }

        .premium-icon svg{

            width:22px;
            height:22px;

        }

        .premium-card-subtitle{

            font-size:11px;

            font-weight:500;

            color:#94a3b8;

        }

        .premium-card-title{

            margin-top:2px;

            font-size:15px;

            font-weight:700;

            color:#111827;

        }

        .premium-action{

            width:38px;
            height:38px;

            display:flex;
            align-items:center;
            justify-content:center;

            border-radius:50%;

            background:#f8fafc;

            border:1px solid #edf2f7;

            color:#64748b;

            transition:.25s;

        }

        .premium-action:hover{

            background:#111827;

            color:white;

            transform:rotate(45deg);

        }

        .premium-card-body{

            margin-bottom:10px;

        }

        .premium-card-value{

            font-size:52px;

            font-weight:800;

            letter-spacing:-2px;

            color:#111827;

        }

        .premium-card-trend{

            margin-top:14px;

            display:flex;

            align-items:center;

            gap:6px;

            font-size:13px;

            font-weight:600;

        }

        .premium-card-trend.positive{

            color:#16a34a;

        }

        .premium-card-trend.negative{

            color:#dc2626;

        }



        .premium-chart{

            position:relative;

            height:120px;

            margin-top:14px;



        }

        .premium-chart canvas{

            width:100% !important;
            height:100% !important;

        }

        .chart-label{

            position:absolute;

            padding:4px 10px;

            border-radius:999px;

            font-size:11px;
            font-weight:600;

            color:white;

            backdrop-filter:blur(10px);

            background:rgba(255,255,255,.10);

            border:1px solid rgba(255,255,255,.08);

        }

        .label-one{

            top:14px;
            right:70px;

        }

        .label-two{

            bottom:18px;
            right:20px;

        }

        .premium-stat-card.red::after{

            content:"";

            position:absolute;

            width:180px;
            height:180px;

            right:-80px;
            bottom:-80px;

            border-radius:50%;

            background:radial-gradient(

                circle,

                rgba(255,99,99,.12),

                transparent 75%

            );

            filter:blur(25px);

        }

        .premium-stat-card.amber::after{

            content:"";

            position:absolute;

            width:180px;
            height:180px;

            right:-80px;
            bottom:-80px;

            border-radius:50%;

            background:radial-gradient(

                circle,

                rgba(251,191,36,.12),

                transparent 75%

            );

            filter:blur(25px);

        }

        .premium-stat-card.green::after{

            content:"";

            position:absolute;

            width:180px;
            height:180px;

            right:-80px;
            bottom:-80px;

            border-radius:50%;

            background:radial-gradient(

                circle,

                rgba(34,197,94,.12),

                transparent 75%

            );

            filter:blur(25px);

        }

        /* =====================================================
        DASHBOARD OVERVIEW ROW
        Prevent cards from becoming unnecessarily tall
        ===================================================== */

        .dashboard-overview-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            grid-template-rows: auto minmax(0, 1fr);
            column-gap: 20px;
            row-gap: 10px;
            align-items: stretch;
        }

        .dashboard-overview-row > * {
            min-width: 0;
        }


        /* =====================================================
        EQUIPMENT STATISTICS CARD (metrics dashboard style)
        ===================================================== */

        .flow-card {
            grid-column: 1;
            grid-row: 1 / -1;
            display: grid;
            grid-template-rows: subgrid;
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            min-width: 0;
            min-height: 0;
        }

        .eq-metrics-head {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
        }

        .eq-metrics-body {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 0;
            height: 100%;
            align-self: stretch;
        }

        .eq-metrics-slot-top,
        .eq-metrics-slot-bottom {
            flex: 1 1 0;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .eq-metrics-slot-top > [data-eq-metrics-panel],
        .eq-metrics-slot-bottom > [data-eq-funnel] {
            flex: 1 1 0;
            min-height: 0;
            height: 100%;
            max-height: 100%;
            overflow: hidden;
        }

        .eq-metrics-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .flow-title {
            font-size: clamp(18px, 2.2vw, 22px);
            font-weight: 700;
            line-height: 1.1;
            color: #111827;
            margin: 0;
        }

        .eq-metrics-controls {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .eq-stats-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 6px);
            z-index: 80;
            width: min(240px, calc(100vw - 32px));
            padding: 6px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }

        .eq-stats-menu.hidden {
            display: none;
        }

        .eq-stats-menu-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .eq-stats-menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s ease;
        }

        .eq-stats-menu-item:hover,
        .eq-stats-menu-item:focus-visible {
            background: #f3f4f6;
            outline: none;
        }

        .eq-stats-menu-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #eceff3;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .eq-stats-menu-title {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
        }

        .eq-stats-menu-desc {
            margin-top: 2px;
            font-size: 10px;
            font-weight: 500;
            color: #9ca3af;
            line-height: 1.3;
        }

        .eq-metrics-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 28px;
            padding: 0 10px;
            border: none;
            border-radius: 999px;
            background: #eceff3;
            color: #111827;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .eq-period-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 80;
            min-width: 148px;
            padding: 6px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }

        /* Fixed so menus escape overflow:hidden on funnel / metrics slots */
        .eq-funnel-menu {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1200;
            min-width: 168px;
            padding: 6px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.18);
        }

        .eq-period-menu.hidden,
        .eq-funnel-menu.hidden {
            display: none;
        }

        .eq-period-option,
        .eq-funnel-menu a {
            display: block;
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            cursor: pointer;
        }

        .eq-period-option:hover,
        .eq-funnel-menu a:hover {
            background: #f3f4f6;
            color: #111827;
        }

        .eq-period-option.is-active {
            background: #f3f4f6;
            color: #111827;
        }

        .eq-funnel-more-wrap {
            position: relative;
            z-index: 9;
        }

        .eq-metrics-icon-btn {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 10px;
            background: #eceff3;
            color: #111827;
            cursor: pointer;
        }

        .eq-metrics-tabs {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .eq-metrics-tab {
            appearance: none;
            border: none;
            background: transparent;
            padding: 0 0 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            cursor: pointer;
            position: relative;
        }

        .eq-metrics-tab.is-active {
            color: #111827;
        }

        .eq-metrics-tab.is-active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 2px;
            border-radius: 999px 999px 0 0;
            background: #111827;
        }

        .eq-metrics-tab-count {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #111827;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .eq-metrics-panel {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 12px;
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 0;
            overflow: hidden;
            box-sizing: border-box;
            align-content: stretch;
            height: 100%;
            min-height: 0;
        }

        .eq-metrics-panel.is-hidden {
            display: none;
        }

        .eq-metrics-cell {
            min-width: 0;
            min-height: 0;
            padding: 4px 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .eq-metrics-cell + .eq-metrics-cell {
            border-left: 1px solid #eef0f3;
        }

        .eq-metrics-cell-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }

        .eq-metrics-avatars {
            display: flex;
            align-items: center;
        }

        .eq-metrics-avatar {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 2px solid #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 37, 204, 0.12);
            color: #0025cc;
        }

        .eq-metrics-avatar + .eq-metrics-avatar {
            margin-left: -8px;
            background: #111827;
            color: #ffffff;
        }

        .eq-metrics-mini-icon {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            border: 1px solid #e5e7eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            background: #fafafa;
        }

        .eq-metrics-mini-icon.is-round {
            border-radius: 999px;
        }

        .eq-metrics-value-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .eq-metrics-value {
            font-size: clamp(22px, 2.6vw, 28px);
            font-weight: 700;
            line-height: 1;
            color: #111827;
            letter-spacing: -0.03em;
        }

        .eq-metrics-label {
            font-size: 11px;
            font-weight: 500;
            color: #9ca3af;
            max-width: 80px;
            line-height: 1.2;
            padding-bottom: 2px;
        }

        .eq-metrics-label.is-top {
            max-width: none;
            margin-bottom: 4px;
            padding-bottom: 0;
        }

        .eq-goal-meta {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 10px;
            font-weight: 500;
            color: #9ca3af;
        }

        .eq-goal-bar {
            margin-top: 6px;
            height: 7px;
            border-radius: 999px;
            background: #111827;
            overflow: hidden;
        }

        .eq-goal-fill {
            height: 100%;
            border-radius: 999px;
            background: #0025cc;
            min-width: 0;
        }

        /* =====================================================
           PAYMENTS FUNNEL — fade-to-blue (#0025cc)
           ===================================================== */

        .eq-funnel-card {
            --eq-blue: #0025cc;
            --eq-blue-soft: rgba(0, 37, 204, 0.12);
            --eq-blue-mid: #3b5bdb;
            background: #ffffff;
            border: 1px solid #e8eaed;
            border-radius: 22px;
            padding: 12px;
            min-height: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .eq-funnel-card.is-hidden {
            display: none;
        }

        /* Small Overview charts — "Most Day Active" bars */
        .eq-mini-cols {
            flex: 1 1 auto;
            min-height: 0;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            padding: 0 4px;
        }

        .eq-mini-col {
            appearance: none;
            border: none;
            background: transparent;
            padding: 0;
            margin: 0;
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            outline: none;
        }

        .eq-mini-plot {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
            max-width: 48px;
            display: flex;
            align-items: flex-end;
            padding-top: 20px;
            box-sizing: border-box;
        }

        .eq-mini-bar {
            position: relative;
            width: 100%;
            height: var(--bar-h, 0%);
            min-height: 6px;
            border-radius: 10px;
            background: linear-gradient(180deg, #e2e6ed 0%, rgba(226, 230, 237, 0.75) 55%, rgba(226, 230, 237, 0.12) 100%);
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .eq-mini-value {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 5px);
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
            white-space: nowrap;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .eq-mini-label {
            flex-shrink: 0;
            max-width: 100%;
            min-height: 2.3em;
            font-size: 10px;
            font-weight: 500;
            line-height: 1.15;
            color: #9ca3af;
            text-align: center;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.2s ease;
        }

        .eq-mini-col.is-peak .eq-mini-bar,
        .eq-mini-col.is-active .eq-mini-bar {
            background: linear-gradient(180deg, #0025cc 0%, rgba(0, 37, 204, 0.55) 55%, rgba(0, 37, 204, 0.08) 100%);
        }

        .eq-mini-col.is-peak .eq-mini-value,
        .eq-mini-col.is-active .eq-mini-value {
            color: #0025cc;
        }

        .eq-mini-col.is-peak .eq-mini-label,
        .eq-mini-col.is-active .eq-mini-label {
            color: #0025cc;
            font-weight: 700;
        }

        .eq-mini-cols:has(.is-active) .eq-mini-col.is-peak:not(.is-active) .eq-mini-bar {
            background: linear-gradient(180deg, #e2e6ed 0%, rgba(226, 230, 237, 0.75) 55%, rgba(226, 230, 237, 0.12) 100%);
            box-shadow: none;
        }

        .eq-mini-cols:has(.is-active) .eq-mini-col.is-peak:not(.is-active) .eq-mini-value {
            color: #111827;
        }

        .eq-mini-cols:has(.is-active) .eq-mini-col.is-peak:not(.is-active) .eq-mini-label {
            color: #9ca3af;
            font-weight: 500;
        }

        .eq-funnel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 4px;
            flex-shrink: 0;
            position: relative;
            z-index: 8;
        }

        .eq-funnel-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.01em;
        }

        .eq-funnel-more {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            padding: 0;
        }

        .eq-funnel-body {
            flex: 1 1 auto;
            min-height: 0;
            display: grid;
            grid-template-columns: 26px minmax(0, 1fr);
            gap: 2px;
            position: relative;
            z-index: 1;
        }

        .eq-funnel-axis {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
            padding: 44px 0 12px;
            color: #9ca3af;
            font-size: 8px;
            font-weight: 500;
            line-height: 1;
            user-select: none;
            text-align: right;
        }

        .eq-funnel-axis span {
            display: block;
            width: 100%;
            text-align: right;
        }

        .eq-funnel-cols {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            min-height: 0;
            height: 100%;
            position: relative;
        }

        .eq-funnel-col {
            appearance: none;
            border: none;
            background: transparent;
            padding: 0;
            margin: 0;
            min-width: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            text-align: left;
            cursor: pointer;
            position: relative;
            border-radius: 14px;
            transition: background 0.2s ease;
        }

        .eq-funnel-col + .eq-funnel-col {
            border-left: 1px solid rgba(148, 163, 184, 0.22);
        }

        .eq-funnel-col.is-active {
            background: linear-gradient(
                180deg,
                rgba(0, 37, 204, 0.10) 0%,
                rgba(0, 37, 204, 0.02) 55%,
                rgba(0, 37, 204, 0) 100%
            );
        }

        .eq-funnel-col.is-active .eq-funnel-col-label {
            color: #111827;
        }

        .eq-funnel-col-head {
            padding: 4px 6px 6px;
            flex-shrink: 0;
            min-height: 36px;
            position: relative;
            z-index: 2;
        }

        .eq-funnel-col-label {
            display: block;
            color: #9ca3af;
            font-size: 8px;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 3px;
        }

        .eq-funnel-col-value {
            display: block;
            color: #111827;
            font-size: clamp(13px, 1.5vw, 17px);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -0.02em;
        }

        .eq-funnel-col-plot {
            flex: 1 1 auto;
            min-height: 64px;
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 10px 14% 4px;
            z-index: 1;
        }

        .eq-funnel-tick {
            position: absolute;
            top: -7px;
            left: 50%;
            transform: translateX(-50%);
            width: 14px;
            height: 3px;
            border-radius: 999px;
            background: rgba(0, 37, 204, 0.45);
            z-index: 3;
        }

        .eq-funnel-col.is-active .eq-funnel-tick {
            opacity: 0;
        }

        .eq-funnel-bar {
            width: 100%;
            border-radius: 3px 3px 0 0;
            position: relative;
            z-index: 1;
            min-height: 10px;
            background: linear-gradient(
                180deg,
                #6b85ff 0%,
                #0025cc 36%,
                rgba(0, 37, 204, 0.28) 70%,
                rgba(0, 37, 204, 0) 100%
            );
        }

        .eq-funnel-col.is-active .eq-funnel-bar {
            background: linear-gradient(
                180deg,
                #4c6fff 0%,
                #0025cc 34%,
                rgba(0, 37, 204, 0.4) 68%,
                rgba(0, 37, 204, 0) 100%
            );
        }

        .eq-funnel-slope {
            position: absolute;
            top: 0;
            left: calc(100% - 1px);
            width: 62%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            background: linear-gradient(
                180deg,
                #6b85ff 0%,
                #0025cc 36%,
                rgba(0, 37, 204, 0.28) 70%,
                rgba(0, 37, 204, 0) 100%
            );
            clip-path: polygon(
                0 0,
                100% var(--slope-drop, 0%),
                100% 100%,
                0 100%
            );
            opacity: 0.9;
        }

        .eq-funnel-col.is-active .eq-funnel-slope {
            background: linear-gradient(
                180deg,
                #4c6fff 0%,
                #0025cc 34%,
                rgba(0, 37, 204, 0.4) 68%,
                rgba(0, 37, 204, 0) 100%
            );
            opacity: 0.85;
            clip-path: polygon(
                0 0,
                100% var(--slope-drop, 0%),
                100% 100%,
                0 100%
            );
        }

        .eq-funnel-tip {
            position: absolute;
            left: 50%;
            top: 46%;
            transform: translate(-50%, -50%);
            z-index: 6;
            pointer-events: none;
            white-space: nowrap;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
            color: #111827;
            font-size: 10px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.15s ease;
        }

        .eq-funnel-tip strong {
            font-weight: 700;
        }

        .eq-funnel-tip.is-visible {
            opacity: 1;
        }

        @media (max-width: 720px) {
            .eq-metrics-panel {
                grid-template-columns: 1fr;
            }

            .eq-metrics-cell + .eq-metrics-cell {
                border-left: none;
                border-top: 1px solid #eef0f3;
                padding-top: 12px;
                margin-top: 4px;
            }

            .eq-funnel-cols {
                grid-template-columns: repeat(5, minmax(78px, 1fr));
                overflow-x: auto;
            }

            .eq-funnel-body {
                grid-template-columns: 22px minmax(0, 1fr);
            }

            .eq-funnel-tip {
                font-size: 9px;
                padding: 6px 10px;
            }
        }


        /* =====================================================
        MAINTENANCE HERO — SAVED-MONEY / LIQUID GAUGE STYLE
        ===================================================== */

        .maintenance-hero {
            grid-column: 2;
            grid-row: 2;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            min-width: 0;
            min-height: 0;
            align-self: stretch;
            padding: 16px 18px;
            background: #ffffff;
            border: 1px solid #e8eaed;
            border-radius: 24px;
            box-sizing: border-box;
        }

        .mh-saved-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .mh-saved-title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }

        .mh-saved-title svg {
            width: 16px;
            height: 16px;
            color: #6b7280;
        }

        .mh-saved-expand {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .mh-saved-expand:hover {
            background: #e5e7eb;
        }

        .mh-year-tabs {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .mh-year-tab {
            appearance: none;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #9ca3af;
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }

        .mh-year-tab.is-active {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #111827;
        }

        .mh-saved-main {
            margin-top: 18px;
            flex: 1;
            min-height: 0;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .mh-liquid {
            position: relative;
            width: min(46%, 168px);
            aspect-ratio: 1;
            flex-shrink: 0;
        }

        .mh-liquid svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .mh-liquid-value {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(18px, 2.4vw, 24px);
            font-weight: 700;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
            pointer-events: none;
        }

        .mh-liquid-value.is-dark {
            color: #0025cc;
            text-shadow: none;
        }

        .mh-liquid-legend {
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-width: 0;
        }

        .mh-legend-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 12px;
            font-weight: 500;
            color: #9ca3af;
            line-height: 1.35;
        }

        .mh-legend-swatch {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .mh-legend-swatch.is-solid {
            background: #0025cc;
        }

        .mh-legend-swatch.is-soft {
            background: rgba(0, 37, 204, 0.35);
        }

        .mh-saved-footer {
            margin-top: auto;
            padding-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            font-weight: 500;
            color: #9ca3af;
        }

        .mh-saved-footer strong {
            color: #111827;
            font-weight: 700;
        }


        /* =====================================================
        RESPONSIVE
        ===================================================== */

        /* Fallback when subgrid is unavailable */
        @supports not (grid-template-rows: subgrid) {
            .flow-card {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .eq-metrics-body {
                flex: 1 1 0;
                min-height: 280px;
            }

            .maintenance-hero {
                align-self: end;
            }
        }

        @media (max-width: 1024px) {
            .dashboard-overview-row {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .flow-card {
                grid-column: 1;
                grid-row: auto;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .maintenance-hero {
                grid-column: 1;
                grid-row: auto;
                min-height: 320px;
            }
        }

        @media (max-width: 640px) {
            .maintenance-hero {
                min-height: 0;
            }

            .mh-saved-main {
                flex-direction: column;
                align-items: flex-start;
            }

            .mh-liquid {
                width: 140px;
            }

            .eq-metrics-body {
                min-height: 0;
            }
        }

    </style>

    @include('partials.building-3d.styles')

    <style>

        /* ===================================================== */
        /* EQUIPMENT QR SCANNER MODAL */
        /* ===================================================== */

        .equipment-scanner-modal-card {
            width: min(92vw, 520px);
            max-height: 90vh;
            overflow-y: auto;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
        }


        /* ===================================================== */
        /* SCANNER CAMERA AREA */
        /* ===================================================== */

        .equipment-scanner-camera {
            overflow: hidden;
            width: 100%;
            min-height: 320px;
            background: #0f172a;
            border-radius: 16px;
        }


        /* ===================================================== */
        /* HTML5 QR CODE VIDEO */
        /* ===================================================== */

        #equipmentQrReader video {
            width: 100% !important;
            border-radius: 16px;
        }


        /* ===================================================== */
        /* SCANNER STATUS */
        /* ===================================================== */

        .equipment-scanner-status {
            margin-top: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }


        /* ===================================================== */
        /* EQUIPMENT RESULT */
        /* ===================================================== */

        .equipment-scan-result {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }


        .equipment-scan-field {
            min-width: 0;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }


        .equipment-scan-field-label {
            display: block;
            margin-bottom: 4px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }


        .equipment-scan-field-value {
            display: block;
            overflow: hidden;
            color: #0f172a;
            font-size: 12px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        @media (max-width: 520px) {

            .equipment-scan-result {
                grid-template-columns: 1fr;
            }

        }


    </style>

    <!-- ═══════════════════════════════════════════════════ MAIN -->

    {{-- ===================================================== --}}
    {{-- DASHBOARD MAIN CONTAINER --}}
    {{-- ===================================================== --}}

    <div class="maintenance-dashboard">
        {{-- ===================================================== --}}
        {{-- DASHBOARD TOOLBAR --}}
        {{-- FULL WIDTH ABOVE MAIN GRID --}}
        {{-- ===================================================== --}}

        <!--<div class="dashboard-toolbar flex w-full items-center gap-4">
            {{-- ===================================================== --}}
            {{-- SEARCH --}}
            {{-- TAKES ALL REMAINING WIDTH --}}
            {{-- ===================================================== --}}

            <div class="mb-1 flex items-center gap-2 text-sm text-gray-500">
                <span>Maintenance</span>

                <i data-lucide="chevron-right" class="h-4 w-4"></i>

                <span class="font-medium text-gray-700">
                    {{
                        ucwords(
                            str_replace("-", " ", request()->segment(3) ?? "Dashboard"),
                        )
                    }}
                </span>
            </div>

            {{-- ===================================================== --}}
            {{-- QUICK ACTIONS --}}
            {{-- ===================================================== --}}

            <div
                class="dashboard-toolbar-actions ml-auto flex items-center gap-2"
            >
                {{-- ===================================================== --}}
                {{-- ADD EQUIPMENT --}}
                {{-- ===================================================== --}}

                {{-- ===================================================== --}}
                {{-- ADD EQUIPMENT --}}
                {{-- ===================================================== --}}

                <button type="button" class="button" onclick="openAddEquipmentModal()">
                    <span class="fold"></span>

                    <div class="points_wrapper">
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                        <i class="point"></i>
                    </div>

                    <span class="inner"
                        ><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-plus" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5"/>
                        <path d="m10.273 2.513-.921-.944.715-.698.622.637.89-.011a2.89 2.89 0 0 1 2.924 2.924l-.01.89.636.622a2.89 2.89 0 0 1 0 4.134l-.637.622.011.89a2.89 2.89 0 0 1-2.924 2.924l-.89-.01-.622.636a2.89 2.89 0 0 1-4.134 0l-.622-.637-.89.011a2.89 2.89 0 0 1-2.924-2.924l.01-.89-.636-.622a2.89 2.89 0 0 1 0-4.134l.637-.622-.011-.89a2.89 2.89 0 0 1 2.924-2.924l.89.01.622-.636a2.89 2.89 0 0 1 4.134 0l-.715.698a1.89 1.89 0 0 0-2.704 0l-.92.944-1.32-.016a1.89 1.89 0 0 0-1.911 1.912l.016 1.318-.944.921a1.89 1.89 0 0 0 0 2.704l.944.92-.016 1.32a1.89 1.89 0 0 0 1.912 1.911l1.318-.016.921.944a1.89 1.89 0 0 0 2.704 0l.92-.944 1.32.016a1.89 1.89 0 0 0 1.911-1.912l-.016-1.318.944-.921a1.89 1.89 0 0 0 0-2.704l-.944-.92.016-1.32a1.89 1.89 0 0 0-1.912-1.911z"/>
                        </svg>Equipment</span
                    >
                </button>

                {{-- ===================================================== --}}
                {{-- ADD SCHEDULE --}}
                {{-- ===================================================== --}}

                <button
                    type="button"
                    class="dashboard-quick-action"
                    onclick="openScheduleModal()"
                >
                    <span class="dashboard-quick-action-icon">
                        <i data-lucide="calendar-plus" class="h-4 w-4"></i>
                    </span>

                    <span>Schedule</span>
                </button>


                {{-- ===================================================== --}}
                {{-- ADD BORROWING --}}
                {{-- ===================================================== --}}

                <button
                    type="button"
                    class="dashboard-quick-action"
                    onclick="openBorrowModal()"
                >
                    <span class="dashboard-quick-action-icon">
                        <i data-lucide="clipboard-plus" class="h-4 w-4"></i>
                    </span>

                    <span>Borrowing</span>
                </button>
            </div>
        </div>-->

        {{-- ===================================================== --}}
        {{-- MAIN DASHBOARD GRID --}}
        {{-- LEFT CONTENT + RECENT ACTIVITIES --}}
        {{-- ===================================================== --}}

        <div class="maintenance-dashboard-grid">
            {{-- ===================================================== --}}
            {{-- LEFT MAIN CONTENT --}}
            {{-- ===================================================== --}}

            <main class="maintenance-dashboard-main">
                {{-- ===================================================== --}}
                {{-- MAINTENANCE OPERATIONS HERO --}}
                {{-- ===================================================== --}}

                {{-- ===================================================== --}}
                {{-- 3D CAMPUS / BUILDING OVERVIEW --}}
                {{-- ADD THIS AFTER dashboard-bottom-charts --}}
                {{-- ===================================================== --}}

                

                <div class="dashboard-overview-row">
                    {{-- ===================================================== --}}
                    {{-- PREMIUM FLOW ANALYTICS CARD --}}
                    {{-- ===================================================== --}}

                    @php
                        $metricsDashboard = $metricsDashboard ?? [
                            'period' => 'week',
                            'year' => (int) now()->format('Y'),
                            'periods' => [],
                            'years' => [],
                            'periodLabels' => [
                                'week' => 'Week',
                                'month' => 'Month',
                                'year' => 'Year',
                                'all' => 'All',
                            ],
                        ];
                        $eqAvailable = max(0, (int) $totalEquipment - (int) $underMaintenance - (int) $borrowedEquipment);
                        $eqInventoryBase = max(1, (int) $totalEquipment);
                        $eqAvailablePercent = min(100, round(($eqAvailable / $eqInventoryBase) * 100));
                        $eqMetricCount = 4;
                        $metricsWeek = $metricsDashboard['periods']['all']
                            ?? $metricsDashboard['periods']['week']
                            ?? [
                            'pending' => (int) $pendingReports,
                            'overdue' => (int) $overdueMaintenance,
                            'open' => (int) $pendingReports + (int) $overdueMaintenance,
                            'handled' => 100,
                            'fill_percent' => 22,
                            'wave_y' => 156,
                            'reports' => [],
                        ];
                        $eqOpsCount = (int) ($metricsWeek['open'] ?? ((int) $pendingReports + (int) $overdueMaintenance));
                        $eqDateStrip = [];
                        for ($d = 5; $d >= 0; $d--) {
                            $eqDateStrip[] = now()->copy()->subDays($d);
                        }

                        $eqFormatCompact = function (int $n): string {
                            if ($n >= 1000000) {
                                return rtrim(rtrim(number_format($n / 1000000, 1, '.', ''), '0'), '.') . 'M';
                            }
                            if ($n >= 1000) {
                                return rtrim(rtrim(number_format($n / 1000, 1, '.', ''), '0'), '.') . 'k';
                            }
                            return number_format($n);
                        };

                        $eqBuildFunnel = function (array $stages) use ($eqFormatCompact): array {
                            $values = array_map(fn ($s) => (int) $s['value'], $stages);
                            $max = max(1, ...$values);
                            $axisTop = (int) (ceil($max / 5) * 5);
                            if ($axisTop < 5) {
                                $axisTop = max(5, $max);
                            }
                            $built = [];
                            foreach ($stages as $index => $stage) {
                                $value = (int) $stage['value'];
                                $height = max(10, round(($value / max(1, $axisTop)) * 100));
                                $prev = $index > 0 ? (int) $stages[$index - 1]['value'] : $value;
                                $conversion = $prev > 0 ? (int) round(($value / $prev) * 100) : 100;
                                $dropoff = $conversion - 100;
                                $nextHeight = $index < count($stages) - 1
                                    ? max(10, round(((int) $stages[$index + 1]['value'] / max(1, $axisTop)) * 100))
                                    : $height;
                                $slopeDropPct = $height > 0
                                    ? max(0, round((($height - $nextHeight) / $height) * 100))
                                    : 0;
                                if ($nextHeight > $height) {
                                    $slopeDropPct = 0;
                                }
                                $built[] = [
                                    'label' => $stage['label'],
                                    'value' => $value,
                                    'display' => $eqFormatCompact($value),
                                    'height' => $height,
                                    'conversion' => $conversion,
                                    'dropoff' => $dropoff,
                                    'slope' => $slopeDropPct,
                                    'unit' => $stage['unit'] ?? 'items',
                                ];
                            }
                            $axisLabels = [];
                            for ($i = 0; $i < 5; $i++) {
                                $axisLabels[] = $eqFormatCompact((int) round($axisTop * (1 - ($i / 4))));
                            }

                            $activeIndex = 0;
                            $activeValue = -1;
                            foreach ($built as $index => $stage) {
                                if ((int) $stage['value'] > $activeValue) {
                                    $activeValue = (int) $stage['value'];
                                    $activeIndex = $index;
                                }
                            }

                            return [
                                'stages' => $built,
                                'axis' => $axisLabels,
                                'active_index' => $activeIndex,
                            ];
                        };

                        $eqOperational = max(0, (int) $totalEquipment - (int) $underMaintenance);
                        $eqFunnelEquipment = $eqBuildFunnel([
                            ['label' => 'Total Equipment', 'value' => (int) $totalEquipment, 'unit' => 'items'],
                            ['label' => 'Operational', 'value' => $eqOperational, 'unit' => 'items'],
                            ['label' => 'Available', 'value' => (int) $eqAvailable, 'unit' => 'items'],
                            ['label' => 'Borrowed', 'value' => (int) $borrowedEquipment, 'unit' => 'items'],
                            ['label' => 'Under Maintenance', 'value' => (int) $underMaintenance, 'unit' => 'items'],
                        ]);

                        $eqPendingCount = (int) ($metricsWeek['reports']['pending'] ?? ($reportStatusChart['data'][0] ?? 0));
                        $eqProcessingCount = (int) ($metricsWeek['reports']['processing'] ?? ($reportStatusChart['data'][1] ?? 0));
                        $eqResolvedCount = (int) ($metricsWeek['reports']['resolved'] ?? ($reportStatusChart['data'][2] ?? 0));
                        $eqReplacementCount = (int) ($metricsWeek['reports']['replacement'] ?? ($reportStatusChart['data'][3] ?? 0));
                        $eqRejectedCount = (int) ($metricsWeek['reports']['rejected'] ?? ($reportStatusChart['data'][4] ?? 0));
                        $eqFunnelEquipmentHrefs = [
                            'Total Equipment' => url('/maintenance/equipment/inventory'),
                            'Operational' => url('/maintenance/equipment/inventory'),
                            'Available' => url('/maintenance/equipment/inventory'),
                            'Borrowed' => url('/maintenance/equipment/inventory?status=Borrowed'),
                            'Under Maintenance' => url('/maintenance/equipment/inventory?status=Under Maintenance'),
                        ];
                        $eqFunnelReportHrefs = [
                            'Submitted Reports' => url('/maintenance/reports'),
                            'Accepted Reports' => url('/maintenance/reports/pending'),
                            'In Progress+' => url('/maintenance/reports/processing'),
                            'Closed Outcomes' => url('/maintenance/reports/resolved'),
                            'Resolved' => url('/maintenance/reports/resolved'),
                        ];
                        $eqSubmittedCount = $eqPendingCount + $eqProcessingCount + $eqResolvedCount + $eqReplacementCount + $eqRejectedCount;
                        $eqAcceptedCount = max(0, $eqSubmittedCount - $eqRejectedCount);
                        $eqActionedCount = $eqProcessingCount + $eqResolvedCount + $eqReplacementCount;
                        $eqClosedCount = $eqResolvedCount + $eqReplacementCount;

                        $eqFunnelOperations = $eqBuildFunnel([
                            ['label' => 'Submitted Reports', 'value' => $eqSubmittedCount, 'unit' => 'reports'],
                            ['label' => 'Accepted Reports', 'value' => $eqAcceptedCount, 'unit' => 'reports'],
                            ['label' => 'In Progress+', 'value' => $eqActionedCount, 'unit' => 'reports'],
                            ['label' => 'Closed Outcomes', 'value' => $eqClosedCount, 'unit' => 'reports'],
                            ['label' => 'Resolved', 'value' => $eqResolvedCount, 'unit' => 'reports'],
                        ]);
                    @endphp

                    <div class="flow-card" id="equipmentMetricsCard">
                        <div class="eq-metrics-head">
                        <div class="eq-metrics-top">
                            <h2 class="flow-title">Overview</h2>

                            <div class="eq-metrics-controls relative" id="equipmentStatisticsMenu">
                                <button
                                    type="button"
                                    class="eq-metrics-pill"
                                    id="eqPeriodPill"
                                    aria-label="Overview period"
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-controls="eqPeriodMenu"
                                >
                                    <span id="eqPeriodPillLabel">All</span>
                                    <i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>
                                </button>
                                <div id="eqPeriodMenu" class="eq-period-menu hidden" role="menu" aria-label="Overview period">
                                    @foreach (($metricsDashboard['periodLabels'] ?? ['week' => 'Week', 'month' => 'Month', 'year' => 'Year', 'all' => 'All']) as $periodKey => $periodLabel)
                                        <button
                                            type="button"
                                            class="eq-period-option {{ $periodKey === 'all' ? 'is-active' : '' }}"
                                            role="menuitem"
                                            data-eq-period="{{ $periodKey }}"
                                        >
                                            {{ $periodLabel }}
                                        </button>
                                    @endforeach
                                </div>

                                <button
                                    type="button"
                                    class="eq-metrics-icon-btn"
                                    onclick="toggleEquipmentStatisticsMenu(event)"
                                    aria-label="Equipment statistics options"
                                    aria-expanded="false"
                                    id="equipmentStatisticsMenuButton"
                                >
                                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                                </button>

                                <div
                                    id="equipmentStatisticsDropdown"
                                    class="eq-stats-menu hidden"
                                    role="menu"
                                    aria-label="Equipment shortcuts"
                                >
                                    <div class="eq-stats-menu-list">
                                        <a
                                            href="{{ url('/maintenance/equipment/inventory') }}"
                                            class="eq-stats-menu-item"
                                            role="menuitem"
                                        >
                                            <div class="eq-stats-menu-icon">
                                                <i data-lucide="monitor" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <div>
                                                <div class="eq-stats-menu-title">Equipment Inventory</div>
                                                <div class="eq-stats-menu-desc">View all equipment</div>
                                            </div>
                                        </a>

                                        <a
                                            href="{{ url('/maintenance/equipment/inventory?status=Under Maintenance') }}"
                                            class="eq-stats-menu-item"
                                            role="menuitem"
                                        >
                                            <div class="eq-stats-menu-icon">
                                                <i data-lucide="wrench" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <div>
                                                <div class="eq-stats-menu-title">Under Maintenance</div>
                                                <div class="eq-stats-menu-desc">Equipment requiring service</div>
                                            </div>
                                        </a>

                                        <a
                                            href="{{ url('/maintenance/equipment/inventory?status=Borrowed') }}"
                                            class="eq-stats-menu-item"
                                            role="menuitem"
                                        >
                                            <div class="eq-stats-menu-icon">
                                                <i data-lucide="package-open" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <div>
                                                <div class="eq-stats-menu-title">Borrowed Equipment</div>
                                                <div class="eq-stats-menu-desc">View borrowed equipment</div>
                                            </div>
                                        </a>

                                        <a
                                            href="{{ url('/maintenance/equipment/categories') }}"
                                            class="eq-stats-menu-item"
                                            role="menuitem"
                                        >
                                            <div class="eq-stats-menu-icon">
                                                <i data-lucide="tags" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <div>
                                                <div class="eq-stats-menu-title">Equipment Categories</div>
                                                <div class="eq-stats-menu-desc">Manage categories</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="eq-metrics-tabs" role="tablist" aria-label="Overview sections">
                            <button
                                type="button"
                                class="eq-metrics-tab is-active"
                                data-eq-metrics-tab="equipment"
                                role="tab"
                                aria-selected="true"
                            >
                                <span class="eq-metrics-tab-count">{{ $eqMetricCount }}</span>
                                Equipment
                            </button>

                            <button
                                type="button"
                                class="eq-metrics-tab"
                                data-eq-metrics-tab="operations"
                                role="tab"
                                aria-selected="false"
                            >
                                <span class="eq-metrics-tab-count" id="eqOpsTabCount">{{ min(99, $eqOpsCount) }}</span>
                                Workloads
                            </button>
                        </div>
                        </div>

                        <div class="eq-metrics-body">
                            <div class="eq-metrics-slot-top">
<div class="eq-metrics-panel" data-eq-metrics-panel="equipment">
                            <div class="eq-metrics-cell">
                                <div class="eq-metrics-cell-top">
                                    <div class="eq-metrics-avatars" aria-hidden="true">
                                        <span class="eq-metrics-avatar">
                                            <i data-lucide="wrench" class="h-3 w-3"></i>
                                        </span>
                                        <span class="eq-metrics-avatar">
                                            <i data-lucide="monitor" class="h-3 w-3"></i>
                                        </span>
                                    </div>
                                    <span class="eq-metrics-mini-icon" aria-hidden="true">
                                        <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                                    </span>
                                </div>

                                <div class="eq-metrics-value-row">
                                    <div class="eq-metrics-value">{{ number_format((int) $underMaintenance) }}</div>
                                    <div class="eq-metrics-label">Under Maintenance</div>
                                </div>
                            </div>

                            <div class="eq-metrics-cell">
                                <div class="eq-metrics-cell-top">
                                    <div class="eq-metrics-label is-top">Total Equipment</div>
                                    <span class="eq-metrics-mini-icon is-round" aria-hidden="true">
                                        <i data-lucide="bar-chart-2" class="h-3.5 w-3.5"></i>
                                    </span>
                                </div>

                                <div class="eq-metrics-value">{{ number_format((int) $totalEquipment) }}</div>

                                <div class="eq-goal-meta">
                                    <span>Available</span>
                                    <span>{{ number_format($eqAvailable) }} ready</span>
                                </div>

                                <div class="eq-goal-bar" aria-hidden="true">
                                    <div class="eq-goal-fill" style="width: {{ $eqAvailablePercent }}%"></div>
                                </div>
                            </div>
                        </div>
<div class="eq-metrics-panel is-hidden" data-eq-metrics-panel="operations">
                            <div class="eq-metrics-cell">
                                <div class="eq-metrics-cell-top">
                                    <div class="eq-metrics-avatars" aria-hidden="true">
                                        <span class="eq-metrics-avatar">
                                            <i data-lucide="clipboard-list" class="h-3 w-3"></i>
                                        </span>
                                        <span class="eq-metrics-avatar">
                                            <i data-lucide="alarm-clock" class="h-3 w-3"></i>
                                        </span>
                                    </div>
                                    <span class="eq-metrics-mini-icon" aria-hidden="true">
                                        <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                                    </span>
                                </div>

                                <div class="eq-metrics-value-row">
                                    <div class="eq-metrics-value" id="eqOpsPendingValue">{{ number_format((int) ($metricsWeek['pending'] ?? $pendingReports)) }}</div>
                                    <div class="eq-metrics-label">Pending Reports</div>
                                </div>
                            </div>

                            <div class="eq-metrics-cell">
                                <div class="eq-metrics-cell-top">
                                    <div class="eq-metrics-label is-top">Overdue Maintenance</div>
                                    <span class="eq-metrics-mini-icon is-round" aria-hidden="true">
                                        <i data-lucide="bar-chart-2" class="h-3.5 w-3.5"></i>
                                    </span>
                                </div>

                                <div class="eq-metrics-value" id="eqOpsOverdueValue">{{ number_format((int) ($metricsWeek['overdue'] ?? $overdueMaintenance)) }}</div>

                                <div class="eq-goal-meta">
                                    <span>Workload</span>
                                    <span id="eqOpsOpenLabel">{{ number_format($eqOpsCount) }} open</span>
                                </div>

                                <div class="eq-goal-bar" aria-hidden="true">
                                    @php
                                        $eqOpsMax = max(1, $eqOpsCount);
                                        $eqOverdueShare = min(100, round(((int) ($metricsWeek['overdue'] ?? $overdueMaintenance) / $eqOpsMax) * 100));
                                    @endphp
                                    <div class="eq-goal-fill" id="eqOpsOverdueFill" style="width: {{ $eqOverdueShare }}%"></div>
                                </div>
                            </div>
                        </div>
                            </div>

                            <div class="eq-metrics-slot-bottom">
                                <div class="eq-funnel-card is-hidden" data-eq-metrics-panel="operations" data-eq-funnel>
                                    <div class="eq-funnel-header">
                                        <h3 class="eq-funnel-title">Inventory</h3>
                                        <div class="eq-funnel-more-wrap">
                                            <button type="button" class="eq-funnel-more" data-eq-funnel-menu-btn="inventory" aria-label="Inventory options" aria-expanded="false" aria-controls="eqInventoryFunnelMenu">
                                                <i data-lucide="ellipsis" class="h-4 w-4"></i>
                                            </button>
                                            <div id="eqInventoryFunnelMenu" class="eq-funnel-menu hidden" role="menu">
                                                <a href="{{ url('/maintenance/equipment/inventory') }}">All equipment</a>
                                                <a href="{{ url('/maintenance/equipment/inventory?status=Borrowed') }}">Borrowed</a>
                                                <a href="{{ url('/maintenance/equipment/inventory?status=Under Maintenance') }}">Under maintenance</a>
                                                <a href="{{ url('/maintenance/equipment/categories') }}">Categories</a>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $eqMiniMax = max(0, ...array_map(fn ($stage) => (int) $stage['value'], $eqFunnelEquipment['stages']));
                                    @endphp
                                    <div class="eq-mini-cols">
                                        @foreach ($eqFunnelEquipment['stages'] as $stage)
                                            <button
                                                type="button"
                                                class="eq-mini-col {{ $eqMiniMax > 0 && (int) $stage['value'] === $eqMiniMax ? 'is-peak' : '' }}"
                                                style="--bar-h: {{ $eqMiniMax > 0 ? round(((int) $stage['value'] / $eqMiniMax) * 100) : 0 }}%;"
                                                data-href="{{ $eqFunnelEquipmentHrefs[$stage['label']] ?? url('/maintenance/equipment/inventory') }}"
                                                title="View {{ $stage['label'] }}"
                                                aria-label="{{ $stage['label'] }}: {{ $stage['display'] }}"
                                            >
                                                <span class="eq-mini-plot">
                                                    <span class="eq-mini-bar"><span class="eq-mini-value">{{ $stage['display'] }}</span></span>
                                                </span>
                                                <span class="eq-mini-label">{{ $stage['label'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="eq-funnel-card" data-eq-metrics-panel="equipment" data-eq-funnel>
                                    <div class="eq-funnel-header">
                                        <h3 class="eq-funnel-title">Reports</h3>
                                        <div class="eq-funnel-more-wrap">
                                            <button type="button" class="eq-funnel-more" data-eq-funnel-menu-btn="reports" aria-label="Reports options" aria-expanded="false" aria-controls="eqReportsFunnelMenu">
                                                <i data-lucide="ellipsis" class="h-4 w-4"></i>
                                            </button>
                                            <div id="eqReportsFunnelMenu" class="eq-funnel-menu hidden" role="menu">
                                                <a href="{{ url('/maintenance/reports') }}">All reports</a>
                                                <a href="{{ url('/maintenance/reports/pending') }}">Pending</a>
                                                <a href="{{ url('/maintenance/reports/processing') }}">Processing</a>
                                                <a href="{{ url('/maintenance/reports/resolved') }}">Resolved</a>
                                                <a href="{{ url('/maintenance/schedules') }}">Schedules</a>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $eqMiniMax = max(0, ...array_map(fn ($stage) => (int) $stage['value'], $eqFunnelOperations['stages']));
                                    @endphp
                                    <div class="eq-mini-cols" id="eqReportsFunnelCols">
                                        @foreach ($eqFunnelOperations['stages'] as $stage)
                                            <button
                                                type="button"
                                                class="eq-mini-col {{ $eqMiniMax > 0 && (int) $stage['value'] === $eqMiniMax ? 'is-peak' : '' }}"
                                                style="--bar-h: {{ $eqMiniMax > 0 ? round(((int) $stage['value'] / $eqMiniMax) * 100) : 0 }}%;"
                                                data-href="{{ $eqFunnelReportHrefs[$stage['label']] ?? url('/maintenance/reports') }}"
                                                title="View {{ $stage['label'] }}"
                                                aria-label="{{ $stage['label'] }}: {{ $stage['display'] }}"
                                            >
                                                <span class="eq-mini-plot">
                                                    <span class="eq-mini-bar"><span class="eq-mini-value">{{ $stage['display'] }}</span></span>
                                                </span>
                                                <span class="eq-mini-label">{{ $stage['label'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <section class="maintenance-hero" id="activeWorkloadCard">
                        @php
                            $opsPending = (int) ($metricsWeek['pending'] ?? $pendingReports);
                            $opsOverdue = (int) ($metricsWeek['overdue'] ?? $overdueMaintenance);
                            $opsTotal = (int) ($metricsWeek['open'] ?? ($opsPending + $opsOverdue));
                            $opsFillPercent = (int) ($metricsWeek['fill_percent'] ?? ($opsTotal > 0
                                ? min(92, max(18, round(($opsPending / max(1, $opsTotal)) * 100)))
                                : 22));
                            $opsClearedPercent = (int) ($metricsWeek['handled'] ?? ($opsTotal > 0
                                ? max(0, 100 - round(($opsOverdue / max(1, $opsTotal)) * 100))
                                : 100));
                            $opsWaveY = (float) ($metricsWeek['wave_y'] ?? (200 - (($opsFillPercent / 100) * 200)));
                            $opsYearNow = (int) now()->format('Y');
                            $opsYears = [$opsYearNow, $opsYearNow - 1, $opsYearNow - 2, $opsYearNow - 3];
                        @endphp

                        <div class="mh-saved-header">
                            <h3 class="mh-saved-title">
                                <i data-lucide="clipboard-list"></i>
                                Active Workload
                            </h3>

                            <a
                                href="{{ url('/maintenance/reports/pending') }}"
                                class="mh-saved-expand"
                                aria-label="Open pending reports"
                                title="Open pending reports"
                            >
                                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                            </a>
                        </div>

                        <div class="mh-year-tabs" role="tablist" aria-label="Workload period">
                            @foreach ($opsYears as $opsYear)
                                <button
                                    type="button"
                                    class="mh-year-tab {{ $opsYear === $opsYearNow ? 'is-active' : '' }}"
                                    data-mh-year="{{ $opsYear }}"
                                    aria-pressed="{{ $opsYear === $opsYearNow ? 'true' : 'false' }}"
                                >
                                    {{ $opsYear }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mh-saved-main">
                            <div class="mh-liquid" aria-hidden="true">
                                <svg viewBox="0 0 200 200">
                                    <defs>
                                        <clipPath id="mhLiquidClip">
                                            <circle cx="100" cy="100" r="88" />
                                        </clipPath>
                                    </defs>

                                    <circle
                                        cx="100"
                                        cy="100"
                                        r="92"
                                        fill="none"
                                        stroke="#0025cc"
                                        stroke-width="3"
                                    />

                                    <g clip-path="url(#mhLiquidClip)">
                                        <path
                                            id="mhLiquidWaveSoft"
                                            fill="rgba(0, 37, 204, 0.38)"
                                            d="M0 {{ $opsWaveY - 8 }}
                                               C 35 {{ $opsWaveY - 22 }}, 65 {{ $opsWaveY + 8 }}, 100 {{ $opsWaveY - 4 }}
                                               S 165 {{ $opsWaveY - 20 }}, 200 {{ $opsWaveY - 2 }}
                                               L 200 200 L 0 200 Z"
                                        />
                                        <path
                                            id="mhLiquidWaveSolid"
                                            fill="#0025cc"
                                            d="M0 {{ $opsWaveY + 6 }}
                                               C 40 {{ $opsWaveY - 10 }}, 70 {{ $opsWaveY + 16 }}, 100 {{ $opsWaveY + 2 }}
                                               S 160 {{ $opsWaveY - 12 }}, 200 {{ $opsWaveY + 8 }}
                                               L 200 200 L 0 200 Z"
                                        />
                                    </g>
                                </svg>

                                <div class="mh-liquid-value {{ $opsFillPercent < 48 ? 'is-dark' : '' }}" id="mhLiquidValue">{{ number_format($opsTotal) }}</div>
                            </div>

                            <div class="mh-liquid-legend">
                                <div class="mh-legend-item">
                                    <span class="mh-legend-swatch is-solid"></span>
                                    <span>Pending reports this period</span>
                                </div>
                                <div class="mh-legend-item">
                                    <span class="mh-legend-swatch is-soft"></span>
                                    <span>Overdue maintenance</span>
                                </div>
                            </div>
                        </div>

                        <div class="mh-saved-footer">
                            <span>Overdue items: <strong id="mhOverdueItems">{{ number_format($opsOverdue) }}</strong></span>
                            <span>Handled: <strong id="mhHandledPct">{{ number_format($opsClearedPercent) }}%</strong></span>
                        </div>
                    </section>
                </div>

                @include('partials.big-bar-card', [
                    'title' => 'Inventory',
                    'unit' => 'items',
                    'panel' => 'equipment',
                    'menuId' => 'bigInventoryFunnelMenu',
                    'stages' => collect($eqFunnelEquipment['stages'])->map(fn ($stage) => [
                        'label' => $stage['label'],
                        'value' => $stage['value'],
                        'href' => $eqFunnelEquipmentHrefs[$stage['label']] ?? url('/maintenance/equipment/inventory'),
                    ])->all(),
                    'menuLinks' => [
                        ['label' => 'All equipment', 'href' => url('/maintenance/equipment/inventory')],
                        ['label' => 'Borrowed', 'href' => url('/maintenance/equipment/inventory?status=Borrowed')],
                        ['label' => 'Under maintenance', 'href' => url('/maintenance/equipment/inventory?status=Under Maintenance')],
                        ['label' => 'Categories', 'href' => url('/maintenance/equipment/categories')],
                    ],
                ])

                @include('partials.big-bar-card', [
                    'title' => 'Reports',
                    'unit' => 'reports',
                    'panel' => 'operations',
                    'hidden' => true,
                    'cardId' => 'eqReportsBigBars',
                    'menuId' => 'bigReportsFunnelMenu',
                    'stages' => collect($eqFunnelOperations['stages'])->map(fn ($stage) => [
                        'label' => $stage['label'],
                        'value' => $stage['value'],
                        'href' => $eqFunnelReportHrefs[$stage['label']] ?? url('/maintenance/reports'),
                    ])->all(),
                    'menuLinks' => [
                        ['label' => 'All reports', 'href' => url('/maintenance/reports')],
                        ['label' => 'Pending', 'href' => url('/maintenance/reports/pending')],
                        ['label' => 'Processing', 'href' => url('/maintenance/reports/processing')],
                        ['label' => 'Resolved', 'href' => url('/maintenance/reports/resolved')],
                        ['label' => 'Schedules', 'href' => url('/maintenance/schedules')],
                    ],
                ])

                @include('maintenance-personnel.partials.dashboard-lifecycle-inspections')

                

                {{-- ===================================================== --}}
                {{-- BOTTOM ANALYTICS CHARTS --}}
                {{-- REPORT STATUS + EQUIPMENT CONDITION --}}
                {{-- ===================================================== --}}

                <div class="dashboard-bottom-charts">
                    {{-- ===================================================== --}}
                    {{-- REPORT STATUS --}}
                    {{-- ===================================================== --}}

                    <section class="dashboard-analytics-card">
                        <div class="dashboard-analytics-header">
                            <div>
                                <h2 class="dashboard-analytics-title">
                                    Report Status
                                </h2>

                                <p class="dashboard-analytics-subtitle">Distribution of active maintenance reports</p>
                            </div>
                        </div>

                        <div class="dashboard-small-chart">
                            <canvas id="reportStatusChart"></canvas>
                        </div>
                    </section>

                    {{-- ===================================================== --}}
                    {{-- EQUIPMENT CONDITION — STATISTIC / RADAR STYLE --}}
                    {{-- ===================================================== --}}

                    <section class="dashboard-analytics-card equipment-statistic-card">
                        <div class="equipment-statistic-header">
                            <div class="equipment-statistic-heading">
                                <span class="equipment-statistic-icon" aria-hidden="true">
                                    <i data-lucide="gauge" class="h-4 w-4"></i>
                                </span>

                                <h2 class="equipment-statistic-title">
                                    Equipment Condition
                                </h2>

                                <span
                                    class="equipment-statistic-info"
                                    title="Current inventory condition compared to a healthy target profile"
                                    aria-label="Chart information"
                                >
                                    <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                </span>
                            </div>

                            <div class="equipment-statistic-toolbar">
                                <a
                                    href="{{ url('/maintenance/equipment/inventory') }}"
                                    class="equipment-statistic-tool"
                                    aria-label="Open equipment inventory"
                                    title="Open inventory"
                                >
                                    <i data-lucide="maximize-2" class="h-3.5 w-3.5"></i>
                                </a>

                                <a
                                    href="{{ url('/maintenance/equipment/categories') }}"
                                    class="equipment-statistic-tool"
                                    aria-label="Manage equipment categories"
                                    title="Manage categories"
                                >
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </a>

                                <button
                                    type="button"
                                    class="equipment-statistic-tool"
                                    aria-label="More options"
                                    title="More"
                                >
                                    <i data-lucide="more-horizontal" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="equipment-statistic-panel">
                            <div class="equipment-statistic-chart">
                                <canvas id="equipmentConditionChart"></canvas>
                            </div>
                        </div>

                        <div class="equipment-statistic-legend">
                            <span class="equipment-statistic-legend-item">
                                <span class="equipment-statistic-swatch current" aria-hidden="true"></span>
                                Current
                            </span>
                            <span class="equipment-statistic-legend-item">
                                <span class="equipment-statistic-swatch target" aria-hidden="true"></span>
                                Target
                            </span>
                        </div>
                    </section>
                </div>

                

                

                @include('partials.building-3d.section')
            </main>

            {{-- ===================================================== --}}
            {{-- RIGHT DASHBOARD SIDEBAR --}}
            {{-- ===================================================== --}}

            <aside class="maintenance-dashboard-sidebar">

            {{-- ===================================================== --}}
            {{-- QUICK ACTIONS --}}
            {{-- ===================================================== --}}

            <div
                    class="dashboard-toolbar-actions dashboard-sidebar-quick-actions ml-auto flex items-center gap-2"
                    id="sidebarQuickActionsTrack"
                >
                    {{-- ===================================================== --}}
                    {{-- ADD EQUIPMENT --}}
                    {{-- ===================================================== --}}

                    {{-- ===================================================== --}}
                    {{-- ADD EQUIPMENT --}}
                    {{-- ===================================================== --}}

                    <!--<button type="button" class="button" onclick="openAddEquipmentModal()">
                        <span class="fold"></span>

                        <div class="points_wrapper">
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                            <i class="point"></i>
                        </div>

                        <span class="inner"
                            ><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-patch-plus" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5"/>
                            <path d="m10.273 2.513-.921-.944.715-.698.622.637.89-.011a2.89 2.89 0 0 1 2.924 2.924l-.01.89.636.622a2.89 2.89 0 0 1 0 4.134l-.637.622.011.89a2.89 2.89 0 0 1-2.924 2.924l-.89-.01-.622.636a2.89 2.89 0 0 1-4.134 0l-.622-.637-.89.011a2.89 2.89 0 0 1-2.924-2.924l.01-.89-.636-.622a2.89 2.89 0 0 1 0-4.134l.637-.622-.011-.89a2.89 2.89 0 0 1 2.924-2.924l.89.01.622-.636a2.89 2.89 0 0 1 4.134 0l-.715.698a1.89 1.89 0 0 0-2.704 0l-.92.944-1.32-.016a1.89 1.89 0 0 0-1.911 1.912l.016 1.318-.944.921a1.89 1.89 0 0 0 0 2.704l.944.92-.016 1.32a1.89 1.89 0 0 0 1.912 1.911l1.318-.016.921.944a1.89 1.89 0 0 0 2.704 0l.92-.944 1.32.016a1.89 1.89 0 0 0 1.911-1.912l-.016-1.318.944-.921a1.89 1.89 0 0 0 0-2.704l-.944-.92.016-1.32a1.89 1.89 0 0 0-1.912-1.911z"/>
                            </svg>Equipment</span
                        >
                    </button>-->

                    <button
                        type="button"
                        class="dashboard-quick-action"
                        onclick="openAddEquipmentModal()"
                    >
                        <span class="dashboard-quick-action-icon">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                        </span>

                        <span>Equipment</span>
                    </button>

                    <button
                        type="button"
                        class="dashboard-quick-action"
                        onclick="openBatchAddEquipmentModal()"
                    >
                        <span class="dashboard-quick-action-icon">
                            <i data-lucide="layers" class="h-4 w-4"></i>
                        </span>

                        <span>Batch add</span>
                    </button>

                    {{-- ===================================================== --}}
                    {{-- ADD SCHEDULE --}}
                    {{-- ===================================================== --}}

                    <button
                        type="button"
                        class="dashboard-quick-action"
                        onclick="openScheduleModal()"
                    >
                        <span class="dashboard-quick-action-icon">
                            <i data-lucide="calendar-plus" class="h-4 w-4"></i>
                        </span>

                        <span>Schedule</span>
                    </button>


                    {{-- ===================================================== --}}
                    {{-- ADD BORROWING --}}
                    {{-- ===================================================== --}}

                    <button
                        type="button"
                        class="dashboard-quick-action"
                        onclick="openBorrowModal()"
                    >
                        <span class="dashboard-quick-action-icon">
                            <i data-lucide="clipboard-plus" class="h-4 w-4"></i>
                        </span>

                        <span>Borrowing</span>
                    </button>

                    <a
                        href="{{ url('/maintenance/semester-inspections') }}"
                        class="dashboard-quick-action"
                    >
                        <span class="dashboard-quick-action-icon">
                            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                        </span>
                        <span>Semester check</span>
                    </a>

                    <a
                        href="{{ url('/maintenance/replacement-suggestions') }}"
                        class="dashboard-quick-action"
                    >
                        <span class="dashboard-quick-action-icon">
                            <i data-lucide="hourglass" class="h-4 w-4"></i>
                        </span>
                        <span>Replacements</span>
                    </a>
                </div>

                

                

                <!-- ══ URGENT REPORTS PIPELINE ══ -->
                {{-- ===================================================== --}}
                {{-- URGENT REPORTS PIPELINE --}}
                {{-- MODERN MEDIA CARD CAROUSEL --}}
                {{-- ===================================================== --}}

                <section class="urgent-pipeline-section">
                    {{-- ===================================================== --}}
                    {{-- SECTION HEADER --}}
                    {{-- ===================================================== --}}

                    {{-- ===================================================== --}}
                    {{-- URGENT REPORTS HEADER --}}
                    {{-- ===================================================== --}}

                    <div class="urgent-pipeline-header">

                        {{-- ========================================= --}}
                        {{-- LEFT SIDE: TITLE --}}
                        {{-- ========================================= --}}

                        <div>
                            <h2 class="urgent-pipeline-title">
                                5 Latest Urgent Reports
                            </h2>

                            <p class="urgent-pipeline-description">
                                Track <span class="font-semibold text-red-500">
                                    {{ $urgentReports }}
                                </span> active urgent issues in real time
                            </p>
                        </div>


                        {{-- ========================================= --}}
                        {{-- RIGHT SIDE: COUNT + CAROUSEL CONTROLS --}}
                        {{-- ========================================= --}}

                        <div class="flex items-center gap-3">

                            


                            {{-- ===================================== --}}
                            {{-- CAROUSEL CONTROLS --}}
                            {{-- ===================================== --}}

                            <div class="urgent-pipeline-controls">

                                <button
                                    type="button"
                                    id="urgent-carousel-prev"
                                    onclick="scrollUrgentCarousel(-1)"
                                    class="urgent-carousel-button"
                                    aria-label="Previous urgent reports"
                                >
                                    <i
                                        data-lucide="chevron-left"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                                <button
                                    type="button"
                                    id="urgent-carousel-next"
                                    onclick="scrollUrgentCarousel(1)"
                                    class="urgent-carousel-button urgent-carousel-button-active"
                                    aria-label="Next urgent reports"
                                >
                                    <i
                                        data-lucide="chevron-right"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                            </div>

                        </div>

                    </div>

                    

                    {{-- ===================================================== --}}
                    {{-- CAROUSEL --}}
                    {{-- ===================================================== --}}

                    <div
                        id="urgent-carousel"
                        class="urgent-media-carousel scroll-hide"
                    >
                        @forelse ($urgentReportList as $report)
                            @php
                                // =================================================
                                // REPORT TITLE
                                // =================================================

                                $reportTitle =
                                    $report->equipment_name ??
                                    ($report->report_unlisted_equipment_name ?? "Reported Issue");

                                // =================================================
                                // REPORTER INITIALS
                                // =================================================

                                $initials = collect(
                                    explode(" ", $report->reporter_full_name ?? "Unknown Reporter"),
                                )
                                    ->filter()
                                    ->take(2)
                                    ->map(fn($name) => strtoupper(substr($name, 0, 1)))
                                    ->implode("");

                                // =================================================
                                // STATUS CLASS
                                // =================================================

                                $statusClass = match ($report->report_current_status) {
                                    "Processing" => "processing",

                                    "For Replacement" => "replacement",

                                    default => "pending",
                                };
                            @endphp

                            {{-- ================================================= --}}
                            {{-- REPORT CARD --}}
                            {{-- ================================================= --}}

                            <article class="urgent-media-card">
                                {{-- ================================================= --}}
                                {{-- MEDIA AREA --}}
                                {{-- ================================================= --}}

                                {{-- ================================================= --}}
                                {{-- CARD CONTENT --}}
                                {{-- ================================================= --}}

                                <div class="urgent-media-content">
                                    {{-- ================================================= --}}
                                    {{-- CATEGORY AND DATE --}}
                                    {{-- ================================================= --}}

                                    <div class="urgent-media-meta">
                                        <span class="urgent-media-category">
                                            <i
                                                data-lucide="siren"
                                                class="h-3 w-3"
                                            ></i>

                                            Urgent
                                        </span>

                                        <span class="urgent-media-time">
                                            {{
                                                \Carbon\Carbon::parse(
                                                    $report->report_submitted_at,
                                                )->diffForHumans()
                                            }}
                                        </span>
                                    </div>

                                    {{-- ================================================= --}}
                                    {{-- TITLE --}}
                                    {{-- ================================================= --}}

                                    <a
                                        href="{{ url(
                                            '/maintenance/reports/details/'
                                            . $report->report_id
                                        ) }}"
                                        class="urgent-media-title"
                                    >
                                        {{ $reportTitle }}
                                    </a>

                                    {{-- ================================================= --}}
                                    {{-- LOCATION --}}
                                    {{-- ================================================= --}}

                                    <div class="urgent-media-location">
                                        <i
                                            data-lucide="map-pin"
                                            class="h-3.5 w-3.5"
                                        ></i>

                                        <span>
                                            {{
                                                $report->room_name ??
                                                    "No room assigned"
                                            }}

                                            @if ($report->floor_level)
                                                · {{ $report->floor_level }}

                                            @endif
                                        </span>
                                    </div>

                                    {{-- ================================================= --}}
                                    {{-- STATUS ACCENT LINE --}}
                                    {{-- ================================================= --}}

                                    <div
                                        class="
                                            urgent-media-progress
                                            {{ $statusClass }}
                                        "
                                    ></div>

                                    {{-- ================================================= --}}
                                    {{-- REPORTER --}}
                                    {{-- ================================================= --}}

                                    <div class="urgent-media-footer">
                                        <div class="urgent-media-reporter">
                                            <div class="urgent-media-avatar">
                                                {{ $initials }}
                                            </div>

                                            <div
                                                class="urgent-media-reporter-info"
                                            >
                                                <span
                                                    class="urgent-media-reporter-name"
                                                >
                                                    {{
                                                        $report->reporter_full_name ??
                                                            "Unknown Reporter"
                                                    }}
                                                </span>

                                                <span
                                                    class="urgent-media-reporter-label"
                                                >
                                                    Reporter
                                                </span>
                                            </div>
                                        </div>

                                        {{-- ===================================================== --}}
                                        {{-- PREPARE URGENT REPORT QUICK VIEW DATA --}}
                                        {{-- ===================================================== --}}

                                        @php
                                            // =====================================================
                                            // PREPARE URGENT REPORT QUICK VIEW DATA
                                            // =====================================================

                                            $urgentReportModalData = [

                                                'id' =>
                                                    $report->report_id,

                                                'title' =>
                                                    $report->equipment_name
                                                    ?? $report->report_unlisted_equipment_name
                                                    ?? 'Reported Issue',

                                                'status' =>
                                                    $report->report_current_status,

                                                'urgency' =>
                                                    $report->report_urgency_level,

                                                // =====================================================
                                                // REPORT ISSUE INFORMATION
                                                // =====================================================

                                                'description' =>
                                                    $report->report_problem_description,

                                                'suggested_issue' =>
                                                    $report->report_suggested_issue,

                                                // =====================================================
                                                // LOCATION INFORMATION
                                                // =====================================================

                                                'room' =>
                                                    $report->room_name,

                                                'floor' =>
                                                    $report->floor_level,

                                                'building' =>
                                                    $report->building_name,

                                                // =====================================================
                                                // EQUIPMENT INFORMATION
                                                // =====================================================

                                                'equipment' =>
                                                    $report->equipment_name
                                                    ?? $report->report_unlisted_equipment_name,

                                                // =====================================================
                                                // REPORTER INFORMATION
                                                // =====================================================

                                                'reporter' =>
                                                    $report->reporter_full_name,

                                                'employee_id' =>
                                                    $report->report_reporter_employee_id,

                                                // =====================================================
                                                // DATE
                                                // =====================================================

                                                'submitted_at' =>
                                                    $report->report_submitted_at,

                                                // =====================================================
                                                // EVIDENCE IMAGE
                                                // =====================================================

                                                'image' =>
                                                    $report->report_uploaded_image
                                                        ? asset(
                                                            'storage/'
                                                            . $report->report_uploaded_image
                                                        )
                                                        : null,

                                                // =====================================================
                                                // FULL REPORT PAGE
                                                // =====================================================

                                                'url' =>
                                                    url(
                                                        '/maintenance/reports/details/'
                                                        . $report->report_id
                                                    ),
                                            ];
                                        @endphp


                                        {{-- ===================================================== --}}
                                        {{-- QUICK VIEW URGENT REPORT BUTTON --}}
                                        {{-- ===================================================== --}}

                                        <button
                                            type="button"
                                            class="urgent-media-view"
                                            aria-label="Quick view report"
                                            onclick='openUrgentReportModal(@json($urgentReportModalData))'
                                        >
                                            <i
                                                data-lucide="arrow-up-right"
                                                class="h-4 w-4"
                                            ></i>
                                        </button>
                                    </div>
                                </div>
                            </article>

                        @empty
                            {{-- ================================================= --}}
                            {{-- EMPTY STATE --}}
                            {{-- ================================================= --}}

                            <div class="urgent-media-empty">
                                <i
                                    data-lucide="check-circle-2"
                                    class="h-7 w-7"
                                ></i>

                                <strong> No active urgent reports </strong>

                                <span>
                                    New urgent maintenance issues will appear
                                    here.
                                </span>
                            </div>

                        @endforelse
                    </div>
                </section>

                {{-- ===================================================== --}}
                {{-- MAINTENANCE SCHEDULE WORKLOAD --}}
                {{-- ===================================================== --}}

                <section class="dashboard-analytics-card">
                    <div class="dashboard-analytics-header">
                        <div>
                            <h2 class="dashboard-analytics-title">
                                Maintenance Schedule Workload
                            </h2>

                            <p class="dashboard-analytics-subtitle">Scheduled maintenance workload for the next 30 days</p>
                        </div>

                        {{-- ================================================= --}}
                        {{-- TOTAL SCHEDULED MAINTENANCE --}}
                        {{-- ================================================= --}}

                        <div class="activity-chart-total">
                            {{
                                array_sum(
                                    $maintenanceWorkloadData,
                                )
                            }}

                            <span> scheduled tasks </span>
                        </div>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- CHART --}}
                    {{-- ===================================================== --}}

                    <div class="dashboard-report-activity-chart">
                        <canvas id="maintenanceWorkloadChart"></canvas>
                    </div>
                </section>

                {{-- ===================================================== --}}
                {{-- MAINTENANCE CALENDAR --}}
                {{-- ADD THIS ABOVE THE EXISTING ACTIVITY CARD --}}
                {{-- ===================================================== --}}

                <div
                    id="dashboardCalendar"
                    class="dashboard-calendar-card"
                    data-events='@json($calendarEvents)'
                >
                    {{-- ================================================= --}}
                    {{-- CALENDAR HEADER --}}
                    {{-- ================================================= --}}

                    <div class="dashboard-calendar-header">
                        <div>
                            <h2 class="dashboard-calendar-title">
                                Maintenance Calendar
                            </h2>

                            <p class="dashboard-calendar-subtitle">Reports and scheduled maintenance</p>
                        </div>

                        <div class="dashboard-calendar-header-icon">
                            <i data-lucide="calendar-days" class="h-4 w-4"></i>
                        </div>
                    </div>

                    {{-- ================================================= --}}
                    {{-- CALENDAR BODY --}}
                    {{-- ================================================= --}}

                    <div class="dashboard-calendar-body">
                        {{-- ================================================= --}}
                        {{-- CURRENT MONTH --}}
                        {{-- ================================================= --}}

                        <div class="dashboard-calendar-month-row">
                            <div
                                id="calendarMonthLabel"
                                class="dashboard-calendar-month"
                            ></div>
                        </div>

                        {{-- ================================================= --}}
                        {{-- WEEKDAY LABELS --}}
                        {{-- ================================================= --}}

                        <div class="calendar-weekdays">
                            <div>Sun</div>

                            <div>Mon</div>

                            <div>Tue</div>

                            <div>Wed</div>

                            <div>Thu</div>

                            <div>Fri</div>

                            <div>Sat</div>
                        </div>

                        {{-- ================================================= --}}
                        {{-- CALENDAR DAYS --}}
                        {{-- FILLED BY YOUR EXISTING JAVASCRIPT --}}
                        {{-- ================================================= --}}

                        <div id="calendarDays" class="calendar-days"></div>

                        {{-- ================================================= --}}
                        {{-- SELECTED DATE EVENTS --}}
                        {{-- FILLED BY YOUR EXISTING JAVASCRIPT --}}
                        {{-- ================================================= --}}

                        <div
                            id="calendarSelectedEvents"
                            class="calendar-selected-events"
                        ></div>
                    </div>
                </div>

                

                {{-- ===================================================== --}}
                {{-- ACTIVITY SIDEBAR CARD --}}
                {{-- NOTHING BELOW THIS WAS REMOVED --}}
                {{-- ===================================================== --}}

                <div class="activity-sidebar-card">
                    {{-- ===================================================== --}}
                    {{-- HEADER --}}
                    {{-- ===================================================== --}}

                    <div class="activity-sidebar-header">
                        <h2 class="activity-sidebar-heading">Activity</h2>

                        {{-- ===================================================== --}}
                        {{-- ACTIVITY OPTIONS --}}
                        {{-- ===================================================== --}}

                        <div class="relative">

                            <button
                                type="button"
                                class="activity-sidebar-menu"
                                aria-label="Activity options"
                                onclick="toggleActivityOptions(event)"
                            >
                                <i data-lucide="more-vertical" class="h-3 w-3"></i>
                            </button>


                            {{-- ================================================= --}}
                            {{-- ACTIVITY OPTIONS DROPDOWN --}}
                            {{-- ================================================= --}}

                            <div
                                id="activityOptionsDropdown"
                                class="absolute right-0 top-full z-50 mt-1.5 hidden w-48 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg"
                            >

                                {{-- ============================================= --}}
                                {{-- REFRESH ACTIVITY --}}
                                {{-- ============================================= --}}

                                <button
                                    type="button"
                                    onclick="refreshDashboardActivity()"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition hover:bg-gray-50"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-500"
                                    >
                                        <i
                                            data-lucide="refresh-cw"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </span>

                                    <span class="text-xs font-semibold text-gray-700">
                                        Refresh Activity
                                    </span>
                                </button>


                                {{-- ============================================= --}}
                                {{-- ACTIVITY FILTERS --}}
                                {{-- ============================================= --}}

                                <a
                                    href="{{ route('maintenance.activities.index') }}#activity-filters"
                                    class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition hover:bg-gray-50"
                                >
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gray-100 text-gray-500"
                                    >
                                        <i
                                            data-lucide="list-filter"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </span>

                                    <span class="flex min-w-0 flex-1 items-center justify-between gap-2">

                                        <span class="text-xs font-semibold text-gray-700">
                                            Activity Filters
                                        </span>

                                        <i
                                            data-lucide="chevron-right"
                                            class="h-3.5 w-3.5 text-gray-400"
                                        ></i>

                                    </span>
                                </a>

                            </div>

                        </div>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- ACTIVITY SUMMARY --}}
                    {{-- SIMILAR TO STATISTIC SECTION IN REFERENCE --}}
                    {{-- ===================================================== --}}

                    <div class="activity-overview">
                        <div class="activity-overview-icon">
                            <div class="activity-overview-icon-inner">
                                <i data-lucide="activity" class="h-6 w-6"></i>
                            </div>
                        </div>

                        <div class="activity-overview-title">
                            Maintenance Activity
                        </div>

                        <div class="activity-overview-description">
                            Latest maintenance events across PaAyo
                        </div>

                        {{-- ===================================================== --}}
                        {{-- MONTHLY REPORT ACTIVITY CHART --}}
                        {{-- ===================================================== --}}

                        <div class="activity-chart-card">
                            {{-- ================================================= --}}
                            {{-- CHART HEADER --}}
                            {{-- ================================================= --}}

                            <div class="activity-chart-header">
                                <div>
                                    <div class="activity-chart-title">
                                        Monthly Report Activity
                                    </div>

                                    <div class="activity-chart-subtitle">
                                        {{
                                            now()->format(
                                                "F Y",
                                            )
                                        }}
                                    </div>
                                </div>

                                {{-- ================================================= --}}
                                {{-- TOTAL REPORTS THIS MONTH --}}
                                {{-- ================================================= --}}

                                <div class="activity-chart-total">
                                    {{
                                        array_sum(
                                            $reportActivityChart,
                                        )
                                    }}

                                    <span>reports</span>
                                </div>
                            </div>

                            {{-- ================================================= --}}
                            {{-- CHART --}}
                            {{-- ================================================= --}}

                            <div class="activity-chart-container">
                                <canvas id="reportActivityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- RECENT ACTIVITIES HEADER --}}
                    {{-- ===================================================== --}}

                    <div class="activity-list-heading">
                        <div>
                            <h3>Recent Activities</h3>

                            <p>Latest system events</p>
                        </div>

                        <button
                            type="button"
                            class="activity-list-add"
                            aria-label="View recent activity history"
                            onclick="openActivityPreviewModal()"
                        >
                            <i data-lucide="history" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- ACTIVITY LIST --}}
                    {{-- ===================================================== --}}

                    <div class="activity-list-panel">
                        @forelse ($recentActivities->take(5) as $activity)
                            <div class="activity-list-item">
                                {{-- ================================================= --}}
                                {{-- ICON --}}
                                {{-- ================================================= --}}

                                <div
                                    class="activity-list-icon"
                                    style="
                            background: {{ $activity->background }};
                            color: {{ $activity->color }};
                        "
                                >
                                    <i
                                        data-lucide="{{ $activity->icon }}"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                                {{-- ================================================= --}}
                                {{-- INFORMATION --}}
                                {{-- ================================================= --}}

                                <div class="activity-list-content">
                                    <div class="activity-list-title">
                                        {{ $activity->title }}
                                    </div>

                                    <div class="activity-list-description">
                                        {{
                                            Str::limit(
                                                $activity->description,
                                                42,
                                            )
                                        }}
                                    </div>

                                    <div class="activity-list-time">
                                        {{
                                            \Carbon\Carbon::parse(
                                                $activity->created_at,
                                            )->diffForHumans()
                                        }}
                                    </div>
                                </div>

                                {{-- ================================================= --}}
                                {{-- VIEW BUTTON --}}
                                {{-- ================================================= --}}

                                @if ($activity->url)

                                    <a
                                        href="{{ $activity->url }}"
                                        class="activity-list-view"
                                        aria-label="View activity details"
                                    >
                                        View
                                    </a>

                                @else

                                    <span
                                        class="activity-list-view opacity-40 cursor-default"
                                        aria-label="No destination available"
                                    >
                                        View
                                    </span>

                                @endif
                            </div>

                        @empty
                            <div class="activity-empty-state">
                                <i data-lucide="activity" class="h-6 w-6"></i>

                                <p>No recent activities.</p>
                            </div>

                        @endforelse
                    </div>

                    {{-- ===================================================== --}}
                    {{-- FOOTER --}}
                    {{-- ===================================================== --}}

                    <div class="activity-sidebar-footer">

                        <a href="{{ route('maintenance.activities.index') }}">
                            View All Activities
                        </a>

                    </div>
                </div>
            </aside>
        </div>
    </div>

    @include('maintenance-personnel.equipment.partials.add-equipment-wizard', ['isStockPage' => true, 'wizardTitle' => 'Add equipment'])
    @include('maintenance-personnel.equipment.partials.batch-add-wizard')


<div
    id="scheduleModal"
    x-data="scheduleEquipmentCart(@js($scheduleEquipmentJson ?? []))"
    x-cloak
    class="fixed inset-0 z-[1300] hidden items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
    @keydown.escape.window="if (!$el.classList.contains('hidden')) closeScheduleModal()"
>
    <div class="my-auto flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Schedule maintenance</h2>
                <p class="mt-1 text-sm text-slate-500">Search and add multiple QR-tagged equipment in one go.</p>
            </div>
            <button
                type="button"
                onclick="closeScheduleModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form
            action="/maintenance/schedules/store"
            method="POST"
            class="flex min-h-0 flex-1 flex-col"
            @submit="prepareSubmit($event)"
        >
            @csrf
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment <span class="text-red-500">*</span></p>
                            <p class="mt-1 text-sm text-slate-500">Type to search, then add each asset to this schedule batch.</p>
                        </div>
                        <p class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80" x-text="cart.length ? (cart.length + ' selected') : 'None selected'"></p>
                    </div>

                    <div class="relative" @click.outside="open = false">
                        <label class="mb-1.5 block text-sm text-slate-600">Find equipment</label>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                x-model="query"
                                @focus="open = true"
                                @input="open = true"
                                @keydown.arrow-down.prevent="move(1)"
                                @keydown.arrow-up.prevent="move(-1)"
                                @keydown.enter.prevent="addHighlighted()"
                                placeholder="Search by name, room, QR, or asset tag"
                                class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                autocomplete="off"
                            >
                        </div>

                        <div
                            x-show="open"
                            x-cloak
                            class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                        >
                            <div class="max-h-64 overflow-y-auto py-1">
                                <template x-if="filtered.length === 0">
                                    <p class="px-3 py-4 text-sm text-slate-400">No matching schedulable equipment.</p>
                                </template>
                                <template x-for="(item, index) in filtered" :key="item.id">
                                    <button
                                        type="button"
                                        @click="addItem(item)"
                                        class="flex w-full items-start gap-3 px-3 py-2.5 text-left transition"
                                        :class="index === highlight ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50'"
                                    >
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                            <i data-lucide="wrench" class="h-4 w-4"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-slate-900" x-text="item.name"></span>
                                            <span class="mt-0.5 block truncate text-xs text-slate-500" x-text="meta(item)"></span>
                                        </span>
                                        <span class="shrink-0 text-[11px] font-semibold text-[#0025cc]">Add</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Only equipment with a generated QR code can be scheduled.</p>
                        <p x-show="pickerError" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="pickerError"></p>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                        <template x-if="cart.length === 0">
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm font-medium text-slate-700">No equipment added</p>
                                <p class="mt-1 text-xs text-slate-400">Search above to add one or many assets to this maintenance batch.</p>
                            </div>
                        </template>
                        <template x-if="cart.length > 0">
                            <div class="divide-y divide-slate-100">
                                <template x-for="(line, index) in cart" :key="line.id">
                                    <div class="flex items-center gap-3 px-4 py-3">
                                        <input type="hidden" :name="'equipment_ids[' + index + ']'" :value="line.id">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-slate-900" x-text="line.name"></p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500" x-text="meta(line)"></p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeLine(index)"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50"
                                            aria-label="Remove equipment"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    <p x-show="cartError" x-cloak class="text-xs font-medium text-rose-600" x-text="cartError"></p>
                </div>

                <div>
                    <label for="scheduleTitle" class="mb-1.5 block text-sm text-slate-600">Title <span class="text-red-500">*</span></label>
                    <input
                        id="scheduleTitle"
                        type="text"
                        name="title"
                        placeholder="Quarterly inspection"
                        required
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    />
                </div>

                <div>
                    <label for="scheduleDescription" class="mb-1.5 block text-sm text-slate-600">
                        Description <span class="text-slate-400">(optional)</span>
                    </label>
                    <textarea
                        id="scheduleDescription"
                        name="description"
                        rows="3"
                        placeholder="Notes or instructions"
                        class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    ></textarea>
                </div>

                <div>
                    <label for="scheduleFrequency" class="mb-1.5 block text-sm text-slate-600">Frequency <span class="text-red-500">*</span></label>
                    <select
                        id="scheduleFrequency"
                        name="frequency"
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    >
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Semi Annual">Semi annual</option>
                        <option value="Annual">Annual</option>
                    </select>
                </div>

                <div x-data="scheduleNextDatePicker()" class="space-y-2.5">
                    <label class="block text-sm text-slate-600">Next date <span class="text-red-500">*</span></label>
                    <input id="scheduleNextDate" type="hidden" name="next_date" x-model="value" />

                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3.5 py-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Scheduled for</p>
                            <p class="truncate text-sm font-semibold text-slate-900" x-text="display"></p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <button
                                type="button"
                                @click="prevMonth()"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                aria-label="Previous month"
                            >
                                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                            </button>
                            <p class="text-sm font-semibold text-slate-900" x-text="monthLabel"></p>
                            <button
                                type="button"
                                @click="nextMonth()"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                aria-label="Next month"
                            >
                                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <div class="mb-1.5 grid grid-cols-7">
                            <template x-for="day in weekdays" :key="day">
                                <div class="py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="day"></div>
                            </template>
                        </div>

                        <div class="grid grid-cols-7 gap-y-1">
                            <template x-for="day in days" :key="day.iso + (day.outside ? '-out' : '')">
                                <button
                                    type="button"
                                    @click="pick(day)"
                                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm transition"
                                    :class="day.selected
                                        ? 'bg-[#0025cc] font-semibold text-white shadow-sm shadow-[#0025cc]/25'
                                        : day.isToday
                                            ? 'font-semibold text-[#0025cc] ring-1 ring-[#0025cc]/30'
                                            : day.outside
                                                ? 'text-slate-300 hover:bg-slate-50 hover:text-slate-500'
                                                : 'text-slate-700 hover:bg-slate-100'"
                                    x-text="day.d"
                                ></button>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                            <button
                                type="button"
                                @click="clearDate()"
                                class="rounded-lg px-2 py-1 text-xs font-medium text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                            >
                                Clear
                            </button>
                            <button
                                type="button"
                                @click="goToday()"
                                class="rounded-lg px-2.5 py-1 text-xs font-semibold text-[#0025cc] transition hover:bg-[#0025cc]/5"
                            >
                                Today
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 px-6 pb-6">
                <button
                    type="button"
                    onclick="closeScheduleModal()"
                    class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                    x-text="cart.length ? ('Create ' + cart.length + ' schedule' + (cart.length === 1 ? '' : 's')) : 'Create schedule'"
                ></button>
            </div>
        </form>
    </div>
</div>

    <!-- ===================================================== -->
    <!-- BORROW MODAL -->
    <!-- ===================================================== -->

    <div
        id="borrowModal"
        x-data="borrowEquipmentCart(@js($borrowableEquipmentJson ?? []))"
        x-cloak
        class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
        @keydown.escape.window="if (!$el.classList.contains('hidden')) closeBorrowModal()"
    >
        <div class="my-auto flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-start justify-between gap-4 px-6 pt-6">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold tracking-tight text-slate-900">Borrow equipment</h2>
                    <p class="mt-1 text-sm text-slate-500">Search and add multiple items for one borrower in a single record set.</p>
                </div>
                <button
                    type="button"
                    onclick="closeBorrowModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form
                method="POST"
                action="/maintenance/borrowing/store"
                class="flex min-h-0 flex-1 flex-col"
                @submit="prepareSubmit($event)"
            >
                @csrf

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment cart <span class="text-red-500">*</span></p>
                                <p class="mt-1 text-sm text-slate-500">Type to search, then add quantity for each equipment line.</p>
                            </div>
                            <p class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80" x-text="cart.length ? (cart.length + ' line' + (cart.length === 1 ? '' : 's') + ' · ' + totalQty + ' pcs') : 'No items yet'"></p>
                        </div>

                        <div class="relative" @click.outside="open = false">
                            <label class="mb-1.5 block text-sm text-slate-600">Find equipment</label>
                            <div class="flex gap-2">
                                <div class="relative min-w-0 flex-1">
                                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                                    <input
                                        type="text"
                                        x-model="query"
                                        @focus="open = true"
                                        @input="open = true"
                                        @keydown.arrow-down.prevent="move(1)"
                                        @keydown.arrow-up.prevent="move(-1)"
                                        @keydown.enter.prevent="selectHighlighted()"
                                        placeholder="Search by name, room, or asset tag"
                                        class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                        autocomplete="off"
                                    >
                                </div>
                                <input
                                    type="number"
                                    min="1"
                                    :max="selected?.available || 1"
                                    x-model.number="addQty"
                                    class="h-11 w-24 rounded-xl border-0 bg-white px-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                    title="Quantity to add"
                                >
                                <button
                                    type="button"
                                    @click="addSelected()"
                                    class="inline-flex h-11 shrink-0 items-center rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]"
                                >
                                    Add
                                </button>
                            </div>

                            <div
                                x-show="open"
                                x-cloak
                                class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div class="max-h-64 overflow-y-auto py-1">
                                    <template x-if="filtered.length === 0">
                                        <p class="px-3 py-4 text-sm text-slate-400">No matching borrowable equipment.</p>
                                    </template>
                                    <template x-for="(item, index) in filtered" :key="item.id">
                                        <button
                                            type="button"
                                            @click="choose(item)"
                                            class="flex w-full items-start gap-3 px-3 py-2.5 text-left transition"
                                            :class="index === highlight ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50'"
                                        >
                                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                <i data-lucide="package" class="h-4 w-4"></i>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-medium text-slate-900" x-text="item.name"></span>
                                                <span class="mt-0.5 block truncate text-xs text-slate-500" x-text="meta(item)"></span>
                                            </span>
                                            <span class="shrink-0 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700" x-text="item.available + ' avail'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p x-show="selected" x-cloak class="mt-2 text-xs text-slate-500">
                                Selected: <span class="font-medium text-slate-800" x-text="selected?.name"></span>
                                <span x-text="' · up to ' + (selected?.available || 0) + ' available'"></span>
                            </p>
                            <p x-show="pickerError" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="pickerError"></p>
                        </div>

                        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <template x-if="cart.length === 0">
                                <div class="px-4 py-8 text-center">
                                    <p class="text-sm font-medium text-slate-700">Cart is empty</p>
                                    <p class="mt-1 text-xs text-slate-400">Add chairs, tables, and other items here before creating the borrow.</p>
                                </div>
                            </template>
                            <template x-if="cart.length > 0">
                                <div class="divide-y divide-slate-100">
                                    <template x-for="(line, index) in cart" :key="line.id">
                                        <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                                            <input type="hidden" :name="'items[' + index + '][equipment_id]'" :value="line.id">
                                            <input type="hidden" :name="'items[' + index + '][condition]'" :value="line.condition || ''">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-slate-900" x-text="line.name"></p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500" x-text="meta(line)"></p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="sr-only" :for="'cart-qty-' + line.id">Quantity</label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :max="line.available"
                                                    :id="'cart-qty-' + line.id"
                                                    :name="'items[' + index + '][quantity]'"
                                                    x-model.number="line.quantity"
                                                    @change="clampLine(line)"
                                                    class="h-9 w-20 rounded-lg border border-slate-200 px-2 text-sm"
                                                >
                                                <span class="text-xs text-slate-400" x-text="'/' + line.available"></span>
                                                <button
                                                    type="button"
                                                    @click="removeLine(index)"
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50"
                                                    aria-label="Remove item"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <p x-show="cartError" x-cloak class="text-xs font-medium text-rose-600" x-text="cartError"></p>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Borrower</p>
                            <p class="mt-1 text-sm text-slate-500">Shared across every item in this borrow.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrowerName" class="mb-1.5 block text-sm text-slate-600">
                                    Borrower name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="borrowerName"
                                    type="text"
                                    name="borrowing_borrower_name"
                                    placeholder="Enter borrower name"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div>
                                <label for="borrowDepartment" class="mb-1.5 block text-sm text-slate-600">
                                    Department <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowDepartment"
                                    type="text"
                                    name="borrowing_borrower_department"
                                    placeholder="Enter department"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="borrowAuthorizedBy" class="mb-1.5 block text-sm text-slate-600">
                                    Authorized by <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowAuthorizedBy"
                                    type="text"
                                    name="borrowing_authorized_by"
                                    placeholder="Enter authorizing personnel"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Schedule</p>
                            <p class="mt-1 text-sm text-slate-500">One borrow / return window for the whole cart.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="borrowDate" class="mb-1.5 block text-sm text-slate-600">
                                    Borrow date <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="borrowDate"
                                    type="date"
                                    name="borrowing_date"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div>
                                <label for="borrowExpectedReturn" class="mb-1.5 block text-sm text-slate-600">
                                    Expected return <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    id="borrowExpectedReturn"
                                    type="date"
                                    name="borrowing_expected_return_date"
                                    required
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Notes</p>
                            <p class="mt-1 text-sm text-slate-500">Optional purpose, destination, and remarks.</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="borrowPurpose" class="mb-1.5 block text-sm text-slate-600">
                                    Purpose <span class="text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    id="borrowPurpose"
                                    name="borrowing_purpose"
                                    rows="2"
                                    placeholder="Describe why the equipment is being borrowed"
                                    class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                ></textarea>
                            </div>
                            <div>
                                <label for="borrowDestination" class="mb-1.5 block text-sm text-slate-600">
                                    Destination <span class="text-slate-400">(optional)</span>
                                </label>
                                <input
                                    id="borrowDestination"
                                    type="text"
                                    name="borrowing_destination_location"
                                    placeholder="Enter destination location"
                                    class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                            <div>
                                <label for="borrowRemarks" class="mb-1.5 block text-sm text-slate-600">
                                    Remarks <span class="text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    id="borrowRemarks"
                                    name="borrowing_remarks"
                                    rows="2"
                                    placeholder="Add any additional notes"
                                    class="w-full resize-none rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-2 px-6 pb-6">
                    <button
                        type="button"
                        onclick="closeBorrowModal()"
                        class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                        x-text="cart.length ? ('Create borrow · ' + cart.length + ' line' + (cart.length === 1 ? '' : 's')) : 'Create borrowing record'"
                    ></button>
                </div>
            </form>
        </div>
    </div>

{{-- ===================================================== --}}
{{-- EQUIPMENT QR SCANNER MODAL --}}
{{-- ===================================================== --}}

<div
    id="equipmentScannerModal"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <div class="equipment-scanner-modal-card">

        {{-- ===================================================== --}}
        {{-- MODAL HEADER --}}
        {{-- ===================================================== --}}

        <div
            class="flex items-center justify-between border-b border-slate-200 px-6 py-4"
        >
            <div>
                <h2
                    id="equipmentScannerTitle"
                    class="font-['Outfit'] text-lg font-bold text-slate-900"
                >
                    Scan Equipment
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Point the camera at an equipment QR code.
                </p>
            </div>

            <button
                type="button"
                onclick="closeEquipmentScanner()"
                class="flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>


        {{-- ===================================================== --}}
        {{-- MODAL BODY --}}
        {{-- ===================================================== --}}

        <div class="p-6">

            {{-- ===================================================== --}}
            {{-- CAMERA VIEW --}}
            {{-- ===================================================== --}}

            <div id="equipmentScannerCameraSection">

                <div
                    id="equipmentQrReader"
                    class="equipment-scanner-camera"
                ></div>

                <div
                    id="equipmentScannerStatus"
                    class="equipment-scanner-status"
                >
                    Starting camera...
                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- EQUIPMENT FOUND RESULT --}}
            {{-- HIDDEN UNTIL A VALID QR IS SCANNED --}}
            {{-- ===================================================== --}}

            <div
                id="equipmentScannerResultSection"
                class="hidden"
            >

                <div class="mb-5 flex items-center gap-3">

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-50 text-emerald-600"
                    >
                        <i
                            data-lucide="circle-check"
                            class="h-5 w-5"
                        ></i>
                    </div>

                    <div>
                        <p
                            id="scanEquipmentName"
                            class="font-['Outfit'] text-base font-bold text-slate-900"
                        >
                            Equipment
                        </p>

                        <p
                            id="scanEquipmentQrCode"
                            class="text-xs text-slate-500"
                        >
                            QR Code
                        </p>
                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- EQUIPMENT INFORMATION --}}
                {{-- ===================================================== --}}

                <div class="equipment-scan-result">

                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Asset Tag
                        </span>

                        <span
                            id="scanEquipmentAssetTag"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Category
                        </span>

                        <span
                            id="scanEquipmentCategory"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Brand
                        </span>

                        <span
                            id="scanEquipmentBrand"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Model
                        </span>

                        <span
                            id="scanEquipmentModel"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Serial Number
                        </span>

                        <span
                            id="scanEquipmentSerial"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Room
                        </span>

                        <span
                            id="scanEquipmentRoom"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Condition
                        </span>

                        <span
                            id="scanEquipmentCondition"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>


                    <div class="equipment-scan-field">
                        <span class="equipment-scan-field-label">
                            Inventory Status
                        </span>

                        <span
                            id="scanEquipmentStatus"
                            class="equipment-scan-field-value"
                        >
                            N/A
                        </span>
                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- RESULT ACTIONS --}}
                {{-- ===================================================== --}}

                <div
                    class="mt-6 flex items-center justify-end gap-2 border-t border-slate-100 pt-4"
                >
                    <button
                        type="button"
                        onclick="restartEquipmentScanner()"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <i
                            data-lucide="scan-line"
                            class="h-4 w-4"
                        ></i>

                        Scan Another
                    </button>

                    <button
                        type="button"
                        onclick="closeEquipmentScanner()"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-[#0025cc] px-4 text-xs font-semibold text-white transition hover:bg-[#001fad]"
                    >
                        Done
                    </button>
                </div>

            </div>

        </div>

    </div>
</div>

{{-- ===================================================== --}}
{{-- URGENT REPORT QUICK VIEW MODAL --}}
{{-- ===================================================== --}}

<div
    id="urgentReportModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#0b1220]/70 p-4"
    aria-hidden="true"
>
    {{-- ================================================= --}}
    {{-- MODAL CONTAINER --}}
    {{-- ================================================= --}}

    <div
        class="relative flex max-h-[80vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        onclick="event.stopPropagation()"
    >
        {{-- ================================================= --}}
        {{-- MODAL HEADER --}}
        {{-- ================================================= --}}

        <div
            class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5"
        >
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">

                    <span
                        id="urgentModalReportId"
                        class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-gray-600"
                    >
                        Report
                    </span>

                    <span
                        id="urgentModalUrgency"
                        class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                    >
                        Urgent
                    </span>

                    <span
                        id="urgentModalStatus"
                        class="rounded-full border px-2.5 py-1 text-xs font-semibold"
                    >
                        Pending
                    </span>

                </div>

                <h2
                    id="urgentModalTitle"
                    class="text-xl font-bold text-gray-900 sm:text-2xl"
                >
                    Report Details
                </h2>

                <p
                    id="urgentModalSubmitted"
                    class="mt-1 text-sm text-gray-500"
                >
                    Submitted date
                </p>
            </div>

            <button
                type="button"
                onclick="closeUrgentReportModal()"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                aria-label="Close report preview"
            >
                <i
                    data-lucide="x"
                    class="h-5 w-5"
                ></i>
            </button>
        </div>


        {{-- ================================================= --}}
        {{-- MODAL BODY --}}
        {{-- ================================================= --}}

        <div class="overflow-y-auto px-6 py-6">

            {{-- ===================================================== --}}
            {{-- SUGGESTED ISSUE --}}
            {{-- ===================================================== --}}

            <div
                id="urgentModalSuggestedIssueSection"
                class="mb-6 hidden"
            >
                <p
                    class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400"
                >
                    Suggested Issue
                </p>

                <div
                    id="urgentModalSuggestedIssue"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium leading-6 text-amber-900"
                >
                    No suggested issue.
                </div>
            </div>


            {{-- ===================================================== --}}
            {{-- PROBLEM DESCRIPTION --}}
            {{-- ===================================================== --}}

            <div class="mb-6">
                <p
                    class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400"
                >
                    Problem Description
                </p>

                <div
                    id="urgentModalDescription"
                    class="rounded-2xl bg-gray-50 p-4 text-sm leading-6 text-gray-700"
                >
                    No description provided.
                </div>
            </div>


            {{-- ================================================= --}}
            {{-- INFORMATION GRID --}}
            {{-- ================================================= --}}

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                {{-- ================================================= --}}
                {{-- EQUIPMENT --}}
                {{-- ================================================= --}}

                <div class="rounded-2xl border border-gray-200 p-4">

                    <div class="mb-3 flex items-center gap-2">

                        <i
                            data-lucide="monitor"
                            class="h-4 w-4 text-gray-400"
                        ></i>

                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-400"
                        >
                            Equipment
                        </span>

                    </div>

                    <p
                        id="urgentModalEquipment"
                        class="font-semibold text-gray-900"
                    >
                        Not specified
                    </p>

                </div>


                {{-- ================================================= --}}
                {{-- REPORTER --}}
                {{-- ================================================= --}}

                <div class="rounded-2xl border border-gray-200 p-4">

                    <div class="mb-3 flex items-center gap-2">

                        <i
                            data-lucide="user"
                            class="h-4 w-4 text-gray-400"
                        ></i>

                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-400"
                        >
                            Reporter
                        </span>

                    </div>

                    <p
                        id="urgentModalReporter"
                        class="font-semibold text-gray-900"
                    >
                        Unknown reporter
                    </p>

                    {{-- EMPLOYEE ID --}}

                    <p
                        id="urgentModalEmployeeId"
                        class="mt-1 text-sm text-gray-500"
                    >
                        Employee ID unavailable
                    </p>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- LOCATION --}}
            {{-- ================================================= --}}

            <div
                class="mt-4 rounded-2xl border border-gray-200 p-4"
            >

                <div class="mb-3 flex items-center gap-2">

                    <i
                        data-lucide="map-pin"
                        class="h-4 w-4 text-gray-400"
                    ></i>

                    <span
                        class="text-xs font-bold uppercase tracking-wider text-gray-400"
                    >
                        Location
                    </span>

                </div>

                {{-- ROOM NAME --}}

                <p
                    id="urgentModalRoom"
                    class="font-semibold text-gray-900"
                >
                    Room not specified
                </p>

                {{-- FLOOR + BUILDING --}}

                <p
                    id="urgentModalLocation"
                    class="mt-1 text-sm text-gray-500"
                >
                    Location information unavailable
                </p>

            </div>


            {{-- ================================================= --}}
            {{-- EVIDENCE --}}
            {{-- HIDDEN WHEN REPORT HAS NO IMAGE --}}
            {{-- ================================================= --}}

            <div
                id="urgentModalEvidenceSection"
                class="mt-6 hidden"
            >

                <p
                    class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-400"
                >
                    Uploaded Evidence
                </p>

                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50"
                >

                    <img
                        id="urgentModalEvidence"
                        src=""
                        alt="Report evidence"
                        class="max-h-[350px] w-full object-contain"
                    >

                </div>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- MODAL FOOTER --}}
        {{-- ================================================= --}}

        <div
            class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/80 px-6 py-4 sm:flex-row sm:items-center sm:justify-end"
        >

            <button
                type="button"
                onclick="closeUrgentReportModal()"
                class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
            >
                Close
            </button>

            <a
                id="urgentModalFullReportLink"
                href="#"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#001fad]"
            >
                View Full Report

                <i
                    data-lucide="arrow-up-right"
                    class="h-4 w-4"
                ></i>
            </a>

        </div>

    </div>
</div>

{{-- ===================================================== --}}
{{-- QUICK ACTIVITY PREVIEW MODAL --}}
{{-- ===================================================== --}}

<div
    id="activityPreviewModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[2px]"
    role="dialog"
    aria-modal="true"
    aria-labelledby="activityPreviewTitle"
    aria-hidden="true"
    onclick="closeActivityPreviewModal()"
>
    <div
        class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.22)]"
        onclick="event.stopPropagation()"
    >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Dashboard
                </p>
                <h2
                    id="activityPreviewTitle"
                    class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
                >
                    Recent Activity
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Your latest maintenance actions
                </p>
            </div>

            <button
                type="button"
                onclick="closeActivityPreviewModal()"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close activity preview"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-6 py-5">

            @forelse ($activityPreview as $activity)
                @php
                    $module = $activity->audit_log_module ?? "";

                    $moduleTone = match ($module) {
                        "Reports" => ["bg-blue-50 text-blue-600 ring-blue-100", "border-blue-100 bg-blue-50 text-blue-700"],
                        "Equipment" => ["bg-indigo-50 text-indigo-600 ring-indigo-100", "border-indigo-100 bg-indigo-50 text-indigo-700"],
                        "Schedules" => ["bg-amber-50 text-amber-600 ring-amber-100", "border-amber-100 bg-amber-50 text-amber-700"],
                        "Borrowing" => ["bg-sky-50 text-sky-600 ring-sky-100", "border-sky-100 bg-sky-50 text-sky-700"],
                        "Infrastructure" => ["bg-emerald-50 text-emerald-600 ring-emerald-100", "border-emerald-100 bg-emerald-50 text-emerald-700"],
                        default => ["bg-slate-50 text-slate-500 ring-slate-200/80", "border-slate-200 bg-slate-50 text-slate-600"],
                    };
                @endphp

                <div class="group flex items-start gap-3.5 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition hover:border-slate-300 hover:bg-slate-50/70">
                    <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $moduleTone[0] }}">
                        <i data-lucide="{{ $activity->icon }}" class="h-4 w-4"></i>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ $activity->title }}
                            </p>

                            @if ($module)
                                <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $moduleTone[1] }}">
                                    {{ $module }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-1 text-sm leading-5 text-slate-500">
                            {{ $activity->description }}
                        </p>

                        <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-400">
                            <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                            {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                        </div>
                    </div>

                    @if ($activity->url)
                        <a
                            href="{{ $activity->url }}"
                            class="mt-1 inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-white hover:text-[#0025cc]"
                        >
                            View
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                        </a>
                    @endif
                </div>

            @empty
                <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-200/80">
                        <i data-lucide="history" class="h-5 w-5"></i>
                    </div>
                    <p class="font-semibold text-slate-800">No activities yet</p>
                    <p class="mt-1 text-sm text-slate-400">
                        Your maintenance actions will appear here.
                    </p>
                </div>
            @endforelse

        </div>

        <div class="flex shrink-0 items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
            <span class="text-xs text-slate-400">
                Showing your latest {{ $activityPreview->count() }} {{ \Illuminate\Support\Str::plural("activity", $activityPreview->count()) }}
            </span>

            <a
                href="{{ route('maintenance.activities.index') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
            >
                View all
                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            </a>
        </div>
    </div>
</div>

<script>
    // =====================================================
// ACTIVITY OPTIONS DROPDOWN
// =====================================================

function toggleActivityOptions(event) {

    event.stopPropagation();

    const dropdown = document.getElementById(
        'activityOptionsDropdown'
    );

    if (!dropdown) {
        return;
    }

    dropdown.classList.toggle('hidden');
}


// =====================================================
// CLOSE ACTIVITY OPTIONS WHEN CLICKING OUTSIDE
// =====================================================

document.addEventListener('click', function (event) {

    const dropdown = document.getElementById(
        'activityOptionsDropdown'
    );

    if (!dropdown) {
        return;
    }

    if (!dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }

});


// =====================================================
// REFRESH DASHBOARD ACTIVITY
// =====================================================

function refreshDashboardActivity() {

    window.location.reload();

}
</script>

<script>
    // =====================================================
// EQUIPMENT STATISTICS DROPDOWN
// =====================================================

function toggleEquipmentStatisticsMenu(event) {

    // Prevent document click from immediately closing it.
    event.stopPropagation();

    const dropdown =
        document.getElementById(
            "equipmentStatisticsDropdown"
        );

    const button =
        document.getElementById(
            "equipmentStatisticsMenuButton"
        );

    if (!dropdown || !button) {
        return;
    }


    // =================================================
    // CHECK CURRENT STATE
    // =================================================

    const isHidden =
        dropdown.classList.contains("hidden");


    // =================================================
    // OPEN OR CLOSE
    // =================================================

    if (isHidden) {

        dropdown.classList.remove("hidden");

        button.setAttribute(
            "aria-expanded",
            "true"
        );

    } else {

        dropdown.classList.add("hidden");

        button.setAttribute(
            "aria-expanded",
            "false"
        );

    }
}

// =====================================================
// SIDEBAR QUICK ACTIONS — DRAG / WHEEL CAROUSEL (NO ARROWS)
// =====================================================

(function initDragScrollTracks() {
    const bindDragScroll = (track, interactiveSelector) => {
        if (!track || track.dataset.dragBound === '1') {
            return;
        }
        track.dataset.dragBound = '1';

        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;
        let moved = false;
        let suppressClick = false;

        const endDrag = () => {
            if (!isDown) {
                return;
            }
            isDown = false;
            track.classList.remove('is-dragging');
            if (moved) {
                suppressClick = true;
                track.classList.add('has-dragged');
                // Keep buttons inert until the ghost click from this drag is gone.
                window.setTimeout(() => {
                    suppressClick = false;
                    track.classList.remove('has-dragged');
                }, 0);
            }
        };

        track.addEventListener('mousedown', (event) => {
            if (event.button !== 0) {
                return;
            }
            isDown = true;
            moved = false;
            suppressClick = false;
            startX = event.pageX;
            scrollLeft = track.scrollLeft;
        });

        track.addEventListener('mousemove', (event) => {
            if (!isDown) {
                return;
            }
            const walk = event.pageX - startX;
            if (!moved && Math.abs(walk) <= 6) {
                return;
            }
            moved = true;
            track.classList.add('is-dragging');
            event.preventDefault();
            track.scrollLeft = scrollLeft - walk;
        });

        track.addEventListener('mouseleave', endDrag);
        window.addEventListener('mouseup', endDrag);

        // Block activation after a drag (covers inline onclick + <a href>).
        track.addEventListener('click', (event) => {
            if (!suppressClick && !moved) {
                return;
            }
            const target = event.target.closest(interactiveSelector);
            if (!target || !track.contains(target)) {
                return;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            suppressClick = false;
            moved = false;
            track.classList.remove('has-dragged');
        }, true);

        track.querySelectorAll(interactiveSelector).forEach((el) => {
            el.addEventListener('dragstart', (event) => event.preventDefault());
        });

        // Mouse wheel scrolls the row sideways and never the page while the pointer is over it.
        track.addEventListener('wheel', (event) => {
            if (event.ctrlKey) {
                return;
            }
            const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
            const maxScroll = track.scrollWidth - track.clientWidth;
            if (!delta || maxScroll <= 0) {
                return;
            }
            event.preventDefault();
            const step = event.deltaMode === 1 ? delta * 16 : delta;
            track.scrollLeft = Math.max(0, Math.min(maxScroll, track.scrollLeft + step));
        }, { passive: false });
    };

    bindDragScroll(
        document.getElementById('sidebarQuickActionsTrack'),
        'button.dashboard-quick-action, a.dashboard-quick-action'
    );
})();


// =====================================================
// CLOSE WHEN CLICKING OUTSIDE
// =====================================================

document.addEventListener(
    "click",
    function (event) {

        const menu =
            document.getElementById(
                "equipmentStatisticsMenu"
            );

        const dropdown =
            document.getElementById(
                "equipmentStatisticsDropdown"
            );

        const button =
            document.getElementById(
                "equipmentStatisticsMenuButton"
            );

        if (
            !menu ||
            !dropdown ||
            !button
        ) {
            return;
        }


        // =================================================
        // IGNORE CLICKS INSIDE MENU
        // =================================================

        if (menu.contains(event.target)) {
            return;
        }


        // =================================================
        // CLOSE DROPDOWN
        // =================================================

        dropdown.classList.add("hidden");

        button.setAttribute(
            "aria-expanded",
            "false"
        );

    }
);


// =====================================================
// CLOSE WITH ESCAPE
// =====================================================

document.addEventListener(
    "keydown",
    function (event) {

        if (event.key !== "Escape") {
            return;
        }

        const dropdown =
            document.getElementById(
                "equipmentStatisticsDropdown"
            );

        const button =
            document.getElementById(
                "equipmentStatisticsMenuButton"
            );

        if (!dropdown || !button) {
            return;
        }

        dropdown.classList.add("hidden");

        button.setAttribute(
            "aria-expanded",
            "false"
        );

    }
);
</script>

<script>
    // =====================================================
// ACTIVITY PREVIEW MODAL
// =====================================================

function openActivityPreviewModal() {
    // =================================================
    // GET MODAL
    // =================================================

    const modal =
        document.getElementById(
            "activityPreviewModal"
        );

    if (!modal) {
        return;
    }

    // =================================================
    // SHOW MODAL
    // =================================================

    modal.classList.remove("hidden");
    modal.classList.add("flex");

    modal.setAttribute(
        "aria-hidden",
        "false"
    );

    // Prevent dashboard from scrolling behind modal.
    document.body.style.overflow = "hidden";

    // Refresh Lucide icons inside modal.
    if (window.lucide) {
        lucide.createIcons();
    }
}


// =====================================================
// CLOSE ACTIVITY PREVIEW MODAL
// =====================================================

function closeActivityPreviewModal() {
    // =================================================
    // GET MODAL
    // =================================================

    const modal =
        document.getElementById(
            "activityPreviewModal"
        );

    if (!modal) {
        return;
    }

    // =================================================
    // HIDE MODAL
    // =================================================

    modal.classList.add("hidden");
    modal.classList.remove("flex");

    modal.setAttribute(
        "aria-hidden",
        "true"
    );

    document.body.style.overflow = "";
}

// =====================================================
// CLOSE ACTIVITY PREVIEW WITH ESCAPE KEY
// =====================================================

document.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Escape") {
            closeActivityPreviewModal();
        }

    }
);
</script>

    <script>

        // =====================================================
        // EQUIPMENT QR SCANNER
        // =====================================================

        let equipmentQrScanner = null;

        let equipmentScannerRunning = false;

        let equipmentScannerProcessing = false;


        // =====================================================
        // OPEN EQUIPMENT SCANNER MODAL
        // =====================================================

        async function openEquipmentScanner() {

            const modal = document.getElementById(
                'equipmentScannerModal'
            );

            if (!modal) {
                console.error('Equipment scanner modal not found.');
                return;
            }


            // =====================================================
            // RESET MODAL TO CAMERA VIEW
            // =====================================================

            document
                .getElementById('equipmentScannerCameraSection')
                ?.classList.remove('hidden');

            document
                .getElementById('equipmentScannerResultSection')
                ?.classList.add('hidden');


            const title = document.getElementById(
                'equipmentScannerTitle'
            );

            if (title) {
                title.textContent = 'Scan Equipment';
            }


            const status = document.getElementById(
                'equipmentScannerStatus'
            );

            if (status) {
                status.textContent = 'Starting camera...';

                status.className =
                    'equipment-scanner-status';
            }


            // =====================================================
            // SHOW MODAL
            // =====================================================

            modal.classList.remove('hidden');

            modal.classList.add('flex');

            document.body.style.overflow = 'hidden';


            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }


            // =====================================================
            // START CAMERA
            // =====================================================
            equipmentScannerProcessing = false;

            await startEquipmentScanner();

            
        }


        // =====================================================
        // START EQUIPMENT QR SCANNER
        // =====================================================

        async function startEquipmentScanner() {

            const reader = document.getElementById(
                'equipmentQrReader'
            );

            const status = document.getElementById(
                'equipmentScannerStatus'
            );


            if (!reader) {
                console.error('QR reader element not found.');
                return;
            }


            // =====================================================
            // CHECK IF QR LIBRARY LOADED
            // =====================================================

            if (typeof Html5Qrcode === 'undefined') {

                if (status) {
                    status.textContent =
                        'QR scanner library failed to load.';
                }

                console.error(
                    'Html5Qrcode library is not available.'
                );

                return;
            }


            // =====================================================
            // PREVENT CAMERA FROM STARTING TWICE
            // =====================================================

            if (equipmentScannerRunning) {
                return;
            }


            


            try {

                // =====================================================
                // CREATE SCANNER INSTANCE
                // =====================================================

                if (!equipmentQrScanner) {

                    equipmentQrScanner =
                        new Html5Qrcode(
                            'equipmentQrReader'
                        );
                }


                if (status) {
                    status.textContent =
                        'Requesting camera access...';
                }


                // =====================================================
                // START BACK CAMERA
                //
                // facingMode environment = rear camera when available
                // =====================================================

                await equipmentQrScanner.start(

                    {
                        facingMode: 'environment'
                    },

                    {
                        fps: 10,

                        qrbox: {
                            width: 220,
                            height: 220
                        },

                        aspectRatio: 1.333334
                    },

                    handleEquipmentQrScan,

                    function () {
                        // Ignore normal scan failures.
                        // The scanner continuously checks frames.
                    }

                );


                equipmentScannerRunning = true;


                if (status) {
                    status.textContent =
                        'Camera ready. Point it at an equipment QR code.';
                }

            } catch (error) {

                equipmentScannerRunning = false;


                console.error(
                    'Unable to start equipment scanner:',
                    error
                );


                if (status) {

                    status.textContent =
                        'Unable to access the camera. Check your browser camera permission.';
                }

            }
        }


        // =====================================================
        // HANDLE SUCCESSFUL QR SCAN
        // =====================================================

        async function handleEquipmentQrScan(
            decodedText
        ) {

            // =====================================================
            // PREVENT THE SAME QR FROM BEING SUBMITTED MANY TIMES
            // =====================================================

            if (equipmentScannerProcessing) {
                return;
            }


            equipmentScannerProcessing = true;


            const status = document.getElementById(
                'equipmentScannerStatus'
            );


            if (status) {
                status.textContent =
                    'QR detected. Finding equipment...';
            }


            // =====================================================
            // STOP CAMERA AFTER QR IS DETECTED
            // =====================================================

            await stopEquipmentScanner();


            try {

                // =====================================================
                // SEND QR VALUE TO LARAVEL
                // =====================================================

                const response = await fetch(
                    "{{ url('/maintenance/equipment/qr/scan') }}",
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.getAttribute('content')
                                ?? ''
                        },

                        body: JSON.stringify({
                            qr_code: decodedText
                        })
                    }
                );


                // =====================================================
                // READ LARAVEL RESPONSE
                // =====================================================

                const data = await response.json();


                // =====================================================
                // EQUIPMENT NOT FOUND
                // =====================================================

                if (!response.ok || !data.success) {

                    throw new Error(
                        data.message ??
                        'Equipment could not be found.'
                    );
                }


                // =====================================================
                // SHOW EQUIPMENT RESULT
                // =====================================================

                showScannedEquipment(
                    data.equipment
                );

            } catch (error) {

                console.error(
                    'Equipment QR scan failed:',
                    error
                );


                // =====================================================
                // SHOW ERROR
                // =====================================================

                if (status) {

                    status.textContent =
                        error.message ??
                        'Unable to process this QR code.';
                }


                // =====================================================
                // ALLOW ANOTHER SCAN
                // =====================================================

                equipmentScannerProcessing = false;


                // =====================================================
                // RESTART CAMERA AFTER SHORT DELAY
                // =====================================================

                setTimeout(
                    async function () {

                        const modal =
                            document.getElementById(
                                'equipmentScannerModal'
                            );


                        // Only restart if modal is still open
                        if (
                            modal &&
                            !modal.classList.contains('hidden')
                        ) {

                            await startEquipmentScanner();
                        }

                    },
                    1500
                );
            }
        }


        // =====================================================
        // DISPLAY SCANNED EQUIPMENT INFORMATION
        // =====================================================

        function showScannedEquipment(equipment) {

            const cameraSection =
                document.getElementById(
                    'equipmentScannerCameraSection'
                );

            const resultSection =
                document.getElementById(
                    'equipmentScannerResultSection'
                );

            const title =
                document.getElementById(
                    'equipmentScannerTitle'
                );


            // =====================================================
            // HIDE CAMERA
            // SHOW RESULT
            // =====================================================

            cameraSection?.classList.add('hidden');

            resultSection?.classList.remove('hidden');


            if (title) {
                title.textContent =
                    'Equipment Found';
            }


            // =====================================================
            // SMALL HELPER FUNCTION
            //
            // If database value is empty, show N/A.
            // =====================================================

            function setEquipmentValue(
                elementId,
                value
            ) {

                const element =
                    document.getElementById(
                        elementId
                    );


                if (!element) {
                    return;
                }


                element.textContent =
                    value !== null &&
                    value !== undefined &&
                    value !== ''
                        ? value
                        : 'N/A';
            }


            // =====================================================
            // FILL EQUIPMENT INFORMATION
            // =====================================================

            setEquipmentValue(
                'scanEquipmentName',
                equipment.name
            );

            setEquipmentValue(
                'scanEquipmentQrCode',
                equipment.qr_code
            );

            setEquipmentValue(
                'scanEquipmentAssetTag',
                equipment.asset_tag
            );

            setEquipmentValue(
                'scanEquipmentCategory',
                equipment.category
            );

            setEquipmentValue(
                'scanEquipmentBrand',
                equipment.brand
            );

            setEquipmentValue(
                'scanEquipmentModel',
                equipment.model
            );

            setEquipmentValue(
                'scanEquipmentSerial',
                equipment.serial_number
            );

            setEquipmentValue(
                'scanEquipmentRoom',
                equipment.room
            );

            setEquipmentValue(
                'scanEquipmentCondition',
                equipment.condition
            );

            setEquipmentValue(
                'scanEquipmentStatus',
                equipment.status
            );


            // =====================================================
            // REFRESH LUCIDE ICONS
            // =====================================================

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }


        // =====================================================
        // STOP EQUIPMENT SCANNER
        // =====================================================

        async function stopEquipmentScanner() {

            if (
                !equipmentQrScanner ||
                !equipmentScannerRunning
            ) {
                return;
            }


            try {

                await equipmentQrScanner.stop();

            } catch (error) {

                console.warn(
                    'Scanner stop warning:',
                    error
                );

            } finally {

                equipmentScannerRunning = false;
            }
        }


        // =====================================================
        // SCAN ANOTHER EQUIPMENT
        // =====================================================

        async function restartEquipmentScanner() {

            equipmentScannerProcessing = false;


            const cameraSection =
                document.getElementById(
                    'equipmentScannerCameraSection'
                );

            const resultSection =
                document.getElementById(
                    'equipmentScannerResultSection'
                );

            const title =
                document.getElementById(
                    'equipmentScannerTitle'
                );

            const status =
                document.getElementById(
                    'equipmentScannerStatus'
                );


            // =====================================================
            // SWITCH BACK TO CAMERA
            // =====================================================

            resultSection?.classList.add('hidden');

            cameraSection?.classList.remove('hidden');


            if (title) {
                title.textContent =
                    'Scan Equipment';
            }


            if (status) {
                status.textContent =
                    'Starting camera...';
            }


            // =====================================================
            // START CAMERA AGAIN
            // =====================================================

            await startEquipmentScanner();
        }


        // =====================================================
        // CLOSE EQUIPMENT SCANNER
        // =====================================================

        async function closeEquipmentScanner() {

            const modal =
                document.getElementById(
                    'equipmentScannerModal'
                );


            // =====================================================
            // STOP CAMERA FIRST
            // =====================================================

            await stopEquipmentScanner();


            equipmentScannerProcessing = false;


            // =====================================================
            // HIDE MODAL
            // =====================================================

            if (modal) {

                modal.classList.add('hidden');

                modal.classList.remove('flex');
            }


            document.body.style.overflow = '';
        }




        // =====================================================
        // OPEN URGENT REPORT QUICK VIEW MODAL
        // =====================================================

        function openUrgentReportModal(report) {

            const modal =
                document.getElementById('urgentReportModal');

            if (!modal) {
                console.error(
                    'Urgent report modal was not found.'
                );

                return;
            }


            // =================================================
            // REPORT ID
            // =================================================

            document.getElementById(
                'urgentModalReportId'
            ).textContent =
                `Report #${report.id ?? ''}`;


            // =================================================
            // REPORT TITLE
            // =================================================

            document.getElementById(
                'urgentModalTitle'
            ).textContent =
                report.title
                || 'Reported Issue';

            // =================================================
            // SUGGESTED ISSUE
            // =================================================

            const suggestedIssueSection =
                document.getElementById(
                    'urgentModalSuggestedIssueSection'
                );

            const suggestedIssue =
                document.getElementById(
                    'urgentModalSuggestedIssue'
                );

            if (
                report.suggested_issue &&
                report.suggested_issue.trim() !== ''
            ) {

                suggestedIssue.textContent =
                    report.suggested_issue;

                suggestedIssueSection.classList.remove(
                    'hidden'
                );

            } else {

                suggestedIssue.textContent =
                    'No suggested issue.';

                suggestedIssueSection.classList.add(
                    'hidden'
                );
            }


            // =================================================
            // DESCRIPTION
            // =================================================

            document.getElementById(
                'urgentModalDescription'
            ).textContent =
                report.description
                || 'No description provided.';

            


            // =================================================
            // EQUIPMENT
            // =================================================

            document.getElementById(
                'urgentModalEquipment'
            ).textContent =
                report.equipment
                || 'Not specified';


            // =================================================
            // REPORTER
            // =================================================

            document.getElementById(
                'urgentModalReporter'
            ).textContent =
                report.reporter
                || 'Unknown reporter';

            document.getElementById(
                'urgentModalEmployeeId'
            ).textContent =
                report.employee_id
                    ? `${report.employee_id}`
                    : 'Employee ID unavailable';


            // =================================================
            // ROOM
            // =================================================

            document.getElementById(
                'urgentModalRoom'
            ).textContent =
                report.room
                || 'Room not specified';


            // =================================================
            // BUILD LOCATION TEXT
            // Example:
            // 2nd Floor · Main Building
            // =================================================

            const locationParts = [];

            if (report.floor) {
                locationParts.push(
                    report.floor
                );
            }

            if (report.building) {
                locationParts.push(
                    report.building
                );
            }

            document.getElementById(
                'urgentModalLocation'
            ).textContent =
                locationParts.length
                    ? locationParts.join(' · ')
                    : 'Location information unavailable';


            // =================================================
            // SUBMITTED DATE
            // =================================================

            const submittedElement =
                document.getElementById(
                    'urgentModalSubmitted'
                );

            if (report.submitted_at) {

                const submittedDate =
                    new Date(
                        report.submitted_at
                    );

                if (!isNaN(
                    submittedDate.getTime()
                )) {

                    submittedElement.textContent =
                        'Submitted '
                        + submittedDate.toLocaleString(
                            undefined,
                            {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit'
                            }
                        );

                } else {

                    submittedElement.textContent =
                        report.submitted_at;
                }

            } else {

                submittedElement.textContent =
                    'Submission date unavailable';
            }


            // =================================================
            // URGENCY BADGE
            // =================================================

            const urgencyBadge =
                document.getElementById(
                    'urgentModalUrgency'
                );

            urgencyBadge.textContent =
                report.urgency
                || 'Unknown';

            urgencyBadge.className =
                'rounded-full border px-2.5 py-1 text-xs font-semibold';

            if (report.urgency === 'Urgent') {

                urgencyBadge.classList.add(
                    'border-red-200',
                    'bg-red-50',
                    'text-red-700'
                );

            } else {

                urgencyBadge.classList.add(
                    'border-gray-200',
                    'bg-gray-50',
                    'text-gray-700'
                );
            }


            // =================================================
            // STATUS BADGE
            // =================================================

            const statusBadge =
                document.getElementById(
                    'urgentModalStatus'
                );

            statusBadge.textContent =
                report.status
                || 'Unknown';

            statusBadge.className =
                'rounded-full border px-2.5 py-1 text-xs font-semibold';

            switch (report.status) {

                case 'Pending':

                    statusBadge.classList.add(
                        'border-amber-200',
                        'bg-amber-50',
                        'text-amber-700'
                    );

                    break;


                case 'Processing':

                    statusBadge.classList.add(
                        'border-blue-200',
                        'bg-blue-50',
                        'text-blue-700'
                    );

                    break;


                case 'Resolved':

                    statusBadge.classList.add(
                        'border-emerald-200',
                        'bg-emerald-50',
                        'text-emerald-700'
                    );

                    break;


                case 'Rejected':

                    statusBadge.classList.add(
                        'border-red-200',
                        'bg-red-50',
                        'text-red-700'
                    );

                    break;


                case 'For Replacement':

                    statusBadge.classList.add(
                        'border-orange-200',
                        'bg-orange-50',
                        'text-orange-700'
                    );

                    break;


                default:

                    statusBadge.classList.add(
                        'border-gray-200',
                        'bg-gray-50',
                        'text-gray-700'
                    );
            }


            // =================================================
            // EVIDENCE IMAGE
            // =================================================

            const evidenceSection =
                document.getElementById(
                    'urgentModalEvidenceSection'
                );

            const evidenceImage =
                document.getElementById(
                    'urgentModalEvidence'
                );

            if (report.image) {

                evidenceImage.src =
                    report.image;

                evidenceSection.classList.remove(
                    'hidden'
                );

            } else {

                evidenceImage.src = '';

                evidenceSection.classList.add(
                    'hidden'
                );
            }


            // =================================================
            // FULL REPORT DESTINATION
            // =================================================

            document.getElementById(
                'urgentModalFullReportLink'
            ).href =
                report.url
                || '#';


            // =================================================
            // SHOW MODAL
            // =================================================

            modal.classList.remove(
                'hidden'
            );

            modal.classList.add(
                'flex'
            );

            modal.setAttribute(
                'aria-hidden',
                'false'
            );


            // =================================================
            // PREVENT PAGE FROM SCROLLING BEHIND MODAL
            // =================================================

            document.body.classList.add(
                'overflow-hidden'
            );


            // =================================================
            // REFRESH LUCIDE ICONS
            // =================================================

            if (window.lucide) {
                lucide.createIcons();
            }
        }


        // =====================================================
        // CLOSE URGENT REPORT QUICK VIEW MODAL
        // =====================================================

        function closeUrgentReportModal() {

            const modal =
                document.getElementById(
                    'urgentReportModal'
                );

            if (!modal) {
                return;
            }

            modal.classList.add(
                'hidden'
            );

            modal.classList.remove(
                'flex'
            );

            modal.setAttribute(
                'aria-hidden',
                'true'
            );

            document.body.classList.remove(
                'overflow-hidden'
            );
        }

        // =====================================================
        // CLOSE URGENT REPORT MODAL WHEN CLICKING BACKDROP
        // =====================================================

        document
            .getElementById(
                'urgentReportModal'
            )
            ?.addEventListener(
                'click',
                function () {

                    closeUrgentReportModal();

                }
            );


        // =====================================================
// DASHBOARD QUICK ACTION MODALS
// =====================================================




// =====================================================
// SCHEDULE MODAL (matches Schedules module)
// =====================================================

function scheduleEquipmentCart(catalog) {
    return {
        catalog: Array.isArray(catalog) ? catalog : [],
        query: '',
        open: false,
        highlight: 0,
        cart: [],
        pickerError: '',
        cartError: '',
        get filtered() {
            const q = String(this.query || '').trim().toLowerCase();
            const selectedIds = new Set(this.cart.map((line) => line.id));
            return this.catalog
                .filter((item) => !selectedIds.has(item.id))
                .filter((item) => {
                    if (!q) return true;
                    return [item.name, item.room, item.qr, item.assetTag]
                        .join(' ')
                        .toLowerCase()
                        .includes(q);
                })
                .slice(0, 40);
        },
        meta(item) {
            const bits = [];
            if (item.room) bits.push(item.room);
            if (item.qr) bits.push(item.qr);
            if (item.assetTag) bits.push(item.assetTag);
            return bits.join(' · ') || 'QR-ready equipment';
        },
        reset() {
            this.query = '';
            this.open = false;
            this.highlight = 0;
            this.cart = [];
            this.pickerError = '';
            this.cartError = '';
        },
        move(delta) {
            if (!this.filtered.length) return;
            this.highlight = (this.highlight + delta + this.filtered.length) % this.filtered.length;
        },
        addHighlighted() {
            if (!this.filtered.length) return;
            this.addItem(this.filtered[this.highlight] || this.filtered[0]);
        },
        addItem(item) {
            this.pickerError = '';
            if (!item) return;
            if (this.cart.some((line) => line.id === item.id)) {
                this.pickerError = 'That equipment is already in the list.';
                return;
            }
            this.cart.push({ ...item });
            this.query = '';
            this.open = false;
            this.highlight = 0;
            this.cartError = '';
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        removeLine(index) {
            this.cart.splice(index, 1);
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        prepareSubmit(event) {
            this.cartError = '';
            if (!this.cart.length) {
                event.preventDefault();
                this.cartError = 'Add at least one equipment item.';
                return;
            }
            const nextDate = document.getElementById('scheduleNextDate')?.value;
            if (!nextDate) {
                event.preventDefault();
                this.cartError = 'Please choose a next maintenance date.';
            }
        },
    };
}
window.scheduleEquipmentCart = scheduleEquipmentCart;
document.addEventListener("alpine:init", () => {
    if (window.Alpine?.data) {
        window.Alpine.data("scheduleEquipmentCart", scheduleEquipmentCart);
    }
});

function scheduleNextDatePicker() {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December",
    ];
    const toIso = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    };
    const fromIso = (iso) => {
        const [y, m, d] = String(iso).split("-").map(Number);
        return new Date(y, m - 1, d);
    };

    return {
        value: toIso(today),
        viewYear: today.getFullYear(),
        viewMonth: today.getMonth(),
        todayIso: toIso(today),
        weekdays: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
        get display() {
            if (!this.value) {
                return "Select a date";
            }
            return fromIso(this.value).toLocaleDateString("en-US", {
                weekday: "short",
                month: "short",
                day: "numeric",
                year: "numeric",
            });
        },
        get monthLabel() {
            return `${months[this.viewMonth]} ${this.viewYear}`;
        },
        get days() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const start = first.getDay();
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const prevDays = new Date(this.viewYear, this.viewMonth, 0).getDate();
            const cells = [];

            for (let i = 0; i < 42; i += 1) {
                let year = this.viewYear;
                let month = this.viewMonth;
                let date;
                let outside = false;

                if (i < start) {
                    date = prevDays - start + i + 1;
                    month -= 1;
                    if (month < 0) {
                        month = 11;
                        year -= 1;
                    }
                    outside = true;
                } else if (i >= start + daysInMonth) {
                    date = i - start - daysInMonth + 1;
                    month += 1;
                    if (month > 11) {
                        month = 0;
                        year += 1;
                    }
                    outside = true;
                } else {
                    date = i - start + 1;
                }

                const iso = toIso(new Date(year, month, date));
                cells.push({
                    d: date,
                    iso,
                    outside,
                    isToday: iso === this.todayIso,
                    selected: iso === this.value,
                });
            }

            return cells;
        },
        prevMonth() {
            if (this.viewMonth === 0) {
                this.viewMonth = 11;
                this.viewYear -= 1;
                return;
            }
            this.viewMonth -= 1;
        },
        nextMonth() {
            if (this.viewMonth === 11) {
                this.viewMonth = 0;
                this.viewYear += 1;
                return;
            }
            this.viewMonth += 1;
        },
        pick(day) {
            this.value = day.iso;
            if (!day.outside) {
                return;
            }
            const selected = fromIso(day.iso);
            this.viewYear = selected.getFullYear();
            this.viewMonth = selected.getMonth();
        },
        goToday() {
            this.value = this.todayIso;
            this.viewYear = today.getFullYear();
            this.viewMonth = today.getMonth();
        },
        clearDate() {
            this.value = "";
        },
    };
}

window.scheduleNextDatePicker = scheduleNextDatePicker;
document.addEventListener("alpine:init", () => {
    if (window.Alpine?.data) {
        window.Alpine.data("scheduleNextDatePicker", scheduleNextDatePicker);
    }
});

function openScheduleModal() {
    const modal = document.getElementById('scheduleModal');

    if (!modal) {
        console.error('Schedule modal not found.');
        return;
    }

    if (modal._x_dataStack?.[0]?.reset) {
        modal._x_dataStack[0].reset();
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    document.body.style.overflow = 'hidden';

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}


function closeScheduleModal() {

    const modal = document.getElementById('scheduleModal');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    document.body.style.overflow = '';

    if (modal._x_dataStack?.[0]?.reset) {
        modal._x_dataStack[0].reset();
    }
}


function borrowEquipmentCart(catalog) {
    return {
        catalog: Array.isArray(catalog) ? catalog : [],
        query: '',
        open: false,
        highlight: 0,
        selected: null,
        addQty: 1,
        cart: [],
        pickerError: '',
        cartError: '',
        get filtered() {
            const q = String(this.query || '').trim().toLowerCase();
            const selectedIds = new Set(this.cart.map((line) => line.id));
            return this.catalog
                .filter((item) => item.available > 0)
                .filter((item) => {
                    if (!q) return true;
                    return [item.name, item.room, item.assetTag]
                        .join(' ')
                        .toLowerCase()
                        .includes(q);
                })
                .slice(0, 40);
        },
        get totalQty() {
            return this.cart.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0);
        },
        meta(item) {
            const bits = [];
            if (item.room) bits.push(item.room);
            if (item.assetTag) bits.push(item.assetTag);
            bits.push((item.tracking || 'Individual') + ' · ' + item.available + ' available');
            return bits.join(' · ');
        },
        reset() {
            this.query = '';
            this.open = false;
            this.highlight = 0;
            this.selected = null;
            this.addQty = 1;
            this.cart = [];
            this.pickerError = '';
            this.cartError = '';
        },
        choose(item) {
            this.selected = item;
            this.query = item.name;
            this.open = false;
            this.addQty = 1;
            this.pickerError = '';
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        move(delta) {
            if (!this.filtered.length) return;
            this.highlight = (this.highlight + delta + this.filtered.length) % this.filtered.length;
        },
        selectHighlighted() {
            if (!this.filtered.length) return;
            this.choose(this.filtered[this.highlight] || this.filtered[0]);
        },
        addSelected() {
            this.pickerError = '';
            if (!this.selected) {
                this.pickerError = 'Search and select an equipment item first.';
                return;
            }
            const qty = Math.max(1, Number(this.addQty) || 1);
            if (qty > this.selected.available) {
                this.pickerError = 'Only ' + this.selected.available + ' available for this item.';
                return;
            }
            const existing = this.cart.find((line) => line.id === this.selected.id);
            if (existing) {
                const next = existing.quantity + qty;
                if (next > existing.available) {
                    this.pickerError = 'Cart would exceed available quantity (' + existing.available + ').';
                    return;
                }
                existing.quantity = next;
            } else {
                this.cart.push({
                    id: this.selected.id,
                    name: this.selected.name,
                    room: this.selected.room,
                    assetTag: this.selected.assetTag,
                    tracking: this.selected.tracking,
                    available: this.selected.available,
                    quantity: qty,
                    condition: '',
                });
            }
            this.selected = null;
            this.query = '';
            this.addQty = 1;
            this.cartError = '';
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        clampLine(line) {
            let qty = Number(line.quantity) || 1;
            if (qty < 1) qty = 1;
            if (qty > line.available) qty = line.available;
            line.quantity = qty;
        },
        removeLine(index) {
            this.cart.splice(index, 1);
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        prepareSubmit(event) {
            this.cartError = '';
            if (!this.cart.length) {
                event.preventDefault();
                this.cartError = 'Add at least one equipment item to the cart.';
                return;
            }
            for (const line of this.cart) {
                this.clampLine(line);
                if (line.quantity > line.available) {
                    event.preventDefault();
                    this.cartError = line.name + ' exceeds available quantity.';
                    return;
                }
            }
        },
    };
}

window.borrowEquipmentCart = borrowEquipmentCart;


// =====================================================
// BORROW MODAL (matches Borrowing module)
// =====================================================

function openBorrowModal() {
    const modal = document.getElementById('borrowModal');
    if (!modal) {
        console.error('Borrow modal not found.');
        return;
    }
    if (modal._x_dataStack?.[0]?.reset) {
        modal._x_dataStack[0].reset();
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    if (window.lucide) window.lucide.createIcons();
}

function closeBorrowModal() {
    const modal = document.getElementById('borrowModal');
    if (!modal) {
        return;
    }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
    if (modal._x_dataStack?.[0]?.reset) {
        modal._x_dataStack[0].reset();
    }
}


// =====================================================
// CLOSE MODALS WHEN CLICKING OUTSIDE
// =====================================================

document.addEventListener('click', function (event) {

    const addEquipmentModal = document.getElementById('addEquipmentModal');
    if (addEquipmentModal && event.target === addEquipmentModal) {
        closeAddEquipmentModal();
    }

    const borrowModal = document.getElementById('borrowModal');
    if (borrowModal && event.target === borrowModal) {
        closeBorrowModal();
    }

    const scheduleModal = document.getElementById('scheduleModal');
    if (scheduleModal && event.target === scheduleModal) {
        closeScheduleModal();
    }

});

// =====================================================
// CLOSE EQUIPMENT SCANNER WHEN CLICKING BACKDROP
// =====================================================

document.addEventListener(
    'click',
    async function (event) {

        const scannerModal =
            document.getElementById(
                'equipmentScannerModal'
            );


        if (
            scannerModal &&
            event.target === scannerModal
        ) {

            await closeEquipmentScanner();
        }
    }
);


// =====================================================
// CLOSE MODALS WITH ESC KEY
// =====================================================

document.addEventListener('keydown', function (event) {

    if (event.key !== 'Escape') {
        return;
    }

    // addEquipmentModal / borrowModal / scheduleModal Escape handled by Alpine
    document.body.style.overflow = '';

});
// =====================================================
// CLOSE QR SCANNER WITH ESCAPE
// =====================================================

document.addEventListener(
    'keydown',
    async function (event) {

        if (event.key !== 'Escape') {
            return;
        }


        const scannerModal =
            document.getElementById(
                'equipmentScannerModal'
            );


        if (
            scannerModal &&
            !scannerModal.classList.contains('hidden')
        ) {

            await closeEquipmentScanner();
        }
    }
);
    </script>



    @include('partials.building-3d.scripts')





























































    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            const metricsCard = document.getElementById('equipmentMetricsCard');
            if (!metricsCard) {
                return;
            }

            const metricsDashboard = @json($metricsDashboard ?? []);
            const reportFunnelSpec = [
                { label: 'Submitted Reports', key: 'submitted', unit: 'reports', href: @json(url('/maintenance/reports')) },
                { label: 'Accepted Reports', key: 'accepted', unit: 'reports', href: @json(url('/maintenance/reports/pending')) },
                { label: 'In Progress+', key: 'actioned', unit: 'reports', href: @json(url('/maintenance/reports/processing')) },
                { label: 'Closed Outcomes', key: 'closed', unit: 'reports', href: @json(url('/maintenance/reports/resolved')) },
                { label: 'Resolved', key: 'resolved', unit: 'reports', href: @json(url('/maintenance/reports/resolved')) },
            ];

            const formatCount = (value) => Number(value || 0).toLocaleString();
            const formatCompact = (value) => {
                const n = Number(value) || 0;
                if (n >= 1000000) {
                    return `${String((n / 1000000).toFixed(1)).replace(/\.0$/, '')}M`;
                }
                if (n >= 1000) {
                    return `${String((n / 1000).toFixed(1)).replace(/\.0$/, '')}k`;
                }
                return formatCount(n);
            };
            const formatDropoff = (value) => {
                const n = Number(value) || 0;
                return `${n > 0 ? '+' : ''}${n}%`;
            };
            const buildFunnel = (stages) => {
                const values = stages.map((stage) => Number(stage.value) || 0);
                const max = Math.max(1, ...values);
                let axisTop = Math.ceil(max / 5) * 5;
                if (axisTop < 5) {
                    axisTop = Math.max(5, max);
                }
                const built = stages.map((stage, index) => {
                    const value = Number(stage.value) || 0;
                    const height = Math.max(10, Math.round((value / Math.max(1, axisTop)) * 100));
                    const prev = index > 0 ? (Number(stages[index - 1].value) || 0) : value;
                    const conversion = prev > 0 ? Math.round((value / prev) * 100) : 100;
                    const nextHeight = index < stages.length - 1
                        ? Math.max(10, Math.round(((Number(stages[index + 1].value) || 0) / Math.max(1, axisTop)) * 100))
                        : height;
                    let slope = height > 0 ? Math.max(0, Math.round(((height - nextHeight) / height) * 100)) : 0;
                    if (nextHeight > height) {
                        slope = 0;
                    }
                    return {
                        ...stage,
                        value,
                        display: formatCompact(value),
                        height,
                        conversion,
                        dropoff: conversion - 100,
                        slope,
                    };
                });
                const axis = [];
                for (let i = 0; i < 5; i += 1) {
                    axis.push(formatCompact(Math.round(axisTop * (1 - (i / 4)))));
                }
                return { stages: built, axis };
            };
            const wavePath = (waveY, variant) => {
                if (variant === 'soft') {
                    return `M0 ${waveY - 8} C 35 ${waveY - 22}, 65 ${waveY + 8}, 100 ${waveY - 4} S 165 ${waveY - 20}, 200 ${waveY - 2} L 200 200 L 0 200 Z`;
                }
                return `M0 ${waveY + 6} C 40 ${waveY - 10}, 70 ${waveY + 16}, 100 ${waveY + 2} S 160 ${waveY - 12}, 200 ${waveY + 8} L 200 200 L 0 200 Z`;
            };

            const placeFunnelMenu = (btn, menu) => {
                if (!btn || !menu) {
                    return;
                }
                const rect = btn.getBoundingClientRect();
                const menuWidth = Math.max(168, menu.offsetWidth || 168);
                const gap = 6;
                let left = rect.right - menuWidth;
                let top = rect.bottom + gap;
                const maxLeft = window.innerWidth - menuWidth - 8;
                left = Math.max(8, Math.min(left, maxLeft));
                menu.style.left = `${left}px`;
                menu.style.top = `${top}px`;
                menu.style.right = 'auto';
                // If it would clip below the viewport, flip above the button.
                const menuHeight = menu.offsetHeight || 160;
                if (top + menuHeight > window.innerHeight - 8) {
                    top = Math.max(8, rect.top - menuHeight - gap);
                    menu.style.top = `${top}px`;
                }
            };

            const closeMenus = (except = null) => {
                const periodMenu = document.getElementById('eqPeriodMenu');
                const periodPill = document.getElementById('eqPeriodPill');
                if (periodMenu && except !== periodMenu) {
                    periodMenu.classList.add('hidden');
                    periodPill?.setAttribute('aria-expanded', 'false');
                }
                metricsCard.querySelectorAll('.eq-funnel-menu').forEach((menu) => {
                    if (menu !== except) {
                        menu.classList.add('hidden');
                        menu.style.top = '';
                        menu.style.left = '';
                        menu.style.right = '';
                    }
                });
                metricsCard.querySelectorAll('[data-eq-funnel-menu-btn]').forEach((btn) => {
                    if (except && btn.getAttribute('aria-controls') === except.id) {
                        return;
                    }
                    btn.setAttribute('aria-expanded', 'false');
                });
                if (except?.id !== 'equipmentStatisticsDropdown') {
                    document.getElementById('equipmentStatisticsDropdown')?.classList.add('hidden');
                    document.getElementById('equipmentStatisticsMenuButton')?.setAttribute('aria-expanded', 'false');
                }
            };

            const bindFunnel = (funnel) => {
                const tip = funnel.querySelector('.eq-funnel-tip');
                const cols = () => funnel.querySelectorAll('.eq-funnel-col, .eq-mini-col');
                const showTip = (col) => {
                    if (!tip || !col) return;
                    const display = col.getAttribute('data-display') || '0';
                    const unit = col.getAttribute('data-unit') || 'items';
                    const conversion = col.getAttribute('data-conversion') || '0';
                    const dropoff = col.getAttribute('data-dropoff') || '0';
                    tip.innerHTML = `<strong>${display}</strong> ${unit} | Conversion: <strong>${conversion}%</strong> | Drop-off: <strong>${formatDropoff(dropoff)}</strong>`;
                    tip.classList.add('is-visible');
                };
                const hideTip = () => tip?.classList.remove('is-visible');
                const clearActive = () => {
                    cols().forEach((item) => item.classList.remove('is-active'));
                    hideTip();
                };
                const setActive = (col) => {
                    if (!col) return;
                    cols().forEach((item) => item.classList.toggle('is-active', item === col));
                    showTip(col);
                };

                funnel.addEventListener('mouseover', (event) => {
                    const col = event.target.closest('.eq-funnel-col, .eq-mini-col');
                    if (col && funnel.contains(col)) {
                        setActive(col);
                    }
                });
                funnel.addEventListener('focusin', (event) => {
                    const col = event.target.closest('.eq-funnel-col, .eq-mini-col');
                    if (col && funnel.contains(col)) {
                        setActive(col);
                    }
                });
                funnel.addEventListener('mouseleave', clearActive);
                funnel.addEventListener('click', (event) => {
                    const col = event.target.closest('.eq-funnel-col, .eq-mini-col');
                    if (!col || !funnel.contains(col)) {
                        return;
                    }
                    const href = col.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });
            };

            const applySlice = (slice) => {
                if (!slice) {
                    return;
                }
                const pending = Number(slice.pending) || 0;
                const overdue = Number(slice.overdue) || 0;
                const open = Number(slice.open) || (pending + overdue);
                const handled = Number(slice.handled) || 0;
                const fillPercent = Number(slice.fill_percent) || 22;
                const waveY = Number(slice.wave_y) || (200 - ((fillPercent / 100) * 200));

                const pendingEl = document.getElementById('eqOpsPendingValue');
                const overdueEl = document.getElementById('eqOpsOverdueValue');
                const openEl = document.getElementById('eqOpsOpenLabel');
                const fillEl = document.getElementById('eqOpsOverdueFill');
                const tabCount = document.getElementById('eqOpsTabCount');
                if (pendingEl) pendingEl.textContent = formatCount(pending);
                if (overdueEl) overdueEl.textContent = formatCount(overdue);
                if (openEl) openEl.textContent = `${formatCount(open)} open`;
                if (fillEl) fillEl.style.width = `${open > 0 ? Math.min(100, Math.round((overdue / Math.max(1, open)) * 100)) : 0}%`;
                if (tabCount) tabCount.textContent = String(Math.min(99, open));

                const liquidValue = document.getElementById('mhLiquidValue');
                const waveSoft = document.getElementById('mhLiquidWaveSoft');
                const waveSolid = document.getElementById('mhLiquidWaveSolid');
                const overdueItems = document.getElementById('mhOverdueItems');
                const handledPct = document.getElementById('mhHandledPct');
                if (liquidValue) {
                    liquidValue.textContent = formatCount(open);
                    liquidValue.classList.toggle('is-dark', fillPercent < 48);
                }
                if (waveSoft) waveSoft.setAttribute('d', wavePath(waveY, 'soft'));
                if (waveSolid) waveSolid.setAttribute('d', wavePath(waveY, 'solid'));
                if (overdueItems) overdueItems.textContent = formatCount(overdue);
                if (handledPct) handledPct.textContent = `${formatCount(handled)}%`;

                const reports = slice.reports || {};
                const funnel = buildFunnel(reportFunnelSpec.map((spec) => ({
                    ...spec,
                    value: Number(reports[spec.key]) || 0,
                })));
                document.getElementById('eqReportsBigBars')?.renderBars?.(funnel.stages);

                const colsWrap = document.getElementById('eqReportsFunnelCols');
                if (colsWrap) {
                    const miniMax = Math.max(0, ...funnel.stages.map((stage) => stage.value));
                    colsWrap.innerHTML = funnel.stages.map((stage) => `
                        <button
                            type="button"
                            class="eq-mini-col${miniMax > 0 && stage.value === miniMax ? ' is-peak' : ''}"
                            style="--bar-h: ${miniMax > 0 ? Math.round((stage.value / miniMax) * 100) : 0}%;"
                            data-href="${stage.href}"
                            title="View ${stage.label}"
                            aria-label="${stage.label}: ${stage.display}"
                        >
                            <span class="eq-mini-plot">
                                <span class="eq-mini-bar"><span class="eq-mini-value">${stage.display}</span></span>
                            </span>
                            <span class="eq-mini-label">${stage.label}</span>
                        </button>
                    `).join('');
                }
            };

            const setPeriodLabel = (label, periodKey = null) => {
                const pillLabel = document.getElementById('eqPeriodPillLabel');
                if (pillLabel) {
                    pillLabel.textContent = label;
                }
                metricsCard.querySelectorAll('[data-eq-period]').forEach((option) => {
                    option.classList.toggle('is-active', periodKey !== null && option.getAttribute('data-eq-period') === periodKey);
                });
            };

            const tabs = metricsCard.querySelectorAll('[data-eq-metrics-tab]');
            const panels = document.querySelectorAll('[data-eq-metrics-panel]');
            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const target = tab.getAttribute('data-eq-metrics-tab');
                    tabs.forEach((item) => {
                        const isActive = item === tab;
                        item.classList.toggle('is-active', isActive);
                        item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    panels.forEach((panel) => {
                        const matches = panel.getAttribute('data-eq-metrics-panel') === target;
                        panel.classList.toggle('is-hidden', !matches);
                    });
                });
            });

            const periodPill = document.getElementById('eqPeriodPill');
            const periodMenu = document.getElementById('eqPeriodMenu');
            periodPill?.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = periodMenu?.classList.contains('hidden');
                closeMenus(willOpen ? periodMenu : null);
                periodMenu?.classList.toggle('hidden', !willOpen);
                periodPill.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
            metricsCard.querySelectorAll('[data-eq-period]').forEach((option) => {
                option.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const key = option.getAttribute('data-eq-period');
                    const slice = metricsDashboard?.periods?.[key];
                    applySlice(slice);
                    setPeriodLabel(metricsDashboard?.periodLabels?.[key] || option.textContent.trim(), key);
                    document.querySelectorAll('.mh-year-tab').forEach((item) => {
                        const isCurrentYear = item.getAttribute('data-mh-year') === String(metricsDashboard?.year || '');
                        item.classList.toggle('is-active', isCurrentYear);
                        item.setAttribute('aria-pressed', isCurrentYear ? 'true' : 'false');
                    });
                    closeMenus();
                });
            });

            document.querySelectorAll('.mh-year-tab').forEach((yearTab) => {
                yearTab.addEventListener('click', () => {
                    const year = yearTab.getAttribute('data-mh-year');
                    const slice = metricsDashboard?.years?.[year];
                    applySlice(slice);
                    setPeriodLabel(year, null);
                    document.querySelectorAll('.mh-year-tab').forEach((item) => {
                        const isActive = item === yearTab;
                        item.classList.toggle('is-active', isActive);
                        item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                });
            });

            metricsCard.querySelectorAll('[data-eq-funnel-menu-btn]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const menu = document.getElementById(btn.getAttribute('aria-controls') || '');
                    const willOpen = menu?.classList.contains('hidden');
                    closeMenus(willOpen ? menu : null);
                    if (!menu) {
                        return;
                    }
                    menu.classList.toggle('hidden', !willOpen);
                    btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) {
                        placeFunnelMenu(btn, menu);
                        requestAnimationFrame(() => placeFunnelMenu(btn, menu));
                    } else {
                        menu.style.top = '';
                        menu.style.left = '';
                        menu.style.right = '';
                    }
                });
            });

            metricsCard.querySelectorAll('[data-eq-funnel]').forEach(bindFunnel);

            const repositionOpenFunnelMenu = () => {
                const openBtn = metricsCard.querySelector('[data-eq-funnel-menu-btn][aria-expanded="true"]');
                if (!openBtn) {
                    return;
                }
                const menu = document.getElementById(openBtn.getAttribute('aria-controls') || '');
                if (menu && !menu.classList.contains('hidden')) {
                    placeFunnelMenu(openBtn, menu);
                }
            };
            window.addEventListener('resize', repositionOpenFunnelMenu);
            window.addEventListener('scroll', repositionOpenFunnelMenu, true);

            document.addEventListener('click', (event) => {
                if (
                    event.target.closest('#eqPeriodPill') ||
                    event.target.closest('#eqPeriodMenu') ||
                    event.target.closest('[data-eq-funnel-menu-btn]') ||
                    event.target.closest('.eq-funnel-menu')
                ) {
                    return;
                }
                closeMenus();
            });
        });
    </script>

    <script>
        const miniChartLabels = @json ($miniChartLabels);

        const urgentChartData = @json ($urgentChartData);

        const maintenanceChartData = @json ($maintenanceChartData);

        const borrowedChartData = @json ($borrowedChartData);

        // =====================================================
        // SOFT SHADOW UNDER THE LINE
        // =====================================================

        const shadowPlugin = {
            id: "shadowPlugin",

            beforeDatasetDraw(chart, args, pluginOptions) {
                const ctx = chart.ctx;

                ctx.save();

                ctx.shadowColor = pluginOptions.color;

                ctx.shadowBlur = 20;

                ctx.shadowOffsetY = 10;

                ctx.shadowOffsetX = 0;
            },

            afterDatasetDraw(chart) {
                chart.ctx.restore();
            },
        };

        Chart.register(shadowPlugin);

        // =====================================================
        // CREATE MODERN MINIMALIST CHART
        // =====================================================

        function createPremiumChart(canvasId, lineColor, dataValues) {
            const canvas = document.getElementById(canvasId);

            if (!canvas) return;

            const ctx = canvas.getContext("2d");

            const fillGradient = ctx.createLinearGradient(0, 0, 0, 140);

            fillGradient.addColorStop(0, lineColor + "33");
            fillGradient.addColorStop(0.45, lineColor + "12");
            fillGradient.addColorStop(1, "rgba(255,255,255,0)");

            new Chart(ctx, {
                type: "line",

                data: {
                    labels: miniChartLabels,

                    datasets: [
                        {
                            data: dataValues,

                            borderColor: lineColor,

                            segment: {
                                borderCapStyle: "round",

                                borderJoinStyle: "round",
                            },

                            backgroundColor: fillGradient,

                            fill: true,

                            borderWidth: 2.5,

                            tension: 0.45,

                            pointRadius(context) {
                                return context.dataIndex === dataValues.length - 1
                                    ? 4
                                    : 0;
                            },

                            pointHoverRadius: 6,

                            pointBorderWidth: 2,

                            pointBackgroundColor: "white",

                            pointBorderColor: lineColor,

                            hitRadius: 20,
                        },
                    ],
                },

                options: {
                    responsive: true,

                    maintainAspectRatio: false,

                    layout: {
                        padding: {
                            top: 12,
                            bottom: 0,
                        },
                    },

                    animation: {
                        duration: 1400,

                        easing: "easeOutQuart",
                    },

                    interaction: {
                        intersect: false,

                        mode: "index",
                    },

                    plugins: {
                        shadowPlugin: {
                            color: lineColor,
                        },

                        legend: {
                            display: false,
                        },

                        tooltip: {
                            displayColors: false,

                            backgroundColor: "white",

                            titleColor: "#111827",

                            bodyColor: "#111827",

                            borderColor: "#E5E7EB",

                            borderWidth: 1,

                            padding: 10,

                            callbacks: {
                                label(context) {
                                    return context.raw + " Reports";
                                },
                            },
                        },
                    },

                    scales: {
                        x: {
                            display: false,

                            grid: {
                                display: false,
                            },
                        },

                        y: {
                            display: false,

                            grid: {
                                display: false,
                            },
                        },
                    },
                },
            });
        }
        // =====================================================
        // URGENT REPORTS
        // =====================================================

        createPremiumChart("urgentChart", "#ff4d67", urgentChartData);

        // =====================================================
        // UNDER MAINTENANCE
        // =====================================================

        createPremiumChart("maintenanceChart", "#ffbf3f", maintenanceChartData);

        // =====================================================
        // BORROWED EQUIPMENT
        // =====================================================

        createPremiumChart("borrowedEquipmentChart", "#38ef7d", borrowedChartData);
    </script>

    {{-- ===================================================== --}}
    {{-- JAVASCRIPT --}}
    {{-- ===================================================== --}}

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // =====================================================
            // INITIALIZE LUCIDE ICONS
            // =====================================================

            if (window.lucide) {
                lucide.createIcons();
            }

            // =====================================================
            // CHART.JS DEFAULT FONT
            // =====================================================

            Chart.defaults.font.family = "'Inter', sans-serif";

            Chart.defaults.color = "#64748b";

            // =====================================================
            // REPORT ACTIVITY LINE CHART
            // =====================================================

            // =====================================================
            // REPORT STATUS DOUGHNUT CHART
            // =====================================================

            // =====================================================
            // REPORT STATUS PROGRESS RING
            //
            // DESIGN:
            //
            // LIGHT GRAY BACKGROUND TRACK
            // BLUE STATUS SEGMENTS
            // ROUNDED ENDS
            // SMALL OPENING
            // CENTER PERCENTAGE
            // DOMINANT STATUS LABEL
            // =====================================================

            // =====================================================
            // REPORT STATUS SEMI GAUGE CHART
            //
            // DESIGN:
            //
            // PARTIAL CIRCULAR GAUGE
            // LIGHT GRAY BACKGROUND TRACK
            // BLUE STATUS SEGMENTS
            // ROUNDED ENDS
            // CENTER PERCENTAGE
            // DOMINANT STATUS
            // LEGEND BELOW
            // =====================================================

            const reportStatusCanvas = document.getElementById("reportStatusChart");

            if (reportStatusCanvas) {
                // =====================================================
                // GET DATA FROM CONTROLLER
                // =====================================================

                const reportStatusLabels = @json ($reportStatusChart["labels"]);

                const reportStatusValues = @json ($reportStatusChart["data"]);

                // =====================================================
                // TOTAL REPORTS
                // =====================================================

                const reportStatusTotal = reportStatusValues.reduce(
                    (total, value) => total + Number(value),

                    0,
                );

                // =====================================================
                // DOMINANT STATUS
                // =====================================================

                const largestStatusValue =
                    reportStatusValues.length > 0 ? Math.max(...reportStatusValues) : 0;

                const largestStatusIndex =
                    reportStatusValues.indexOf(largestStatusValue);

                const largestStatusLabel =
                    largestStatusIndex >= 0
                        ? reportStatusLabels[largestStatusIndex]
                        : "No Reports";

                const largestStatusPercentage =
                    reportStatusTotal > 0
                        ? Math.round((largestStatusValue / reportStatusTotal) * 100)
                        : 0;

                // =====================================================
                // BACKGROUND TRACK PLUGIN
                //
                // DRAWS ONE CONTINUOUS LIGHT GRAY ARC
                // BEHIND THE STATUS SEGMENTS.
                // =====================================================

                const reportStatusGaugeTrack = {
                    id: "reportStatusGaugeTrack",

                    beforeDatasetsDraw(chart) {
                        const metadata = chart.getDatasetMeta(0);

                        const firstArc = metadata.data.find(
                            (arc) => arc.outerRadius > 0,
                        );

                        if (!firstArc) {
                            return;
                        }

                        const ctx = chart.ctx;

                        const trackRadius =
                            (firstArc.innerRadius + firstArc.outerRadius) / 2;

                        const trackWidth = firstArc.outerRadius - firstArc.innerRadius;

                        // =================================================
                        // IMPORTANT
                        //
                        // USE THE ACTUAL ARC ANGLES GENERATED BY CHART.JS.
                        //
                        // THIS PREVENTS THE BACKGROUND TRACK FROM
                        // BEING ROTATED DIFFERENTLY FROM THE DATA.
                        // =================================================

                        const startAngle = firstArc.startAngle;

                        const lastArc = metadata.data[metadata.data.length - 1];

                        const endAngle = lastArc.endAngle;

                        ctx.save();

                        ctx.beginPath();

                        ctx.arc(
                            firstArc.x,

                            firstArc.y,

                            trackRadius,

                            startAngle,

                            endAngle,
                        );

                        ctx.strokeStyle = "#e2e8f0";

                        ctx.lineWidth = trackWidth;

                        ctx.lineCap = "round";

                        ctx.stroke();

                        ctx.restore();
                    },
                };

                // =====================================================
                // CENTER TEXT PLUGIN
                // =====================================================

                const reportStatusGaugeCenterText = {
                    id: "reportStatusGaugeCenterText",

                    afterDatasetsDraw(chart) {
                        const metadata = chart.getDatasetMeta(0);

                        const firstArc = metadata.data.find(
                            (arc) => arc.outerRadius > 0,
                        );

                        if (!firstArc) {
                            return;
                        }

                        const ctx = chart.ctx;

                        const centerX = firstArc.x;

                        const centerY = firstArc.y;

                        ctx.save();

                        ctx.textAlign = "center";

                        ctx.textBaseline = "middle";

                        // =================================================
                        // PERCENTAGE
                        // =================================================

                        ctx.fillStyle = "#0f172a";

                        ctx.font = '700 38px "Outfit", sans-serif';

                        ctx.fillText(
                            largestStatusPercentage + "%",

                            centerX,

                            centerY - 4,
                        );

                        // =================================================
                        // DOMINANT STATUS
                        // =================================================

                        ctx.fillStyle = "#64748b";

                        ctx.font = '500 11px "Inter", sans-serif';

                        ctx.fillText(
                            largestStatusLabel,

                            centerX,

                            centerY + 25,
                        );

                        ctx.restore();
                    },
                };

                // =====================================================
                // REJECTED DIAGONAL STRIPE PATTERN
                // ADD THIS BEFORE new Chart(...)
                // =====================================================

                const rejectedPatternCanvas = document.createElement("canvas");

                rejectedPatternCanvas.width = 10;
                rejectedPatternCanvas.height = 10;

                const rejectedPatternCtx = rejectedPatternCanvas.getContext("2d");

                // =====================================================
                // TRANSPARENT BACKGROUND
                // =====================================================

                rejectedPatternCtx.clearRect(0, 0, 10, 10);

                // =====================================================
                // DIAGONAL STRIPE COLOR
                //
                // CHANGE THIS COLOR IF YOU WANT.
                // =====================================================

                rejectedPatternCtx.strokeStyle = "#94a3b8";

                rejectedPatternCtx.lineWidth = 2;

                rejectedPatternCtx.beginPath();

                // =====================================================
                // DRAW REPEATING DIAGONAL LINES
                // =====================================================

                rejectedPatternCtx.moveTo(-2, 10);

                rejectedPatternCtx.lineTo(10, -2);

                rejectedPatternCtx.moveTo(3, 13);

                rejectedPatternCtx.lineTo(13, 3);

                rejectedPatternCtx.stroke();

                // =====================================================
                // CREATE CHART.JS PATTERN
                // =====================================================

                const rejectedStripePattern = reportStatusCanvas

                    .getContext("2d")

                    .createPattern(
                        rejectedPatternCanvas,

                        "repeat",
                    );

                // =====================================================
                // CREATE GAUGE
                // =====================================================

                new Chart(
                    reportStatusCanvas,

                    {
                        type: "doughnut",

                        data: {
                            labels: reportStatusLabels,

                            datasets: [
                                {
                                    data: reportStatusValues,

                                    // =========================================
                                    // BLUE SHADES
                                    // =========================================

                                    // =====================================================
                                    // STATUS COLORS
                                    //
                                    // REJECTED AUTOMATICALLY GETS DIAGONAL STRIPES
                                    // EVEN IF THE CONTROLLER CHANGES THE STATUS ORDER.
                                    // =====================================================

                                    backgroundColor: reportStatusLabels.map(
                                        (status) => {
                                            if (status === "Pending") {
                                                return "#1d4ed8";
                                            }

                                            if (status === "Processing") {
                                                return "#2563eb";
                                            }

                                            if (status === "Resolved") {
                                                return "#3b82f6";
                                            }

                                            if (status === "For Replacement") {
                                                return "#60a5fa";
                                            }

                                            if (status === "Rejected") {
                                                return rejectedStripePattern;
                                            }

                                            return "#cbd5e1";
                                        },
                                    ),

                                    borderWidth: 0,

                                    // =========================================
                                    // ROUNDED STATUS SEGMENTS
                                    // =========================================

                                    borderRadius: 30,

                                    // =========================================
                                    // VERY SMALL SEPARATION
                                    //
                                    // IMAGE 1 HAS OVERLAPPING VISUAL LAYERS,
                                    // NOT LARGE GAPS.
                                    // =========================================

                                    spacing: 0,

                                    hoverOffset: 2,
                                },
                            ],
                        },

                        options: {
                            responsive: true,

                            maintainAspectRatio: false,

                            // =================================================
                            // THICK GAUGE
                            // =================================================

                            cutout: "65%",

                            // =================================================
                            // GAUGE START POSITION
                            //
                            // STARTS AT LOWER LEFT.
                            // =================================================

                            rotation: -135,

                            // =================================================
                            // PARTIAL CIRCLE
                            //
                            // 270 DEGREES CREATES THE OPEN GAUGE SHAPE.
                            // =================================================

                            circumference: 270,

                            layout: {
                                padding: {
                                    top: 20,

                                    right: 35,

                                    bottom: 5,

                                    left: 35,
                                },
                            },

                            plugins: {
                                legend: {
                                    display: true,

                                    position: "bottom",

                                    labels: {
                                        usePointStyle: true,

                                        pointStyle: "circle",

                                        boxWidth: 7,

                                        boxHeight: 7,

                                        padding: 14,

                                        color: "#64748b",

                                        font: {
                                            family: "Inter",

                                            size: 10,
                                        },
                                    },
                                },

                                tooltip: {
                                    callbacks: {
                                        label(context) {
                                            const value = Number(context.raw);

                                            const percentage =
                                                reportStatusTotal > 0
                                                    ? Math.round(
                                                          (value / reportStatusTotal) *
                                                              100,
                                                      )
                                                    : 0;

                                            return (
                                                context.label +
                                                ": " +
                                                value +
                                                " reports (" +
                                                percentage +
                                                "%)"
                                            );
                                        },
                                    },
                                },
                            },
                        },

                        plugins: [reportStatusGaugeTrack, reportStatusGaugeCenterText],
                    },
                );
            }

            // =====================================================
            // EQUIPMENT CONDITION
            // SMOOTH RADAR CHART (COMPETITOR-ANALYSIS STYLE)
            // =====================================================

            const equipmentConditionCanvas = document.getElementById(
                "equipmentConditionChart",
            );

            if (equipmentConditionCanvas) {
                const equipmentConditionLabels = @json ($equipmentConditionChart["labels"]);
                const equipmentConditionData = @json ($equipmentConditionChart["data"]);

                const currentValues = equipmentConditionData.map(
                    (value) => Number(value) || 0,
                );
                const maxValue = Math.max(...currentValues, 1);
                const currentScaled = currentValues.map((value) =>
                    Math.round((value / maxValue) * 100),
                );

                // Healthy target profile (relative scale 0-100)
                const targetScaled = equipmentConditionLabels.map((label) => {
                    const key = String(label).toLowerCase();
                    if (key.includes("good")) return 100;
                    if (key.includes("damaged")) return 12;
                    if (key.includes("maintenance")) return 18;
                    if (key.includes("disposed")) return 8;
                    return 20;
                });

                const shortLabels = equipmentConditionLabels.map((label) => {
                    const text = String(label);
                    if (text.toLowerCase().includes("under maintenance")) {
                        return "Maintenance";
                    }
                    return text;
                });

                new Chart(equipmentConditionCanvas, {
                    type: "radar",
                    data: {
                        labels: shortLabels,
                        datasets: [
                            {
                                label: "Current",
                                data: currentScaled,
                                borderColor: "#0025cc",
                                backgroundColor: "rgba(0, 37, 204, 0.18)",
                                borderWidth: 2.5,
                                pointBackgroundColor: "#0025cc",
                                pointBorderColor: "#0025cc",
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                fill: true,
                            },
                            {
                                label: "Target",
                                data: targetScaled,
                                borderColor: "#14b8a6",
                                backgroundColor: "rgba(20, 184, 166, 0.14)",
                                borderWidth: 2.5,
                                pointBackgroundColor: "#14b8a6",
                                pointBorderColor: "#14b8a6",
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        elements: {
                            line: {
                                tension: 0.48,
                                borderJoinStyle: "round",
                            },
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                backgroundColor: "#111827",
                                titleColor: "#f9fafb",
                                bodyColor: "#e5e7eb",
                                padding: 10,
                                cornerRadius: 8,
                                displayColors: true,
                                callbacks: {
                                    label(context) {
                                        const index = context.dataIndex;
                                        const rawCount = currentValues[index] ?? 0;
                                        if (context.dataset.label === "Current") {
                                            return ` Current: ${rawCount} equipment (${context.parsed.r}%)`;
                                        }
                                        return ` Target profile: ${context.parsed.r}%`;
                                    },
                                },
                            },
                        },
                        scales: {
                            r: {
                                min: 0,
                                max: 100,
                                beginAtZero: true,
                                ticks: {
                                    display: false,
                                    stepSize: 25,
                                },
                                grid: {
                                    circular: true,
                                    color: "rgba(148, 163, 184, 0.28)",
                                },
                                angleLines: {
                                    color: "rgba(148, 163, 184, 0.22)",
                                },
                                pointLabels: {
                                    color: "#9ca3af",
                                    font: {
                                        size: 12,
                                        weight: "500",
                                        family: "Inter, sans-serif",
                                    },
                                    padding: 10,
                                },
                            },
                        },
                    },
                });
            }

            // =====================================================
            // MAINTENANCE SCHEDULE WORKLOAD CHART
            //
            // REFERENCE DESIGN:
            //
            // REAL NEXT 30 DAY DATA
            // GROUPED INTO 7 DISPLAY POINTS
            // TWO SMOOTH LINES
            // SUBTLE BLUE FADE DIRECTLY BELOW BLUE LINE
            // DARK TOOLTIP
            // VERTICAL DASHED HOVER LINE
            // ACTIVE X AXIS LABEL
            // =====================================================

            const maintenanceWorkloadCanvas = document.getElementById(
                "maintenanceWorkloadChart",
            );

            if (maintenanceWorkloadCanvas) {
                // =====================================================
                // GET REAL CONTROLLER DATA
                // =====================================================

                const maintenanceRawLabels = @json ($maintenanceWorkloadLabels);

                const maintenanceRawData = @json ($maintenanceWorkloadData);

                // =====================================================
                // GROUP 30 DAYS INTO 7 DISPLAY POINTS
                // =====================================================

                const maintenanceDisplayCount = 7;

                const maintenanceDisplayLabels = [];

                const maintenanceDisplayData = [];

                for (
                    let displayIndex = 0;
                    displayIndex < maintenanceDisplayCount;
                    displayIndex++
                ) {
                    const startIndex = Math.floor(
                        (displayIndex * maintenanceRawData.length) /
                            maintenanceDisplayCount,
                    );

                    const endIndex = Math.floor(
                        ((displayIndex + 1) * maintenanceRawData.length) /
                            maintenanceDisplayCount,
                    );

                    const groupValues = maintenanceRawData.slice(
                        startIndex,

                        endIndex,
                    );

                    const groupTotal = groupValues.reduce(
                        (total, value) => total + Number(value),

                        0,
                    );

                    maintenanceDisplayData.push(groupTotal);

                    maintenanceDisplayLabels.push(maintenanceRawLabels[startIndex]);
                }

                // =====================================================
                // SECOND VISUAL TREND LINE
                // =====================================================

                const maintenanceTrendData = maintenanceDisplayData.map(
                    (value, index, values) => {
                        const previousValue = values[index - 1] ?? value;

                        const nextValue = values[index + 1] ?? value;

                        return (previousValue + value + nextValue) / 3;
                    },
                );

                // =====================================================
                // CUSTOM BLUE SHADOW PLUGIN
                //
                // IMPORTANT:
                //
                // THIS DOES NOT USE fill: true.
                //
                // IT DRAWS A SHALLOW GRADIENT DIRECTLY BELOW THE BLUE
                // LINE, WHICH IS CLOSER TO THE REFERENCE IMAGE.
                // =====================================================

                const maintenanceBlueShadowPlugin = {
                    id: "maintenanceBlueShadowPlugin",

                    beforeDatasetsDraw(chart) {
                        const meta = chart.getDatasetMeta(0);

                        if (!meta || meta.hidden || !meta.data.length) {
                            return;
                        }

                        const ctx = chart.ctx;

                        const chartArea = chart.chartArea;

                        const points = meta.data;

                        const blueDataset = chart.data.datasets[0];

                        // =================================================
                        // CREATE GRADIENT
                        //
                        // STRONGEST NEAR THE BLUE LINE.
                        //
                        // COMPLETELY TRANSPARENT LOWER DOWN.
                        // =================================================

                        const gradient = ctx.createLinearGradient(
                            0,
                            chartArea.top,
                            0,
                            chartArea.bottom,
                        );

                        // 1. A much stronger start opacity at the top (under the line)
                        gradient.addColorStop(
                            0,
                            "rgba(0, 37, 204, 0.45)",
                        );

                        // 2. Keep the color solid as it starts to drop
                        gradient.addColorStop(
                            0.35,
                            "rgba(0, 37, 204, 0.22)",
                        );

                        // 3. A soft, gradual fade out towards the bottom
                        gradient.addColorStop(
                            0.7,
                            "rgba(0, 37, 204, 0.08)",
                        );

                        // 4. Completely transparent at the very bottom baseline
                        gradient.addColorStop(1, "rgba(0, 37, 204, 0)");

                        ctx.save();

                        // =================================================
                        // CLIP EVERYTHING TO CHART AREA
                        // =================================================

                        ctx.beginPath();

                        ctx.rect(
                            chartArea.left,

                            chartArea.top,

                            chartArea.right - chartArea.left,

                            chartArea.bottom - chartArea.top,
                        );

                        ctx.clip();

                        // =================================================
                        // BUILD THE EXACT SAME CURVED PATH AS CHART.JS
                        //
                        // THIS USES THE ACTUAL DATASET LINE ELEMENT.
                        // =================================================

                        ctx.beginPath();

                        meta.dataset.path(ctx);

                        // =================================================
                        // CONTINUE PATH DOWNWARD ONLY A SHORT DISTANCE
                        //
                        // THIS IS THE IMPORTANT PART.
                        //
                        // REFERENCE IMAGE HAS A SOFT SHADOW UNDER THE LINE,
                        // NOT A HEAVY AREA FILL TO THE X AXIS.
                        // =================================================

                        const lastPoint = points[points.length - 1];

                        const firstPoint = points[0];

                        const shadowDepth = 75;

                        ctx.lineTo(
                            lastPoint.x,

                            Math.min(
                                lastPoint.y + shadowDepth,

                                chartArea.bottom,
                            ),
                        );

                        ctx.lineTo(
                            firstPoint.x,

                            Math.min(
                                firstPoint.y + shadowDepth,

                                chartArea.bottom,
                            ),
                        );

                        ctx.closePath();

                        // =================================================
                        // APPLY GRADIENT
                        // =================================================

                        ctx.fillStyle = gradient;

                        ctx.fill();

                        ctx.restore();
                    },
                };

                // =====================================================
                // VERTICAL HOVER LINE PLUGIN
                // =====================================================

                const maintenanceWorkloadHoverLine = {
                    id: "maintenanceWorkloadHoverLine",

                    afterDatasetsDraw(chart) {
                        const activeElements = chart.tooltip?.getActiveElements();

                        if (!activeElements?.length) {
                            return;
                        }

                        const activeElement = activeElements[0].element;

                        const activeIndex = activeElements[0].index;

                        const x = activeElement.x;

                        const ctx = chart.ctx;

                        const chartArea = chart.chartArea;

                        // =================================================
                        // VERTICAL DASHED LINE
                        // =================================================

                        ctx.save();

                        ctx.beginPath();

                        ctx.setLineDash([3, 3]);

                        ctx.moveTo(
                            x,

                            chartArea.top,
                        );

                        ctx.lineTo(
                            x,

                            chartArea.bottom,
                        );

                        ctx.lineWidth = 1;

                        ctx.strokeStyle = "#d7dce5";

                        ctx.stroke();

                        ctx.restore();

                        // =================================================
                        // ACTIVE X AXIS LABEL
                        // =================================================

                        const xScale = chart.scales.x;

                        const labelX = xScale.getPixelForTick(activeIndex);

                        const labelY = xScale.bottom + 17;

                        const activeLabel = maintenanceDisplayLabels[activeIndex];

                        ctx.save();

                        ctx.font = '600 10px "Inter", sans-serif';

                        const textWidth = ctx.measureText(activeLabel).width;

                        const boxWidth = textWidth + 14;

                        const boxHeight = 22;

                        ctx.fillStyle = "#f1f1f3";

                        ctx.beginPath();

                        ctx.roundRect(
                            labelX - boxWidth / 2,

                            labelY - boxHeight / 2,

                            boxWidth,

                            boxHeight,

                            6,
                        );

                        ctx.fill();

                        ctx.fillStyle = "#475569";

                        ctx.textAlign = "center";

                        ctx.textBaseline = "middle";

                        ctx.fillText(
                            activeLabel,

                            labelX,

                            labelY,
                        );

                        ctx.restore();
                    },
                };

                // =====================================================
                // CREATE CHART
                // =====================================================

                new Chart(
                    maintenanceWorkloadCanvas,

                    {
                        type: "line",

                        data: {
                            labels: maintenanceDisplayLabels,

                            datasets: [
                                // =================================================
                                // MAIN BLUE LINE
                                // =================================================

                                {
                                    label: "Scheduled workload",

                                    data: maintenanceDisplayData,

                                    borderColor: "#0025cc",

                                    backgroundColor: "transparent",

                                    borderWidth: 1.5,

                                    // =================================================
                                    // CUSTOM PLUGIN HANDLES SHADOW
                                    // =================================================

                                    fill: false,

                                    tension: 0.42,

                                    cubicInterpolationMode: "monotone",

                                    pointRadius: 0,

                                    pointHoverRadius: 4,

                                    pointHitRadius: 25,

                                    pointHoverBackgroundColor: "#0025cc",

                                    pointHoverBorderColor: "white",

                                    pointHoverBorderWidth: 2,
                                },

                                // =================================================
                                // ORANGE TREND LINE
                                // =================================================

                                {
                                    label: "Workload trend",

                                    data: maintenanceTrendData,

                                    borderColor: "#e9b26f",

                                    backgroundColor: "transparent",

                                    borderWidth: 1.5,

                                    fill: false,

                                    tension: 0.42,

                                    cubicInterpolationMode: "monotone",

                                    pointRadius: 0,

                                    pointHoverRadius: 4,

                                    pointHitRadius: 25,

                                    pointHoverBackgroundColor: "#e9b26f",

                                    pointHoverBorderColor: "white",

                                    pointHoverBorderWidth: 2,
                                },
                            ],
                        },

                        options: {
                            responsive: true,

                            maintainAspectRatio: false,

                            normalized: true,

                            interaction: {
                                mode: "index",

                                intersect: false,
                            },

                            layout: {
                                padding: {
                                    top: 10,

                                    right: 8,

                                    bottom: 10,

                                    left: 0,
                                },
                            },

                            animation: {
                                duration: 350,
                            },

                            plugins: {
                                // =================================================
                                // HIDE LEGEND
                                // =================================================

                                legend: {
                                    display: false,
                                },

                                // =================================================
                                // DARK TOOLTIP
                                // =================================================

                                tooltip: {
                                    enabled: true,

                                    mode: "index",

                                    intersect: false,

                                    position: "nearest",

                                    backgroundColor: "#0f172a",

                                    titleColor: "white",

                                    bodyColor: "#94a3b8",

                                    borderWidth: 0,

                                    padding: {
                                        top: 10,

                                        right: 12,

                                        bottom: 10,

                                        left: 12,
                                    },

                                    cornerRadius: 7,

                                    caretSize: 0,

                                    displayColors: true,

                                    usePointStyle: false,

                                    boxWidth: 2,

                                    boxHeight: 14,

                                    boxPadding: 7,

                                    titleSpacing: 4,

                                    bodySpacing: 7,

                                    titleMarginBottom: 7,

                                    titleFont: {
                                        family: "Inter",

                                        size: 11,

                                        weight: "600",
                                    },

                                    bodyFont: {
                                        family: "Inter",

                                        size: 10,

                                        weight: "400",
                                    },

                                    callbacks: {
                                        // =================================================
                                        // TOOLTIP TITLE
                                        // =================================================

                                        title(context) {
                                            return context[0].label;
                                        },

                                        // =================================================
                                        // TOOLTIP VALUES
                                        // =================================================

                                        label(context) {
                                            const value = Math.round(
                                                Number(context.raw),
                                            );

                                            return (
                                                context.dataset.label + "     " + value
                                            );
                                        },
                                    },
                                },
                            },

                            scales: {
                                // =================================================
                                // X AXIS
                                // =================================================

                                x: {
                                    offset: false,

                                    border: {
                                        display: false,
                                    },

                                    grid: {
                                        display: false,
                                    },

                                    ticks: {
                                        autoSkip: false,

                                        color: "#8c929c",

                                        padding: 14,

                                        maxRotation: 0,

                                        minRotation: 0,

                                        font: {
                                            family: "Inter",

                                            size: 10,

                                            weight: "400",
                                        },
                                    },
                                },

                                // =================================================
                                // Y AXIS
                                // =================================================

                                y: {
                                    beginAtZero: true,

                                    grace: "20%",

                                    border: {
                                        display: false,
                                    },

                                    grid: {
                                        display: true,

                                        drawTicks: false,

                                        color: "rgba(226, 232, 240, 0.65)",

                                        lineWidth: 1,
                                    },

                                    ticks: {
                                        precision: 0,

                                        color: "#8c929c",

                                        padding: 14,

                                        maxTicksLimit: 5,

                                        font: {
                                            family: "Inter",

                                            size: 10,

                                            weight: "400",
                                        },
                                    },
                                },
                            },
                        },

                        plugins: [
                            // =====================================================
                            // DRAW BLUE SHADOW FIRST
                            // =====================================================

                            maintenanceBlueShadowPlugin,

                            // =====================================================
                            // DRAW HOVER ELEMENTS
                            // =====================================================

                            maintenanceWorkloadHoverLine,
                        ],
                    },
                );
            }

            // =====================================================
            // URGENT REPORT CAROUSEL
            // =====================================================

            const urgentTrack = document.getElementById("urgentCarouselTrack");

            const urgentPreviousButton = document.getElementById(
                "urgentPreviousButton",
            );

            const urgentNextButton = document.getElementById("urgentNextButton");

            let urgentCarouselIndex = 0;

            function getUrgentVisibleCards() {
                if (window.innerWidth <= 640) {
                    return 1;
                }

                return 2;
            }

            function updateUrgentCarousel() {
                if (!urgentTrack) {
                    return;
                }

                const cards = urgentTrack.querySelectorAll(".urgent-report-card");

                if (!cards.length) {
                    return;
                }

                const visibleCards = getUrgentVisibleCards();

                const maximumIndex = Math.max(0, cards.length - visibleCards);

                urgentCarouselIndex = Math.min(urgentCarouselIndex, maximumIndex);

                const cardWidth = cards[0].getBoundingClientRect().width;

                const gap = 14;

                urgentTrack.style.transform = `translateX(-${
                    urgentCarouselIndex * (cardWidth + gap)
                }px)`;
            }

            urgentPreviousButton?.addEventListener(
                "click",

                function () {
                    urgentCarouselIndex = Math.max(0, urgentCarouselIndex - 1);

                    updateUrgentCarousel();
                },
            );

            urgentNextButton?.addEventListener(
                "click",

                function () {
                    if (!urgentTrack) {
                        return;
                    }

                    const cardCount = urgentTrack.querySelectorAll(
                        ".urgent-report-card",
                    ).length;

                    const maximumIndex = Math.max(
                        0,
                        cardCount - getUrgentVisibleCards(),
                    );

                    urgentCarouselIndex = Math.min(
                        maximumIndex,
                        urgentCarouselIndex + 1,
                    );

                    updateUrgentCarousel();
                },
            );

            window.addEventListener(
                "resize",

                updateUrgentCarousel,
            );

            // =====================================================
            // MAINTENANCE CALENDAR
            // =====================================================

            const calendarElement = document.getElementById("dashboardCalendar");

            const calendarDaysElement = document.getElementById("calendarDays");

            const calendarMonthLabel = document.getElementById("calendarMonthLabel");

            const calendarSelectedEvents = document.getElementById(
                "calendarSelectedEvents",
            );

            if (
                calendarElement &&
                calendarDaysElement &&
                calendarMonthLabel &&
                calendarSelectedEvents
            ) {
                const calendarEvents = JSON.parse(
                    calendarElement.dataset.events || "[]",
                );

                const currentDate = new Date();

                const calendarYear = currentDate.getFullYear();

                const calendarMonth = currentDate.getMonth();

                let selectedDate = @json ($calendarSelectedDate);

                // =================================================
                // FORMAT DATE AS YYYY MM DD
                // =================================================

                function formatCalendarDate(
                    year,

                    month,

                    day,
                ) {
                    return [
                        year,

                        String(month + 1).padStart(2, "0"),

                        String(day).padStart(2, "0"),
                    ].join("-");
                }

                // =================================================
                // SHOW EVENTS FOR SELECTED DATE
                // =================================================

                function showCalendarEvents(date) {
                    const selectedEvents = calendarEvents.filter(
                        (event) => event.date === date,
                    );

                    calendarSelectedEvents.innerHTML = "";

                    if (!selectedEvents.length) {
                        calendarSelectedEvents.innerHTML = `

                                    <div class="dashboard-empty-state">

                                        No reports or maintenance schedules
                                        for this date.

                                    </div>

                                `;

                        return;
                    }

                    selectedEvents.forEach((event) => {
                        const eventElement = document.createElement("div");

                        eventElement.className = "calendar-event-item";

                        eventElement.innerHTML = `

                                        <div class="calendar-event-title">

                                            ${event.title}

                                        </div>


                                        <div class="calendar-event-description">

                                            ${event.description}

                                            ·

                                            ${event.location}

                                        </div>

                                    `;

                        eventElement.addEventListener(
                            "click",

                            function () {
                                if (event.url) {
                                    window.location.href = event.url;
                                }
                            },
                        );

                        calendarSelectedEvents.appendChild(eventElement);
                    });
                }

                // =================================================
                // BUILD CURRENT MONTH CALENDAR
                // =================================================

                function buildCalendar() {
                    const monthName = new Intl.DateTimeFormat(
                        "en",

                        {
                            month: "long",

                            year: "numeric",
                        },
                    ).format(
                        new Date(
                            calendarYear,

                            calendarMonth,

                            1,
                        ),
                    );

                    calendarMonthLabel.textContent = monthName;

                    calendarDaysElement.innerHTML = "";

                    const firstDay = new Date(
                        calendarYear,

                        calendarMonth,

                        1,
                    ).getDay();

                    const daysInMonth = new Date(
                        calendarYear,

                        calendarMonth + 1,

                        0,
                    ).getDate();

                    // =================================================
                    // EMPTY DAYS BEFORE MONTH START
                    // =================================================

                    for (let index = 0; index < firstDay; index++) {
                        const emptyDay = document.createElement("div");

                        emptyDay.className = "calendar-day empty";

                        calendarDaysElement.appendChild(emptyDay);
                    }

                    // =================================================
                    // MONTH DAYS
                    // =================================================

                    for (let day = 1; day <= daysInMonth; day++) {
                        const date = formatCalendarDate(
                            calendarYear,

                            calendarMonth,

                            day,
                        );

                        const dayButton = document.createElement("button");

                        dayButton.type = "button";

                        dayButton.className = "calendar-day";

                        dayButton.textContent = day;

                        const hasEvents = calendarEvents.some(
                            (event) => event.date === date,
                        );

                        if (hasEvents) {
                            dayButton.classList.add("has-events");
                        }

                        if (
                            date ===
                            formatCalendarDate(
                                currentDate.getFullYear(),

                                currentDate.getMonth(),

                                currentDate.getDate(),
                            )
                        ) {
                            dayButton.classList.add("today");
                        }

                        dayButton.addEventListener(
                            "click",

                            function () {
                                selectedDate = date;

                                showCalendarEvents(selectedDate);
                            },
                        );

                        calendarDaysElement.appendChild(dayButton);
                    }

                    showCalendarEvents(selectedDate);
                }

                buildCalendar();
            }
        });
    </script>

    <script>
        // =====================================================
        // REPORT ACTIVITY CHART
        // =====================================================

        document.addEventListener("DOMContentLoaded", function () {
            // =================================================
            // GET CANVAS
            // =================================================

            const canvas = document.getElementById("reportActivityChart");

            if (!canvas) {
                return;
            }

            // =================================================
            // REAL DATABASE VALUES FROM LARAVEL
            // =================================================

            const reportActivityData = @json ($reportActivityChart);

            // =================================================
            // FIND THE BUSIEST PERIOD
            // =================================================

            const highestValue = Math.max(...reportActivityData);

            const highestIndex = reportActivityData.indexOf(highestValue);

            // =================================================
            // CREATE BAR COLORS
            // DARKEST BAR = BUSIEST PERIOD
            // =================================================

            const barColors = reportActivityData.map(function (value, index) {
                return index === highestIndex ? "#0751d1" : "#BBC0FC";
            });

            // =================================================
            // CREATE CHART
            // =================================================

            new Chart(canvas, {
                type: "bar",

                data: {
                    labels: ["1-7", "8-14", "15-21", "22-28", "29-End"],

                    datasets: [
                        {
                            data: reportActivityData,

                            backgroundColor: barColors,

                            borderWidth: 0,

                            borderRadius: 7,

                            borderSkipped: false,

                            barPercentage: 0.68,

                            categoryPercentage: 0.78,
                        },
                    ],
                },

                options: {
                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false,
                        },

                        tooltip: {
                            displayColors: false,

                            callbacks: {
                                title: function (items) {
                                    return (
                                        items[0].label +
                                        " " +
                                        "{{
            now()->format(
                "M Y",
            )
        }}"
                                    );
                                },

                                label: function (context) {
                                    const count = context.raw;

                                    return (
                                        count + (count === 1 ? " report" : " reports")
                                    );
                                },
                            },
                        },
                    },

                    scales: {
                        // =================================================
                        // X AXIS
                        // =================================================

                        x: {
                            border: {
                                display: false,
                            },

                            grid: {
                                display: false,
                            },

                            ticks: {
                                color: "#94a3b8",

                                font: {
                                    family: "Inter",

                                    size: 9,
                                },
                            },
                        },

                        // =================================================
                        // Y AXIS
                        // =================================================

                        y: {
                            beginAtZero: true,

                            border: {
                                display: false,
                            },

                            grid: {
                                color: "#e2e8f0",

                                drawTicks: false,
                            },

                            ticks: {
                                precision: 0,

                                padding: 6,

                                color: "#64748b",

                                font: {
                                    family: "Inter",

                                    size: 9,
                                },
                            },
                        },
                    },
                },
            });
        });
    </script>

    <script>
        // =====================================================
        // CREATE LUCIDE ICONS
        // =====================================================

        lucide.createIcons();

        // =====================================================
        // CLOCK
        // =====================================================

        function updateClock() {
            const now = new Date();

            const date = now.toLocaleDateString("en-US", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
            });

            const time = now.toLocaleTimeString("en-US", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });

            const dateEl = document.getElementById("hdr-date");

            const timeEl = document.getElementById("hdr-time");

            if (dateEl) {
                dateEl.textContent = date;
            }

            if (timeEl) {
                timeEl.textContent = time;
            }
        }

        updateClock();

        setInterval(updateClock, 1000);

        // =====================================================
        // ACCORDION
        // =====================================================

        function toggleAccordion(id, chevId) {
            const content = document.getElementById(id);

            const chev = document.getElementById(chevId);

            if (content) {
                content.classList.toggle("open");
            }

            if (chev) {
                chev.classList.toggle("rotated");
            }
        }

        // =====================================================
        // CURRENT BUILDING AND FLOOR FILTER STATE
        // =====================================================

        let selectedBuilding = "all";

        let selectedFloor = "all";

        // =====================================================
        // BUILDING FILTER
        // =====================================================

        function filterBuilding(buildingId) {
            selectedBuilding = String(buildingId);

            // =====================================================
            // RESET FLOOR WHEN BUILDING CHANGES
            // =====================================================

            selectedFloor = "all";

            // =====================================================
            // UPDATE BUILDING BUTTON ACTIVE STATE
            // =====================================================

            document
                .querySelectorAll(".building-filter-button")
                .forEach((button) => {
                    const buttonBuilding = button.dataset.buildingFilter;

                    button.classList.toggle(
                        "active",
                        buttonBuilding === selectedBuilding,
                    );
                });

            // =====================================================
            // UPDATE FLOOR BUTTONS
            // SHOW ONLY FLOORS BELONGING TO SELECTED BUILDING
            // =====================================================

            document
                .querySelectorAll(".floor-filter-button")
                .forEach((button) => {
                    const floorId = button.dataset.floorFilter;

                    const floorBuildingId = button.dataset.buildingId;

                    // =====================================================
                    // ALL FLOORS BUTTON
                    // =====================================================

                    if (floorId === "all") {
                        button.style.display = "";

                        button.classList.add("active");

                        return;
                    }

                    // =====================================================
                    // REMOVE ACTIVE STATE FROM INDIVIDUAL FLOORS
                    // =====================================================

                    button.classList.remove("active");

                    // =====================================================
                    // SHOW FLOOR IF IT BELONGS TO SELECTED BUILDING
                    // =====================================================

                    const shouldShow =
                        selectedBuilding === "all" ||
                        floorBuildingId === selectedBuilding;

                    button.style.display = shouldShow ? "" : "none";
                });

            // =====================================================
            // APPLY FILTERS TO FLOOR SECTIONS
            // =====================================================

            applyRoomFilters();
        }

        // =====================================================
        // FLOOR FILTER
        // =====================================================

        function filterFloor(floorId) {
            selectedFloor = String(floorId);

            // =====================================================
            // UPDATE FLOOR BUTTON ACTIVE STATE
            // =====================================================

            document
                .querySelectorAll(".floor-filter-button")
                .forEach((button) => {
                    const buttonFloor = button.dataset.floorFilter;

                    button.classList.toggle(
                        "active",
                        buttonFloor === selectedFloor,
                    );
                });

            // =====================================================
            // APPLY FILTERS TO FLOOR SECTIONS
            // =====================================================

            applyRoomFilters();
        }

        // =====================================================
        // APPLY BUILDING AND FLOOR FILTERS
        // =====================================================

        function applyRoomFilters() {
            const sections = document.querySelectorAll(".room-floor-section");

            sections.forEach((section) => {
                const sectionBuilding = String(section.dataset.building);

                const sectionFloor = String(section.dataset.floor);

                // =====================================================
                // CHECK BUILDING
                // =====================================================

                const matchesBuilding =
                    selectedBuilding === "all" ||
                    sectionBuilding === selectedBuilding;

                // =====================================================
                // CHECK FLOOR
                // =====================================================

                const matchesFloor =
                    selectedFloor === "all" || sectionFloor === selectedFloor;

                // =====================================================
                // SHOW OR HIDE FLOOR SECTION
                // =====================================================

                section.style.display =
                    matchesBuilding && matchesFloor ? "block" : "none";
            });

            // =====================================================
            // REFRESH LUCIDE ICONS
            // =====================================================

            lucide.createIcons();
        }

        // =====================================================
        // INITIALIZE DASHBOARD FILTERS
        // =====================================================

        document.addEventListener("DOMContentLoaded", function () {
            selectedBuilding = "all";

            selectedFloor = "all";

            // =====================================================
            // SET ALL BUILDINGS BUTTON ACTIVE
            // =====================================================

            document
                .querySelectorAll(".building-filter-button")
                .forEach((button) => {
                    button.classList.toggle(
                        "active",
                        button.dataset.buildingFilter === "all",
                    );
                });

            // =====================================================
            // SET ALL FLOORS BUTTON ACTIVE
            // =====================================================

            document
                .querySelectorAll(".floor-filter-button")
                .forEach((button) => {
                    button.style.display = "";

                    button.classList.toggle(
                        "active",
                        button.dataset.floorFilter === "all",
                    );
                });

            // =====================================================
            // SHOW ALL DATABASE FLOOR SECTIONS
            // =====================================================

            applyRoomFilters();
        });

        // =====================================================
        // URGENT REPORT CAROUSEL
        // =====================================================

        function scrollUrgentCarousel(direction) {
            const carousel = document.getElementById("urgent-carousel");

            if (!carousel) {
                return;
            }

            // =====================================================
            // GET ONE REPORT CARD
            // =====================================================

            const card = carousel.querySelector(".urgent-media-card");

            if (!card) {
                return;
            }

            // =====================================================
            // CALCULATE ONE CARD WIDTH INCLUDING GAP
            // =====================================================

            const gap = 16;

            const scrollAmount = card.offsetWidth + gap;

            // =====================================================
            // MOVE ONE CARD LEFT OR RIGHT
            // =====================================================

            carousel.scrollBy({
                left: direction * scrollAmount,

                behavior: "smooth",
            });
        }

        // =====================================================
        // UPDATE CAROUSEL BUTTON VISIBILITY
        // =====================================================

        // =====================================================
        // UPDATE URGENT CAROUSEL BUTTON STATES
        // =====================================================

        function updateUrgentCarouselButtons() {
            const carousel = document.getElementById("urgent-carousel");

            const previousButton = document.getElementById(
                "urgent-carousel-prev",
            );

            const nextButton = document.getElementById("urgent-carousel-next");

            if (!carousel || !previousButton || !nextButton) {
                return;
            }

            // =====================================================
            // CHECK SCROLL POSITION
            // =====================================================

            const isAtBeginning = carousel.scrollLeft <= 2;

            const isAtEnd =
                carousel.scrollLeft + carousel.clientWidth >=
                carousel.scrollWidth - 2;

            // =====================================================
            // DISABLE PREVIOUS BUTTON AT BEGINNING
            // =====================================================

            previousButton.disabled = isAtBeginning;

            previousButton.classList.toggle(
                "urgent-carousel-button-active",
                !isAtBeginning,
            );

            // =====================================================
            // DISABLE NEXT BUTTON AT END
            // =====================================================

            nextButton.disabled = isAtEnd;

            nextButton.classList.toggle(
                "urgent-carousel-button-active",
                !isAtEnd,
            );
        }

        // =====================================================
        // INITIALIZE CAROUSEL
        // =====================================================

        document.addEventListener("DOMContentLoaded", function () {
            const carousel = document.getElementById("urgent-carousel");

            if (!carousel) {
                return;
            }

            // =====================================================
            // UPDATE BUTTONS WHEN USER SCROLLS
            // =====================================================

            carousel.addEventListener("scroll", updateUrgentCarouselButtons);

            // =====================================================
            // UPDATE BUTTONS WHEN WINDOW RESIZES
            // =====================================================

            window.addEventListener("resize", updateUrgentCarouselButtons);

            // =====================================================
            // INITIAL BUTTON STATE
            // =====================================================

            updateUrgentCarouselButtons();
        });
    </script>

    <script>
        (function initMaintenanceDashboardSmoothScroll() {
            const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
            const scroller = document.querySelector("body.mp-layout main");

            if (!scroller || reduceMotion.matches) {
                return;
            }

            let current = scroller.scrollTop;
            let target = scroller.scrollTop;
            let rafId = 0;
            const ease = 0.18;

            function getDelta(event) {
                let delta = event.deltaY;

                if (event.deltaMode === 1) {
                    delta *= 16;
                } else if (event.deltaMode === 2) {
                    delta *= scroller.clientHeight;
                }

                return delta;
            }

            function isVerticallyScrollable(element) {
                const style = window.getComputedStyle(element);
                const overflowY = style.overflowY;
                const canOverflow =
                    overflowY === "auto" ||
                    overflowY === "scroll" ||
                    overflowY === "overlay";

                return canOverflow && element.scrollHeight > element.clientHeight + 1;
            }

            function shouldIgnore(event) {
                if (event.defaultPrevented || event.ctrlKey) {
                    return true;
                }

                if (Math.abs(event.deltaY) < Math.abs(event.deltaX)) {
                    return true;
                }

                const origin =
                    event.target && event.target.nodeType === 1
                        ? event.target
                        : event.target && event.target.parentElement;

                if (
                    origin &&
                    origin.closest &&
                    origin.closest(
                        "#building3DViewport, .swal2-container, [role='dialog']"
                    )
                ) {
                    return true;
                }

                let node = origin;

                while (node && node !== scroller) {
                    if (isVerticallyScrollable(node)) {
                        const scrollingUp = event.deltaY < 0;
                        const atTop = node.scrollTop <= 0;
                        const atBottom =
                            node.scrollTop + node.clientHeight >=
                            node.scrollHeight - 1;

                        if (!(scrollingUp && atTop) && !(!scrollingUp && atBottom)) {
                            return true;
                        }
                    }

                    node = node.parentElement;
                }

                return false;
            }

            function tick() {
                current += (target - current) * ease;

                if (Math.abs(target - current) < 0.4) {
                    current = target;
                    scroller.scrollTop = current;
                    rafId = 0;
                    return;
                }

                scroller.scrollTop = current;
                rafId = requestAnimationFrame(tick);
            }

            scroller.addEventListener(
                "wheel",
                function (event) {
                    if (shouldIgnore(event)) {
                        current = scroller.scrollTop;
                        target = scroller.scrollTop;
                        return;
                    }

                    event.preventDefault();

                    const maxScroll = Math.max(
                        0,
                        scroller.scrollHeight - scroller.clientHeight
                    );

                    target = Math.max(0, Math.min(maxScroll, target + getDelta(event)));

                    if (!rafId) {
                        current = scroller.scrollTop;
                        rafId = requestAnimationFrame(tick);
                    }
                },
                { passive: false }
            );

            scroller.addEventListener(
                "scroll",
                function () {
                    if (rafId) {
                        return;
                    }

                    current = scroller.scrollTop;
                    target = scroller.scrollTop;
                },
                { passive: true }
            );
        })();
    </script>

    @include('layouts.partials.equipment-layout-icons')
    @include('layouts.partials.equipment-asset-tag')
    @include('layouts.partials.equipment-category-detect')
    @include('layouts.partials.equipment-photo-viewer')

    <style>
        .eq-modal-scroll {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 transparent;
        }

        .eq-modal-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .eq-modal-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .eq-modal-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .eq-modal-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

@endsection
