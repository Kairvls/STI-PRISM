@extends ("layouts.maintenance-layout")

@section ("title", "Infrastructure Monitoring | PRISM")

@section ("content")
    @php
        use App\Support\LayoutEquipmentPayload;
        use Illuminate\Support\Str;

        $initialFloor =
            $floors->firstWhere("floor_id", $requestedFloorId) ?? $floors->first();
        $roomCatalog = $rooms
            ->map(
                fn($room) => [
                    "id" => $room->room_id,
                    "floor_id" => $room->room_floor_id,
                    "name" => $room->room_name,
                    "type" => $room->room_type ?: "Room",
                    "color" => $room->room_color ?: "#60A5FA",
                    "status" => $room->room_status,
                    "layout_mode" => $room->room_layout_mode ?: "loose_equipment",
                    "x" => (int) $room->room_x,
                    "y" => (int) $room->room_y,
                    "width" => (int) $room->room_width,
                    "height" => (int) $room->room_height,
                    "monitoring" => $room->monitoring,
                    "comlab_rows" => data_get($room->room_metadata, 'comlab_rows', []),
                    "comlab_row_layouts" => data_get($room->room_metadata, 'comlab_row_layouts'),
                    "equipment" => $room->equipment
                        ->values()
                        ->map(fn($equipment) => LayoutEquipmentPayload::fromModel($equipment))
                        ->all(),
                ],
            )
            ->values();
        $roomCountsByFloor = $rooms->countBy("room_floor_id");
    @endphp

    <div
        data-infrastructure-monitor
        x-data="infrastructureMonitor({{ (int) optional($initialFloor)->floor_id }})"
        x-init="init()"
        @keydown.space.window="
            const target = $event.target;
            const tag = (target.tagName || '').toLowerCase();
            const isTypingContext =
                target.isContentEditable ||
                ['input', 'textarea', 'select'].includes(tag);

            if (!isTypingContext) {
                $event.preventDefault();
                spacePressed = true;
            }
        "
        @keyup.space.window="
            const target = $event.target;
            const tag = (target.tagName || '').toLowerCase();
            const isTypingContext =
                target.isContentEditable ||
                ['input', 'textarea', 'select'].includes(tag);

            if (!isTypingContext) {
                spacePressed = false;
            }
        "
        @keydown.escape.window="
            if (wizardOpen) {
                if (wizardFullscreen) {
                    wizardFullscreen = false;
                    $nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    closeCampusWizard();
                }
            }
        "
        @keydown.window="handleLayoutUndoHotkey($event)"
        @pointermove.window="trackRoomRotation($event); trackEquipmentRotation($event); trackComlabRowRotation($event); trackEquipmentAction($event)"
        @pointerup.window="endRoomRotation($event); endEquipmentRotation($event); endComlabRowRotation($event); endEquipmentAction($event)"
        class="flex w-full flex-1 flex-col"
    >
        {{-- ========================================================= --}}
        {{-- INFRASTRUCTURE WORKSPACE TOOLBAR --}}
        {{-- ========================================================= --}}

        <div class="mt-6 mb-5 shrink-0">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">

                {{-- LEFT: LEGEND --}}
                <div class="flex min-w-0 flex-1 items-center gap-2">

                    {{-- CONDITION LEGEND --}}
                    <div class="hidden items-center gap-3 text-[11px] font-medium text-slate-500 sm:flex">
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Good</span>
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span>Maint.</span>
                        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>Critical</span>
                    </div>

                </div>

                {{-- RIGHT: SEARCH + FIND + CONFIGURE CAMPUS --}}
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">

                    {{-- SEARCH --}}
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                        <input
                            id="room-blueprint-search"
                            type="search"
                            x-model="roomSearch"
                            @keydown.enter.prevent="focusRoomSearch()"
                            placeholder="Search rooms…"
                            class="h-9 w-48 rounded-xl border border-slate-200 bg-white pl-8 pr-3 text-[11px] font-medium text-slate-700 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:w-60 xl:w-52"
                        >
                    </div>

                    {{-- FIND --}}
                    <button
                        type="button"
                        @click="focusRoomSearch()"
                        class="flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-[11px] font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50"
                    >
                        <i data-lucide="scan-search" class="h-3.5 w-3.5"></i>
                        Find
                    </button>

                    {{-- CONFIGURE CAMPUS --}}
                    <button
                        type="button"
                        @click="openCampusWizard()"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-[11px] font-bold text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-blue-800"
                    >
                        <i data-lucide="building-2" class="h-3.5 w-3.5"></i>
                        Configure campus
                    </button>

                </div>

            </div>
        </div>

        <!-- =============================== -->
        <!-- Workspace -->
        <!-- Replace this whole class -->
        <!-- =============================== -->

        <div
            class="layout-workspace w-full min-w-0 flex-1"
            :class="drawerOpen ? 'is-drawer-open' : 'is-drawer-closed'"
        >
            <div class="layout-monitor-main flex min-h-0 min-w-0 flex-col gap-6">
            <div class="flex min-w-0 flex-col">
                {{-- FLOOR FOLDER TABS --}}
                <div
                    class="floor-tabs-bar flex min-w-0 items-end gap-1 overflow-x-auto"
                    role="tablist"
                    aria-label="Floor selection"
                >
                    @forelse ($floors as $floor)
                        @php
                            $floorRoomCount = (int) ($roomCountsByFloor[$floor->floor_id] ?? 0);
                        @endphp
                        <button
                            type="button"
                            @click="selectFloor({{ $floor->floor_id }})"
                            :class="Number(activeFloor) === {{ $floor->floor_id }}
                                ? 'floor-tab floor-tab--active'
                                : 'floor-tab floor-tab--inactive'"
                            role="tab"
                            :aria-selected="Number(activeFloor) === {{ $floor->floor_id }}"
                        >
                            <span class="floor-tab__label">{{ $floor->floor_level }}</span>
                            <span class="floor-tab__count">{{ $floorRoomCount }}</span>
                        </button>
                    @empty
                        <div class="floor-tab floor-tab--inactive opacity-60">
                            No floors
                        </div>
                    @endforelse
                </div>

            <section
                x-ref="blueprintWorkspace"
                class="relative -mt-px flex min-w-0 flex-col overflow-hidden rounded-[28px] rounded-tl-none border border-slate-200 bg-white xl:min-h-[520px]"
            >
                <!-- ========================================================= -->
                <!-- TOP TOOLBAR -->
                <!-- ========================================================= -->
                <div
                    x-ref="blueprintToolbar"
                    class="top-2 left-2 right-2 z-30 flex items-start mt-4 ml-4 mr-4 justify-between gap-4 md:top-4 md:left-4 md:right-4"
                >

                    <!-- ========================================================= -->
                    <!-- Floor Level -->
                    <!-- ========================================================= -->
                    <!--
                    <div
                        class="rounded-xl border border-blue-500 bg-white/85 px-4 py-2.5 shadow-lg backdrop-blur"
                    >
                        <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">
                            Active Floor
                        </p>

                        <p class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <i
                                data-lucide="map"
                                class="h-4 w-4 text-[#005EA6]"
                            ></i>

                            <span x-text="activeFloorLabel"></span>
                        </p>
                    </div>-->

                    <!-- ========================================================= -->
                    <!-- Action Buttons -->
                    <!-- ========================================================= -->
                    

                        <!-- ===================================== -->
                        <!-- Premium Blueprint Controls -->
                        <!-- ===================================== -->

                        <div
                            x-ref="blueprintControlsDock"
                            class="absolute right-0 top-0 bottom-0 z-30 flex w-12 items-start justify-center border-l border-slate-200/80 bg-slate-50/85 pt-3 sm:w-14 sm:pt-4 md:w-16"
                        >

                            <div
                                class="blueprint-toolbar flex w-12 flex-col overflow-hidden rounded-2xl border border-white/70 bg-white/90 shadow-2xl backdrop-blur-xl"
                            >
                                <!-- ==================== -->
                                <!-- Edit Layout -->
                                <!-- ==================== -->
                                <div class="border-b border-slate-200">

                                    <button
                                        type="button"
                                        @click="toggleBlueprintEdit()"
                                        :data-tooltip="editMode ? null : 'Edit Layout'"
                                        :aria-label="editMode ? 'Exit Edit Mode' : 'Edit Layout'"
                                        :class="editMode
                                            ? 'group/edit-btn bg-[#FFF200] text-slate-900'
                                            : 'hover:bg-slate-100 text-slate-700'"
                                        class="relative flex h-12 w-full items-center justify-center transition"
                                    >

                                        <i
                                            data-lucide="pencil"
                                            class="h-4 w-4 transition-opacity duration-150"
                                            :class="editMode ? 'group-hover/edit-btn:opacity-0' : ''"
                                        ></i>

                                        {{-- Hover X overlay while in edit mode --}}
                                        <span
                                            x-show="editMode"
                                            x-cloak
                                            class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-150 group-hover/edit-btn:opacity-100"
                                            aria-hidden="true"
                                        >
                                            <i data-lucide="x" class="h-5 w-5 text-slate-900"></i>
                                        </span>

                                    </button>

                                </div>

                                <!-- ==================== -->
                                <!-- Save Layout -->
                                <!-- ==================== -->
                                <div
                                    x-show="editMode"
                                    x-transition
                                    class="border-b border-slate-200"
                                >

                                    <button
                                        type="button"
                                        @click="saveLayout()"
                                        :disabled="saving"
                                        :data-tooltip="saving ? 'Saving...' : 'Save Layout'"
                                        :class="saving
                                            ? 'bg-emerald-700 text-white opacity-80'
                                            : saveSuccess
                                                ? 'bg-blue-600 text-white'
                                                : 'hover:bg-slate-100 text-slate-700'"
                                        class="flex h-12 w-full items-center justify-center transition"
                                    >

                                        <svg
                                            x-show="saving"
                                            class="h-4 w-4 animate-spin"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle
                                                class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"
                                            ></circle>

                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0"
                                            ></path>

                                        </svg>

                                        <i
                                            x-show="!saving"
                                            :data-lucide="saveSuccess ? 'check' : 'save'"
                                            class="h-4 w-4"
                                        ></i>

                                    </button>

                                </div>

                                <!-- ==================== -->
                                <!-- Undo Layout Change -->
                                <!-- ==================== -->
                                <div
                                    x-show="editMode"
                                    x-transition
                                    class="border-b border-slate-200"
                                >
                                    <button
                                        type="button"
                                        @click="undoLayoutChange()"
                                        :disabled="!layoutUndoStack.length"
                                        :data-tooltip="layoutUndoStack.length ? 'Undo grouped changes (Ctrl+Z)' : 'Nothing to undo'"
                                        :class="layoutUndoStack.length
                                            ? 'hover:bg-slate-100 text-slate-700'
                                            : 'cursor-not-allowed text-slate-300'"
                                        class="flex h-12 w-full items-center justify-center transition"
                                    >
                                        <i data-lucide="undo-2" class="h-4 w-4"></i>
                                    </button>
                                </div>

                                <!-- ==================== -->
                                <!-- Zoom In -->
                                <!-- ==================== -->

                                <button
                                    type="button"
                                    @click="zoomBlueprint(0.1)"
                                    data-tooltip="Zoom In"
                                    class="flex h-10 w-full items-center justify-center transition hover:bg-slate-100"
                                >

                                    <i
                                        data-lucide="plus"
                                        class="h-4 w-4 transition group-hover:scale-110"
                                    ></i>

                                </button>

                                <!-- ==================== -->
                                <!-- Zoom Percentage -->
                                <!-- ==================== -->

                                <div
                                    class="flex h-12 flex-col items-center justify-center border-y border-slate-200 bg-slate-50"
                                >

                                    <input
                                        x-model="zoomInput"
                                        @focus="$event.target.select()"
                                        @keydown.enter.prevent="applyZoomInput()"
                                        class="w-full bg-transparent text-center text-[13px] font-black leading-none outline-none"
                                    >

                                    <span class="mt-0.5 text-[13px] leading-none text-slate-400">
                                        %
                                    </span>
                                </div>

                                

                                <div class="border-t border-slate-200"></div>

                                <!-- ==================== -->
                                <!-- Zoom Out -->
                                <!-- ==================== -->

                                <button
                                    type="button"
                                    @click="zoomBlueprint(-0.1)"
                                    data-tooltip="Zoom Out"
                                    class="flex h-10 w-full items-center justify-center transition hover:bg-slate-100"
                                >

                                    <i
                                        data-lucide="minus"
                                        class="h-4 w-4 transition group-hover:scale-110"
                                    ></i>

                                </button>

                                <div class="border-t border-slate-200"></div>

                                <!-- ==================== -->
                                <!-- Reset -->
                                <!-- ==================== -->

                                <button
                                    type="button"
                                    @click="resetBlueprintView()"
                                    data-tooltip="Reset View"
                                    class="flex w-full items-center justify-center py-3 hover:bg-slate-100"
                                >

                                    <i
                                        data-lucide="history"
                                        class="h-4 w-4 transition duration-300 group-hover:rotate-180"
                                    ></i>

                                </button>

                                <div
                                    x-show="editMode"
                                    x-transition
                                    class="border-t border-slate-200"
                                >
                                    <button
                                        type="button"
                                        @click="toggleRoomPaintMode()"
                                        :data-tooltip="roomPaintMode ? 'Close Room Paint' : 'Paint Rooms'"
                                        :class="roomPaintMode ? 'bg-[#005EA6] text-white hover:bg-[#004b86]' : 'hover:bg-slate-100 text-slate-700'"
                                        class="flex w-full items-center justify-center py-3 transition"
                                    >
                                        <i data-lucide="paintbrush" class="h-4 w-4"></i>
                                    </button>
                                </div>

                                <div class="border-t border-slate-200"></div>

                                <!-- ==================== -->
                                <!-- Hide / Show Inspector -->
                                <!-- ==================== -->

                                <button
                                    type="button"
                                    @click="toggleDrawer()"
                                    :data-tooltip="drawerOpen ? 'Hide panel' : 'Show panel'"
                                    class="flex w-full items-center justify-center py-3 transition hover:bg-slate-100"
                                    :class="drawerOpen ? 'text-slate-700' : 'bg-[#005EA6] text-white hover:bg-[#004b86]'"
                                >
                                    <span x-show="drawerOpen" class="flex items-center justify-center">
                                        <i data-lucide="panel-right-close" class="h-4 w-4"></i>
                                    </span>
                                    <span x-show="!drawerOpen" class="flex items-center justify-center">
                                        <i data-lucide="panel-right-open" class="h-4 w-4"></i>
                                    </span>
                                </button>

                                <!-- ==================== -->
                                <!-- Fit -->
                                <!-- ==================== -->

                                <button
                                    type="button"
                                    @click="toggleFullscreen()"
                                    :data-tooltip="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"
                                    class="flex w-full items-center justify-center py-3 hover:bg-slate-100"
                                >

                                    <i
                                        x-show="!isFullscreen"
                                        data-lucide="maximize"
                                        class="h-4 w-4"
                                    ></i>

                                    <i
                                        x-show="isFullscreen"
                                        data-lucide="minimize"
                                        class="h-4 w-4"
                                    ></i>

                                </button>

                            </div>

                            <div
                                x-show="editMode && roomPaintMode"
                                x-transition
                                class="absolute top-0 right-full mr-2 w-[calc(100vw-5rem)] max-w-44 rounded-lg border border-slate-200 bg-white/95 p-2 shadow-2xl backdrop-blur-xl sm:w-44"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-[8px] font-extrabold uppercase tracking-[.16em] text-slate-400">Room paint</p>
                                        <h3 class="mt-0.5 text-[11px] font-bold leading-4 text-slate-900">Paint room</h3>
                                    </div>
                                    <span
                                        x-show="selectedRoom"
                                        class="rounded-full border border-slate-200 px-1 py-0.5 text-[9px] font-semibold text-slate-500"
                                        x-text="roomPaintColor || 'No color selected'"
                                    ></span>
                                </div>

                                <div class="mt-2 space-y-2">
                                    <div class="space-y-2">
                                        <div>
                                            <label class="mb-1 block text-[8px] font-bold uppercase tracking-[.14em] text-slate-400">Custom color</label>
                                            <input x-model="roomPaintColor" type="color" class="h-7 w-full cursor-pointer rounded-md border border-slate-200 bg-white p-0.5" />
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-[8px] font-bold uppercase tracking-[.14em] text-slate-400">Quick palette</label>
                                            <div class="grid grid-cols-8 gap-1">
                                                <template x-for="color in roomPaintPresets" :key="color">
                                                    <button
                                                        type="button"
                                                        @click="roomPaintColor = color"
                                                        class="h-6 rounded-md border border-slate-200 shadow-sm transition hover:scale-105"
                                                        :class="roomPaintColor === color ? 'ring-2 ring-[#005EA6] ring-offset-2' : ''"
                                                        :style="`background:${color}`"
                                                        :data-tooltip="color"
                                                    ></button>
                                                </template>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            @click="resetSelectedRoomColor()"
                                            :disabled="!selectedRoom"
                                            class="w-full rounded-md border border-dashed border-slate-300 px-2 py-1 text-[10px] font-semibold text-slate-700 transition hover:border-[#005EA6] hover:bg-blue-50"
                                        >
                                            Reset room color
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                    

                </div>








                <!--<div
                    x-show="editMode"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-[-10px]"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-[-10px]"
                    class="absolute left-1/2 top-3 z-20 -translate-x-1/2 inline-flex items-center rounded-xl border border-slate-800 bg-slate-950/80 px-3 py-2 text-xs font-medium text-slate-200 shadow-xl shadow-black/40 backdrop-blur-md select-none"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex h-2 w-2">
                            <span 
                                x-show="saving" 
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"
                            ></span>
                            
                            <span 
                                class="relative inline-flex rounded-full h-2 w-2 transition-colors duration-300"
                                :class="saving ? 'bg-amber-400' : 'bg-emerald-400'"
                            ></span>
                        </span>

                        <span 
                            x-text="
                            saving
                                ? 'Saving changes...'
                                : layoutDirty
                                    ? 'Unsaved changes'
                                    : 'Editing...'
                            "
                            class="tracking-wide"
                            :class="saving ? 'text-amber-200/90' : 'text-slate-300'"
                        ></span>
                    </div>
                </div>-->

                

                <!-- ========================================= -->
                <!-- Blueprint Viewport -->
                <!-- Fixed viewport -->
                <!-- ========================================= -->

                <div
                    x-ref="blueprintViewport"
                    
                    @wheel="handleBlueprintWheel($event)"
                    @mousedown="startBlueprintPan($event)"
                    @mousemove.window="moveBlueprintPan($event)"
                    @mouseup.window="endBlueprintPan()"
                    @mouseleave="endBlueprintPan()"
                    class="relative min-h-[420px] flex-1 overflow-hidden bg-white xl:min-h-[480px]"
                    :class="isRotating ? 'cursor-grabbing' : blueprint.isPanning ? 'cursor-grabbing' : 'cursor-grab'"
                >
                    <!--bg-gradient-to-br from-[#dbe6f1] via-[#edf3f8] to-[#cbd9e7] for blueprintCanvas-->
                    <div
                        x-ref="blueprintCanvas"
                        class="blueprint-grid absolute left-0 top-0 overflow-hidden rounded-[24px] rounded-tl-none border-2 border-dashed border-slate-300 bg-white"
                        :class="{ 'is-drawer-animating': drawerAnimating }"
                        :style="`

                            width:${blueprint.width}px;

                            height:${blueprint.height}px;

                            transform:

                                translate3d(

                                    ${blueprint.panX}px,

                                    ${blueprint.panY}px,

                                    0

                                )

                                scale(${blueprint.zoom});

                            transform-origin:0 0;

                            will-change:transform;

                        `"
                    >
                        <div
                            class="pointer-events-none absolute left-1/2 top-1/2 h-64 w-[520px] -translate-x-1/2 -translate-y-1/2 rotate-[-8deg] rounded-[50%] border-[24px] border-white/50 bg-sky-100/20"
                        ></div>
                        <div
                            class="pointer-events-none absolute bottom-8 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full bg-white/65 px-4 py-2 text-[10px] font-bold uppercase tracking-[.2em] text-slate-400"
                        >
                            <i data-lucide="navigation" class="h-3 w-3"></i>
                            Central corridor
                        </div>

                        @foreach ($floors as $floor)
                            <div
                                x-show="Number(activeFloor) === {{ $floor->floor_id }}"
                                x-cloak
                                class="absolute inset-0"
                                data-floor-panel="{{ $floor->floor_id }}"
                                @click="
                                    if (!$event.target.closest('.room-block')) {
                                        selectedRoom = null;
                                    }
                                "
                            >
                                @forelse ($rooms->where("room_floor_id", $floor->floor_id) as $room)
                                    @php
                                        $statusColor = match ($room->room_status) {
                                            "Critical" => "#EF4444",
                                            "Maintenance Needed" => "#F59E0B",
                                            default => "#10B981",
                                        };
                                        $labelOrientation = (int) $room->room_height > (int) $room->room_width
                                            ? "vertical"
                                            : "horizontal";
                                        $roomColor = $room->room_color ?: "#60A5FA";
                                        $labelTextColor = "#0f172a";
                                        $hex = ltrim((string) $roomColor, "#");
                                        if (strlen($hex) === 3) {
                                            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                                        }
                                        if (strlen($hex) === 6 && ctype_xdigit($hex)) {
                                            $r = hexdec(substr($hex, 0, 2));
                                            $g = hexdec(substr($hex, 2, 2));
                                            $b = hexdec(substr($hex, 4, 2));
                                            $yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;
                                            $labelTextColor = $yiq >= 150 ? "#0f172a" : "#ffffff";
                                        }
                                    @endphp
                                    <!--ring-[#5B6682]/40-->
                                    <button
                                        type="button"
                                        @click.stop="if (editMode && roomPaintMode) { selectRoomForPaint({{ $room->room_id }}); return; } selectRoom({{ $room->room_id }})"
                                        @dblclick.stop="openRoomInspector({{ $room->room_id }})"
                                        
                                        class="room-block room-card group absolute overflow-visible z-10 rounded-xl border-2 border-white p-3 text-left shadow-[0_14px_22px_rgba(15,23,42,.18)] transition-[box-shadow,filter] duration-200 hover:z-20 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#07319C] {{ $room->room_status === 'Critical' ? 'critical-room' : '' }}"
                                        :class="{'cursor-move ring-2 ring-[#07319C] rounded-lg': editMode, 'ring-2 ring-[#07319C]': selectedRoom === {{ $room->room_id }}}"
                                        data-size="large"
                                        data-label-orientation="{{ $labelOrientation }}"
                                        data-id="{{ $room->room_id }}"
                                        data-floor="{{ $floor->floor_id }}"
                                        data-x="{{ $room->room_x }}"
                                        data-y="{{ $room->room_y }}"
                                        data-width="{{ $room->room_width }}"
                                        data-height="{{ $room->room_height }}"
                                        data-rotation="{{ data_get($room->room_metadata, 'rotation', 0) }}"
                                        data-name="{{ e($room->room_name) }}"
                                        data-type="{{ e($room->room_type ?: 'Room') }}"
                                        data-color="{{ $room->room_color ?: '#60A5FA' }}"
                                        data-assets="{{ $room->equipment->sum("equipment_quantity") }}"
                                        data-active-reports="{{ $room->monitoring["active_reports"] }}"
                                        style="left:{{ $room->room_x }}px;top:{{ $room->room_y }}px;width:{{ $room->room_width }}px;height:{{ $room->room_height }}px;background:{{ $room->room_color ?: '#60A5FA' }};--room-depth:{{ $room->room_color ?: '#60A5FA' }};--room-label-color:{{ $labelTextColor }};transform:rotate({{ data_get($room->room_metadata, 'rotation', 0) }}deg);transform-origin:center center;"
                                    >
                                        <span
                                            class="relative z-10 flex h-full flex-col justify-between"
                                        >
                                            <span class="room-content">
                                                <span
                                                    class="room-name"
                                                    data-full-name="{{ $room->room_name }}"
                                                    data-room-name
                                                >
                                                    {{ $room->room_name }}
                                                </span>

                                                <span
                                                    class="room-status absolute right-0 top-0 h-2.5 w-2.5 rounded-full bg-slate-200"
                                                    style="background: {{ $statusColor }}"
                                                ></span>
                                            </span>
                                        </span>

                                        <span
                                            x-show="!editMode"
                                            x-transition.opacity
                                            role="button"
                                            tabindex="0"
                                            data-tooltip="View room layout"
                                            @click.stop="openRoomLayout({{ $room->room_id }})"
                                            @keydown.enter.stop.prevent="openRoomLayout({{ $room->room_id }})"
                                            class="absolute right-2 top-2 z-40 flex h-7 w-7 items-center justify-center rounded-lg bg-white/85 text-slate-700 opacity-0 shadow-sm ring-1 ring-slate-200 transition hover:bg-white group-hover:opacity-100"
                                        >
                                            <i
                                                data-lucide="more-vertical"
                                                class="h-4 w-4"
                                            ></i>
                                        </span>

                                        {{-- ========================= --}}
                                        {{-- EQUIPMENT VISUALIZATION --}}
                                        {{-- Place AFTER the room content and BEFORE the resize handles --}}
                                        {{-- ========================= --}}

                                        <div
                                            class="absolute inset-0 z-20 overflow-hidden rounded-[inherit]"
                                        ></div>

                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -left-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -right-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -bottom-1.5 -left-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -bottom-1.5 -right-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -top-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -bottom-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -left-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            class="resize-grip pointer-events-none absolute -right-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>

                                        <div
                                            x-show="editMode && selectedRoom === {{ $room->room_id }}"
                                            x-transition.opacity
                                            @pointerdown.stop.prevent="beginRoomRotation($event)"
                                            role="button"
                                            tabindex="0"
                                            aria-label="Rotate selected room"
                                            class="rotate-handle-cursor absolute left-1/2 z-40 flex h-10 w-10 -translate-x-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
                                            :class="rotateHandleSide === 'top' ? 'top-[-52px]' : 'bottom-[-52px]'"
                                            :style="{ transform: 'translateX(-50%) rotate(' + (-selectedRoomControl.rotation) + 'deg)' }"
                                        >
                                            <span x-show="!isRotating" class="flex items-center justify-center">
                                                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                                            </span>
                                            <span x-show="isRotating" class="flex items-center justify-center text-sm font-black leading-none text-slate-900">
                                                <span x-text="Math.round(rotationDisplayAngle) + '°'"></span>
                                            </span>
                                        </div>
                                    </button>
                                @empty
                                    <div
                                        class="absolute inset-0 z-10 flex items-center justify-center"
                                    >
                                        <div
                                            class="max-w-sm rounded-3xl border border-dashed border-slate-300 bg-white/80 p-8 text-center shadow-lg backdrop-blur"
                                        >
                                            <i
                                                data-lucide="scan-line"
                                                class="mx-auto h-10 w-10 text-slate-300"
                                            ></i>
                                            <h3
                                                class="mt-4 font-bold text-slate-800"
                                            >
                                                This floor is a blank canvas
                                            </h3>
                                            <p class="mt-2 text-sm text-slate-500">Use Configure campus to map rooms and their initial assets.</p>
                                        </div>
                                        <template x-if="roomLayout.edit && selectedEquipmentId === item.id">
                                                <span
                                                    class="resize-grip absolute -left-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -right-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -bottom-1.5 -left-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -bottom-1.5 -right-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -top-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -bottom-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -left-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <span
                                                    class="resize-grip absolute -right-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                                ></span>
                                                <div
                                                    x-show="roomLayout.edit && selectedEquipmentId === item.id"
                                                    x-transition.opacity
                                                    @pointerdown.stop.prevent="beginEquipmentRotation($event)"
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="Rotate selected equipment"
                                                    class="rotate-equipment-handle-cursor absolute left-1/2 bottom-[-52px] z-40 flex h-10 w-10 -translate-x-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
                                                    :style="{ transform: 'translateX(-50%) rotate(' + (-selectedEquipmentControl.rotation) + 'deg)' }"
                                                >
                                                    <span x-show="!equipmentIsRotating" class="flex items-center justify-center">
                                                        <i data-lucide="rotate-cw" class="h-4 w-4"></i>
                                                    </span>
                                                    <span x-show="equipmentIsRotating" class="flex items-center justify-center text-sm font-black leading-none text-slate-900">
                                                        <span x-text="Math.round(equipmentRotationDisplayAngle) + '°'"></span>
                                                    </span>
                                                </div>
                                        </template>
                                    </div>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            </div>

            <div
                class="grid w-full min-h-0 flex-1 grid-cols-1 items-stretch"
                :class="drawerOpen
                    ? 'gap-0 xl:grid-cols-1'
                    : 'gap-4 xl:grid-cols-[minmax(0,1fr)_360px]'"
            >
                <div class="flex min-h-0 min-w-0 w-full flex-1 flex-col">
                    @include ("maintenance-personnel.infrastructure.floor-insights")
                </div>

                {{-- Room health rate beside Floor Health Overview when drawer is hidden --}}
                <div
                    class="layout-health-dock flex min-h-0 min-w-0 flex-col self-stretch overflow-hidden"
                    x-show="!drawerOpen"
                    :class="!drawerOpen
                        ? 'max-w-[360px] opacity-100 xl:w-[360px]'
                        : 'is-hidden pointer-events-none'"
                    :aria-hidden="drawerOpen"
                >
                    <div class="flex min-h-0 w-full flex-1 flex-col">
                        @include ("maintenance-personnel.infrastructure.floor-health-panel")
                    </div>
                </div>
            </div>
            </div>

            <div
                class="layout-drawer-shell flex min-w-0 flex-col overflow-hidden"
                :class="drawerOpen ? 'is-open' : 'is-closed'"
                :aria-hidden="!drawerOpen"
            >
                <div class="layout-drawer-pane flex min-h-0 flex-1 flex-col gap-4">
                    @include ("maintenance-personnel.infrastructure.monitor-drawer")

                    {{-- Room health rate under inspector while drawer is open --}}
                    <div class="min-w-0 shrink-0" x-show="drawerOpen">
                        @include ("maintenance-personnel.infrastructure.floor-health-panel")
                    </div>
                </div>
            </div>
        </div>

        @include ("maintenance-personnel.infrastructure.campus-wizard")

        <div
            x-show="roomManager.open"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[1200] flex items-center justify-center bg-[#0b1220]/70 p-4"
            @click.self="closeRoomManager()"
        >
            <div
                class="w-full max-w-lg overflow-hidden rounded-[28px] bg-white shadow-2xl"
            >
                <div
                    class="bg-gradient-to-br from-[#005EA6] to-[#003f71] px-6 py-5 text-white"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p
                                class="text-[10px] font-extrabold uppercase tracking-[.22em] text-white/65"
                            >Layout editor</p>
                            <h2 class="mt-1 text-2xl font-black">
                                Manage room
                            </h2>
                            <p class="mt-1 text-sm text-white/75">Rename the room, or archive a mistaken room and clear its live details.</p>
                        </div>
                        <button
                            type="button"
                            @click="closeRoomManager()"
                            class="rounded-xl bg-white/10 p-2 transition hover:bg-white/20"
                            aria-label="Close room manager"
                        >
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div class="space-y-5 p-6">
                    <div
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <p
                            class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400"
                        >Selected room</p>
                        <p
                            class="mt-1 text-lg font-black text-slate-950"
                            x-text="roomManager.originalName || 'Room'"
                        ></p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            <span x-text="roomManager.type"></span>
                            <span> · </span>
                            <span x-text="roomManager.assets"></span>
                            <span> live assets · </span>
                            <span x-text="roomManager.activeReports"></span>
                            <span> active reports</span>
                        </p>
                    </div>

                    <label class="block">
                        <span
                            class="text-xs font-extrabold uppercase tracking-wider text-slate-500"
                        >
                            Room name
                        </span>
                        <input
                            type="text"
                            x-model="roomManager.name"
                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-900 outline-none transition focus:border-[#005EA6] focus:ring-4 focus:ring-blue-100"
                            placeholder="Example: Comlab 1"
                        />
                    </label>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            @click="renameRoom()"
                            :disabled="saving || !roomManager.name.trim()"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-[#005EA6] px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-900/15 disabled:opacity-50"
                        >
                            <i data-lucide="pencil" class="h-4 w-4"></i>
                            <span
                                x-text="saving ? 'Saving...' : 'Save name'"
                            ></span>
                        </button>
                        <button
                            type="button"
                            @click="closeRoomManager()"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600"
                        >
                            Cancel
                        </button>
                    </div>

                    <div
                        class="rounded-2xl border border-red-100 bg-red-50 p-4"
                    >
                        <div class="flex gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600"
                            >
                                <i data-lucide="archive-x" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-950">
                                    Rooms stay in the building
                                </h3>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Rooms are not archived. Edit type, name, or status instead — changes stay in history.</p>
                            </div>
                        </div>
                        <a
                            :href="`/maintenance/rooms?history=${roomManager.selectedRoomId || ''}`"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[#0025cc] px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/10"
                        >
                            <i data-lucide="history" class="h-4 w-4"></i>
                            Open room history
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="roomLayout.open"
            x-transition.opacity
            x-cloak
            @keydown.escape.window="
                if (!roomLayout.open) return;
                if (roomLayout.fullscreen) {
                    roomLayout.fullscreen = false;
                    $nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    requestCloseRoomLayout();
                }
            "
            class="fixed inset-0 z-[1250] flex items-center justify-center bg-[#0b1220]/70"
            :class="roomLayout.fullscreen ? 'p-0' : 'p-4'"
        >
            <div
                class="flex w-full flex-col overflow-hidden bg-white shadow-2xl"
                :class="roomLayout.fullscreen
                    ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none'
                    : 'h-[92vh] max-h-[92vh] max-w-6xl rounded-2xl'"
            >
                <div
                    class="flex shrink-0 flex-col gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Room interior layout</p>
                            <span
                                x-show="roomLayout.edit"
                                x-cloak
                                class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-100"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Editing
                            </span>
                        </div>
                        <h2
                            class="mt-1 truncate text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl"
                            x-text="roomLayout.name || 'Room layout'"
                        ></h2>
                        <p class="mt-0.5 text-sm text-slate-500" x-text="roomLayoutHint()"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="toggleRoomLayoutEdit()"
                            :class="roomLayout.edit
                                ? 'bg-slate-900 text-white hover:bg-slate-800'
                                : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'"
                            class="inline-flex h-10 items-center gap-2 rounded-xl px-4 text-sm font-medium transition"
                        >
                            <i data-lucide="pencil" class="h-4 w-4"></i>
                            <span x-text="roomLayout.edit ? 'Editing…' : 'Edit layout'"></span>
                        </button>
                        <button
                            type="button"
                            @click="saveLayout()"
                            :disabled="saving || !roomLayout.edit"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Save
                        </button>
                        <div class="ml-1 flex items-center gap-1 border-l border-slate-200 pl-2">
                            <button
                                type="button"
                                @click="toggleRoomLayoutFullscreen()"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                :title="roomLayout.fullscreen ? 'Exit full screen' : 'Full screen'"
                                :aria-label="roomLayout.fullscreen ? 'Exit full screen' : 'Full screen'"
                            >
                                <i :data-lucide="roomLayout.fullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                            </button>
                            <button
                                type="button"
                                @click="requestCloseRoomLayout()"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                aria-label="Close room layout"
                            >
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="grid min-h-0 flex-1 grid-rows-[minmax(0,1fr)] gap-0 overflow-hidden bg-slate-50 lg:grid-cols-[minmax(0,1fr)_320px]"
                >
                    <div x-show="!isComlabRoomLayout()" class="h-full min-h-0 min-w-0">
                    <div
                        x-ref="roomInteriorCanvas"
                        class="room-interior-grid relative m-4 h-[calc(100%-2rem)] min-h-0 min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                        @pointerdown="if (roomLayout.edit) { selectEquipment(null); clearLayoutSelection(); }"
                    >
                        <div
                            class="pointer-events-none absolute inset-x-20 top-4 rounded-full border border-dashed border-slate-300 px-4 py-1 text-center text-[10px] font-black uppercase tracking-[.2em] text-slate-400"
                        >
                            Front wall / board
                        </div>
                        <div
                            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 rounded-lg bg-slate-100/80 px-2 py-6 text-[9px] font-black uppercase tracking-[.18em] text-slate-400 [writing-mode:vertical-rl]"
                        >
                            Left row pods
                        </div>
                        <div
                            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-lg bg-slate-100/80 px-2 py-6 text-[9px] font-black uppercase tracking-[.18em] text-slate-400 [writing-mode:vertical-rl]"
                        >
                            Right row pods
                        </div>
                        <div
                            class="pointer-events-none absolute inset-x-28 top-[46%] text-center text-[9px] font-black uppercase tracking-[.2em] text-slate-300"
                        >
                            Center ceiling
                        </div>

                        <template x-if="roomLayout.equipment.length === 0">
                            <div
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <div
                                    class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
                                >
                                    <i
                                        data-lucide="package-open"
                                        class="mx-auto h-10 w-10 text-slate-300"
                                    ></i>
                                    <p class="mt-3 text-sm font-bold text-slate-500">No equipment yet for this room.</p>
                                </div>
                            </div>
                        </template>

                        <template
                            x-for="group in layoutGroups()"
                            :key="group.key"
                        >
                            <div
                                class="room-equipment-node absolute z-20 flex min-w-0 items-center gap-2 overflow-visible rounded-2xl border-2 border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-md hover:border-[#07319C]"
                                :class="{
                                    'ring-2 ring-[#07319C]/80 cursor-move':
                                        roomLayout.edit &&
                                        selectedEquipmentId !== group.primaryId,

                                    'ring-2 ring-[#07319C] cursor-move':
                                        roomLayout.edit &&
                                        selectedEquipmentId === group.primaryId,

                                    'ring-2 ring-emerald-500':
                                        !roomLayout.edit &&
                                        roomLayout.selectedGroupKey === group.key,

                                    'cursor-move':
                                        equipmentAction &&
                                        equipmentAction.type === 'drag' &&
                                        selectedEquipmentId === group.primaryId
                                }"
                                :data-equipment-id="group.primaryId"
                                :data-group-ids="group.ids.join(',')"
                                :data-group-key="group.key"
                                :data-x="group.x"
                                :data-y="group.y"
                                :data-width="group.width || 140"
                                :data-height="group.height || 96"
                                :data-rotation="group.rotation || 0"
                                :data-zone="group.zone"
                                :data-label-orientation="(group.height || 96) > (group.width || 140) ? 'vertical' : 'horizontal'"
                                @pointerdown.stop="
                                    openLayoutGroup(group.key);
                                    handleEquipmentPointerDown($event, group.primaryId);
                                "
                                :style="`
                                    left:${group.x}%;
                                    top:${group.y}%;
                                    width:${group.width || 140}px;
                                    height:${group.height || 96}px;
                                    touch-action:none;
                                    transform:translate(-50%,-50%) rotate(${(equipmentIsRotating && selectedEquipmentId === group.primaryId && equipmentLiveRotation != null) ? equipmentLiveRotation : (group.rotation || 0)}deg);
                                    transform-origin:center center;
                                    will-change:left,top,transform;
                                `"
                            >
                                <span
                                    class="equipment-label relative z-10 flex h-full w-full min-w-0 items-center gap-2 overflow-hidden"
                                >
                                    <span
                                        class="equipment-icon shrink-0 text-lg"
                                        x-html="equipmentIcon(group.name)"
                                    ></span>

                                    <span class="equipment-copy flex min-w-0 flex-col leading-tight">
                                        <span
                                            class="equipment-name truncate"
                                            x-text="group.label"
                                        ></span>

                                        <span
                                            class="equipment-meta truncate text-[10px] font-semibold text-slate-400"
                                            x-text="group.zone || 'No zone'"
                                        ></span>
                                    </span>
                                </span>

                                <!-- Resize Handles -->
                                <template x-if="roomLayout.edit && selectedEquipmentId === group.primaryId">

                                    <div>

                                        <span
                                            class="resize-grip absolute -left-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#0b00a6] bg-white cursor-nwse-resize"
                                            data-handle-x="left"
                                            data-handle-y="top"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -right-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nesw-resize"
                                            data-handle-x="right"
                                            data-handle-y="top"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -bottom-1.5 -left-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nesw-resize"
                                            data-handle-x="left"
                                            data-handle-y="bottom"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -bottom-1.5 -right-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nwse-resize"
                                            data-handle-x="right"
                                            data-handle-y="bottom"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -top-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ns-resize"
                                            data-handle-x="center"
                                            data-handle-y="top"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -bottom-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ns-resize"
                                            data-handle-x="center"
                                            data-handle-y="bottom"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -left-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ew-resize"
                                            data-handle-x="left"
                                            data-handle-y="center"
                                        ></span>

                                        <span
                                            class="resize-grip absolute -right-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ew-resize"
                                            data-handle-x="right"
                                            data-handle-y="center"
                                        ></span>

                                    </div>

                                </template>

                                <!-- Rotate Handle -->
                                <template x-if="roomLayout.edit && selectedEquipmentId === group.primaryId">

                                    <div
                                        class="equipment-rotate-gimbal pointer-events-none absolute left-1/2 top-1/2 z-40"
                                        :style="equipmentRotateGimbalStyle(group)"
                                    >

                                        <button
                                            type="button"
                                            @pointerdown.stop.prevent="beginEquipmentRotation($event, group.primaryId)"
                                            aria-label="Rotate selected equipment"
                                            class="rotate-equipment-handle-cursor pointer-events-auto absolute left-1/2 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
                                            :style="equipmentRotateHandleStyle(group)"
                                        >

                                            <span
                                                x-show="!equipmentIsRotating"
                                                x-init="$nextTick(() => window.lucide?.createIcons())"
                                                class="flex items-center justify-center"
                                            >
                                                <i
                                                    data-lucide="refresh-cw"
                                                    class="h-4 w-4"
                                                ></i>
                                            </span>

                                            <span
                                                x-show="equipmentIsRotating"
                                                class="flex items-center justify-center text-sm font-black leading-none text-slate-900"
                                            >
                                                <span
                                                    x-text="Math.round(equipmentRotationDisplayAngle) + '°'"
                                                ></span>
                                            </span>

                                        </button>

                                    </div>

                                </template>

                            </div>
                        </template>
                        
                    </div>
                    </div>

                    <div
                        x-show="isComlabRoomLayout()"
                        x-cloak
                        class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden p-4"
                    >
                        @include('maintenance-personnel.infrastructure.partials.comlab-layout-canvas')
                    </div>

                    <div x-show="!isComlabRoomLayout()" class="min-h-0 min-w-0 h-full">
                    <aside
                        class="flex h-full min-h-0 flex-col border-l border-slate-200 bg-white"
                    >
                        <template x-if="selectedLayoutAsset()">
                            <div class="flex min-h-0 flex-1 flex-col">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <button
                                        type="button"
                                        @click="roomLayout.selectedAssetId = null; roomLayout.lifecycle = { loading: false, data: null, equipmentId: null }; if (!(selectedLayoutGroup() && selectedLayoutGroup().quantity > 1)) { clearLayoutSelection(); }"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-slate-900"
                                    >
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                        <span x-text="(selectedLayoutGroup() && selectedLayoutGroup().quantity > 1) ? 'Back to group' : 'All groups'"></span>
                                    </button>
                                    <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Asset details</p>
                                    <h3 class="mt-1 truncate text-base font-semibold text-slate-900" x-text="selectedLayoutAsset()?.asset_tag || selectedLayoutAsset()?.name"></h3>
                                </div>
                                @include('maintenance-personnel.infrastructure.partials.layout-asset-details')
                            </div>
                        </template>

                        <template x-if="!selectedLayoutAsset() && selectedLayoutGroup() && selectedLayoutGroup().quantity > 1">
                            <div class="flex min-h-0 flex-1 flex-col">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <button
                                        type="button"
                                        @click="clearLayoutSelection()"
                                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-slate-900"
                                    >
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                        All groups
                                    </button>
                                    <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400" x-text="selectedLayoutGroup()?.name"></p>
                                    <h3 class="mt-1 text-base font-semibold text-slate-900">
                                        <span x-text="selectedLayoutGroup()?.quantity"></span> assets
                                    </h3>
                                    <p class="mt-0.5 text-xs text-slate-500" x-text="selectedLayoutGroup()?.zone || 'No zone'"></p>
                                    <div class="relative mt-3">
                                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                                        <input
                                            type="search"
                                            x-model="roomLayout.listSearch"
                                            @input="roomLayout.listPage = 1"
                                            placeholder="Search asset tag / serial"
                                            class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-9 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                        />
                                    </div>
                                </div>
                                <div class="min-h-0 flex-1 space-y-1.5 overflow-y-auto px-3 py-3">
                                    <template x-for="asset in pagedGroupAssets()" :key="'asset-' + asset.id">
                                        <button
                                            type="button"
                                            @click="selectLayoutAsset(asset.id)"
                                            class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50"
                                        >
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-800" x-text="asset.asset_tag || asset.name"></p>
                                                <p class="truncate text-[11px] text-slate-400" x-text="asset.serial_number || 'No serial'"></p>
                                            </div>
                                            <span
                                                class="ml-2 inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-semibold"
                                                :class="
                                                    (asset.condition || '') === 'Good'
                                                        ? 'bg-emerald-50 text-emerald-700'
                                                        : (asset.condition || '') === 'Damaged'
                                                            ? 'bg-rose-50 text-rose-700'
                                                            : 'bg-slate-100 text-slate-600'
                                                "
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                                <span x-text="asset.condition || '—'"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500" x-show="groupAssetPageCount() > 1">
                                    <button type="button" class="rounded-lg px-2 py-1 hover:bg-slate-100 disabled:opacity-40" :disabled="roomLayout.listPage <= 1" @click="roomLayout.listPage = Math.max(1, roomLayout.listPage - 1)">Prev</button>
                                    <span x-text="roomLayout.listPage + ' / ' + groupAssetPageCount()"></span>
                                    <button type="button" class="rounded-lg px-2 py-1 hover:bg-slate-100 disabled:opacity-40" :disabled="roomLayout.listPage >= groupAssetPageCount()" @click="roomLayout.listPage = Math.min(groupAssetPageCount(), roomLayout.listPage + 1)">Next</button>
                                </div>
                            </div>
                        </template>

                        <template x-if="!selectedLayoutAsset() && !(selectedLayoutGroup() && selectedLayoutGroup().quantity > 1)">
                            <div class="flex min-h-0 flex-1 flex-col">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Equipment</p>
                                            <p class="mt-1 text-sm text-slate-500">Select a group, then an asset.</p>
                                        </div>
                                        <span
                                            class="inline-flex shrink-0 items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"
                                            x-text="layoutGroups().length + (layoutGroups().length === 1 ? ' group' : ' groups')"
                                        ></span>
                                    </div>
                                    <div class="relative mt-3">
                                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                                        <input
                                            type="search"
                                            x-model="roomLayout.listSearch"
                                            @input="roomLayout.listPage = 1"
                                            placeholder="Search equipment / zone"
                                            class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-9 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                        />
                                    </div>
                                </div>
                                <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 py-2">
                                    <template
                                        x-for="group in pagedLayoutGroups()"
                                        :key="'list-' + group.key"
                                    >
                                        <button
                                            type="button"
                                            @click="openLayoutGroup(group.key); selectEquipment(group.primaryId)"
                                            class="flex w-full items-start gap-3 rounded-xl px-3 py-2.5 text-left transition"
                                            :class="roomLayout.selectedGroupKey === group.key
                                                ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20'
                                                : 'hover:bg-slate-50'"
                                        >
                                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base">
                                                <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(group.name)"></span>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="truncate text-sm font-semibold text-slate-900" x-text="group.label"></span>
                                                    <span
                                                        x-show="group.quantity > 1"
                                                        class="shrink-0 rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500"
                                                        x-text="'×' + group.quantity"
                                                    ></span>
                                                </span>
                                                <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500">
                                                    <span class="inline-flex items-center gap-1">
                                                        <i data-lucide="map-pin" class="h-3 w-3 text-slate-400"></i>
                                                        <span x-text="group.zone || 'Not assigned'"></span>
                                                    </span>
                                                    <template x-if="group.quantity === 1">
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 font-medium"
                                                            :class="
                                                                (group.members[0]?.condition || '') === 'Good'
                                                                    ? 'bg-emerald-50 text-emerald-700'
                                                                    : (group.members[0]?.condition || '') === 'Damaged'
                                                                        ? 'bg-rose-50 text-rose-700'
                                                                        : 'bg-slate-100 text-slate-600'
                                                            "
                                                            x-text="group.members[0]?.condition || 'Unknown'"
                                                        ></span>
                                                    </template>
                                                </span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500" x-show="layoutGroupPageCount() > 1">
                                    <button type="button" class="rounded-lg px-2 py-1 hover:bg-slate-100 disabled:opacity-40" :disabled="roomLayout.listPage <= 1" @click="roomLayout.listPage = Math.max(1, roomLayout.listPage - 1)">Prev</button>
                                    <span x-text="roomLayout.listPage + ' / ' + layoutGroupPageCount()"></span>
                                    <button type="button" class="rounded-lg px-2 py-1 hover:bg-slate-100 disabled:opacity-40" :disabled="roomLayout.listPage >= layoutGroupPageCount()" @click="roomLayout.listPage = Math.min(layoutGroupPageCount(), roomLayout.listPage + 1)">Next</button>
                                </div>
                            </div>
                        </template>
                    </aside>
                    </div>

                    <div
                        x-show="isComlabRoomLayout()"
                        x-cloak
                        class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden"
                    >
                        @include('maintenance-personnel.infrastructure.partials.comlab-layout-sidebar')
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================================== -->
        <!-- Discard Layout Changes Modal -->
        <!-- Place BELOW Room Layout Modal -->
        <!-- ===================================== -->

        <div
            x-show="closeLayoutModal.open"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[1300] flex items-center justify-center bg-[#0b1220]/70 p-4"
        >

            <div
                @click.stop
                class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl"
            >

                <div class="relative overflow-hidden bg-white">

                    <!-- ===================================================== -->
                    <!-- BACKGROUND WARNING ICON -->
                    <!-- ===================================================== -->
                    <div
                        class="
                            pointer-events-none
                            absolute
                            -bottom-12
                            -left-12
                            select-none
                            text-slate-100
                        "
                        aria-hidden="true"
                    >
                        <i
                            data-lucide="badge-alert"
                            class="h-60 w-60 stroke-[1.5]"
                        ></i>
                    </div>


                    <!-- ===================================================== -->
                    <!-- MODAL CONTENT -->
                    <!-- ===================================================== -->
                    <div class="relative z-10 px-6 pb-5 pt-6">

                        <button
                            type="button"
                            @click="closeLayoutModal.open = false"
                            class="
                                absolute right-5 top-5
                                flex h-8 w-8
                                items-center justify-center
                                rounded-lg
                                text-slate-300
                                transition
                                hover:bg-slate-100
                                hover:text-slate-600
                            "
                        >
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>

                        <h2
                            class="pr-10 text-xl font-semibold text-slate-800"
                            x-text="closeLayoutModal.title"
                        ></h2>

                        <p
                            class="mt-2 max-w-md pr-8 text-sm leading-6 text-slate-600"
                            x-text="closeLayoutModal.message"
                        ></p>

                    </div>


                    <!-- ===================================================== -->
                    <!-- MODAL ACTIONS -->
                    <!-- ===================================================== -->
                    <div class="relative z-10 flex justify-end gap-3 px-6 pb-6">

                        <button
                            type="button"
                            @click="closeLayoutModal.open = false"
                            class="
                                min-w-[100px]
                                rounded-md
                                bg-slate-300
                                px-5 py-2.5
                                text-sm font-medium
                                text-white
                                transition
                                hover:bg-slate-400
                            "
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            @click="
                                closeLayoutModal.open = false;
                                if (layoutDirty && originalRoomLayout) {
                                    roomLayout.equipment = JSON.parse(JSON.stringify(originalRoomLayout));
                                    if (originalComlabRows) {
                                        roomLayout.comlabRows = JSON.parse(JSON.stringify(originalComlabRows));
                                    }
                                    if (originalComlabRowLayouts) {
                                        roomLayout.comlabRowLayouts = JSON.parse(JSON.stringify(originalComlabRowLayouts));
                                    }
                                    layoutDirty = false;
                                }
                                closeRoomLayout();
                            "
                            class="
                                min-w-[50px]
                                rounded-md
                                bg-red-600
                                px-5 py-2.5
                                text-sm font-medium
                                text-white
                                transition
                                hover:bg-red-700
                            "
                        >
                            OK
                        </button>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================== -->
        <!-- Blueprint Layout Confirmation -->
        <!-- Place below the existing modal -->
        <!-- ===================================== -->

        <div
            x-show="blueprintLayoutModal.open"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[1301] flex items-center justify-center bg-[#0b1220]/70 p-4"
        >

            <div
                @click.stop
                class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl"
            >

                <!-- ========================================================= -->
                <!-- BLUEPRINT DISCARD CONFIRMATION MODAL CONTENT -->
                <!-- ========================================================= -->

                <div
                    class="
                        relative
                        overflow-hidden
                        border border-white/50
                        bg-white/70
                        shadow-2xl
                        backdrop-blur-xl
                    "
                >

                    <!-- ===================================================== -->
                    <!-- BACKGROUND WARNING ICON -->
                    <!-- ===================================================== -->
                    <div
                        class="
                            pointer-events-none
                            absolute
                            -bottom-12
                            -left-12
                            select-none
                            text-slate-200/50
                        "
                        aria-hidden="true"
                    >
                        <i
                            data-lucide="badge-alert"
                            class="h-64 w-64 stroke-[1]"
                        ></i>
                    </div>


                    <!-- ===================================================== -->
                    <!-- MODAL CONTENT -->
                    <!-- ===================================================== -->
                    <div class="relative z-10 px-6 pb-5 pt-6">

                        <!-- Close Button -->
                        <button
                            type="button"
                            @click="blueprintLayoutModal.open = false"
                            class="
                                absolute
                                right-5
                                top-5
                                flex
                                h-8
                                w-8
                                items-center
                                justify-center
                                rounded-lg
                                text-slate-300
                                transition
                                hover:bg-slate-100/70
                                hover:text-slate-600
                            "
                        >
                            <i
                                data-lucide="x"
                                class="h-5 w-5"
                            ></i>
                        </button>


                        <!-- Title -->
                        <h2
                            class="
                                pr-10
                                text-xl
                                font-semibold
                                text-slate-800
                            "
                            x-text="blueprintLayoutModal.title"
                        ></h2>


                        <!-- Message -->
                        <p
                            class="
                                mt-2
                                max-w-md
                                pr-8
                                text-sm
                                leading-6
                                text-slate-600
                            "
                            x-text="blueprintLayoutModal.message"
                        ></p>

                    </div>


                    <!-- ===================================================== -->
                    <!-- MODAL ACTIONS -->
                    <!-- ===================================================== -->
                    <div
                        class="
                            relative
                            z-10
                            flex
                            justify-end
                            gap-3
                            px-6
                            pb-6
                        "
                    >

                        <!-- Continue Editing -->
                        <button
                            type="button"
                            @click="blueprintLayoutModal.open = false"
                            class="
                                rounded-md
                                bg-slate-300
                                px-5
                                py-2.5
                                text-sm
                                font-medium
                                text-white
                                transition
                                hover:bg-slate-400
                            "
                        >
                            Cancel
                        </button>


                        <!-- Discard Changes -->
                        <button
                            type="button"
                            @click="discardBlueprintChanges()"
                            class="
                                rounded-md
                                bg-red-600
                                px-5
                                py-2.5
                                text-sm
                                font-medium
                                text-white
                                shadow-sm
                                transition
                                hover:bg-red-700
                            "
                        >
                            OK
                        </button>

                    </div>

                </div>

            </div>

        </div>

        <div
            x-show="toast"
            x-transition
            x-cloak
            class="mp-toast mp-toast-inline fixed bottom-5 right-5 z-[1100] is-visible"
        >
            <div class="mp-toast-top">
                <div class="mp-toast-brand">
                    <span class="mp-toast-brand-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </span>
                    <span class="mp-toast-brand-name">PaAyo</span>
                </div>
                <div class="mp-toast-actions">
                    <button
                        type="button"
                        class="mp-toast-close"
                        aria-label="Dismiss"
                        @click="toast = ''"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"/>
                            <path d="m6 6 12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <p class="mp-toast-title" x-text="toast"></p>
            <p class="mp-toast-message">Campus infrastructure</p>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Folder-style floor tabs overlapping the layout viewport */
        .floor-tabs-bar {
            scrollbar-width: none;
        }

        .floor-tabs-bar::-webkit-scrollbar {
            display: none;
        }

        .floor-tab {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4375rem 0.75rem 0.5rem;
            border: 1px solid #dbe3ee;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            cursor: pointer;
            background: #eef2f7;
            color: #64748b;
        }

        .floor-tab__label {
            letter-spacing: -0.01em;
        }

        .floor-tab__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.125rem;
            height: 1.125rem;
            padding: 0 0.25rem;
            border-radius: 999px;
            background: rgba(100, 116, 139, 0.1);
            font-size: 10px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: #64748b;
        }

        .floor-tab--inactive:hover {
            background: #e5ebf3;
            color: #334155;
        }

        .floor-tab--inactive:hover .floor-tab__count {
            background: rgba(100, 116, 139, 0.16);
            color: #475569;
        }

        .floor-tab--active {
            z-index: 2;
            margin-bottom: -1px;
            padding-bottom: calc(0.5rem + 1px);
            background: #fff;
            color: #0f172a;
            font-weight: 700;
            border-color: #cbd5e1;
        }

        .floor-tab--active .floor-tab__count {
            background: #e8f1ff;
            color: #005ea6;
        }

        .floor-tab--active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 2px;
            background: #fff;
        }

        /* Infrastructure monitor: page scrolls; no nested scroll in buildings layout */
        body.mp-layout:has([data-infrastructure-monitor]) main {
            overflow-y: auto;
            overflow-x: hidden;
        }

        body.mp-layout:has([data-infrastructure-monitor]) main::-webkit-scrollbar {
            width: 6px;
        }

        body.mp-layout:has([data-infrastructure-monitor]) main::-webkit-scrollbar-track {
            background: transparent;
        }

        body.mp-layout:has([data-infrastructure-monitor]) main::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        [data-floor-insights] .floor-insights-scroll::-webkit-scrollbar {
            width: 5px;
        }

        [data-floor-insights] .floor-insights-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        [data-floor-insights] .floor-insights-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.5);
            border-radius: 999px;
        }

        [data-floor-insights] .floor-insights-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.6);
        }

        /* Workspace: CSS grid so the canvas column always yields real width to
           the inspector. Flex let the blueprint spill under the drawer when
           reopening from the wide (hidden) state. */
        .layout-workspace {
            display: flex;
            flex-direction: column;
            min-width: 0;
            gap: 0;
            transition: gap 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .layout-workspace.is-drawer-open {
            gap: 0;
        }

        @media (min-width: 1280px) {
            .layout-workspace {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: stretch;
                gap: 0;
                column-gap: 0;
            }

            .layout-workspace.is-drawer-open {
                column-gap: 1.5rem;
            }
        }

        /* Drawer width drives the auto grid track; main (1fr) shrinks with it. */
        .layout-drawer-shell {
            --drawer-width: 100%;
            width: var(--drawer-width);
            min-width: 0;
            max-width: none;
            overflow: hidden;
            pointer-events: auto;
            transition: width 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        @media (min-width: 1280px) {
            .layout-drawer-shell {
                --drawer-width: min(460px, max(360px, 22vw));
            }

            .layout-drawer-shell.is-open {
                min-height: calc(100vh - 10rem - 20px);
            }
        }

        .layout-drawer-shell.is-closed {
            width: 0;
            max-width: 0;
            height: 0;
            min-height: 0;
            overflow: hidden;
            pointer-events: none;
            border: 0;
            padding: 0;
            margin: 0;
        }

        .layout-drawer-pane {
            width: var(--drawer-width);
            min-width: var(--drawer-width);
            max-width: var(--drawer-width);
            flex-shrink: 0;
        }

        /* While collapsed, kill the pane's intrinsic min width so the auto
           grid track can stay at 0 instead of reserving the open drawer size. */
        .layout-drawer-shell.is-closed .layout-drawer-pane {
            width: 0;
            min-width: 0;
            max-width: 0;
        }

        .layout-monitor-main {
            min-width: 0;
            overflow: hidden;
        }

        .layout-health-dock {
            align-self: stretch;
            transition:
                width 300ms cubic-bezier(0.22, 1, 0.36, 1),
                max-width 300ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 200ms ease;
        }

        .layout-health-dock:not(.is-hidden) > * {
            flex: 1 1 0%;
            min-height: 0;
        }

        .layout-health-dock.is-hidden {
            pointer-events: none;
        }

        .blueprint-grid.is-drawer-animating {
            transition: transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .blueprint-grid {
            background-image:
                linear-gradient(rgba(100, 116, 139, 0.12) 1px, transparent 1px),
                linear-gradient(
                    90deg,
                    rgba(100, 116, 139, 0.12) 1px,
                    transparent 1px
                );
            background-size: 20px 20px;
        }
        /*.room-card:before {
            content: "";
            position: absolute;
            left: 10px;
            right: -9px;
            bottom: -10px;
            height: 12px;
            background: yellow;
            clip-path: polygon(0 0, 100% 0, 91% 100%, 7% 100%);
            border-radius: 0 0 5px 5px;
        }
        .room-card:after {
            content: "";
            position: absolute;
            top: 9px;
            right: -10px;
            bottom: -8px;
            width: 12px;
            background: yellow;
            clip-path: polygon(0 0, 100% 9%, 100% 92%, 0 100%);
        }*/
        @keyframes criticalPulse {
            0%,
            100% {
                box-shadow:
                    0 14px 22px rgba(15, 23, 42, 0.18),
                    0 0 0 0 rgba(239, 68, 68, 0.45);
            }
            50% {
                box-shadow:
                    0 14px 22px rgba(15, 23, 42, 0.18),
                    0 0 0 8px rgba(239, 68, 68, 0);
            }
        }
        /* ============================== */
        /* Equipment Icons */
        /* ============================== */

        .equipment-node {
            cursor: grab;

            user-select: none;

            transition: 0.15s;

            z-index: 50;
        }

        .equipment-node:hover {
            transform: scale(1.1);
        }

        .equipment-node:active {
            cursor: grabbing;

            transform: scale(1.05);
        }
        .room-equipment-node{

            touch-action:none;

            user-select:none;

            will-change:left,top;

            backface-visibility:hidden;

            transform-style:preserve-3d;

            transition:box-shadow .15s ease;

        }

        .room-equipment-node.dragging{

            transition:none;

            z-index:9999;

        }
        .room-equipment-node:hover {
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.18);
        }
        .room-equipment-node[data-label-orientation="vertical"] {
            padding-left: 2px;
            padding-right: 2px;
        }
        .room-equipment-node[data-label-orientation="vertical"] .equipment-label {
            flex-direction: column;
            justify-content: center;
            gap: 0.25rem;
        }
        .room-equipment-node[data-label-orientation="vertical"] .equipment-copy {
            align-items: center;
            width: auto;
            max-width: 100%;
            height: 100%;
            max-height: 100%;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            overflow: hidden;
        }
        .room-equipment-node[data-label-orientation="vertical"] .equipment-name,
        .room-equipment-node[data-label-orientation="vertical"] .equipment-meta {
            max-width: none;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .room-equipment-node.is-compact .equipment-icon {
            display: none;
        }
        .resize-grip {
            touch-action: none;
            pointer-events: auto;
        }
        .room-interior-grid {
            background-image:
                linear-gradient(rgba(100, 116, 139, 0.12) 1px, transparent 1px),
                linear-gradient(
                    90deg,
                    rgba(100, 116, 139, 0.12) 1px,
                    transparent 1px
                );
            background-size: 24px 24px;
        }
        .rotate-handle-cursor,
        .rotate-active-cursor,
        body.rotate-active-cursor,
        .rotate-equipment-handle-cursor,
        body.equipment-rotate-active-cursor {
            cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Cg fill='none' stroke='%23ffffff' stroke-width='4.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9.4 11.4a7.6 7.6 0 0 1 13.6 3.4'/%3E%3Cpath d='M22.6 20.6a7.6 7.6 0 0 1-13.6-3.4'/%3E%3Cpath d='M22.8 8.4v5.4h-5.4'/%3E%3Cpath d='M9.2 23.6v-5.4h5.4'/%3E%3C/g%3E%3Cg fill='none' stroke='%230F172A' stroke-width='2.1' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9.4 11.4a7.6 7.6 0 0 1 13.6 3.4'/%3E%3Cpath d='M22.6 20.6a7.6 7.6 0 0 1-13.6-3.4'/%3E%3Cpath d='M22.8 8.4v5.4h-5.4'/%3E%3Cpath d='M9.2 23.6v-5.4h5.4'/%3E%3C/g%3E%3Ccircle cx='16' cy='16' r='2.1' fill='%230F172A' stroke='%23ffffff' stroke-width='1.4'/%3E%3C/svg%3E") 16 16, crosshair !important;
        }
        .room-search-highlight {
            animation: roomSearchPulse 1.2s ease-in-out 3;
        }
        @keyframes roomSearchPulse {
            0%,
            100% {
                box-shadow:
                    0 14px 22px rgba(15, 23, 42, 0.18),
                    0 0 0 0 rgba(255, 242, 0, 0.9);
            }
            50% {
                box-shadow:
                    0 18px 28px rgba(15, 23, 42, 0.24),
                    0 0 0 14px rgba(255, 242, 0, 0);
            }
        }
        .critical-room {
            animation: criticalPulse 1.8s ease-in-out infinite;
        }

        /* =======================================
        Responsive Room Name
        ======================================= */

        .room-content {
            width: 100%;
            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;
        }

        .room-name {
            width: 100%;

            text-align: center;

            font-weight: 700;

            color: var(--room-label-color, #0f172a);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

            user-select: none;

            pointer-events: none;

            transition: font-size 0.15s ease;
        }

        .room-block[data-label-orientation="vertical"] {
            padding-left: 2px;
            padding-right: 2px;
        }

        .room-block[data-label-orientation="vertical"] .room-name {
            width: auto;
            max-width: 100%;
            height: 100%;
            max-height: 100%;
            writing-mode: vertical-rl;
            text-orientation: mixed;
            line-height: 1.05;
        }

        .room-block[data-label-orientation="vertical"] .room-status {
            right: 1px;
            top: 1px;
        }

        /* ---------- LARGE ---------- */

        .room-block[data-size="large"] .room-name {
            font-size: 14px;
        }

        /* ---------- MEDIUM ---------- */

        .room-block[data-size="medium"] .room-name {
            font-size: 12px;
        }

        /* ---------- SMALL ---------- */

        .room-block[data-size="small"] .room-name {
            font-size: 10px;
        }

        /* ---------- TINY ---------- */

        .room-block[data-size="tiny"] .room-name {
            font-size: 9px;
        }

        .room-block {
            --edge-left: 0;
            --edge-right: 0;
            --edge-top: 0;
            --edge-bottom: 0;
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
            backface-visibility: hidden;
            border-color: rgba(255, 255, 255, 0.96);
        }

        .room-block::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            z-index: 15;
            box-shadow:
                inset 0 0 0 1px rgba(15, 23, 42, 0.16),
                inset 3px 0 0 0 rgba(255, 255, 255, var(--edge-left)),
                inset 4px 0 0 0 rgba(15, 23, 42, calc(var(--edge-left) * 0.32)),
                inset -3px 0 0 0 rgba(255, 255, 255, var(--edge-right)),
                inset -4px 0 0 0 rgba(15, 23, 42, calc(var(--edge-right) * 0.32)),
                inset 0 3px 0 0 rgba(255, 255, 255, var(--edge-top)),
                inset 0 4px 0 0 rgba(15, 23, 42, calc(var(--edge-top) * 0.32)),
                inset 0 -3px 0 0 rgba(255, 255, 255, var(--edge-bottom)),
                inset 0 -4px 0 0 rgba(15, 23, 42, calc(var(--edge-bottom) * 0.32));
        }

        .room-block[data-edge-left="true"] {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .room-block[data-edge-right="true"] {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .room-block[data-edge-top="true"] {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .room-block[data-edge-bottom="true"] {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .room-block.is-dragging,
        .room-block.is-resizing {
            transition: none !important;
            z-index: 80 !important;
            will-change: transform, width, height, left, top;
        }

        .room-block.is-dragging {
            cursor: grabbing !important;
            box-shadow: 0 22px 40px rgba(15, 23, 42, 0.28);
            filter: brightness(1.03);
        }


        /* =======================================
        Premium Blueprint Toolbar
        Place at the bottom of <style>
        ======================================= */

        .blueprint-toolbar{

            animation:toolbarFloat .35s ease;

        }

        @keyframes toolbarFloat{

            from{

                opacity:0;

                transform:translateY(-12px);

            }

            to{

                opacity:1;

                transform:translateY(0);

            }

        }
    </style>

    @push ("scripts")
        @include('layouts.partials.equipment-asset-tag')
        @include('layouts.partials.equipment-layout-icons')
        <script>
            function infrastructureMonitor(initialFloor) {
                return {
                    activeFloor: initialFloor || null,
                    selectedRoom: null,
                    drawerOpen: true,
                    drawerAnimating: false,
                    drawerAnimFrame: 0,
                    drawerAnimTimer: 0,
                    // Double-tap stamp for opening the drawer (survives edit drag/resize).
                    roomTapStamp: { id: null, at: 0 },
                    fitBlueprintRaf: 0,
                    editMode: false,
                    saving: false,
                    
                    saveSuccess:false,

                    layoutDirty:false,
                    originalBlueprintLayout: [],
                    layoutUndoStack: [],
                    layoutUndoLastAt: 0,
                    layoutUndoOpenedGroup: false,
                    roomDrag: null,
                    roomResize: null,
                    equipmentFallbackBound: false,
                    equipmentDrag: null,
                    equipmentAction: null,
                    equipmentActionRaf: 0,
                    equipmentPendingDrag: null,
                    selectedEquipmentId: null,
                    selectedEquipmentControl: {
                        x: 0,
                        y: 0,
                        width: 0,
                        height: 0,
                        rotation: 0,
                    },
                    equipmentIsRotating: false,
                    equipmentLiveRotation: null,
                    equipmentRotationDrag: null,
                    equipmentRotationDisplayAngle: 0,
                    equipmentRotationHandleOffset: 90,
                    equipmentRotateHandleSide: "bottom",
                    roomSearch: "",
                    zoomInput: "100",
                    roomRotationInput: 0,
                    roomPaintMode: false,
                    roomPaintColor: '#FFF200',
                    roomPaintPresets: [
                        '#FFF200',
                        '#84CC16',
                        '#22C55E',
                        '#38BDF8',
                        '#A78BFA',
                        '#F97316',
                        '#EF4444',
                        '#94A3B8',
                    ],
                    selectedRoomControl: {
                        x: 0,
                        y: 0,
                        width: 0,
                        height: 0,
                        rotation: 0,
                    },
                    isRotating: false,
                    rotationDrag: null,
                    rotationDragAngle: 0,
                    rotationDisplayAngle: 0,
                    rotationHandleOffset: 90,
                    rotateHandleSide: "bottom",
                    roomCatalog: @js ($roomCatalog),

                    workstationLayout: {

                        open: false,

                        loading: false,

                        roomId: null,

                        room: null,

                        slots: [],

                        selectedSlotId: null,

                        selectedSlot: null,

                        generatorOpen: false,

                        generator: {

                            template_id: null,

                            count: 8,

                            start_x: 12,

                            start_y: 30,

                            spacing_x: 11,

                            orientation: 'north',

                        },

                    },
                    // =========================
                    // Shared Equipment Store
                    // PHASE 1
                    // Place here
                    // =========================

                    isFullscreen:false,

                    equipmentList: [],
                    blueprint: {
                        // =====================================
                        // Blueprint Canvas Size
                        // Change these values anytime
                        // =====================================

                        width: 1180,

                        height: 720,

                        // =====================================
                        // View Controls
                        // =====================================

                        zoom: 1,

                        panX: 0,

                        panY: 0,

                        isPanning: false,

                        startX: 0,

                        startY: 0,

                        originX: 0,

                        originY: 0,
                    },

                    spacePressed: false,
                    roomManager: {
                        open: false,
                        id: null,
                        name: "",
                        originalName: "",
                        type: "",
                        assets: 0,
                        activeReports: 0,
                    },
                    roomLayout: {
                        open: false,
                        edit: false,
                        fullscreen: false,
                        id: null,
                        name: "",
                        type: "",
                        equipment: [],
                        selectedGroupKey: null,
                        selectedAssetId: null,
                        listSearch: "",
                        listPage: 1,
                        listPerPage: 12,
                        comlabRows: [],
                        comlabRowLayouts: [],
                        comlabNav: 'rows',
                        selectedComlabRow: null,
                        selectedComlabRowTable: null,
                        comlabSetCarouselIndex: 0,
                        comlabHoldingPage: 0,
                        lifecycle: {
                            loading: false,
                            data: null,
                            equipmentId: null,
                        },
                    },
                    selectedComlabRowTable: null,
                    comlabRowIsRotating: false,
                    comlabRowLiveRotation: null,
                    comlabRowRotationDrag: null,
                    comlabRowRotateHandleSide: 'bottom',
                    comlabRowRotationDisplayAngle: 0,

                    originalRoomLayout: null,
                    originalComlabRows: null,
                    originalComlabRowLayouts: null,

                    blueprintLayoutModal:{

                        open:false,

                        title:"",

                        message:""

                    },
                    
                    wizardOpen: {{
                $errors->getBag("campusWizard")->any()
                    ? "true"
                    : "false"
            }},
                    wizardFullscreen: false,
                    canManageCampusSetup: {{ ($canManageCampusSetup ?? false) ? 'true' : 'false' }},
                    step: 1,
                    wizardSetupUnlocked: false,
                    unlockPromptOpen: false,
                    unlockCredential: "",
                    unlockVerifyBusy: false,
                    wizardFloorIndex: 0,
                    wizardRoomKey: 0,
                    wizardEquipmentKey: 0,
                    wizardHasLocalChanges: false,
                    wizardBaselineFloorSignature: '',
                    step3Mode: 'fast',
                    step3ValidationAttempted: false,
                    step3InlineErrors: {},
                    step4InlineErrors: [],
                    toast: "",
                    floors: @js ($floors
                    ->map(fn($f) => [
                        "id" => $f->floor_id,
                        "label" => $f->floor_level,
                        "room_count" => (int) ($roomCountsByFloor[$f->floor_id] ?? 0),
                    ])
                    ->values()),
                    existingRoomNamesByFloor: @js(
                        $rooms
                            ->groupBy('room_floor_id')
                            ->map(fn ($items) => $items->pluck('room_name')->filter()->values()->all())
                            ->toArray()
                    ),
                    form: Object.assign(
                        {
                            building_name: "",

                            building_logo: null,

                            building_address: null,

                            setup_locked: false,

                            minFloor: 2,

                            maxFloor: 3,

                            floors: [],
                        },
                        @js ($wizardCampus ?? []),
                    ),
                    get activeFloorLabel() {
                        return (
                            this.floors.find((f) => f.id === this.activeFloor)?.label ||
                            "No floor selected"
                        );
                    },
                    get floorRooms() {
                        return (this.roomCatalog || []).filter(
                            (room) => Number(room.floor_id) === Number(this.activeFloor)
                        );
                    },
                    roomStatusTone(status) {
                        const value = String(status || "Normal");

                        if (value === "Critical") {
                            return "critical";
                        }

                        if (value === "Maintenance Needed") {
                            return "maintenance";
                        }

                        return "normal";
                    },
                    get floorStats() {
                        const rooms = this.floorRooms;

                        return {
                            rooms: rooms.length,
                            attention: rooms.filter(
                                (room) => this.roomStatusTone(room.status) !== "normal"
                            ).length,
                            reports: rooms.reduce(
                                (sum, room) =>
                                    sum + Number(room.monitoring?.active_reports || 0),
                                0,
                            ),
                            todayReports: rooms.reduce(
                                (sum, room) =>
                                    sum + Number(room.monitoring?.today_reports || 0),
                                0,
                            ),
                            equipment: rooms.reduce((sum, room) => {
                                const monitored = Number(
                                    room.monitoring?.equipment_count || 0,
                                );

                                if (monitored > 0) {
                                    return sum + monitored;
                                }

                                return (
                                    sum +
                                    (Array.isArray(room.equipment)
                                        ? room.equipment.length
                                        : 0)
                                );
                            }, 0),
                            damaged: rooms.reduce(
                                (sum, room) =>
                                    sum +
                                    Number(room.monitoring?.equipment_damaged || 0),
                                0,
                            ),
                        };
                    },
                    get attentionRooms() {
                        return this.floorRooms
                            .map((room) => ({
                                ...room,
                                reports: Number(
                                    room.monitoring?.active_reports || 0,
                                ),
                                damaged: Number(
                                    room.monitoring?.equipment_damaged || 0,
                                ),
                                tone: this.roomStatusTone(room.status),
                            }))
                            .filter(
                                (room) =>
                                    room.tone !== "normal" ||
                                    room.reports > 0 ||
                                    room.damaged > 0,
                            )
                            .sort((a, b) => {
                                const rank = {
                                    critical: 0,
                                    maintenance: 1,
                                    normal: 2,
                                };

                                return (
                                    rank[a.tone] - rank[b.tone] ||
                                    b.reports - a.reports
                                );
                            })
                            ;
                    },
                    get upcomingSchedules() {
                        const items = [];

                        this.floorRooms.forEach((room) => {
                            (room.monitoring?.schedules || []).forEach(
                                (schedule) => {
                                    items.push({
                                        roomId: room.id,
                                        roomName: room.name,
                                        title:
                                            schedule.maintenance_schedule_title ||
                                            "Maintenance",
                                        equipment:
                                            schedule.equipment_name || "Equipment",
                                        date: schedule.maintenance_schedule_next_date,
                                        status:
                                            schedule.maintenance_schedule_status ||
                                            "Scheduled",
                                    });
                                },
                            );
                        });

                        return items
                            .sort(
                                (a, b) =>
                                    new Date(a.date || 0) - new Date(b.date || 0),
                            );
                    },
                    get floorHotIssues() {
                        const counts = new Map();

                        this.floorRooms.forEach((room) => {
                            (room.monitoring?.frequent_problems || []).forEach(
                                (problem) => {
                                    const label = String(
                                        problem.report_problem_description || "",
                                    ).trim();

                                    if (!label) {
                                        return;
                                    }

                                    counts.set(
                                        label,
                                        (counts.get(label) || 0) +
                                            Number(problem.occurrences || 0),
                                    );
                                },
                            );
                        });

                        return Array.from(counts.entries())
                            .map(([label, count]) => ({ label, count }))
                            .sort((a, b) => b.count - a.count)
                            .slice(0, 4);
                    },
                    roomHealthPercent(room) {
                        const monitoring = room.monitoring || {};
                        const total = Math.max(
                            1,
                            Number(monitoring.equipment_count || 0) ||
                                (Array.isArray(room.equipment)
                                    ? room.equipment.length
                                    : 0) ||
                                1,
                        );
                        const good = Number(monitoring.equipment_good || 0);
                        let pct = Math.round((good / total) * 100);

                        const status = String(room.status || "Normal");

                        if (status === "Critical") {
                            pct = Math.min(pct, 40);
                        } else if (status === "Maintenance Needed") {
                            pct = Math.min(pct, 72);
                        }

                        const reports = Number(monitoring.active_reports || 0);

                        return Math.max(8, Math.min(100, pct - reports * 4));
                    },
                    get floorHealthRooms() {
                        return this.floorRooms
                            .map((room) => ({
                                id: room.id,
                                name: room.name,
                                color: room.color || "#60A5FA",
                                health: this.roomHealthPercent(room),
                            }))
                            .sort((a, b) => b.health - a.health)
                            .slice(0, 2);
                    },
                    get floorHealthAverage() {
                        if (!this.floorRooms.length) {
                            return 0;
                        }

                        const total = this.floorRooms.reduce(
                            (sum, room) => sum + this.roomHealthPercent(room),
                            0,
                        );

                        return Math.round(total / this.floorRooms.length);
                    },
                    get floorRadarPolygon() {
                        const clamp = (value, max) =>
                            Math.max(0.15, Math.min(1, Number(value || 0) / max));

                        const values = [
                            clamp(this.floorStats.reports, 40),
                            clamp(this.floorStats.attention, 6),
                            clamp(this.floorHealthAverage, 100),
                            clamp(this.floorStats.equipment, 15),
                            clamp(this.upcomingSchedules.length, 4),
                            clamp(
                                this.floorHotIssues.reduce(
                                    (sum, issue) => sum + Number(issue.count || 0),
                                    0,
                                ),
                                8,
                            ),
                        ];

                        const cx = 120;
                        const cy = 120;
                        const maxR = 82;

                        return values
                            .map((value, index) => {
                                const angle =
                                    Math.PI / 2 + index * ((2 * Math.PI) / 6);

                                const x = cx + maxR * value * Math.cos(angle);
                                const y = cy - maxR * value * Math.sin(angle);

                                return `${x},${y}`;
                            })
                            .join(" ");
                    },
                    scrollToFloorInsights() {
                        document
                            .querySelector("[data-floor-insights]")
                            ?.scrollIntoView({ behavior: "smooth", block: "start" });
                    },
                    formatInsightDate(value) {
                        if (!value) {
                            return "No date";
                        }

                        const date = new Date(value);

                        if (Number.isNaN(date.getTime())) {
                            return String(value);
                        }

                        return date.toLocaleDateString(undefined, {
                            month: "short",
                            day: "numeric",
                        });
                    },
                    scheduleIsOverdue(value) {
                        if (!value) {
                            return false;
                        }

                        const date = new Date(value);

                        if (Number.isNaN(date.getTime())) {
                            return false;
                        }

                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        date.setHours(0, 0, 0, 0);

                        return date < today;
                    },
                    focusInsightRoom(roomId) {
                        this.selectedRoom = Number(roomId);
                        if (!this.drawerOpen) {
                            this.setDrawerOpen(true);
                        }

                        this.$nextTick(() => {
                            this.$refs.blueprintWorkspace?.scrollIntoView({
                                behavior: "smooth",
                                block: "start",
                            });

                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });
                    },
                    selectPriorityRoom(floorId = null) {
                        const targetFloor = Number(floorId || this.activeFloor);
                        const rooms = (this.roomCatalog || []).filter(
                            (room) => Number(room.floor_id) === targetFloor,
                        );

                        if (!rooms.length) {
                            this.selectedRoom = null;
                            return;
                        }

                        const todayTotal = rooms.reduce(
                            (sum, room) =>
                                sum + Number(room.monitoring?.today_reports || 0),
                            0,
                        );
                        const metric =
                            todayTotal > 0 ? "today_reports" : "week_reports";

                        const ranked = [...rooms].sort((a, b) => {
                            const aMetric = Number(
                                a.monitoring?.[metric] || 0,
                            );
                            const bMetric = Number(
                                b.monitoring?.[metric] || 0,
                            );

                            if (bMetric !== aMetric) {
                                return bMetric - aMetric;
                            }

                            const aActive = Number(
                                a.monitoring?.active_reports || 0,
                            );
                            const bActive = Number(
                                b.monitoring?.active_reports || 0,
                            );

                            if (bActive !== aActive) {
                                return bActive - aActive;
                            }

                            return String(a.name || "").localeCompare(
                                String(b.name || ""),
                            );
                        });

                        this.selectedRoom = Number(ranked[0].id);
                    },
                    get activeWizardFloor() {
                        return this.form.floors[this.wizardFloorIndex] || null;
                    },
                    get isWizardSetupLocked() {
                        return !!this.form.setup_locked && !this.wizardSetupUnlocked;
                    },
                    openUnlockSetupPrompt() {
                        if (!this.canManageCampusSetup) {
                            this.toast = "You do not have permission to unlock setup.";
                            setTimeout(() => (this.toast = ""), 3000);
                            return;
                        }

                        this.unlockCredential = "";
                        this.unlockPromptOpen = true;
                    },
                    closeUnlockSetupPrompt() {
                        this.unlockPromptOpen = false;
                        this.unlockCredential = "";
                        this.unlockVerifyBusy = false;
                    },
                    async confirmUnlockSetup() {
                        if (!this.canManageCampusSetup || this.unlockVerifyBusy) {
                            return;
                        }

                        const credential = String(this.unlockCredential || "").trim();

                        if (!credential) {
                            this.toast = "Enter your password or unlock code first.";
                            setTimeout(() => (this.toast = ""), 3000);
                            return;
                        }

                        this.unlockVerifyBusy = true;

                        try {
                            const response = await fetch(
                                @js (route("maintenance.infrastructure.campus.unlock-verify")),
                                {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": document
                                            .querySelector('meta[name="csrf-token"]')
                                            .content,
                                    },
                                    body: JSON.stringify({
                                        unlock_credential: credential,
                                    }),
                                },
                            );

                            if (!response.ok) {
                                throw new Error();
                            }

                            this.wizardSetupUnlocked = true;
                            this.closeUnlockSetupPrompt();
                            this.toast = "Setup unlocked. You can now edit Step 1.";
                            setTimeout(() => (this.toast = ""), 3000);
                        } catch (error) {
                            this.toast = "Invalid credential. Setup remains locked.";
                            setTimeout(() => (this.toast = ""), 3000);
                        } finally {
                            this.unlockVerifyBusy = false;
                        }
                    },
                    unlockWizardSetup() {
                        this.openUnlockSetupPrompt();
                    },
                    lockWizardSetup() {
                        this.wizardSetupUnlocked = false;
                        this.closeUnlockSetupPrompt();
                        this.toast = "Setup locked again.";
                        setTimeout(() => (this.toast = ""), 3000);
                    },
                    init() {
                        window.infrastructure = this;

                        try {
                            const savedDrawer = localStorage.getItem(
                                "prism.buildingLayout.drawerOpen",
                            );
                            if (savedDrawer === "0") {
                                this.drawerOpen = false;
                            } else if (savedDrawer === "1") {
                                this.drawerOpen = true;
                            }
                        } catch (error) {
                            // Ignore storage failures
                        }

                        if (this.form.floors.length === 0) {
                            this.generateFloors();
                        } else {
                            const numbers = this.form.floors.map((floor) => {
                                return parseInt(floor.level);
                            });

                            this.form.minFloor = Math.min(...numbers);

                            this.form.maxFloor = Math.max(...numbers);
                        }

                        this.$watch("form.minFloor", () => {
                            this.generateFloors();
                        });

                        this.$watch("form.maxFloor", () => {
                            this.generateFloors();
                        });

                        this.$watch("selectedRoom", () => {
                            this.roomRotationInput = this.getSelectedRoomRotation();
                            this.syncSelectedRoomControl();

                            if (this.editMode && this.roomPaintMode && this.selectedRoom) {
                                const room = this.roomCatalog.find((item) => item.id === this.selectedRoom);

                                if (room) {
                                    this.roomPaintColor = room.color || this.defaultRoomColor(room.type);
                                }
                            }
                        });

                        this.$watch("editMode", () => {
                            this.$nextTick(() => this.updateRotateHandlePlacement());
                        });

                        this.$watch("roomPaintColor", (value) => {
                            if (!this.editMode || !this.roomPaintMode || !this.selectedRoom) {
                                return;
                            }

                            this.paintRoomColor(this.selectedRoom, value);
                        });

                        let resizeTimer;

                        window.addEventListener("resize", () => {

                            clearTimeout(resizeTimer);

                            resizeTimer = setTimeout(() => {

                                this.fitBlueprint();

                            }, 40);

                        });

                        this.$nextTick(() => {
                            const viewport = this.$refs.blueprintViewport;
                            if (viewport && typeof ResizeObserver !== "undefined") {
                                let roTimer = 0;
                                this.blueprintResizeObserver = new ResizeObserver(() => {
                                    clearTimeout(roTimer);
                                    roTimer = setTimeout(() => {
                                        if (!this.drawerAnimating) {
                                            this.fitBlueprint();
                                        }
                                    }, 16);
                                });
                                this.blueprintResizeObserver.observe(viewport);
                            }

                            this.bindDragging();

                            this.$nextTick(() => {

                                requestAnimationFrame(() => {

                                    requestAnimationFrame(() => {

                                        this.fitBlueprint();

                                    });

                                });

                            });

                            document.querySelectorAll(".room-block").forEach((room) => {
                                this.syncRoomLabel(room);
                            });
                            this.syncRoomEdges();

                            document.addEventListener(

                                "fullscreenchange",

                                ()=>{

                                    this.isFullscreen = !!document.fullscreenElement;

                                    this.$nextTick(()=>{

                                        this.fitBlueprint();

                                    });

                                }

                            );

                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });

                        // =====================================================
                        // PHASE 3.1
                        // CHECK IF DASHBOARD SENT A ROOM ID
                        // =====================================================

                        const urlParams =
                            new URLSearchParams(
                                window.location.search
                            );

                        const dashboardRoomId =
                            urlParams.get('room');

                        if (dashboardRoomId) {
                            const roomId = Number(dashboardRoomId);
                            const dashboardRoom = (this.roomCatalog || []).find(
                                (room) => Number(room.id) === roomId,
                            );

                            if (dashboardRoom) {
                                this.activeFloor = Number(dashboardRoom.floor_id);
                                this.selectedRoom = roomId;
                            } else {
                                this.selectPriorityRoom();
                            }
                        } else {
                            this.selectPriorityRoom();
                        }
                    },
                    selectFloor(id) {
                        const nextFloor = Number(id);

                        if (Number(this.activeFloor) === nextFloor) {
                            return;
                        }

                        this.activeFloor = nextFloor;
                        this.closeRoomManager();

                        queueMicrotask(() => {
                            const selectedOnFloor = (this.roomCatalog || []).some(
                                (room) =>
                                    Number(room.id) === Number(this.selectedRoom) &&
                                    Number(room.floor_id) === nextFloor,
                            );

                            if (!selectedOnFloor) {
                                this.selectPriorityRoom(nextFloor);
                            }
                        });
                    },
                    zoomBlueprint(delta) {
                        const oldZoom = this.blueprint.zoom;

                        const newZoom = Math.min(
                            2.2,

                            Math.max(
                                0.55,

                                +(oldZoom + delta).toFixed(2),
                            ),
                        );

                        if (oldZoom === newZoom) return;

                        const viewport = this.$refs.blueprintViewport;

                        const cx = viewport.clientWidth / 2;

                        const cy = viewport.clientHeight / 2;

                        this.blueprint.panX =
                            cx - ((cx - this.blueprint.panX) / oldZoom) * newZoom;

                        this.blueprint.panY =
                            cy - ((cy - this.blueprint.panY) / oldZoom) * newZoom;

                        this.blueprint.zoom = newZoom;

                        this.zoomInput = Math.round(newZoom * 100);
                        this.$nextTick(() => this.updateRotateHandlePlacement());
                    },
                    applyZoomInput() {
                        const value = parseFloat(String(this.zoomInput).replace("%", ""));

                        if (isNaN(value)) {
                            this.toast = "Invalid zoom value.";

                            this.zoomInput = Math.round(this.blueprint.zoom * 100);

                            setTimeout(() => (this.toast = ""), 2500);

                            return;
                        }

                        if (value < 55 || value > 220) {
                            this.toast = "Zoom must be between 55% and 220%.";

                            this.zoomInput = Math.round(this.blueprint.zoom * 100);

                            setTimeout(() => (this.toast = ""), 2500);

                            return;
                        }

                        this.blueprint.zoom = value / 100;

                        this.zoomInput = Math.round(value);
                        this.$nextTick(() => this.updateRotateHandlePlacement());
                    },
                    handleBlueprintWheel(event) {
                        if (!this.spacePressed) {
                            return;
                        }

                        event.preventDefault();

                        this.zoomBlueprint(event.deltaY > 0 ? -0.08 : 0.08);
                    },
                    getSelectedRoomRotation() {
                        if (!this.selectedRoom) {
                            return 0;
                        }

                        const room = document.querySelector(
                            `.room-block[data-id="${this.selectedRoom}"]`,
                        );

                        return room ? Number(room.dataset.rotation || 0) : 0;
                    },
                    normalizeRotation(rotation) {
                        return (((rotation % 360) + 540) % 360) - 180;
                    },
                    formatRotationDisplay(rotation) {
                        return (((rotation + 180) % 360 + 360) % 360) - 180;
                    },
                    setSelectedRoomRotation(rotation) {
                        const room = document.querySelector(
                            `.room-block[data-id="${this.selectedRoom}"]`,
                        );

                        if (!room) {
                            return;
                        }

                        const value = rotation;

                        room.dataset.rotation = value;
                        room.style.transform = `rotate(${value}deg)`;
                        room.style.transformOrigin = "center center";

                        this.roomRotationInput = value;
                        this.rotationDragAngle = value;
                        this.rotationDisplayAngle = this.formatRotationDisplay(value);
                        this.layoutDirty = true;
                    },
                    beginRoomRotation(event) {
                        if (!this.editMode || !this.selectedRoom) return;

                        const room = document.querySelector(
                            `.room-block[data-id="${this.selectedRoom}"]`,
                        );
                        if (!room) return;

                        const rect = room.getBoundingClientRect();
                        const centerX = rect.left + rect.width / 2;
                        const centerY = rect.top + rect.height / 2;
                        const lastMouseAngle = Math.atan2(event.clientY - centerY, event.clientX - centerX) * (180 / Math.PI);

                        this.rotationDrag = {
                            centerX,
                            centerY,
                            pointerId: event.pointerId,
                            lastMouseAngle,
                            handleElement: event.currentTarget,
                        };

                        this.isRotating = true;
                        this.rotationDragAngle = this.getSelectedRoomRotation();
                        this.captureLayoutUndo();
                        document.body.classList.add("rotate-active-cursor");

                        event.currentTarget.setPointerCapture?.(event.pointerId);
                    },
                    trackRoomRotation(event) {
                        if (!this.rotationDrag || event.pointerId !== this.rotationDrag.pointerId) {
                            return;
                        }

                        const { centerX, centerY } = this.rotationDrag;
                        const newMouseAngle = Math.atan2(event.clientY - centerY, event.clientX - centerX) * (180 / Math.PI);
                        let delta = newMouseAngle - this.rotationDrag.lastMouseAngle;

                        if (delta > 180) {
                            delta -= 360;
                        } else if (delta < -180) {
                            delta += 360;
                        }

                        const currentRotation = this.getSelectedRoomRotation();
                        const degrees = currentRotation + delta;

                        this.rotationDrag.lastMouseAngle = newMouseAngle;
                        this.setSelectedRoomRotation(degrees);
                        this.rotationDragAngle = degrees;
                        this.rotationDisplayAngle = this.formatRotationDisplay(degrees);
                        this.syncSelectedRoomControl();
                    },
                    endRoomRotation(event) {
                        if (!this.rotationDrag || event.pointerId !== this.rotationDrag.pointerId) {
                            return;
                        }

                        const room = document.querySelector(
                            `.room-block[data-id="${this.selectedRoom}"]`,
                        );
                        if (room) {
                            const finalRotation = Number(room.dataset.rotation || 0);
                            room.dataset.rotation = finalRotation;
                            room.style.transform = `rotate(${finalRotation}deg)`;
                            this.roomRotationInput = this.formatRotationDisplay(finalRotation);
                            this.rotationDragAngle = finalRotation;
                            this.rotationDisplayAngle = this.formatRotationDisplay(finalRotation);
                        }

                        this.rotationDrag.handleElement?.releasePointerCapture?.(event.pointerId);
                        this.rotationDrag = null;
                        this.isRotating = false;
                        document.body.classList.remove("cursor-grabbing", "rotate-active-cursor");
                        this.dropLayoutUndoIfUnchanged();
                        this.layoutDirty = true;
                        this.syncRoomEdges();
                    },
                    rotateSelectedRoom(delta) {
                        if (!this.editMode || !this.selectedRoom) {
                            return;
                        }

                        this.captureLayoutUndo();
                        const current = this.getSelectedRoomRotation();
                        this.setSelectedRoomRotation(current + delta);
                        this.syncSelectedRoomControl();
                        this.dropLayoutUndoIfUnchanged();
                    },
                    syncSelectedRoomControl() {
                        if (!this.selectedRoom) {
                            this.selectedRoomControl = {
                                x: 0,
                                y: 0,
                                width: 0,
                                height: 0,
                                rotation: 0,
                            };
                            this.rotateHandleSide = "bottom";
                            return;
                        }

                        const room = document.querySelector(
                            `.room-block[data-id="${this.selectedRoom}"]`,
                        );

                        if (!room) {
                            return;
                        }

                        const roomX = Number(room.dataset.x || room.offsetLeft || 0);
                        const roomY = Number(room.dataset.y || room.offsetTop || 0);
                        const roomWidth = Number(room.dataset.width || room.offsetWidth || 0);
                        const roomHeight = Number(room.dataset.height || room.offsetHeight || 0);

                        const rawRotation = Number(room.dataset.rotation || 0);

                        this.selectedRoomControl = {
                            x: roomX + roomWidth / 2,
                            y: roomY + roomHeight + 12,
                            width: roomWidth,
                            height: roomHeight,
                            rotation: rawRotation,
                        };
                        this.rotationDisplayAngle = this.formatRotationDisplay(rawRotation);
                        this.updateRotateHandlePlacement();
                    },
                    updateRotateHandlePlacement() {
                        if (this.isRotating || !this.editMode || !this.selectedRoom) {
                            return;
                        }

                        const room = document.querySelector(
                            `.room-block[data-id="${this.selectedRoom}"]`,
                        );
                        const viewport = this.$refs.blueprintViewport;
                        const canvas = this.$refs.blueprintCanvas;

                        if (!room || !viewport) {
                            this.rotateHandleSide = "bottom";
                            return;
                        }

                        const zoom = this.blueprint?.zoom || 1;
                        const padding = 8;
                        const handleRadius = 20 * zoom;
                        const handleOutset = 32 * zoom;
                        const height = Number(room.dataset.height || room.offsetHeight || 0);
                        const offsetFromCenter = (height / 2) * zoom + handleOutset;

                        const roomRect = room.getBoundingClientRect();
                        const viewportRect = viewport.getBoundingClientRect();
                        const canvasRect = canvas?.getBoundingClientRect() || viewportRect;
                        const clipTop = Math.max(viewportRect.top, canvasRect.top) + padding;
                        const clipBottom = Math.min(viewportRect.bottom, canvasRect.bottom) - padding;
                        const cy = roomRect.top + roomRect.height / 2;
                        const theta = (Number(room.dataset.rotation || 0) * Math.PI) / 180;
                        const cos = Math.cos(theta);

                        const projectY = (localY) => cy + localY * cos;
                        const bottomY = projectY(offsetFromCenter);
                        const topY = projectY(-offsetFromCenter);
                        const bottomFits =
                            bottomY + handleRadius <= clipBottom &&
                            bottomY - handleRadius >= clipTop;
                        const topFits =
                            topY + handleRadius <= clipBottom &&
                            topY - handleRadius >= clipTop;

                        if (bottomFits) {
                            this.rotateHandleSide = "bottom";
                            return;
                        }

                        if (topFits) {
                            this.rotateHandleSide = "top";
                            return;
                        }

                        const overflow = (y) =>
                            Math.max(0, y + handleRadius - clipBottom) +
                            Math.max(0, clipTop - (y - handleRadius));

                        this.rotateHandleSide =
                            overflow(topY) < overflow(bottomY) ? "top" : "bottom";
                    },
                    applySelectedRoomRotation() {
                        if (!this.editMode || !this.selectedRoom) {
                            return;
                        }

                        const value = Number(this.roomRotationInput);

                        if (Number.isNaN(value)) {
                            this.roomRotationInput = this.getSelectedRoomRotation();
                            return;
                        }

                        this.setSelectedRoomRotation(value);
                        this.rotationDisplayAngle = this.formatRotationDisplay(value);
                        this.syncSelectedRoomControl();
                    },
                    resetSelectedRoomRotation() {
                        if (!this.editMode || !this.selectedRoom) {
                            return;
                        }

                        this.setSelectedRoomRotation(0);
                    },
                    selectEquipment(equipmentId) {
                        if (!this.roomLayout.edit) return;

                        this.selectedEquipmentId = equipmentId;
                        if (equipmentId) {
                            this.selectedComlabRowTable = null;
                        }

                        if (!equipmentId) {
                            this.selectedEquipmentControl = {
                                x: 0,
                                y: 0,
                                width: 0,
                                height: 0,
                                rotation: 0,
                            };
                            this.equipmentIsRotating = false;
                            document.body.classList.remove("equipment-rotate-active-cursor");
                            this.equipmentRotateHandleSide = "bottom";
                            return;
                        }

                        this.$nextTick(() => {
                            this.equipmentRotateHandleSide = "bottom";
                            this.syncSelectedEquipmentControl();
                            this.updateEquipmentRotateHandlePlacement();
                        });
                    },
                    handleEquipmentPointerDown(event, equipmentId) {

                        if (!this.roomLayout.edit) return;

                        if (event.button !== 0) return;

                        event.preventDefault();

                        const node = event.currentTarget;

                        const target =
                            event.target instanceof Element
                                ? event.target
                                : event.target.parentElement;

                        const resizeHandle =
                            target?.closest(".resize-grip");

                        const rotateHandle =
                            target?.closest(".rotate-equipment-handle-cursor");

                        this.selectEquipment(equipmentId);

                        if (rotateHandle) {

                            this.beginEquipmentRotation(event, equipmentId);

                            return;

                        }

                        if (resizeHandle) {

                            this.beginEquipmentResize(
                                event,
                                node,
                                resizeHandle
                            );

                            return;

                        }

                        // Don't drag yet.
                        // Just remember where the pointer started.

                        this.equipmentPendingDrag = {

                            node,

                            pointerId: event.pointerId,

                            startX: event.clientX,

                            startY: event.clientY,

                        };

                    },
                    beginEquipmentDrag(event, node) {
                        
                        const parentRect = node.parentElement.getBoundingClientRect();
                        
                        const currentX = parseFloat(node.dataset.x || 50);
                        const currentY = parseFloat(node.dataset.y || 50);
                        const centerX = (currentX / 100) * parentRect.width;
                        const centerY = (currentY / 100) * parentRect.height;

                        if (node.classList.contains('comlab-floor-node')) {
                            this._comlabFloorDidDrag = true;
                        }

                        node.classList.add("dragging");
                        node.setPointerCapture?.(event.pointerId);

                        this.equipmentAction = {
                            type: "drag",
                            node,
                            parentRect,
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                            startCenterX: centerX,
                            startCenterY: centerY,
                            latestEvent: event,
                        };
                    },
                    beginEquipmentResize(event, node, handle) {
                        const parentRect = node.parentElement.getBoundingClientRect();
                        const centerX = ((parseFloat(node.dataset.x) || 50) / 100) * parentRect.width;
                        const centerY = ((parseFloat(node.dataset.y) || 50) / 100) * parentRect.height;
                        const width = Number(node.dataset.width || 120);
                        const height = Number(node.dataset.height || 96);
                        const handleX = handle.dataset.handleX;
                        const handleY = handle.dataset.handleY;
                        const rotationDeg = Number(node.dataset.rotation || 0);
                        const rotationRad = (rotationDeg * Math.PI) / 180;
                        const cos = Math.cos(rotationRad);
                        const sin = Math.sin(rotationRad);

                        if (node.classList.contains('comlab-floor-node')) {
                            this._comlabFloorDidDrag = true;
                        }

                        node.classList.add("dragging");
                        node.setPointerCapture?.(event.pointerId);

                        this.equipmentAction = {
                            type: "resize",
                            node,
                            parentRect,
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                            startCenterX: centerX,
                            startCenterY: centerY,
                            startWidth: width,
                            startHeight: height,
                            handleX,
                            handleY,
                            rotationRad,
                            cos,
                            sin,
                            latestEvent: event,
                        };
                    },
                    syncEquipmentLabel(node, width, height) {
                        if (!node) {
                            return;
                        }

                        const w = Number(width ?? node.dataset.width ?? 120);
                        const h = Number(height ?? node.dataset.height ?? 96);

                        node.dataset.labelOrientation = h > w ? "vertical" : "horizontal";
                        node.classList.toggle("is-compact", w < 40);
                    },

                    getNodeDragHalfExtents(node) {
                        const width = Math.max(10, Number(node.dataset.width) || 120);
                        const height = Math.max(10, Number(node.dataset.height) || 96);
                        const rotationDeg = Number(node.dataset.rotation || 0);
                        const rad = (rotationDeg * Math.PI) / 180;
                        const cos = Math.abs(Math.cos(rad));
                        const sin = Math.abs(Math.sin(rad));
                        // Axis-aligned bounds after rotation (vertical row uses height as width).
                        const halfW = (width * cos + height * sin) / 2;
                        const halfH = (width * sin + height * cos) / 2;
                        const isRow = node.classList.contains("comlab-row-node");
                        const inset = isRow ? 4 : 0;
                        return {
                            halfW: Math.max(8, halfW - inset),
                            halfH: Math.max(8, halfH - inset),
                            isRow,
                        };
                    },

                    clampNodeCenter(node, x, y, parentRect) {
                        const { halfW, halfH, isRow } = this.getNodeDragHalfExtents(node);
                        // Rows may sit nearly on the canvas edge; other nodes keep full half-size padding.
                        const minX = isRow ? Math.min(halfW, parentRect.width * 0.02) : halfW;
                        const maxX = isRow
                            ? Math.max(parentRect.width - halfW, parentRect.width * 0.98)
                            : parentRect.width - halfW;
                        const minY = isRow ? Math.min(halfH, parentRect.height * 0.02) : halfH;
                        const maxY = isRow
                            ? Math.max(parentRect.height - halfH, parentRect.height * 0.98)
                            : parentRect.height - halfH;

                        return {
                            x: Math.min(Math.max(x, Math.min(minX, maxX)), Math.max(minX, maxX)),
                            y: Math.min(Math.max(y, Math.min(minY, maxY)), Math.max(minY, maxY)),
                        };
                    },

                    applyEquipmentActionFrame() {
                        this.equipmentActionRaf = 0;
                        const action = this.equipmentAction;

                        if (!action || !this.roomLayout.edit) {
                            return;
                        }

                        const event = action.latestEvent;

                        if (!event) {
                            return;
                        }

                        const node = action.node;
                        const rect = action.parentRect;
                        const dx = event.clientX - action.startX;
                        const dy = event.clientY - action.startY;

                        if (action.type === "drag") {
                            let x = action.startCenterX + dx;
                            let y = action.startCenterY + dy;
                            const clamped = this.clampNodeCenter(node, x, y, rect);
                            x = clamped.x;
                            y = clamped.y;

                            node.style.left = x + "px";
                            node.style.top = y + "px";
                            node.dataset.x = Math.round((x / rect.width) * 100);
                            node.dataset.y = Math.round((y / rect.height) * 100);

                            if (
                                this.isComlabRoomLayout()
                                && node.classList.contains('comlab-floor-node')
                            ) {
                                this.highlightComlabRowDropTarget(
                                    this.findComlabRowAtClientPoint(event.clientX, event.clientY),
                                );
                            }
                        }

                        if (action.type === "resize") {
                            const isRowNode = node.classList.contains("comlab-row-node");
                            const MIN_WIDTH = isRowNode ? 120 : 20;
                            const MAX_WIDTH = isRowNode ? 480 : 1000;
                            const MIN_HEIGHT = isRowNode ? 40 : 20;
                            const MAX_HEIGHT = isRowNode ? 120 : 1000;
                            const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
                            const localDx = dx * action.cos + dy * action.sin;
                            const localDy = -dx * action.sin + dy * action.cos;
                            let width = action.startWidth;
                            let height = action.startHeight;
                            let shiftLocalX = 0;
                            let shiftLocalY = 0;

                            if (action.handleX === "left") {
                                width = clamp(action.startWidth - localDx, MIN_WIDTH, MAX_WIDTH);
                                shiftLocalX = (action.startWidth - width) / 2;
                            } else if (action.handleX === "right") {
                                width = clamp(action.startWidth + localDx, MIN_WIDTH, MAX_WIDTH);
                                shiftLocalX = (width - action.startWidth) / 2;
                            }

                            if (action.handleY === "top") {
                                height = clamp(action.startHeight - localDy, MIN_HEIGHT, MAX_HEIGHT);
                                shiftLocalY = (action.startHeight - height) / 2;
                            } else if (action.handleY === "bottom") {
                                height = clamp(action.startHeight + localDy, MIN_HEIGHT, MAX_HEIGHT);
                                shiftLocalY = (height - action.startHeight) / 2;
                            }

                            const shiftWorldX = shiftLocalX * action.cos - shiftLocalY * action.sin;
                            const shiftWorldY = shiftLocalX * action.sin + shiftLocalY * action.cos;
                            let centerX = action.startCenterX + shiftWorldX;
                            let centerY = action.startCenterY + shiftWorldY;

                            const prevW = node.dataset.width;
                            const prevH = node.dataset.height;
                            node.dataset.width = String(Math.round(width));
                            node.dataset.height = String(Math.round(height));
                            const clamped = this.clampNodeCenter(node, centerX, centerY, rect);
                            node.dataset.width = prevW;
                            node.dataset.height = prevH;
                            centerX = clamped.x;
                            centerY = clamped.y;

                            node.style.width = width + "px";
                            node.style.height = height + "px";
                            node.style.left = centerX + "px";
                            node.style.top = centerY + "px";
                            node.dataset.width = Math.round(width);
                            node.dataset.height = Math.round(height);
                            node.dataset.x = Math.round((centerX / rect.width) * 100);
                            node.dataset.y = Math.round((centerY / rect.height) * 100);
                            this.syncEquipmentLabel(node, width, height);
                        }

                        this.updateEquipmentRotateHandlePlacement(node);
                        if (node.classList.contains('comlab-row-node')) {
                            this.updateComlabRowRotateHandlePlacement(node);
                        }
                    },
                    trackEquipmentAction(event) {
                        if (this.equipmentIsRotating) {
                            return;
                        }

                        if (this.equipmentPendingDrag && !this.equipmentAction) {

                            if (this.equipmentIsRotating) {
                                this.equipmentPendingDrag = null;
                                return;
                            }

                            if (event.pointerId !== this.equipmentPendingDrag.pointerId) {
                                return;
                            }

                            const dx = event.clientX - this.equipmentPendingDrag.startX;
                            const dy = event.clientY - this.equipmentPendingDrag.startY;

                            if (Math.hypot(dx, dy) < 5) {
                                return;
                            }

                            this.beginEquipmentDrag(
                                event,
                                this.equipmentPendingDrag.node
                            );

                            this.equipmentPendingDrag = null;

                            
                        }
                        if (!this.equipmentAction || !this.roomLayout.edit) return;
                        if (event.pointerId !== this.equipmentAction.pointerId) return;
                        if ((event.buttons & 1) === 0) {

                            this.endEquipmentAction(event);

                            return;

                        }

                        this.equipmentAction.latestEvent = event;

                        if (this.equipmentActionRaf) {
                            return;
                        }

                        this.equipmentActionRaf = requestAnimationFrame(() => {
                            this.applyEquipmentActionFrame();
                        });
                    },
                    endEquipmentAction(event) {
                        if (this.equipmentPendingDrag) {
                            this.equipmentPendingDrag = null;
                        }
                        if (!this.equipmentAction) return;
                        if (event.pointerId !== this.equipmentAction.pointerId) return;

                        if (this.equipmentActionRaf) {
                            cancelAnimationFrame(this.equipmentActionRaf);
                            this.equipmentActionRaf = 0;
                            this.applyEquipmentActionFrame();
                        }

                        const node = this.equipmentAction.node;
                        const rect = node.parentElement.getBoundingClientRect();
                        const left = parseFloat(node.style.left || "0") || 0;
                        const top = parseFloat(node.style.top || "0") || 0;
                        const percentX = Math.round((left / rect.width) * 100);
                        const percentY = Math.round((top / rect.height) * 100);
                        const isRowNode = node.classList.contains("comlab-row-node");
                        const minPct = isRowNode ? 1 : 4;
                        const maxPct = isRowNode ? 99 : 96;

                        node.dataset.x = Math.min(maxPct, Math.max(minPct, percentX));
                        node.dataset.y = Math.min(maxPct, Math.max(minPct, percentY));
                        node.dataset.width = parseInt(node.style.width || node.dataset.width || 120, 10);
                        node.dataset.height = parseInt(node.style.height || node.dataset.height || 96, 10);
                        this.syncEquipmentLabel(node);

                        if (node.classList.contains("comlab-row-node")) {
                            const row = (this.roomLayout.comlabRowLayouts || []).find(
                                (entry) => entry.name === node.dataset.rowName,
                            );
                            if (row) {
                                row.x = Number(node.dataset.x);
                                row.y = Number(node.dataset.y);
                                row.width = Number(node.dataset.width);
                                row.height = Number(node.dataset.height);
                                row.rotation = Number(node.dataset.rotation || 0);
                            }
                            node.style.left = node.dataset.x + "%";
                            node.style.top = node.dataset.y + "%";
                            this.equipmentAction = null;
                            node.classList.remove("dragging");
                            node.releasePointerCapture?.(event.pointerId);
                            this.equipmentPendingDrag = null;
                            this.layoutDirty = true;
                            this.$nextTick(() => this.updateComlabRowRotateHandlePlacement(node));
                            return;
                        }

                        const action = this.equipmentAction;
                        const item = this.roomLayout.equipment.find(
                            equipment => equipment.id === Number(node.dataset.equipmentId)
                        );

                        if (
                            item
                            && this.isComlabRoomLayout()
                            && node.classList.contains('comlab-floor-node')
                            && action.type === 'drag'
                        ) {
                            const rowName = this.findComlabRowAtClientPoint(event.clientX, event.clientY);
                            this.highlightComlabRowDropTarget(null);
                            if (rowName) {
                                this.assignEquipmentToComlabRow(item.id, rowName);
                                this.selectedEquipmentId = null;
                                this.roomLayout.selectedAssetId = null;
                                this.selectComlabRowTable(rowName);
                                this.equipmentAction = null;
                                node.classList.remove("dragging");
                                node.releasePointerCapture?.(event.pointerId);
                                this.equipmentPendingDrag = null;
                                this.layoutDirty = true;
                                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                                return;
                            }
                        }

                        if (item) {
                            item.x = Number(node.dataset.x);
                            item.y = Number(node.dataset.y);
                            item.width = Number(node.dataset.width);
                            item.height = Number(node.dataset.height);
                            item.rotation = Number(node.dataset.rotation || 0);

                            if (this.isComlabRoomLayout() && this.isComlabFloorEquipment(item)) {
                                if (item._holding || item.placement_zone === 'Holding' || item.location === 'Holding') {
                                    // Keep in holding; skip zone sync from drag of other nodes
                                } else {
                                    item.placement_zone = 'Floor';
                                    item.location = 'Floor';
                                    node.dataset.zone = 'Floor';
                                }
                            }
                        }

                        this.highlightComlabRowDropTarget(null);

                        node.style.left = node.dataset.x + "%";
                        node.style.top = node.dataset.y + "%";

                        this.equipmentAction = null;

                        node.classList.remove("dragging");
                        node.releasePointerCapture?.(event.pointerId);
                        this.syncEquipmentZone(node);
                        this.layoutDirty = true;
                        this.equipmentPendingDrag = null;
                        this.syncSelectedEquipmentControl();
                    },
                    getSelectedEquipmentRotation() {
                        if (!this.selectedEquipmentId) return 0;

                        if (
                            this.equipmentIsRotating &&
                            this.equipmentLiveRotation != null
                        ) {
                            return this.equipmentLiveRotation;
                        }

                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );

                        return item ? Number(item.dataset.rotation || 0) : 0;
                    },
                    liveEquipmentRotation(item) {
                        if (
                            this.equipmentIsRotating &&
                            this.selectedEquipmentId === item.id &&
                            this.equipmentLiveRotation != null
                        ) {
                            return this.equipmentLiveRotation;
                        }

                        return Number(item.rotation || 0);
                    },
                    equipmentRotateGimbalStyle(item) {
                        const rotation = this.liveEquipmentRotation(item);

                        return `transform:translate(-50%,-50%) rotate(${-rotation}deg)`;
                    },
                    equipmentRotateHandleStyle(item) {
                        return this.buildEquipmentRotateHandleStyle(
                            item,
                            this.equipmentRotateHandleSide || "bottom",
                        );
                    },
                    buildEquipmentRotateHandleStyle(item, side = "bottom") {
                        const rotation = this.liveEquipmentRotation
                            ? this.liveEquipmentRotation(item)
                            : Number(item?.rotation || 0);
                        const width = Number(item.width || 120);
                        const height = Number(item.height || 96);
                        const rad = (rotation * Math.PI) / 180;
                        const aabbWidth =
                            Math.abs(width * Math.cos(rad)) +
                            Math.abs(height * Math.sin(rad));
                        const aabbHeight =
                            Math.abs(width * Math.sin(rad)) +
                            Math.abs(height * Math.cos(rad));
                        const handleSize = 40;
                        const offsetX = aabbWidth / 2 + 22;
                        const offsetY = aabbHeight / 2 + 22;

                        if (side === "top") {
                            return `left:0;top:${-(offsetY + handleSize)}px;transform:translateX(-50%)`;
                        }

                        if (side === "right") {
                            return `left:${offsetX}px;top:0;transform:translateY(-50%)`;
                        }

                        if (side === "left") {
                            return `left:${-(offsetX + handleSize)}px;top:0;transform:translateY(-50%)`;
                        }

                        return `left:0;top:${offsetY}px;transform:translateX(-50%)`;
                    },
                    updateEquipmentRotateHandlePlacement(node = null) {
                        if (this.equipmentIsRotating || !this.roomLayout?.edit || !this.selectedEquipmentId) {
                            return;
                        }

                        const itemNode =
                            node ||
                            document.querySelector(
                                `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                            );
                        const canvas = this.isComlabRoomLayout()
                            ? this.$refs.comlabInteriorCanvas
                            : this.$refs.roomInteriorCanvas;

                        if (!itemNode || !canvas) {
                            this.equipmentRotateHandleSide = "bottom";
                            return;
                        }

                        const padding = 8;
                        const isFloorNode = itemNode.classList.contains('comlab-floor-node');
                        // Floor markers keep a name label; reserve a bit more so the handle clears it.
                        const handleReserve = isFloorNode ? 72 : 62;
                        const nodeRect = itemNode.getBoundingClientRect();
                        const canvasRect = canvas.getBoundingClientRect();
                        const clip = {
                            top: canvasRect.top + padding,
                            right: canvasRect.right - padding,
                            bottom: canvasRect.bottom - padding,
                            left: canvasRect.left + padding,
                        };
                        const space = {
                            bottom: clip.bottom - nodeRect.bottom,
                            top: nodeRect.top - clip.top,
                            right: clip.right - nodeRect.right,
                            left: nodeRect.left - clip.left,
                        };

                        // Always prefer bottom; flip only when the canvas edge would cover the handle.
                        const order = ['bottom', 'top', 'right', 'left'];
                        const fitting = order.find((side) => space[side] >= handleReserve);
                        const side =
                            fitting ||
                            order.reduce((best, current) =>
                                space[current] > space[best] ? current : best,
                            );
                        const handle = itemNode.querySelector(".rotate-equipment-handle-cursor");
                        const style = this.buildEquipmentRotateHandleStyle(
                            {
                                width: itemNode.dataset.width,
                                height: itemNode.dataset.height,
                                rotation: itemNode.dataset.rotation,
                            },
                            side,
                        );

                        if (handle) {
                            handle.setAttribute("style", style);
                        }

                        if (!this.equipmentAction) {
                            this.equipmentRotateHandleSide = side;
                        }
                    },
                    getEquipmentCenterClient(node) {
                        const parent = node?.parentElement;

                        if (!parent) {
                            const rect = node.getBoundingClientRect();

                            return {
                                x: rect.left + rect.width / 2,
                                y: rect.top + rect.height / 2,
                            };
                        }

                        const parentRect = parent.getBoundingClientRect();
                        const xPct = (parseFloat(node.dataset.x) || 50) / 100;
                        const yPct = (parseFloat(node.dataset.y) || 50) / 100;

                        return {
                            x: parentRect.left + parentRect.width * xPct,
                            y: parentRect.top + parentRect.height * yPct,
                        };
                    },
                    applyEquipmentRotationVisual(node, rotation) {
                        node.dataset.rotation = rotation;
                        node.style.transform = `translate(-50%,-50%) rotate(${rotation}deg)`;
                    },
                    setSelectedEquipmentRotation(rotation) {
                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );
                        if (!item) return;

                        const value = rotation;
                        this.applyEquipmentRotationVisual(item, value);

                        if (!this.equipmentIsRotating) {
                            const equipment = this.roomLayout.equipment.find(
                                equipment => equipment.id === Number(item.dataset.equipmentId)
                            );

                            if (equipment) {
                                equipment.rotation = value;
                            }

                            this.layoutDirty = true;
                        }

                        this.equipmentLiveRotation = value;
                        this.equipmentRotationDisplayAngle = this.formatRotationDisplay(value);
                        this.selectedEquipmentControl.rotation = value;
                    },
                    beginEquipmentRotation(event, equipmentId = null) {
                        if (!this.roomLayout.edit) return;

                        if (equipmentId) {
                            this.selectEquipment(equipmentId);
                        }

                        if (!this.selectedEquipmentId) return;

                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );
                        if (!item) return;

                        this.equipmentPendingDrag = null;

                        if (this.equipmentAction) {
                            this.endEquipmentAction(event);
                        }

                        const center = this.getEquipmentCenterClient(item);
                        const startRotation = Number(item.dataset.rotation || 0);
                        const startMouseAngle =
                            Math.atan2(event.clientY - center.y, event.clientX - center.x) *
                            (180 / Math.PI);

                        this.equipmentRotationDrag = {
                            node: item,
                            pointerId: event.pointerId,
                            startRotation,
                            startMouseAngle,
                            handleElement: event.currentTarget,
                        };

                        this.equipmentIsRotating = true;
                        this.equipmentLiveRotation = startRotation;
                        this.equipmentRotationDisplayAngle = this.formatRotationDisplay(startRotation);
                        document.body.classList.add("equipment-rotate-active-cursor");

                        if (item.classList.contains('comlab-floor-node')) {
                            this._comlabFloorDidDrag = true;
                        }

                        event.currentTarget.setPointerCapture?.(event.pointerId);
                    },
                    trackEquipmentRotation(event) {
                        if (!this.equipmentRotationDrag || event.pointerId !== this.equipmentRotationDrag.pointerId) {
                            return;
                        }

                        const { node, startRotation, startMouseAngle } = this.equipmentRotationDrag;
                        const center = this.getEquipmentCenterClient(node);
                        const mouseAngle =
                            Math.atan2(event.clientY - center.y, event.clientX - center.x) *
                            (180 / Math.PI);
                        let delta = mouseAngle - startMouseAngle;

                        if (delta > 180) {
                            delta -= 360;
                        } else if (delta < -180) {
                            delta += 360;
                        }

                        this.setSelectedEquipmentRotation(startRotation + delta);
                    },
                    endEquipmentRotation(event) {
                        if (!this.equipmentRotationDrag || event.pointerId !== this.equipmentRotationDrag.pointerId) {
                            return;
                        }

                        const item = this.equipmentRotationDrag.node || document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );
                        if (item) {
                            const finalRotation = this.normalizeRotation(
                                Number(item.dataset.rotation || 0),
                            );
                            this.applyEquipmentRotationVisual(item, finalRotation);

                            const equipment = this.roomLayout.equipment.find(
                                entry => entry.id === Number(item.dataset.equipmentId)
                            );

                            if (equipment) {
                                equipment.rotation = finalRotation;
                            }

                            this.equipmentLiveRotation = finalRotation;
                            this.equipmentRotationDisplayAngle = this.formatRotationDisplay(finalRotation);
                            this.selectedEquipmentControl.rotation = finalRotation;
                        }

                        this.equipmentRotationDrag.handleElement?.releasePointerCapture?.(event.pointerId);
                        this.equipmentRotationDrag = null;
                        this.equipmentIsRotating = false;
                        this.equipmentLiveRotation = null;
                        document.body.classList.remove("cursor-grabbing", "equipment-rotate-active-cursor");
                        this.layoutDirty = true;
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                            this.updateEquipmentRotateHandlePlacement();
                        });
                    },
                    syncSelectedEquipmentControl() {
                        if (!this.selectedEquipmentId) {
                            this.selectedEquipmentControl = {
                                x: 0,
                                y: 0,
                                width: 0,
                                height: 0,
                                rotation: 0,
                            };
                            return;
                        }

                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );

                        if (!item) return;

                        const rect = item.getBoundingClientRect();
                        const parentRect = item.parentElement?.getBoundingClientRect() || { left: 0, top: 0 };

                        this.selectedEquipmentControl = {
                            x: rect.left - parentRect.left + rect.width / 2,
                            y: rect.top - parentRect.top + rect.height + 12,
                            width: Number(item.dataset.width || rect.width),
                            height: Number(item.dataset.height || rect.height),
                            rotation: Number(item.dataset.rotation || 0),
                        };
                        this.equipmentRotationDisplayAngle = this.formatRotationDisplay(Number(item.dataset.rotation || 0));
                        this.updateEquipmentRotateHandlePlacement();
                    },
                    startBlueprintPan(event) {
                        // Only left mouse
                        if (event.button !== 0) return;
                        if (!this.spacePressed) return;

                        // Ignore clicks on rooms
                        if (event.target.closest(".room-block")) return;

                        this.blueprint.isPanning = true;

                        this.blueprint.startX = event.clientX;

                        this.blueprint.startY = event.clientY;

                        this.blueprint.originX = this.blueprint.panX;

                        this.blueprint.originY = this.blueprint.panY;
                    },
                    moveBlueprintPan(event) {
                        if (!this.blueprint.isPanning) return;
                        this.blueprint.panX =
                            this.blueprint.originX + event.clientX - this.blueprint.startX;
                        this.blueprint.panY =
                            this.blueprint.originY + event.clientY - this.blueprint.startY;
                        this.$nextTick(() => this.updateRotateHandlePlacement());
                    },
                    endBlueprintPan() {
                        this.blueprint.isPanning = false;
                    },
                    resetBlueprintView() {
                        this.fitBlueprint();
                    },

                    setDrawerOpen(open) {
                        const nextOpen = Boolean(open);

                        if (nextOpen === this.drawerOpen) {
                            return;
                        }

                        const viewport = this.$refs.blueprintViewport;
                        const currentWidth = viewport ? viewport.clientWidth : 0;
                        const drawerWidth = this.getDrawerSlotWidth();
                        const predictedWidth = nextOpen
                            ? Math.max(160, currentWidth - drawerWidth)
                            : currentWidth + drawerWidth;

                        this.drawerOpen = nextOpen;

                        // Closing resets the double-click stamp so the next open
                        // always needs two fresh clicks on a room.
                        if (!nextOpen) {
                            this.roomTapStamp = { id: null, at: 0 };
                        }

                        try {
                            localStorage.setItem(
                                "prism.buildingLayout.drawerOpen",
                                this.drawerOpen ? "1" : "0",
                            );
                        } catch (error) {
                            // Ignore storage failures (private mode, etc.)
                        }

                        // Force a synchronous style flush so the grid track
                        // shrinks before we measure/fit the blueprint.
                        if (this.$el) {
                            void this.$el.offsetWidth;
                        }

                        this.animateBlueprintForDrawer(predictedWidth);
                    },

                    getDrawerSlotWidth() {
                        if (typeof window === "undefined" || window.innerWidth < 1280) {
                            return 0;
                        }

                        const drawerWidth = Math.min(
                            460,
                            Math.max(360, window.innerWidth * 0.22),
                        );
                        const workspaceGap = 24;

                        return drawerWidth + workspaceGap;
                    },

                    animateBlueprintForDrawer(predictedViewportWidth) {
                        if (this.drawerAnimFrame) {
                            cancelAnimationFrame(this.drawerAnimFrame);
                            this.drawerAnimFrame = 0;
                        }

                        if (this.drawerAnimTimer) {
                            clearTimeout(this.drawerAnimTimer);
                            this.drawerAnimTimer = 0;
                        }

                        this.drawerAnimating = true;

                        if (predictedViewportWidth && window.innerWidth >= 1280) {
                            this.fitBlueprint({
                                viewportWidth: predictedViewportWidth,
                                immediate: true,
                            });
                        }

                        // Refit on successive frames while the grid column
                        // settles, then once more after the transition ends.
                        const refitFromDom = () => {
                            this.fitBlueprint({ immediate: true });
                        };

                        this.drawerAnimFrame = requestAnimationFrame(() => {
                            refitFromDom();
                            this.drawerAnimFrame = requestAnimationFrame(() => {
                                this.drawerAnimFrame = 0;
                                refitFromDom();
                            });
                        });

                        this.drawerAnimTimer = setTimeout(() => {
                            this.drawerAnimTimer = 0;
                            this.drawerAnimating = false;
                            this.$nextTick(() => {
                                requestAnimationFrame(() => this.fitBlueprint());
                            });
                        }, 320);
                    },

                    toggleDrawer() {
                        this.setDrawerOpen(!this.drawerOpen);
                    },

                    // Single click = select only. Double-click opens the inspector.
                    selectRoom(roomId) {
                        const nextId = Number(roomId);

                        if (!Number.isFinite(nextId) || nextId <= 0) {
                            return;
                        }

                        // Edit mode: Interact.js tap owns selection. Ignore native
                        // click so it cannot pair with tap and fake a double-click.
                        if (this.editMode && !this.roomPaintMode) {
                            return;
                        }

                        this.selectedRoom = nextId;
                    },

                    openRoomInspector(roomId) {
                        const nextId = Number(roomId);

                        if (!Number.isFinite(nextId) || nextId <= 0) {
                            return;
                        }

                        this.selectedRoom = nextId;
                        this.roomTapStamp = { id: null, at: 0 };

                        if (!this.drawerOpen) {
                            this.setDrawerOpen(true);
                        }
                    },

                    

                    fitBlueprint(options = {}) {

                        const workspace = this.$refs.blueprintWorkspace;
                        const toolbar = this.$refs.blueprintToolbar;
                        const viewport = this.$refs.blueprintViewport;
                        const dock = this.$refs.blueprintControlsDock;

                        if (!workspace || !viewport) return;

                        if (this.fitBlueprintRaf) {
                            cancelAnimationFrame(this.fitBlueprintRaf);
                            this.fitBlueprintRaf = 0;
                        }

                        const dockWidth = dock ? dock.clientWidth : 64;

                        const padding = {
                            top: 8,
                            right: dockWidth + 10,
                            bottom: 8,
                            left: 8,
                        };

                        const blueprintWidth = this.blueprint.width;
                        const blueprintHeight = this.blueprint.height;

                        const viewportWidth = options.viewportWidth ?? viewport.clientWidth;

                        let targetViewportHeight;

                        if (this.isFullscreen) {

                            const toolbarHeight = toolbar
                                ? toolbar.getBoundingClientRect().height
                                : 0;

                            targetViewportHeight =
                                window.innerHeight -
                                toolbarHeight -
                                24;

                        } else {
                            // Always size height so a width-fit zoom wins. That keeps
                            // the dashed layout balanced when the inspector opens or
                            // closes instead of letterboxing/clipping rooms.
                            const widthForFit = Math.max(
                                160,
                                viewportWidth - padding.left - padding.right,
                            );
                            const heightToFillWidth =
                                (widthForFit * blueprintHeight) / blueprintWidth +
                                padding.top +
                                padding.bottom;
                            const maxHeight = this.drawerOpen ? 850 : 920;

                            targetViewportHeight = Math.max(
                                500,
                                Math.min(heightToFillWidth, maxHeight),
                            );
                        }

                        viewport.style.height = targetViewportHeight + "px";

                        const toolbarHeight = toolbar
                            ? toolbar.getBoundingClientRect().height
                            : 0;

                        workspace.style.height =
                            toolbarHeight + targetViewportHeight + "px";

                        const applyZoom = () => {
                            this.fitBlueprintRaf = 0;

                            const measuredWidth = options.viewportWidth ?? viewport.clientWidth;
                            const availableWidth =
                                measuredWidth -
                                padding.left -
                                padding.right;

                            const availableHeight =
                                viewport.clientHeight -
                                padding.top -
                                padding.bottom;

                            if (availableWidth <= 0 || availableHeight <= 0) {
                                return;
                            }

                            const zoom = Math.min(
                                availableWidth / blueprintWidth,
                                availableHeight / blueprintHeight
                            );

                            this.blueprint.zoom = zoom;

                            this.zoomInput = Math.round(zoom * 100);

                            const scaledWidth =
                                blueprintWidth * zoom;

                            const scaledHeight =
                                blueprintHeight * zoom;

                            this.blueprint.panX =
                                padding.left +
                                (availableWidth - scaledWidth) / 2;

                            this.blueprint.panY =
                                (availableHeight - scaledHeight) / 2;
                        };

                        if (options.immediate) {
                            applyZoom();
                        } else {
                            this.fitBlueprintRaf = requestAnimationFrame(applyZoom);
                        }

                        this.$nextTick(() => this.updateRotateHandlePlacement());

                    },
                    async toggleFullscreen(){

                        const element = this.$refs.blueprintWorkspace;

                        if(!document.fullscreenElement){

                            await element.requestFullscreen();

                            this.isFullscreen = true;

                            this.$nextTick(()=>{

                                this.fitBlueprint();

                            });

                        }else{

                            await document.exitFullscreen();

                            this.isFullscreen = false;

                            this.$nextTick(()=>{

                                this.fitBlueprint();

                            });

                        }

                    },
                    get currentRoom() {

                        return this.roomCatalog.find(

                            room => room.id === this.selectedRoom

                        ) || null;

                    },
                    // =====================================
                    // Date formatting helper
                    // =====================================
                    formatDate(date) {

                        if (!date) return '';

                        return new Date(date).toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                        });

                    },

                    // =====================================
                    // Time ago helper
                    // =====================================
                    // =====================================
                    // Time ago helper
                    // Replace your existing timeAgo()
                    // =====================================
                    // =====================================
                    // Time ago helper
                    // =====================================
                    timeAgo(date) {

                        if (!date) return '';

                        const seconds = Math.floor((Date.now() - new Date(date)) / 1000);

                        if (seconds < 60) {
                            return 'Just now';
                        }

                        const minutes = Math.floor(seconds / 60);

                        if (minutes < 60) {
                            return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
                        }

                        const hours = Math.floor(minutes / 60);

                        if (hours < 24) {
                            return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
                        }

                        const days = Math.floor(hours / 24);

                        if (days < 30) {
                            return `${days} day${days !== 1 ? 's' : ''} ago`;
                        }

                        // =====================================
                        // More accurate month calculation
                        // Average month = 30.44 days
                        // =====================================
                        const months = Math.floor(days / 30.44);

                        if (months < 12) {
                            return `${months} month${months !== 1 ? 's' : ''} ago`;
                        }

                        // =====================================
                        // More accurate year calculation
                        // Average year = 365.25 days
                        // =====================================
                        const years = Math.floor(days / 365.25);

                        return `${years} year${years !== 1 ? 's' : ''} ago`;

                    },
                    toggleBlueprintEdit(){

                        if(!this.editMode){

                            this.originalBlueprintLayout = this.snapshotLayoutRooms();
                            this.clearLayoutUndoStack();

                            this.editMode = true;

                            this.$nextTick(() => {
                                if (window.lucide) lucide.createIcons();
                            });

                            return;

                        }

                        if(this.layoutDirty){

                            this.blueprintLayoutModal.title =
                                "Discard Room Layout Changes?";

                            this.blueprintLayoutModal.message =
                                "You have unsaved changes. Leaving Edit Mode will restore every room to its previous position and size.";

                            this.blueprintLayoutModal.open = true;

                            return;

                        }

                        this.editMode = false;

                        this.roomPaintMode = false;
                        this.clearLayoutUndoStack();

                    },
                    snapshotLayoutRooms() {
                        return [...document.querySelectorAll(".room-block")].map((room) => ({
                            id: Number(room.dataset.id),
                            x: Number(room.dataset.x) || 0,
                            y: Number(room.dataset.y) || 0,
                            width: Number(room.dataset.width) || 0,
                            height: Number(room.dataset.height) || 0,
                            rotation: Number(room.dataset.rotation || 0),
                            color: room.dataset.color || room.style.background || "#60A5FA",
                        }));
                    },
                    layoutSnapshotsEqual(left = [], right = []) {
                        if (left.length !== right.length) {
                            return false;
                        }

                        const serialize = (rooms) =>
                            JSON.stringify(
                                [...rooms]
                                    .map((room) => ({
                                        id: Number(room.id),
                                        x: Number(room.x) || 0,
                                        y: Number(room.y) || 0,
                                        width: Number(room.width) || 0,
                                        height: Number(room.height) || 0,
                                        rotation: Number(room.rotation || 0),
                                        color: String(room.color || "").toLowerCase(),
                                    }))
                                    .sort((a, b) => a.id - b.id),
                            );

                        return serialize(left) === serialize(right);
                    },
                    captureLayoutUndo() {
                        if (!this.editMode) {
                            return;
                        }

                        const now = Date.now();
                        const withinGroup =
                            this.layoutUndoStack.length > 0 &&
                            this.layoutUndoLastAt > 0 &&
                            now - this.layoutUndoLastAt < 3000;

                        if (withinGroup) {
                            this.layoutUndoOpenedGroup = false;
                            return;
                        }

                        this.layoutUndoStack = [
                            ...this.layoutUndoStack.slice(-39),
                            this.snapshotLayoutRooms(),
                        ];
                        this.layoutUndoOpenedGroup = true;
                    },
                    dropLayoutUndoIfUnchanged() {
                        const last = this.layoutUndoStack[this.layoutUndoStack.length - 1];

                        if (!last) {
                            this.layoutUndoOpenedGroup = false;
                            return;
                        }

                        if (
                            this.layoutUndoOpenedGroup &&
                            this.layoutSnapshotsEqual(last, this.snapshotLayoutRooms())
                        ) {
                            this.layoutUndoStack = this.layoutUndoStack.slice(0, -1);
                            this.layoutUndoOpenedGroup = false;
                            return;
                        }

                        this.layoutUndoLastAt = Date.now();
                        this.layoutUndoOpenedGroup = false;
                    },
                    clearLayoutUndoStack() {
                        this.layoutUndoStack = [];
                        this.layoutUndoLastAt = 0;
                        this.layoutUndoOpenedGroup = false;
                    },
                    applyLayoutSnapshot(snapshot = []) {
                        snapshot.forEach((original) => {
                            const room = document.querySelector(
                                `.room-block[data-id="${original.id}"]`,
                            );

                            if (!room) {
                                return;
                            }

                            room.dataset.x = original.x;
                            room.dataset.y = original.y;
                            room.dataset.width = original.width;
                            room.dataset.height = original.height;
                            room.dataset.rotation = original.rotation || 0;
                            room.dataset.color = original.color;
                            room.style.left = original.x + "px";
                            room.style.top = original.y + "px";
                            room.style.width = original.width + "px";
                            room.style.height = original.height + "px";
                            room.style.background = original.color;
                            room.style.setProperty("--room-depth", original.color);
                            room.style.transform = `rotate(${original.rotation || 0}deg)`;
                            room.style.transformOrigin = "center center";
                            this.syncRoomLabel(room);

                            const catalogRoom = this.roomCatalog.find(
                                (item) => item.id === original.id,
                            );

                            if (catalogRoom) {
                                catalogRoom.color = original.color;
                                catalogRoom.x = original.x;
                                catalogRoom.y = original.y;
                                catalogRoom.width = original.width;
                                catalogRoom.height = original.height;
                            }
                        });

                        this.syncSelectedRoomControl();
                        this.syncRoomEdges();
                    },
                    syncLayoutDirtyFromOriginal() {
                        this.layoutDirty = !this.layoutSnapshotsEqual(
                            this.snapshotLayoutRooms(),
                            this.originalBlueprintLayout || [],
                        );
                    },
                    undoLayoutChange() {
                        if (!this.editMode || !this.layoutUndoStack.length) {
                            return;
                        }

                        if (this.roomDrag) {
                            this.commitRoomDrag();
                        }

                        if (this.roomResize) {
                            this.commitRoomResize();
                        }

                        const stack = [...this.layoutUndoStack];
                        const snapshot = stack.pop();
                        this.layoutUndoStack = stack;
                        this.applyLayoutSnapshot(snapshot);
                        this.syncLayoutDirtyFromOriginal();
                        this.layoutUndoLastAt = 0;
                        this.layoutUndoOpenedGroup = false;
                    },
                    handleLayoutUndoHotkey(event) {
                        const key = String(event.key || "").toLowerCase();

                        if (key !== "z" || !(event.ctrlKey || event.metaKey) || event.shiftKey) {
                            return;
                        }

                        const target = event.target;
                        const tag = (target?.tagName || "").toLowerCase();
                        const isTypingContext =
                            target?.isContentEditable ||
                            ["input", "textarea", "select"].includes(tag);

                        if (!this.editMode || isTypingContext) {
                            return;
                        }

                        event.preventDefault();
                        this.undoLayoutChange();
                    },
                    discardBlueprintChanges(){

                        this.applyLayoutSnapshot(this.originalBlueprintLayout);
                        this.clearLayoutUndoStack();

                        this.layoutDirty = false;

                        this.editMode = false;

                        this.roomPaintMode = false;

                        this.blueprintLayoutModal.open = false;

                    },
                    toggleRoomPaintMode() {
                        if (!this.editMode) {
                            return;
                        }

                        this.roomPaintMode = !this.roomPaintMode;

                        if (!this.roomPaintMode) {
                            return;
                        }

                        if (this.selectedRoom) {
                            const room = this.roomCatalog.find((item) => item.id === this.selectedRoom);
                            if (room) {
                                this.roomPaintColor = room.color || this.defaultRoomColor(room.type);
                                return;
                            }
                        }

                        const fallbackRoom = this.roomCatalog.find((item) => item.floor_id === this.activeFloor)
                            || this.roomCatalog[0];

                        if (fallbackRoom) {
                            this.selectRoomForPaint(fallbackRoom.id);
                        }
                    },
                    selectRoomForPaint(roomId) {
                        const normalizedRoomId = Number(roomId);
                        const room = this.roomCatalog.find((item) => item.id === normalizedRoomId);

                        if (!room) {
                            return;
                        }

                        this.selectedRoom = normalizedRoomId;
                        this.roomTapStamp = { id: null, at: 0 };
                        this.roomPaintColor = room.color || this.defaultRoomColor(room.type);
                    },
                    defaultRoomColor(type) {
                        switch (String(type || '').trim()) {
                            case 'Lecture Room':
                                return '#84CC16';
                            case 'Computer Laboratory':
                                return '#FFF200';
                            case 'Hospitality Suite':
                            case 'HM Room':
                                return '#F39200';
                            case 'Hotel Room Simulation':
                                return '#EA580C';
                            case 'Library':
                                return '#A78BFA';
                            case 'Canteen':
                                return '#84CC16';
                            case 'Clinic':
                            case 'School Clinic':
                                return '#FB7185';
                            case 'Faculty Room':
                            case 'Office':
                            case 'Exit':
                                return '#22C55E';
                            case 'Utility':
                                return '#94A3B8';
                            case 'Hallway':
                                return '#CBD5E1';
                            case 'Restroom':
                                return '#38BDF8';
                            case 'Elevator':
                                return '#64748B';
                            case 'Stairs':
                                return '#94A3B8';
                            default:
                                return '#60A5FA';
                        }
                    },
                    paintRoomColor(roomId, color = this.roomPaintColor) {
                        const normalizedRoomId = Number(roomId);
                        const room = this.roomCatalog.find((item) => item.id === normalizedRoomId);

                        if (!room) {
                            return;
                        }

                        const resolvedColor = color || this.defaultRoomColor(room.type);
                        const node = document.querySelector(`.room-block[data-id="${normalizedRoomId}"]`);

                        if (
                            room.color === resolvedColor &&
                            (!node || node.dataset.color === resolvedColor)
                        ) {
                            return;
                        }

                        this.captureLayoutUndo();
                        room.color = resolvedColor;

                        if (node) {
                            node.dataset.color = resolvedColor;
                            node.style.background = resolvedColor;
                            node.style.setProperty("--room-depth", resolvedColor);
                            this.applyRoomLabelContrast(node);
                        }

                        if (this.selectedRoom === normalizedRoomId) {
                            this.roomPaintColor = resolvedColor;
                        }

                        this.dropLayoutUndoIfUnchanged();
                        this.layoutDirty = true;
                    },
                    resetSelectedRoomColor() {
                        if (!this.selectedRoom) {
                            return;
                        }

                        const room = this.roomCatalog.find((item) => item.id === this.selectedRoom);

                        if (!room) {
                            return;
                        }

                        this.paintRoomColor(this.selectedRoom, this.defaultRoomColor(room.type));
                    },
                    focusRoomSearch() {
                        const query = this.roomSearch.trim().toLowerCase();
                        if (!query) return;

                        const room = this.roomCatalog.find((item) =>
                            [item.name, item.type]
                                .filter(Boolean)
                                .join(" ")
                                .toLowerCase()
                                .includes(query),
                        );

                        if (!room) {
                            this.toast = "No matching room found";
                            setTimeout(() => (this.toast = ""), 2500);
                            return;
                        }

                        this.activeFloor = room.floor_id;
                        this.selectedRoom = room.id;
                        this.blueprint.zoom = 1.35;
                        this.blueprint.panX = Math.round(
                            360 - room.x * this.blueprint.zoom,
                        );
                        this.blueprint.panY = Math.round(
                            240 - room.y * this.blueprint.zoom,
                        );

                        this.$nextTick(() => {
                            const node = document.querySelector(
                                `.room-block[data-id="${room.id}"]`,
                            );
                            if (!node) return;
                            node.classList.remove("room-search-highlight");
                            void node.offsetWidth;
                            node.classList.add("room-search-highlight");
                        });
                    },
                    addFloor() {
                        this.form.floors.push({
                            level: "3rd Floor",
                            rooms: [],
                        });

                        this.wizardFloorIndex = this.form.floors.length - 1;
                    },
                    nextWizardStep() {
                        if (!this.canManageCampusSetup) {
                            this.toast = "You do not have permission to manage campus setup.";
                            setTimeout(() => (this.toast = ""), 3000);
                            return;
                        }

                        if (this.step === 1) {
                            if (this.isWizardSetupLocked) {
                                this.step = Math.min(4, this.step + 1);
                                return;
                            }

                            const buildingName = String(this.form.building_name || "").trim();

                            if (!buildingName) {
                                this.toast = "Campus name is required before continuing.";
                                setTimeout(() => (this.toast = ""), 3000);
                                return;
                            }

                            const min = Number(this.form.minFloor);
                            const max = Number(this.form.maxFloor);

                            if (!Number.isFinite(min) || !Number.isFinite(max)) {
                                this.toast = "Enter valid floor numbers first.";
                                setTimeout(() => (this.toast = ""), 3000);
                                return;
                            }

                            let safeMin = Math.max(1, Math.min(30, Math.trunc(min)));
                            let safeMax = Math.max(1, Math.min(30, Math.trunc(max)));

                            if (safeMin > safeMax) {
                                [safeMin, safeMax] = [safeMax, safeMin];
                            }

                            this.form.minFloor = safeMin;
                            this.form.maxFloor = safeMax;
                            this.generateFloors();

                            if (!this.form.floors.length) {
                                this.toast = "No floors were generated. Check your floor range.";
                                setTimeout(() => (this.toast = ""), 3000);
                                return;
                            }

                            this.wizardFloorIndex = Math.min(
                                this.wizardFloorIndex,
                                this.form.floors.length - 1,
                            );
                        }

                        if (this.step === 3) {
                            this.step3ValidationAttempted = true;

                            if (!this.validateStep3Entries()) {
                                this.toast = 'Please fix the highlighted fields in Step 3.';
                                setTimeout(() => (this.toast = ''), 3000);
                                return;
                            }
                        }

                        this.step = Math.min(4, this.step + 1);
                    },
                    goToWizardStep(target) {
                        const boundedTarget = Math.max(1, Math.min(4, Number(target) || 1));

                        if (boundedTarget <= this.step) {
                            this.step = boundedTarget;
                            return;
                        }

                        while (this.step < boundedTarget) {
                            const before = this.step;
                            this.nextWizardStep();

                            if (this.step === before) {
                                break;
                            }
                        }
                    },
                    generateFloors() {
                        const min = Number(this.form.minFloor);
                        const max = Number(this.form.maxFloor);

                        if (!Number.isFinite(min) || !Number.isFinite(max)) {
                            return;
                        }

                        const safeMin = Math.max(1, Math.min(30, Math.trunc(min)));
                        const safeMax = Math.max(1, Math.min(30, Math.trunc(max)));

                        if (safeMin > safeMax) {
                            return;
                        }

                        const activeFloorLevel = this.activeWizardFloor?.level || null;
                        const existingFloors = {};

                        this.form.floors.forEach((floor) => {
                            existingFloors[floor.level] = floor;
                        });

                        const newFloors = [];

                        for (
                            let floorNumber = safeMin;
                            floorNumber <= safeMax;
                            floorNumber++
                        ) {
                            const level = this.floorLabel(floorNumber);

                            if (existingFloors[level]) {
                                newFloors.push(existingFloors[level]);
                            } else {
                                newFloors.push({
                                    id: null,

                                    level,

                                    rooms: [],
                                });
                            }
                        }

                        this.form.floors = newFloors;
                        this.wizardHasLocalChanges = true;

                        if (!this.form.floors.length) {
                            this.wizardFloorIndex = 0;
                            return;
                        }

                        const matchedIndex = activeFloorLevel
                            ? this.form.floors.findIndex((floor) => floor.level === activeFloorLevel)
                            : -1;

                        this.wizardFloorIndex = matchedIndex >= 0
                            ? matchedIndex
                            : Math.min(this.wizardFloorIndex, this.form.floors.length - 1);
                    },
                    floorLabel(number) {
                        const mod10 = number % 10;
                        const mod100 = number % 100;

                        if (mod10 === 1 && mod100 !== 11) {
                            return `${number}st Floor`;
                        }

                        if (mod10 === 2 && mod100 !== 12) {
                            return `${number}nd Floor`;
                        }

                        if (mod10 === 3 && mod100 !== 13) {
                            return `${number}rd Floor`;
                        }

                        return `${number}th Floor`;
                    },
                    addRoom(fi) {
                        this.wizardFloorIndex = fi;
                        this.wizardHasLocalChanges = true;

                        this.form.floors[fi].rooms.push({
                            id: null,

                            client_key: `new-room-${++this.wizardRoomKey}`,

                            name: "",

                            type: "Lecture Room",

                            status: "Normal",

                            equipment: [],
                        });

                        this.$nextTick(() => {
                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });
                    },
                    addEquipment(fi, ri) {
                        this.wizardHasLocalChanges = true;
                        this.form.floors[fi].rooms[ri].equipment.push({
                            id: null,

                            client_key: `new-${++this.wizardEquipmentKey}`,

                            name: "",

                            category_id: "",

                            quantity: 1,

                            condition: "Good",

                            zone: "Holding",
                        });

                        this.$nextTick(() => {
                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });
                    },
                    addQuickRoom(fi) {
                        const floor = this.form.floors[fi];

                        if (!floor) {
                            return;
                        }

                        floor.rooms.push({
                            id: null,
                            client_key: `quick-room-${++this.wizardRoomKey}`,
                            name: '',
                            type: 'Lecture Room',
                            status: 'Normal',
                            equipment: [],
                        });

                        this.wizardHasLocalChanges = true;

                        this.$nextTick(() => {
                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });

                        this.wizardFloorIndex = fi;
                    },
                    clearStep3ErrorsForFloor(fi, types = []) {
                        const prefixTypes = Array.isArray(types) && types.length
                            ? types
                            : null;

                        this.step3InlineErrors = Object.fromEntries(
                            Object.entries(this.step3InlineErrors).filter(([key]) => {
                                const parts = key.split('-');
                                const errorType = parts[0] === 'eq' ? `${parts[0]}-${parts[1]}` : parts[0];
                                const floorIndexPart = parts[0] === 'eq' ? parts[2] : parts[1];

                                if (Number(floorIndexPart) !== Number(fi)) {
                                    return true;
                                }

                                if (!prefixTypes) {
                                    return false;
                                }

                                return !prefixTypes.includes(errorType);
                            }),
                        );
                    },
                    validateStep3RoomNamesForFloor(fi) {
                        const floor = this.form.floors?.[fi];

                        if (!floor) {
                            return;
                        }

                        const existingNames = new Set(
                            (this.existingRoomNamesByFloor?.[String(floor.id ?? '')] || [])
                                .map((name) => String(name || '').trim().toLowerCase())
                                .filter((name) => name.length > 0),
                        );
                        const seenNames = new Set();

                        (floor.rooms || []).forEach((room, ri) => {
                            const roomName = String(room.name || '').trim();

                            if (!roomName && !this.roomHasMeaningfulData(room)) {
                                return;
                            }

                            if (!roomName) {
                                this.setStep3Error('room-name', fi, ri, null, 'Room name is required');
                                return;
                            }

                            const normalized = roomName.toLowerCase();

                            if (seenNames.has(normalized) || existingNames.has(normalized)) {
                                this.setStep3Error('room-name', fi, ri, null, 'Room name already exists on this floor');
                            }

                            seenNames.add(normalized);
                        });
                    },
                    handleStep3RoomNameInput(fi) {
                        this.clearStep3ErrorsForFloor(fi, ['room-name']);

                        if (this.step === 3 && this.step3ValidationAttempted) {
                            this.validateStep3RoomNamesForFloor(fi);
                        }
                    },
                    selectWizardFloor(fi, options = {}) {
                        const boundedIndex = Math.max(
                            0,
                            Math.min(Number(fi) || 0, Math.max((this.form.floors?.length || 1) - 1, 0)),
                        );
                        const previousIndex = this.wizardFloorIndex;

                        this.wizardFloorIndex = boundedIndex;

                        if (options.revalidateStep3 !== false && this.step === 3 && this.step3ValidationAttempted) {
                            this.clearStep3ErrorsForFloor(previousIndex, ['room-name']);
                            this.clearStep3ErrorsForFloor(boundedIndex, ['room-name']);
                            this.validateStep3RoomNamesForFloor(boundedIndex);
                        }

                        this.$nextTick(() => this.refreshCampusWizardIcons());
                    },
                    step3ErrorKey(type, fi, ri = null, ei = null) {
                        return [type, fi, ri, ei].filter((part) => part !== null && part !== undefined).join('-');
                    },
                    setStep3Error(type, fi, ri = null, ei = null, message = 'Required field') {
                        const key = this.step3ErrorKey(type, fi, ri, ei);
                        this.step3InlineErrors[key] = message;
                    },
                    getStep3Error(type, fi, ri = null, ei = null) {
                        const key = this.step3ErrorKey(type, fi, ri, ei);
                        return this.step3InlineErrors[key] || '';
                    },
                    clearStep3InlineErrors() {
                        this.step3InlineErrors = {};
                    },
                    clearStep4InlineErrors() {
                        this.step4InlineErrors = [];
                    },
                    roomHasName(room) {
                        return String(room?.name || '').trim().length > 0;
                    },
                    equipmentHasMeaningfulData(eq) {
                        return String(eq?.name || '').trim().length > 0
                            || String(eq?.category_id || '').trim().length > 0;
                    },
                    roomHasMeaningfulData(room) {
                        if (this.roomHasName(room)) {
                            return true;
                        }

                        return (room?.equipment || []).some((eq) => this.equipmentHasMeaningfulData(eq));
                    },
                    pruneIgnorableWizardDrafts() {
                        this.form.floors = (this.form.floors || []).map((floor) => ({
                            ...floor,
                            rooms: (floor.rooms || [])
                                .map((room) => ({
                                    ...room,
                                    name: String(room?.name || '').trim(),
                                    equipment: (room.equipment || []).filter((eq) => this.equipmentHasMeaningfulData(eq)),
                                }))
                                .filter((room) => this.roomHasMeaningfulData(room)),
                        }));
                    },
                    countNamedRoomsForFloor(floor) {
                        return (floor?.rooms || []).filter((room) => this.roomHasName(room)).length;
                    },
                    countDraftRooms() {
                        return (this.form.floors || []).reduce(
                            (total, floor) => total + this.countNamedRoomsForFloor(floor),
                            0,
                        );
                    },
                    floorStructureSignature(floors = []) {
                        return (floors || [])
                            .map((floor) => `${floor?.id ?? 'new'}:${String(floor?.level || '').trim()}`)
                            .join('|');
                    },
                    captureWizardFloorBaseline() {
                        this.wizardBaselineFloorSignature = this.floorStructureSignature(this.form.floors || []);
                    },
                    wizardFloorStructureChanged() {
                        const current = this.floorStructureSignature(this.form.floors || []);

                        // First-time campus create: always treat floors as part of the review.
                        if (!this.form.setup_locked && !this.wizardBaselineFloorSignature) {
                            return true;
                        }

                        return current !== this.wizardBaselineFloorSignature;
                    },
                    wizardReviewRooms() {
                        const items = [];

                        (this.form.floors || []).forEach((floor) => {
                            (floor.rooms || []).forEach((room) => {
                                if (!this.roomHasName(room)) {
                                    return;
                                }

                                const equipmentNames = (room.equipment || [])
                                    .map((eq) => String(eq?.name || '').trim())
                                    .filter((name) => name.length > 0);

                                items.push({
                                    floor: floor.level || 'Floor',
                                    name: String(room.name || '').trim(),
                                    equipmentCount: equipmentNames.length,
                                    equipmentNames,
                                });
                            });
                        });

                        return items;
                    },
                    countDraftEquipment() {
                        return this.wizardReviewRooms().reduce(
                            (total, room) => total + Number(room.equipmentCount || 0),
                            0,
                        );
                    },
                    validateStep3Entries() {
                        this.clearStep3InlineErrors();

                        let hasError = false;

                        (this.form.floors || []).forEach((floor, fi) => {
                            const existingNames = new Set(
                                (this.existingRoomNamesByFloor?.[String(floor.id ?? '')] || [])
                                    .map((name) => String(name || '').trim().toLowerCase())
                                    .filter((name) => name.length > 0),
                            );
                            const seenNames = new Set();

                            (floor.rooms || []).forEach((room, ri) => {
                                const roomName = String(room.name || '').trim();

                                if (!roomName && !this.roomHasMeaningfulData(room)) {
                                    return;
                                }

                                if (!roomName) {
                                    this.setStep3Error('room-name', fi, ri, null, 'Room name is required');
                                    hasError = true;
                                } else {
                                    const normalized = roomName.toLowerCase();

                                    if (seenNames.has(normalized) || existingNames.has(normalized)) {
                                        this.setStep3Error('room-name', fi, ri, null, 'Room name already exists on this floor');
                                        hasError = true;
                                    }

                                    seenNames.add(normalized);
                                }

                                (room.equipment || []).forEach((eq, ei) => {
                                    if (!this.equipmentHasMeaningfulData(eq)) {
                                        return;
                                    }

                                    if (!String(eq.name || '').trim()) {
                                        this.setStep3Error('eq-name', fi, ri, ei, 'Equipment name is required');
                                        hasError = true;
                                    }
                                });
                            });
                        });

                        return !hasError;
                    },
                    validateStep4BeforeSubmit() {
                        this.clearStep4InlineErrors();

                        if (this.countDraftRooms() === 0) {
                            this.step4InlineErrors.push('Add at least one room before saving campus updates.');
                        }

                        if (!this.validateStep3Entries()) {
                            this.step4InlineErrors.push('Please fix highlighted room/equipment errors before saving.');
                        }

                        return this.step4InlineErrors.length === 0;
                    },
                    submitCampusWizard(event) {
                        if (!this.canManageCampusSetup) {
                            this.toast = 'You do not have permission to manage campus setup.';
                            setTimeout(() => (this.toast = ''), 3000);
                            return;
                        }

                        this.pruneIgnorableWizardDrafts();

                        if (!this.validateStep4BeforeSubmit()) {
                            this.toast = 'Unable to save. Review inline validation badges.';
                            setTimeout(() => (this.toast = ''), 3200);
                            return;
                        }

                        this.syncCampusWizardFormPayload(event.target);

                        this.$nextTick(() => {
                            event.target.submit();
                        });
                    },
                    refreshCampusWizardIcons() {
                        const root = document.querySelector('[data-campus-wizard]');
                        if (!window.lucide || !root) {
                            return;
                        }
                        if (typeof lucide.createIcons === 'function') {
                            lucide.createIcons({ nodes: [root] });
                        }
                    },
                    syncCampusWizardFormPayload(formEl) {
                        if (!formEl) {
                            return;
                        }

                        formEl.querySelectorAll('[data-wizard-sync]').forEach((node) => node.remove());

                        // Avoid duplicate floors[...] values from visible step fields.
                        formEl.querySelectorAll('input[name^="floors["], select[name^="floors["], textarea[name^="floors["]').forEach((el) => {
                            el.disabled = true;
                        });

                        const appendHidden = (name, value) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = value == null ? '' : String(value);
                            input.setAttribute('data-wizard-sync', '1');
                            formEl.appendChild(input);
                        };

                        (this.form.floors || []).forEach((floor, fi) => {
                            appendHidden(`floors[${fi}][level]`, floor.level ?? '');
                            appendHidden(`floors[${fi}][id]`, floor.id ?? '');

                            (floor.rooms || []).forEach((room, ri) => {
                                const roomName = String(room.name || '').trim();
                                if (!roomName) {
                                    return;
                                }

                                appendHidden(`floors[${fi}][rooms][${ri}][id]`, room.id ?? '');
                                appendHidden(`floors[${fi}][rooms][${ri}][name]`, roomName);
                                appendHidden(`floors[${fi}][rooms][${ri}][type]`, room.type || 'Lecture Room');
                                appendHidden(`floors[${fi}][rooms][${ri}][status]`, room.status || 'Normal');

                                (room.equipment || []).forEach((eq, ei) => {
                                    const eqName = String(eq.name || '').trim();
                                    if (!eqName) {
                                        return;
                                    }

                                    appendHidden(`floors[${fi}][rooms][${ri}][equipment][${ei}][id]`, eq.id ?? '');
                                    appendHidden(`floors[${fi}][rooms][${ri}][equipment][${ei}][name]`, eqName);
                                    appendHidden(`floors[${fi}][rooms][${ri}][equipment][${ei}][category_id]`, eq.category_id ?? '');
                                    appendHidden(`floors[${fi}][rooms][${ri}][equipment][${ei}][quantity]`, eq.quantity ?? 1);
                                    appendHidden(`floors[${fi}][rooms][${ri}][equipment][${ei}][condition]`, eq.condition || 'Good');
                                    appendHidden(`floors[${fi}][rooms][${ri}][equipment][${ei}][zone]`, eq.zone || 'Holding');
                                });
                            });
                        });
                    },
                    async loadCampus(forceOverwrite = true) {
                        try {
                            const response = await fetch(
                                @js (route("maintenance.infrastructure.campus.load")),
                            );

                            if (!response.ok) {
                                throw new Error();
                            }

                            const data = await response.json();

                            if (!forceOverwrite && this.wizardHasLocalChanges) {
                                return;
                            }

                            this.form = Object.assign(
                                {
                                    building_name: "",

                                    building_logo: null,

                                    building_address: null,

                                    setup_locked: false,

                                    minFloor: 2,

                                    maxFloor: 3,

                                    floors: [],
                                },
                                data,
                            );

                            this.form.floors = (this.form.floors || []).map((floor) => ({
                                ...floor,
                                rooms: (floor.rooms || []).map((room) => ({
                                    ...room,
                                    client_key: room.client_key || `load-room-${++this.wizardRoomKey}`,
                                    equipment: (room.equipment || []).map((equipment) => ({
                                        ...equipment,
                                        client_key: equipment.client_key || `load-${++this.wizardEquipmentKey}`,
                                    })),
                                })),
                            }));

                            if (this.form.floors.length > 0) {
                                const numbers = this.form.floors.map((floor) =>
                                    parseInt(floor.level),
                                );

                                this.form.minFloor = Math.min(...numbers);

                                this.form.maxFloor = Math.max(...numbers);
                            }

                            this.wizardFloorIndex = 0;
                            this.wizardHasLocalChanges = false;
                            this.step3ValidationAttempted = false;
                            this.wizardSetupUnlocked = false;
                            this.unlockPromptOpen = false;
                            this.unlockCredential = "";
                            this.unlockVerifyBusy = false;
                            this.captureWizardFloorBaseline();
                        } catch (error) {
                            console.error(error);

                            this.toast = "Unable to load campus.";

                            setTimeout(() => (this.toast = ""), 3000);
                        }
                    },

                    openCampusWizard() {
                        this.step = (String(this.form.building_name || '').trim() || (this.form.floors || []).length > 0)
                            ? 2
                            : 1;

                        this.wizardOpen = true;
                        this.wizardFullscreen = false;

                        this.wizardHasLocalChanges = false;
                        this.step3ValidationAttempted = false;

                        this.loadCampus(false);

                        this.$nextTick(() => {
                            this.refreshCampusWizardIcons();
                        });
                    },

                    toggleWizardFullscreen() {
                        this.wizardFullscreen = !this.wizardFullscreen;
                        this.$nextTick(() => {
                            this.refreshCampusWizardIcons();
                        });
                    },

                    closeCampusWizard() {
                        this.wizardOpen = false;
                        this.wizardFullscreen = false;
                        this.step = 1;
                        this.loadCampus();
                    },
                    
                    // =====================================
                    // PHASE 2
                    // Reverse lookup
                    // Position -> Zone
                    // Place below zonePosition()
                    // =====================================

                    detectEquipmentZone(x, y){

                        if(y <= 25){

                            return "Front Wall";

                        }

                        if(y >= 80){

                            return "Rear Wall";

                        }

                        if(x <= 25){

                            return "Left Row Pods";

                        }

                        if(x >= 75){

                            return "Right Row Pods";

                        }

                        if(

                            x >= 35 &&
                            x <= 65 &&
                            y >= 30 &&
                            y <= 55

                        ){

                            return "Center Ceiling";

                        }

                        return "Storage";

                    },
                    syncEquipmentZone(node) {
                        const ids = String(node.dataset.groupIds || node.dataset.equipmentId || '')
                            .split(',')
                            .map((value) => Number(value))
                            .filter((value) => Number.isFinite(value) && value > 0);

                        if (!ids.length) return null;

                        const x = +node.dataset.x;
                        const y = +node.dataset.y;
                        const width = +node.dataset.width || 140;
                        const isFloorNode = node.classList.contains('comlab-floor-node');
                        const height = isFloorNode
                            ? Math.max(20, +node.dataset.height || 40)
                            : Math.max(80, +node.dataset.height || 96);
                        const rotation = +node.dataset.rotation || 0;
                        // Keep organizational zone separate from exact X/Y drag position.
                        const preservedZone = node.dataset.zone || null;

                        let firstItem = null;

                        ids.forEach((id) => {
                            const item = this.roomLayout.equipment.find(
                                (equipment) => equipment.id === id,
                            );

                            if (!item) return;

                            item.x = x;
                            item.y = y;
                            item.width = width;
                            item.height = height;
                            item.rotation = rotation;

                            const zone = this.isComlabRoomLayout() && this.isComlabFloorEquipment(item)
                                ? null
                                : (item.placement_zone
                                    || item.location
                                    || preservedZone
                                    || this.detectEquipmentZone(x, y));

                            item.location = zone;
                            item.placement_zone = zone;
                            node.dataset.zone = zone || '';

                            if (!firstItem) {
                                firstItem = item;
                            }

                            const room = this.roomCatalog.find(
                                (room) => room.id === this.roomLayout.id,
                            );

                            const catalogItem = room?.equipment?.find(
                                (equipment) => equipment.id === id,
                            );

                            if (catalogItem && catalogItem !== item) {
                                Object.assign(catalogItem, {
                                    x: item.x,
                                    y: item.y,
                                    width: item.width,
                                    height: item.height,
                                    rotation: item.rotation,
                                    location: zone,
                                    placement_zone: zone,
                                });
                            }
                        });

                        return firstItem;
                    },
                    
                    getLayoutZoom() {
                        const zoom = Number(this.blueprint?.zoom);

                        return Number.isFinite(zoom) && zoom > 0.01 ? zoom : 1;
                    },
                    getCanvasPointer(event, canvasRect = null, zoom = null) {
                        const canvas = this.$refs.blueprintCanvas;
                        const rect = canvasRect || canvas?.getBoundingClientRect();
                        const scale = zoom || this.getLayoutZoom();
                        const clientX = event.client?.x ?? event.clientX ?? 0;
                        const clientY = event.client?.y ?? event.clientY ?? 0;

                        if (!rect) {
                            return { x: 0, y: 0 };
                        }

                        return {
                            x: (clientX - rect.left) / scale,
                            y: (clientY - rect.top) / scale,
                        };
                    },
                    clampRoomPosition(el, x, y) {
                        const parent = el.parentElement;
                        const width = Number(el.dataset.width) || el.offsetWidth || 0;
                        const height = Number(el.dataset.height) || el.offsetHeight || 0;
                        const maxX = Math.max(
                            0,
                            (parent?.clientWidth || this.blueprint?.width || width) - width,
                        );
                        const maxY = Math.max(
                            0,
                            (parent?.clientHeight || this.blueprint?.height || height) - height,
                        );

                        return {
                            x: Math.min(Math.max(0, x), maxX),
                            y: Math.min(Math.max(0, y), maxY),
                        };
                    },
                    computeRoomResize(state, pointer) {
                        const dx = pointer.x - state.pointer.x;
                        const dy = pointer.y - state.pointer.y;
                        const edges = state.edges;
                        const minW = 20;
                        const minH = 80;
                        const maxW = 600;
                        const maxH = 450;
                        const right = state.startX + state.startWidth;
                        const bottom = state.startY + state.startHeight;

                        let x = state.startX;
                        let y = state.startY;
                        let width = state.startWidth;
                        let height = state.startHeight;

                        if (edges.left) {
                            x = state.startX + dx;
                            width = state.startWidth - dx;
                        } else if (edges.right) {
                            width = state.startWidth + dx;
                        }

                        if (edges.top) {
                            y = state.startY + dy;
                            height = state.startHeight - dy;
                        } else if (edges.bottom) {
                            height = state.startHeight + dy;
                        }

                        if (width < minW) {
                            width = minW;
                            if (edges.left) x = right - width;
                        } else if (width > maxW) {
                            width = maxW;
                            if (edges.left) x = right - width;
                        }

                        if (height < minH) {
                            height = minH;
                            if (edges.top) y = bottom - height;
                        } else if (height > maxH) {
                            height = maxH;
                            if (edges.top) y = bottom - height;
                        }

                        const parent = state.el.parentElement;
                        const parentW = parent?.clientWidth || this.blueprint?.width || width;
                        const parentH = parent?.clientHeight || this.blueprint?.height || height;

                        if (x < 0) {
                            if (edges.left) width += x;
                            x = 0;
                        }

                        if (y < 0) {
                            if (edges.top) height += y;
                            y = 0;
                        }

                        if (x + width > parentW) {
                            if (edges.left) {
                                x = parentW - width;
                            } else {
                                width = parentW - x;
                            }
                        }

                        if (y + height > parentH) {
                            if (edges.top) {
                                y = parentH - height;
                            } else {
                                height = parentH - y;
                            }
                        }

                        width = Math.max(minW, Math.min(maxW, width));
                        height = Math.max(minH, Math.min(maxH, height));
                        x = Math.max(0, Math.min(x, parentW - width));
                        y = Math.max(0, Math.min(y, parentH - height));

                        return { x, y, width, height };
                    },
                    applyRoomResizeVisual(el, rect) {
                        const x = Math.round(rect.x);
                        const y = Math.round(rect.y);
                        const width = Math.round(rect.width);
                        const height = Math.round(rect.height);

                        Object.assign(el.style, {
                            left: x + "px",
                            top: y + "px",
                            width: width + "px",
                            height: height + "px",
                        });
                        Object.assign(el.dataset, {
                            x,
                            y,
                            width,
                            height,
                        });
                        this.syncRoomLabel(el);
                        if (this.selectedRoom === Number(el.dataset.id)) {
                            this.updateRotateHandlePlacement();
                        }
                    },
                    commitRoomResize() {
                        const state = this.roomResize;

                        if (!state) {
                            return;
                        }

                        if (state.raf) {
                            cancelAnimationFrame(state.raf);
                            state.raf = 0;
                        }

                        if (state.latestPointer) {
                            this.applyRoomResizeVisual(
                                state.el,
                                this.computeRoomResize(state, state.latestPointer),
                            );
                        }

                        state.el.classList.remove("is-resizing");
                        this.roomResize = null;
                        this.dropLayoutUndoIfUnchanged();
                        this.layoutDirty = true;

                        if (this.selectedRoom === Number(state.el.dataset.id)) {
                            this.syncSelectedRoomControl();
                        }

                        this.syncRoomEdges();
                    },
                    applyRoomDragVisual(el, x, y) {
                        const originX = Number(el.dataset.x) || 0;
                        const originY = Number(el.dataset.y) || 0;
                        const rotation = Number(el.dataset.rotation || 0);

                        el.style.transform = `translate3d(${x - originX}px, ${y - originY}px, 0) rotate(${rotation}deg)`;
                    },
                    queueRoomDragFrame() {
                        if (!this.roomDrag || this.roomDrag.raf) {
                            return;
                        }

                        this.roomDrag.raf = requestAnimationFrame(() => {
                            this.flushRoomDrag();
                        });
                    },
                    flushRoomDrag() {
                        const drag = this.roomDrag;

                        if (!drag) {
                            return;
                        }

                        drag.raf = 0;

                        const next = this.clampRoomPosition(drag.el, drag.x, drag.y);

                        drag.x = next.x;
                        drag.y = next.y;
                        this.applyRoomDragVisual(drag.el, next.x, next.y);
                        if (this.selectedRoom === Number(drag.el.dataset.id)) {
                            this.updateRotateHandlePlacement();
                        }
                    },
                    commitRoomDrag() {
                        const drag = this.roomDrag;

                        if (!drag) {
                            return;
                        }

                        if (drag.raf) {
                            cancelAnimationFrame(drag.raf);
                            drag.raf = 0;
                        }

                        this.flushRoomDrag();

                        const el = drag.el;
                        const x = Math.round(drag.x);
                        const y = Math.round(drag.y);
                        const rotation = Number(el.dataset.rotation || 0);

                        el.dataset.x = x;
                        el.dataset.y = y;
                        el.style.left = x + "px";
                        el.style.top = y + "px";
                        el.style.transform = `rotate(${rotation}deg)`;
                        el.style.willChange = "";
                        el.classList.remove("is-dragging");

                        this.roomDrag = null;
                        this.dropLayoutUndoIfUnchanged();
                        this.layoutDirty = true;

                        if (this.selectedRoom === Number(el.dataset.id)) {
                            this.syncSelectedRoomControl();
                        }

                        this.syncRoomEdges();
                    },
                    bindDragging() {
                        if (!window.interact) {
                            console.warn("Interact.js not loaded.");
                            return;
                        }
                        interact(".room-block")
                            .on("tap", (event) => {

                                if (!this.editMode) return;

                                // Do not preventDefault — it suppresses native dblclick.
                                if (this.roomPaintMode) {
                                    this.selectRoomForPaint(
                                        Number(event.currentTarget.dataset.id),
                                    );

                                    return;
                                }

                                const nextId = Number(event.currentTarget.dataset.id);

                                if (!Number.isFinite(nextId) || nextId <= 0) {
                                    return;
                                }

                                const now = Date.now();
                                const prevAt = Number(this.roomTapStamp.at || 0);
                                const sameRoom = Number(this.roomTapStamp.id) === nextId;
                                const dt = now - prevAt;

                                // Same physical click often emits two tap events;
                                // ignore the duplicate so one click cannot open the drawer.
                                if (sameRoom && dt < 120) {
                                    this.selectedRoom = nextId;
                                    return;
                                }

                                // Real second tap on the same room → open inspector
                                if (sameRoom && dt >= 120 && dt <= 500) {
                                    this.openRoomInspector(nextId);
                                    return;
                                }

                                // First tap: select only
                                this.selectedRoom = nextId;
                                this.roomTapStamp = { id: nextId, at: now };
                            })
                            .draggable({
                                inertia: false,
                                styleCursor: false,
                                ignoreFrom: ".rotate-handle-cursor",
                                listeners: {
                                    start: (event) => {
                                        if (!this.editMode || this.roomPaintMode || this.roomResize) {
                                            event.interaction?.stop?.();
                                            return;
                                        }

                                        const el = event.target.closest?.(".room-block") || event.target;
                                        const originX = parseFloat(el.dataset.x) || 0;
                                        const originY = parseFloat(el.dataset.y) || 0;
                                        const zoom = this.getLayoutZoom();
                                        const canvasRect = this.$refs.blueprintCanvas?.getBoundingClientRect();
                                        const pointer = this.getCanvasPointer(event, canvasRect, zoom);

                                        if (this.roomDrag?.raf) {
                                            cancelAnimationFrame(this.roomDrag.raf);
                                        }

                                        el.classList.add("is-dragging");
                                        el.style.willChange = "transform";
                                        this.selectedRoom = Number(el.dataset.id);
                                        this.captureLayoutUndo();
                                        this.roomDrag = {
                                            el,
                                            x: originX,
                                            y: originY,
                                            grabX: pointer.x - originX,
                                            grabY: pointer.y - originY,
                                            canvasRect,
                                            zoom,
                                            raf: 0,
                                        };
                                        this.applyRoomDragVisual(el, originX, originY);
                                    },
                                    move: (event) => {
                                        if (!this.roomDrag) {
                                            return;
                                        }

                                        const pointer = this.getCanvasPointer(
                                            event,
                                            this.roomDrag.canvasRect,
                                            this.roomDrag.zoom,
                                        );

                                        this.roomDrag.x = pointer.x - this.roomDrag.grabX;
                                        this.roomDrag.y = pointer.y - this.roomDrag.grabY;
                                        this.queueRoomDragFrame();
                                    },
                                    end: () => {
                                        this.commitRoomDrag();
                                    },
                                },
                            })
                            .resizable({
                                edges: { left: true, right: true, bottom: true, top: true },
                                margin: 16,
                                inertia: false,
                                listeners: {
                                    start: (event) => {
                                        if (!this.editMode || this.roomPaintMode) {
                                            event.interaction?.stop?.();
                                            return;
                                        }

                                        if (this.roomDrag) {
                                            this.commitRoomDrag();
                                        }

                                        const el = event.target.closest?.(".room-block") || event.target;
                                        const zoom = this.getLayoutZoom();
                                        const canvasRect = this.$refs.blueprintCanvas?.getBoundingClientRect();

                                        el.classList.add("is-resizing");
                                        this.selectedRoom = Number(el.dataset.id);
                                        this.captureLayoutUndo();
                                        this.roomResize = {
                                            el,
                                            startX: parseFloat(el.dataset.x) || 0,
                                            startY: parseFloat(el.dataset.y) || 0,
                                            startWidth: parseFloat(el.dataset.width) || el.offsetWidth,
                                            startHeight: parseFloat(el.dataset.height) || el.offsetHeight,
                                            edges: {
                                                left: !!event.edges.left,
                                                right: !!event.edges.right,
                                                top: !!event.edges.top,
                                                bottom: !!event.edges.bottom,
                                            },
                                            pointer: this.getCanvasPointer(event, canvasRect, zoom),
                                            latestPointer: null,
                                            canvasRect,
                                            zoom,
                                            raf: 0,
                                        };
                                    },
                                    move: (event) => {
                                        if (!this.roomResize) {
                                            return;
                                        }

                                        this.roomResize.latestPointer = this.getCanvasPointer(
                                            event,
                                            this.roomResize.canvasRect,
                                            this.roomResize.zoom,
                                        );

                                        if (this.roomResize.raf) {
                                            return;
                                        }

                                        this.roomResize.raf = requestAnimationFrame(() => {
                                            const state = this.roomResize;

                                            if (!state) {
                                                return;
                                            }

                                            state.raf = 0;
                                            this.applyRoomResizeVisual(
                                                state.el,
                                                this.computeRoomResize(
                                                    state,
                                                    state.latestPointer || state.pointer,
                                                ),
                                            );
                                        });
                                    },
                                    end: () => {
                                        this.commitRoomResize();
                                    },
                                },
                            });
                    },
                    bindEquipmentFallback() {
                        if (this.equipmentFallbackBound) return;
                        this.equipmentFallbackBound = true;

                        document.addEventListener("pointerdown", (event) => {

                            if (event.defaultPrevented || this.equipmentAction || this.equipmentPendingDrag) {
                                return;
                            }

                            const node = event.target.closest(".room-equipment-node");

                            if (!node || !this.roomLayout.edit) return;

                            event.preventDefault();

                            node.classList.add("dragging");

                            node.setPointerCapture?.(event.pointerId);

                            const parentRect = node.parentElement.getBoundingClientRect();

                            const currentX = parseFloat(node.dataset.x || 50);

                            const currentY = parseFloat(node.dataset.y || 50);

                            const equipmentPixelX = parentRect.width * currentX / 100;

                            const equipmentPixelY = parentRect.height * currentY / 100;

                            node.style.left = equipmentPixelX + "px";

                            node.style.top = equipmentPixelY + "px";

                            this.equipmentDrag = {

                                node,

                                parentRect,

                                x: equipmentPixelX,

                                y: equipmentPixelY,

                                startX: event.clientX,

                                startY: event.clientY,

                            };

                        });

                        document.addEventListener("pointermove", (event) => {

                            if (!this.equipmentDrag || !this.roomLayout.edit) return;

                            event.preventDefault();

                            const {

                                node,

                                x: startPixelX,

                                y: startPixelY,

                                startX,

                                startY,

                                parentRect

                            } = this.equipmentDrag;

                            let x = startPixelX + event.clientX - startX;

                            let y = startPixelY + event.clientY - startY;

                            x = Math.min(parentRect.width - 12, Math.max(12, x));

                            y = Math.min(parentRect.height - 12, Math.max(12, y));

                            node.style.left = x + "px";

                            node.style.top = y + "px";

                            node.dataset.x = Math.round((x / parentRect.width) * 100);

                            node.dataset.y = Math.round((y / parentRect.height) * 100);

                        });

                        document.addEventListener("pointerup", () => {

                            if (!this.equipmentDrag) return;

                            const node = this.equipmentDrag.node;
                            const x = Math.min(96, Math.max(4, +node.dataset.x));
                            const y = Math.min(96, Math.max(4, +node.dataset.y));

                            node.dataset.x = x;
                            node.dataset.y = y;
                            node.style.left = x + "%";
                            node.style.top = y + "%";
                            this.syncEquipmentZone(node);

                            node.classList.remove("dragging");

                            this.equipmentDrag = null;

                            if (this.roomLayout.edit) {

                                this.layoutDirty = true;

                            }

                        });
                    },
                    async saveLayout(manual = true) {
                        if (this.saving) return;
                        this.saving = true;
                        const floorBeingSaved = this.activeFloor;
                        const nodes = [
                            ...document.querySelectorAll(
                                `.room-block[data-floor="${floorBeingSaved}"]`,
                            ),
                        ];
                        try {
                            const response = await fetch(
                                @js (route("maintenance.infrastructure.layout.save")),
                                {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        Accept: "application/json",
                                        "X-CSRF-TOKEN": document.querySelector(
                                            'meta[name="csrf-token"]',
                                        ).content,
                                    },
                                    body: JSON.stringify({
                                        floor_id: floorBeingSaved,

                                        ...(this.roomLayout.open && this.isComlabRoomLayout()
                                            ? {
                                                comlab_room_id: this.roomLayout.id,
                                                comlab_rows: (this.roomLayout.comlabRowLayouts || []).map((entry) => entry.name),
                                                comlab_row_layouts: this.roomLayout.comlabRowLayouts || [],
                                            }
                                            : {}),

                                        rooms: nodes.map((n) => {
                                            const rawRotation = Number(n.dataset.rotation || 0);
                                            const normalizedRotation = ((rawRotation % 360) + 360) % 360;

                                            return {
                                                id: Number(n.dataset.id),

                                                x: Math.round(Number(n.dataset.x || 0)),

                                                y: Math.round(Number(n.dataset.y || 0)),

                                                width: Math.round(Number(n.dataset.width || 0)),

                                                height: Math.round(Number(n.dataset.height || 0)),

                                                rotation: Math.round(normalizedRotation),

                                                color: n.dataset.color || '#60A5FA',
                                            };
                                        }),

                                        equipment: this.roomLayout.open
                                            ? this.roomLayout.equipment.map((item) => {
                                                const isFloor = this.isComlabRoomLayout() && this.isComlabFloorEquipment(item)
                                                    && !item._holding
                                                    && item.placement_zone !== 'Holding'
                                                    && item.location !== 'Holding';
                                                const holding = !!item._holding
                                                    || item.placement_zone === 'Holding'
                                                    || item.location === 'Holding';
                                                const zone = holding
                                                    ? 'Holding'
                                                    : (isFloor
                                                        ? null
                                                        : (item.placement_zone
                                                            || item.location
                                                            || this.detectEquipmentZone(+item.x, +item.y)));

                                                return {
                                                    id: +item.id,
                                                    x: holding ? 0 : Math.round(Number(item.x) || 0),
                                                    y: holding ? 0 : Math.round(Number(item.y) || 0),
                                                    width: +item.width || 120,
                                                    height: +item.height || 96,
                                                    rotation: Math.round((((Number(item.rotation) || 0) % 360) + 360) % 360),
                                                    zone,
                                                };
                                            })
                                            : [
                                            ...document.querySelectorAll(
                                                ".room-equipment-node",
                                            ),
                                        ].map((node) => {
                                            const rawRotation = Number(node.dataset.rotation || 0);
                                            const normalizedRotation = ((rawRotation % 360) + 360) % 360;

                                            return {
                                                id: +node.dataset.equipmentId,
                                                x: +node.dataset.x,
                                                y: +node.dataset.y,
                                                width: +node.dataset.width || 120,
                                                height: +node.dataset.height || 96,
                                                rotation: Math.round(normalizedRotation),
                                                zone: node.dataset.zone || this.detectEquipmentZone(+node.dataset.x, +node.dataset.y),
                                            };
                                        }),
                                    }),
                                },
                            );
                            if (!response.ok) throw new Error();

                            /* =======================================
                        Refresh from database after saving
                        Place HERE
                        ======================================= */

                            if (this.roomLayout.open) {
                                await this.refreshRoomEquipment(this.roomLayout.id);

                                const room = this.roomCatalog.find(
                                    (item) => item.id === this.roomLayout.id,
                                );

                                if (room) {
                                    room.equipment = this.roomLayout.equipment.map(
                                        (item) => ({
                                            ...item,
                                        }),
                                    );

                                    if (this.isComlabRoomLayout()) {
                                        room.comlab_rows = (this.roomLayout.comlabRowLayouts || []).map((entry) => entry.name);
                                        room.comlab_row_layouts = JSON.parse(JSON.stringify(this.roomLayout.comlabRowLayouts || []));
                                    }
                                }
                            }

                            if (manual) {

                                this.layoutDirty = false;
                                this.clearLayoutUndoStack();
                                // Exit room editing mode
                                this.roomLayout.edit = false;

                                this.saveSuccess = true;

                                setTimeout(() => {

                                    this.saveSuccess = false;

                                }, 2000);

                                // Exit blueprint editing mode
                                this.editMode = false;

                                this.roomPaintMode = false;

                                this.toast = "✔ Layout saved successfully";

                                setTimeout(() => {

                                    this.toast = "";

                                }, 2000);

                            }
                        } catch (e) {
                            this.toast = "Could not save the layout";
                            setTimeout(() => (this.toast = ""), 3000);
                        } finally {
                            this.saving = false;
                            
                        }
                    },

                    // ======================================
                    // BELOW saveLayout()
                    // ======================================

                    async refreshRoomEquipment(roomId) {

                        const response = await fetch(

                            `/maintenance/infrastructure/rooms/${roomId}/equipment`

                        );

                        if (!response.ok) {

                            return;

                        }

                        const latestEquipment = await response.json();

                        const room = this.roomCatalog.find(

                            room => room.id === roomId

                        );

                        if(!room){

                            return;

                        }

                        latestEquipment.forEach(incoming=>{

                            let existing = room.equipment.find(

                                item => item.id === incoming.id

                            );

                            if(existing){

                                Object.assign(

                                    existing,

                                    incoming

                                );

                            }else{

                                room.equipment.push(incoming);

                            }

                        });

                        room.equipment = room.equipment.filter(existing=>{

                            return latestEquipment.some(

                                item => item.id === existing.id

                            );

                        });

                        if(

                            this.roomLayout.open &&

                            this.roomLayout.id === roomId

                        ){

                            this.roomLayout.equipment = room.equipment;
                            this.originalRoomLayout = JSON.parse(
                                JSON.stringify(this.roomLayout.equipment)
                            );

                        }

                    },

                    applyRoomUpdate(roomId, updates = {}) {
                        const normalizedRoomId = Number(roomId);
                        if (!normalizedRoomId) {
                            return;
                        }

                        const room = this.roomCatalog.find((item) => item.id === normalizedRoomId);
                        if (room) {
                            if (typeof updates.name === "string") {
                                room.name = updates.name;
                            }
                            if (typeof updates.type === "string") {
                                room.type = updates.type;
                            }
                            if (typeof updates.color === "string") {
                                room.color = updates.color;
                            }
                            if (typeof updates.status === "string") {
                                room.status = updates.status;
                            }
                        }

                        const node = document.querySelector(`.room-block[data-id="${normalizedRoomId}"]`);
                        if (!node) {
                            return;
                        }

                        if (typeof updates.name === "string") {
                            node.dataset.name = updates.name;
                            const roomNameNode = node.querySelector("[data-room-name]");
                            if (roomNameNode) {
                                roomNameNode.dataset.fullName = updates.name;
                                this.syncRoomLabel(node);
                            }

                            if (this.roomLayout.open && this.roomLayout.id === normalizedRoomId) {
                                this.roomLayout.name = updates.name;
                            }

                            if (this.roomManager.id === normalizedRoomId) {
                                this.roomManager.name = updates.name;
                                this.roomManager.originalName = updates.name;
                            }
                        }

                        if (typeof updates.type === "string") {
                            node.dataset.type = updates.type;

                            if (this.roomManager.id === normalizedRoomId) {
                                this.roomManager.type = updates.type;
                            }
                        }

                        if (typeof updates.color === "string") {
                            node.dataset.color = updates.color;
                            node.style.background = updates.color;
                            node.style.setProperty("--room-depth", updates.color);
                            this.applyRoomLabelContrast(node);
                        }

                        if (typeof updates.status === "string") {
                            node.classList.toggle("critical-room", updates.status === "Critical");
                            const statusDot = node.querySelector(".room-status");
                            if (statusDot) {
                                const statusColor =
                                    updates.status === "Critical"
                                        ? "#EF4444"
                                        : updates.status === "Maintenance Needed"
                                          ? "#F59E0B"
                                          : "#10B981";

                                statusDot.style.background = statusColor;
                            }
                        }
                    },

                    // =====================================
                    // Place BELOW saveLayout()
                    // =====================================

                    zonePosition(location) {
                        const zone = String(location || '').trim();
                        const zones = {
                            Holding: { x: 50, y: 90 },
                            Floor: { x: 78, y: 52 },
                            "Row 1": { x: 38, y: 28 },
                            "Row 2": { x: 38, y: 48 },
                            "Row 3": { x: 38, y: 68 },
                            "Front Wall": { x: 50, y: 12 },
                            "Rear Wall": { x: 50, y: 88 },
                            "Center Ceiling": { x: 50, y: 48 },
                            "Left Row Pods": { x: 18, y: 55 },
                            "Right Row Pods": { x: 82, y: 55 },
                            Storage: { x: 90, y: 90 },
                        };

                        if (zones[zone]) return zones[zone];
                        const rowMatch = zone.match(/^Row\s+(\d+)$/i);
                        if (rowMatch) {
                            const n = Math.max(1, Number(rowMatch[1]) || 1);
                            return { x: 38, y: Math.min(78, 20 + n * 18) };
                        }
                        return { x: 50, y: 50 };
                    },

                    /** Placement options for Add/Assign/Edit equipment — matches room interior layout. */
                    placementZonesForRoom(roomId, currentZone = null) {
                        const room = this.roomCatalog.find((item) => item.id === roomId);
                        const fromLayouts = Array.isArray(room?.comlab_row_layouts)
                            ? room.comlab_row_layouts.map((entry) => entry.name).filter(Boolean)
                            : [];
                        const fromNames = Array.isArray(room?.comlab_rows)
                            ? room.comlab_rows.filter(Boolean)
                            : [];
                        const rows = (fromLayouts.length ? fromLayouts : fromNames);
                        const zones = ['Holding', 'Floor', ...rows];
                        const current = String(currentZone || '').trim();
                        if (current && !zones.includes(current)) {
                            zones.push(current);
                        }
                        return zones;
                    },

                    /** Display label for placement values (value stays "Holding" in data). */
                    placementZoneLabel(zone) {
                        const value = String(zone || '').trim();
                        if (value === 'Holding') return 'Holding Area';
                        return value || '—';
                    },

                    isComlabRoom(name) {
                        return /computer\s*laboratory/i.test(String(name || ''));
                    },

                    /** Row-table interior layout for every loose-equipment room (comlabs + lecture/HM/library/etc.).
                     *  Must not depend on roomLayout.open — that flips false during the close fade and briefly
                     *  reveals the legacy LEFT/RIGHT ROW PODS canvas behind the comlab UI. */
                    isComlabRoomLayout() {
                        return true;
                    },

                    isComputerLabLayout() {
                        if (!this.isComlabRoomLayout()) return false;
                        return this.isComlabRoom(this.roomLayout.name)
                            || this.isComlabRoom(this.roomLayout.type);
                    },

                    roomLayoutHint() {
                        if (!this.isComlabRoomLayout()) {
                            return 'Drag equipment inside the room, then save to apply your changes.';
                        }
                        if (this.roomLayout.edit) {
                            return 'Drag row tables to arrange. Drop any equipment on rows, or place icons on the floor.';
                        }
                        return this.isComputerLabLayout()
                            ? 'Select a row and click Open to browse computer sets and equipment.'
                            : 'Select a row and click Open to browse equipment, or select floor icons.';
                    },

                    isComlabRowZone(zone) {
                        return /^Row\s+\d+$/i.test(String(zone || '').trim());
                    },

                    legacyZoneToComlabRow(zone) {
                        const map = {
                            'Left Row Pods': 'Row 1',
                            'Center Ceiling': 'Row 2',
                            'Right Row Pods': 'Row 3',
                        };
                        return map[zone] || null;
                    },

                    initComlabLayout(room) {
                        const catalogRoom = this.roomCatalog.find((item) => item.id === room.id);
                        const savedNames = catalogRoom?.comlab_rows || room.comlab_rows;
                        const savedLayouts = catalogRoom?.comlab_row_layouts || room.comlab_row_layouts;
                        const isLab = this.isComputerLabLayout();

                        // Only restore rows the user previously saved — never auto-create tables
                        if (Array.isArray(savedLayouts) && savedLayouts.length) {
                            this.roomLayout.comlabRowLayouts = savedLayouts.map((entry) => ({
                                name: entry.name,
                                x: Number(entry.x ?? 50),
                                y: Number(entry.y ?? 50),
                                width: Number(entry.width ?? 280),
                                height: Number(entry.height ?? 56),
                                rotation: Number(entry.rotation ?? 0),
                            }));
                            this.roomLayout.comlabRows = this.roomLayout.comlabRowLayouts.map((entry) => entry.name);
                        } else if (Array.isArray(savedNames) && savedNames.length) {
                            this.roomLayout.comlabRows = [...savedNames];
                            this.roomLayout.comlabRowLayouts = this.buildDefaultComlabRowLayouts(savedNames);
                        } else {
                            this.roomLayout.comlabRows = [];
                            this.roomLayout.comlabRowLayouts = [];
                        }

                        this.roomLayout.comlabNav = 'rows';
                        this.roomLayout.selectedComlabRow = null;
                        this.roomLayout.selectedComlabRowTable = null;
                        this.selectedComlabRowTable = null;
                        this.roomLayout.comlabSetCarouselIndex = 0;

                        this.roomLayout.equipment.forEach((item) => {
                            const zone = item.placement_zone || item.location || '';

                            if (zone === 'Holding' || item._holding) {
                                item._holding = true;
                                item.placement_zone = 'Holding';
                                item.location = 'Holding';
                                return;
                            }

                            if (zone === 'Floor') {
                                item._holding = false;
                                item.placement_zone = 'Floor';
                                item.location = 'Floor';
                                const fx = Number(item.x);
                                const fy = Number(item.y);
                                if (!Number.isFinite(fx) || !Number.isFinite(fy) || (fx === 0 && fy === 0) || (fx === 40 && fy === 40)) {
                                    item.x = 50;
                                    item.y = 55;
                                }
                                const w = Number(item.width || 0);
                                const h = Number(item.height || 0);
                                const isLegacyCard = w >= 120 && w <= 160 && h >= 80 && h <= 110;
                                if (!w || w < 24 || w > 1000 || isLegacyCard) item.width = isLegacyCard ? 64 : (w > 1000 ? 1000 : 64);
                                if (!h || h < 24 || h > 1000 || isLegacyCard) item.height = isLegacyCard ? 64 : (h > 1000 ? 1000 : 64);
                                return;
                            }

                            if (!this.isComlabRowZone(zone)) {
                                const mapped = isLab ? this.legacyZoneToComlabRow(zone) : null;
                                if (mapped) {
                                    item.placement_zone = mapped;
                                    item.location = mapped;
                                } else if (
                                    zone &&
                                    zone !== 'Holding' &&
                                    !isLab &&
                                    this.legacyZoneToComlabRow(zone)
                                ) {
                                    // Decorative zones from the old canvas → free floor placement
                                    item.placement_zone = null;
                                    item.location = null;
                                }
                            }

                            if (this.isComlabRowZone(item.placement_zone || item.location || '')) {
                                item._holding = false;
                                return;
                            }

                            if (this.isFloorEquipmentType(item)) {
                                const hasPos =
                                    item.x != null &&
                                    item.y != null &&
                                    !(Number(item.x) === 40 && Number(item.y) === 40) &&
                                    !(Number(item.x) === 0 && Number(item.y) === 0) &&
                                    item.placement_zone !== 'Holding' &&
                                    item.location !== 'Holding';

                                if (!hasPos || item._holding) {
                                    item._holding = true;
                                    item.placement_zone = 'Holding';
                                    item.location = 'Holding';
                                } else {
                                    item._holding = false;
                                    if (!item.placement_zone && !item.location) {
                                        item.placement_zone = 'Floor';
                                        item.location = 'Floor';
                                    }
                                }

                                // Keep valid sizes (incl. user-resized up to 1000). Only fix missing/invalid or legacy bulky cards.
                                const w = Number(item.width || 0);
                                const h = Number(item.height || 0);
                                const isLegacyCard = w >= 120 && w <= 160 && h >= 80 && h <= 110;
                                if (!w || w < 24 || w > 1000 || isLegacyCard) item.width = isLegacyCard ? 64 : (w > 1000 ? 1000 : 64);
                                if (!h || h < 24 || h > 1000 || isLegacyCard) item.height = isLegacyCard ? 64 : (h > 1000 ? 1000 : 64);
                            }
                        });
                    },

                    buildDefaultComlabRowLayouts(names) {
                        const total = Math.max(names.length, 1);
                        return names.map((name, index) => ({
                            name,
                            x: 50,
                            y: Math.round(20 + (index * (55 / total))),
                            width: 300,
                            height: 56,
                            rotation: 0,
                        }));
                    },

                    isFloorEquipmentType(item) {
                        // Non-lab rooms: every asset can sit on the floor as an icon marker
                        if (!this.isComputerLabLayout()) {
                            return true;
                        }

                        const name = typeof item === 'string' ? item : item?.name;
                        const cat = this.equipmentVisualCategory(name);
                        // Only computer-set parts belong in row tables for comlabs
                        if (['monitor', 'mouse', 'keyboard', 'system_unit', 'power'].includes(cat)) {
                            return false;
                        }
                        return true;
                    },

                    isComlabFloorEquipment(item) {
                        const zone = (typeof item === 'object' && item)
                            ? (item.placement_zone || item.location || '')
                            : '';

                        if (zone === 'Holding') return false;
                        if (zone === 'Floor') return true;

                        // Items already assigned to a row table are never floor markers
                        if (this.isComlabRowZone(zone)) {
                            return false;
                        }

                        return this.isFloorEquipmentType(item);
                    },

                    isComlabRowAssignedSetItem(item) {
                        const zone = item.placement_zone || item.location || '';
                        return this.isComlabRowZone(zone);
                    },

                    comlabRowItemsLabel(rowName) {
                        const items = this.equipmentInComlabRow(rowName);
                        if (this.isComputerLabLayout()) {
                            const summary = this.comlabRowSummary(rowName);
                            const parts = [];
                            parts.push(
                                summary.setCount === 1
                                    ? '1 Computer Set'
                                    : `${summary.setCount} Computer Sets`,
                            );
                            if (summary.otherCount > 0) {
                                parts.push(
                                    summary.otherCount === 1
                                        ? '1 Item'
                                        : `${summary.otherCount} Items`,
                                );
                            }
                            return parts.join(' · ');
                        }
                        const total = items.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0);
                        return total === 1 ? '1 Item' : `${total} Items`;
                    },

                    comlabCanvasEquipment() {
                        return (this.roomLayout.equipment || []).filter((item) => {
                            if (!this.isComlabFloorEquipment(item)) return false;
                            if (item._holding || item.placement_zone === 'Holding' || item.location === 'Holding') {
                                return false;
                            }
                            return true;
                        });
                    },

                    comlabDraggableSetItems() {
                        return (this.roomLayout.equipment || []).filter((item) => {
                            if (this.isComlabFloorEquipment(item)) {
                                return false;
                            }
                            const zone = item.placement_zone || item.location || '';
                            return !this.isComlabRowZone(zone);
                        });
                    },

                    comlabDraggableFloorItems() {
                        return (this.roomLayout.equipment || []).filter((item) => {
                            if (!this.isComlabFloorEquipment(item)) return false;
                            return !!item._holding || item.placement_zone === 'Holding' || item.location === 'Holding';
                        });
                    },

                    comlabHoldingItems() {
                        return [...this.comlabDraggableSetItems(), ...this.comlabDraggableFloorItems()];
                    },

                    comlabHoldingPerPage() {
                        return 6;
                    },

                    comlabHoldingPages() {
                        const items = this.comlabHoldingItems();
                        const per = this.comlabHoldingPerPage();
                        const pages = [];
                        for (let i = 0; i < items.length; i += per) {
                            pages.push(items.slice(i, i + per));
                        }
                        return pages.length ? pages : [[]];
                    },

                    comlabHoldingPageCount() {
                        return Math.max(1, this.comlabHoldingPages().length);
                    },

                    comlabHoldingPrev() {
                        const max = this.comlabHoldingPageCount();
                        if (max <= 1) return;
                        this.roomLayout.comlabHoldingPage =
                            (this.roomLayout.comlabHoldingPage - 1 + max) % max;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    comlabHoldingNext() {
                        const max = this.comlabHoldingPageCount();
                        if (max <= 1) return;
                        this.roomLayout.comlabHoldingPage =
                            (this.roomLayout.comlabHoldingPage + 1) % max;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    selectComlabRowTable(name) {
                        this.selectedComlabRowTable = name;
                        this.selectedEquipmentId = null;
                        this.$nextTick(() => {
                            this.updateComlabRowRotateHandlePlacement();
                            if (window.lucide) lucide.createIcons();
                        });
                    },

                    selectComlabFloorEquipment(id) {
                        this.selectedComlabRowTable = null;
                        this.roomLayout.selectedComlabRow = null;
                        this.roomLayout.selectedAssetId = id;
                        this.loadAssetLifecycle(id);
                        // Stay on room layout — never open the row page for floor items
                        this.roomLayout.comlabNav = 'rows';
                        if (this.roomLayout.edit) {
                            this.selectEquipment(id);
                        } else {
                            this.selectedEquipmentId = null;
                        }
                        this.$nextTick(() => {
                            this.updateEquipmentRotateHandlePlacement();
                            if (window.lucide) lucide.createIcons();
                        });
                    },

                    deselectComlabFloorEquipment() {
                        this.roomLayout.selectedAssetId = null;
                        if (this.roomLayout.edit) {
                            this.selectEquipment(null);
                        } else {
                            this.selectedEquipmentId = null;
                        }
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    clearComlabCanvasSelection() {
                        this.selectedComlabRowTable = null;
                        this.deselectComlabFloorEquipment();
                    },

                    onComlabFloorPointerDown(event, id) {
                        this._comlabFloorPointerSelected = false;
                        this._comlabFloorDidDrag = false;

                        if (this.roomLayout.edit) {
                            if (this.selectedEquipmentId !== id) {
                                this.selectComlabFloorEquipment(id);
                                this._comlabFloorPointerSelected = true;
                            }
                            this.handleEquipmentPointerDown(event, id);
                            return;
                        }

                        // View mode: select on pointer down; click may toggle off
                        if (this.roomLayout.selectedAssetId !== id) {
                            this.selectComlabFloorEquipment(id);
                            this._comlabFloorPointerSelected = true;
                        }
                    },

                    onComlabFloorClick(id) {
                        if (this._comlabFloorPointerSelected) {
                            this._comlabFloorPointerSelected = false;
                            return;
                        }
                        if (this._comlabFloorDidDrag) {
                            this._comlabFloorDidDrag = false;
                            return;
                        }

                        const isSelected = this.roomLayout.edit
                            ? this.selectedEquipmentId === id
                            : this.roomLayout.selectedAssetId === id;

                        if (isSelected) {
                            this.deselectComlabFloorEquipment();
                        } else {
                            this.selectComlabFloorEquipment(id);
                        }
                    },

                    shortEquipmentName(name) {
                        const raw = String(name || '').trim();
                        if (!raw) return 'Equipment';

                        const cat = this.equipmentVisualCategory(name);
                        const labels = {
                            chair: 'Chair',
                            table: 'Table',
                            whiteboard: 'Whiteboard',
                            curtain: 'Curtain',
                            fan: 'Fan',
                            aircon: 'AirConditioner',
                            tv: 'TV',
                            projector: 'Projector',
                            monitor: 'Monitor',
                            mouse: 'Mouse',
                            keyboard: 'Keyboard',
                            system_unit: 'System Unit',
                            power: 'UPS/AVR',
                            network: 'Ethernet',
                            bulb: 'Bulb',
                            printer: 'Printer',
                        };
                        if (labels[cat]) return labels[cat];

                        const cleaned = raw
                            .replace(/\b(floor|wall|ceiling|mounted|mount|inverter|standing|split|type|window|long|short|the|a|an|lcd|led|flat|screen|office|classroom|laboratory|electric|compact|fluorescent|light)\b/gi, ' ')
                            .replace(/[^a-zA-Z0-9\s]/g, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();

                        if (!cleaned) {
                            return raw.split(/\s+/).pop() || 'Equipment';
                        }

                        const parts = cleaned.split(/\s+/).slice(-2);
                        return parts.map((part) =>
                            part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()
                        ).join(' ');
                    },

                    comlabFloorLabelSide(item) {
                        const x = Number(item?.x ?? 50);
                        const y = Number(item?.y ?? 50);
                        const isSelected =
                            this.roomLayout?.edit
                            && this.selectedEquipmentId === item?.id;
                        const rotateSide = isSelected
                            ? (this.equipmentRotateHandleSide || 'bottom')
                            : null;

                        // Keep the name label opposite the rotate handle so they don't overlap.
                        if (rotateSide === 'bottom') {
                            if (y > 14) return 'top';
                            if (x <= 50) return 'right';
                            return 'left';
                        }
                        if (rotateSide === 'top') {
                            if (y < 86) return 'bottom';
                            if (x <= 50) return 'right';
                            return 'left';
                        }
                        if (rotateSide === 'left') {
                            if (x < 90) return 'right';
                            return y < 50 ? 'bottom' : 'top';
                        }
                        if (rotateSide === 'right') {
                            if (x > 10) return 'left';
                            return y < 50 ? 'bottom' : 'top';
                        }

                        if (y >= 88) return 'top';
                        if (y <= 12) return 'bottom';
                        if (x <= 10) return 'right';
                        if (x >= 90) return 'left';
                        return 'bottom';
                    },

                    comlabFloorLabelStyle(item) {
                        const rotation = this.liveEquipmentRotation
                            ? this.liveEquipmentRotation(item)
                            : Number(item?.rotation || 0);
                        const side = this.comlabFloorLabelSide(item);
                        const gap = 6;

                        if (side === 'top') {
                            return `left:50%; bottom:calc(100% + ${gap}px); transform:translateX(-50%) rotate(${-rotation}deg); transform-origin:center bottom;`;
                        }
                        if (side === 'left') {
                            return `right:calc(100% + ${gap}px); top:50%; transform:translateY(-50%) rotate(${-rotation}deg); transform-origin:right center;`;
                        }
                        if (side === 'right') {
                            return `left:calc(100% + ${gap}px); top:50%; transform:translateY(-50%) rotate(${-rotation}deg); transform-origin:left center;`;
                        }
                        return `left:50%; top:calc(100% + ${gap}px); transform:translateX(-50%) rotate(${-rotation}deg); transform-origin:center top;`;
                    },

                    openComlabRowDetail(row) {
                        this.selectComlabRow(row);
                    },

                    handleComlabRowPointerDown(event, rowName) {
                        if (!this.roomLayout.edit) {
                            this.selectComlabRowTable(rowName);
                            return;
                        }
                        if (event.button !== 0) return;
                        event.preventDefault();

                        const node = event.currentTarget;
                        const target = event.target instanceof Element ? event.target : event.target.parentElement;
                        const resizeHandle = target?.closest('.resize-grip');
                        const rotateHandle = target?.closest('.rotate-equipment-handle-cursor');

                        this.selectComlabRowTable(rowName);
                        this.selectedEquipmentId = null;

                        if (rotateHandle) {
                            this.beginComlabRowRotation(event, rowName);
                            return;
                        }

                        if (resizeHandle) {
                            this.beginEquipmentResize(event, node, resizeHandle);
                            return;
                        }

                        this.equipmentPendingDrag = {
                            node,
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                        };
                    },

                    beginComlabRowRotation(event, rowName) {
                        if (!this.roomLayout.edit) return;
                        this.selectComlabRowTable(rowName);
                        const item = document.querySelector(`.comlab-row-node[data-row-name="${rowName}"]`);
                        if (!item) return;

                        this.equipmentPendingDrag = null;
                        if (this.equipmentAction) {
                            this.endEquipmentAction(event);
                        }

                        const center = this.getEquipmentCenterClient(item);
                        const startRotation = Number(item.dataset.rotation || 0);
                        const startMouseAngle = Math.atan2(event.clientY - center.y, event.clientX - center.x) * (180 / Math.PI);

                        this.comlabRowRotationDrag = {
                            node: item,
                            rowName,
                            pointerId: event.pointerId,
                            startRotation,
                            startMouseAngle,
                            handleElement: event.currentTarget,
                        };
                        this.comlabRowIsRotating = true;
                        this.comlabRowLiveRotation = startRotation;
                        this.comlabRowRotationDisplayAngle = this.formatRotationDisplay(startRotation);
                        document.body.classList.add('equipment-rotate-active-cursor');
                        event.currentTarget.setPointerCapture?.(event.pointerId);
                    },

                    trackComlabRowRotation(event) {
                        if (!this.comlabRowRotationDrag || event.pointerId !== this.comlabRowRotationDrag.pointerId) {
                            return;
                        }
                        const { node, startRotation, startMouseAngle } = this.comlabRowRotationDrag;
                        const center = this.getEquipmentCenterClient(node);
                        const mouseAngle = Math.atan2(event.clientY - center.y, event.clientX - center.x) * (180 / Math.PI);
                        let delta = mouseAngle - startMouseAngle;
                        if (delta > 180) delta -= 360;
                        else if (delta < -180) delta += 360;
                        const value = startRotation + delta;
                        node.dataset.rotation = value;
                        node.style.transform = `translate(-50%,-50%) rotate(${value}deg)`;
                        this.comlabRowLiveRotation = value;
                        this.comlabRowRotationDisplayAngle = this.formatRotationDisplay(value);

                        const row = (this.roomLayout.comlabRowLayouts || []).find(
                            (entry) => entry.name === this.comlabRowRotationDrag.rowName,
                        );
                        if (row) {
                            // Keep handle outside while rotating by refreshing side occasionally
                            this.updateComlabRowRotateHandlePlacement(node, true);
                        }
                    },

                    endComlabRowRotation(event) {
                        if (!this.comlabRowRotationDrag || event.pointerId !== this.comlabRowRotationDrag.pointerId) {
                            return;
                        }
                        const { node, rowName } = this.comlabRowRotationDrag;
                        const finalRotation = this.normalizeRotation(Number(node.dataset.rotation || 0));
                        node.dataset.rotation = finalRotation;
                        node.style.transform = `translate(-50%,-50%) rotate(${finalRotation}deg)`;
                        const row = (this.roomLayout.comlabRowLayouts || []).find((entry) => entry.name === rowName);
                        if (row) {
                            row.rotation = finalRotation;
                        }
                        this.comlabRowRotationDrag.handleElement?.releasePointerCapture?.(event.pointerId);
                        this.comlabRowRotationDrag = null;
                        this.comlabRowIsRotating = false;
                        this.comlabRowLiveRotation = null;
                        this.comlabRowRotationDisplayAngle = this.formatRotationDisplay(finalRotation);
                        document.body.classList.remove('equipment-rotate-active-cursor');
                        this.layoutDirty = true;
                        this.$nextTick(() => this.updateComlabRowRotateHandlePlacement(node));
                    },

                    liveComlabRowRotation(row) {
                        if (
                            this.comlabRowIsRotating &&
                            this.selectedComlabRowTable === row?.name &&
                            this.comlabRowLiveRotation != null
                        ) {
                            return this.comlabRowLiveRotation;
                        }
                        return Number(row?.rotation || 0);
                    },

                    comlabRowRotateGimbalStyle(row) {
                        const rotation = this.liveComlabRowRotation(row);
                        return `transform:translate(-50%,-50%) rotate(${-rotation}deg)`;
                    },

                    comlabRowRotateHandleStyle(row) {
                        return this.buildComlabRowRotateHandleStyle(
                            row,
                            this.comlabRowRotateHandleSide || 'bottom',
                        );
                    },

                    buildComlabRowRotateHandleStyle(row, side = 'bottom') {
                        const rotation = this.liveComlabRowRotation(row);
                        const width = Number(row?.width || 280);
                        const height = Number(row?.height || 56);
                        const rad = (rotation * Math.PI) / 180;
                        const aabbWidth =
                            Math.abs(width * Math.cos(rad)) +
                            Math.abs(height * Math.sin(rad));
                        const aabbHeight =
                            Math.abs(width * Math.sin(rad)) +
                            Math.abs(height * Math.cos(rad));
                        const handleSize = this.comlabRowIsRotating ? 44 : 36;
                        const gap = 18;
                        const offsetX = aabbWidth / 2 + gap;
                        const offsetY = aabbHeight / 2 + gap;

                        if (side === 'top') {
                            return `left:0;top:${-(offsetY + handleSize)}px;transform:translateX(-50%)`;
                        }
                        if (side === 'right') {
                            return `left:${offsetX}px;top:0;transform:translateY(-50%)`;
                        }
                        if (side === 'left') {
                            return `left:${-(offsetX + handleSize)}px;top:0;transform:translateY(-50%)`;
                        }
                        return `left:0;top:${offsetY}px;transform:translateX(-50%)`;
                    },

                    updateComlabRowRotateHandlePlacement(node = null, whileRotating = false) {
                        if (!this.roomLayout?.edit || !this.selectedComlabRowTable) {
                            this.comlabRowRotateHandleSide = 'bottom';
                            return;
                        }

                        const itemNode =
                            node ||
                            document.querySelector(
                                `.comlab-row-node[data-row-name="${this.selectedComlabRowTable}"]`,
                            );
                        const canvas = this.$refs.comlabInteriorCanvas;

                        if (!itemNode || !canvas) {
                            this.comlabRowRotateHandleSide = 'bottom';
                            return;
                        }

                        const padding = 8;
                        const handleReserve = whileRotating ? 72 : 58;
                        const nodeRect = itemNode.getBoundingClientRect();
                        const canvasRect = canvas.getBoundingClientRect();
                        const clip = {
                            top: canvasRect.top + padding,
                            right: canvasRect.right - padding,
                            bottom: canvasRect.bottom - padding,
                            left: canvasRect.left + padding,
                        };
                        const space = {
                            bottom: clip.bottom - nodeRect.bottom,
                            top: nodeRect.top - clip.top,
                            right: clip.right - nodeRect.right,
                            left: nodeRect.left - clip.left,
                        };
                        const order = ['bottom', 'top', 'right', 'left'];
                        const fitting = order.find((side) => space[side] >= handleReserve);
                        const side =
                            fitting ||
                            order.reduce((best, current) =>
                                space[current] > space[best] ? current : best,
                            );

                        const row = (this.roomLayout.comlabRowLayouts || []).find(
                            (entry) => entry.name === this.selectedComlabRowTable,
                        ) || {
                            width: itemNode.dataset.width,
                            height: itemNode.dataset.height,
                            rotation: itemNode.dataset.rotation,
                            name: this.selectedComlabRowTable,
                        };

                        // Prefer live dataset size/rotation while dragging/rotating
                        const styleSource = {
                            name: row.name,
                            width: Number(itemNode.dataset.width || row.width || 280),
                            height: Number(itemNode.dataset.height || row.height || 56),
                            rotation: Number(itemNode.dataset.rotation || row.rotation || 0),
                        };

                        this.comlabRowRotateHandleSide = side;

                        const handle = itemNode.querySelector('.rotate-equipment-handle-cursor');
                        if (handle) {
                            handle.setAttribute('style', this.buildComlabRowRotateHandleStyle(styleSource, side));
                        }
                    },

                    comlabDropOnCanvas(event) {
                        if (!this.roomLayout.edit) return;
                        const id = Number(event.dataTransfer.getData('equipmentId'));
                        if (!id) return;
                        const item = this.roomLayout.equipment.find((entry) => entry.id === id);
                        if (!item) return;
                        // Labs: only floor gear. Other rooms: any equipment can sit on the floor as an icon.
                        if (this.isComputerLabLayout() && !this.isFloorEquipmentType(item)) return;

                        const canvas = this.$refs.comlabInteriorCanvas;
                        if (!canvas) return;
                        const rect = canvas.getBoundingClientRect();
                        const x = Math.round(((event.clientX - rect.left) / rect.width) * 100);
                        const y = Math.round(((event.clientY - rect.top) / rect.height) * 100);

                        item.x = Math.min(96, Math.max(4, x));
                        item.y = Math.min(96, Math.max(4, y));
                        item.placement_zone = 'Floor';
                        item.location = 'Floor';
                        item._holding = false;
                        if (!item.width || Number(item.width) < 24 || Number(item.width) > 1000) item.width = 64;
                        if (!item.height || Number(item.height) < 24 || Number(item.height) > 1000) item.height = 64;
                        this.roomLayout.comlabHoldingPage = Math.min(
                            this.roomLayout.comlabHoldingPage || 0,
                            Math.max(0, this.comlabHoldingPageCount() - 1),
                        );
                        this.layoutDirty = true;
                    },

                    returnEquipmentToHolding(item) {
                        if (!item) return;
                        item.placement_zone = 'Holding';
                        item.location = 'Holding';
                        item._holding = true;
                        item.x = null;
                        item.y = null;
                    },

                    sendEquipmentToHolding(equipmentId) {
                        if (!this.roomLayout.edit) return;
                        const item = (this.roomLayout.equipment || []).find((entry) => entry.id === Number(equipmentId));
                        if (!item) return;

                        const wasInRow = this.isComlabRowZone(item.placement_zone || item.location || '');
                        this.returnEquipmentToHolding(item);
                        if (this.selectedEquipmentId === item.id) {
                            this.selectedEquipmentId = null;
                        }
                        if (this.roomLayout.selectedAssetId === item.id) {
                            this.roomLayout.selectedAssetId = null;
                            if (wasInRow && this.roomLayout.comlabNav === 'asset') {
                                this.roomLayout.comlabNav = this.comlabSetsInSelectedRow().length ? 'set' : 'row';
                            }
                        }
                        if (!wasInRow) {
                            this.selectedComlabRowTable = null;
                        }
                        this.roomLayout.comlabHoldingPage = 0;
                        this.layoutDirty = true;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    comlabDropOnHolding(event) {
                        if (!this.roomLayout.edit) return;
                        const id = Number(event.dataTransfer.getData('equipmentId'));
                        if (!id) return;
                        this.sendEquipmentToHolding(id);
                    },

                    deleteComlabRow(rowName) {
                        if (!this.roomLayout.edit || !rowName) return;

                        (this.roomLayout.equipment || []).forEach((item) => {
                            const zone = item.placement_zone || item.location || '';
                            if (zone === rowName) {
                                this.returnEquipmentToHolding(item);
                            }
                        });

                        this.roomLayout.comlabRowLayouts = (this.roomLayout.comlabRowLayouts || [])
                            .filter((entry) => entry.name !== rowName);
                        this.roomLayout.comlabRows = this.roomLayout.comlabRowLayouts.map((entry) => entry.name);

                        if (this.selectedComlabRowTable === rowName) {
                            this.selectedComlabRowTable = null;
                        }
                        if (this.roomLayout.selectedComlabRow === rowName) {
                            this.roomLayout.selectedComlabRow = null;
                            this.roomLayout.comlabNav = 'rows';
                            this.roomLayout.selectedAssetId = null;
                        }

                        this.roomLayout.comlabHoldingPage = 0;
                        this.layoutDirty = true;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },
                    isComputerSetComponent(name) {
                        return this.computerSetComponentRole(name) !== null;
                    },

                    computerSetComponentRole(name) {
                        const n = String(name || '').toLowerCase();
                        if (/chair|table|curtain|projector|floor mat|long table|cabinet|whiteboard|door|air\s*con|printer|\btv\b|speaker|fan|bulb|ethernet|cable/.test(n)) {
                            return null;
                        }
                        if (/monitor|display/.test(n) && !/tv|television/.test(n)) return 'monitor';
                        if (/keyboard/.test(n)) return 'keyboard';
                        if (/mouse/.test(n)) return 'mouse';
                        if (/system\s*unit|\bcpu\b|desktop|tower|\bpc\b/.test(n)) return 'system_unit';
                        if (/\bavr\b|\bups\b/.test(n)) return 'power';
                        if (/webcam|headset/.test(n)) return 'peripheral';
                        return null;
                    },

                    equipmentInComlabRow(rowName) {
                        return (this.roomLayout.equipment || []).filter((item) =>
                            this.isComlabRowAssignedSetItem(item)
                            && (item.placement_zone || item.location) === rowName,
                        );
                    },

                    comlabSetNumberOffset(rowName) {
                        let offset = 0;
                        for (const row of (this.roomLayout.comlabRows || [])) {
                            if (row === rowName) {
                                break;
                            }
                            offset += this.buildComlabComputerSets(this.equipmentInComlabRow(row)).length;
                        }
                        return offset;
                    },

                    buildComlabComputerSets(rowItems, startSetNum = 1) {
                        const components = rowItems.filter((item) => {
                            if (!this.isComputerSetComponent(item.name)) {
                                return false;
                            }
                            const qty = Number(item.quantity) || 1;
                            return !(qty > 1 && String(item.tracking_mode || '') === 'Bulk');
                        });

                        const roleOrder = [
                            'monitor',
                            'keyboard',
                            'mouse',
                            'system_unit',
                            'power',
                            'peripheral',
                        ];

                        const pools = {};
                        roleOrder.forEach((role) => { pools[role] = []; });

                        components
                            .slice()
                            .sort((a, b) =>
                                String(a.asset_tag || a.name).localeCompare(String(b.asset_tag || b.name)),
                            )
                            .forEach((item) => {
                                const role = this.computerSetComponentRole(item.name);
                                if (role && pools[role]) {
                                    pools[role].push(item);
                                }
                            });

                        const setCount = Math.max(
                            0,
                            ...roleOrder.map((role) => pools[role].length),
                        );

                        const sets = [];
                        for (let i = 0; i < setCount; i += 1) {
                            const members = [];
                            const usedRoles = new Set();

                            roleOrder.forEach((role) => {
                                if (usedRoles.has(role)) return;
                                const item = pools[role][i];
                                if (!item) return;
                                members.push(item);
                                usedRoles.add(role);
                            });

                            if (!members.length) continue;

                            sets.push({
                                index: sets.length,
                                label: `Computer Set ${startSetNum + sets.length}`,
                                members,
                            });
                        }

                        return sets;
                    },

                    comlabRowSummary(rowName) {
                        const items = this.equipmentInComlabRow(rowName);
                        const startNum = this.comlabSetNumberOffset(rowName) + 1;
                        const sets = this.buildComlabComputerSets(items, startNum);
                        const setMemberIds = new Set(
                            sets.flatMap((set) => (set.members || []).map((member) => member.id)),
                        );
                        const other = items.filter((item) => !setMemberIds.has(item.id));

                        return {
                            sets,
                            other,
                            setCount: sets.length,
                            otherCount: other.length,
                            total: items.length,
                        };
                    },

                    comlabSetsInSelectedRow() {
                        if (!this.roomLayout.selectedComlabRow) {
                            return [];
                        }
                        const startNum = this.comlabSetNumberOffset(this.roomLayout.selectedComlabRow) + 1;
                        return this.buildComlabComputerSets(
                            this.equipmentInComlabRow(this.roomLayout.selectedComlabRow),
                            startNum,
                        );
                    },

                    comlabOtherInSelectedRow() {
                        if (!this.roomLayout.selectedComlabRow) {
                            return [];
                        }
                        return this.comlabRowSummary(this.roomLayout.selectedComlabRow).other;
                    },

                    activeComlabSet() {
                        const sets = this.comlabSetsInSelectedRow();
                        return sets[this.roomLayout.comlabSetCarouselIndex] || null;
                    },

                    comlabUnassignedEquipment() {
                        const rows = new Set(this.roomLayout.comlabRows || []);
                        return (this.roomLayout.equipment || []).filter((item) => {
                            const zone = item.placement_zone || item.location || '';
                            return !rows.has(zone);
                        });
                    },

                    selectComlabRow(row) {
                        this.roomLayout.selectedComlabRow = row;
                        this.roomLayout.comlabNav = 'row';
                        this.roomLayout.comlabSetCarouselIndex = 0;
                        this.roomLayout.selectedAssetId = null;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    selectComlabSet(index) {
                        this.roomLayout.comlabSetCarouselIndex = index;
                        this.roomLayout.comlabNav = 'set';
                        this.roomLayout.selectedAssetId = null;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    comlabSetCarouselPerPage() {
                        return 3;
                    },

                    comlabSetCarouselPage() {
                        const per = this.comlabSetCarouselPerPage();
                        return Math.floor((this.roomLayout.comlabSetCarouselIndex || 0) / per);
                    },

                    comlabSetCarouselPageCount() {
                        const total = this.comlabSetsInSelectedRow().length;
                        return Math.max(1, Math.ceil(total / this.comlabSetCarouselPerPage()));
                    },

                    comlabSetCarouselPageLabel() {
                        const total = this.comlabSetsInSelectedRow().length;
                        if (total <= this.comlabSetCarouselPerPage()) {
                            return `${total} set${total === 1 ? '' : 's'}`;
                        }
                        return `${this.comlabSetCarouselPage() + 1} / ${this.comlabSetCarouselPageCount()}`;
                    },

                    comlabCarouselGoToPage(page) {
                        const per = this.comlabSetCarouselPerPage();
                        const maxPage = this.comlabSetCarouselPageCount() - 1;
                        const nextPage = Math.min(Math.max(0, page), maxPage);
                        this.selectComlabSet(nextPage * per);
                    },

                    comlabCarouselPrev() {
                        const page = this.comlabSetCarouselPage();
                        if (page <= 0) return;
                        this.comlabCarouselGoToPage(page - 1);
                    },

                    comlabCarouselNext() {
                        const max = this.comlabSetCarouselPageCount();
                        const page = this.comlabSetCarouselPage();
                        if (page >= max - 1) return;
                        this.comlabCarouselGoToPage(page + 1);
                    },

                    selectComlabAsset(id) {
                        const item = (this.roomLayout.equipment || []).find((e) => e.id === id);
                        if (item && this.isEquipmentInHolding(item)) {
                            this.selectComlabHoldingItem(id);
                            return;
                        }
                        if (item && this.isComlabFloorEquipment(item)) {
                            this.selectComlabFloorEquipment(id);
                            return;
                        }
                        if (item) {
                            const zone = item.placement_zone || item.location || '';
                            if (this.isComlabRowZone(zone)) {
                                this.roomLayout.selectedComlabRow = zone;
                                this.roomLayout.comlabNav = 'asset';
                            } else {
                                this.roomLayout.comlabNav = 'rows';
                            }
                        } else {
                            this.roomLayout.comlabNav = 'asset';
                        }
                        this.roomLayout.selectedAssetId = id;
                        this.loadAssetLifecycle(id);
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    isEquipmentInHolding(item) {
                        if (!item) return false;
                        return !!item._holding
                            || item.placement_zone === 'Holding'
                            || item.location === 'Holding';
                    },

                    selectComlabHoldingItem(id) {
                        this.selectedComlabRowTable = null;
                        this.roomLayout.selectedComlabRow = null;
                        this.selectedEquipmentId = null;
                        this.roomLayout.selectedAssetId = id;
                        this.loadAssetLifecycle(id);
                        this.roomLayout.comlabNav = 'rows';
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    comlabAssetPlacementLabel(item) {
                        if (!item) return '—';
                        if (this.isEquipmentInHolding(item)) return 'Holding Area';
                        const zone = item.placement_zone || item.location || '';
                        if (this.isComlabRowZone(zone)) return zone;
                        if (this.isComlabFloorEquipment(item)) return 'Floor';
                        return zone || '—';
                    },

                    comlabAssetPlacementHint(item) {
                        if (!item) return '—';
                        if (this.isEquipmentInHolding(item)) return 'Holding area';
                        if (this.isComlabFloorEquipment(item)) return 'Floor placement';
                        return this.roomLayout.selectedComlabRow
                            || item.placement_zone
                            || item.location
                            || '—';
                    },

                    comlabDragStart(event, equipmentId) {
                        if (!this.roomLayout.edit) return;
                        event.dataTransfer.setData('equipmentId', String(equipmentId));
                        event.dataTransfer.effectAllowed = 'move';
                    },

                    comlabNavTo(level) {
                        this.roomLayout.comlabNav = level;
                        if (level === 'rows') {
                            this.roomLayout.selectedComlabRow = null;
                            this.roomLayout.selectedAssetId = null;
                        }
                        if (level === 'row') {
                            this.roomLayout.selectedAssetId = null;
                        }
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    comlabBack() {
                        if (this.roomLayout.selectedAssetId && this.roomLayout.comlabNav === 'rows') {
                            this.roomLayout.selectedAssetId = null;
                            this.selectedEquipmentId = null;
                            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                            return;
                        }
                        if (this.roomLayout.comlabNav === 'asset') {
                            this.roomLayout.selectedAssetId = null;
                            this.roomLayout.comlabNav = this.comlabSetsInSelectedRow().length ? 'set' : 'row';
                        } else if (this.roomLayout.comlabNav === 'set') {
                            this.roomLayout.comlabNav = 'row';
                        } else if (this.roomLayout.comlabNav === 'row') {
                            this.comlabNavTo('rows');
                        } else {
                            this.comlabNavTo('rows');
                        }
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    addComlabRow() {
                        const existing = this.roomLayout.comlabRowLayouts || [];
                        const used = new Set(existing.map((entry) => entry.name));
                        let n = existing.length + 1;
                        while (used.has(`Row ${n}`)) n += 1;
                        const name = `Row ${n}`;
                        this.roomLayout.comlabRowLayouts = [
                            ...existing,
                            {
                                name,
                                x: 50,
                                y: Math.min(88, 18 + n * 14),
                                width: 300,
                                height: 56,
                                rotation: 0,
                            },
                        ];
                        this.roomLayout.comlabRows = this.roomLayout.comlabRowLayouts.map((entry) => entry.name);
                        this.layoutDirty = true;
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    assignEquipmentToComlabRow(equipmentId, rowName) {
                        const item = this.roomLayout.equipment.find((e) => e.id === equipmentId);
                        if (!item || !rowName) return;
                        item.placement_zone = rowName;
                        item.location = rowName;
                        item._holding = false;
                        this.roomLayout.comlabHoldingPage = Math.min(
                            this.roomLayout.comlabHoldingPage || 0,
                            Math.max(0, this.comlabHoldingPageCount() - 1),
                        );
                        this.layoutDirty = true;
                    },

                    findComlabRowAtClientPoint(clientX, clientY) {
                        const rows = document.querySelectorAll('.comlab-row-node[data-row-name]');
                        for (const rowNode of rows) {
                            const rect = rowNode.getBoundingClientRect();
                            if (
                                clientX >= rect.left
                                && clientX <= rect.right
                                && clientY >= rect.top
                                && clientY <= rect.bottom
                            ) {
                                return rowNode.dataset.rowName || null;
                            }
                        }
                        return null;
                    },

                    highlightComlabRowDropTarget(rowName) {
                        document.querySelectorAll('.comlab-row-node').forEach((node) => {
                            const active = rowName && node.dataset.rowName === rowName;
                            node.classList.toggle('ring-2', !!active);
                            node.classList.toggle('ring-emerald-400', !!active);
                        });
                    },

                    comlabDropOnRow(event, rowName) {
                        if (!this.roomLayout.edit) return;
                        const id = Number(event.dataTransfer.getData('equipmentId'));
                        if (!id) return;
                        this.assignEquipmentToComlabRow(id, rowName);
                        if (this.selectedEquipmentId === id) {
                            this.selectedEquipmentId = null;
                        }
                        if (this.roomLayout.selectedAssetId === id) {
                            this.roomLayout.selectedAssetId = null;
                        }
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    layoutGroups() {
                        const map = new Map();

                        (this.roomLayout.equipment || []).forEach((item) => {
                            const zone = item.placement_zone || item.location || 'Unassigned';
                            const qty = Number(item.quantity) || 1;
                            const isBulk = String(item.tracking_mode || '') === 'Bulk' || qty > 1;
                            const key = isBulk
                                ? `id:${item.id}`
                                : `n:${String(item.name || '').toLowerCase()}|z:${zone}`;

                            if (!map.has(key)) {
                                map.set(key, {
                                    key,
                                    name: item.name,
                                    zone,
                                    ids: [],
                                    members: [],
                                    x: 0,
                                    y: 0,
                                    width: item.width || 140,
                                    height: Math.max(80, item.height || 96),
                                    rotation: item.rotation || 0,
                                    quantity: 0,
                                    isBulk,
                                    primaryId: item.id,
                                });
                            }

                            const group = map.get(key);
                            group.ids.push(item.id);
                            group.members.push(item);
                            group.quantity += isBulk ? qty : 1;
                            group.width = Math.max(group.width, item.width || 140);
                            group.height = Math.max(group.height, Math.max(80, item.height || 96));
                        });

                        return [...map.values()].map((group) => {
                            const count = group.members.length || 1;
                            group.x = Math.round(
                                group.members.reduce((sum, member) => sum + Number(member.x || 50), 0) / count,
                            );
                            group.y = Math.round(
                                group.members.reduce((sum, member) => sum + Number(member.y || 50), 0) / count,
                            );
                            group.rotation = group.members[0]?.rotation || 0;
                            group.primaryId = group.ids[0];
                            group.label = group.quantity > 1
                                ? `${group.name} × ${group.quantity}`
                                : group.name;
                            return group;
                        });
                    },

                    filteredLayoutGroups() {
                        const q = String(this.roomLayout.listSearch || '').trim().toLowerCase();
                        const groups = this.layoutGroups();
                        if (!q) return groups;
                        return groups.filter((group) =>
                            String(group.name || '').toLowerCase().includes(q)
                            || String(group.zone || '').toLowerCase().includes(q)
                            || String(group.label || '').toLowerCase().includes(q)
                            || group.members.some((member) =>
                                String(member.asset_tag || '').toLowerCase().includes(q)
                                || String(member.serial_number || '').toLowerCase().includes(q)
                            )
                        );
                    },

                    layoutGroupPageCount() {
                        return Math.max(
                            1,
                            Math.ceil(this.filteredLayoutGroups().length / (this.roomLayout.listPerPage || 12)),
                        );
                    },

                    pagedLayoutGroups() {
                        const perPage = this.roomLayout.listPerPage || 12;
                        const page = Math.min(this.roomLayout.listPage || 1, this.layoutGroupPageCount());
                        const start = (page - 1) * perPage;
                        return this.filteredLayoutGroups().slice(start, start + perPage);
                    },

                    selectedLayoutGroup() {
                        const key = this.roomLayout.selectedGroupKey;
                        if (!key) return null;
                        return this.layoutGroups().find((group) => group.key === key) || null;
                    },

                    filteredGroupAssets() {
                        const group = this.selectedLayoutGroup();
                        if (!group) return [];
                        const q = String(this.roomLayout.listSearch || '').trim().toLowerCase();
                        if (!q) return group.members;
                        return group.members.filter((member) =>
                            String(member.asset_tag || '').toLowerCase().includes(q)
                            || String(member.serial_number || '').toLowerCase().includes(q)
                            || String(member.name || '').toLowerCase().includes(q)
                        );
                    },

                    groupAssetPageCount() {
                        return Math.max(
                            1,
                            Math.ceil(this.filteredGroupAssets().length / (this.roomLayout.listPerPage || 12)),
                        );
                    },

                    pagedGroupAssets() {
                        const perPage = this.roomLayout.listPerPage || 12;
                        const page = Math.min(this.roomLayout.listPage || 1, this.groupAssetPageCount());
                        const start = (page - 1) * perPage;
                        return this.filteredGroupAssets().slice(start, start + perPage);
                    },

                    selectedLayoutAsset() {
                        const id = this.roomLayout.selectedAssetId;
                        if (!id) return null;
                        return (this.roomLayout.equipment || []).find((item) => item.id === id) || null;
                    },

                    clearLayoutSelection() {
                        this.roomLayout.selectedGroupKey = null;
                        this.roomLayout.selectedAssetId = null;
                        this.roomLayout.listSearch = '';
                        this.roomLayout.listPage = 1;
                        this.roomLayout.lifecycle = { loading: false, data: null, equipmentId: null };
                        if (this.isComlabRoomLayout()) {
                            this.roomLayout.comlabNav = 'rows';
                            this.roomLayout.selectedComlabRow = null;
                            this.roomLayout.comlabSetCarouselIndex = 0;
                        }
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    openLayoutGroup(key) {
                        this.roomLayout.selectedGroupKey = key;
                        this.roomLayout.selectedAssetId = null;
                        this.roomLayout.listSearch = '';
                        this.roomLayout.listPage = 1;
                        this.roomLayout.lifecycle = { loading: false, data: null, equipmentId: null };

                        const group = this.layoutGroups().find((item) => item.key === key);
                        if (group && group.quantity === 1 && group.primaryId) {
                            this.selectLayoutAsset(group.primaryId);
                            return;
                        }

                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    formatLayoutDate(value) {
                        if (!value) return '—';
                        const date = new Date(value);
                        if (Number.isNaN(date.getTime())) return String(value);
                        return date.toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                        });
                    },

                    formatLayoutCost(value) {
                        if (value === null || value === undefined || value === '') return '—';
                        const amount = Number(value);
                        if (Number.isNaN(amount)) return '—';
                        return '₱' + amount.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        });
                    },

                    async loadAssetLifecycle(equipmentId) {
                        if (!equipmentId) {
                            this.roomLayout.lifecycle = { loading: false, data: null, equipmentId: null };
                            return;
                        }

                        this.roomLayout.lifecycle = {
                            loading: true,
                            data: null,
                            equipmentId,
                        };

                        try {
                            const response = await fetch(
                                `/maintenance/equipment/lifecycle/${equipmentId}`,
                                {
                                    headers: {
                                        Accept: 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                },
                            );

                            if (!response.ok) {
                                throw new Error('Failed to load lifecycle');
                            }

                            const data = await response.json();

                            if (this.roomLayout.lifecycle.equipmentId !== equipmentId) {
                                return;
                            }

                            this.roomLayout.lifecycle = {
                                loading: false,
                                data,
                                equipmentId,
                            };
                        } catch (error) {
                            if (this.roomLayout.lifecycle.equipmentId !== equipmentId) {
                                return;
                            }

                            this.roomLayout.lifecycle = {
                                loading: false,
                                data: null,
                                equipmentId,
                            };
                        }

                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    selectLayoutAsset(id) {
                        this.roomLayout.selectedAssetId = id;
                        this.loadAssetLifecycle(id);
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    },

                    // ===================================
                    // Auto Position Equipment
                    // Place BELOW zonePosition()
                    // ===================================

                    updateEquipmentLocation(item) {
                        const pos = this.zonePosition(item.location);

                        item.x = pos.x;

                        item.y = pos.y;
                    },
                    openRoomManager(node) {
                        this.roomManager = {
                            open: true,
                            id: +node.dataset.id,
                            name: node.dataset.name || "",
                            originalName: node.dataset.name || "",
                            type: node.dataset.type || "Room",
                            assets: +(node.dataset.assets || 0),
                            activeReports: +(node.dataset.activeReports || 0),
                        };
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                        });
                    },
                    closeRoomManager() {
                        this.roomManager.open = false;
                    },
                    openRoomLayout(roomId) {
                        const room = this.roomCatalog.find((item) => item.id === roomId);
                        if (!room) return;

                        if (room.layout_mode === 'workstation_grid') {
                            this.roomLayout.open = false;
                            this.loadWorkstationLayout(roomId);
                            return;
                        }

                        this.roomLayout = {
                            open: true,
                            edit: false,
                            fullscreen: false,
                            id: room.id,
                            name: room.name,
                            type: room.type || 'Room',
                            equipment: room.equipment.map((item) => ({
                                width: item.width || 120,
                                height: item.height || 96,
                                rotation: item.rotation || 0,
                                ...item,
                            })),
                            selectedGroupKey: null,
                            selectedAssetId: null,
                            listSearch: '',
                            listPage: 1,
                            listPerPage: 12,
                            comlabRows: [],
                            comlabRowLayouts: [],
                            comlabNav: 'rows',
                            selectedComlabRow: null,
                            selectedComlabRowTable: null,
                            comlabSetCarouselIndex: 0,
                            comlabHoldingPage: 0,
                        };
                        this.selectedComlabRowTable = null;

                        this.initComlabLayout(room);
                        this.$nextTick(() => {
                            this.bindDragging();
                            if (window.lucide) lucide.createIcons();
                        });
                    },
                    async loadWorkstationLayout(roomId) {
                        this.workstationLayout.loading = true;

                        try {
                            const response = await fetch(
                                `/maintenance/infrastructure/rooms/${roomId}/layout`,
                                {
                                    headers: {
                                        Accept: 'application/json',
                                    },
                                },
                            );

                            if (!response.ok) {
                                throw new Error();
                            }

                            const payload = await response.json();

                            this.workstationLayout.open = true;
                            this.workstationLayout.roomId = payload.room.id;
                            this.workstationLayout.room = payload.room;
                            this.workstationLayout.slots = payload.workstation_slots || [];
                            this.workstationLayout.selectedSlotId = null;
                            this.workstationLayout.selectedSlot = null;
                            this.workstationLayout.generator.template_id = payload.workstation_slots?.[0]?.template_id || null;
                        } catch (error) {
                            this.toast = 'Unable to load workstation layout.';

                            setTimeout(() => (this.toast = ''), 3000);
                        } finally {
                            this.workstationLayout.loading = false;
                        }
                    },
                    selectWorkstationSlot(slot) {
                        this.workstationLayout.selectedSlotId = slot?.id ?? null;
                        this.workstationLayout.selectedSlot = slot ?? null;
                    },
                    workstationSlotHealth(slot) {
                        const assets = slot?.assets || [];
                        if (assets.length === 0) return 'No assets assigned';

                        const failed = assets.filter((asset) => ['Damaged', 'Under Maintenance', 'Disposed'].includes(asset.condition)).length;
                        if (failed === 0) return 'Healthy';
                        if (failed >= 2) return 'Needs attention';
                        return 'Partially degraded';
                    },
                    openWorkstationGenerator() {
                        this.workstationLayout.generatorOpen = true;
                    },
                    closeWorkstationGenerator() {
                        this.workstationLayout.generatorOpen = false;
                    },
                    async createWorkstationRow() {
                        if (!this.workstationLayout.roomId) return;

                        if (!this.workstationLayout.generator.template_id) {
                            this.toast = 'Select a workstation template first.';
                            setTimeout(() => (this.toast = ''), 2500);
                            return;
                        }

                        try {
                            const response = await fetch(
                                `/maintenance/infrastructure/rooms/${this.workstationLayout.roomId}/workstation-slots`,
                                {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        Accept: 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify(this.workstationLayout.generator),
                                },
                            );

                            if (!response.ok) throw new Error();

                            const payload = await response.json();
                            this.workstationLayout.slots = [
                                ...(this.workstationLayout.slots || []),
                                ...(payload.slots || []),
                            ];
                            this.workstationLayout.generatorOpen = false;
                            this.toast = 'Workstation row created.';
                            setTimeout(() => (this.toast = ''), 2500);
                        } catch (error) {
                            this.toast = 'Unable to create workstation row.';
                            setTimeout(() => (this.toast = ''), 3000);
                        }
                    },
                    closeLayoutModal:{

                        open:false,

                        title:"",

                        message:""

                    },
                    closeRoomLayout() {
                        this.roomLayout.open = false;
                        this.roomLayout.edit = false;
                        this.roomLayout.fullscreen = false;
                        this.clearLayoutSelection();
                    },
                    requestCloseRoomLayout(){

                        if(this.roomLayout.edit && this.layoutDirty){

                            this.closeLayoutModal.title = "Discard Changes?";

                            this.closeLayoutModal.message =
                                "You have unsaved layout changes. Any changes will be lost.";

                            this.closeLayoutModal.open = true;

                            return;

                        }

                        this.closeRoomLayout();

                    },
                    toggleRoomLayoutFullscreen() {
                        this.roomLayout.fullscreen = !this.roomLayout.fullscreen;
                        this.$nextTick(() => {
                            if (window.lucide) lucide.createIcons();
                            // Recalculate rotate-handle placement after the canvas resizes
                            if (this.selectedEquipmentId) {
                                this.updateEquipmentRotateHandlePlacement();
                            }
                            if (this.selectedComlabRowTable) {
                                this.updateComlabRowRotateHandlePlacement();
                            }
                        });
                    },

                    toggleRoomLayoutEdit() {

                        if (this.roomLayout.edit && this.layoutDirty) {

                            this.closeLayoutModal.title = "Discard Unsaved Changes?";

                            this.closeLayoutModal.message =
                                "Any changes made since entering Edit Mode will be lost.";

                            this.closeLayoutModal.open = true;

                            return;

                        }

                        const enteringEdit = !this.roomLayout.edit;

                        if (enteringEdit) {
                            this.originalRoomLayout = JSON.parse(
                                JSON.stringify(this.roomLayout.equipment),
                            );
                            this.originalComlabRows = this.isComlabRoomLayout()
                                ? JSON.parse(JSON.stringify(this.roomLayout.comlabRows || []))
                                : null;
                            this.originalComlabRowLayouts = this.isComlabRoomLayout()
                                ? JSON.parse(JSON.stringify(this.roomLayout.comlabRowLayouts || []))
                                : null;
                            this.layoutDirty = false;
                        }

                        this.roomLayout.edit = !this.roomLayout.edit;

                        this.$nextTick(() => this.bindDragging());

                    },
                    equipmentIcon(name, size = null) {
                        return window.PrismEquipmentIcons
                            ? window.PrismEquipmentIcons.svg(name, size)
                            : '📦';
                    },

                    equipmentVisualCategory(name) {
                        return window.PrismEquipmentIcons
                            ? window.PrismEquipmentIcons.category(name)
                            : 'default';
                    },

                    equipmentCategoryGroup(name) {
                        return window.PrismEquipmentIcons
                            ? window.PrismEquipmentIcons.group(name)
                            : 'Other Equipment';
                    },
                    applyRoomLabelContrast(el) {
                        if (!el) {
                            return;
                        }

                        const color = el.dataset.color || el.style.backgroundColor || "#60A5FA";
                        el.style.setProperty("--room-label-color", this.getContrastingTextColor(color));
                    },
                    getContrastingTextColor(color) {
                        const rgb = this.parseCssColor(color);

                        if (!rgb) {
                            return "#0f172a";
                        }

                        const yiq = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;

                        return yiq >= 150 ? "#0f172a" : "#ffffff";
                    },
                    parseCssColor(color) {
                        if (!color) {
                            return null;
                        }

                        const value = String(color).trim();
                        const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

                        if (hex) {
                            let digits = hex[1];

                            if (digits.length === 3) {
                                digits = digits.split("").map((part) => part + part).join("");
                            }

                            return {
                                r: parseInt(digits.slice(0, 2), 16),
                                g: parseInt(digits.slice(2, 4), 16),
                                b: parseInt(digits.slice(4, 6), 16),
                            };
                        }

                        const rgb = value.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);

                        if (!rgb) {
                            return null;
                        }

                        return {
                            r: Number(rgb[1]),
                            g: Number(rgb[2]),
                            b: Number(rgb[3]),
                        };
                    },
                    markRoomEdge(el, edge) {
                        if (!el) {
                            return;
                        }

                        el.setAttribute(`data-edge-${edge}`, "true");
                        el.style.setProperty(`--edge-${edge}`, "1");
                    },
                    syncRoomEdges() {
                        const tolerance = 6;
                        const edges = ["left", "right", "top", "bottom"];
                        const rooms = [...document.querySelectorAll(".room-block")];

                        rooms.forEach((el) => {
                            edges.forEach((edge) => {
                                el.removeAttribute(`data-edge-${edge}`);
                                el.style.setProperty(`--edge-${edge}`, "0");
                            });
                        });

                        const layoutRooms = rooms
                            .map((el) => ({
                                el,
                                floor: Number(el.dataset.floor),
                                x: Number(el.dataset.x) || 0,
                                y: Number(el.dataset.y) || 0,
                                width: Number(el.dataset.width) || el.offsetWidth || 0,
                                height: Number(el.dataset.height) || el.offsetHeight || 0,
                                rotation: Number(el.dataset.rotation || 0),
                            }))
                            .filter((room) => Math.abs(room.rotation % 360) < 1);

                        const axisOverlap = (aStart, aEnd, bStart, bEnd) =>
                            Math.min(aEnd, bEnd) - Math.max(aStart, bStart) > tolerance;

                        const withinTouch = (gap) =>
                            Math.abs(gap) <= tolerance || (gap < 0 && Math.abs(gap) <= tolerance + 12);

                        for (let i = 0; i < layoutRooms.length; i++) {
                            for (let j = i + 1; j < layoutRooms.length; j++) {
                                const a = layoutRooms[i];
                                const b = layoutRooms[j];

                                if (a.floor !== b.floor) {
                                    continue;
                                }

                                const aRight = a.x + a.width;
                                const bRight = b.x + b.width;
                                const aBottom = a.y + a.height;
                                const bBottom = b.y + b.height;

                                if (axisOverlap(a.y, aBottom, b.y, bBottom)) {
                                    const gapAtoB = b.x - aRight;
                                    const gapBtoA = a.x - bRight;

                                    if (withinTouch(gapAtoB)) {
                                        this.markRoomEdge(a.el, "right");
                                        this.markRoomEdge(b.el, "left");
                                    } else if (withinTouch(gapBtoA)) {
                                        this.markRoomEdge(b.el, "right");
                                        this.markRoomEdge(a.el, "left");
                                    }
                                }

                                if (axisOverlap(a.x, aRight, b.x, bRight)) {
                                    const gapAtoB = b.y - aBottom;
                                    const gapBtoA = a.y - bBottom;

                                    if (withinTouch(gapAtoB)) {
                                        this.markRoomEdge(a.el, "bottom");
                                        this.markRoomEdge(b.el, "top");
                                    } else if (withinTouch(gapBtoA)) {
                                        this.markRoomEdge(b.el, "bottom");
                                        this.markRoomEdge(a.el, "top");
                                    }
                                }
                            }
                        }
                    },
                    syncRoomLabel(el) {
                        if (!el) {
                            return;
                        }

                        const width = Number(el.dataset.width) || el.offsetWidth || 0;
                        const height = Number(el.dataset.height) || el.offsetHeight || 0;
                        const vertical = height > width;
                        const longAxis = vertical ? height : width;

                        let size = "large";

                        if (longAxis < 50) {
                            size = "tiny";
                        } else if (longAxis < 90) {
                            size = "small";
                        } else if (longAxis < 140) {
                            size = "medium";
                        }

                        el.dataset.size = size;
                        el.dataset.labelOrientation = vertical ? "vertical" : "horizontal";
                        this.applyRoomLabelContrast(el);

                        const roomName = el.querySelector(".room-name, [data-room-name]");

                        if (roomName) {
                            roomName.textContent = this.abbreviateRoom(
                                roomName.dataset.fullName || el.dataset.name || "",
                                size,
                            );
                        }
                    },
                    abbreviateRoom(name, size) {
                        if (!name) return "";

                        if (size === "large") {
                            return name;
                        }

                        let short = name;

                        short = short.replace(/Computer Laboratory/gi, "Comlab");
                        short = short.replace(/Computer Lab/gi, "Comlab");
                        short = short.replace(/Lecture Room/gi, "Lecture");
                        short = short.replace(/Administration Office/gi, "Admin");
                        short = short.replace(/Registrar Office/gi, "Registrar");
                        short = short.replace(/Guidance Office/gi, "Guidance");

                        if (size === "medium" || size === "small") {
                            return short;
                        }

                        // Tiny version

                        return short
                            .split(" ")
                            .map((word) => {
                                if (/^\d+$/.test(word)) {
                                    return word;
                                }

                                return word.charAt(0);
                            })
                            .join("")
                            .toUpperCase();
                    },
                    async renameRoom() {
                        const name = this.roomManager.name.trim();
                        if (!name || !this.roomManager.id) return;
                        this.saving = true;
                        try {
                            const response = await fetch(
                                `/maintenance/infrastructure/rooms/${this.roomManager.id}`,
                                {
                                    method: "PATCH",
                                    headers: {
                                        "Content-Type": "application/json",
                                        Accept: "application/json",
                                        "X-CSRF-TOKEN": document.querySelector(
                                            'meta[name="csrf-token"]',
                                        ).content,
                                    },
                                    body: JSON.stringify({ room_name: name }),
                                },
                            );
                            if (!response.ok) throw new Error();
                            const node = document.querySelector(
                                `.room-block[data-id="${this.roomManager.id}"]`,
                            );
                            if (node) {
                                node.dataset.name = name;
                                const label = node.querySelector("[data-room-name]");
                                if (label) {
                                    label.dataset.fullName = name;
                                }
                                this.syncRoomLabel(node);
                            }
                            this.roomManager.originalName = name;
                            this.toast = "Room renamed";
                            setTimeout(() => (this.toast = ""), 2500);
                        } catch (e) {
                            this.toast = "Could not rename the room";
                            setTimeout(() => (this.toast = ""), 3000);
                        } finally {
                            this.saving = false;
                        }
                    },
                    async archiveRoom() {
                        if (!this.roomManager.id) return;
                        const ok = window.confirm(
                            `Archive ${this.roomManager.originalName}? Live equipment and schedules inside this room will be cleared, but old reports/history will remain.`,
                        );
                        if (!ok) return;
                        this.saving = true;
                        try {
                            const response = await fetch(
                                `/maintenance/infrastructure/rooms/${this.roomManager.id}`,
                                {
                                    method: "DELETE",
                                    headers: {
                                        "Content-Type": "application/json",
                                        Accept: "application/json",
                                        "X-CSRF-TOKEN": document.querySelector(
                                            'meta[name="csrf-token"]',
                                        ).content,
                                    },
                                    body: JSON.stringify({
                                        reason: "Archived from layout editor",
                                    }),
                                },
                            );
                            if (!response.ok) throw new Error();
                            document
                                .querySelector(
                                    `.room-block[data-id="${this.roomManager.id}"]`,
                                )
                                ?.remove();
                            if (this.selectedRoom === this.roomManager.id) {
                                this.selectedRoom = null;
                            }
                            this.closeRoomManager();
                            this.toast = "Room archived";
                            setTimeout(() => (this.toast = ""), 2500);
                        } catch (e) {
                            this.toast = "Could not archive the room";
                            setTimeout(() => (this.toast = ""), 3000);
                        } finally {
                            this.saving = false;
                        }
                    },
                };
            }
        </script>
    @endpush
@endsection
