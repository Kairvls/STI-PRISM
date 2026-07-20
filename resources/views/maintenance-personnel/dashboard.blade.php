@extends ("layouts.maintenance-layout")

@section ("title", "Maintenance Dashboard")

@section ("content")
    {{-- ══════════════════════════════════════════════════════════════
     PRISM · Maintenance Personnel Dashboard
     resources/views/maintenance/dashboard.blade.php
══════════════════════════════════════════════════════════════ --}}

    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    />
    <script src="https://unpkg.com/lucide@latest"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

                    background: #ffffff;

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

                    align-items: start;
                }


                /* ===================================================== */
                /* LEFT MAIN CONTENT */
                /* ===================================================== */

                .maintenance-dashboard-main {
                    min-width: 0;

                    display: flex;

                    flex-direction: column;

                    gap: 24px;
                }


                /* ===================================================== */
                /* RIGHT SIDEBAR */
                /* ===================================================== */

                .maintenance-dashboard-sidebar {
                    min-width: 0;

                    display: flex;

                    flex-direction: column;

                    gap: 20px;
                }


                /* ===================================================== */
                /* SIDEBAR CARD */
                /* ===================================================== */

                .dashboard-side-card {
                    background: #ffffff;

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

                    gap: 8px;

                    flex: 0 0 auto;

                    white-space: nowrap;
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

                    padding: 0 20px;

                    border: 1px solid #e2e8f0;

                    border-radius: 10px;

                    background: #ffffff;

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

                }

                /* ===================================================== */
                /* ACTIVITY SIDEBAR CARD */
                /* ===================================================== */

                .activity-sidebar-card {
                    width: 100%;

                    overflow: hidden;

                    background: #ffffff;

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
                    width: 32px;

                    height: 32px;

                    display: inline-flex;

                    align-items: center;

                    justify-content: center;

                    border: 0;

                    border-radius: 9px;

                    background: transparent;

                    color: #94a3b8;

                    cursor: pointer;
                }


                .activity-sidebar-menu:hover {
                    background: #f8fafc;

                    color: #334155;
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

                    background: #ffffff;

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

                    background: #ffffff;

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

                    background: #ffffff;

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

                    background: #ffffff;

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

                    background: #4f46e5;

                    color: #ffffff;
                }


                .urgent-carousel-button-active:hover {
                    background: #4338ca;

                    color: #ffffff;

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

                    background: #ffffff;
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
                    padding: 28px 26px;

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

                    background: #fee2e2;

                    border-radius: 999px;

                    color: #dc2626;

                    font-size: 9px;

                    font-weight: 700;
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

                    background: #ffffff;

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

                    background: #ffffff;

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

            background: #ffffff;

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
        /* MAINTENANCE CALENDAR */
        /* RIGHT SIDEBAR ABOVE ACTIVITY */
        /* ===================================================== */

        .dashboard-calendar-card {
            min-width: 0;

            overflow: hidden;

            background: #ffffff;

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
            background: #0f172a;

            color: #ffffff;
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

            background: #ffffff;

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
            background: #0f172a;

            color: #ffffff;

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
            background: #ffffff;
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

            background:#ffffff;

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
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;

            /* IMPORTANT: Both sides get equal height */
            align-items: stretch;
        }

        .dashboard-overview-row > * {
            min-width: 0;
        }


        /* =====================================================
        FLOW ANALYTICS CARD
        More compact height
        ===================================================== */

        .flow-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 28px;

            /* CHANGED: Reduced from 34px */
            padding: 26px;
            box-shadow:
                0 1px 2px rgba(0, 0, 0, 0.02),
                0 8px 24px rgba(0, 0, 0, 0.04);
        }

        .flow-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .maintenance-hero-eyebrows {
            /* CHANGED: Reduced spacing */
            margin-bottom: 6px;

            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;

            color: #6b7280;
        }

        .flow-title {
            
            font-size: clamp(18px, 2.5vw, 20px);

            font-weight: 600;
            line-height: 1.1;

            color: #111827;
        }

        .flow-subtitle {
            color: #94a3b8;
            font-size: 14px;
        }

        .flow-menu {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #f4f6f9;
        }


        /* =====================================================
        FLOW STATS
        ===================================================== */

        .flow-stats {
            /* CHANGED: Reduced from 38px */
            margin-top: 24px;

            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .flow-stat h2 {
            font-size: 22px;
            font-weight: 600;
            color: #111827;
        }

        .flow-stat p {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            margin-top: 4px;
        }


        /* =====================================================
        FLOW GRAPH AREA
        ===================================================== */

        .flow-area {
            position: relative;

            /* CHANGED: Reduced from 45px */
            margin-top: 25px;

            /* CHANGED: Reduced from 240px */
            height: 190px;

            /* CHANGED: Match new 26px card padding */
            margin-left: -26px;
            margin-right: -26px;

            width: calc(100% + 52px);
        }

        .flow-svg {
            width: 100%;
            height: 100%;
        }


        /* =====================================================
        MAINTENANCE HERO GRID
        ===================================================== */

        
        .maintenance-hero {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));

            /* First row is summary cards */
            /* Second row fills ALL remaining height */
            grid-template-rows: 130px minmax(0, 1fr);

            gap: 12px;

            width: 100%;
            height: 100%;

            min-width: 0;
            min-height: 0;
        }

        .maintenance-hero-summary {
            display: contents;
        }


        /* =====================================================
        TOP SUMMARY CARDS
        ===================================================== */

        
        .maintenance-summary-card {
            display: flex;
            flex-direction: column;

            min-width: 0;

            /* Fill the 130px grid row */
            height: 100%;

            padding: 22px 26px;

            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 28px;
        }

        .maintenance-summary-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
        }

        .maintenance-summary-number {
            display: block;

            /* CHANGED: Reduced spacing */
            margin-top: 4px;

            font-size: 22px;
            font-weight: 600;
            line-height: 1;

            color: #111827;
        }

        .maintenance-summary-status {
            /* CHANGED: Reduced spacing */
            margin-top: 6px;

            font-size: 14px;
            color: #6b7280;
        }

        .maintenance-summary-action {
            display: flex;
            align-items: center;
            justify-content: center;

            width: 100%;

            /* CHANGED: Slightly shorter */
            height: 36px;

            margin-top: auto;

            border: 1px solid #e5e7eb;
            border-radius: 999px;

            font-size: 13px;
            font-weight: 600;

            color: #111827;
            background: #ffffff;

            transition: 0.2s ease;
        }

        .maintenance-summary-action:hover {
            background: #f9fafb;
        }


        /* =====================================================
        BOTTOM FULL WIDTH HERO CARD
        ===================================================== */

        .maintenance-hero-main {
            grid-column: 1 / -1;

            display: flex;
            flex-direction: column;

            min-width: 0;
            min-height: 0;

            /* Fill remaining space until left card bottom */
            height: 100%;

            padding: 26px;

            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 28px;

            box-shadow:
                0 1px 2px rgba(0, 0, 0, 0.02),
                0 8px 24px rgba(0, 0, 0, 0.04);
        }

        .maintenance-hero-eyebrow {
            /* CHANGED: Reduced spacing */
            margin-bottom: 8px;

            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;

            color: #6b7280;
        }

        .maintenance-hero-title {
            margin: 0;
            max-width: 700px;

            /* CHANGED: Slightly smaller */
            font-size: clamp(18px, 2.5vw, 20px);

            font-weight: 600;
            line-height: 1.1;

            color: #111827;
        }

        .maintenance-hero-description {
            max-width: 650px;

            /* CHANGED: Reduced spacing */
            margin-top: 10px;

            font-size: 12px;
            line-height: 1.5;

            color: #6b7280;
        }

        /* =====================================================
        MINI ACTIVITY CHART
        ===================================================== */

        .maintenance-mini-chart {
            width: 100%;
            height: 55px;
            margin-top: 14px;
        }

        .maintenance-mini-chart-bars {
            display: flex;
            align-items: stretch;
            gap: 6px;

            width: 100%;
            height: 100%;
        }

        .maintenance-mini-chart-item {
            flex: 1;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;

            min-width: 0;
        }

        .maintenance-mini-chart-bar {
            display: block;

            width: 100%;
            max-width: 24px;

            background: #9ca3af;

            border-radius: 4px 4px 0 0;

            min-height: 2px;

            transition: height 0.3s ease;
        }

        .maintenance-mini-chart-item small {
            margin-top: 4px;

            font-size: 9px;
            color: #9ca3af;
        }

        /* =====================================================
        ACTION BUTTONS
        ===================================================== */

        .maintenance-hero-actions {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;

            width: 100%;

            /* Push buttons to bottom of card */
            margin-top: auto;
            padding-top: 14px;
        }

        .maintenance-hero-primary,
        .maintenance-hero-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            /* Both buttons share the available space */
            flex: 1;

            /* Important for preventing overflow */
            min-width: 0;

            height: 38px;
            padding: 0 12px;

            border-radius: 999px;

            font-size: 14px;
            font-weight: 600;

            white-space: nowrap;

            transition: 0.2s ease;
        }

        .maintenance-hero-primary {
            background: #111827;
            color: #ffffff;
            border: 1px solid #111827;
        }

        .maintenance-hero-primary:hover {
            background: #000000;
        }

        .maintenance-hero-secondary {
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .maintenance-hero-secondary:hover {
            background: #f9fafb;
        }


        /* =====================================================
        RESPONSIVE
        ===================================================== */

        @media (max-width: 1024px) {
            .dashboard-overview-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {

            .maintenance-hero {
                grid-template-columns: 1fr;
            }

            .maintenance-hero-main {
                grid-column: 1;
            }

            .maintenance-summary-card {
                height: auto;
                min-height: 140px;
                padding: 20px;
                border-radius: 22px;
            }

            .maintenance-hero-main {
                padding: 22px;
                border-radius: 22px;
            }

            .maintenance-hero-actions {
                flex-direction: column;
            }

            .maintenance-hero-primary,
            .maintenance-hero-secondary {
                width: 100%;
            }
        }

        /* =====================================================
        3D BUILDING OVERVIEW
        ADD THIS INSIDE YOUR STYLE
        ===================================================== */

        .dashboard-building-section {
            width: 100%;
            min-width: 0;

            overflow: hidden;

            background: #ffffff;

            border: 1px solid #e5e7eb;
            border-radius: 24px;

            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.04);
        }


        /* =====================================================
        HEADER
        ===================================================== */

        .dashboard-building-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 24px;

            padding: 22px 26px;
        }


        .dashboard-building-eyebrow {
            margin: 0 0 5px;

            color: #94a3b8;

            font-size: 9px;
            font-weight: 700;

            letter-spacing: 0.08em;
        }


        .dashboard-building-title {
            margin: 0;

            color: #0f172a;

            font-family: "Outfit", sans-serif;

            font-size: 20px;
            font-weight: 700;
        }


        .dashboard-building-subtitle {
            margin: 4px 0 0;

            color: #94a3b8;

            font-size: 11px;
        }


        /* =====================================================
        VIEW BUILDING BUTTON
        ===================================================== */

        .dashboard-building-action {
            height: 40px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 0 15px;

            flex-shrink: 0;

            border: 1px solid #e2e8f0;
            border-radius: 10px;

            background: #ffffff;

            color: #334155;

            font-size: 11px;
            font-weight: 600;

            text-decoration: none;

            transition: 0.2s ease;
        }


        .dashboard-building-action:hover {
            background: #f8fafc;

            border-color: #cbd5e1;
        }


        .dashboard-building-action svg {
            width: 15px;
            height: 15px;
        }


        /* =====================================================
        3D BUILDING VIEWPORT
        ===================================================== */

        


        /* =====================================================
        3D BUILDING IMAGE
        ===================================================== */

        /* =====================================================
        PHASE 1: THREE.JS 3D BUILDING VIEWPORT
        ===================================================== */

        .dashboard-building-view {
            position: relative;

            width: 100%;

            /* Controls how tall the building area is */
            height: clamp(220px, 34vw, 430px);

            overflow: hidden;

            background: #f8f7f4;
        }


        /* THREE.JS RENDER CONTAINER */

        #building3DViewport {
            position: absolute;
            inset: 0;

            width: 100%;
            height: 100%;
        }


        /* THREE.JS CANVAS */

        #building3DViewport canvas {
            display: block;

            width: 100% !important;
            height: 100% !important;

            cursor: grab;
        }

        #building3DViewport canvas:active {
            cursor: grabbing;
        }


        /* =====================================================
        3D CONTROLS
        ===================================================== */

        .building-3d-controls {
            position: absolute;

            right: 20px;
            bottom: 20px;

            z-index: 10;

            display: flex;
            align-items: center;

            gap: 6px;

            padding: 6px;

            background: rgba(255, 255, 255, 0.9);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 12px;

            box-shadow:
                0 4px 16px rgba(15, 23, 42, 0.08);
        }


        .building-3d-control {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: 8px;

            background: transparent;

            color: #475569;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .building-3d-control:hover {
            background: #f1f5f9;

            color: #0f172a;
        }


        .building-3d-control svg {
            width: 15px;
            height: 15px;
        }


        /* Keep overlays above Three.js */

        .dashboard-building-badge {
            z-index: 10;
        }


        /* =====================================================
        FLOATING BADGE
        ===================================================== */

        .dashboard-building-badge {
            position: absolute;

            left: 20px;
            bottom: 20px;

            display: inline-flex;
            align-items: center;

            gap: 7px;

            padding: 9px 13px;

            background: rgba(255, 255, 255, 0.9);

            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 999px;

            box-shadow:
                0 4px 16px rgba(15, 23, 42, 0.08);

            color: #334155;

            font-size: 10px;
            font-weight: 600;
        }


        .dashboard-building-badge-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #22c55e;
        }

        /* =====================================================
        PHASE 7.4
        3D BUILDING FLOOR FILTERS
        ===================================================== */

        .building-floor-filters {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 20;

            display: flex;
            align-items: center;
            gap: 6px;

            padding: 5px;

            background: rgba(2, 6, 23, 0.72);
            border: 1px solid rgba(34, 211, 238, 0.2);
            border-radius: 999px;

            backdrop-filter: blur(12px);
        }

        .building-floor-filter {
            height: 34px;
            padding: 0 14px;

            border: 0;
            border-radius: 999px;

            background: transparent;
            color: #94a3b8;

            font-size: 12px;
            font-weight: 600;

            white-space: nowrap;
            cursor: pointer;

            transition: 0.2s ease;
        }

        .building-floor-filter:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
        }

        .building-floor-filter.active {
            color: #ffffff;
            background: rgba(34, 211, 238, 0.18);
            box-shadow:
                inset 0 0 0 1px rgba(34, 211, 238, 0.35),
                0 0 16px rgba(34, 211, 238, 0.08);
        }


        /* =====================================================
        RESPONSIVE FLOOR FILTER
        ===================================================== */

        @media (max-width: 640px) {

            .building-floor-filters {
                max-width: calc(100% - 32px);
                overflow-x: auto;
            }

        }


        /* =====================================================
        RESPONSIVE
        ===================================================== */

        @media (max-width: 768px) {

            .dashboard-building-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .dashboard-building-action {
                width: 100%;
            }

            .dashboard-building-view {
                height: 400px;
            }

        }

        /* =====================================================
        PHASE 7.7
        3D ROOM HOVER TOOLTIP
        ===================================================== */

        .building-room-tooltip {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 50;

            min-width: 150px;
            padding: 12px 14px;

            background: rgba(2, 6, 23, 0.92);

            border:
                1px solid
                rgba(34, 211, 238, 0.25);

            border-radius: 12px;

            box-shadow:
                0 12px 30px
                rgba(0, 0, 0, 0.28);

            backdrop-filter:
                blur(12px);

            pointer-events: none;

            opacity: 0;
            visibility: hidden;

            transform:
                translate(
                    12px,
                    12px
                );

            transition:
                opacity 0.15s ease,
                visibility 0.15s ease;
        }


        /* =====================================================
        TOOLTIP VISIBLE STATE
        ===================================================== */

        .building-room-tooltip.visible {
            opacity: 1;
            visibility: visible;
        }


        /* =====================================================
        TOOLTIP HEADER
        ===================================================== */

        .building-room-tooltip-header {
            display: flex;
            align-items: center;
            gap: 6px;

            margin-bottom: 5px;
        }


        .building-room-tooltip-dot {
            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: #22d3ee;

            box-shadow:
                0 0 8px
                rgba(34, 211, 238, 0.8);
        }


        .building-room-tooltip-eyebrow {
            font-size: 9px;
            font-weight: 700;

            letter-spacing: 0.12em;

            color: #67e8f9;
        }


        /* =====================================================
        ROOM NAME
        ===================================================== */

        .building-room-tooltip-name {
            font-size: 14px;
            font-weight: 700;

            color: #ffffff;
        }


        /* =====================================================
        FLOOR AND STATUS
        ===================================================== */

        .building-room-tooltip-details {
            display: flex;
            align-items: center;
            gap: 5px;

            margin-top: 3px;

            font-size: 11px;

            color: #94a3b8;
        }


        .building-room-tooltip-separator {
            opacity: 0.5;
        }

        /* ===================================================== */
        /* PHASE 7.8: COMPACT ROOM DETAILS PANEL */
        /* ===================================================== */

        .building-room-details-panel {
            position: absolute;
            top: 76px;
            right: 20px;
            width: 280px;
            padding: 18px;

            background: rgba(2, 6, 23, 0.94);
            border: 1px solid rgba(34, 211, 238, 0.25);
            border-radius: 16px;

            backdrop-filter: blur(18px);

            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.35),
                0 0 30px rgba(34, 211, 238, 0.06);

            z-index: 20;

            opacity: 0;
            visibility: hidden;
            transform: translateX(15px);

            transition:
                opacity 0.2s ease,
                transform 0.2s ease,
                visibility 0.2s ease;
        }

        .building-room-details-panel.visible {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }


        /* HEADER */

        .building-room-details-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .building-room-details-eyebrow {
            display: block;

            margin-bottom: 5px;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: 0.12em;

            color: #22d3ee;
        }

        .building-room-details-header h3 {
            margin: 0;

            font-size: 18px;
            font-weight: 700;

            color: #ffffff;
        }


        /* CLOSE */

        .building-room-details-close {
            width: 30px;
            height: 30px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;

            background: rgba(15, 23, 42, 0.8);

            color: #94a3b8;

            cursor: pointer;
        }

        .building-room-details-close:hover {
            color: #ffffff;
            border-color: rgba(34, 211, 238, 0.4);
        }

        .building-room-details-close svg {
            width: 15px;
            height: 15px;
        }


        /* ROOM INFO */

        .building-room-details-info {
            margin-top: 16px;

            padding: 12px;

            background: rgba(15, 23, 42, 0.65);

            border-radius: 10px;
        }

        .building-room-details-row {
            display: flex;
            justify-content: space-between;

            padding: 5px 0;

            font-size: 12px;
        }

        .building-room-details-row span {
            color: #64748b;
        }

        .building-room-details-row strong {
            color: #e2e8f0;
        }


        /* STATS */

        .building-room-details-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);

            gap: 7px;

            margin-top: 10px;
        }

        .building-room-details-stat {
            padding: 10px 6px;

            text-align: center;

            background: rgba(15, 23, 42, 0.65);

            border-radius: 10px;
        }

        .building-room-details-stat span {
            display: block;

            min-height: 28px;

            font-size: 9px;
            line-height: 1.3;

            color: #64748b;
        }

        .building-room-details-stat strong {
            display: block;

            margin-top: 4px;

            font-size: 17px;

            color: #ffffff;
        }


        /* VIEW ROOM BUTTON */

        .building-room-details-view {
            width: 100%;

            margin-top: 12px;
            padding: 10px 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            border: 1px solid rgba(34, 211, 238, 0.35);
            border-radius: 10px;

            background: rgba(8, 145, 178, 0.15);

            color: #67e8f9;

            font-size: 12px;
            font-weight: 600;

            cursor: pointer;
        }

        .building-room-details-view:hover {
            background: rgba(8, 145, 178, 0.25);
        }

        .building-room-details-view svg {
            width: 14px;
            height: 14px;
        }

        /* =====================================================
        PHASE 8.2 PART 4
        BACK TO BUILDING OVERVIEW BUTTON
        ===================================================== */

        .building-back-overview-btn {
            position: absolute;
            left: 24px;
            bottom: 24px;
            z-index: 20;

            display: flex;
            align-items: center;
            gap: 8px;

            padding: 10px 16px;

            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 999px;

            color: #334155;
            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);

            transition:
                transform 0.2s ease,
                background 0.2s ease;
        }

        .building-back-overview-btn:hover {
            transform: translateY(-2px);
            background: #ffffff;
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

        <div class="dashboard-toolbar flex w-full items-center gap-4">
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
            {{-- MAILBOX --}}
            {{-- ===================================================== --}}

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

                <a
                    href="{{ url('/maintenance/equipment/create') }}"
                    class="group inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                >
                    <!--<i
                        data-lucide="package-plus"
                        class="h-4 w-4 text-gray-400 transition-colors group-hover:text-yellow-600"
                    ></i>-->

                    <span>Equipment</span>
                </a>

                {{-- ===================================================== --}}
                {{-- ADD SCHEDULE --}}
                {{-- ===================================================== --}}

                <a
                    href="{{ url('/maintenance/schedules/create') }}"
                    class="group inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                >
                    <!--<i
                        data-lucide="calendar-plus"
                        class="h-4 w-4 text-gray-400 transition-colors group-hover:text-yellow-600"
                    ></i>-->

                    <span>Schedule</span>
                </a>

                {{-- ===================================================== --}}
                {{-- ADD BORROWING --}}
                {{-- ===================================================== --}}

                <a
                    href="{{ url('/maintenance/borrowing/create') }}"
                    class="group inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                >
                    <!--<i
                        data-lucide="clipboard-plus"
                        class="h-4 w-4 text-gray-400 transition-colors group-hover:text-yellow-600"
                    ></i>-->

                    <span>Borrowing</span>
                </a>
            </div>
        </div>

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

                

                

                <div class="dashboard-overview-row">

                {{-- ===================================================== --}}
                {{-- PREMIUM FLOW ANALYTICS CARD --}}
                {{-- ===================================================== --}}

                @php
                    $total = max(1, $urgentReports + $underMaintenance + $borrowedEquipment);

                    $urgentPercent = round(($urgentReports / $total) * 100);
                    $maintenancePercent = round(($underMaintenance / $total) * 100);
                    $borrowedPercent = round(($borrowedEquipment / $total) * 100);
                @endphp
                <div class="flow-card">

                    <div >
                        {{-- Header --}}
                        <div class="flow-header">
                            <div>
                                <div class="maintenance-hero-eyebrows">
                                    MAINTENANCE OPERATIONS
                                </div>

                                <h2 class="flow-title">Equipment Statistics</h2>
                            </div>

                            <button class="flow-menu">
                                <i data-lucide="more-vertical" class="h-4 w-4"></i>
                            </button>
                        </div>

                        {{-- Statistics --}}
                        <div class="flow-stats">
                            <div class="flow-stat">
                                <h2>{{ $urgentReports }}</h2>

                                <p>Urgent Reports</p>
                            </div>

                            <div class="flow-stat">
                                <h2>{{ $underMaintenance }}</h2>

                                <p>Under Maintenance</p>
                            </div>

                            <div class="flow-stat">
                                <h2>{{ $borrowedEquipment }}</h2>

                                <p>Borrowed Equipment</p>
                            </div>
                        </div>

                        
                    </div>
                    {{-- FLOW AREA --}}
                    <div class="flow-area">
                        {{-- LEFT BADGE --}}

                        

                        <svg
                            id="flowRibbon"
                            class="flow-svg"
                            viewBox="0 0 1000 220"
                            preserveAspectRatio="none"
                        >
                            <defs>
                                {{-- ===================================================== --}}
                                {{-- SOFT GLOW --}}
                                {{-- ===================================================== --}}

                                <filter
                                    id="glowBlur"
                                    x="-40%"
                                    y="-80%"
                                    width="180%"
                                    height="260%"
                                >
                                    <feGaussianBlur stdDeviation="28" />
                                </filter>

                                {{-- ===================================================== --}}
                                {{-- LIGHT BLOOM --}}
                                {{-- ===================================================== --}}

                                <filter
                                    id="softBlur"
                                    x="-30%"
                                    y="-60%"
                                    width="160%"
                                    height="220%"
                                >
                                    <feGaussianBlur stdDeviation="10" />
                                </filter>

                                {{-- ===================================================== --}}
                                {{-- MAIN COLOR --}}
                                {{-- ===================================================== --}}

                                <linearGradient
                                    id="flowGradient"
                                    x1="0%"
                                    y1="0%"
                                    x2="100%"
                                    y2="0%"
                                >
                                    <stop offset="0%" stop-color="#BBC8FC" />

                                    <stop offset="16%" stop-color="#8FA4FA" />

                                    <stop offset="34%" stop-color="#6381F8" />

                                    <stop offset="48%" stop-color="#2750F5" />

                                    <stop offset="63%" stop-color="#0B3AF4" />

                                    <stop offset="80%" stop-color="#092FC8" />

                                    <stop offset="100%" stop-color="#07259C" />
                                </linearGradient>

                                {{-- ===================================================== --}}
                                {{-- GLASS HIGHLIGHT --}}
                                {{-- ===================================================== --}}

                                <linearGradient
                                    id="highlightGradient"
                                    x1="0%"
                                    y1="0%"
                                    x2="0%"
                                    y2="100%"
                                >
                                    <stop
                                        offset="0%"
                                        stop-color="rgba(255,255,255,.95)"
                                    />

                                    <stop
                                        offset="55%"
                                        stop-color="rgba(255,255,255,.25)"
                                    />

                                    <stop
                                        offset="100%"
                                        stop-color="rgba(255,255,255,0)"
                                    />
                                </linearGradient>
                            </defs>

                            

                            {{-- ===================================================== --}}
                            {{-- OUTER GLOW --}}
                            {{-- ===================================================== --}}

                            <path
                                id="glowLayer"
                                filter="url(#glowBlur)"
                                fill="url(#flowGradient)"
                                opacity=".16"
                            />

                            {{-- ===================================================== --}}
                            {{-- OUTER TRANSLUCENT RIBBON --}}
                            {{-- ===================================================== --}}

                            <path
                                id="outerRibbon"
                                fill="url(#flowGradient)"
                                opacity=".18"
                            />

                            {{-- ===================================================== --}}
                            {{-- MIDDLE TRANSLUCENT RIBBON --}}
                            {{-- ===================================================== --}}

                            <path
                                id="middleRibbon"
                                fill="url(#flowGradient)"
                                opacity=".36"
                            />

                            {{-- ===================================================== --}}
                            {{-- MAIN RIBBON --}}
                            {{-- ===================================================== --}}

                            <path id="mainRibbon" fill="url(#flowGradient)" />

                            {{-- ===================================================== --}}
                            {{-- GLOSS --}}
                            {{-- ===================================================== --}}

                            <path
                                id="highlightRibbon"
                                fill="url(#highlightGradient)"
                                opacity=".85"
                            />

                            {{-- ===================================================== --}}
                            {{-- SOFT BLOOM --}}
                            {{-- ===================================================== --}}

                            <path
                                id="softBloom"
                                filter="url(#softBlur)"
                                fill="url(#flowGradient)"
                                opacity=".10"
                            />

                            {{-- ===================================================== --}}
                            {{-- GUIDE LINES --}}
                            {{-- ===================================================== --}}

                            <line
                                x1="120"
                                y1="72"
                                x2="120"
                                y2="160"
                                stroke="#94a3b8"
                                stroke-width="2"
                                stroke-dasharray="6 6"
                                opacity=".9"
                            />

                            <line
                                x1="500"
                                y1="72"
                                x2="500"
                                y2="160"
                                stroke="#94a3b8"
                                stroke-width="2"
                                stroke-dasharray="6 6"
                                opacity=".9"
                            />

                            <line
                                x1="880"
                                y1="72"
                                x2="880"
                                y2="160"
                                stroke="#94a3b8"
                                stroke-width="2"
                                stroke-dasharray="6 6"
                                opacity=".9"
                            />

                            <foreignObject
                                x="85"
                                y="10"
                                width="70"
                                height="40"
                            >
                                <div xmlns="http://www.w3.org/1999/xhtml" class="flow-badge glass">
                                    {{ $urgentPercent }}%
                                </div>
                            </foreignObject>

                            <foreignObject
                                x="465"
                                y="0"
                                width="70"
                                height="40"
                            >
                                <div xmlns="http://www.w3.org/1999/xhtml" class="flow-badge badge-center">
                                    {{ $maintenancePercent }}%
                                </div>
                            </foreignObject>

                            <foreignObject
                                x="845"
                                y="10"
                                width="70"
                                height="40"
                            >
                                <div xmlns="http://www.w3.org/1999/xhtml" class="flow-badge glass">
                                    {{ $borrowedPercent }}%
                                </div>
                            </foreignObject>

                            <foreignObject
                                x="85"
                                y="170"
                                width="70"
                                height="40">
                                <div xmlns="http://www.w3.org/1999/xhtml" class="flow-badge glass">
                                    {{ $urgentPercent }}%
                                </div>
                            </foreignObject>

                            <foreignObject
                                x="465"
                                y="170"
                                width="70"
                                height="40">
                                <div xmlns="http://www.w3.org/1999/xhtml" class="flow-badge badge-center">
                                    {{ $maintenancePercent }}%
                                </div>
                            </foreignObject>

                            <foreignObject
                                x="845"
                                y="170"
                                width="70"
                                height="40">
                                <div xmlns="http://www.w3.org/1999/xhtml" class="flow-badge glass">
                                    {{ $borrowedPercent }}%
                                </div>
                            </foreignObject>
                        </svg>
                    </div>
                </div>


                <section class="maintenance-hero">

                    {{-- ===================================================== --}}
                    {{-- TOP TWO CARDS --}}
                    {{-- ===================================================== --}}
                    <div class="maintenance-hero-summary">

                        {{-- PENDING REPORTS --}}
                        <div class="maintenance-summary-card">
                            <span class="maintenance-summary-label">
                                Pending Reports
                            </span>

                            <span class="maintenance-summary-number">
                                {{ $pendingReports }}
                            </span>

                            <a
                                href="{{ url('/maintenance/schedules') }}"
                                class="maintenance-summary-action"
                            >
                                View Reports
                            </a>
                        </div>


                        {{-- OVERDUE MAINTENANCE --}}
                        <div class="maintenance-summary-card">
                            <span class="maintenance-summary-label">
                                Overdue Maintenance
                            </span>

                            <span class="maintenance-summary-number">
                                {{ $overdueMaintenance }}
                            </span>

                            <a
                                href="{{ url('/maintenance/schedules') }}"
                                class="maintenance-summary-action"
                            >
                                View Schedule
                            </a>
                        </div>

                    </div>


                    {{-- ===================================================== --}}
                    {{-- BOTTOM FULL WIDTH CARD --}}
                    {{-- ===================================================== --}}
                    <div class="maintenance-hero-main">

                        <div class="maintenance-hero-eyebrow">
                            MAINTENANCE OPERATIONS
                        </div>

                        <h2 class="maintenance-hero-title">
                            {{ $urgentReports }} urgent reports require attention
                        </h2>

                        <p class="maintenance-hero-description">
                            Review active maintenance issues and prioritize critical
                            equipment requiring immediate action.
                        </p>

                        {{-- ===================================================== --}}
                        {{-- REAL URGENT REPORT ACTIVITY: LAST 7 DAYS --}}
                        {{-- ===================================================== --}}
                        @php
                            // Get the highest value so the bars can be scaled proportionally.
                            // Example: if the highest count is 5, a day with 5 reports = 100%.
                            $urgentChartMax = max($urgentChartData ?: [0]);

                            // Prevent division by zero when there are no urgent reports.
                            $urgentChartMax = max($urgentChartMax, 1);
                        @endphp

                        <div class="maintenance-mini-chart">
                            <div class="maintenance-mini-chart-bars">

                                @foreach ($urgentChartData as $index => $count)
                                    @php
                                        // Convert the real report count into a percentage height.
                                        $barHeight = ($count / $urgentChartMax) * 100;
                                    @endphp

                                    <div class="maintenance-mini-chart-item">

                                        <span
                                            class="maintenance-mini-chart-bar"
                                            style="height: {{ $barHeight }}%;"
                                            title="{{ $miniChartLabels[$index] }}: {{ $count }} urgent reports"
                                        ></span>

                                        <small>
                                            {{ $miniChartLabels[$index] }}
                                        </small>

                                    </div>
                                @endforeach

                            </div>
                        </div>

                        <div class="maintenance-hero-actions">

                            <a
                                href="{{ url('/maintenance/reports') }}"
                                class="maintenance-hero-primary"
                            >
                                Review Reports

                                <i
                                    data-lucide="chevrons-right"
                                    class="h-4 w-4"
                                ></i>
                            </a>

                            <button
                                type="button"
                                class="maintenance-hero-secondary"
                            >
                                <i
                                    data-lucide="scan-line"
                                    class="h-4 w-4"
                                ></i>

                                Scan Equipment
                            </button>

                        </div>

                    </div>

                </section>

                

                </div>

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
                    {{-- EQUIPMENT CONDITION --}}
                    {{-- ===================================================== --}}

                    <section class="dashboard-analytics-card">
                        {{-- ===================================================== --}}
                        {{-- HEADER --}}
                        {{-- ===================================================== --}}

                        <div class="dashboard-analytics-header">
                            <div>
                                <h2 class="dashboard-analytics-title">
                                    Equipment Condition
                                </h2>

                                <p class="dashboard-analytics-subtitle">Current condition of registered equipment</p>
                            </div>

                            {{-- ===================================================== --}}
                            {{-- TOTAL REGISTERED EQUIPMENT --}}
                            {{-- ===================================================== --}}

                            <div class="activity-chart-total">
                                {{
                                    collect(
                                        $equipmentConditionChart["data"],
                                    )->sum()
                                }}

                                <span> equipment </span>
                            </div>
                        </div>

                        {{-- ===================================================== --}}
                        {{-- CONCENTRIC BUBBLE CHART --}}
                        {{-- ===================================================== --}}

                        <div class="dashboard-small-chart">
                            <canvas id="equipmentConditionChart"></canvas>
                        </div>
                    </section>
                </div>

                {{-- ===================================================== --}}
                {{-- 3D CAMPUS / BUILDING OVERVIEW --}}
                {{-- ADD THIS AFTER dashboard-bottom-charts --}}
                {{-- ===================================================== --}}

                <section class="dashboard-building-section">

                    {{-- HEADER --}}
                    <div class="dashboard-building-header">
                        <div>
                            <p class="dashboard-building-eyebrow">
                                INFRASTRUCTURE OVERVIEW
                            </p>

                            <h2 class="dashboard-building-title">
                                Building Rooms
                            </h2>

                            <p class="dashboard-building-subtitle">
                                Interactive overview of rooms, equipment, and maintenance status.
                            </p>
                        </div>

                        <a
                            href="#"
                            class="dashboard-building-action"
                        >
                            <i data-lucide="maximize-2"></i>
                            <span>View Building</span>
                        </a>
                    </div>


                    {{-- 3D BUILDING VIEW --}}
                    {{-- ===================================================== --}}
                    {{-- PHASE 1: INTERACTIVE 3D BUILDING VIEWPORT --}}
                    {{-- ===================================================== --}}

                    <div class="dashboard-building-view">

                        {{-- THREE.JS WILL RENDER THE 3D SCENE HERE --}}
                        <div id="building3DViewport"></div>

                        {{-- ===================================================== --}}
                        {{-- PHASE 7.8: COMPACT ROOM DETAILS PANEL --}}
                        {{-- ===================================================== --}}

                        <div
                            id="buildingRoomDetailsPanel"
                            class="building-room-details-panel"
                        >

                            {{-- HEADER --}}
                            <div class="building-room-details-header">

                                <div>
                                    <span class="building-room-details-eyebrow">
                                        SELECTED ROOM
                                    </span>

                                    <h3 id="buildingRoomDetailsName">
                                        Room
                                    </h3>
                                </div>

                                <button
                                    type="button"
                                    id="buildingRoomDetailsClose"
                                    class="building-room-details-close"
                                    aria-label="Close room details"
                                >
                                    <i data-lucide="x"></i>
                                </button>

                            </div>


                            {{-- ROOM INFORMATION --}}
                            <div class="building-room-details-info">

                                <div class="building-room-details-row">
                                    <span>Floor</span>

                                    <strong id="buildingRoomDetailsFloor">
                                        Unknown
                                    </strong>
                                </div>

                                <div class="building-room-details-row">
                                    <span>Status</span>

                                    <strong id="buildingRoomDetailsStatus">
                                        Available
                                    </strong>
                                </div>

                            </div>


                            {{-- MAINTENANCE SUMMARY --}}
                            <div class="building-room-details-stats">

                                <div class="building-room-details-stat">
                                    <span>Active Reports</span>

                                    <strong id="buildingRoomDetailsActiveReports">
                                        0
                                    </strong>
                                </div>

                                <div class="building-room-details-stat">
                                    <span>Urgent Reports</span>

                                    <strong id="buildingRoomDetailsUrgentReports">
                                        0
                                    </strong>
                                </div>

                                <div class="building-room-details-stat">
                                    <span>Maintenance</span>

                                    <strong id="buildingRoomDetailsMaintenance">
                                        0
                                    </strong>
                                </div>

                            </div>


                            {{-- ACTION --}}
                            <button
                                type="button"
                                id="buildingRoomDetailsView"
                                class="building-room-details-view"
                            >
                                View Room

                                <i data-lucide="arrow-right"></i>
                            </button>

                        </div>

                        {{-- ===================================================== --}}
                        {{-- PHASE 7.7: ROOM HOVER TOOLTIP --}}
                        {{-- ===================================================== --}}

                        <div
                            id="buildingRoomTooltip"
                            class="building-room-tooltip"
                        >
                            <div class="building-room-tooltip-header">

                                <span
                                    id="buildingRoomTooltipDot"
                                    class="building-room-tooltip-dot"
                                ></span>

                                <span class="building-room-tooltip-eyebrow">
                                    ROOM
                                </span>

                            </div>

                            <div
                                id="buildingRoomTooltipName"
                                class="building-room-tooltip-name"
                            >
                                Room
                            </div>

                            <div class="building-room-tooltip-details">

                                <span id="buildingRoomTooltipFloor">
                                    Floor
                                </span>

                                <span class="building-room-tooltip-separator">
                                    •
                                </span>

                                <span id="buildingRoomTooltipStatus">
                                    Available
                                </span>

                            </div>
                        </div>


                        {{-- FLOATING LABEL --}}
                        <!--<div class="dashboard-building-badge">
                            <span class="dashboard-building-badge-dot"></span>

                            Interactive Building Overview
                        </div>-->

                        

                        {{-- ===================================================== --}}
                        {{-- PHASE 7.4: FLOOR FILTER CONTROLS --}}
                        {{-- ===================================================== --}}

                        <div class="building-floor-filters">
                            <button
                                type="button"
                                class="building-floor-filter active"
                                data-floor-filter="all"
                            >
                                All Floors
                            </button>

                            <div
                                id="buildingFloorFilterButtons"
                                class="building-floor-filter-dynamic"
                            ></div>
                        </div>

                        <button
                            type="button"
                            id="backToBuildingOverview"
                            style="display: none;"
                            class="building-back-overview-btn"
                        >
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Building Overview</span>
                        </button>

                        


                        {{-- 3D CONTROLS --}}
                        <div class="building-3d-controls">

                            <button
                                type="button"
                                id="buildingZoomOut"
                                class="building-3d-control"
                                title="Zoom Out"
                            >
                                <i data-lucide="minus"></i>
                            </button>

                            <button
                                type="button"
                                id="buildingZoomIn"
                                class="building-3d-control"
                                title="Zoom In"
                            >
                                <i data-lucide="plus"></i>
                            </button>

                            <button
                                type="button"
                                id="buildingReset"
                                class="building-3d-control"
                                title="Reset View"
                            >
                                <i data-lucide="rotate-ccw"></i>
                            </button>

                        </div>

                    </div>

                </section>

                
            </main>

            {{-- ===================================================== --}}
            {{-- RIGHT DASHBOARD SIDEBAR --}}
            {{-- ===================================================== --}}

            <aside class="maintenance-dashboard-sidebar">
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

                <!-- ══ URGENT REPORTS PIPELINE ══ -->
                {{-- ===================================================== --}}
                {{-- URGENT REPORTS PIPELINE --}}
                {{-- MODERN MEDIA CARD CAROUSEL --}}
                {{-- ===================================================== --}}

                <section class="urgent-pipeline-section">
                    {{-- ===================================================== --}}
                    {{-- SECTION HEADER --}}
                    {{-- ===================================================== --}}

                    <div class="urgent-pipeline-header">
                        <div>
                            <h2 class="urgent-pipeline-title">
                                Top 5 Newest Urgent Reports
                            </h2>

                            <p class="urgent-pipeline-description">Track active urgent issues in real time</p>
                        </div>

                        {{-- ================================================= --}}
                        {{-- CAROUSEL CONTROLS --}}
                        {{-- ================================================= --}}

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

                                        <a
                                            href="{{ url(
                                                '/maintenance/reports/details/'
                                                . $report->report_id
                                            ) }}"
                                            class="urgent-media-view"
                                            aria-label="View report"
                                        >
                                            <i
                                                data-lucide="arrow-up-right"
                                                class="h-4 w-4"
                                            ></i>
                                        </a>
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
                {{-- ACTIVITY SIDEBAR CARD --}}
                {{-- NOTHING BELOW THIS WAS REMOVED --}}
                {{-- ===================================================== --}}

                <div class="activity-sidebar-card">
                    {{-- ===================================================== --}}
                    {{-- HEADER --}}
                    {{-- ===================================================== --}}

                    <div class="activity-sidebar-header">
                        <h2 class="activity-sidebar-heading">Activity</h2>

                        <button
                            type="button"
                            class="activity-sidebar-menu"
                            aria-label="Activity options"
                        >
                            <i data-lucide="more-vertical" class="h-4 w-4"></i>
                        </button>
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
                            Latest maintenance events across PRISM
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

                        <a
                            href="{{ url('/maintenance/reports') }}"
                            class="activity-list-add"
                            aria-label="View all reports"
                        >
                            <i data-lucide="plus" class="h-4 w-4"></i>
                        </a>
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

                                <a
                                    href="{{ url(
                            '/maintenance/reports/details/'
                            . $activity->report_id
                        ) }}"
                                    class="activity-list-view"
                                >
                                    View
                                </a>
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
                        <a href="{{ url('/maintenance/reports') }}">
                            See All Activities
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- THREE.JS --}}
    {{-- PHASE 1: 3D BUILDING VIEWER --}}
    {{-- ===================================================== --}}

    <script type="importmap">
    {
        "imports": {
            "three": "https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js",
            "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.180.0/examples/jsm/"
        }
    }
    </script>

    @php
        // =====================================================
        // PREPARE PHASE 2.1 BUILDING DATA
        // =====================================================

        $building3DData = $floors->map(function ($floor) use ($roomsByFloor) {
            return [
                'id' => $floor->floor_id,

                'name' => $floor->floor_level,

                'rooms' => collect(
                    $roomsByFloor->get(
                        $floor->floor_id,
                        collect()
                    )
                )
                ->filter(function ($room) {
                    return ! $room->room_is_archived;
                })
                ->map(function ($room) {
                    return [
                        'id' => $room->room_id,
                        'name' => $room->room_name,
                        'type' => $room->room_type,
                        'status' => $room->dashboard_status,

                        'activeReportCount' => $room->active_report_count,

                        'urgentReportCount' => $room->urgent_report_count,

                        'maintenanceEquipmentCount' => $room->maintenance_equipment_count,

                        'x' => (float) $room->room_x,
                        'y' => (float) $room->room_y,

                        'width' => (float) $room->room_width,
                        'height' => (float) $room->room_height,

                        'color' => $room->room_color ?? '#ffffff',

                        'rotation' => (float) data_get(
                            $room->room_metadata,
                            'rotation',
                            0
                        ),
                    ];
                })
                ->values(),
            ];
        })->values();
    @endphp

    <script type="module">

        // =====================================================
        // IMPORT THREE.JS DIRECTLY
        // =====================================================

        import * as THREE from 'three';

        import {
            OrbitControls
        } from 'three/addons/controls/OrbitControls.js';

        // =====================================================
        // BLOOM POST PROCESSING
        // ADD THESE IMPORTS HERE
        // =====================================================

        import {
            EffectComposer
        } from 'three/addons/postprocessing/EffectComposer.js';

        import {
            RenderPass
        } from 'three/addons/postprocessing/RenderPass.js';

        import {
            UnrealBloomPass
        } from 'three/addons/postprocessing/UnrealBloomPass.js';

        // =====================================================
        // PHASE 2.1
        // REAL FLOOR AND ROOM DATA FROM LARAVEL
        // =====================================================
        const building3DData = @json($building3DData);

        console.log(
            'REAL 3D BUILDING DATA:',
            building3DData
        );


        // =====================================================
        // GET VIEWPORT
        // =====================================================

        const container =
            document.getElementById('building3DViewport');

        console.log(
            '3D Building Viewport:',
            container
        );


        if (!container) {

            console.error(
                'building3DViewport was not found.'
            );

        } else {

            console.log(
                'Starting Three.js building viewer...'
            );


            // =====================================================
            // SCENE
            // DEEP BLUE BLACK HOLOGRAPHIC BACKGROUND
            // =====================================================

            const scene =
                new THREE.Scene();

            scene.background =
                new THREE.Color(
                    0x020b14
                );


            // =====================================================
            // MATCH FOG WITH NEW BACKGROUND
            // =====================================================

            scene.fog =
                new THREE.FogExp2(
                    0x020b14,
                    0.012
                );


            // =====================================================
            // CAMERA
            // =====================================================

            const camera =
                new THREE.PerspectiveCamera(
                    45,
                    container.clientWidth /
                        container.clientHeight,
                    0.1,
                    1000
                );

            camera.position.set(
                18,
                14,
                20
            );


            // =====================================================
            // RENDERER
            // =====================================================

            const renderer =
                new THREE.WebGLRenderer({
                    antialias: true,
                    alpha: false
                });

            renderer.setPixelRatio(
                Math.min(
                    window.devicePixelRatio,
                    2
                )
            );

            renderer.setSize(
                container.clientWidth,
                container.clientHeight
            );

            

            // Better color rendering
            renderer.outputColorSpace =
                THREE.SRGBColorSpace;

            // Better lighting calculation
            renderer.toneMapping =
                THREE.ACESFilmicToneMapping;

            renderer.toneMappingExposure =
                1.15;

            // Shadows
            renderer.shadowMap.enabled = true;

            renderer.shadowMap.type =
                THREE.PCFSoftShadowMap;

            container.appendChild(
                renderer.domElement
            );

            // =====================================================
            // BLOOM POST PROCESSING
            // ADD DIRECTLY AFTER RENDERER SETUP
            // =====================================================

            const composer =
                new EffectComposer(
                    renderer
                );

            const renderPass =
                new RenderPass(
                    scene,
                    camera
                );

            composer.addPass(
                renderPass
            );


            // =====================================================
            // HOLOGRAPHIC CYAN BLOOM
            // =====================================================

            const bloomPass =
                new UnrealBloomPass(

                    new THREE.Vector2(
                        container.clientWidth,
                        container.clientHeight
                    ),

                    0.45,   // Strength, was 1.0
                    0.30,   // Radius, was 0.45
                    0.55    // Threshold, was 0.35

                );

            composer.addPass(
                bloomPass
            );

            
            // =====================================================
            // SET INITIAL COMPOSER SIZE
            // PUT composer.setSize() HERE
            // =====================================================

            composer.setSize(
                container.clientWidth,
                container.clientHeight
            );



            // =====================================================
            // CAMERA CONTROLS
            // =====================================================

            const controls =
                new OrbitControls(
                    camera,
                    renderer.domElement
                );

            controls.enableDamping = true;
            controls.dampingFactor = 0.06;

            controls.enableZoom = true;
            controls.enablePan = true;

            controls.minDistance = 8;
            controls.maxDistance = 35;

            controls.target.set(
                0,
                2.5,
                0
            );

            controls.addEventListener(
                'start',
                () => {

                    cameraTransition = null;

                }
            );


            // =====================================================
            // LIGHTING
            // BRIGHT BLUE CYAN HOLOGRAPHIC LIGHTING
            // =====================================================


            // =====================================================
            // BLUE AMBIENT LIGHT
            // PROVIDES SOFT BLUE LIGHT ACROSS THE WHOLE SCENE
            // =====================================================

            const ambientLight =
                new THREE.AmbientLight(
                    0x38bdf8,
                    1.2
                );

            scene.add(
                ambientLight
            );


            // =====================================================
            // CYAN TOP LIGHT
            // CREATES BRIGHT LIGHT FROM ABOVE
            // =====================================================

            const topLight =
                new THREE.PointLight(
                    0x22d3ee,
                    12,
                    100
                );

            topLight.position.set(
                0,
                20,
                0
            );

            scene.add(
                topLight
            );


            // =====================================================
            // BLUE GROUND LIGHT
            // CREATES BLUE GLOW UNDER THE BUILDING
            // =====================================================

            const groundLight =
                new THREE.PointLight(
                    0x008cff,
                    15,
                    50
                );

            groundLight.position.set(
                0,
                1,
                5
            );

            scene.add(
                groundLight
            );


            // =====================================================
            // SOFT DIRECTIONAL LIGHT
            // KEEPS THE BUILDING SHAPE VISIBLE
            // =====================================================

            const directionalLight =
                new THREE.DirectionalLight(
                    0xbfe8ff,
                    1.5
                );

            directionalLight.position.set(
                10,
                18,
                12
            );

            directionalLight.castShadow =
                true;

            scene.add(
                directionalLight
            );


            // =====================================================
            // BLUE HOLOGRAPHIC GROUND PLANE
            // REPLACES EXISTING DIGITAL TWIN BASE PLATFORM
            // =====================================================

            const floor =
                new THREE.Mesh(

                    new THREE.PlaneGeometry(
                        100,
                        100
                    ),

                    new THREE.MeshBasicMaterial({

                        color: 0x031d2e,

                        transparent: true,

                        opacity: 0.45,

                        side: THREE.DoubleSide

                    })

                );

            floor.rotation.x =
                -Math.PI / 2;


            // =====================================================
            // PLACE SLIGHTLY BELOW GRID
            // PREVENTS GRID FLICKERING
            // =====================================================

            floor.position.y =
                -0.02;

            scene.add(
                floor
            );


            // =====================================================
            // FUTURISTIC BLUE DIGITAL GRID
            // REPLACES EXISTING DIGITAL BLUEPRINT GRID
            // =====================================================

            const grid =
                new THREE.GridHelper(

                    100,

                    100,

                    0x00cfff,

                    0x075985

                );


            // =====================================================
            // KEEP GRID SLIGHTLY ABOVE GROUND
            // =====================================================

            grid.position.y =
                0.01;

            grid.material.transparent =
                true;

            grid.material.opacity =
                0.35;

            scene.add(
                grid
            );


            // =====================================================
            // BUILDING GROUP
            // =====================================================

            // =====================================================
            // PHASE 2: 3D BUILDING WITH INDIVIDUAL ROOMS
            // REPLACES THE OLD PLACEHOLDER BUILDING BLOCKS
            // =====================================================


            // =====================================================
            // BUILDING GROUP
            // ALL FLOORS AND ROOMS WILL BE ADDED HERE
            // =====================================================

            const building = new THREE.Group();

            scene.add(building);

            // =====================================================
            // PHASE 8.1
            // EXTERIOR BUILDING SHELL
            // =====================================================

            // This group is separate from the existing interior building.
            // Later, Phase 8.2 will switch between:
            // exteriorBuilding = outside view
            // building = interior room view

            const exteriorBuilding =
                new THREE.Group();

            exteriorBuilding.userData.isBuildingExterior = true;

            scene.add(
                exteriorBuilding
            );

            // =====================================================
            // PHASE 8.2
            // BUILDING VIEW MODE
            //
            // exterior = user is viewing the outside building shell
            // interior = user is viewing floors and rooms
            // =====================================================

            let currentBuildingView = 'exterior';

            let isBuildingViewTransitioning = false;


            // =====================================================
            // PHASE 8.2
            // INITIAL BUILDING VISIBILITY
            //
            // Dashboard starts in Exterior Mode.
            // Hide the interior rooms and floors.
            // Show the exterior building shell.
            // =====================================================

            building.visible = false;

            exteriorBuilding.visible = true;


            // =====================================================
            // EXTERIOR HOLOGRAPHIC GLASS MATERIAL
            // BRIGHTER CYAN FOR BLOOM
            // =====================================================

            // =====================================================
            // EXTERIOR BUILDING SHELL MATERIAL
            // RESTORED DARKER APPEARANCE
            // =====================================================

            // =====================================================
            // EXTERIOR MATERIAL
            // RESTORED ORIGINAL HOLOGRAPHIC GLASS EFFECT
            // =====================================================

            const exteriorMaterial =
                new THREE.MeshPhysicalMaterial({

                    color: 0x0c4a6e,

                    emissive: 0x063b52,

                    emissiveIntensity: 0.35,

                    transparent: true,

                    opacity: 0.18,

                    roughness: 0.15,

                    metalness: 0.1,

                    side: THREE.DoubleSide,

                    depthWrite: false

                });


            // =====================================================
            // EXTERIOR EDGE MATERIAL
            // RESTORED ORIGINAL CYAN ARCHITECTURAL WIREFRAME
            // =====================================================

            const exteriorEdgeMaterial =
                new THREE.LineBasicMaterial({

                    color: 0x67e8f9,

                    transparent: true,

                    opacity: 0.85

                });

            // =====================================================
            // PHASE 8.1
            // HELPER FUNCTION TO CREATE BUILDING SECTIONS
            // =====================================================

            function createExteriorSection(
                width,
                height,
                depth,
                x,
                y,
                z
            ) {

                const geometry =
                    new THREE.BoxGeometry(
                        width,
                        height,
                        depth
                    );


                // Main transparent building section
                const mesh =
                    new THREE.Mesh(
                        geometry,
                        exteriorMaterial.clone()
                    );

                mesh.position.set(
                    x,
                    y,
                    z
                );

                mesh.castShadow = true;
                mesh.receiveShadow = true;


                // Cyan wireframe outline
                const edges =
                    new THREE.EdgesGeometry(
                        geometry
                    );

                const outline =
                    new THREE.LineSegments(
                        edges,
                        exteriorEdgeMaterial.clone()
                    );

                mesh.add(
                    outline
                );


                exteriorBuilding.add(
                    mesh
                );

                return mesh;
            }

            // =====================================================
            // PHASE 8.3 PART 2
            // CREATE CURVED FRONT ARCH
            // =====================================================

            function createExteriorFrontArch(
                width,
                height,
                depth,
                x,
                y,
                z
            ) {

                // =================================================
                // ARCH GROUP
                // =================================================

                const archGroup =
                    new THREE.Group();

                archGroup.position.set(
                    x,
                    y,
                    z
                );


                // =================================================
                // ARCH DIMENSIONS
                //
                // The arch is made from:
                //
                // 1. Left vertical column
                // 2. Right vertical column
                // 3. Curved upper section
                //
                // This creates the large rounded feature visible
                // on the Robinsons front facade.
                // =================================================

                const columnWidth =
                    Math.max(
                        width * 0.16,
                        0.35
                    );

                const curveRadius =
                    width / 2;

                const straightHeight =
                    Math.max(
                        height - curveRadius,
                        height * 0.45
                    );


                // =================================================
                // ARCH MATERIAL
                //
                // Keep this consistent with the existing
                // blueprint exterior style.
                // =================================================

                const archMaterial =
                    new THREE.MeshBasicMaterial({

                        color: 0x17375e,

                        transparent: true,

                        opacity: 0.28,

                        side: THREE.DoubleSide,

                        depthWrite: false

                    });


                const archEdgeMaterial =
                    new THREE.LineBasicMaterial({

                        color: 0x79cfff,

                        transparent: true,

                        opacity: 0.75

                    });


                // =================================================
                // LEFT VERTICAL COLUMN
                // =================================================

                const leftColumnGeometry =
                    new THREE.BoxGeometry(

                        columnWidth,

                        straightHeight,

                        depth

                    );


                const leftColumn =
                    new THREE.Mesh(

                        leftColumnGeometry,

                        archMaterial.clone()

                    );


                leftColumn.position.set(

                    -(width / 2) +
                        (columnWidth / 2),

                    -(height / 2) +
                        (straightHeight / 2),

                    0

                );


                archGroup.add(
                    leftColumn
                );


                // =================================================
                // LEFT COLUMN EDGES
                // =================================================

                const leftColumnEdges =
                    new THREE.LineSegments(

                        new THREE.EdgesGeometry(
                            leftColumnGeometry
                        ),

                        archEdgeMaterial.clone()

                    );


                leftColumnEdges.position.copy(
                    leftColumn.position
                );


                archGroup.add(
                    leftColumnEdges
                );


                // =================================================
                // RIGHT VERTICAL COLUMN
                // =================================================

                const rightColumnGeometry =
                    new THREE.BoxGeometry(

                        columnWidth,

                        straightHeight,

                        depth

                    );


                const rightColumn =
                    new THREE.Mesh(

                        rightColumnGeometry,

                        archMaterial.clone()

                    );


                rightColumn.position.set(

                    (width / 2) -
                        (columnWidth / 2),

                    -(height / 2) +
                        (straightHeight / 2),

                    0

                );


                archGroup.add(
                    rightColumn
                );


                // =================================================
                // RIGHT COLUMN EDGES
                // =================================================

                const rightColumnEdges =
                    new THREE.LineSegments(

                        new THREE.EdgesGeometry(
                            rightColumnGeometry
                        ),

                        archEdgeMaterial.clone()

                    );


                rightColumnEdges.position.copy(
                    rightColumn.position
                );


                archGroup.add(
                    rightColumnEdges
                );


                // =================================================
                // CURVED TOP
                //
                // Creates the rounded arch shape.
                //
                // RingGeometry gives us an outer curve and an
                // inner opening instead of a solid semicircle.
                // =================================================

                const outerRadius =
                    width / 2;


                const innerRadius =
                    Math.max(

                        outerRadius - columnWidth,

                        outerRadius * 0.55

                    );


                const archGeometry =
                    new THREE.RingGeometry(

                        innerRadius,

                        outerRadius,

                        32,

                        1,

                        0,

                        Math.PI

                    );


                const curvedArch =
                    new THREE.Mesh(

                        archGeometry,

                        archMaterial.clone()

                    );


                // =================================================
                // POSITION THE CURVE
                //
                // RingGeometry is created in the XY plane.
                // Since the front facade faces the Z direction,
                // no rotation is needed here.
                // =================================================

                curvedArch.position.set(

                    0,

                    -(height / 2) +
                        straightHeight,

                    depth / 2 + 0.01

                );


                archGroup.add(
                    curvedArch
                );


                // =================================================
                // CURVED ARCH OUTLINE
                // =================================================

                const curvedArchEdges =
                    new THREE.LineSegments(

                        new THREE.EdgesGeometry(
                            archGeometry
                        ),

                        archEdgeMaterial.clone()

                    );


                curvedArchEdges.position.copy(
                    curvedArch.position
                );


                archGroup.add(
                    curvedArchEdges
                );


                // =================================================
                // MARK ALL OBJECTS AS EXTERIOR
                //
                // This is important because Phase 8.2 uses this
                // property for exterior clicking and interaction.
                // =================================================

                archGroup.traverse(
                    child => {

                        if (
                            child.isMesh ||
                            child.isLineSegments
                        ) {

                            child.userData.isBuildingExterior =
                                true;

                        }

                    }
                );


                // =================================================
                // ADD TO EXTERIOR BUILDING GROUP
                // =================================================

                exteriorBuilding.add(
                    archGroup
                );


                return archGroup;

            }


            // =====================================================
            // PHASE 8.1
            // MAIN STI BUILDING BODY
            //
            // Inspired by the actual long horizontal shape
            // of the STI College Ormoc building.
            // =====================================================



            // =====================================================
            // PHASE 8.1
            // SAVE IDENTIFICATION
            // USED LATER FOR CLICKING THE BUILDING
            // =====================================================

            exteriorBuilding.userData = {

                type: 'exterior-building',

                name: 'STI College Ormoc'

            };

            const raycaster = new THREE.Raycaster();

            const mouse = new THREE.Vector2();

            const clickableRooms = [];

            let hoveredRoom = null;

            let selectedRoom = null;

            // =====================================================
            // PHASE 7.9
            // ROOM VISUAL STATE HELPERS
            // =====================================================

            function restoreRoomVisual(room) {

                if (!room) {
                    return;
                }

                // Restore the room's original material appearance
                if (room.userData.originalEmissive !== undefined) {

                    room.material.emissive.setHex(
                        room.userData.originalEmissive
                    );

                }

                if (room.userData.originalEmissiveIntensity !== undefined) {

                    room.material.emissiveIntensity =
                        room.userData.originalEmissiveIntensity;

                }

                // Restore normal room size
                room.scale.set(
                    1,
                    1,
                    1
                );

            }


            function applyRoomHoverVisual(room) {

                if (!room || room === selectedRoom) {
                    return;
                }

                // Bright cyan hover effect
                room.material.emissive.setHex(
                    0x22d3ee
                );

                room.material.emissiveIntensity =
                    0.8;

            }


            function applyRoomSelectedVisual(room) {

                if (!room) {
                    return;
                }

                // Strong selected highlight
                room.material.emissive.setHex(
                    0xfacc15
                );

                room.material.emissiveIntensity =
                    1;

                // Slightly enlarge selected room
                room.scale.set(
                    1.05,
                    1.05,
                    1.05
                );

            }

            // =====================================================
            // PHASE 7.8
            // ROOM DETAILS PANEL ELEMENTS
            // =====================================================

            const roomDetailsPanel =
                document.getElementById(
                    'buildingRoomDetailsPanel'
                );

            const roomDetailsName =
                document.getElementById(
                    'buildingRoomDetailsName'
                );

            const roomDetailsFloor =
                document.getElementById(
                    'buildingRoomDetailsFloor'
                );

            const roomDetailsStatus =
                document.getElementById(
                    'buildingRoomDetailsStatus'
                );

            const roomDetailsActiveReports =
                document.getElementById(
                    'buildingRoomDetailsActiveReports'
                );

            const roomDetailsUrgentReports =
                document.getElementById(
                    'buildingRoomDetailsUrgentReports'
                );

            const roomDetailsMaintenance =
                document.getElementById(
                    'buildingRoomDetailsMaintenance'
                );

            const roomDetailsClose =
                document.getElementById(
                    'buildingRoomDetailsClose'
                );

            const roomDetailsView =
                document.getElementById(
                    'buildingRoomDetailsView'
                );

            const roomTooltip =
                document.getElementById(
                    'buildingRoomTooltip'
                );

            const roomTooltipName =
                document.getElementById(
                    'buildingRoomTooltipName'
                );

            const roomTooltipFloor =
                document.getElementById(
                    'buildingRoomTooltipFloor'
                );

            const roomTooltipStatus =
                document.getElementById(
                    'buildingRoomTooltipStatus'
                );

            const roomTooltipDot =
                document.getElementById(
                    'buildingRoomTooltipDot'
                );

            const buildingFloorGroups =
                new Map();

            let cameraTransition = null;

            // =====================================================
            // PHASE 7.9
            // SAVE CAMERA VIEW BEFORE SELECTING A ROOM
            // =====================================================

            let cameraPositionBeforeRoomSelection = null;
            let cameraTargetBeforeRoomSelection = null;

            // =====================================================
            // PHASE 7.7
            // FORMAT ROOM STATUS FOR TOOLTIP
            // =====================================================

            function formatRoomStatus(status) {

                switch (status) {

                    case 'critical':
                        return 'Critical';

                    case 'needs-repair':
                        return 'Needs Repair';

                    case 'maintenance':
                        return 'Maintenance';

                    case 'available':
                        return 'Available';

                    default:
                        return 'Available';

                }

            }

            // =====================================================
            // PHASE 7.7
            // UPDATE TOOLTIP CONTENT
            // =====================================================

            function updateRoomTooltip(
                room,
                mouseEvent
            ) {

                if (
                    !roomTooltip ||
                    !room
                ) {
                    return;
                }


                // =================================================
                // ROOM INFORMATION
                // =================================================

                roomTooltipName.textContent =
                    room.userData.roomName ||
                    'Room';

                roomTooltipFloor.textContent =
                    room.userData.floorName ||
                    'Floor';

                roomTooltipStatus.textContent =
                    formatRoomStatus(
                        room.userData.roomStatus
                    );


                // =================================================
                // STATUS DOT COLOR
                // =================================================

                if (
                    room.userData.roomStatus ===
                    'critical'
                ) {

                    roomTooltipDot.style.background =
                        '#ef4444';

                    roomTooltipDot.style.boxShadow =
                        '0 0 8px rgba(239, 68, 68, 0.9)';

                }

                else if (
                    room.userData.roomStatus ===
                    'needs-repair' ||
                    room.userData.roomStatus ===
                    'maintenance'
                ) {

                    roomTooltipDot.style.background =
                        '#f59e0b';

                    roomTooltipDot.style.boxShadow =
                        '0 0 8px rgba(245, 158, 11, 0.9)';

                }

                else {

                    roomTooltipDot.style.background =
                        '#22d3ee';

                    roomTooltipDot.style.boxShadow =
                        '0 0 8px rgba(34, 211, 238, 0.9)';

                }


                // =================================================
                // POSITION TOOLTIP
                // =================================================

                const viewRect =
                    renderer.domElement
                        .getBoundingClientRect();

                const mouseX =
                    mouseEvent.clientX -
                    viewRect.left;

                const mouseY =
                    mouseEvent.clientY -
                    viewRect.top;

                roomTooltip.style.left =
                    `${mouseX}px`;

                roomTooltip.style.top =
                    `${mouseY}px`;


                // =================================================
                // SHOW TOOLTIP
                // =================================================

                roomTooltip.classList.add(
                    'visible'
                );

            }

            // =====================================================
            // PHASE 7.7
            // HIDE ROOM TOOLTIP
            // =====================================================

            function hideRoomTooltip() {

                if (!roomTooltip) {
                    return;
                }

                roomTooltip.classList.remove(
                    'visible'
                );

            }

            // =====================================================
            // PHASE 7.8
            // OPEN COMPACT ROOM DETAILS PANEL
            // =====================================================

            function openRoomDetailsPanel(room) {

                if (
                    !room ||
                    !roomDetailsPanel
                ) {
                    return;
                }


                // =============================================
                // UPDATE ROOM INFORMATION
                // =============================================

                roomDetailsName.textContent =
                    room.roomName || 'Room';

                roomDetailsFloor.textContent =
                    room.floorName || 'Unknown';

                roomDetailsStatus.textContent =
                    formatRoomStatus(
                        room.roomStatus
                    );


                // =============================================
                // UPDATE MAINTENANCE COUNTS
                // =============================================

                roomDetailsActiveReports.textContent =
                    room.activeReportCount || 0;

                roomDetailsUrgentReports.textContent =
                    room.urgentReportCount || 0;

                roomDetailsMaintenance.textContent =
                    room.maintenanceEquipmentCount || 0;


                // =============================================
                // SAVE SELECTED ROOM ID
                // =============================================

                roomDetailsView.dataset.roomId =
                    room.roomId;


                // =============================================
                // SHOW PANEL
                // =============================================

                roomDetailsPanel.classList.add(
                    'visible'
                );

            }


            // =====================================================
            // ROOM MATERIALS
            // THESE COLORS WILL LATER REPRESENT ROOM STATUS
            // =====================================================

            // =====================================================
            // ROOM MATERIALS
            // MODERN HOLOGRAPHIC DIGITAL TWIN
            // =====================================================

            const roomMaterials = {

                // NORMAL ROOM
                normal: new THREE.MeshPhysicalMaterial({

                    color: 0x0c4a6e,

                    emissive: 0x062f46,

                    emissiveIntensity: 0.45,

                    transparent: true,

                    opacity: 0.38,

                    roughness: 0.2,

                    metalness: 0.15,

                    side: THREE.DoubleSide,

                    depthWrite: true

                }),


                // MAINTENANCE / ACTIVE REPORT
                warning: new THREE.MeshPhysicalMaterial({

                    color: 0x78350f,

                    emissive: 0xf59e0b,

                    emissiveIntensity: 0.45,

                    transparent: true,

                    opacity: 0.48,

                    roughness: 0.25,

                    metalness: 0.1,

                    side: THREE.DoubleSide,

                    depthWrite: true

                }),


                // URGENT / CRITICAL
                urgent: new THREE.MeshPhysicalMaterial({

                    color: 0x7f1d1d,

                    emissive: 0xef4444,

                    emissiveIntensity: 0.6,

                    transparent: true,

                    opacity: 0.52,

                    roughness: 0.25,

                    metalness: 0.1,

                    side: THREE.DoubleSide,

                    depthWrite: true

                })

            };


            // =====================================================
            // ROOM BORDER MATERIAL
            // CYAN HOLOGRAPHIC OUTLINE
            // =====================================================

            const roomEdgeMaterial =
                new THREE.LineBasicMaterial({

                    color: 0x67e8f9,

                    transparent: true,

                    opacity: 0.9

                });


            // =====================================================
            // CREATE INDIVIDUAL ROOM
            //
            // name   = room name
            // x      = horizontal position
            // y      = floor height
            // z      = depth position
            // width  = room width
            // depth  = room depth
            // status = normal, warning, or urgent
            // =====================================================

            function createRoom(
                name,
                x,
                y,
                z,
                width,
                depth,
                status = 'normal'
            ) {

                // =================================================
                // ROOM HEIGHT
                // =================================================

                const roomHeight = 1.8;


                // =================================================
                // CREATE ROOM GEOMETRY
                // =================================================

                const geometry =
                    new THREE.BoxGeometry(
                        width,
                        roomHeight,
                        depth
                    );


                // =================================================
                // CREATE ROOM MESH
                // =================================================

                const room =
                    new THREE.Mesh(
                        geometry,
                        roomMaterials[status].clone()
                    );


                // =================================================
                // ROOM POSITION
                // =================================================

                room.position.set(
                    x,
                    y + 0.06 + (roomHeight / 2),
                    z
                );


                // =================================================
                // ENABLE SHADOWS
                // =================================================

                room.castShadow = true;

                room.receiveShadow = true;


                // =================================================
                // SAVE ROOM INFORMATION
                // THIS WILL BE USED IN PHASE 3 FOR CLICKING ROOMS
                // =================================================

                room.userData = {
                    type: 'room',
                    name: name,
                    status: status
                };


                // =================================================
                // ADD ROOM TO BUILDING
                // =================================================

                


                // =================================================
                // CREATE ROOM OUTLINE
                // MAKES EACH ROOM EASIER TO SEE
                // =================================================

                const edges =
                    new THREE.EdgesGeometry(
                        geometry
                    );

                const outline =
                    new THREE.LineSegments(
                        edges,
                        roomEdgeMaterial
                    );

                room.add(
                    outline
                );


                return room;
            }


            // =====================================================
            // PHASE 2.3
            // INDEPENDENT FLOOR ALIGNMENT AND STACKING
            // =====================================================


            // =====================================================
            // CONFIGURATION
            // =====================================================

            // Converts your saved 2D blueprint pixels into 3D units.
            const BLUEPRINT_SCALE = 0.02;

            // Vertical distance between each floor.
            

            // Minimum visible room size.
            const MIN_ROOM_SIZE = 0.5;

            const FLOOR_BASE_HEIGHT = 0.15;

            // Increase this number to create more space between floors
            const FLOOR_VERTICAL_GAP = 4;

            // =====================================================
            // PHASE 7.3
            // CREATE FLOATING FLOOR LABEL
            // =====================================================

            function createFloorLabel(text) {

                // Create canvas for the label
                const canvas = document.createElement('canvas');

                canvas.width = 512;
                canvas.height = 128;

                const context = canvas.getContext('2d');


                // =================================================
                // LABEL BACKGROUND
                // =================================================

                context.fillStyle = 'rgba(2, 6, 23, 0.85)';

                context.fillRect(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );


                // =================================================
                // LABEL BORDER
                // =================================================

                context.strokeStyle = '#22d3ee';

                context.lineWidth = 4;

                context.strokeRect(
                    2,
                    2,
                    canvas.width - 4,
                    canvas.height - 4
                );


                // =================================================
                // LABEL TEXT
                // =================================================

                context.font = 'bold 48px Arial';

                context.fillStyle = '#ffffff';

                context.textAlign = 'center';

                context.textBaseline = 'middle';

                context.fillText(
                    text,
                    canvas.width / 2,
                    canvas.height / 2
                );


                // =================================================
                // CONVERT CANVAS INTO THREE.JS TEXTURE
                // =================================================

                const texture =
                    new THREE.CanvasTexture(canvas);

                texture.colorSpace =
                    THREE.SRGBColorSpace;


                // =================================================
                // CREATE SPRITE MATERIAL
                // =================================================

                const material =
                    new THREE.SpriteMaterial({

                        map: texture,

                        transparent: true,

                        depthTest: false

                    });


                // =================================================
                // CREATE SPRITE
                // =================================================

                const label =
                    new THREE.Sprite(material);

                label.scale.set(
                    4,
                    1,
                    1
                );

                return label;
            }

            // =====================================================
            // PHASE 7.10
            // CREATE DYNAMIC ROOM NAME LABEL
            // ONLY VISIBLE WHEN A SINGLE FLOOR IS SELECTED
            // =====================================================

            function createRoomLabel(text) {

                // Create canvas for room name
                const canvas = document.createElement('canvas');

                canvas.width = 512;
                canvas.height = 128;

                const context =
                    canvas.getContext('2d');


                // =================================================
                // LABEL BACKGROUND
                // =================================================

                context.fillStyle =
                    'rgba(2, 6, 23, 0.88)';

                context.beginPath();

                context.roundRect(
                    4,
                    4,
                    canvas.width - 8,
                    canvas.height - 8,
                    24
                );

                context.fill();


                // =================================================
                // LABEL BORDER
                // =================================================

                context.strokeStyle =
                    'rgba(34, 211, 238, 0.85)';

                context.lineWidth = 4;

                context.stroke();


                // =================================================
                // ROOM NAME
                // =================================================

                context.font =
                    'bold 42px Arial';

                context.fillStyle =
                    '#ffffff';

                context.textAlign =
                    'center';

                context.textBaseline =
                    'middle';

                context.fillText(
                    text || 'Room',
                    canvas.width / 2,
                    canvas.height / 2
                );


                // =================================================
                // CONVERT CANVAS TO THREE.JS TEXTURE
                // =================================================

                const texture =
                    new THREE.CanvasTexture(canvas);

                texture.colorSpace =
                    THREE.SRGBColorSpace;


                // =================================================
                // CREATE SPRITE
                // =================================================

                const material =
                    new THREE.SpriteMaterial({

                        map: texture,

                        transparent: true,

                        depthTest: false,

                        depthWrite: false

                    });


                const label =
                    new THREE.Sprite(material);


                // =================================================
                // PHASE 7.10
                // IDENTIFY AS ROOM LABEL
                // =================================================

                label.userData.type =
                    'room-label';


                // Smaller than floor labels
                label.scale.set(
                    2.8,
                    0.7,
                    1
                );


                // Hidden by default because All Floors is default
                label.visible =
                    false;


                return label;
            }


            // =====================================================
            // LOOP THROUGH EACH DATABASE FLOOR
            // =====================================================

            building3DData.forEach((floorData, floorIndex) => {

                // =====================================================
                // PHASE 7.4
                // CREATE GROUP FOR THIS FLOOR
                // =====================================================

                const floorGroup =
                    new THREE.Group();

                floorGroup.userData = {
                    type: 'floor',
                    floorId: String(floorData.id),
                    floorName: floorData.name
                };

                building.add(
                    floorGroup
                );

                buildingFloorGroups.set(
                    String(floorData.id),
                    floorGroup
                );

                const floorY = floorIndex * FLOOR_VERTICAL_GAP;


                // =================================================
                // SKIP EMPTY FLOORS
                // =================================================

                if (
                    !floorData.rooms ||
                    floorData.rooms.length === 0
                ) {

                    return;

                }


                // =================================================
                // STEP 1
                // CALCULATE THIS FLOOR'S OWN BLUEPRINT BOUNDS
                // =================================================

                let floorMinX = Infinity;
                let floorMinY = Infinity;

                let floorMaxX = -Infinity;
                let floorMaxY = -Infinity;


                floorData.rooms.forEach((roomData) => {

                    const roomX =
                        Number(roomData.x) || 0;

                    const roomY =
                        Number(roomData.y) || 0;

                    const roomWidth =
                        Number(roomData.width) || 100;

                    const roomHeight =
                        Number(roomData.height) || 100;


                    floorMinX =
                        Math.min(
                            floorMinX,
                            roomX
                        );

                    floorMinY =
                        Math.min(
                            floorMinY,
                            roomY
                        );


                    floorMaxX =
                        Math.max(
                            floorMaxX,
                            roomX + roomWidth
                        );

                    floorMaxY =
                        Math.max(
                            floorMaxY,
                            roomY + roomHeight
                        );

                });


                // =================================================
                // STEP 2
                // CALCULATE THIS FLOOR'S CENTER
                // =================================================

                const floorCenterX =
                    (
                        floorMinX +
                        floorMaxX
                    ) / 2;

                const floorCenterY =
                    (
                        floorMinY +
                        floorMaxY
                    ) / 2;


                

                // =================================================
                // PHASE 7.1
                // CALCULATE ACTUAL SIZE OF THIS FLOOR
                // =================================================

                const floorWidth =
                    Math.max(
                        (floorMaxX - floorMinX) * BLUEPRINT_SCALE,
                        4
                    );

                const floorDepth =
                    Math.max(
                        (floorMaxY - floorMinY) * BLUEPRINT_SCALE,
                        4
                    );


                // =================================================
                // PHASE 7.1
                // CREATE ARCHITECTURAL FLOOR SLAB
                // =================================================

                const slabGeometry =
                    new THREE.BoxGeometry(
                        floorWidth + 1,
                        0.12,
                        floorDepth + 1
                    );

                const slabMaterial =
                    new THREE.MeshPhysicalMaterial({

                        color: 0x063b52,

                        emissive: 0x002b3d,

                        emissiveIntensity: 0.25,

                        transparent: true,

                        opacity: 0.28,

                        roughness: 0.2,

                        metalness: 0.15,

                        side: THREE.DoubleSide,

                        depthWrite: false

                    });

                const floorSlab =
                    new THREE.Mesh(
                        slabGeometry,
                        slabMaterial
                    );

                floorSlab.position.set(
                    0,
                    floorY,
                    0
                );

                floorSlab.receiveShadow = true;

                floorGroup.add(
                    floorSlab
                );


                // =================================================
                // PHASE 7.1
                // CREATE CYAN FLOOR PERIMETER
                // =================================================

                const slabEdges =
                    new THREE.EdgesGeometry(
                        slabGeometry
                    );

                const slabEdgeMaterial =
                    new THREE.LineBasicMaterial({

                        color: 0x22d3ee,

                        transparent: true,

                        opacity: 0.75

                    });

                const slabOutline =
                    new THREE.LineSegments(
                        slabEdges,
                        slabEdgeMaterial
                    );

                // =================================================
                // FIX
                // KEEP FLOOR BORDER AT THE SAME HEIGHT AS FLOOR SLAB
                // =================================================

                slabOutline.position.set(
                    0,
                    floorY,
                    0
                );

                floorGroup.add(
                    slabOutline
                );

                // =================================================
                // PHASE 7.3
                // ADD DYNAMIC FLOATING FLOOR LABEL
                // =================================================

                const floorLabel =
                    createFloorLabel(
                        floorData.name || `Floor ${floorIndex + 1}`
                    );


                // =================================================
                // POSITION LABEL BESIDE THE FLOOR
                // =================================================

                floorLabel.position.set(

                    // Left side of the floor
                    -(floorWidth / 2) - 2.5,

                    // Slightly above the floor
                    floorY + 0.8,

                    // Center depth
                    0

                );


                // =================================================
                // ADD LABEL TO BUILDING
                // =================================================

                floorGroup.add(
                    floorLabel
                );


                // =================================================
                // STEP 4
                // CREATE ROOMS FOR THIS FLOOR
                // =================================================

                floorData.rooms.forEach((roomData) => {


                    // =============================================
                    // ORIGINAL 2D BLUEPRINT DATA
                    // =============================================

                    const originalX =
                        Number(roomData.x) || 0;

                    const originalY =
                        Number(roomData.y) || 0;

                    const originalWidth =
                        Number(roomData.width) || 100;

                    const originalHeight =
                        Number(roomData.height) || 100;


                    // =============================================
                    // CONVERT ROOM SIZE TO THREE.JS UNITS
                    // =============================================

                    const roomWidth =
                        Math.max(
                            originalWidth *
                            BLUEPRINT_SCALE,

                            MIN_ROOM_SIZE
                        );

                    const roomDepth =
                        Math.max(
                            originalHeight *
                            BLUEPRINT_SCALE,

                            MIN_ROOM_SIZE
                        );


                    // =============================================
                    // FIND CENTER OF ROOM IN 2D BLUEPRINT
                    //
                    // Your database position represents the
                    // top left corner.
                    //
                    // Three.js positions boxes from the center.
                    // =============================================

                    const roomCenterX =
                        originalX +
                        originalWidth / 2;

                    const roomCenterY =
                        originalY +
                        originalHeight / 2;


                    // =============================================
                    // CENTER ROOM RELATIVE TO ITS OWN FLOOR
                    // =============================================

                    const roomX =
                        (
                            roomCenterX -
                            floorCenterX
                        ) *
                        BLUEPRINT_SCALE;

                    const roomZ =
                        (
                            roomCenterY -
                            floorCenterY
                        ) *
                        BLUEPRINT_SCALE;


                    // =============================================
                    // DETERMINE ROOM STATUS
                    // =============================================

                    // =============================================
                    // PHASE 6
                    // DETERMINE DYNAMIC ROOM STATUS
                    //
                    // available    = no active issue
                    // needs-repair = active non urgent report
                    // maintenance  = equipment under maintenance
                    // critical     = active urgent report
                    // =============================================

                    let room3DStatus =
                        'normal';


                    // =============================================
                    // ACTIVE REPORT
                    // OR EQUIPMENT UNDER MAINTENANCE
                    // =============================================

                    if (
                        roomData.status === 'needs-repair' ||
                        roomData.status === 'maintenance'
                    ) {

                        room3DStatus =
                            'warning';

                    }


                    // =============================================
                    // ACTIVE URGENT REPORT
                    // HIGHEST PRIORITY
                    // =============================================

                    if (
                        roomData.status === 'critical'
                    ) {

                        room3DStatus =
                            'urgent';

                    }


                    // =============================================
                    // CREATE ROOM
                    // =============================================

                    const roomMesh =
                        createRoom(
                            roomData.name,
                            roomX,
                            floorY,
                            roomZ,
                            roomWidth,
                            roomDepth,
                            room3DStatus
                        );

                    // =================================================
                    // PHASE 7.10
                    // CREATE ROOM NAME LABEL
                    // =================================================

                    const roomLabel =
                        createRoomLabel(
                            roomData.name
                        );


                    // Position label above the room
                    roomLabel.position.set(
                        0,
                        1.8,
                        0
                    );


                    // Add label directly to room mesh
                    // This makes the label follow the room position
                    roomMesh.add(
                        roomLabel
                    );

                    roomMesh.userData.originalEmissive =
                        roomMesh.material.emissive.getHex();

                    roomMesh.userData.originalEmissiveIntensity =
                        roomMesh.material.emissiveIntensity;

                    // =================================================
                    // PHASE 7.4
                    // ADD ROOM TO ITS FLOOR GROUP
                    // =================================================

                    floorGroup.add(
                        roomMesh
                    );


                    // =============================================
                    // PHASE 7.8
                    // SAVE DATABASE INFORMATION FOR ROOM PANEL
                    // =============================================

                    roomMesh.userData = {

                        type: 'room',

                        roomId:
                            roomData.id,

                        roomName:
                            roomData.name,

                        roomType:
                            roomData.type,

                        roomStatus:
                            roomData.status,

                        floorId:
                            floorData.id,

                        floorName:
                            floorData.name,

                        // =========================================
                        // PHASE 7.8
                        // ROOM MAINTENANCE INFORMATION
                        // =========================================

                        activeReportCount:
                            roomData.activeReportCount || 0,

                        urgentReportCount:
                            roomData.urgentReportCount || 0,

                        maintenanceEquipmentCount:
                            roomData.maintenanceEquipmentCount || 0,

                        originalEmissive:
                            roomMesh.material.emissive.getHex(),

                        originalEmissiveIntensity:
                            roomMesh.material.emissiveIntensity

                    };

                    clickableRooms.push(
                        roomMesh
                    );


                    // =============================================
                    // APPLY SAVED ROOM ROTATION
                    // =============================================

                    roomMesh.rotation.y =
                        THREE.MathUtils.degToRad(
                            roomData.rotation || 0
                        );

                });

            });

            // =====================================================
            // PHASE 8.1 PART 2
            // CALCULATE COMPLETE INTERIOR BUILDING BOUNDS
            // =====================================================

            function getBuildingInteriorBounds() {

                building.updateMatrixWorld(true);

                const bounds =
                    new THREE.Box3()
                        .setFromObject(building);

                if (bounds.isEmpty()) {
                    return null;
                }

                return bounds;
            }


            // =====================================================
            // PHASE 8.1 PART 2
            // CREATE AUTO FITTING EXTERIOR BUILDING
            // =====================================================

            // =====================================================
            // PHASE 8.3 PART 1
            // BASIC ARCHITECTURAL EXTERIOR SHAPE
            //
            // REFERENCE:
            // ROBINSONS ORMOC CENTRUM / STI COLLEGE ORMOC
            //
            // IMPORTANT BUILDING LAYOUT:
            //
            // GROUND FLOOR
            // Robinsons / commercial area
            //
            // SECOND AND THIRD FLOOR
            // STI College Ormoc
            //
            // FRONT
            // Robinsons commercial facade
            // Large central arched architectural feature
            //
            // BACK
            // Actual STI College entrance
            // Staircase from ground level to second floor
            // =====================================================

            function createDynamicBuildingExterior() {

                const bounds =
                    getBuildingInteriorBounds();

                if (!bounds) {

                    console.warn(
                        'Could not calculate building interior bounds.'
                    );

                    return;
                }


                // =================================================
                // GET COMPLETE INTERIOR SIZE AND CENTER
                // =================================================

                const size =
                    bounds.getSize(
                        new THREE.Vector3()
                    );

                const center =
                    bounds.getCenter(
                        new THREE.Vector3()
                    );


                // =================================================
                // EXTRA SPACE AROUND INTERIOR
                // =================================================

                const paddingX = 2.5;
                const paddingY = 1.5;
                const paddingZ = 2.5;


                const buildingWidth =
                    size.x + paddingX;

                const buildingHeight =
                    size.y + paddingY;

                const buildingDepth =
                    size.z + paddingZ;


                // =================================================
                // BUILDING DIRECTION
                //
                // CURRENT ASSUMPTION:
                //
                // +Z = FRONT
                // -Z = BACK
                //
                // Robinsons facade is located at +Z.
                // STI entrance will eventually be located at -Z.
                // =================================================

                const frontZ =
                    center.z + (buildingDepth / 2);

                const backZ =
                    center.z - (buildingDepth / 2);


                // =================================================
                // 1. MAIN LONG BUILDING BODY
                //
                // Represents the overall rectangular structure.
                //
                // This contains:
                //
                // Ground Floor
                // Robinsons / Commercial
                //
                // Upper Floors
                // STI College Ormoc
                // =================================================

                const exteriorMainBuilding =
                    createExteriorSection(

                        buildingWidth,

                        buildingHeight,

                        buildingDepth,

                        center.x,

                        center.y,

                        center.z

                    );

                exteriorMainBuilding.userData.isBuildingExterior =
                    true;


                // =================================================
                // 2. FRONT GROUND FLOOR COMMERCIAL BAND
                //
                // This represents the long Robinsons frontage
                // visible across the ground floor.
                //
                // IMPORTANT:
                // This is NOT the STI College entrance.
                // =================================================

                const commercialBandHeight =
                    Math.max(
                        buildingHeight * 0.28,
                        1.2
                    );

                const commercialBandDepth =
                    Math.max(
                        buildingDepth * 0.08,
                        0.6
                    );

                const commercialBand =
                    createExteriorSection(

                        buildingWidth * 0.96,

                        commercialBandHeight,

                        commercialBandDepth,

                        center.x,

                        center.y -
                            (buildingHeight / 2) +
                            (commercialBandHeight / 2),

                        frontZ +
                            (commercialBandDepth / 2)

                    );

                commercialBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 3. SECOND FLOOR FRONT FACADE
                //
                // Represents the long horizontal upper facade
                // occupied partly by STI College.
                // =================================================

                const upperFacadeHeight =
                    Math.max(
                        buildingHeight * 0.22,
                        1
                    );

                const upperFacadeDepth =
                    Math.max(
                        buildingDepth * 0.035,
                        0.3
                    );

                const secondFloorFacade =
                    createExteriorSection(

                        buildingWidth * 0.94,

                        upperFacadeHeight,

                        upperFacadeDepth,

                        center.x,

                        center.y,

                        frontZ +
                            (upperFacadeDepth / 2)

                    );

                secondFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // 4. THIRD FLOOR / UPPER FACADE BAND
                //
                // Creates the upper horizontal shape visible
                // along the entire building.
                // =================================================

                const thirdFloorFacade =
                    createExteriorSection(

                        buildingWidth * 0.96,

                        upperFacadeHeight * 0.8,

                        upperFacadeDepth,

                        center.x,

                        center.y +
                            (buildingHeight * 0.30),

                        frontZ +
                            (upperFacadeDepth / 2)

                    );

                thirdFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // 5. FRONT CENTRAL ROBINSONS ARCH FEATURE
                //
                // IMPORTANT:
                //
                // This is NOT the STI College entrance.
                //
                // This represents the large curved architectural
                // feature visible at the center of the Robinsons
                // Ormoc Centrum front facade.
                //
                // The actual STI entrance is located at the BACK
                // of the building.
                // =================================================

                const frontFeatureWidth =
                    Math.max(
                        buildingWidth * 0.18,
                        3
                    );

                const frontFeatureDepth =
                    Math.max(
                        buildingDepth * 0.05,
                        0.4
                    );

                const frontFeatureHeight =
                    buildingHeight * 1.12;


                // =================================================
                // CREATE THE CURVED FRONT ARCH
                // =================================================

                const frontCentralArch =
                    createExteriorFrontArch(

                        frontFeatureWidth,

                        frontFeatureHeight,

                        frontFeatureDepth,

                        center.x,

                        center.y +
                            (
                                frontFeatureHeight -
                                buildingHeight
                            ) / 2,

                        frontZ +
                            (frontFeatureDepth / 2)

                    );

                // =================================================
                // CENTRAL GLASS FACADE
                //
                // The real building has a large glass section
                // underneath the curved architectural arch.
                //
                // This is only a simplified blueprint representation.
                // =================================================

                const glassWidth =
                    frontFeatureWidth * 0.62;

                const glassHeight =
                    frontFeatureHeight * 0.58;

                const glassDepth =
                    0.08;


                const frontGlassFacade =
                    createExteriorSection(

                        glassWidth,

                        glassHeight,

                        glassDepth,

                        center.x,

                        center.y +
                            (frontFeatureHeight * 0.02),

                        frontZ +
                            frontFeatureDepth +
                            0.02

                    );


                frontGlassFacade.userData.isBuildingExterior =
                    true;


                


                // =================================================
                // PHASE 8.3 PART 2
                // FRONT HORIZONTAL ARCHITECTURAL BANDS
                //
                // These represent the long horizontal layers
                // visible across the Robinsons Ormoc facade.
                // =================================================

                const facadeBandDepth =
                    Math.max(
                        buildingDepth * 0.045,
                        0.35
                    );


                // =================================================
                // LOWER FRONT BAND
                // =================================================

                const lowerFacadeBand =
                    createExteriorSection(

                        buildingWidth * 0.98,

                        0.32,

                        facadeBandDepth,

                        center.x,

                        center.y -
                            (buildingHeight * 0.18),

                        frontZ +
                            (facadeBandDepth / 2) +
                            0.05

                    );

                lowerFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // MIDDLE FRONT BAND
                // =================================================

                const middleFacadeBand =
                    createExteriorSection(

                        buildingWidth * 0.96,

                        0.22,

                        facadeBandDepth * 0.8,

                        center.x,

                        center.y +
                            (buildingHeight * 0.10),

                        frontZ +
                            (facadeBandDepth / 2) +
                            0.03

                    );

                middleFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // UPPER FRONT BAND
                // =================================================

                const upperFacadeBand =
                    createExteriorSection(

                        buildingWidth * 0.98,

                        0.28,

                        facadeBandDepth,

                        center.x,

                        center.y +
                            (buildingHeight * 0.38),

                        frontZ +
                            (facadeBandDepth / 2) +
                            0.05

                    );

                upperFacadeBand.userData.isBuildingExterior =
                    true;

                // =================================================
                // PHASE 8.3 PART 2.1
                // WRAPAROUND HORIZONTAL ARCHITECTURAL BANDS
                //
                // Extends the three existing front facade bands
                // around the LEFT SIDE, RIGHT SIDE, and BACK.
                //
                // IMPORTANT:
                // All bands use the exact same Y positions as the
                // existing front bands so they connect visually
                // around the corners of the building.
                //
                // +Z = FRONT
                // -Z = BACK
                // =================================================


                // =================================================
                // SHARED BAND POSITIONS
                // Must match the existing front facade bands.
                // =================================================

                const lowerBandY =
                    center.y -
                    (buildingHeight * 0.18);

                const middleBandY =
                    center.y +
                    (buildingHeight * 0.10);

                const upperBandY =
                    center.y +
                    (buildingHeight * 0.38);


                // =================================================
                // SIDE BAND THICKNESS
                //
                // Since the side walls run along the Z axis,
                // the thin dimension is now X instead of Z.
                // =================================================

                const sideBandThickness =
                    facadeBandDepth;


                // =================================================
                // BACK BAND OFFSET
                // Places the bands slightly outside the back wall.
                // =================================================

                const backBandZ =
                    backZ -
                    (facadeBandDepth / 2) -
                    0.05;


                // =================================================
                // LEFT SIDE POSITION
                // =================================================

                const leftBandX =
                    center.x -
                    (buildingWidth / 2) -
                    (sideBandThickness / 2) -
                    0.05;


                // =================================================
                // RIGHT SIDE POSITION
                // =================================================

                const rightBandX =
                    center.x +
                    (buildingWidth / 2) +
                    (sideBandThickness / 2) +
                    0.05;


                // =================================================
                // 1. LOWER BACK BAND
                // =================================================

                const lowerBackFacadeBand =
                    createExteriorSection(

                        buildingWidth * 0.98,

                        0.32,

                        facadeBandDepth,

                        center.x,

                        lowerBandY,

                        backBandZ

                    );

                lowerBackFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 2. MIDDLE BACK BAND
                // =================================================

                const middleBackFacadeBand =
                    createExteriorSection(

                        buildingWidth * 0.96,

                        0.22,

                        facadeBandDepth * 0.8,

                        center.x,

                        middleBandY,

                        backZ -
                        ((facadeBandDepth * 0.8) / 2) -
                        0.03

                    );

                middleBackFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 3. UPPER BACK BAND
                // =================================================

                const upperBackFacadeBand =
                    createExteriorSection(

                        buildingWidth * 0.98,

                        0.28,

                        facadeBandDepth,

                        center.x,

                        upperBandY,

                        backBandZ

                    );

                upperBackFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 4. LOWER LEFT SIDE BAND
                //
                // Width is thin because this is attached to the
                // left wall.
                //
                // Depth runs almost the entire building length.
                // =================================================

                const lowerLeftFacadeBand =
                    createExteriorSection(

                        sideBandThickness,

                        0.32,

                        buildingDepth * 0.98,

                        leftBandX,

                        lowerBandY,

                        center.z

                    );

                lowerLeftFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 5. MIDDLE LEFT SIDE BAND
                // =================================================

                const middleLeftFacadeBand =
                    createExteriorSection(

                        sideBandThickness * 0.8,

                        0.22,

                        buildingDepth * 0.96,

                        center.x -
                        (buildingWidth / 2) -
                        ((sideBandThickness * 0.8) / 2) -
                        0.03,

                        middleBandY,

                        center.z

                    );

                middleLeftFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 6. UPPER LEFT SIDE BAND
                // =================================================

                const upperLeftFacadeBand =
                    createExteriorSection(

                        sideBandThickness,

                        0.28,

                        buildingDepth * 0.98,

                        leftBandX,

                        upperBandY,

                        center.z

                    );

                upperLeftFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 7. LOWER RIGHT SIDE BAND
                // =================================================

                const lowerRightFacadeBand =
                    createExteriorSection(

                        sideBandThickness,

                        0.32,

                        buildingDepth * 0.98,

                        rightBandX,

                        lowerBandY,

                        center.z

                    );

                lowerRightFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 8. MIDDLE RIGHT SIDE BAND
                // =================================================

                const middleRightFacadeBand =
                    createExteriorSection(

                        sideBandThickness * 0.8,

                        0.22,

                        buildingDepth * 0.96,

                        center.x +
                        (buildingWidth / 2) +
                        ((sideBandThickness * 0.8) / 2) +
                        0.03,

                        middleBandY,

                        center.z

                    );

                middleRightFacadeBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 9. UPPER RIGHT SIDE BAND
                // =================================================

                const upperRightFacadeBand =
                    createExteriorSection(

                        sideBandThickness,

                        0.28,

                        buildingDepth * 0.98,

                        rightBandX,

                        upperBandY,

                        center.z

                    );

                upperRightFacadeBand.userData.isBuildingExterior =
                    true;

                
                


                

                // =================================================
                // PHASE 8.3 PART 3
                // WINDOW ROWS AND FACADE SEGMENTATION
                //
                // Adds simplified architectural window modules
                // to the second and third floor.
                //
                // IMPORTANT:
                //
                // FRONT:
                // Window rows are split into LEFT and RIGHT sections
                // because the central Robinsons arch occupies the
                // middle of the facade.
                //
                // LEFT / RIGHT SIDES:
                // Window rows continue along the building depth.
                //
                // BACK:
                // Window rows are also split around the center because
                // the future STI College entrance and staircase will
                // occupy the central rear section.
                // =================================================


                // =================================================
                // WINDOW CONFIGURATION
                // =================================================

                const windowHeight =
                    Math.max(
                        buildingHeight * 0.11,
                        0.55
                    );

                const windowDepth =
                    Math.max(
                        facadeBandDepth * 0.35,
                        0.08
                    );

                const windowGap =
                    Math.max(
                        buildingWidth * 0.012,
                        0.18
                    );


                // =================================================
                // FLOOR WINDOW Y POSITIONS
                //
                // These sit between the horizontal facade bands.
                // =================================================

                const secondFloorWindowY =
                    center.y -
                    (buildingHeight * 0.03);

                const thirdFloorWindowY =
                    center.y +
                    (buildingHeight * 0.25);


                // =================================================
                // FRONT WINDOW AREA
                //
                // The center is intentionally left empty because
                // the large Robinsons architectural arch and glass
                // facade already occupy this section.
                // =================================================

                const frontWindowSideWidth =
                    (
                        buildingWidth -
                        frontFeatureWidth
                    ) / 2;


                // =================================================
                // HELPER FUNCTION
                // CREATE FRONT OR BACK WINDOW ROW
                //
                // Creates individual rectangular window modules
                // across a horizontal section.
                // =================================================

                function createHorizontalWindowRow(
                    startX,
                    totalWidth,
                    y,
                    z,
                    faceDirection
                ) {

                    const approximateWindowWidth =
                        Math.max(
                            buildingWidth * 0.055,
                            0.7
                        );

                    const windowCount =
                        Math.max(
                            2,
                            Math.floor(
                                totalWidth /
                                (
                                    approximateWindowWidth +
                                    windowGap
                                )
                            )
                        );

                    const usableWidth =
                        totalWidth -
                        (
                            windowGap *
                            (windowCount - 1)
                        );

                    const actualWindowWidth =
                        usableWidth /
                        windowCount;


                    for (
                        let i = 0;
                        i < windowCount;
                        i++
                    ) {

                        const windowX =
                            startX +
                            (actualWindowWidth / 2) +
                            i *
                            (
                                actualWindowWidth +
                                windowGap
                            );


                        const windowSection =
                            createExteriorSection(

                                actualWindowWidth,

                                windowHeight,

                                windowDepth,

                                windowX,

                                y,

                                z

                            );


                        windowSection.userData.isBuildingExterior =
                            true;

                        windowSection.userData.isExteriorWindow =
                            true;

                        windowSection.userData.windowFace =
                            faceDirection;

                    }

                }


                // =================================================
                // FRONT WINDOW Z POSITION
                // =================================================

                const frontWindowZ =
                    frontZ +
                    (windowDepth / 2) +
                    facadeBandDepth +
                    0.04;


                // =================================================
                // LEFT FRONT WINDOW SECTION
                // =================================================

                const leftFrontWindowStart =
                    center.x -
                    (buildingWidth / 2) +
                    (buildingWidth * 0.03);

                const leftFrontWindowWidth =
                    frontWindowSideWidth -
                    (buildingWidth * 0.06);


                // =================================================
                // RIGHT FRONT WINDOW SECTION
                // =================================================

                const rightFrontWindowStart =
                    center.x +
                    (frontFeatureWidth / 2) +
                    (buildingWidth * 0.03);

                const rightFrontWindowWidth =
                    frontWindowSideWidth -
                    (buildingWidth * 0.06);


                // =================================================
                // SECOND FLOOR FRONT WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(

                    leftFrontWindowStart,

                    leftFrontWindowWidth,

                    secondFloorWindowY,

                    frontWindowZ,

                    'front'

                );


                // =================================================
                // SECOND FLOOR FRONT WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(

                    rightFrontWindowStart,

                    rightFrontWindowWidth,

                    secondFloorWindowY,

                    frontWindowZ,

                    'front'

                );


                // =================================================
                // THIRD FLOOR FRONT WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(

                    leftFrontWindowStart,

                    leftFrontWindowWidth,

                    thirdFloorWindowY,

                    frontWindowZ,

                    'front'

                );


                // =================================================
                // THIRD FLOOR FRONT WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(

                    rightFrontWindowStart,

                    rightFrontWindowWidth,

                    thirdFloorWindowY,

                    frontWindowZ,

                    'front'

                );


                // =================================================
                // BACK WINDOW CONFIGURATION
                //
                // Leave a center opening for the future STI entrance
                // and staircase architecture.
                // =================================================

                const backCenterClearance =
                Math.max(
                    buildingWidth * 0.225,
                    3.75
                );
                const backWindowSideWidth =
                    (
                        buildingWidth -
                        backCenterClearance
                    ) / 2;

                const backWindowZ =
                    backZ -
                    (windowDepth / 2) -
                    facadeBandDepth -
                    0.04;


                // =================================================
                // LEFT BACK WINDOW SECTION
                // =================================================

                const leftBackWindowStart =
                    center.x -
                    (buildingWidth / 2) +
                    (buildingWidth * 0.03);

                const leftBackWindowWidth =
                    backWindowSideWidth -
                    (buildingWidth * 0.06);


                // =================================================
                // RIGHT BACK WINDOW SECTION
                // =================================================

                const rightBackWindowStart =
                    center.x +
                    (backCenterClearance / 2) +
                    (buildingWidth * 0.03);

                const rightBackWindowWidth =
                    backWindowSideWidth -
                    (buildingWidth * 0.06);


                // =================================================
                // SECOND FLOOR BACK WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(

                    leftBackWindowStart,

                    leftBackWindowWidth,

                    secondFloorWindowY,

                    backWindowZ,

                    'back'

                );


                // =================================================
                // SECOND FLOOR BACK WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(

                    rightBackWindowStart,

                    rightBackWindowWidth,

                    secondFloorWindowY,

                    backWindowZ,

                    'back'

                );


                // =================================================
                // THIRD FLOOR BACK WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(

                    leftBackWindowStart,

                    leftBackWindowWidth,

                    thirdFloorWindowY,

                    backWindowZ,

                    'back'

                );


                // =================================================
                // THIRD FLOOR BACK WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(

                    rightBackWindowStart,

                    rightBackWindowWidth,

                    thirdFloorWindowY,

                    backWindowZ,

                    'back'

                );


                // =================================================
                // SIDE WINDOW HELPER
                //
                // Side windows run along the Z axis instead of X.
                // =================================================

                function createSideWindowRow(
                    x,
                    y,
                    startZ,
                    totalDepth,
                    faceDirection
                ) {

                    const approximateWindowWidth =
                        Math.max(
                            buildingDepth * 0.08,
                            0.7
                        );

                    const sideWindowGap =
                        Math.max(
                            buildingDepth * 0.018,
                            0.18
                        );


                    const windowCount =
                        Math.max(
                            2,
                            Math.floor(
                                totalDepth /
                                (
                                    approximateWindowWidth +
                                    sideWindowGap
                                )
                            )
                        );


                    const usableDepth =
                        totalDepth -
                        (
                            sideWindowGap *
                            (windowCount - 1)
                        );


                    const actualWindowDepth =
                        usableDepth /
                        windowCount;


                    for (
                        let i = 0;
                        i < windowCount;
                        i++
                    ) {

                        const windowZ =
                            startZ +
                            (actualWindowDepth / 2) +
                            i *
                            (
                                actualWindowDepth +
                                sideWindowGap
                            );


                        const windowSection =
                            createExteriorSection(

                                windowDepth,

                                windowHeight,

                                actualWindowDepth,

                                x,

                                y,

                                windowZ

                            );


                        windowSection.userData.isBuildingExterior =
                            true;

                        windowSection.userData.isExteriorWindow =
                            true;

                        windowSection.userData.windowFace =
                            faceDirection;

                    }

                }


                // =================================================
                // SIDE WINDOW POSITIONS
                // =================================================

                const leftWindowX =
                    center.x -
                    (buildingWidth / 2) -
                    facadeBandDepth -
                    (windowDepth / 2) -
                    0.04;

                const rightWindowX =
                    center.x +
                    (buildingWidth / 2) +
                    facadeBandDepth +
                    (windowDepth / 2) +
                    0.04;


                // =================================================
                // SIDE WINDOW DEPTH AREA
                //
                // Leave a little space near the front and back
                // corners so the windows do not collide with the
                // corner architectural sections.
                // =================================================

                const sideWindowStartZ =
                    backZ +
                    (buildingDepth * 0.06);

                const sideWindowTotalDepth =
                    buildingDepth * 0.88;


                // =================================================
                // LEFT SIDE
                // SECOND FLOOR WINDOWS
                // =================================================

                createSideWindowRow(

                    leftWindowX,

                    secondFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    'left'

                );


                // =================================================
                // LEFT SIDE
                // THIRD FLOOR WINDOWS
                // =================================================

                createSideWindowRow(

                    leftWindowX,

                    thirdFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    'left'

                );


                // =================================================
                // RIGHT SIDE
                // SECOND FLOOR WINDOWS
                // =================================================

                createSideWindowRow(

                    rightWindowX,

                    secondFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    'right'

                );


                // =================================================
                // RIGHT SIDE
                // THIRD FLOOR WINDOWS
                // =================================================

                createSideWindowRow(

                    rightWindowX,

                    thirdFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    'right'

                );


                // =================================================
                // 6. MAIN TOP ROOF
                //
                // Long flat roof following the overall building.
                //
                // The actual reference has rounded corners.
                // Those will be refined later.
                // =================================================

                const mainRoofThickness =
                    0.25;

                const exteriorRoof =
                    createExteriorSection(

                        buildingWidth + 0.5,

                        mainRoofThickness,

                        buildingDepth + 0.5,

                        center.x,

                        center.y +
                            (buildingHeight / 2) +
                            (mainRoofThickness / 2),

                        center.z

                    );

                exteriorRoof.userData.isBuildingExterior =
                    true;


                // =================================================
                // 7. FRONT ROOF FASCIA
                //
                // Creates the thicker upper edge visible from
                // the street-facing side of the real building.
                // =================================================

                const roofFasciaHeight =
                    Math.max(
                        buildingHeight * 0.10,
                        0.5
                    );

                const roofFasciaDepth =
                    Math.max(
                        buildingDepth * 0.06,
                        0.5
                    );


                const frontRoofFascia =
                    createExteriorSection(

                        buildingWidth + 0.3,

                        roofFasciaHeight,

                        roofFasciaDepth,

                        center.x,

                        center.y +
                            (buildingHeight / 2) -
                            (roofFasciaHeight / 2),

                        frontZ +
                            (roofFasciaDepth / 2)

                    );

                frontRoofFascia.userData.isBuildingExterior =
                    true;


                // =================================================
                // 8. LEFT SIDE CORNER MASS
                //
                // The real building has large corner sections
                // instead of separate front wings.
                //
                // This replaces the incorrect left front wing
                // from the previous version.
                // =================================================

                const cornerWidth =
                    Math.max(
                        buildingWidth * 0.12,
                        1.8
                    );

                const cornerDepth =
                    Math.max(
                        buildingDepth * 0.10,
                        0.8
                    );


                const leftCornerSection =
                    createExteriorSection(

                        cornerWidth,

                        buildingHeight * 0.95,

                        cornerDepth,

                        center.x -
                            (buildingWidth / 2) +
                            (cornerWidth / 2),

                        center.y,

                        frontZ +
                            (cornerDepth / 2)

                    );

                leftCornerSection.userData.isBuildingExterior =
                    true;


                // =================================================
                // 9. RIGHT SIDE CORNER MASS
                //
                // Mirrors the left side.
                // =================================================

                const rightCornerSection =
                    createExteriorSection(

                        cornerWidth,

                        buildingHeight * 0.95,

                        cornerDepth,

                        center.x +
                            (buildingWidth / 2) -
                            (cornerWidth / 2),

                        center.y,

                        frontZ +
                            (cornerDepth / 2)

                    );

                rightCornerSection.userData.isBuildingExterior =
                    true;


                // =================================================
                // PHASE 8.3 PART 4
                // REAR STI COLLEGE ORMOC ENTRANCE
                // AND EXTERIOR STAIRCASE
                //
                // IMPORTANT BUILDING LAYOUT:
                //
                // GROUND FLOOR:
                // Robinsons / Commercial Area
                //
                // SECOND FLOOR:
                // STI College Ormoc entrance
                //
                // The staircase begins at ground level behind the
                // building and rises toward a central second-floor
                // entrance.
                //
                // +Z = FRONT
                // -Z = BACK
                // =================================================


                // =================================================
                // STI ENTRANCE DIMENSIONS
                // =================================================

                const stiEntranceWidth =
                    Math.max(
                        buildingWidth * 0.18,
                        3
                    );

                const stiEntranceDepth =
                    Math.max(
                        buildingDepth * 0.10,
                        1
                    );

                // =================================================
                // PHASE 8.3 PART 2.2
                // WRAPAROUND MAIN FACADE SECTIONS
                //
                // Extends the main facade sections to:
                // 1. LEFT SIDE
                // 2. RIGHT SIDE
                // 3. REAR SIDE
                //
                // IMPORTANT:
                // The rear ground floor and second floor facades
                // are split into LEFT and RIGHT sections.
                //
                // This creates a clear opening for the actual
                // STI College entrance, landing, and staircase.
                //
                // The third floor remains continuous.
                // =================================================


                // =================================================
                // REAR STI ENTRANCE FACADE OPENING
                //
                // Creates extra clearance around the STI entrance.
                // =================================================

                const rearEntranceOpeningWidth =
                    stiEntranceWidth + 0.8;


                // =================================================
                // SHARED SIDE FACADE THICKNESS
                // =================================================

                const sideFacadeThickness =
                    upperFacadeDepth;


                // =================================================
                // 1. REAR GROUND FLOOR COMMERCIAL BAND
                // SPLIT AROUND STI ENTRANCE
                // =================================================

                const rearCommercialTotalWidth =
                    buildingWidth * 0.96;


                const rearCommercialSideWidth =
                    (
                        rearCommercialTotalWidth -
                        rearEntranceOpeningWidth
                    ) / 2;


                // =================================================
                // LEFT AND RIGHT X POSITIONS
                // =================================================

                const rearCommercialLeftX =
                    center.x -
                    (rearEntranceOpeningWidth / 2) -
                    (rearCommercialSideWidth / 2);


                const rearCommercialRightX =
                    center.x +
                    (rearEntranceOpeningWidth / 2) +
                    (rearCommercialSideWidth / 2);


                // =================================================
                // REAR COMMERCIAL BAND
                // LEFT SECTION
                // =================================================

                const rearCommercialBandLeft =
                    createExteriorSection(

                        rearCommercialSideWidth,

                        commercialBandHeight,

                        commercialBandDepth,

                        rearCommercialLeftX,

                        center.y -
                            (buildingHeight / 2) +
                            (commercialBandHeight / 2),

                        backZ -
                            (commercialBandDepth / 2)

                    );

                rearCommercialBandLeft.userData.isBuildingExterior =
                    true;


                // =================================================
                // REAR COMMERCIAL BAND
                // RIGHT SECTION
                // =================================================

                const rearCommercialBandRight =
                    createExteriorSection(

                        rearCommercialSideWidth,

                        commercialBandHeight,

                        commercialBandDepth,

                        rearCommercialRightX,

                        center.y -
                            (buildingHeight / 2) +
                            (commercialBandHeight / 2),

                        backZ -
                            (commercialBandDepth / 2)

                    );

                rearCommercialBandRight.userData.isBuildingExterior =
                    true;


                // =================================================
                // 2. LEFT GROUND FLOOR COMMERCIAL BAND
                // =================================================

                const leftCommercialBand =
                    createExteriorSection(

                        commercialBandDepth,

                        commercialBandHeight,

                        buildingDepth * 0.96,

                        center.x -
                            (buildingWidth / 2) -
                            (commercialBandDepth / 2),

                        center.y -
                            (buildingHeight / 2) +
                            (commercialBandHeight / 2),

                        center.z

                    );

                leftCommercialBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 3. RIGHT GROUND FLOOR COMMERCIAL BAND
                // =================================================

                const rightCommercialBand =
                    createExteriorSection(

                        commercialBandDepth,

                        commercialBandHeight,

                        buildingDepth * 0.96,

                        center.x +
                            (buildingWidth / 2) +
                            (commercialBandDepth / 2),

                        center.y -
                            (buildingHeight / 2) +
                            (commercialBandHeight / 2),

                        center.z

                    );

                rightCommercialBand.userData.isBuildingExterior =
                    true;


                // =================================================
                // 4. REAR SECOND FLOOR FACADE
                // SPLIT AROUND STI ENTRANCE
                // =================================================

                const rearSecondFacadeTotalWidth =
                    buildingWidth * 0.94;


                const rearSecondFacadeSideWidth =
                    (
                        rearSecondFacadeTotalWidth -
                        rearEntranceOpeningWidth
                    ) / 2;


                // =================================================
                // LEFT AND RIGHT X POSITIONS
                // =================================================

                const rearSecondLeftX =
                    center.x -
                    (rearEntranceOpeningWidth / 2) -
                    (rearSecondFacadeSideWidth / 2);


                const rearSecondRightX =
                    center.x +
                    (rearEntranceOpeningWidth / 2) +
                    (rearSecondFacadeSideWidth / 2);


                // =================================================
                // REAR SECOND FLOOR FACADE
                // LEFT SECTION
                // =================================================

                const rearSecondFloorFacadeLeft =
                    createExteriorSection(

                        rearSecondFacadeSideWidth,

                        upperFacadeHeight,

                        upperFacadeDepth,

                        rearSecondLeftX,

                        center.y,

                        backZ -
                            (upperFacadeDepth / 2)

                    );

                rearSecondFloorFacadeLeft.userData.isBuildingExterior =
                    true;


                // =================================================
                // REAR SECOND FLOOR FACADE
                // RIGHT SECTION
                // =================================================

                const rearSecondFloorFacadeRight =
                    createExteriorSection(

                        rearSecondFacadeSideWidth,

                        upperFacadeHeight,

                        upperFacadeDepth,

                        rearSecondRightX,

                        center.y,

                        backZ -
                            (upperFacadeDepth / 2)

                    );

                rearSecondFloorFacadeRight.userData.isBuildingExterior =
                    true;


                // =================================================
                // 5. LEFT SECOND FLOOR FACADE
                // =================================================

                const leftSecondFloorFacade =
                    createExteriorSection(

                        sideFacadeThickness,

                        upperFacadeHeight,

                        buildingDepth * 0.94,

                        center.x -
                            (buildingWidth / 2) -
                            (sideFacadeThickness / 2),

                        center.y,

                        center.z

                    );

                leftSecondFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // 6. RIGHT SECOND FLOOR FACADE
                // =================================================

                const rightSecondFloorFacade =
                    createExteriorSection(

                        sideFacadeThickness,

                        upperFacadeHeight,

                        buildingDepth * 0.94,

                        center.x +
                            (buildingWidth / 2) +
                            (sideFacadeThickness / 2),

                        center.y,

                        center.z

                    );

                rightSecondFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // 7. REAR THIRD FLOOR FACADE
                //
                // This remains continuous because the STI entrance
                // opening only needs to affect the lower levels.
                // =================================================

                const rearThirdFloorFacade =
                    createExteriorSection(

                        buildingWidth * 0.96,

                        upperFacadeHeight * 0.8,

                        upperFacadeDepth,

                        center.x,

                        center.y +
                            (buildingHeight * 0.30),

                        backZ -
                            (upperFacadeDepth / 2)

                    );

                rearThirdFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // 8. LEFT THIRD FLOOR FACADE
                // =================================================

                const leftThirdFloorFacade =
                    createExteriorSection(

                        sideFacadeThickness,

                        upperFacadeHeight * 0.8,

                        buildingDepth * 0.96,

                        center.x -
                            (buildingWidth / 2) -
                            (sideFacadeThickness / 2),

                        center.y +
                            (buildingHeight * 0.30),

                        center.z

                    );

                leftThirdFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // 9. RIGHT THIRD FLOOR FACADE
                // =================================================

                const rightThirdFloorFacade =
                    createExteriorSection(

                        sideFacadeThickness,

                        upperFacadeHeight * 0.8,

                        buildingDepth * 0.96,

                        center.x +
                            (buildingWidth / 2) +
                            (sideFacadeThickness / 2),

                        center.y +
                            (buildingHeight * 0.30),

                        center.z

                    );

                rightThirdFloorFacade.userData.isBuildingExterior =
                    true;


                // =================================================
                // ESTIMATED FLOOR HEIGHT
                //
                // The exterior represents approximately three
                // vertical building levels.
                //
                // We use this to calculate the second-floor height.
                // =================================================

                const estimatedFloorHeight =
                    buildingHeight / 3;


                // =================================================
                // GROUND LEVEL
                // =================================================

                const buildingBottomY =
                    center.y -
                    (buildingHeight / 2);


                // =================================================
                // SECOND FLOOR ENTRANCE HEIGHT
                //
                // This is one floor above ground level.
                // =================================================

                const secondFloorEntranceY =
                    buildingBottomY +
                    estimatedFloorHeight;


                // =================================================
                // 1. SECOND FLOOR STI ENTRANCE LANDING
                //
                // This platform sits behind the building at the
                // second-floor level.
                //
                // The staircase will connect directly to it.
                // =================================================

                const stiLandingThickness =
                    0.22;

                const stiLandingDepth =
                    Math.max(
                        buildingDepth * 0.12,
                        1.4
                    );


                const stiEntranceLanding =
                    createExteriorSection(

                        stiEntranceWidth,

                        stiLandingThickness,

                        stiLandingDepth,

                        center.x,

                        secondFloorEntranceY,

                        backZ -
                        (stiLandingDepth / 2)

                    );

                stiEntranceLanding.userData.isBuildingExterior =
                    true;

                stiEntranceLanding.userData.isStiEntrance =
                    true;


                // =================================================
                // 2. STI SECOND FLOOR ENTRANCE FRAME
                //
                // Marks the doorway at the rear center.
                //
                // This is the actual school entrance position.
                // =================================================

                const stiDoorWidth =
                    Math.max(
                        stiEntranceWidth * 0.45,
                        1.2
                    );

                const stiDoorHeight =
                    Math.max(
                        estimatedFloorHeight * 0.58,
                        1.5
                    );

                const stiDoorDepth =
                    Math.max(
                        buildingDepth * 0.025,
                        0.18
                    );


                const stiEntranceFrame =
                    createExteriorSection(

                        stiDoorWidth,

                        stiDoorHeight,

                        stiDoorDepth,

                        center.x,

                        secondFloorEntranceY +
                        (stiDoorHeight / 2),

                        backZ -
                        (stiDoorDepth / 2) -
                        0.04

                    );

                stiEntranceFrame.userData.isBuildingExterior =
                    true;

                stiEntranceFrame.userData.isStiEntrance =
                    true;


                // =================================================
                // 3. STAIRCASE CONFIGURATION
                // SIDEWAYS REAR STAIRCASE
                //
                // The STI entrance remains centered at the BACK.
                //
                // The staircase now runs SIDEWAYS along the X axis.
                //
                // TOP:
                // Center landing at the STI entrance.
                //
                // BOTTOM:
                // Extends toward the RIGHT side of the building.
                //
                // Change stairDirection to -1 if you want the
                // staircase to descend toward the LEFT instead.
                // =================================================

                const stairDirection = -1;

                const stairWidth =
                    Math.max(
                        stiEntranceDepth * 0.85,
                        1.8
                    );

                const stairRun =
                    Math.max(
                        buildingWidth * 0.32,
                        4
                    );

                const stairStepCount =
                    12;


                // =================================================
                // CALCULATE STAIR STEP DIMENSIONS
                // =================================================

                const stairStepHeight =
                    estimatedFloorHeight /
                    stairStepCount;

                const stairStepRun =
                    stairRun /
                    stairStepCount;


                // =================================================
                // STAIRCASE Z POSITION
                //
                // Keeps the entire staircase behind the building
                // and aligned with the STI entrance landing.
                // =================================================

                const staircaseZ =
                    backZ -
                    stiLandingDepth -
                    (stairWidth / 2);

                // =================================================
                // STAIRCASE TOP CONNECTION POINT
                // Start from the RIGHT SIDE of the entrance landing
                // Keep the existing staircase direction unchanged
                // =================================================

                const stairTopX =
                    center.x +
                    (stiEntranceWidth / 2);


                // =================================================
                // CREATE SIDEWAYS STAIR STEPS
                //
                // Higher steps are closer to the center landing.
                //
                // Lower steps extend sideways toward the right.
                //
                // stairDirection = 1  -> right side
                // stairDirection = -1 -> left side
                // =================================================

                for (
                    let i = 0;
                    i < stairStepCount;
                    i++
                ) {

                    const currentStepHeight =
                        stairStepHeight *
                        (i + 1);


                    // =================================================
                    // STEP HEIGHT
                    // =================================================

                    const stepY =
                        buildingBottomY +
                        (currentStepHeight / 2);


                    // =================================================
                    // STEP X POSITION
                    //
                    // Step 0 is the lowest and farthest from entrance.
                    //
                    // Final step ends near the center landing.
                    // =================================================

                    const distanceFromLanding =
                        stairRun -
                        (
                            stairStepRun *
                            (i + 0.5)
                        );

                    const stepX =
                        stairTopX +
                        (
                            distanceFromLanding *
                            stairDirection
                        );


                    // =================================================
                    // CREATE STEP
                    //
                    // Width is now along X.
                    // Stair width is now along Z.
                    // =================================================

                    const stairStep =
                        createExteriorSection(

                            stairStepRun,

                            currentStepHeight,

                            stairWidth,

                            stepX,

                            stepY,

                            staircaseZ

                        );


                    stairStep.userData.isBuildingExterior =
                        true;

                    stairStep.userData.isStiStaircase =
                        true;

                }


                // =================================================
                // 4. BACK STAIR RAILING
                // FIXED FOR SIDEWAYS STAIRCASE
                // =================================================

                const railingThickness =
                    0.08;

                const railingHeight =
                    Math.max(
                        estimatedFloorHeight * 0.30,
                        0.8
                    );

                const stairSlopeAngle =
                    Math.atan2(
                        estimatedFloorHeight,
                        stairRun
                    );

                const staircaseCenterX =
                    stairTopX +
                    (
                        (stairRun / 2) *
                        stairDirection
                    );


                // =================================================
                // FIX:
                // Since the staircase runs along the X axis,
                // the railing geometry must follow X.
                //
                // Rotation direction must be OPPOSITE when
                // stairDirection is positive.
                // =================================================

                const railingRotationZ =
                    -stairDirection *
                    stairSlopeAngle;


                // =================================================
                // BACK STAIR RAILING
                // =================================================

                const backStairRailing =
                    createExteriorSection(

                        stairRun,

                        railingThickness,

                        railingHeight,

                        staircaseCenterX,

                        buildingBottomY +
                        (estimatedFloorHeight / 2) +
                        (railingHeight / 2),

                        staircaseZ -
                        (stairWidth / 2)

                    );

                backStairRailing.rotation.z =
                    railingRotationZ;

                backStairRailing.userData.isBuildingExterior =
                    true;

                backStairRailing.userData.isStiStaircase =
                    true;


                // =================================================
                // 5. FRONT STAIR RAILING
                // =================================================

                const frontStairRailing =
                    createExteriorSection(

                        stairRun,

                        railingThickness,

                        railingHeight,

                        staircaseCenterX,

                        buildingBottomY +
                        (estimatedFloorHeight / 2) +
                        (railingHeight / 2),

                        staircaseZ +
                        (stairWidth / 2)

                    );

                frontStairRailing.rotation.z =
                    railingRotationZ;

                frontStairRailing.userData.isBuildingExterior =
                    true;

                frontStairRailing.userData.isStiStaircase =
                    true;


                // =================================================
                // 6. LEFT LANDING RAILING
                // =================================================

                const landingRailingHeight =
                    Math.max(
                        estimatedFloorHeight * 0.25,
                        0.7
                    );


                const leftLandingRailing =
                    createExteriorSection(

                        railingThickness,

                        landingRailingHeight,

                        stiLandingDepth,

                        center.x -
                        (stiEntranceWidth / 2),

                        secondFloorEntranceY +
                        (landingRailingHeight / 2),

                        backZ -
                        (stiLandingDepth / 2)

                    );

                leftLandingRailing.userData.isBuildingExterior =
                    true;

                leftLandingRailing.userData.isStiEntrance =
                    true;


                // =================================================
                // 7. RIGHT LANDING RAILING
                // =================================================

                const rightLandingRailing =
                    createExteriorSection(

                        railingThickness,

                        landingRailingHeight,

                        stiLandingDepth,

                        center.x +
                        (stiEntranceWidth / 2),

                        secondFloorEntranceY +
                        (landingRailingHeight / 2),

                        backZ -
                        (stiLandingDepth / 2)

                    );

                rightLandingRailing.userData.isBuildingExterior =
                    true;

                rightLandingRailing.userData.isStiEntrance =
                    true;


                // =================================================
                // 8. STI ENTRANCE CANOPY
                //
                // Small protective roof above the second-floor
                // entrance.
                //
                // This is intentionally simple for the blueprint
                // exterior.
                // =================================================

                const stiCanopyWidth =
                    stiEntranceWidth * 0.75;

                const stiCanopyDepth =
                    Math.max(
                        stiLandingDepth * 0.65,
                        0.8
                    );

                const stiCanopyThickness =
                    0.12;


                const stiEntranceCanopy =
                    createExteriorSection(

                        stiCanopyWidth,

                        stiCanopyThickness,

                        stiCanopyDepth,

                        center.x,

                        secondFloorEntranceY +
                        stiDoorHeight +
                        0.25,

                        backZ -
                        (stiCanopyDepth / 2)

                    );

                stiEntranceCanopy.userData.isBuildingExterior =
                    true;

                stiEntranceCanopy.userData.isStiEntrance =
                    true;

            }


            // =====================================================
            // PHASE 8.1 PART 2
            // BUILD EXTERIOR AFTER ALL FLOORS AND ROOMS EXIST
            // =====================================================

            createDynamicBuildingExterior();

            // =====================================================
            // PHASE 7.4
            // GENERATE FLOOR FILTER BUTTONS
            // =====================================================

            const floorFilterButtonContainer =
                document.getElementById(
                    'buildingFloorFilterButtons'
                );

            // =====================================================
            // PHASE 8.2 PART 4
            // BACK TO BUILDING OVERVIEW BUTTON
            // =====================================================

            const backToBuildingOverviewButton =
                document.getElementById(
                    'backToBuildingOverview'
                );

            // =====================================================
            // PHASE 8.2
            // HIDE FLOOR FILTERS WHILE VIEWING EXTERIOR
            // =====================================================

            if (floorFilterButtonContainer) {

                floorFilterButtonContainer.style.display =
                    currentBuildingView === 'exterior'
                        ? 'none'
                        : '';

            }

            if (floorFilterButtonContainer) {

                building3DData.forEach(
                    floorData => {

                        const button =
                            document.createElement(
                                'button'
                            );

                        button.type =
                            'button';

                        button.className =
                            'building-floor-filter';

                        button.dataset.floorFilter =
                            String(floorData.id);

                        button.textContent =
                            floorData.name;

                        floorFilterButtonContainer.appendChild(
                            button
                        );

                    }
                );

            }

            // =====================================================
            // PHASE 7.5
            // AUTO FOCUS CAMERA ON SELECTED FLOOR
            // =====================================================

            // =====================================================
            // PHASE 7.5 + 7.6
            // AUTO FOCUS WITH SMOOTH CAMERA TRANSITION
            // =====================================================

            function focusCameraOnObject(object) {

                if (!object) {
                    return;
                }

                // =================================================
                // GET OBJECT BOUNDS
                // =================================================

                const bounds =
                    new THREE.Box3().setFromObject(object);

                if (bounds.isEmpty()) {
                    return;
                }

                const size =
                    bounds.getSize(
                        new THREE.Vector3()
                    );

                const center =
                    bounds.getCenter(
                        new THREE.Vector3()
                    );


                // =================================================
                // CALCULATE CAMERA DISTANCE
                // =================================================

                const maxDimension =
                    Math.max(
                        size.x,
                        size.y,
                        size.z
                    );

                const distance =
                    Math.max(
                        maxDimension * 1.8,
                        10
                    );


                // =================================================
                // CALCULATE TARGET CAMERA POSITION
                // =================================================

                const targetPosition =
                    new THREE.Vector3(

                        center.x + distance,

                        center.y + distance * 0.75,

                        center.z + distance

                    );


                // =================================================
                // PHASE 7.6
                // START SMOOTH CAMERA TRANSITION
                // =================================================

                cameraTransition = {

                    startPosition:
                        camera.position.clone(),

                    endPosition:
                        targetPosition.clone(),

                    startTarget:
                        controls.target.clone(),

                    endTarget:
                        center.clone(),

                    startTime:
                        performance.now(),

                    duration:
                        800

                };

            }

            // =====================================================
            // PHASE 7.4
            // FLOOR FILTERING
            // =====================================================

            const floorFilterButtons =
                document.querySelectorAll(
                    '.building-floor-filter'
                );

            floorFilterButtons.forEach(
                button => {

                    button.addEventListener(
                        'click',
                        function () {

                            const selectedFloor =
                                this.dataset.floorFilter;


                            // =========================================
                            // UPDATE ACTIVE BUTTON
                            // =========================================

                            floorFilterButtons.forEach(
                                filterButton => {

                                    filterButton.classList.remove(
                                        'active'
                                    );

                                }
                            );

                            this.classList.add(
                                'active'
                            );


                            // =========================================
                            // PHASE 7.10
                            // SHOW / HIDE FLOORS AND ROOM LABELS
                            // =========================================

                            buildingFloorGroups.forEach(
                                (floorGroup, floorId) => {

                                    const isAllFloors =
                                        selectedFloor === 'all';

                                    const isSelectedFloor =
                                        floorId === selectedFloor;


                                    // =====================================
                                    // FLOOR VISIBILITY
                                    // =====================================

                                    floorGroup.visible =
                                        isAllFloors ||
                                        isSelectedFloor;


                                    // =====================================
                                    // ROOM LABEL VISIBILITY
                                    // =====================================

                                    floorGroup.traverse(
                                        child => {

                                            if (
                                                child.userData.type ===
                                                'room-label'
                                            ) {

                                                // Hide labels on All Floors
                                                // Show labels only on selected floor

                                                child.visible =
                                                    !isAllFloors &&
                                                    isSelectedFloor;

                                            }

                                        }
                                    );

                                }
                            );

                            // =========================================
                            // PHASE 7.5
                            // AUTO FOCUS CAMERA
                            // =========================================

                            if (
                                selectedFloor === 'all'
                            ) {

                                // Focus on the complete building
                                focusCameraOnObject(
                                    building
                                );

                            } else {

                                // Get the selected floor group
                                const selectedFloorGroup =
                                    buildingFloorGroups.get(
                                        selectedFloor
                                    );

                                // Focus only on the selected floor
                                if (selectedFloorGroup) {

                                    focusCameraOnObject(
                                        selectedFloorGroup
                                    );

                                }

                            }


                            // =========================================
                            // CLEAR HOVERED ROOM
                            // =========================================

                            if (hoveredRoom) {

                                hoveredRoom = null;

                            }


                            renderer.domElement.style.cursor =
                                'grab';

                        }
                    );

                }
            );


            // =====================================================
            // PHASE 2.3
            // CALCULATE FINAL 3D BUILDING BOUNDS
            // =====================================================

            const buildingBounds =
                new THREE.Box3()
                    .setFromObject(building);


            // =====================================================
            // PHASE 2.3
            // AUTOMATIC CAMERA FIT
            // =====================================================

            if (!buildingBounds.isEmpty()) {

                const buildingSize =
                    buildingBounds.getSize(
                        new THREE.Vector3()
                    );

                const buildingCenter =
                    buildingBounds.getCenter(
                        new THREE.Vector3()
                    );


                // =================================================
                // CAMERA LOOKS AT CENTER OF COMPLETE BUILDING
                // =================================================

                controls.target.copy(
                    buildingCenter
                );


                // =================================================
                // CALCULATE APPROPRIATE CAMERA DISTANCE
                // =================================================

                const maxDimension =
                    Math.max(
                        buildingSize.x,
                        buildingSize.y,
                        buildingSize.z
                    );


                const cameraDistance =
                    Math.max(
                        maxDimension * 2,
                        12
                    );


                // =================================================
                // POSITION CAMERA AT ISOMETRIC ANGLE
                // =================================================

                camera.position.set(

                    buildingCenter.x +
                        cameraDistance,

                    buildingCenter.y +
                        cameraDistance *
                        0.75,

                    buildingCenter.z +
                        cameraDistance

                );


                controls.update();

            }


            // =====================================================
            // PHASE 2.3 COMPLETE
            // EACH FLOOR IS NOW CENTERED AND STACKED
            // =====================================================

           
            // =====================================================


            


            // =====================================================
            // PHASE 2.1 COMPLETE
            // ROOMS ARE NOW GENERATED FROM DATABASE
            // =====================================================

            // =====================================================
            // PHASE 3
            // GET MOUSE POSITION INSIDE THE 3D VIEWER
            // =====================================================

            function getViewerMousePosition(event) {

                const rect =
                    renderer.domElement.getBoundingClientRect();

                mouse.x =
                    (
                        (event.clientX - rect.left) /
                        rect.width
                    ) * 2 - 1;

                mouse.y =
                    -(
                        (
                            event.clientY - rect.top
                        ) /
                        rect.height
                    ) * 2 + 1;
            }

            // =====================================================
            // PHASE 7.4
            // GET ONLY ROOMS FROM VISIBLE FLOORS
            // =====================================================

            function getVisibleClickableRooms() {

                return clickableRooms.filter(
                    room => {

                        // Room must be visible
                        if (!room.visible) {
                            return false;
                        }

                        // Floor group must also be visible
                        if (
                            room.parent &&
                            room.parent.userData.type === 'floor'
                        ) {

                            return room.parent.visible;

                        }

                        return true;

                    }
                );

            }

            // =====================================================
            // PHASE 8.2 PART 2
            // CHECK IF POINTER IS OVER EXTERIOR BUILDING
            // =====================================================

            function getExteriorIntersection(event) {

                if (currentBuildingView !== 'exterior') {
                    return null;
                }

                const rect =
                    renderer.domElement.getBoundingClientRect();

                mouse.x =
                    ((event.clientX - rect.left) / rect.width) * 2 - 1;

                mouse.y =
                    -((event.clientY - rect.top) / rect.height) * 2 + 1;

                raycaster.setFromCamera(
                    mouse,
                    camera
                );

                const intersections =
                    raycaster.intersectObject(
                        exteriorBuilding,
                        true
                    );

                if (intersections.length === 0) {
                    return null;
                }

                return intersections[0];

            }

            // =====================================================
            // PHASE 8.2 PART 3
            // FADE EXTERIOR BUILDING
            // =====================================================

            function fadeExteriorBuilding(
                targetOpacity,
                duration = 500,
                onComplete = null
            ) {

                const materials = [];

                exteriorBuilding.traverse(
                    object => {

                        if (
                            object.material
                        ) {

                            const objectMaterials =
                                Array.isArray(
                                    object.material
                                )
                                    ? object.material
                                    : [object.material];

                            objectMaterials.forEach(
                                material => {

                                    materials.push({

                                        material:
                                            material,

                                        startOpacity:
                                            material.opacity

                                    });

                                }
                            );

                        }

                    }
                );


                const startTime =
                    performance.now();


                function animateFade(
                    currentTime
                ) {

                    const elapsed =
                        currentTime -
                        startTime;

                    const progress =
                        Math.min(
                            elapsed / duration,
                            1
                        );


                    // Smooth easing
                    const eased =
                        progress *
                        progress *
                        (
                            3 -
                            2 * progress
                        );


                    materials.forEach(
                        item => {

                            item.material.opacity =
                                THREE.MathUtils.lerp(
                                    item.startOpacity,
                                    targetOpacity,
                                    eased
                                );

                        }
                    );


                    if (
                        progress < 1
                    ) {

                        requestAnimationFrame(
                            animateFade
                        );

                    } else {

                        if (onComplete) {

                            onComplete();

                        }

                    }

                }


                requestAnimationFrame(
                    animateFade
                );

            }

            // =====================================================
            // PHASE 8.2 PART 3
            // GET CINEMATIC CAMERA POSITION NEAR EXTERIOR
            // =====================================================

            function getExteriorFocusView() {

                const box =
                    new THREE.Box3()
                        .setFromObject(
                            exteriorBuilding
                        );

                const center =
                    box.getCenter(
                        new THREE.Vector3()
                    );

                const size =
                    box.getSize(
                        new THREE.Vector3()
                    );


                const distance =
                    Math.max(
                        size.x,
                        size.y,
                        size.z
                    );


                const position =
                    new THREE.Vector3(

                        center.x +
                            distance * 0.75,

                        center.y +
                            distance * 0.5,

                        center.z +
                            distance * 0.9

                    );


                return {

                    position:
                        position,

                    target:
                        center

                };

            }

            // =====================================================
            // PHASE 8.2 PART 2
            // ENTER INTERIOR BUILDING MODE
            // =====================================================

            // =====================================================
            // PHASE 8.2 PART 3
            // CINEMATICALLY ENTER INTERIOR BUILDING MODE
            // =====================================================

            function enterInteriorMode() {

                if (
                    currentBuildingView ===
                    'interior'
                ) {
                    return;
                }


                if (
                    isBuildingViewTransitioning
                ) {
                    return;
                }


                // =================================================
                // LOCK INTERACTION DURING TRANSITION
                // =================================================

                isBuildingViewTransitioning =
                    true;

                renderer.domElement.style.cursor =
                    'default';


                // =================================================
                // GET CINEMATIC EXTERIOR FOCUS POSITION
                // =================================================

                const exteriorView =
                    getExteriorFocusView();


                // =================================================
                // FIRST CAMERA MOVEMENT
                // MOVE TOWARD EXTERIOR
                // =================================================

                cameraTransition = {

                    startPosition:
                        camera.position.clone(),

                    endPosition:
                        exteriorView.position.clone(),

                    startTarget:
                        controls.target.clone(),

                    endTarget:
                        exteriorView.target.clone(),

                    startTime:
                        performance.now(),

                    duration:
                        700

                };


                // =================================================
                // WAIT FOR CAMERA APPROACH
                // =================================================

                setTimeout(
                    () => {

                        // =============================================
                        // FADE EXTERIOR BUILDING
                        // =============================================

                        fadeExteriorBuilding(
                            0,
                            450,
                            () => {

                                // =====================================
                                // HIDE EXTERIOR
                                // =====================================

                                exteriorBuilding.visible =
                                    false;


                                // =====================================
                                // SHOW INTERIOR
                                // =====================================

                                building.visible =
                                    true;


                                // =====================================
                                // CHANGE VIEW MODE
                                // =====================================

                                currentBuildingView =
                                    'interior';


                                // =====================================
                                // SHOW FLOOR FILTERS
                                // =====================================

                                if (
                                    floorFilterButtonContainer
                                ) {

                                    floorFilterButtonContainer
                                        .style
                                        .display = '';

                                }

                                if (
                                    backToBuildingOverviewButton
                                ) {

                                    backToBuildingOverviewButton
                                        .style
                                        .display = '';

                                }


                                // =====================================
                                // FOCUS CAMERA ON INTERIOR
                                // =====================================

                                focusCameraOnObject(
                                    building
                                );


                                // =====================================
                                // UNLOCK INTERACTION
                                //
                                // Wait for the interior camera
                                // transition to mostly finish.
                                // =====================================

                                setTimeout(
                                    () => {

                                        isBuildingViewTransitioning =
                                            false;

                                        renderer.domElement.style.cursor =
                                            'grab';

                                    },
                                    850
                                );

                            }
                        );

                    },
                    700
                );

            }


            // =====================================================
            // PHASE 8.2 PART 4
            // CINEMATICALLY RETURN TO EXTERIOR BUILDING MODE
            // =====================================================

            function returnToExteriorMode() {

                // Do nothing if already outside
                if (
                    currentBuildingView ===
                    'exterior'
                ) {
                    return;
                }


                // Prevent multiple transitions
                if (
                    isBuildingViewTransitioning
                ) {
                    return;
                }


                // =================================================
                // LOCK INTERACTION
                // =================================================

                isBuildingViewTransitioning =
                    true;

                renderer.domElement.style.cursor =
                    'default';


                // =================================================
                // CLEAR ROOM HOVER AND SELECTION
                // =================================================

                if (hoveredRoom) {

                    if (
                        hoveredRoom !==
                        selectedRoom
                    ) {

                        restoreRoomVisual(
                            hoveredRoom
                        );

                    }

                    hoveredRoom = null;

                }


                if (selectedRoom) {

                    restoreRoomVisual(
                        selectedRoom
                    );

                    selectedRoom = null;

                }


                hideRoomTooltip();

                roomDetailsPanel?.classList.remove(
                    'visible'
                );


                // =================================================
                // HIDE INTERIOR CONTROLS
                // =================================================

                if (
                    floorFilterButtonContainer
                ) {

                    floorFilterButtonContainer
                        .style
                        .display = 'none';

                }


                if (
                    backToBuildingOverviewButton
                ) {

                    backToBuildingOverviewButton
                        .style
                        .display = 'none';

                }


                // =================================================
                // GET EXTERIOR CAMERA VIEW
                // =================================================

                const exteriorView =
                    getExteriorFocusView();


                // =================================================
                // MOVE CAMERA TOWARD EXTERIOR VIEW
                // =================================================

                cameraTransition = {

                    startPosition:
                        camera.position.clone(),

                    endPosition:
                        exteriorView.position.clone(),

                    startTarget:
                        controls.target.clone(),

                    endTarget:
                        exteriorView.target.clone(),

                    startTime:
                        performance.now(),

                    duration:
                        800

                };


                // =================================================
                // WAIT FOR CAMERA MOVEMENT
                // =================================================

                setTimeout(
                    () => {

                        // =========================================
                        // HIDE INTERIOR
                        // =========================================

                        building.visible =
                            false;


                        // =========================================
                        // SHOW EXTERIOR
                        // =========================================

                        exteriorBuilding.visible =
                            true;


                        // =========================================
                        // CHANGE VIEW MODE
                        // IMPORTANT:
                        // getExteriorIntersection() only works
                        // when currentBuildingView is "exterior"
                        // =========================================

                        currentBuildingView =
                            'exterior';


                        // =========================================
                        // RESTORE EXTERIOR MATERIAL OPACITY
                        // Phase 8.2 Part 3 faded everything to 0
                        // =========================================

                        exteriorBuilding.traverse(
                            object => {

                                if (!object.material) {
                                    return;
                                }

                                const materials =
                                    Array.isArray(
                                        object.material
                                    )
                                        ? object.material
                                        : [object.material];


                                materials.forEach(
                                    material => {

                                        // Exterior mesh material
                                        if (
                                            material.isMeshPhysicalMaterial
                                        ) {

                                            material.opacity =
                                                0.18;

                                        }

                                        // Exterior cyan wireframe
                                        if (
                                            material.isLineBasicMaterial
                                        ) {

                                            material.opacity =
                                                0.85;

                                        }

                                    }
                                );

                            }
                        );


                        // =========================================
                        // UNLOCK INTERACTION
                        // =========================================

                        isBuildingViewTransitioning =
                            false;

                        renderer.domElement.style.cursor =
                            'grab';

                    },
                    850
                );

            }

            // =====================================================
            // PHASE 8.2 PART 4
            // BACK TO BUILDING OVERVIEW BUTTON CLICK
            // =====================================================

            backToBuildingOverviewButton
                ?.addEventListener(
                    'click',
                    () => {

                        returnToExteriorMode();

                    }
                );


            // =====================================================
            // PHASE 3
            // ROOM HOVER
            // =====================================================

            renderer.domElement.addEventListener(
                'pointermove',
                function (event) {

                    // =====================================================
                    // PHASE 8.2 PART 3
                    // IGNORE HOVER DURING CINEMATIC TRANSITION
                    // =====================================================

                    if (
                        isBuildingViewTransitioning
                    ) {

                        renderer.domElement.style.cursor =
                            'default';

                        return;

                    }

                    

                    // =====================================================
                    // PHASE 8.2 PART 2
                    // EXTERIOR BUILDING HOVER
                    // =====================================================

                    if (currentBuildingView === 'exterior') {

                        const exteriorHit =
                            getExteriorIntersection(event);

                        renderer.domElement.style.cursor =
                            exteriorHit
                                ? 'pointer'
                                : 'grab';

                        // Stop here while viewing the exterior.
                        // The room hover code below should only run
                        // when we are inside the building.
                        return;
                    }


                    // =====================================================
                    // EXISTING ROOM HOVER CODE
                    // =====================================================

                    getViewerMousePosition(event);

                    raycaster.setFromCamera(
                        mouse,
                        camera
                    );

                    // =================================================
                    // PHASE 7.4
                    // ONLY RAYCAST VISIBLE FLOOR ROOMS
                    // =================================================

                    const intersections =
                        raycaster.intersectObjects(
                            getVisibleClickableRooms(),
                            false
                        );


                    // ==============================================
                    // RESET PREVIOUS HOVER
                    // ==============================================

                    if (
                        hoveredRoom &&
                        hoveredRoom !== selectedRoom
                    ) {

                        restoreRoomVisual(
                            hoveredRoom
                        );

                    }


                    // ==============================================
                    // ROOM IS BEING HOVERED
                    // ==============================================

                    if (intersections.length > 0) {

                        hoveredRoom =
                            intersections[0].object;

                        // =================================================
                        // PHASE 7.7
                        // SHOW ROOM TOOLTIP
                        // =================================================

                        updateRoomTooltip(
                            hoveredRoom,
                            event
                        );

                        renderer.domElement.style.cursor =
                            'pointer';


                        // ==============================================
                        // PHASE 7.9
                        // APPLY HOVER VISUAL
                        // ==============================================

                        if (
                            hoveredRoom !== selectedRoom
                        ) {

                            applyRoomHoverVisual(
                                hoveredRoom
                            );

                        }

                    } else {

                        hoveredRoom = null;

                        renderer.domElement.style.cursor =
                            'grab';

                        hideRoomTooltip();

                    }

                }
            );

            // =====================================================
            // PHASE 7.7
            // HIDE TOOLTIP WHEN POINTER LEAVES 3D VIEW
            // =====================================================

            renderer.domElement.addEventListener(
                'pointerleave',
                () => {

                    hideRoomTooltip();

                }
            );


            // =====================================================
            // PHASE 3
            // ROOM CLICK SELECTION
            // =====================================================

            renderer.domElement.addEventListener(
                'click',
                function (event) {

                    // =====================================================
                    // PHASE 8.2 PART 2
                    // EXTERIOR BUILDING CLICK
                    // =====================================================

                    if (currentBuildingView === 'exterior') {

                        const exteriorHit =
                            getExteriorIntersection(event);

                        if (exteriorHit) {

                            enterInteriorMode();

                        }

                        // Prevent room selection from running
                        // while in exterior mode.
                        return;
                    }

                    getViewerMousePosition(event);

                    raycaster.setFromCamera(
                        mouse,
                        camera
                    );

                    // =================================================
                    // PHASE 7.4
                    // ONLY CLICK ROOMS ON VISIBLE FLOORS
                    // =================================================

                    const intersections =
                        raycaster.intersectObjects(
                            getVisibleClickableRooms(),
                            false
                        );


                    if (
                        intersections.length === 0
                    ) {

                        if (selectedRoom) {

                            restoreRoomVisual(
                                selectedRoom
                            );

                            selectedRoom = null;

                        }

                        roomDetailsPanel?.classList.remove(
                            'visible'
                        );

                        return;

                    }


                    const roomMesh =
                        intersections[0].object;


                    // ==============================================
                    // PHASE 7.9
                    // RESTORE PREVIOUS SELECTED ROOM
                    // ==============================================

                    if (
                        selectedRoom &&
                        selectedRoom !== roomMesh
                    ) {

                        restoreRoomVisual(
                            selectedRoom
                        );

                    }


                    // ==============================================
                    // SELECT NEW ROOM
                    // ==============================================

                    selectedRoom =
                        roomMesh;

                    applyRoomSelectedVisual(
                        selectedRoom
                    );

                    // =================================================
                // SAVE CURRENT CAMERA VIEW BEFORE ROOM FOCUS
                // =================================================

                cameraPositionBeforeRoomSelection =
                    camera.position.clone();

                cameraTargetBeforeRoomSelection =
                    controls.target.clone();

                    focusCameraOnObject(
                        selectedRoom
                    );


                    // ==============================================
                    // TEST REAL DATABASE ROOM INFORMATION
                    // ==============================================

                    console.log(
                        'Selected 3D Room:',
                        selectedRoom.userData
                    );


                    // =====================================================
                    // PHASE 7.8
                    // OPEN ROOM DETAILS PANEL
                    // =====================================================

                    openRoomDetailsPanel(
                        selectedRoom.userData
                    );

                }
            );

            // =====================================================
            // PHASE 7.8
            // CLOSE ROOM DETAILS PANEL
            // =====================================================

            roomDetailsClose?.addEventListener(
                'click',
                () => {

                    roomDetailsPanel?.classList.remove(
                        'visible'
                    );

                    // =================================================
                    // PHASE 7.9
                    // CLEAR SELECTED ROOM VISUAL
                    // =================================================

                    if (selectedRoom) {

                        restoreRoomVisual(
                            selectedRoom
                        );

                        selectedRoom = null;

                    }

                    // =================================================
                    // PHASE 7.9
                    // SMOOTHLY RETURN TO PREVIOUS CAMERA VIEW
                    // =================================================

                    if (
                        cameraPositionBeforeRoomSelection &&
                        cameraTargetBeforeRoomSelection
                    ) {

                        cameraTransition = {

                            startPosition:
                                camera.position.clone(),

                            endPosition:
                                cameraPositionBeforeRoomSelection.clone(),

                            startTarget:
                                controls.target.clone(),

                            endTarget:
                                cameraTargetBeforeRoomSelection.clone(),

                            startTime:
                                performance.now(),

                            duration:
                                800

                        };


                        // Clear saved camera state
                        cameraPositionBeforeRoomSelection = null;
                        cameraTargetBeforeRoomSelection = null;

                    }

                }
            );


            // =====================================================
            // PHASE 7.8
            // VIEW FULL ROOM DETAILS
            // =====================================================

            roomDetailsView?.addEventListener(
                'click',
                () => {

                    const roomId =
                        roomDetailsView.dataset.roomId;

                    if (!roomId) {
                        return;
                    }

                    window.location.href =
                        `/maintenance/infrastructure?room=${encodeURIComponent(roomId)}`;

                }
            );


            // =====================================================
            // PHASE 3
            // TEMPORARY ROOM INSPECTOR CONNECTION
            // =====================================================

            function open3DRoomInspector(room) {

                // ==============================================
                // MAKE SURE THE ROOM HAS A DATABASE ID
                // ==============================================

                if (!room || !room.roomId) {

                    console.error(
                        'Selected 3D room does not have a valid room ID:',
                        room
                    );

                    return;
                }

                // ==============================================
                // CREATE INFRASTRUCTURE MONITOR URL
                //
                // Example:
                // /maintenance/infrastructure?room=11
                // ==============================================

                const monitorUrl =
                    `/maintenance/infrastructure?room=${encodeURIComponent(room.roomId)}`;

                // ==============================================
                // OPEN INFRASTRUCTURE MONITOR
                // ==============================================

                window.location.href = monitorUrl;
            }

            // =====================================================
            // GRID
            // =====================================================

            


            // =====================================================
            // ZOOM BUTTONS
            // =====================================================

            document
                .getElementById('buildingZoomIn')
                ?.addEventListener(
                    'click',
                    () => {

                        camera.position.multiplyScalar(
                            0.85
                        );

                        controls.update();

                    }
                );


            document
                .getElementById('buildingZoomOut')
                ?.addEventListener(
                    'click',
                    () => {

                        camera.position.multiplyScalar(
                            1.15
                        );

                        controls.update();

                    }
                );


            // =====================================================
            // RESET BUTTON
            // =====================================================

            document
                .getElementById('buildingReset')
                ?.addEventListener(
                    'click',
                    () => {

                        // =================================================
                        // PHASE 7.9
                        // CLEAR ROOM SELECTION
                        // =================================================

                        if (selectedRoom) {

                            restoreRoomVisual(
                                selectedRoom
                            );

                            selectedRoom = null;

                        }

                        hoveredRoom = null;

                        hideRoomTooltip();

                        roomDetailsPanel?.classList.remove(
                            'visible'
                        );

                        cameraTransition = null;

                        camera.position.set(
                            18,
                            14,
                            20
                        );

                        controls.target.set(
                            0,
                            2.5,
                            0
                        );

                        controls.update();

                    }
                );


            // =====================================================
            // RESPONSIVE RESIZE
            // =====================================================

            const resizeObserver =
                new ResizeObserver(() => {

                    const width =
                        container.clientWidth;

                    const height =
                        container.clientHeight;


                    if (
                        width === 0 ||
                        height === 0
                    ) {
                        return;
                    }


                    camera.aspect =
                        width / height;

                    camera.updateProjectionMatrix();


                    renderer.setSize(
                        width,
                        height,
                        false
                    );

                });


            resizeObserver.observe(
                container
            );


            // =====================================================
            // ANIMATION
            // =====================================================

            function animate() {

                requestAnimationFrame(
                    animate
                );


                // =================================================
                // PHASE 7.6
                // SMOOTH CAMERA TRANSITION
                // =================================================

                if (cameraTransition) {

                    const elapsed =
                        performance.now() -
                        cameraTransition.startTime;

                    let progress =
                        elapsed /
                        cameraTransition.duration;

                    progress =
                        Math.min(
                            progress,
                            1
                        );


                    // =================================================
                    // SMOOTH EASE IN AND EASE OUT
                    // =================================================

                    const easedProgress =
                        progress < 0.5
                            ? 2 * progress * progress
                            : 1 -
                                Math.pow(
                                    -2 * progress + 2,
                                    2
                                ) / 2;


                    // =================================================
                    // MOVE CAMERA SMOOTHLY
                    // =================================================

                    camera.position.lerpVectors(
                        cameraTransition.startPosition,
                        cameraTransition.endPosition,
                        easedProgress
                    );


                    // =================================================
                    // MOVE CAMERA TARGET SMOOTHLY
                    // =================================================

                    controls.target.lerpVectors(
                        cameraTransition.startTarget,
                        cameraTransition.endTarget,
                        easedProgress
                    );


                    // =================================================
                    // END TRANSITION
                    // =================================================

                    if (progress >= 1) {

                        cameraTransition = null;

                    }

                }


                controls.update();

                composer.render();

            }


            animate();


            console.log(
                'Three.js building viewer started successfully.'
            );

        }

    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            createRibbon();
        });

        function createRibbon() {
            const glow = document.getElementById("glowLayer");
            const outer = document.getElementById("outerRibbon");
            const middle = document.getElementById("middleRibbon");
            const ribbon = document.getElementById("mainRibbon");
            const highlight = document.getElementById("highlightRibbon");
            const bloom = document.getElementById("softBloom");

            if (!glow || !outer || !middle || !ribbon || !highlight || !bloom) {
                return;
            }

            const total = {{
            max(
                1,
                $urgentReports + $underMaintenance + $borrowedEquipment,
            )
        }};

            const urgent = {{ $urgentReports }} / total;
            const maintenance = {{ $underMaintenance }} / total;
            const borrowed = {{ $borrowedEquipment }} / total;

            const WIDTH = 1000;
            const CENTER = 110;

            function smoothstep(x) {
                return x * x * (3 - 2 * x);
            }

            function build(offset, scale = 1) {
                let path = "";

                // ==========================
                // TOP EDGE
                // ==========================

                for (let x = 0; x <= WIDTH; x += 8) {
                    const t = x / WIDTH;

                    const leftFade = Math.sin(
                        ((Math.min(t, 0.12) / 0.12) * Math.PI) / 2,
                    );

                    const rightFade = Math.sin(
                        ((Math.min(1 - t, 0.12) / 0.12) * Math.PI) / 2,
                    );

                    const taper = Math.min(leftFade, rightFade);

                    const wave =
                        Math.sin(t * Math.PI * 2 + offset) * 5 +
                        Math.sin(t * Math.PI * 6 - offset * 1.2) * 2.5 +
                        Math.cos(t * Math.PI * 10) * 1.2;

                    const pulse = Math.sin(offset + t * 6) * 4;

                    const SCALE = 160;
                    const MIN = 3;

                    const leftWidth = urgent > 0 ? MIN + urgent * SCALE : 0;
                    const middleWidth = maintenance > 0 ? MIN + maintenance * SCALE : 0;
                    const rightWidth = borrowed > 0 ? MIN + borrowed * SCALE : 0;

                    const hump1 = leftWidth * Math.exp(-Math.pow((t - 0.12) / 0.12, 2));

                    const hump2 =
                        middleWidth * Math.exp(-Math.pow((t - 0.5) / 0.18, 2));

                    const hump3 =
                        rightWidth * Math.exp(-Math.pow((t - 0.88) / 0.12, 2));

                    const baseWidth = hump1 + hump2 + hump3;

                    const breathing = 1 + Math.sin(offset * 2) * 0.04;

                    const MIN_WIDTH = 0;

                    const width = Math.max(baseWidth, 0) * taper * breathing * scale;

                    const y = CENTER + wave + pulse;

                    if (x === 0) {
                        path = `M -30 ${CENTER}`;
                        path += ` L ${x} ${y - width}`;
                    } else {
                        path += ` L ${x} ${y - width}`;
                    }
                }

                // ==========================
                // BOTTOM EDGE
                // ==========================

                for (let x = WIDTH; x >= 0; x -= 8) {
                    const t = x / WIDTH;

                    const leftFade = Math.sin(
                        ((Math.min(t, 0.12) / 0.12) * Math.PI) / 2,
                    );

                    const rightFade = Math.sin(
                        ((Math.min(1 - t, 0.12) / 0.12) * Math.PI) / 2,
                    );

                    const taper = Math.min(leftFade, rightFade);

                    const wave =
                        Math.sin(t * Math.PI * 2 + offset) * 5 +
                        Math.sin(t * Math.PI * 6 - offset * 1.2) * 2.5 +
                        Math.cos(t * Math.PI * 10) * 1.2;

                    const pulse = Math.sin(offset + t * 6) * 4;

                    const SCALE = 160;
                    const MIN = 3;

                    const leftWidth = urgent > 0 ? MIN + urgent * SCALE : 0;
                    const middleWidth = maintenance > 0 ? MIN + maintenance * SCALE : 0;
                    const rightWidth = borrowed > 0 ? MIN + borrowed * SCALE : 0;

                    const hump1 = leftWidth * Math.exp(-Math.pow((t - 0.12) / 0.12, 2));

                    const hump2 =
                        middleWidth * Math.exp(-Math.pow((t - 0.5) / 0.18, 2));

                    const hump3 =
                        rightWidth * Math.exp(-Math.pow((t - 0.88) / 0.12, 2));

                    const baseWidth = hump1 + hump2 + hump3;

                    const breathing = 1 + Math.sin(offset * 2) * 0.04;

                    const MIN_WIDTH = 0;

                    const width = Math.max(baseWidth, 0) * taper * breathing * scale;

                    const y = CENTER + wave + pulse;

                    path += ` L ${x} ${y + width}`;
                }

                path += ` L ${WIDTH + 30} ${CENTER} Z`;

                return path;
            }

            let time = 0;

            function animate() {
                time += 0.02;

                glow.setAttribute("d", build(time - 0.22, 1.7));
                outer.setAttribute("d", build(time - 0.12, 1.45));
                middle.setAttribute("d", build(time - 0.05, 1.2));
                ribbon.setAttribute("d", build(time, 1.0));
                highlight.setAttribute("d", build(time + 0.04, 0.55));
                bloom.setAttribute("d", build(time + 0.02, 1.55));

                requestAnimationFrame(animate);
            }

            animate();
        }
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

                            pointBackgroundColor: "#ffffff",

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

                            backgroundColor: "#ffffff",

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
            // CONCENTRIC BUBBLE CHART
            // REPLACE YOUR CURRENT EQUIPMENT CONDITION CHART JS
            // =====================================================

            const equipmentConditionCanvas = document.getElementById(
                "equipmentConditionChart",
            );

            if (equipmentConditionCanvas) {
                // =====================================================
                // GET DATA FROM YOUR EXISTING CONTROLLER
                // =====================================================

                const equipmentConditionLabels = @json ($equipmentConditionChart["labels"]);

                const equipmentConditionData = @json ($equipmentConditionChart["data"]);

                // =====================================================
                // COMBINE LABELS AND VALUES
                // =====================================================

                const equipmentConditionItems = equipmentConditionLabels.map(
                    (label, index) => ({
                        label: label,

                        value: Number(equipmentConditionData[index]) || 0,
                    }),
                );

                // =====================================================
                // SORT FROM LARGEST TO SMALLEST
                //
                // IMPORTANT:
                // LARGEST CIRCLE MUST BE DRAWN FIRST
                // =====================================================

                equipmentConditionItems.sort((a, b) => b.value - a.value);

                // =====================================================
                // FIND LARGEST VALUE
                // PREVENT DIVISION BY ZERO
                // =====================================================

                // =====================================================
                // BLUE COLORS
                // LARGEST CIRCLE = LIGHTEST
                // SMALLEST CIRCLE = DARKEST
                // =====================================================

                const equipmentConditionColors = [
                    "rgba(79, 70, 229, 0.10)",

                    "rgba(79, 70, 229, 0.20)",

                    "rgba(79, 70, 229, 0.38)",

                    "rgba(79, 70, 229, 0.82)",
                ];

                // =====================================================
                // CUSTOM PLUGIN
                // REFERENCE STYLE CONCENTRIC CIRCLES
                // =====================================================

                const equipmentConditionBubblePlugin = {
                    id: "equipmentConditionBubblePlugin",

                    beforeDatasetsDraw(chart) {
                        const {
                            ctx,

                            chartArea: {
                                left,

                                bottom,

                                width,

                                height,
                            },
                        } = chart;

                        // =====================================================
                        // HORIZONTAL CENTER
                        // =====================================================

                        const centerX = left + width / 2;

                        // =====================================================
                        // ALL CIRCLES SHARE THE SAME BOTTOM POINT
                        // =====================================================

                        const sharedBottom = bottom - 2;

                        // =====================================================
                        // FIXED SIZE RATIOS
                        //
                        // IMPORTANT:
                        // DO NOT USE RAW EQUIPMENT COUNT FOR RADIUS.
                        //
                        // THESE RATIOS COPY THE REFERENCE DESIGN MORE CLOSELY.
                        // =====================================================

                        const radiusRatios = [
                            1,

                            0.76,

                            0.55,

                            0.36,
                        ];

                        // =====================================================
                        // MAXIMUM OUTER CIRCLE SIZE
                        //
                        // CHANGE ONLY THESE VALUES IF YOU WANT TO
                        // ADJUST THE WHOLE CHART SIZE.
                        // =====================================================

                        const maximumRadius = Math.min(
                            width * 0.32,

                            height * 0.43,
                        );

                        // =====================================================
                        // BUILD CIRCLE INFORMATION
                        // =====================================================

                        const circles = equipmentConditionItems.map((item, index) => {
                            // =================================================
                            // GET SIZE RATIO
                            // =================================================

                            const ratio =
                                radiusRatios[index] ??
                                Math.max(
                                    0.2,

                                    0.36 - (index - 3) * 0.08,
                                );

                            // =================================================
                            // CALCULATE RADIUS
                            // =================================================

                            const radius = maximumRadius * ratio;

                            // =================================================
                            // SAME BOTTOM POINT
                            //
                            // CENTER Y CHANGES BASED ON RADIUS.
                            // =================================================

                            const centerY = sharedBottom - radius;

                            return {
                                ...item,

                                radius,

                                centerY,
                            };
                        });

                        ctx.save();

                        // =====================================================
                        // DRAW ALL CIRCLES FIRST
                        //
                        // LARGEST TO SMALLEST
                        // =====================================================

                        circles.forEach((circle, index) => {
                            ctx.beginPath();

                            ctx.arc(
                                centerX,

                                circle.centerY,

                                circle.radius,

                                0,

                                Math.PI * 2,
                            );

                            ctx.fillStyle =
                                equipmentConditionColors[
                                    Math.min(
                                        index,

                                        equipmentConditionColors.length - 1,
                                    )
                                ];

                            ctx.fill();
                        });

                        // =====================================================
                        // DRAW CURVED TEXT HELPER
                        //
                        // LONG LABELS FOLLOW THE TOP ARC OF THEIR CIRCLE.
                        // EG: UNDER MAINTENANCE
                        // =====================================================

                        function drawCurvedText(
                            ctx,

                            text,

                            centerX,

                            centerY,

                            radius,

                            centerAngle,
                        ) {
                            // =====================================================
                            // CONVERT TEXT INTO CHARACTERS
                            // =====================================================

                            const characters = [...text];

                            // =====================================================
                            // CALCULATE TOTAL TEXT WIDTH
                            // =====================================================

                            const totalTextWidth = characters.reduce(
                                (total, character) =>
                                    total + ctx.measureText(character).width,

                                0,
                            );

                            // =====================================================
                            // CONVERT TEXT WIDTH INTO ARC ANGLE
                            // =====================================================

                            const totalAngle = totalTextWidth / radius;

                            // =====================================================
                            // START ANGLE
                            //
                            // THIS CENTERS THE WHOLE TEXT ON THE TOP ARC.
                            // =====================================================

                            let currentAngle = centerAngle - totalAngle / 2;

                            // =====================================================
                            // DRAW EACH CHARACTER
                            // =====================================================

                            characters.forEach((character) => {
                                const characterWidth = ctx.measureText(character).width;

                                const characterAngle = characterWidth / radius;

                                currentAngle += characterAngle / 2;

                                ctx.save();

                                // =================================================
                                // MOVE TO CHARACTER POSITION
                                // =================================================

                                ctx.translate(
                                    centerX + Math.cos(currentAngle) * radius,

                                    centerY + Math.sin(currentAngle) * radius,
                                );

                                // =================================================
                                // ROTATE CHARACTER ALONG CIRCLE
                                // =================================================

                                ctx.rotate(currentAngle + Math.PI / 2);

                                // =================================================
                                // DRAW CHARACTER
                                // =================================================

                                ctx.fillText(
                                    character,

                                    0,

                                    0,
                                );

                                ctx.restore();

                                currentAngle += characterAngle / 2;
                            });
                        }

                        // =====================================================
                        // DRAW LABELS AFTER ALL CIRCLES
                        //
                        // DESIGN RULES:
                        //
                        // LONG LABELS = CURVED
                        // SHORT LABELS = HORIZONTAL
                        //
                        // DARKEST SMALLEST CIRCLE = WHITE TEXT
                        // OTHER CIRCLES = DARK TEXT
                        // =====================================================

                        circles.forEach((circle, index) => {
                            // =================================================
                            // CHECK IF THIS IS THE SMALLEST CIRCLE
                            //
                            // THE LAST CIRCLE IS THE DARKEST CIRCLE.
                            // =================================================

                            const isSmallestCircle = index === circles.length - 1;

                            // =================================================
                            // TEXT COLOR
                            //
                            // SMALLEST DARK CIRCLE = WHITE
                            // OTHER CIRCLES = DARK GRAY
                            // =================================================

                            const textColor = isSmallestCircle ? "#BBC9FC" : "#012274";

                            // =================================================
                            // TOP OF CURRENT CIRCLE
                            // =================================================

                            const currentTop = circle.centerY - circle.radius;

                            let labelY;

                            // =================================================
                            // CALCULATE LABEL POSITION
                            // =================================================

                            if (index < circles.length - 1) {
                                const innerCircle = circles[index + 1];

                                const innerTop =
                                    innerCircle.centerY - innerCircle.radius;

                                labelY = currentTop + (innerTop - currentTop) / 2;
                            } else {
                                labelY = circle.centerY;
                            }

                            // =================================================
                            // COMMON TEXT SETTINGS
                            // =================================================

                            ctx.fillStyle = textColor;

                            ctx.textAlign = "center";

                            ctx.textBaseline = "middle";

                            ctx.font = '600 11px "Inter", sans-serif';

                            // =================================================
                            // CURVE ONLY LONG LABELS
                            //
                            // EG:
                            //
                            // UNDER MAINTENANCE = CURVED
                            //
                            // GOOD = NORMAL
                            // DAMAGED = NORMAL
                            // DISPOSED = NORMAL
                            // =================================================

                            const shouldCurveLabel = circle.label.length > 12;

                            if (shouldCurveLabel) {
                                // =================================================
                                // CURVED LABEL RADIUS
                                // =================================================

                                const curvedTextRadius = circle.radius * 0.82;

                                // =================================================
                                // DRAW CURVED LABEL
                                // =================================================

                                drawCurvedText(
                                    ctx,

                                    circle.label,

                                    centerX,

                                    circle.centerY,

                                    curvedTextRadius,

                                    -Math.PI / 2,
                                );

                                // =================================================
                                // DRAW COUNT
                                //
                                // KEEP COUNT HORIZONTAL BELOW THE CURVED LABEL
                                // =================================================

                                ctx.fillStyle = textColor;

                                ctx.font = '700 14px "Outfit", sans-serif';

                                const curvedCountY =
                                    circle.centerY - curvedTextRadius + 18;

                                ctx.fillText(
                                    circle.value,

                                    centerX,

                                    curvedCountY,
                                );
                            } else {
                                // =================================================
                                // SHORT LABEL
                                // =================================================

                                ctx.fillStyle = textColor;

                                ctx.font = '600 11px "Inter", sans-serif';

                                ctx.fillText(
                                    circle.label,

                                    centerX,

                                    labelY - 8,
                                );

                                // =================================================
                                // EQUIPMENT COUNT
                                // =================================================

                                ctx.fillStyle = textColor;

                                ctx.font = '700 14px "Outfit", sans-serif';

                                ctx.fillText(
                                    circle.value,

                                    centerX,

                                    labelY + 10,
                                );
                            }
                        });

                        ctx.restore();
                    },
                };

                // =====================================================
                // CREATE CHART
                // =====================================================

                new Chart(
                    equipmentConditionCanvas,

                    {
                        // =================================================
                        // EMPTY SCATTER CHART
                        //
                        // THE CUSTOM PLUGIN DRAWS THE ACTUAL VISUALIZATION
                        // =================================================

                        type: "scatter",

                        data: {
                            datasets: [],
                        },

                        plugins: [equipmentConditionBubblePlugin],

                        options: {
                            responsive: true,

                            maintainAspectRatio: false,

                            // =================================================
                            // REMOVE DEFAULT CHART PADDING
                            // =================================================

                            layout: {
                                padding: {
                                    top: 4,

                                    right: 10,

                                    bottom: 4,

                                    left: 10,
                                },
                            },

                            // =================================================
                            // DISABLE DEFAULT INTERACTION
                            // =================================================

                            interaction: {
                                mode: null,
                            },

                            // =================================================
                            // HIDE DEFAULT CHART ELEMENTS
                            // =================================================

                            plugins: {
                                legend: {
                                    display: false,
                                },

                                tooltip: {
                                    enabled: false,
                                },
                            },

                            // =================================================
                            // HIDE X AND Y AXES
                            // =================================================

                            scales: {
                                x: {
                                    display: false,
                                },

                                y: {
                                    display: false,
                                },
                            },
                        },
                    },
                );
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
                            "rgba(114, 180, 220, 0.45)", // Raised from 0.14
                        );

                        // 2. Keep the color solid as it starts to drop
                        gradient.addColorStop(
                            0.35,
                            "rgba(114, 180, 220, 0.22)", // Raised from 0.08
                        );

                        // 3. A soft, gradual fade out towards the bottom
                        gradient.addColorStop(
                            0.7,
                            "rgba(114, 180, 220, 0.08)", // Raised from 0.025
                        );

                        // 4. Completely transparent at the very bottom baseline
                        gradient.addColorStop(1, "rgba(114, 180, 220, 0)");

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

                                    borderColor: "#72b4dc",

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

                                    pointHoverBackgroundColor: "#72b4dc",

                                    pointHoverBorderColor: "#ffffff",

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

                                    pointHoverBorderColor: "#ffffff",

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

                                    titleColor: "#ffffff",

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
                return index === highestIndex ? "#6d5ce7" : "#ddd6fe";
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

@endsection
