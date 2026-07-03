@extends ("layouts.maintenance-layout")

@section ("title", "Infrastructure Monitoring | PRISM")

@section ("content")
    @php
        $initialFloor =
            $floors->firstWhere("floor_id", $requestedFloorId) ?? $floors->first();
        $roomCatalog = $rooms
            ->map(
                fn($room) => [
                    "id" => $room->room_id,
                    "floor_id" => $room->room_floor_id,
                    "name" => $room->room_name,
                    "type" => $room->room_type ?: "Room",
                    "status" => $room->room_status,
                    "layout_mode" => $room->room_layout_mode ?: "loose_equipment",
                    "x" => (int) $room->room_x,
                    "y" => (int) $room->room_y,
                    "width" => (int) $room->room_width,
                    "height" => (int) $room->room_height,
                    "equipment" => $room->equipment
                        ->values()
                        ->map(
                            fn($equipment) => [
                                "id" => $equipment->equipment_id,

                                "name" => $equipment->equipment_name,

                                "category" => $equipment->equipment_category_id,

                                "category_name" => optional($equipment->category)->equipment_category_name,

                                "quantity" => (int) $equipment->equipment_quantity,

                                "condition" => $equipment->equipment_condition_status,

                                "inventory_status" =>
                                    $equipment->equipment_inventory_status,

                                "location" => $equipment->equipment_current_location,

                                "placement_zone" =>
                                    $equipment->equipment_placement_zone,

                                "x" => (int) ($equipment->equipment_position_x ?? 50),

                                "y" => (int) ($equipment->equipment_position_y ?? 50),

                                "width" => (int) ($equipment->equipment_width ?? 120),

                                "height" => (int) ($equipment->equipment_height ?? 96),

                                "rotation" => (int) ($equipment->equipment_rotation ?? 0),
                            ],
                        )
                        ->all(),
                ],
            )
            ->values();
    @endphp
    @php
        use Illuminate\Support\Str;
    @endphp

    <div
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
            wizardOpen = false;

            await loadCampus();

            step = 1;
        "
        @pointermove.window="trackRoomRotation($event); trackEquipmentRotation($event); trackEquipmentAction($event)"
        @pointerup.window="endRoomRotation($event); endEquipmentRotation($event); endEquipmentAction($event)"
        class="mx-auto flex min-h-0 max-w-[1700px] flex-1 flex-col overflow-hidden"
    >
        @if (session("success"))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition
                class="mb-5 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800"
            >
                <span class="flex items-center gap-2"
                    ><i data-lucide="circle-check" class="h-5 w-5"></i>{{
                        session(
                            "success",
                        )
                    }}</span
                >
                <button @click="show = false" aria-label="Dismiss">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        @endif

        <header
            class="mb-6 flex shrink-0 flex-col gap-5 xl:flex-row xl:items-end xl:justify-between"
        >
            <div>
                <div
                    class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[.22em] text-[#005EA6]"
                >
                    <span
                        class="h-2 w-2 rounded-full bg-[#FFF200] ring-4 ring-yellow-100"
                    ></span>
                    Campus spatial intelligence
                </div>
                <h1
                    class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl"
                >
                    Infrastructure Monitoring
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">Explore STI Ormoc room health, assets, reports, and maintenance in one spatial workspace.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button
                    @click="
                        step = (String(form.building_name || '').trim() || (form.floors || []).length > 0)
                            ? 2
                            : 1;

                        wizardOpen = true;

                        wizardHasLocalChanges = false;

                        loadCampus(false);

                        $nextTick(() => {
                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });
                    "
                    class="inline-flex items-center gap-2 rounded-xl bg-[#005EA6] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-900/15 transition hover:-translate-y-0.5 hover:bg-[#004b86]"
                >
                    <i data-lucide="building-2" class="h-4 w-4"></i> Configure
                    campus
                </button>
                
            </div>
        </header>

        <section
            class="mb-5 flex shrink-0 flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:flex-row lg:items-center lg:justify-between"
        >
            <div
                class="flex gap-2 overflow-x-auto p-1"
                role="tablist"
                aria-label="Floor selection"
            >
                @forelse ($floors as $floor)
                    <button
                        @click="selectFloor({{ $floor->floor_id }})"
                        :class="activeFloor === {{ $floor->floor_id }} ? 'bg-[#005EA6] text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
                        class="flex min-w-max items-center gap-3 rounded-xl px-5 py-3 text-sm font-bold transition"
                        role="tab"
                    >
                        <span>{{ $floor->floor_level }}</span>
                        <span
                            :class="activeFloor === {{ $floor->floor_id }} ? 'bg-white/15' : 'bg-slate-100'"
                            class="rounded-md px-2 py-0.5 text-[10px]"
                            >{{ $floor->rooms_count }} rooms</span
                        >
                    </button>
                @empty
                    <p class="px-4 py-3 text-sm text-slate-500">No floors configured yet.</p>
                @endforelse
            </div>
            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-2 px-4 pb-2 text-[11px] font-semibold text-slate-500 lg:pb-0"
            >
                <span class="flex items-center gap-2"
                    ><i class="h-2.5 w-2.5 rounded-full bg-emerald-500"></i
                    >Good</span
                >
                <span class="flex items-center gap-2"
                    ><i class="h-2.5 w-2.5 rounded-full bg-amber-400"></i>Under Maintenance</span
                >
                <span class="flex items-center gap-2"
                    ><i
                        class="h-2.5 w-2.5 animate-pulse rounded-full bg-red-500"
                    ></i
                    >Critical</span
                >
            </div>
        </section>

        <section
            class="mb-5 flex shrink-0 flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between"
        >
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#005EA6]/10 text-[#005EA6]"
                >
                    <i data-lucide="search" class="h-5 w-5"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <input
                        id="room-blueprint-search"
                        type="search"
                        x-model="roomSearch"
                        @keydown.enter.prevent="focusRoomSearch()"
                        placeholder="Search Library, Room 305, Comlab 1..."
                        class="mt-1 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold outline-none transition focus:border-[#005EA6] focus:ring-4 focus:ring-blue-100"
                    />
                </div>
                <button
                    type="button"
                    @click="focusRoomSearch()"
                    class="rounded-xl bg-[#005EA6] px-4 py-3 text-sm font-black text-white"
                >
                    Find
                </button>
            </div>
            
        </section>

        <!-- =============================== -->
        <!-- Workspace -->
        <!-- Replace this whole class -->
        <!-- =============================== -->

        <div class="flex min-h-0 w-full flex-1 gap-6 overflow-hidden">
            <section
                x-ref="blueprintWorkspace"
                class="relative flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-[#e8eef5] shadow-xl"
            >
                <div
                    class="absolute z-20 rounded-xl border border-blue-500 bg-white/85 px-4 py-2.5 shadow-lg backdrop-blur"
                    :class="isFullscreen ? 'top-4 left-4' : 'top-2 left-2'"
                >
                    <!--<p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Active Floor</p>-->
                    <p class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="map" class="h-4 w-4 text-[#005EA6]"></i><span x-text="activeFloorLabel"></span></p>
                </div>






                <div
                    class="absolute z-30 flex items-center gap-3"
                    :class="isFullscreen ? 'top-4 right-4' : 'top-2 right-2'"
                >

                    <button
                        @click="toggleBlueprintEdit()"
                        :class="editMode
                            ? 'bg-yellow-400 text-slate-900 border-yellow-500 shadow-lg shadow-yellow-400/20 ring-4 ring-yellow-400/30'
                            : 'bg-white/80 hover:bg-white text-slate-700 border-yellow-500 hover:text-slate-900 shadow-md'"
                        class="inline-flex items-center gap-2.5 rounded-xl border px-4 py-2.5 text-sm font-medium backdrop-blur-md transition-all duration-200 ease-in-out active:scale-95"
                    >
                        <span x-show="editMode" class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-900 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-slate-900"></span>
                        </span>

                        <i data-lucide="pencil" class="h-4 w-4 transition-transform" :class="editMode ? 'scale-110' : ''"></i>

                        <span 
                            x-text="editMode ? 'Editing Room Layout' : 'Edit Room Layout'"
                            class="tracking-wide"
                        ></span>
                    </button>

                    <button
                        x-show="editMode"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        @click="saveLayout()"
                        :disabled="saving"
                        :class="saving 
                            ? 'bg-emerald-700/80 cursor-not-allowed opacity-80 shadow-none' 
                            : saveSuccess 
                                ? 'bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/10' 
                                : 'bg-emerald-600 hover:bg-emerald-500 active:scale-95 shadow-md shadow-emerald-600/20'"
                        class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white border border-transparent transition-all duration-200 ease-in-out"
                    >
                        <svg x-show="saving" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <i x-show="!saving" :data-lucide="saveSuccess ? 'check' : 'save'" class="h-4 w-4" x-cloak></i>

                        <span 
                            x-text="saving ? 'Saving...' : saveSuccess ? 'Saved!' : 'Save Layout'"
                            class="tracking-wide"
                        ></span>
                    </button>

                </div>








                <div
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
                </div>

                <!-- ===================================== -->
                <!-- Premium Blueprint Controls -->
                <!-- Replace the old Blueprint Controls -->
                <!-- ===================================== -->

                <div
                    class="absolute z-30"
                    
                    :class="isFullscreen ? 'top-20 right-4' : 'top-16 right-2'"
                >

                    <div
                        class="blueprint-toolbar flex w-12 flex-col overflow-hidden rounded-2xl border border-white/70 bg-white/90 shadow-2xl backdrop-blur-xl"
                    >

                        <!-- ==================== -->
                        <!-- Zoom In -->
                        <!-- ==================== -->

                        <button
                            type="button"
                            @click="zoomBlueprint(0.1)"
                            title="Zoom In"
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
                            title="Zoom Out"
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
                            title="Reset View"
                            class="flex w-full items-center justify-center py-3 hover:bg-slate-100"
                        >

                            <i
                                data-lucide="history"
                                class="h-4 w-4 transition duration-300 group-hover:rotate-180"
                            ></i>

                        </button>

                        <div class="border-t border-slate-200"></div>

                        <!-- ==================== -->
                        <!-- Fit -->
                        <!-- ==================== -->

                        <button
                            type="button"
                            @click="toggleFullscreen()"
                            :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"
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

                </div>

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
                    class="relative min-h-0 flex-1 overflow-hidden bg-white"
                    :class="isRotating ? 'cursor-grabbing' : blueprint.isPanning ? 'cursor-grabbing' : 'cursor-grab'"
                >
                <!--bg-gradient-to-br from-[#dbe6f1] via-[#edf3f8] to-[#cbd9e7] for blueprintCanvas-->
                    <div
                        x-ref="blueprintCanvas"
                        class="blueprint-grid absolute left-0 top-0 overflow-hidden rounded-[24px] border border-white/70 bg-white shadow-inner"
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
                            class="pointer-events-none absolute inset-[1px] rounded-[26px] border-[14px] border-slate-500/15 shadow-[inset_0_0_0_2px_rgba(255,255,255,.8)]"
                        ></div>
                        <div
                            class="pointer-events-none absolute left-1/2 top-1/2 h-64 w-[520px] -translate-x-1/2 -translate-y-1/2 rotate-[-8deg] rounded-[50%] border-[24px] border-white/50 bg-sky-100/20 shadow-inner"
                        ></div>
                        <div
                            class="pointer-events-none absolute bottom-8 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full bg-white/65 px-4 py-2 text-[10px] font-bold uppercase tracking-[.2em] text-slate-400"
                        >
                            <i data-lucide="navigation" class="h-3 w-3"></i>
                            Central corridor
                        </div>

                        @foreach ($floors as $floor)
                            <div
                                x-show="activeFloor === {{ $floor->floor_id }}"
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
                                    @endphp
                                    <!--ring-[#5B6682]/40-->
                                    <button
                                        type="button"
                                        @click.stop="if(!editMode) selectedRoom={{ $room->room_id }}"
                                        
                                        class="room-block room-card group absolute overflow-visible z-10 rounded-xl border-2 p-3 text-left shadow-[0_14px_22px_rgba(15,23,42,.18)] transition duration-200 hover:z-20 hover:-translate-y-1 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-[#07319C] {{ $room->room_status === 'Critical' ? 'critical-room' : '' }}"
                                        :class="{'cursor-move ring-2 ring-[#07319C] rounded-lg': editMode, 'ring-2 ring-[#07319C]': selectedRoom === {{ $room->room_id }}}"
                                        data-size="large"
                                        data-id="{{ $room->room_id }}"
                                        data-floor="{{ $floor->floor_id }}"
                                        data-x="{{ $room->room_x }}"
                                        data-y="{{ $room->room_y }}"
                                        data-width="{{ $room->room_width }}"
                                        data-height="{{ $room->room_height }}"
                                        data-rotation="{{ data_get($room->room_metadata, 'rotation', 0) }}"
                                        data-name="{{ e($room->room_name) }}"
                                        data-type="{{ e($room->room_type ?: 'Room') }}"
                                        data-assets="{{ $room->equipment->sum("equipment_quantity") }}"
                                        data-active-reports="{{ $room->monitoring["active_reports"] }}"
                                        style="left:{{ $room->room_x }}px;top:{{ $room->room_y }}px;width:{{ $room->room_width }}px;height:{{ $room->room_height }}px;--room-depth:{{ $room->room_color ?: '#60A5FA' }};transform:rotate({{ data_get($room->room_metadata, 'rotation', 0) }}deg);transform-origin:center center;"
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
                                            title="View room layout"
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
                                            class="absolute inset-0 z-20 overflow-hidden rounded-xl"
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
                                            title="Rotate selected room"
                                            :class="isRotating ? 'cursor-grabbing rotate-handle-cursor' : 'cursor-grab rotate-handle-cursor'"
                                            class="absolute left-1/2 bottom-[-52px] z-40 flex h-10 w-10 -translate-x-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
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
                                                    title="Rotate selected equipment"
                                                    :class="equipmentIsRotating ? 'cursor-grabbing rotate-equipment-handle-cursor' : 'cursor-grab rotate-equipment-handle-cursor'"
                                                    class="absolute left-1/2 bottom-[-52px] z-40 flex h-10 w-10 -translate-x-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
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

            @include ("maintenance-personnel.infrastructure.monitor-drawer")
        </div>

        @include ("maintenance-personnel.infrastructure.campus-wizard")

        <div
            x-show="roomManager.open"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[1200] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm"
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
                                <h3 class="font-black text-red-950">
                                    Archive/reset this room
                                </h3>
                                <p class="mt-1 text-xs leading-5 text-red-700">This removes the room from the active blueprint and deletes live equipment and schedules inside it. Old reports/history are preserved for audit.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="archiveRoom()"
                            :disabled="saving"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-900/15 disabled:opacity-50"
                        >
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                            Archive room and clear details
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-show="roomLayout.open"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[1250] flex items-center justify-center bg-slate-950/60 p-4"
        >
            <div
                class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[30px] bg-white shadow-2xl"
            >
                <div
                    class="flex flex-col gap-4 bg-gradient-to-br from-slate-950 to-[#a68800] px-6 py-5 text-white lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.22em] text-white/60">Room interior layout</p>
                        <h2
                            class="mt-1 text-2xl font-black"
                            x-text="roomLayout.name || 'Room layout'"
                        ></h2>
                        <p class="mt-1 text-sm text-white/70">Drag equipment inside the room, then click Save Layout to apply your changes.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="toggleRoomLayoutEdit()"
                            :class="roomLayout.edit
                                ? 'bg-[#FFF200] text-slate-950 hover:bg-[#f3e80e]'
                                : 'bg-white/10 text-white hover:bg-white/20'"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold"
                        >
                            <i data-lucide="pencil" class="h-4 w-4"></i>
                            <span
                                x-text="
                                    roomLayout.edit
                                        ? 'Editing...'
                                        : 'Edit layout'
                                "
                            ></span>
                        </button>
                        <button
                            type="button"
                            @click="saveLayout()"
                            :disabled="saving || !roomLayout.edit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Save
                        </button>
                        <button
                            type="button"
                            @click="requestCloseRoomLayout()"
                            class="rounded-xl bg-white/10 p-2 transition hover:bg-white/20"
                            aria-label="Close room layout"
                        >
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div
                    class="grid min-h-0 flex-1 gap-4 overflow-auto bg-slate-100 p-5 lg:grid-cols-[minmax(0,1fr)_260px]"
                >
                    <div
                        x-ref="roomInteriorCanvas"
                        class="room-interior-grid relative h-[520px] min-w-[620px] overflow-hidden rounded-[26px] border-8 border-slate-300 bg-white shadow-inner"
                        @pointerdown="if (roomLayout.edit) selectEquipment(null)"
                    >
                        <div
                            class="pointer-events-none absolute inset-x-20 top-4 rounded-full border border-dashed border-slate-300 px-4 py-1 text-center text-[10px] font-black uppercase tracking-[.2em] text-slate-400"
                        >
                            Front wall / board
                        </div>
                        <div
                            class="pointer-events-none absolute bottom-3 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-xl bg-amber-100 px-4 py-2 text-xs font-black text-amber-700"
                        >
                            <i data-lucide="door-open" class="h-4 w-4"></i>
                            Door
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
                            x-for="item in roomLayout.equipment"
                            :key="item.id"
                        >
                            <div
                                class="room-equipment-node absolute z-20 flex min-w-[86px] items-center gap-2 overflow-visible rounded-2xl border-2 border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-md hover:border-[#07319C]"
                                :class="{
                                    'ring-2 ring-[#07319C]/80 cursor-move':
                                        roomLayout.edit &&
                                        selectedEquipmentId !== item.id,

                                    'ring-2 ring-[#07319C] cursor-move':
                                        roomLayout.edit &&
                                        selectedEquipmentId === item.id,

                                    'cursor-move':
                                        equipmentAction &&
                                        equipmentAction.type === 'drag' &&
                                        selectedEquipmentId === item.id
                                }"
                                :data-equipment-id="item.id"
                                :data-x="item.x"
                                :data-y="item.y"
                                :data-width="item.width || 120"
                                :data-height="item.height || 96"
                                :data-rotation="item.rotation || 0"
                                @pointerdown.stop="handleEquipmentPointerDown($event, item.id)"
                                :style="`
                                    left:${item.x}%;
                                    top:${item.y}%;
                                    width:${item.width || 120}px;
                                    height:${item.height || 96}px;
                                    touch-action:none;
                                    transform:translate(-50%,-50%) rotate(${item.rotation || 0}deg);
                                    transform-origin:center center;
                                    will-change:left,top,transform;
                                `"
                            >
                                <!-- Equipment Icon -->
                                <span
                                    class="text-lg"
                                    x-text="equipmentIcon(item.name)"
                                ></span>

                                <!-- Equipment Details -->
                                <div class="flex flex-col leading-tight">
                                    <span
                                        class="max-w-[130px] truncate"
                                        x-text="item.name"
                                    ></span>

                                    <span
                                        class="max-w-[130px] truncate text-[10px] font-semibold text-slate-400"
                                        x-text="
                                            item.location ||
                                            item.placement_zone ||
                                            'No location'
                                        "
                                    ></span>
                                </div>

                                <!-- Resize Handles -->
                                <template x-if="roomLayout.edit && selectedEquipmentId === item.id">

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
                                <template x-if="roomLayout.edit && selectedEquipmentId === item.id">

                                    <div
                                        x-effect="
                                            if (roomLayout.edit && selectedEquipmentId === item.id) {
                                                $nextTick(() => {
                                                    if (window.lucide) {
                                                        lucide.createIcons();
                                                    }
                                                });
                                            }
                                        "
                                        class="absolute -bottom-12 left-1/2 z-40 -translate-x-1/2 pointer-events-auto"
                                    >

                                        <button
                                            type="button"
                                            @pointerdown.stop.prevent="beginEquipmentRotation($event)"
                                            :class="equipmentIsRotating
                                                ? 'cursor-grabbing rotate-equipment-handle-cursor'
                                                : 'cursor-grab rotate-equipment-handle-cursor'"
                                            class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-100"
                                        >

                                            <template x-if="!equipmentIsRotating">
                                                <span
                                                    x-init="$nextTick(() => window.lucide?.createIcons())"
                                                    class="flex items-center justify-center"
                                                >
                                                    <i
                                                        data-lucide="refresh-cw"
                                                        class="h-4 w-4"
                                                    ></i>
                                                </span>
                                            </template>

                                            <template x-if="equipmentIsRotating">
                                                <span
                                                    class="flex items-center justify-center text-sm font-black leading-none text-slate-900"
                                                >
                                                    <span
                                                        x-text="Math.round(equipmentRotationDisplayAngle) + '°'"
                                                    ></span>
                                                </span>
                                            </template>

                                        </button>

                                    </div>

                                </template>

                            </div>
                        </template>
                        
                    </div>

                    <aside
                        class="flex h-[520px] min-h-0 flex-col rounded-[24px] bg-white p-4 shadow-sm"
                    >
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Equipment list</p>
                            <p class="mt-1 text-sm font-bold text-slate-600">Drag items on the room map, then save.</p>
                        </div>
                        <div class="mt-3 min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                            <template
                                x-for="item in roomLayout.equipment"
                                :key="'list-' + item.id"
                            >
                                <div
                                    class="rounded-2xl border border-slate-100 bg-slate-50 p-3"
                                >
                                    <p class="font-black text-slate-800">
                                        <span
                                            x-text="equipmentIcon(item.name)"
                                        ></span>
                                        <span x-text="item.name"></span>
                                    </p>
                                    <div class="mt-2 space-y-1 text-xs">
                                        <p class="font-semibold text-slate-600">
                                            Condition:
                                            <span
                                                x-text="item.condition || 'Unknown'"
                                            ></span>
                                        </p>

                                        <p class="text-slate-500">
                                            Location:
                                            <span
                                                x-text="
                                                    item.location || 'Not assigned'
                                                "
                                            ></span>
                                        </p>

                                        <p class="text-slate-500">
                                            Placement:
                                            <span
                                                x-text="
                                                    item.placement_zone || 'None'
                                                "
                                            ></span>
                                        </p>

                                        <p class="text-slate-400">X:
                                        <span x-text="item.x"></span>% • Y: <span x-text="item.y"></span>%</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </aside>
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
            class="fixed inset-0 z-[1300] flex items-center justify-center bg-slate-950/60 p-4"
        >

            <div
                @click.stop
                class="w-full max-w-md overflow-hidden rounded-[28px] bg-white shadow-2xl"
            >

                <div
                    class="bg-gradient-to-r from-yellow-500 to-[#FFF200] px-6 py-5 text-white"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex p-4 items-center justify-center rounded-2xl bg-white/50"
                        >

                            <i data-lucide="triangle-alert" class="h-6 w-6 text-gray-500"></i>

                        </div>

                        <div>

                            <h2
                                class="text-xl text-gray-900 font-bold"
                                x-text="closeLayoutModal.title"
                            ></h2>

                            <p
                                class="mt-1 text-sm text-gray-600"
                                x-text="closeLayoutModal.message"
                            ></p>

                        </div>

                    </div>

                </div>

                <div class="flex justify-end gap-3 p-6">

                    <button

                        @click="closeLayoutModal.open = false"

                        class="rounded-xl border border-slate-500 hover:text-gray-900 hover:border-gray-800 px-5 py-2.5 text-gray-500"

                    >

                        Continue Editing

                    </button>

                    <button

                        @click="

                            closeLayoutModal.open = false;

                            closeRoomLayout();

                        "

                        class="rounded-xl bg-red-600 px-5 py-2.5 text-white hover:bg-red-700"

                    >

                        Discard Changes

                    </button>

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
            class="fixed inset-0 z-[1301] flex items-center justify-center bg-slate-950/60 p-4"
        >

            <div
                @click.stop
                class="w-full max-w-md overflow-hidden rounded-[28px] bg-white shadow-2xl"
            >

                <div
                    class="bg-gradient-to-r from-yellow-500 to-[#FFF200] px-6 py-5 text-white"
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="flex p-4 items-center justify-center rounded-2xl bg-white/50"
                        >

                            <i data-lucide="triangle-alert" class="h-6 w-6 text-gray-500"></i>

                        </div>

                        <div>

                            <h2
                                class="text-xl text-gray-900 font-bold"
                                x-text="blueprintLayoutModal.title"
                            ></h2>

                            <p
                                class="mt-1 text-sm text-gray-600"
                                x-text="blueprintLayoutModal.message"
                            ></p>

                        </div>

                    </div>

                </div>


                <div class="flex justify-end gap-3 p-6">

                    <button
                        @click="blueprintLayoutModal.open=false"
                        class="rounded-xl border border-slate-500 hover:text-gray-900 hover:border-gray-800 px-5 py-2.5 text-gray-500"
                    >
                        Continue Editing
                    </button>

                    <button
                        @click="discardBlueprintChanges()"
                        class="rounded-xl bg-red-600 px-5 py-2.5 text-white hover:bg-red-700"
                    >
                        Discard Changes
                    </button>

                </div>

            </div>

        </div>

        <div
            x-show="toast"
            x-transition
            x-cloak
            class="fixed bottom-6 right-6 z-[1100] rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-2xl"
            x-text="toast"
        ></div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
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
        .rotate-equipment-handle-cursor {
            cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='28' viewBox='0 0 28 28'%3E%3Cpath d='M6 14a8 8 0 0 1 10.6-7.6M22 14a8 8 0 0 0-10.6 7.6' stroke='%23004E8C' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3Cpath d='M22 8v4h-4' stroke='%23004E8C' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3Cpath d='M6 20v-4h4' stroke='%23004E8C' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") 14 14, grab;
        }
        .rotate-active-cursor,
        body.rotate-active-cursor,
        body.equipment-rotate-active-cursor {
            cursor: grabbing;
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

            color: #0f172a;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

            user-select: none;

            pointer-events: none;

            transition: 0.2s;
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
        <script>
            function infrastructureMonitor(initialFloor) {
                return {
                    activeFloor: initialFloor || null,
                    selectedRoom: null,
                    editMode: false,
                    saving: false,
                    
                    saveSuccess:false,

                    layoutDirty:false,
                    equipmentFallbackBound: false,
                    equipmentDrag: null,
                    equipmentAction: null,
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
                    equipmentRotationDrag: null,
                    equipmentRotationDisplayAngle: 0,
                    equipmentRotationHandleOffset: 90,
                    roomSearch: "",
                    zoomInput: "100",
                    roomRotationInput: 0,
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
                        id: null,
                        name: "",
                        equipment: [],
                    },

                    originalRoomLayout: null,

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
                    step3Mode: 'fast',
                    step3ValidationAttempted: false,
                    step3InlineErrors: {},
                    step4InlineErrors: [],
                    toast: "",
                    floors: @js ($floors
                    ->map(fn($f) => ["id" => $f->floor_id, "label" => $f->floor_level])
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
                        });

                        window.addEventListener("resize", () => {
                            this.fitBlueprint();
                        });

                        this.$nextTick(() => {
                            this.bindDragging();

                            this.$nextTick(() => {
                                this.fitBlueprint();
                            });

                            document.querySelectorAll(".room-block").forEach((room) => {
                                const w = parseInt(room.dataset.width);

                                const h = parseInt(room.dataset.height);

                                let size = "large";

                                if (w < 50 || h < 40) {
                                    size = "tiny";
                                } else if (w < 90 || h < 60) {
                                    size = "small";
                                } else if (w < 140 || h < 80) {
                                    size = "medium";
                                }

                                room.dataset.size = size;
                                const roomName = room.querySelector(".room-name");

                                if (roomName) {
                                    roomName.textContent = this.abbreviateRoom(
                                        roomName.dataset.fullName,

                                        size,
                                    );
                                }
                            });

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
                    },
                    selectFloor(id) {
                        this.activeFloor = id;
                        this.selectedRoom = null;
                        this.closeRoomManager();
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
                        document.body.classList.add("cursor-grabbing", "rotate-active-cursor");

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
                        this.layoutDirty = true;
                    },
                    rotateSelectedRoom(delta) {
                        if (!this.editMode || !this.selectedRoom) {
                            return;
                        }

                        const current = this.getSelectedRoomRotation();
                        this.setSelectedRoomRotation(current + delta);
                        this.syncSelectedRoomControl();
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
                            return;
                        }

                        this.$nextTick(() => {
                            this.syncSelectedEquipmentControl();
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

                            this.beginEquipmentRotation(event);

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
                        
                        const rect = node.getBoundingClientRect();

                        const centerX =
                            rect.left - parentRect.left + rect.width / 2;

                        const centerY =
                            rect.top - parentRect.top + rect.height / 2;

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
                        };
                    },
                    trackEquipmentAction(event) {
                        if (this.equipmentPendingDrag && !this.equipmentAction) {

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

                        const action = this.equipmentAction;
                        const node = action.node;
                        const rect = action.parentRect;
                        const dx = event.clientX - action.startX;
                        const dy = event.clientY - action.startY;

                        if (action.type === "drag") {
                            let x = action.startCenterX + dx;
                            let y = action.startCenterY + dy;

                            x = Math.min(rect.width - 12, Math.max(12, x));
                            y = Math.min(rect.height - 12, Math.max(12, y));

                            node.style.left = x + "px";
                            node.style.top = y + "px";
                            node.dataset.x = Math.round((x / rect.width) * 100);
                            node.dataset.y = Math.round((y / rect.height) * 100);
                        }

                        if (action.type === "resize") {
                            const MIN_WIDTH = 50;
                            const MAX_WIDTH = 220;

                            const MIN_HEIGHT = 80;
                            const MAX_HEIGHT = 220;
                            let width = action.startWidth;
                            let height = action.startHeight;
                            let centerX = action.startCenterX;
                            let centerY = action.startCenterY;

                            const localDx = dx * action.cos + dy * action.sin;
                            const localDy = -dx * action.sin + dy * action.cos;
                            let shiftLocalX = 0;
                            let shiftLocalY = 0;

                            if (action.handleX === "left") {
                                width = Math.min(
                                    MAX_WIDTH,
                                    Math.max(MIN_WIDTH, action.startWidth - localDx)
                                );
                                shiftLocalX = localDx / 2;
                            } else if (action.handleX === "right") {
                                width = Math.min(
                                    MAX_WIDTH,
                                    Math.max(MIN_WIDTH, action.startWidth + localDx)
                                );
                                shiftLocalX = localDx / 2;
                            }

                            if (action.handleY === "top") {
                                height = Math.min(
                                    MAX_HEIGHT,
                                    Math.max(MIN_HEIGHT, action.startHeight - localDy)
                                );
                                shiftLocalY = localDy / 2;
                            } else if (action.handleY === "bottom") {
                                height = Math.min(
                                    MAX_HEIGHT,
                                    Math.max(MIN_HEIGHT, action.startHeight + localDy)
                                );
                                shiftLocalY = localDy / 2;
                            }

                            

                            const shiftWorldX = shiftLocalX * action.cos - shiftLocalY * action.sin;
                            const shiftWorldY = shiftLocalX * action.sin + shiftLocalY * action.cos;

                            centerX = action.startCenterX + shiftWorldX;
                            centerY = action.startCenterY + shiftWorldY;

                            centerX = Math.min(rect.width - width / 2, Math.max(width / 2, centerX));
                            centerY = Math.min(rect.height - height / 2, Math.max(height / 2, centerY));

                            node.style.width = width + "px";
                            node.style.height = height + "px";
                            node.style.left = centerX + "px";
                            node.style.top = centerY + "px";
                            node.dataset.width = width;
                            node.dataset.height = height;
                            node.dataset.x = Math.round((centerX / rect.width) * 100);
                            node.dataset.y = Math.round((centerY / rect.height) * 100);
                        }

                        if (this.selectedEquipmentId === Number(node.dataset.equipmentId)) {
                            this.syncSelectedEquipmentControl();
                        }
                    },
                    endEquipmentAction(event) {
                        if (this.equipmentPendingDrag) {
                            this.equipmentPendingDrag = null;
                        }
                        if (!this.equipmentAction) return;
                        if (event.pointerId !== this.equipmentAction.pointerId) return;

                        const node = this.equipmentAction.node;
                        const rect = node.parentElement.getBoundingClientRect();
                        const left = parseFloat(node.style.left || "0") || 0;
                        const top = parseFloat(node.style.top || "0") || 0;
                        const percentX = Math.round((left / rect.width) * 100);
                        const percentY = Math.round((top / rect.height) * 100);

                        node.dataset.x = Math.min(96, Math.max(4, percentX));
                        node.dataset.y = Math.min(96, Math.max(4, percentY));
                        node.dataset.width = parseInt(node.style.width);
                        node.dataset.height = parseInt(node.style.height);

                        // ADD HERE
                        const item = this.roomLayout.equipment.find(
                            equipment => equipment.id === Number(node.dataset.equipmentId)
                        );

                        if (item) {
                            item.x = Number(node.dataset.x);
                            item.y = Number(node.dataset.y);
                            item.width = Number(node.dataset.width);
                            item.height = Number(node.dataset.height);
                            item.rotation = Number(node.dataset.rotation || 0);
                        }

                        node.style.left = node.dataset.x + "%";
                        node.style.top = node.dataset.y + "%";

                        this.equipmentAction = null;

                        node.classList.remove("dragging");
                        node.releasePointerCapture?.(event.pointerId);
                        this.syncEquipmentZone(node);
                        this.layoutDirty = true;
                        this.equipmentPendingDrag = null;
                    },
                    getSelectedEquipmentRotation() {
                        if (!this.selectedEquipmentId) return 0;

                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );

                        return item ? Number(item.dataset.rotation || 0) : 0;
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
                    },
                    setSelectedEquipmentRotation(rotation) {
                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );
                        if (!item) return;

                        const value = rotation;
                        item.dataset.rotation = value;
                        const equipment = this.roomLayout.equipment.find(
                            equipment => equipment.id === Number(item.dataset.equipmentId)
                        );

                        if (equipment) {
                            equipment.rotation = value;
                        }
                        item.style.transform = `translate(-50%,-50%) rotate(${value}deg)`;

                        this.equipmentRotationDisplayAngle = this.formatRotationDisplay(value);
                        this.selectedEquipmentControl.rotation = value;
                        this.layoutDirty = true;
                    },
                    beginEquipmentRotation(event) {
                        if (!this.roomLayout.edit || !this.selectedEquipmentId) return;

                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );
                        if (!item) return;

                        const rect = item.getBoundingClientRect();
                        const centerX = rect.left + rect.width / 2;
                        const centerY = rect.top + rect.height / 2;
                        const lastMouseAngle = Math.atan2(event.clientY - centerY, event.clientX - centerX) * (180 / Math.PI);

                        this.equipmentRotationDrag = {
                            centerX,
                            centerY,
                            pointerId: event.pointerId,
                            lastMouseAngle,
                            handleElement: event.currentTarget,
                        };

                        this.equipmentIsRotating = true;
                        this.equipmentRotationDisplayAngle = this.formatRotationDisplay(this.getSelectedEquipmentRotation());
                        document.body.classList.add("cursor-grabbing", "equipment-rotate-active-cursor");

                        event.currentTarget.setPointerCapture?.(event.pointerId);
                    },
                    trackEquipmentRotation(event) {
                        if (!this.equipmentRotationDrag || event.pointerId !== this.equipmentRotationDrag.pointerId) {
                            return;
                        }

                        const { centerX, centerY } = this.equipmentRotationDrag;
                        const newMouseAngle = Math.atan2(event.clientY - centerY, event.clientX - centerX) * (180 / Math.PI);
                        let delta = newMouseAngle - this.equipmentRotationDrag.lastMouseAngle;

                        if (delta > 180) {
                            delta -= 360;
                        } else if (delta < -180) {
                            delta += 360;
                        }

                        const currentRotation = this.getSelectedEquipmentRotation();
                        const degrees = currentRotation + delta;

                        this.equipmentRotationDrag.lastMouseAngle = newMouseAngle;
                        this.setSelectedEquipmentRotation(degrees);
                    },
                    endEquipmentRotation(event) {
                        if (!this.equipmentRotationDrag || event.pointerId !== this.equipmentRotationDrag.pointerId) {
                            return;
                        }

                        const item = document.querySelector(
                            `.room-equipment-node[data-equipment-id="${this.selectedEquipmentId}"]`,
                        );
                        if (item) {
                            const finalRotation = Number(item.dataset.rotation || 0);
                            const normalized = this.normalizeRotation(finalRotation);
                            item.dataset.rotation = normalized;
                            item.style.transform = `translate(-50%,-50%) rotate(${normalized}deg)`;
                            this.equipmentRotationDisplayAngle = this.formatRotationDisplay(normalized);
                            this.selectedEquipmentControl.rotation = normalized;
                        }

                        this.equipmentRotationDrag.handleElement?.releasePointerCapture?.(event.pointerId);
                        this.equipmentRotationDrag = null;
                        this.equipmentIsRotating = false;
                        document.body.classList.remove("cursor-grabbing", "equipment-rotate-active-cursor");
                        this.layoutDirty = true;
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
                    },
                    endBlueprintPan() {
                        this.blueprint.isPanning = false;
                    },
                    resetBlueprintView() {
                        this.fitBlueprint();
                    },

                    fitBlueprint() {
                        const padding = {
                            top: 15,

                            right: 8,

                            bottom: 8,

                            left: 8,
                        };

                        const viewport = this.$refs.blueprintViewport;

                        if (!viewport) return;

                        // -----------------------------------
                        // Available viewport space
                        // -----------------------------------

                        const availableWidth =
                            viewport.clientWidth - padding.left - padding.right;

                        const availableHeight =
                            viewport.clientHeight - padding.top - padding.bottom;

                        // -----------------------------------
                        // Blueprint original size
                        // -----------------------------------

                        const blueprintWidth = this.blueprint.width;

                        const blueprintHeight = this.blueprint.height;

                        // -----------------------------------
                        // Calculate scale for BOTH directions
                        // -----------------------------------

                        const scaleX = availableWidth / blueprintWidth;

                        const scaleY = availableHeight / blueprintHeight;

                        // -----------------------------------
                        // Choose whichever fits BOTH
                        // -----------------------------------

                        const zoom = Math.min(scaleX, scaleY);

                        this.blueprint.zoom = zoom;

                        this.zoomInput = Math.round(zoom * 100);

                        // -----------------------------------
                        // Calculate scaled size
                        // -----------------------------------

                        const scaledWidth = blueprintWidth * zoom;

                        const scaledHeight = blueprintHeight * zoom;

                        // -----------------------------------
                        // Perfectly center the blueprint
                        // -----------------------------------

                        this.blueprint.panX =
                            padding.left + (availableWidth - scaledWidth) / 2;

                        this.blueprint.panY =
                            padding.top + (availableHeight - scaledHeight) / 2;
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
                    toggleBlueprintEdit(){

                        if(!this.editMode){

                            this.originalBlueprintLayout = [

                                ...document.querySelectorAll(".room-block")

                            ].map(room=>({

                                id:+room.dataset.id,

                                x:+room.dataset.x,

                                y:+room.dataset.y,

                                width:+room.dataset.width,

                                height:+room.dataset.height,

                                rotation:+room.dataset.rotation || 0

                            }));

                            this.editMode = true;

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

                    },
                    discardBlueprintChanges(){

                        this.originalBlueprintLayout.forEach(original=>{

                            const room = document.querySelector(

                                `.room-block[data-id="${original.id}"]`

                            );

                            if(!room) return;

                            room.dataset.x = original.x;

                            room.dataset.y = original.y;

                            room.dataset.width = original.width;

                            room.dataset.height = original.height;

                            room.dataset.rotation = original.rotation || 0;

                            room.style.left = original.x + "px";

                            room.style.top = original.y + "px";

                            room.style.width = original.width + "px";

                            room.style.height = original.height + "px";

                            room.style.transform = `rotate(${room.dataset.rotation}deg)`;

                            room.style.transformOrigin = "center center";

                        });

                        this.layoutDirty = false;

                        this.editMode = false;

                        this.blueprintLayoutModal.open = false;

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

                            zone: "Front Wall",
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

                        this.$nextTick(() => {
                            event.target.submit();
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

                        this.wizardHasLocalChanges = false;
                        this.step3ValidationAttempted = false;

                        this.loadCampus(false);

                        this.$nextTick(() => {
                            if (window.lucide) {
                                lucide.createIcons();
                            }
                        });
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
                        const item = this.roomLayout.equipment.find(
                            (equipment) => equipment.id === +node.dataset.equipmentId,
                        );

                        if (!item) return null;

                        item.x = +node.dataset.x;
                        item.y = +node.dataset.y;

                        const zone = this.detectEquipmentZone(item.x, item.y);

                        item.location = zone;
                        item.placement_zone = zone;
                        
                        node.dataset.zone = zone;

                        return item;
                    },
                    
                    bindDragging() {
                        if (!window.interact) {
                            console.warn("Interact.js not loaded.");
                            return;
                        }
                        interact(".room-block")
                            /*.on("tap", (event) => {
                                if (!this.editMode) return;
                                event.preventDefault();
                                this.openRoomManager(event.currentTarget);
                            })*/
                            .on("tap", (event) => {

                                if (!this.editMode) return;

                                event.preventDefault();

                                this.selectedRoom = Number(event.currentTarget.dataset.id);

                            })
                            .draggable({
                                inertia: false,
                                modifiers: [
                                    interact.modifiers.snap({
                                        targets: [interact.snappers.grid({ x: 20, y: 20 })],
                                        range: Infinity,
                                    }),
                                    interact.modifiers.restrictRect({
                                        restriction: "parent",
                                        endOnly: true,
                                    }),
                                ],
                                listeners: {
                                    move: (event) => {
                                        if (!this.editMode) return;
                                        const el = event.target;
                                        const x = Math.max(
                                            0,
                                            (parseInt(el.dataset.x) || 0) + event.dx,
                                        );
                                        const y = Math.max(
                                            0,
                                            (parseInt(el.dataset.y) || 0) + event.dy,
                                        );
                                        el.style.left = x + "px";
                                        el.style.top = y + "px";
                                        el.dataset.x = Math.round(x);
                                        el.dataset.y = Math.round(y);
                                    },
                                    end:()=>{

                                        if(!this.editMode){

                                            return;

                                        }

                                        this.layoutDirty = true;

                                    },
                                },
                            })
                            .resizable({
                                edges: { left: true, right: true, bottom: true, top: true },
                                margin: 12,
                                modifiers: [
                                    interact.modifiers.snapSize({
                                        targets: [interact.snappers.grid({ x: 20, y: 20 })],
                                    }),
                                    interact.modifiers.restrictSize({
                                        min: {
                                            width: 80,
                                            height: 80,
                                        },
                                        max: { width: 600, height: 450 },
                                    }),
                                    interact.modifiers.restrictEdges({ outer: "parent" }),
                                ],
                                listeners: {
                                    move: (event) => {
                                        if (!this.editMode) return;
                                        const el = event.target;
                                        let x =
                                            (parseInt(el.dataset.x) || 0) +
                                            event.deltaRect.left;
                                        let y =
                                            (parseInt(el.dataset.y) || 0) +
                                            event.deltaRect.top;
                                        x = Math.max(0, x);
                                        y = Math.max(0, y);
                                        const width = Math.round(event.rect.width);
                                        const height = Math.round(event.rect.height);
                                        /* ---------- Responsive Room Layout ---------- */

                                        let size = "large";

                                        if (width < 50 || height < 40) {
                                            size = "tiny";
                                        } else if (width < 90 || height < 60) {
                                            size = "small";
                                        } else if (width < 140 || height < 80) {
                                            size = "medium";
                                        }

                                        el.dataset.size = size;
                                        const roomName = el.querySelector(".room-name");

                                        if (roomName) {
                                            roomName.textContent = this.abbreviateRoom(
                                                roomName.dataset.fullName,

                                                size,
                                            );
                                        }
                                        Object.assign(el.style, {
                                            width: width + "px",
                                            height: height + "px",
                                            left: x + "px",
                                            top: y + "px",
                                        });
                                        Object.assign(el.dataset, {
                                            x: Math.round(x),
                                            y: Math.round(y),
                                            width,
                                            height,
                                        });

                                        if (this.selectedRoom === Number(el.dataset.id)) {
                                            this.syncSelectedRoomControl();
                                        }
                                    },
                                    end: () => {

                                        if (!this.editMode) return;

                                        this.layoutDirty = true;

                                    },
                                },
                            })
                            .draggable({
                                inertia: false,
                                modifiers: [
                                    interact.modifiers.snap({
                                        targets: [interact.snappers.grid({ x: 20, y: 20 })],
                                        range: Infinity,
                                    }),
                                    interact.modifiers.restrictRect({
                                        restriction: "parent",
                                        endOnly: true,
                                    }),
                                ],
                                listeners: {
                                    move: (event) => {
                                        if (!this.editMode) return;
                                        const el = event.target;
                                        const x = Math.max(
                                            0,
                                            (parseInt(el.dataset.x) || 0) + event.dx,
                                        );
                                        const y = Math.max(
                                            0,
                                            (parseInt(el.dataset.y) || 0) + event.dy,
                                        );
                                        el.style.left = x + "px";
                                        el.style.top = y + "px";
                                        el.dataset.x = Math.round(x);
                                        el.dataset.y = Math.round(y);

                                        if (this.selectedRoom === Number(el.dataset.id)) {
                                            this.syncSelectedRoomControl();
                                        }
                                    },
                                    end:()=>{

                                        if(!this.editMode){

                                            return;

                                        }

                                        this.layoutDirty = true;

                                    },
                                },
                            });


                    },
                    bindEquipmentFallback() {
                        if (this.equipmentFallbackBound) return;
                        this.equipmentFallbackBound = true;

                        document.addEventListener("pointerdown", (event) => {

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
                                            };
                                        }),

                                        equipment: [
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
                                }
                            }

                            if (manual) {

                                this.layoutDirty = false;
                                // Exit room editing mode
                                this.roomLayout.edit = false;

                                this.saveSuccess = true;

                                setTimeout(() => {

                                    this.saveSuccess = false;

                                }, 2000);

                                // Exit blueprint editing mode
                                this.editMode = false;

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
                                roomNameNode.textContent = this.abbreviateRoom(
                                    updates.name,
                                    node.dataset.size || "large",
                                );
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
                        const zones = {
                            "Front Wall": { x: 50, y: 12 },

                            "Rear Wall": { x: 50, y: 88 },

                            "Center Ceiling": { x: 50, y: 48 },

                            "Left Row Pods": { x: 18, y: 55 },

                            "Right Row Pods": { x: 82, y: 55 },

                            Storage: { x: 90, y: 90 },
                        };

                        return zones[location] ?? { x: 50, y: 50 };
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
                            id: room.id,
                            name: room.name,
                            equipment: room.equipment.map((item) => ({
                                width: item.width || 120,
                                height: item.height || 96,
                                rotation: item.rotation || 0,
                                ...item,
                            })),
                        };
                        this.roomLayout.equipment.forEach((item)=>{

                            const hasSavedPosition =
                                item.x !== null &&
                                item.y !== null &&
                                item.x !== undefined &&
                                item.y !== undefined &&
                                !(Number(item.x) === 40 && Number(item.y) === 40);

                            if (!hasSavedPosition) {

                                const pos = this.zonePosition(
                                    item.placement_zone || item.location
                                );

                                item.x = Number(pos.x);
                                item.y = Number(pos.y);

                                this.layoutDirty = true;

                            } else {

                                item.x = Number(item.x);
                                item.y = Number(item.y);

                            }

                        });
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
                    },
                    requestCloseRoomLayout(){

                        if(this.roomLayout.edit){

                            this.closeLayoutModal.title = "Discard Changes?";

                            this.closeLayoutModal.message =
                                "You are still editing the room layout. Any unsaved changes will be lost.";

                            this.closeLayoutModal.open = true;

                            return;

                        }

                        this.closeRoomLayout();

                    },
                    toggleRoomLayoutEdit() {

                        if (this.roomLayout.edit && this.layoutDirty) {

                            this.closeLayoutModal.title = "Discard Unsaved Changes?";

                            this.closeLayoutModal.message =
                                "Any changes made since entering Edit Mode will be lost.";

                            this.closeLayoutModal.open = true;

                            return;

                        }

                        this.roomLayout.edit = !this.roomLayout.edit;

                        this.$nextTick(() => this.bindDragging());

                    },
                    equipmentIcon(name) {
                        const label = (name || "").toLowerCase();
                        if (label.includes("projector")) return "📽️";
                        if (label.includes("printer")) return "🖨️";
                        if (label.includes("tv") || label.includes("monitor")) return "📺";
                        if (label.includes("speaker") || label.includes("sound"))
                            return "🔊";
                        if (label.includes("router") || label.includes("wifi")) return "📡";
                        if (
                            label.includes("pc") ||
                            label.includes("desktop") ||
                            label.includes("computer")
                        )
                            return "💻";
                        return "📦";
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
                                if (label) label.textContent = name;
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
