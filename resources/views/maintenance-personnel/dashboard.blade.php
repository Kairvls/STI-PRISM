@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Dashboard')

@section('content')

{{-- ══════════════════════════════════════════════════════════════
     PRISM · Maintenance Personnel Dashboard
     resources/views/maintenance/dashboard.blade.php
══════════════════════════════════════════════════════════════ --}}



    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            margin: 0;
            overflow-x: hidden;
        }

        

        /* ── MAIN LAYOUT ─────────────────────────────────────── */
        

        

        /* ── STAT CARD ───────────────────────────────────────── */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover {
            box-shadow: 0 8px 24px rgba(0,55,199,0.1);
            transform: translateY(-2px);
        }

        /* ── ACTION BUTTON ───────────────────────────────────── */
        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 13.5px;
            cursor: pointer;
            transition: all .2s ease;
            border: none;
            font-family: 'Outfit', sans-serif;
            flex: 1;
            justify-content: center;
            white-space: nowrap;
        }
        .action-btn:hover { transform: translateY(-2px); }

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
            clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%, 10px 50%);
            background: #e2e8f0;
            color: #94a3b8;
            letter-spacing: .03em;
            min-width: 110px;
            transition: all .2s;
            position: relative;
            cursor: default;
        }
        .pipeline-step:first-child {
            clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%);
            padding-left: 14px;
        }
        .pipeline-step.done {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .pipeline-step.active {
            background: #0037C7;
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
            transition: all .2s ease;
            position: relative;
            overflow: hidden;
        }
        .room-card:hover {
            border-color: #0037C7;
            box-shadow: 0 4px 16px rgba(0,55,199,0.12);
            transform: translateY(-2px);
        }
        .room-card.available::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: #22c55e;
            border-radius: 4px 0 0 4px;
        }
        .room-card.needs-repair::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: #f59e0b;
            border-radius: 4px 0 0 4px;
        }
        .room-card.critical::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
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
            transition: all .18s ease;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #64748b;
        }
        .floor-tab.active {
            background: #0037C7;
            border-color: #0037C7;
            color: #fff;
        }

        /* ── URGENT REPORT CARD ──────────────────────────────── */
        .urgent-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 20px 22px;
            min-width: 360px;
            flex-shrink: 0;
            transition: box-shadow .2s;
        }
        .urgent-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); }

        /* ── SCROLL HIDE ─────────────────────────────────────── */
        .scroll-hide::-webkit-scrollbar { display: none; }
        .scroll-hide { -ms-overflow-style: none; scrollbar-width: none; }

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
            transition: max-height .25s ease;
        }
        .accordion-content.open { max-height: 300px; }
        .chevron-icon { transition: transform .25s ease; }
        .chevron-icon.rotated { transform: rotate(180deg); }

    </style>






<!-- ═══════════════════════════════════════════════════ MAIN -->

<div class="p-4 sm:p-6 lg:p-8">
    

    <!-- ─── PAGE BODY ─── -->
    <div class="p-6 md:p-8 flex flex-col gap-7">

        <!-- ══ GREETING + ACTIONS ══ -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">

            <div>
                <div style="font-size:.75rem; color:#94a3b8; font-weight:500; letter-spacing:.06em; text-transform:uppercase; margin-bottom:4px;">
                    Welcome back
                </div>
                <h1 style="font-family:'Outfit',sans-serif; font-weight:800; font-size:1.75rem; color:#0f172a; line-height:1.1;">
                    Kenn Mehares
                </h1>
                <p style="color:#64748b; font-size:.85rem; margin-top:3px;">Maintenance Personnel Dashboard</p>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex flex-wrap gap-3">

                <button class="action-btn"
                    style="background:#0037C7; color:#fff; min-width:130px;"
                    onmouseover="this.style.boxShadow='0 8px 20px rgba(0,55,199,0.35)'"
                    onmouseout="this.style.boxShadow='none'">
                    <i data-lucide="scan-line" class="w-4 h-4"></i>
                    Scan QR
                </button>

                <button class="action-btn"
                    style="background:#16a34a; color:#fff; min-width:130px;"
                    onmouseover="this.style.boxShadow='0 8px 20px rgba(22,163,74,0.35)'"
                    onmouseout="this.style.boxShadow='none'">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Add Findings
                </button>

                <button class="action-btn"
                    style="background:#dc2626; color:#fff; min-width:130px;"
                    onmouseover="this.style.boxShadow='0 8px 20px rgba(220,38,38,0.35)'"
                    onmouseout="this.style.boxShadow='none'">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    Urgent Reports
                </button>

                <button class="action-btn"
                    style="background:#d97706; color:#fff; min-width:130px;"
                    onmouseover="this.style.boxShadow='0 8px 20px rgba(217,119,6,0.35)'"
                    onmouseout="this.style.boxShadow='none'">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                    Borrowing Record
                </button>

            </div>

        </div>

        <!-- ══ STAT CARDS ══ -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">

            <div class="stat-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:#dbeafe;">
                        <i data-lucide="file-clock" class="w-4 h-4" style="color:#0037C7;"></i>
                    </div>
                    <span class="badge" style="background:#dbeafe; color:#1d4ed8;">+3 today</span>
                </div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#0f172a; line-height:1;">22</div>
                <div style="font-size:.75rem; color:#64748b; margin-top:4px; font-weight:500;">Pending Reports</div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:#fee2e2;">
                        <i data-lucide="alert-circle" class="w-4 h-4" style="color:#dc2626;"></i>
                    </div>
                    <span class="badge" style="background:#fee2e2; color:#dc2626;">High</span>
                </div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#dc2626; line-height:1;">4</div>
                <div style="font-size:.75rem; color:#64748b; margin-top:4px; font-weight:500;">Urgent Reports</div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:#fef3c7;">
                        <i data-lucide="wrench" class="w-4 h-4" style="color:#d97706;"></i>
                    </div>
                </div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#0f172a; line-height:1;">7</div>
                <div style="font-size:.75rem; color:#64748b; margin-top:4px; font-weight:500;">Under Maintenance</div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:#d1fae5;">
                        <i data-lucide="arrow-left-right" class="w-4 h-4" style="color:#16a34a;"></i>
                    </div>
                </div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#0f172a; line-height:1;">0</div>
                <div style="font-size:.75rem; color:#64748b; margin-top:4px; font-weight:500;">Borrowed Equipment</div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:#f3e8ff;">
                        <i data-lucide="calendar-x" class="w-4 h-4" style="color:#9333ea;"></i>
                    </div>
                </div>
                <div style="font-family:'Outfit',sans-serif; font-size:1.8rem; font-weight:800; color:#0f172a; line-height:1;">0</div>
                <div style="font-size:.75rem; color:#64748b; margin-top:4px; font-weight:500;">Overdue Maintenance</div>
            </div>

        </div>

        <!-- ══ URGENT REPORTS PIPELINE ══ -->
        <div>

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1.1rem; color:#0f172a;">
                        Urgent Reports Pipeline
                    </h2>
                    <p style="font-size:.75rem; color:#94a3b8; margin-top:2px;">Track active urgent issues in real-time</p>
                </div>
                <a href="#" class="flex items-center gap-1.5 px-4 py-2 rounded-xl transition"
                   style="background:#0037C7; color:#fff; font-size:.8rem; font-weight:600; text-decoration:none; font-family:'Outfit',sans-serif;"
                   onmouseover="this.style.background='#002ea8'" onmouseout="this.style.background='#0037C7'">
                    View All
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <!-- HORIZONTAL SCROLL CONTAINER -->
            <div class="flex gap-4 overflow-x-auto scroll-hide pb-2">

                <!-- URGENT CARD 1 -->
                <div class="urgent-card">

                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="badge" style="background:#fee2e2; color:#dc2626; margin-bottom:8px;">🔴 Urgent</span>
                            <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.95rem; color:#0f172a; margin-top:6px; line-height:1.3;">
                                Aircon Not Working
                            </div>
                            <div style="font-size:.75rem; color:#64748b; margin-top:3px;">
                                <i data-lucide="map-pin" class="w-3 h-3 inline"></i>
                                Room 201 · Building A
                            </div>
                        </div>
                        <div style="font-size:.7rem; color:#94a3b8; white-space:nowrap; margin-left:12px;">2h ago</div>
                    </div>

                    <!-- PIPELINE -->
                    <div class="pipeline-track mb-3" style="gap:2px;">
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 10px;">
                            <i data-lucide="check" class="w-3 h-3"></i> Submitted
                        </div>
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            <i data-lucide="check" class="w-3 h-3"></i> Reviewed
                        </div>
                        <div class="pipeline-step urgent-active" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            <i data-lucide="loader" class="w-3 h-3"></i> In Progress
                        </div>
                        <div class="pipeline-step" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px; border-radius:0 6px 6px 0; clip-path:none;">
                            Resolved
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                 style="background:#0037C7; font-size:.6rem; font-weight:700; color:#fff;">JD</div>
                            <span style="font-size:.75rem; color:#64748b;">Reported by Juan Dela Cruz</span>
                        </div>
                        <button style="font-size:.75rem; font-weight:600; color:#0037C7; background:none; border:none; cursor:pointer;">
                            View →
                        </button>
                    </div>

                </div>

                <!-- URGENT CARD 2 -->
                <div class="urgent-card">

                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="badge" style="background:#fee2e2; color:#dc2626; margin-bottom:8px;">🔴 Urgent</span>
                            <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.95rem; color:#0f172a; margin-top:6px; line-height:1.3;">
                                Projector Flickering
                            </div>
                            <div style="font-size:.75rem; color:#64748b; margin-top:3px;">
                                <i data-lucide="map-pin" class="w-3 h-3 inline"></i>
                                Computer Lab 1 · 3rd Floor
                            </div>
                        </div>
                        <div style="font-size:.7rem; color:#94a3b8; white-space:nowrap; margin-left:12px;">5h ago</div>
                    </div>

                    <div class="pipeline-track mb-3" style="gap:2px;">
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 10px;">
                            <i data-lucide="check" class="w-3 h-3"></i> Submitted
                        </div>
                        <div class="pipeline-step urgent-active" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            <i data-lucide="loader" class="w-3 h-3"></i> Reviewed
                        </div>
                        <div class="pipeline-step" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            In Progress
                        </div>
                        <div class="pipeline-step" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px; border-radius:0 6px 6px 0; clip-path:none;">
                            Resolved
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                 style="background:#0037C7; font-size:.6rem; font-weight:700; color:#fff;">MA</div>
                            <span style="font-size:.75rem; color:#64748b;">Reported by Maria Adlawan</span>
                        </div>
                        <button style="font-size:.75rem; font-weight:600; color:#0037C7; background:none; border:none; cursor:pointer;">
                            View →
                        </button>
                    </div>

                </div>

                <!-- URGENT CARD 3 -->
                <div class="urgent-card">

                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="badge" style="background:#fee2e2; color:#dc2626; margin-bottom:8px;">🔴 Urgent</span>
                            <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.95rem; color:#0f172a; margin-top:6px; line-height:1.3;">
                                No Power in Room
                            </div>
                            <div style="font-size:.75rem; color:#64748b; margin-top:3px;">
                                <i data-lucide="map-pin" class="w-3 h-3 inline"></i>
                                Room 304 · Building A
                            </div>
                        </div>
                        <div style="font-size:.7rem; color:#94a3b8; white-space:nowrap; margin-left:12px;">1d ago</div>
                    </div>

                    <div class="pipeline-track mb-3" style="gap:2px;">
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 10px;">
                            <i data-lucide="check" class="w-3 h-3"></i> Submitted
                        </div>
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            <i data-lucide="check" class="w-3 h-3"></i> Reviewed
                        </div>
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            <i data-lucide="check" class="w-3 h-3"></i> In Progress
                        </div>
                        <div class="pipeline-step urgent-active" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px; border-radius:0 6px 6px 0; clip-path:none;">
                            <i data-lucide="loader" class="w-3 h-3"></i> Resolved
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                 style="background:#0037C7; font-size:.6rem; font-weight:700; color:#fff;">RP</div>
                            <span style="font-size:.75rem; color:#64748b;">Reported by Rico Pareja</span>
                        </div>
                        <button style="font-size:.75rem; font-weight:600; color:#0037C7; background:none; border:none; cursor:pointer;">
                            View →
                        </button>
                    </div>

                </div>

                <!-- URGENT CARD 4 -->
                <div class="urgent-card">

                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="badge" style="background:#fee2e2; color:#dc2626; margin-bottom:8px;">🔴 Urgent</span>
                            <div style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.95rem; color:#0f172a; margin-top:6px; line-height:1.3;">
                                Water Leakage Ceiling
                            </div>
                            <div style="font-size:.75rem; color:#64748b; margin-top:3px;">
                                <i data-lucide="map-pin" class="w-3 h-3 inline"></i>
                                Faculty Room · 2nd Floor
                            </div>
                        </div>
                        <div style="font-size:.7rem; color:#94a3b8; white-space:nowrap; margin-left:12px;">3h ago</div>
                    </div>

                    <div class="pipeline-track mb-3" style="gap:2px;">
                        <div class="pipeline-step done" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 10px;">
                            <i data-lucide="check" class="w-3 h-3"></i> Submitted
                        </div>
                        <div class="pipeline-step" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            Reviewed
                        </div>
                        <div class="pipeline-step" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px;">
                            In Progress
                        </div>
                        <div class="pipeline-step" style="flex:1; min-width:0; font-size:10px; padding:6px 10px 6px 18px; border-radius:0 6px 6px 0; clip-path:none;">
                            Resolved
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                 style="background:#0037C7; font-size:.6rem; font-weight:700; color:#fff;">SL</div>
                            <span style="font-size:.75rem; color:#64748b;">Reported by Sara Lopez</span>
                        </div>
                        <button style="font-size:.75rem; font-weight:600; color:#0037C7; background:none; border:none; cursor:pointer;">
                            View →
                        </button>
                    </div>

                </div>

            </div>

        </div>

        <!-- ══ BUILDINGS & ROOMS + RECENT ACTIVITY ══ -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- BUILDINGS & ROOMS (2/3 width) -->
            <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden"
                 style="box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <!-- HEADER -->
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6 pb-4"
                     style="border-bottom:1px solid #f1f5f9;">

                    <div>
                        <h2 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1rem; color:#0f172a;">
                            Buildings & Rooms Monitoring
                        </h2>
                        <p style="font-size:.72rem; color:#94a3b8; margin-top:2px;">Real-time room and equipment tracking</p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">

                        <!-- BUILDING FILTER -->
                        <div class="flex items-center gap-1.5 p-1 rounded-xl" style="background:#f8fafc; border:1px solid #e2e8f0;">
                            <button class="floor-tab active text-xs" id="bldg-a" onclick="filterBuilding('a')">Building A</button>
                            <button class="floor-tab text-xs" id="bldg-lab" onclick="filterBuilding('lab')">Computer Lab</button>
                        </div>

                        <!-- FLOOR FILTER -->
                        <div class="flex items-center gap-1.5 p-1 rounded-xl" style="background:#f8fafc; border:1px solid #e2e8f0;">
                            <button class="floor-tab active text-xs" id="floor-all" onclick="filterFloor('all')">All</button>
                            <button class="floor-tab text-xs" id="floor-2" onclick="filterFloor('2')">2nd Floor</button>
                            <button class="floor-tab text-xs" id="floor-3" onclick="filterFloor('3')">3rd Floor</button>
                        </div>

                    </div>

                </div>

                <!-- LEGEND -->
                <div class="flex items-center gap-5 px-6 py-3" style="background:#fafbfc; border-bottom:1px solid #f1f5f9;">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full" style="background:#22c55e;"></div>
                        <span style="font-size:.72rem; color:#64748b; font-weight:500;">Available</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full" style="background:#f59e0b;"></div>
                        <span style="font-size:.72rem; color:#64748b; font-weight:500;">Needs Repair</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full" style="background:#dc2626;"></div>
                        <span style="font-size:.72rem; color:#64748b; font-weight:500;">Critical</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full" style="background:#94a3b8;"></div>
                        <span style="font-size:.72rem; color:#64748b; font-weight:500;">Under Maintenance</span>
                    </div>
                </div>

                <div class="p-6">

                    <!-- 2ND FLOOR -->
                    <div class="room-floor-section" data-floor="2">

                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center"
                                     style="background:#0037C7;">
                                    <i data-lucide="layers" class="w-3 h-3" style="color:#fff;"></i>
                                </div>
                                <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.85rem; color:#0f172a;">2nd Floor</span>
                            </div>
                            <div style="flex:1; height:1px; background:#e2e8f0;"></div>
                            <span style="font-size:.72rem; color:#94a3b8; font-weight:500;">6 rooms</span>
                        </div>

                        <!-- FLOOR PLAN GRID -->
                        <div class="grid grid-cols-3 gap-3 mb-2">

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 201</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Classroom</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;">
                                        <i data-lucide="package" class="w-3 h-3 inline"></i> 24 items
                                    </span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                            <div class="room-card needs-repair">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 202</span>
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5" style="color:#f59e0b;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Classroom</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;">
                                        <i data-lucide="package" class="w-3 h-3 inline"></i> 22 items
                                    </span>
                                    <span class="badge" style="background:#fef3c7; color:#d97706; font-size:.65rem;">Repair</span>
                                </div>
                            </div>

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 203</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Faculty Room</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;">
                                        <i data-lucide="package" class="w-3 h-3 inline"></i> 18 items
                                    </span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                            <div class="room-card critical">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 204</span>
                                    <i data-lucide="x-circle" class="w-3.5 h-3.5" style="color:#dc2626;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Classroom</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;">
                                        <i data-lucide="package" class="w-3 h-3 inline"></i> 20 items
                                    </span>
                                    <span class="badge" style="background:#fee2e2; color:#dc2626; font-size:.65rem;">Critical</span>
                                </div>
                            </div>

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 205</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Science Lab</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;">
                                        <i data-lucide="package" class="w-3 h-3 inline"></i> 31 items
                                    </span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                            <div class="room-card needs-repair"
                                 style="border-color:#94a3b8;">
                                <div class="flex items-center justify-between mb-2" style="border-left-color:#94a3b8;">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 206</span>
                                    <i data-lucide="wrench" class="w-3.5 h-3.5" style="color:#94a3b8;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Storage</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;">
                                        <i data-lucide="package" class="w-3 h-3 inline"></i> 8 items
                                    </span>
                                    <span class="badge" style="background:#f1f5f9; color:#64748b; font-size:.65rem;">Maintenance</span>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- CORRIDOR INDICATOR -->
                    <div class="flex items-center gap-2 my-4">
                        <div style="flex:1; height:1px; background:#e2e8f0; border-top:1px dashed #cbd5e1;"></div>
                        <span style="font-size:.68rem; color:#94a3b8; font-weight:500; white-space:nowrap; padding:3px 10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:999px;">
                            ↑ Corridor ↑
                        </span>
                        <div style="flex:1; height:1px; background:#e2e8f0; border-top:1px dashed #cbd5e1;"></div>
                    </div>

                    <!-- 3RD FLOOR -->
                    <div class="room-floor-section" data-floor="3">

                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center"
                                     style="background:#0037C7;">
                                    <i data-lucide="layers" class="w-3 h-3" style="color:#fff;"></i>
                                </div>
                                <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.85rem; color:#0f172a;">3rd Floor</span>
                            </div>
                            <div style="flex:1; height:1px; background:#e2e8f0;"></div>
                            <span style="font-size:.72rem; color:#94a3b8; font-weight:500;">6 rooms</span>
                        </div>

                        <div class="grid grid-cols-3 gap-3">

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 301</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Classroom</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;"><i data-lucide="package" class="w-3 h-3 inline"></i> 24 items</span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 302</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Classroom</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;"><i data-lucide="package" class="w-3 h-3 inline"></i> 22 items</span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                            <div class="room-card needs-repair">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 303</span>
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5" style="color:#f59e0b;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">AVR</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;"><i data-lucide="package" class="w-3 h-3 inline"></i> 16 items</span>
                                    <span class="badge" style="background:#fef3c7; color:#d97706; font-size:.65rem;">Repair</span>
                                </div>
                            </div>

                            <div class="room-card critical">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 304</span>
                                    <i data-lucide="x-circle" class="w-3.5 h-3.5" style="color:#dc2626;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Classroom</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;"><i data-lucide="package" class="w-3 h-3 inline"></i> 20 items</span>
                                    <span class="badge" style="background:#fee2e2; color:#dc2626; font-size:.65rem;">Critical</span>
                                </div>
                            </div>

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 305</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Library</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;"><i data-lucide="package" class="w-3 h-3 inline"></i> 40 items</span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                            <div class="room-card available">
                                <div class="flex items-center justify-between mb-2">
                                    <span style="font-family:'Outfit',sans-serif; font-weight:700; font-size:.8rem; color:#0f172a;">Room 306</span>
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5" style="color:#22c55e;"></i>
                                </div>
                                <div style="font-size:.68rem; color:#64748b; margin-bottom:8px;">Guidance</div>
                                <div class="flex items-center justify-between">
                                    <span style="font-size:.68rem; color:#94a3b8;"><i data-lucide="package" class="w-3 h-3 inline"></i> 12 items</span>
                                    <span class="badge" style="background:#d1fae5; color:#16a34a; font-size:.65rem;">Good</span>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- RECENT ACTIVITY (1/3 width) -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
                 style="box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <div class="px-5 pt-5 pb-4" style="border-bottom:1px solid #f1f5f9;">
                    <h2 style="font-family:'Outfit',sans-serif; font-weight:700; font-size:1rem; color:#0f172a;">
                        Recent Activities
                    </h2>
                    <p style="font-size:.72rem; color:#94a3b8; margin-top:2px;">Latest system events</p>
                </div>

                <div class="p-5 flex flex-col gap-4" style="max-height:580px; overflow-y:auto;">

                    <div class="flex items-start gap-3">
                        <div class="activity-dot" style="background:#fee2e2;">
                            <i data-lucide="alert-triangle" class="w-4 h-4" style="color:#dc2626;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size:.8rem; font-weight:600; color:#0f172a; line-height:1.3;">Urgent Report Submitted</div>
                            <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Aircon broken at Room 201</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:4px;">2 hours ago</div>
                        </div>
                    </div>

                    <div style="height:1px; background:#f1f5f9;"></div>

                    <div class="flex items-start gap-3">
                        <div class="activity-dot" style="background:#fef3c7;">
                            <i data-lucide="wrench" class="w-4 h-4" style="color:#d97706;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size:.8rem; font-weight:600; color:#0f172a; line-height:1.3;">Equipment Repaired</div>
                            <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Projector in 3rd floor fixed</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:4px;">4 hours ago</div>
                        </div>
                    </div>

                    <div style="height:1px; background:#f1f5f9;"></div>

                    <div class="flex items-start gap-3">
                        <div class="activity-dot" style="background:#d1fae5;">
                            <i data-lucide="check-circle" class="w-4 h-4" style="color:#16a34a;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size:.8rem; font-weight:600; color:#0f172a; line-height:1.3;">Report Resolved</div>
                            <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Network issue at Room 305</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:4px;">Yesterday, 3:40 PM</div>
                        </div>
                    </div>

                    <div style="height:1px; background:#f1f5f9;"></div>

                    <div class="flex items-start gap-3">
                        <div class="activity-dot" style="background:#dbeafe;">
                            <i data-lucide="qr-code" class="w-4 h-4" style="color:#0037C7;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size:.8rem; font-weight:600; color:#0f172a; line-height:1.3;">QR Code Scanned</div>
                            <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Monitor #PC-042 verified</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:4px;">Yesterday, 1:15 PM</div>
                        </div>
                    </div>

                    <div style="height:1px; background:#f1f5f9;"></div>

                    <div class="flex items-start gap-3">
                        <div class="activity-dot" style="background:#f3e8ff;">
                            <i data-lucide="clipboard-list" class="w-4 h-4" style="color:#9333ea;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size:.8rem; font-weight:600; color:#0f172a; line-height:1.3;">Maintenance Scheduled</div>
                            <div style="font-size:.72rem; color:#64748b; margin-top:2px;">HVAC system — Room 204</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:4px;">June 15, 2026</div>
                        </div>
                    </div>

                    <div style="height:1px; background:#f1f5f9;"></div>

                    <div class="flex items-start gap-3">
                        <div class="activity-dot" style="background:#fee2e2;">
                            <i data-lucide="package-x" class="w-4 h-4" style="color:#dc2626;"></i>
                        </div>
                        <div class="flex-1">
                            <div style="font-size:.8rem; font-weight:600; color:#0f172a; line-height:1.3;">Equipment Disposed</div>
                            <div style="font-size:.72rem; color:#64748b; margin-top:2px;">Old CRT Monitor — Storage</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:4px;">June 14, 2026</div>
                        </div>
                    </div>

                </div>

                <div class="px-5 py-3" style="border-top:1px solid #f1f5f9;">
                    <button class="w-full py-2.5 rounded-xl text-sm font-600 transition"
                            style="background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; font-weight:600; font-size:.8rem;"
                            onmouseover="this.style.background='#dbeafe'; this.style.borderColor='#93c5fd'; this.style.color='#0037C7'"
                            onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'; this.style.color='#64748b'">
                        View All Activity
                    </button>
                </div>

            </div>

        </div>

    </div>
</div>



<script>

    lucide.createIcons();

    /* ── CLOCK ── */
    function updateClock() {
        const now  = new Date();
        const date = now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
        const time = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
        const dateEl = document.getElementById('hdr-date');
        const timeEl = document.getElementById('hdr-time');
        if (dateEl) dateEl.textContent = date;
        if (timeEl) timeEl.textContent = time;
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ── ACCORDION ── */
    function toggleAccordion(id, chevId) {
        const content = document.getElementById(id);
        const chev    = document.getElementById(chevId);
        content.classList.toggle('open');
        chev.classList.toggle('rotated');
    }

    /* ── BUILDING FILTER ── */
    function filterBuilding(building) {
        document.querySelectorAll('[id^="bldg-"]').forEach(b => b.classList.remove('active'));
        document.getElementById('bldg-' + building).classList.add('active');
        // In real implementation: show/hide rooms by building
    }

    /* ── FLOOR FILTER ── */
    function filterFloor(floor) {
        document.querySelectorAll('[id^="floor-"]').forEach(b => b.classList.remove('active'));
        document.getElementById('floor-' + floor).classList.add('active');

        const sections = document.querySelectorAll('.room-floor-section');
        sections.forEach(sec => {
            if (floor === 'all') {
                sec.style.display = 'block';
            } else {
                sec.style.display = sec.dataset.floor === floor ? 'block' : 'none';
            }
        });

        /* Show/hide corridor indicator */
        const corridor = document.querySelector('.my-4');
        if (corridor) {
            corridor.style.display = floor === 'all' ? 'flex' : 'none';
        }
    }

</script>



@endsection

