<style>
        /* =====================================================
        3D BUILDING OVERVIEW
        ADD THIS INSIDE YOUR STYLE
        ===================================================== */

        .dashboard-building-section {
            width: 100%;
            min-width: 0;

            overflow: hidden;

            background: white;

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
        HEADER ACTIONS + ROOM LAYOUT SWITCH
        ===================================================== */

        .dashboard-building-header-actions {
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
            gap: 10px;
        }

        .dashboard-building-layout-switch {
            height: 40px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px 0 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .dashboard-building-layout-switch:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .dashboard-building-layout-switch[hidden] {
            display: none;
        }

        .dashboard-building-layout-switch-label {
            white-space: nowrap;
        }

        .dashboard-building-layout-switch-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .dashboard-building-layout-switch-track {
            position: relative;
            width: 36px;
            height: 20px;
            flex-shrink: 0;
            border-radius: 999px;
            background: #cbd5e1;
            transition: background 0.2s ease;
        }

        .dashboard-building-layout-switch-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background: white;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);
            transition: transform 0.2s ease;
        }

        .dashboard-building-layout-switch-input:checked + .dashboard-building-layout-switch-track {
            background: #0025cc;
        }

        .dashboard-building-layout-switch-input:checked + .dashboard-building-layout-switch-track
            .dashboard-building-layout-switch-thumb {
            transform: translateX(16px);
        }

        .dashboard-building-layout-switch-input:focus-visible + .dashboard-building-layout-switch-track {
            outline: 2px solid rgba(0, 37, 204, 0.35);
            outline-offset: 2px;
        }

        /* =====================================================
        FULL SCREEN BUTTON
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

            background: white;

            color: #334155;

            font-family: inherit;
            font-size: 11px;
            font-weight: 600;

            text-decoration: none;

            cursor: pointer;

            appearance: none;
            -webkit-appearance: none;

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
            position: relative;

            width: 100%;

            height: clamp(360px, 45vw, 520px);

            min-height: 360px;

            overflow: hidden;

            /* Exterior: dark ice-blue studio behind the holographic shell */
            background:
                radial-gradient(
                    ellipse 70% 55% at 50% 48%,
                    #0c3d55 0%,
                    #072536 42%,
                    #020d18 100%
                );
        }

        #building3DViewport.is-interior-view {
            background:
                radial-gradient(
                    ellipse 70% 55% at 50% 48%,
                    #0c3d55 0%,
                    #072536 42%,
                    #020d18 100%
                );
        }


        /* ===================================================== */
        /* THREE.JS CANVAS */
        /* ALWAYS FILL THE VIEWPORT */
        /* ===================================================== */

        #building3DViewport canvas {
            display: block;

            width: 100% !important;

            height: 100% !important;
        }

        #building3DViewport canvas:active {
            cursor: grabbing;
        }

        .building-3d-context-notice {
            position: absolute;
            inset: 0;
            z-index: 60;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 24px;
            background: #f8fafc;
            text-align: center;
        }

        .building-3d-context-notice[hidden] {
            display: none;
        }

        .building-3d-context-notice-title {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .building-3d-context-notice-text {
            max-width: 320px;
            margin: 0;
            font-size: 12px;
            line-height: 1.5;
            color: #64748b;
        }

        .building-3d-context-notice-btn {
            margin-top: 8px;
            height: 36px;
            padding: 0 16px;
            border: 0;
            border-radius: 8px;
            background: #0025cc;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .building-3d-context-notice-btn:hover {
            background: #001db3;
        }


        /* =====================================================
        3D CONTROLS
        ===================================================== */

        /* =====================================================
        3D VIEWPORT CONTROLS WRAPPER
        Keep the same bottom-right placement
        Matches Enter Building / Return button design
        ===================================================== */

        .building-3d-controls {
            position: absolute;

            /* KEEP CURRENT PLACEMENT */
            right: 20px;
            bottom: 20px;

            z-index: 10;

            display: flex;
            align-items: center;

            gap: 6px;

            padding: 4px;

            /* Soft frosted control — blends with clay exterior */
            background: rgba(255, 255, 255, 0.72);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            border: 1px solid rgba(148, 163, 184, 0.35);

            border-radius: 12px;

            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.10);
        }

        #building3DViewport.is-interior-view .building-3d-controls {
            background: rgba(255, 255, 255, 0.88);
            border-color: rgba(148, 163, 184, 0.35);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.10);
        }

        /* =====================================================
        ENTER BUILDING BUTTON
        Anchored to the building roof center in 3D (via JS)
        ===================================================== */

        .building-enter-btn {
            position: absolute;

            left: 50%;
            top: 40%;
            right: auto;

            z-index: 20;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 10px 16px;

            border: 1px solid rgba(103, 232, 249, 0.35);

            border-radius: 999px;

            background: rgba(2, 11, 20, 0.82);

            color: #e6faff;

            font-size: 13px;
            font-weight: 700;

            cursor: pointer;

            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            box-shadow:
                0 10px 28px rgba(0, 0, 0, 0.28),
                0 0 18px rgba(34, 211, 238, 0.08);

            transform: translate(-50%, -115%);

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;

            white-space: nowrap;
            pointer-events: auto;
        }

        .building-enter-btn:hover {
            background: rgba(8, 47, 73, 0.92);

            border-color: rgba(103, 232, 249, 0.7);

            box-shadow:
                0 14px 32px rgba(0, 0, 0, 0.32),
                0 0 22px rgba(34, 211, 238, 0.16);

            transform: translate(-50%, calc(-115% - 3px));
        }

        .building-enter-btn i {
            width: 16px;
            height: 16px;
            color: #67e8f9;
        }

        /* Small pointer so it feels attached to the roof */
        .building-enter-btn::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: -7px;
            width: 12px;
            height: 12px;
            background: rgba(2, 11, 20, 0.82);
            border-right: 1px solid rgba(103, 232, 249, 0.35);
            border-bottom: 1px solid rgba(103, 232, 249, 0.35);
            transform: translateX(-50%) rotate(45deg);
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.18);
        }





        


        /* =====================================================
        3D VIEWPORT CONTROLS
        Same placement and size
        Matches Enter Building / Return button design
        ===================================================== */

        .building-3d-control {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            /* Soft light control — blends with exterior shell */
            border: 1px solid rgba(148, 163, 184, 0.4);
            border-radius: 9px;

            background: rgba(255, 255, 255, 0.88);

            color: #475569;

            cursor: pointer;

            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        /* =====================================================
        HOVER
        ===================================================== */

        .building-3d-control:hover {
            background: #ffffff;

            border-color: rgba(59, 130, 246, 0.45);

            color: #2563eb;

            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);

            transform: translateY(-1px);
        }

        #building3DViewport.is-interior-view .building-3d-control {
            border-color: rgba(148, 163, 184, 0.4);
            background: rgba(255, 255, 255, 0.95);
            color: #334155;
            box-shadow: none;
        }

        #building3DViewport.is-interior-view .building-3d-control:hover {
            background: #ffffff;
            border-color: rgba(100, 116, 139, 0.55);
            color: #0f172a;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }


        /* =====================================================
        CONTROL ICON
        ===================================================== */

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
            flex-wrap: nowrap;
            align-items: center;
            gap: 6px 8px;

            max-width: calc(100% - 96px);
            padding: 5px 8px;

            background: rgba(2, 6, 23, 0.72);
            border: 1px solid rgba(34, 211, 238, 0.2);
            border-radius: 999px;

            backdrop-filter: blur(12px);
            overflow-x: auto;
            scrollbar-width: none;
        }

        .building-floor-filters::-webkit-scrollbar {
            display: none;
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
            color: white;
            background: rgba(255, 255, 255, 0.08);
        }

        .building-floor-filter.active {
            color: white;
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

            .dashboard-building-header-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .dashboard-building-layout-switch,
            .dashboard-building-action {
                flex: 1 1 auto;
                justify-content: center;
            }

            .dashboard-building-view {
                height: 400px;
            }

        }


        /* =====================================================
        BUILDING OVERVIEW FULL SCREEN
        Header stays out of fullscreen. Exit control lives on the 3D view.
        ===================================================== */

        .dashboard-building-view.is-building-fullscreen {
            width: 100%;
            height: 100%;
            max-height: 100vh;
            max-height: 100dvh;

            overflow: hidden;
        }

        .dashboard-building-view.is-building-fullscreen.is-pseudo-fullscreen {
            position: fixed;
            inset: 0;
            z-index: 99999;

            width: 100vw;
            height: 100vh;
            height: 100dvh;
        }

        .dashboard-building-view.is-building-fullscreen #building3DViewport {
            height: 100% !important;
            min-height: 0;
        }

        .building-exit-fullscreen-btn {
            display: none;

            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 60;

            width: 42px;
            height: 42px;

            align-items: center;
            justify-content: center;

            padding: 0;

            border: 1px solid rgba(103, 232, 249, 0.35);
            border-radius: 12px;

            background: rgba(2, 11, 20, 0.82);

            color: #e6faff;

            cursor: pointer;

            box-shadow:
                0 10px 24px rgba(0, 0, 0, 0.28),
                0 0 16px rgba(34, 211, 238, 0.08);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .dashboard-building-view.is-building-fullscreen .building-exit-fullscreen-btn {
            display: inline-flex;
            height: 42px;
        }

        .dashboard-building-view.is-building-fullscreen .building-back-overview-btn {
            top: 16px;
            right: 66px;
            z-index: 60;
            height: 42px;
            padding-top: 0;
            padding-bottom: 0;
            box-sizing: border-box;
        }

        .building-exit-fullscreen-btn:hover {
            background: rgba(8, 47, 73, 0.92);
            border-color: rgba(103, 232, 249, 0.7);
            transform: translateY(-1px);
            box-shadow:
                0 14px 28px rgba(0, 0, 0, 0.32),
                0 0 20px rgba(34, 211, 238, 0.14);
        }

        .building-exit-fullscreen-btn svg,
        .building-exit-fullscreen-btn i {
            width: 18px;
            height: 18px;
            color: #67e8f9;
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

            color: white;
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

        .building-room-tooltip-property {
            color: #5eead4;
        }

        .building-room-tooltip-property[hidden],
        #buildingRoomDetailsPropertyRow[hidden],
        .building-custodian-finder[hidden],
        .building-custodian-finder-panel[hidden] {
            display: none !important;
        }

        .building-room-details-property-link {
            color: #5eead4;
            font-weight: 700;
            text-decoration: none;
            text-align: right;
        }

        .building-room-details-property-link:hover {
            text-decoration: underline;
        }

        /* Find a person's assigned property */
        .building-custodian-finder {
            position: absolute;
            left: 16px;
            bottom: 16px;
            z-index: 25;
            display: flex;
            flex-direction: column-reverse;
            align-items: flex-start;
            gap: 8px;
            max-width: calc(100% - 32px);
        }

        .building-custodian-finder-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 36px;
            padding: 0 14px;
            border: 1px solid rgba(94, 234, 212, 0.28);
            border-radius: 999px;
            background: rgba(2, 6, 23, 0.78);
            color: #ccfbf1;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            backdrop-filter: blur(12px);
            transition: 0.2s ease;
        }

        .building-custodian-finder-toggle:hover,
        .building-custodian-finder-toggle[aria-expanded="true"] {
            background: rgba(20, 184, 166, 0.22);
            color: white;
        }

        .building-custodian-finder-toggle svg {
            width: 15px;
            height: 15px;
        }

        .building-custodian-finder-panel {
            width: 290px;
            max-width: 100%;
            padding: 10px;
            border: 1px solid rgba(94, 234, 212, 0.22);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.94);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(18px);
        }

        .building-custodian-finder-search {
            width: 100%;
            height: 36px;
            padding: 0 12px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.9);
            color: white;
            font-size: 12px;
            outline: none;
        }

        .building-custodian-finder-search:focus {
            border-color: rgba(94, 234, 212, 0.55);
        }

        .building-custodian-finder-results {
            max-height: 220px;
            margin-top: 8px;
            overflow-y: auto;
        }

        .building-custodian-finder-person,
        .building-custodian-finder-room {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            border: 0;
            border-radius: 9px;
            background: transparent;
            color: #e2e8f0;
            font-size: 12px;
            text-align: left;
            cursor: pointer;
        }

        .building-custodian-finder-person:hover,
        .building-custodian-finder-room:hover {
            background: rgba(20, 184, 166, 0.16);
        }

        .building-custodian-finder-person strong {
            display: block;
            font-weight: 600;
            color: white;
        }

        .building-custodian-finder-person small,
        .building-custodian-finder-room small,
        .building-custodian-finder-count {
            color: #94a3b8;
            font-size: 11px;
        }

        .building-custodian-finder-room {
            padding-left: 22px;
        }

        .building-custodian-finder-empty {
            padding: 10px;
            color: #94a3b8;
            font-size: 12px;
        }

        /* ===================================================== */
        /* PHASE 7.8: COMPACT ROOM DETAILS PANEL */
        /* ===================================================== */

        .building-room-details-panel {
            position: fixed;
            top: 96px;
            right: 24px;
            width: min(320px, calc(100vw - 32px));
            max-height: min(78vh, 640px);
            padding: 18px;
            overflow-y: auto;

            background: rgba(2, 6, 23, 0.96);
            border: 1px solid rgba(34, 211, 238, 0.25);
            border-radius: 16px;

            backdrop-filter: blur(18px);

            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.35),
                0 0 30px rgba(34, 211, 238, 0.06);

            z-index: 1200;

            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateX(15px);

            transition:
                opacity 0.2s ease,
                transform 0.2s ease,
                visibility 0.2s ease;
        }

        .building-room-details-panel.visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(0);
        }

        .building-room-details-panel.is-anchored {
            right: auto;
            transform: none;
        }

        .building-room-details-panel.is-anchored.visible {
            transform: none;
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

            color: white;
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
            color: white;
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
            color: #CACACA;
        }

        .building-room-details-row strong {
            color: #e2e8f0;
        }


        /* STATS */

        .building-room-details-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);

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

            color: #CACACA;
        }

        .building-room-details-stat strong {
            display: block;

            margin-top: 4px;

            font-size: 17px;

            color: white;
        }

        .building-room-details-stat strong.is-skeleton {
            width: 1.5rem;
            height: 0.95rem;
            margin: 6px auto 0;
            border-radius: 6px;
            color: transparent;
            background: linear-gradient(90deg, rgba(148, 163, 184, 0.18) 25%, rgba(226, 232, 240, 0.45) 50%, rgba(148, 163, 184, 0.18) 75%);
            background-size: 200% 100%;
            animation: building-room-skel 1s ease-in-out infinite;
        }

        .building-room-skel {
            padding: 4px 0;
        }

        .building-room-skel-row {
            height: 52px;
            margin: 8px 10px;
            border-radius: 8px;
            background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: building-room-skel 1s ease-in-out infinite;
        }

        .building-room-skel-row:nth-child(2) {
            width: 92%;
        }

        .building-room-skel-row:nth-child(3) {
            width: 78%;
        }

        @keyframes building-room-skel {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
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

            color: white;

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

        /* =====================================================
        ENTER BUILDING + RETURN BUTTON
        Return stays top-right; Enter is roof-anchored in 3D
        ===================================================== */

        .building-back-overview-btn {
            position: absolute;

            /* SAME TOP RIGHT POSITION */
            top: 24px;
            right: 24px;

            z-index: 20;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            padding: 10px 14px;

            /* DARK GLASS DESIGN */
            background: rgba(2, 11, 20, 0.82);

            border: 1px solid rgba(103, 232, 249, 0.35);
            border-radius: 10px;

            color: #e6faff;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);

            transition:
                transform 0.2s ease,
                background 0.2s ease,
                border-color 0.2s ease;
        }


        /* =====================================================
        HOVER
        ===================================================== */

        .building-back-overview-btn:hover {
            transform: translateY(-2px);

            background: rgba(8, 47, 73, 0.92);

            border-color: rgba(103, 232, 249, 0.7);
        }


        /* =====================================================
        ICON SIZE
        ===================================================== */

        .building-back-overview-btn i {
            width: 17px;
            height: 17px;
        }

        .building-status-period-compact {
            display: inline-flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 6px;
            margin-left: 2px;
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 600;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .building-period-controls {
            display: inline-flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
        }

        .building-status-period-custom-inline,
        .building-room-details-custom-range {
            display: inline-flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
        }

        .building-status-period-custom-inline[hidden],
        .building-room-details-custom-range[hidden] {
            display: none !important;
        }

        .building-floor-filters .building-period-select {
            min-width: 7.25rem;
            width: auto;
            height: 32px;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 11px;
        }

        .building-floor-filters .building-period-date {
            width: 8.75rem;
            max-width: none;
            height: 32px;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 11px;
        }

        .building-period-select {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            min-width: 120px;
            width: auto;
            flex: 0 0 auto;
        }

        .building-period-select--dark,
        .building-period-date--dark {
            border-color: rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.75);
            color: #e2e8f0;
        }

        .building-period-date {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            border-radius: 8px;
            padding: 4px 8px;
            font-size: 11px;
        }

        .building-period-apply {
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 10px;
            cursor: pointer;
        }

        .building-room-details-row--stack {
            align-items: flex-start;
            flex-direction: column;
            gap: 6px;
        }

        .building-room-details-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .building-room-details-chips span,
        .building-room-details-chip {
            display: inline-flex;
            border-radius: 6px;
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 7px;
        }

        .building-room-details-muted {
            color: #94a3b8;
            font-size: 12px;
        }

        .building-room-details-period {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .building-room-details-period-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        .building-room-details-period .building-period-controls {
            width: 100%;
        }

        .building-room-details-period .building-period-select {
            flex: 1 1 auto;
            min-width: 0;
            width: auto;
        }

        .building-room-details-period .building-period-date {
            flex: 0 0 auto;
            width: auto;
            max-width: 10.5rem;
        }

        .building-room-details-reports {
            margin-top: 12px;
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
        }

        .building-room-report-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .building-room-report-row:last-child {
            border-bottom: 0;
        }

        .building-room-report-sev {
            flex: 0 0 auto;
            align-self: flex-start;
            height: fit-content;
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .building-room-report-sev.is-critical { background: #fee2e2; color: #b91c1c; }
        .building-room-report-sev.is-high { background: #ffedd5; color: #c2410c; }
        .building-room-report-sev.is-medium { background: #e0f2fe; color: #0369a1; }
        .building-room-report-sev.is-low { background: #f1f5f9; color: #475569; }

</style>
