<section id="dashboardBuildingSection" class="dashboard-building-section">
    {{-- HEADER --}}
    <div class="dashboard-building-header">
        <div>
            <p class="dashboard-building-eyebrow">INFRASTRUCTURE OVERVIEW</p>

            <h2 class="dashboard-building-title">
                Campus Monitoring
            </h2>

            <p class="dashboard-building-subtitle">Interactive overview of rooms, equipment, and maintenance status.</p>
        </div>

        <button
            type="button"
            id="buildingFullscreenBtn"
            class="dashboard-building-action"
            aria-label="Enter full screen"
            aria-pressed="false"
        >
            <i data-lucide="maximize-2"></i>
            <span>Full Screen</span>
        </button>
    </div>

    {{-- 3D BUILDING VIEW --}}
    {{-- ===================================================== --}}
    {{-- PHASE 1: INTERACTIVE 3D BUILDING VIEWPORT --}}
    {{-- ===================================================== --}}

    <div id="dashboardBuildingView" class="dashboard-building-view">
        {{-- THREE.JS WILL RENDER THE 3D SCENE HERE --}}
        <div id="building3DViewport"></div>

        <button
            type="button"
            id="buildingExitFullscreenBtn"
            class="building-exit-fullscreen-btn"
            aria-label="Exit full screen"
        >
            <i data-lucide="minimize-2"></i>
        </button>

        {{-- ===================================================== --}}
        {{-- ENTER BUILDING BUTTON --}}
        {{-- Opens the interior without clicking the 3D shell --}}
        {{-- ===================================================== --}}

        <button
            type="button"
            id="enterBuildingBtn"
            class="building-enter-btn"
        >
            <i data-lucide="door-open" class="h-4 w-4"></i>

            <!--<span class="text-xs">Enter Building</span>-->
        </button>

        {{-- FIND A PERSON'S ASSIGNED PROPERTY --}}
        <div id="buildingCustodianFinder" class="building-custodian-finder" hidden>
            <button
                type="button"
                id="buildingCustodianFinderToggle"
                class="building-custodian-finder-toggle"
                aria-expanded="false"
            >
                <i data-lucide="user-round-search"></i>
                <span>Find person</span>
            </button>

            <div id="buildingCustodianFinderPanel" class="building-custodian-finder-panel" hidden>
                <input
                    type="search"
                    id="buildingCustodianFinderSearch"
                    class="building-custodian-finder-search"
                    placeholder="Search name or employee ID"
                    autocomplete="off"
                >
                <div id="buildingCustodianFinderResults" class="building-custodian-finder-results"></div>
            </div>
        </div>

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

                    <h3 id="buildingRoomDetailsName">Room</h3>
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
                        No Active Reports
                    </strong>
                </div>

                <div
                    id="buildingRoomDetailsPropertyRow"
                    class="building-room-details-row"
                    hidden
                >
                    <span>Property</span>

                    <a
                        id="buildingRoomDetailsProperty"
                        class="building-room-details-property-link"
                        href="#"
                    ></a>
                </div>
            </div>

            {{-- MAINTENANCE SUMMARY --}}
            <div class="building-room-details-stats">
                <div class="building-room-details-stat">
                    <span>Active Reports</span>

                    <strong
                        id="buildingRoomDetailsActiveReports"
                    >
                        0
                    </strong>
                </div>

                <div class="building-room-details-stat">
                    <span>Urgent Reports</span>

                    <strong
                        id="buildingRoomDetailsUrgentReports"
                    >
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

                <!--<i data-lucide="arrow-right"></i>-->
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

            <div
                id="buildingRoomTooltipProperty"
                class="building-room-tooltip-details building-room-tooltip-property"
                hidden
            ></div>
        </div>

        {{-- FLOATING LABEL --}}
        <!--<div class="dashboard-building-badge">
            <span class="dashboard-building-badge-dot"></span>

            Interactive Building Overview
        </div>-->

        {{-- ===================================================== --}}
        {{-- PHASE 7.4: FLOOR FILTER CONTROLS --}}
        {{-- ===================================================== --}}

        <div
            id="buildingFloorFilters"
            class="building-floor-filters"
            style="display: none"
        >
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
            style="display: none"
            class="building-back-overview-btn"
        >
             <!--<i data-lucide="arrow-return-left" class="h-4 w-4"></i>-->
             <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5"/>
            </svg>
            
            <!--<span class="text-xs">Return</span>-->
        </button>

        {{-- 3D CONTROLS (reset icon hidden; use hold + R) --}}
        <div class="building-3d-controls" style="display: none" aria-hidden="true">
            <button
                type="button"
                id="buildingReset"
                class="building-3d-control"
                data-tooltip="Reset View"
                tabindex="-1"
            >
                <i data-lucide="rotate-ccw"></i>
            </button>
        </div>
    </div>
</section>
