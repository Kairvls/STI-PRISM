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
        @keydown.space.window.prevent="spacePressed = true"
        @keyup.space.window="spacePressed = false"
        @keydown.escape.window="
            wizardOpen = false;

            await loadCampus();

            step = 1;
        "
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
                        await loadCampus();

                        step = 1;

                        wizardOpen = true;

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
                    >Healthy</span
                >
                <span class="flex items-center gap-2"
                    ><i class="h-2.5 w-2.5 rounded-full bg-amber-400"></i>Needs
                    attention</span
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
                                data-lucide="rotate-ccw"
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
                    class="relative min-h-0 flex-1 overflow-hidden"
                    :class="blueprint.isPanning
                        ? 'cursor-grabbing'
                        : 'cursor-grab'"
                >
                    <div
                        x-ref="blueprintCanvas"
                        class="blueprint-grid absolute left-0 top-0 overflow-hidden rounded-[24px] border border-white/70 bg-gradient-to-br from-[#dbe6f1] via-[#edf3f8] to-[#cbd9e7] shadow-inner"
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
                            class="pointer-events-none absolute inset-[38px] rounded-[36px] border-[14px] border-slate-500/15 shadow-[inset_0_0_0_2px_rgba(255,255,255,.8)]"
                        ></div>
                        <div
                            class="pointer-events-none absolute left-1/2 top-1/2 h-64 w-[520px] -translate-x-1/2 -translate-y-1/2 rotate-[-8deg] rounded-[50%] border-[24px] border-white/50 bg-sky-100/60 shadow-inner"
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
                            >
                                @forelse ($rooms->where("room_floor_id", $floor->floor_id) as $room)
                                    @php
                                        $statusColor = match ($room->room_status) {
                                            "Critical" => "#EF4444",
                                            "Maintenance Needed" => "#F59E0B",
                                            default => "#10B981",
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        @click="if(!editMode) selectedRoom={{ $room->room_id }}"
                                        class="room-block room-card group absolute overflow-hidden z-10 rounded-xl border-2 p-3 text-left shadow-[0_14px_22px_rgba(15,23,42,.18)] transition duration-200 hover:z-20 hover:-translate-y-1 hover:brightness-105 focus:outline-none focus:ring-4 focus:ring-[#005EA6]/25 {{ $room->room_status === 'Critical' ? 'critical-room' : '' }}"
                                        :class="{'cursor-move ring-4 ring-[#FFF200]/50': editMode, 'ring-4 ring-[#005EA6]/25': selectedRoom === {{ $room->room_id }}}"
                                        data-size="large"
                                        data-id="{{ $room->room_id }}"
                                        data-floor="{{ $floor->floor_id }}"
                                        data-x="{{ $room->room_x }}"
                                        data-y="{{ $room->room_y }}"
                                        data-width="{{ $room->room_width }}"
                                        data-height="{{ $room->room_height }}"
                                        data-name="{{ e($room->room_name) }}"
                                        data-type="{{ e($room->room_type ?: 'Room') }}"
                                        data-assets="{{ $room->equipment->sum("equipment_quantity") }}"
                                        data-active-reports="{{ $room->monitoring["active_reports"] }}"
                                        style="left:{{ $room->room_x }}px;top:{{ $room->room_y }}px;width:{{ $room->room_width }}px;height:{{ $room->room_height }}px;background:{{ $room->room_color ?: '#60A5FA' }};border-color:{{ $statusColor }};--room-depth:{{ $room->room_color ?: '#60A5FA' }}"
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
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -left-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -right-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -bottom-1.5 -left-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -bottom-1.5 -right-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -top-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -bottom-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -left-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
                                        <span
                                            x-show="editMode"
                                            class="resize-grip pointer-events-none absolute -right-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white"
                                        ></span>
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
                    class="flex flex-col gap-4 bg-gradient-to-br from-slate-950 to-[#005EA6] px-6 py-5 text-white lg:flex-row lg:items-center lg:justify-between"
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
                                ? 'bg-[#FFF200] text-slate-950'
                                : 'bg-white/10 text-white'"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-black"
                        >
                            <i data-lucide="move" class="h-4 w-4"></i>
                            <span
                                x-text="
                                    roomLayout.edit
                                        ? 'Editing Layout'
                                        : 'Edit layout'
                                "
                            ></span>
                        </button>
                        <button
                            type="button"
                            @click="saveLayout()"
                            :disabled="saving || !roomLayout.edit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-black text-white disabled:opacity-50"
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
                                class="room-equipment-node absolute z-20 flex min-w-[86px] items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 shadow-lg"
                                :class="roomLayout.edit
                                    ? 'cursor-grab ring-4 ring-[#FFF200]/40'
                                    : ''"
                                :data-equipment-id="item.id"
                                :data-x="item.x"
                                :data-y="item.y"
                                :style="`
                                    left:${item.x}%;
                                    top:${item.y}%;
                                    transform:translate(-50%,-50%);
                                    will-change:left,top;
                                    `"
                            >
                                <span
                                    class="text-lg"
                                    x-text="equipmentIcon(item.name)"
                                ></span>
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
                            </div>
                        </template>
                    </div>

                    <aside
                        class="space-y-3 rounded-[24px] bg-white p-4 shadow-sm"
                    >
                        <div>
                            <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Equipment list</p>
                            <p class="mt-1 text-sm font-bold text-slate-600">Drag items on the room map, then save.</p>
                        </div>
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
        .room-card:before {
            content: "";
            position: absolute;
            left: 10px;
            right: -9px;
            bottom: -10px;
            height: 12px;
            background: color-mix(in srgb, var(--room-depth), #0f172a 30%);
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
            background: color-mix(in srgb, var(--room-depth), #0f172a 42%);
            clip-path: polygon(0 0, 100% 9%, 100% 92%, 0 100%);
        }
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
                    roomSearch: "",
                    zoomInput: "100",
                    roomCatalog: @js ($roomCatalog),
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
                $errors->any()
                    ? "true"
                    : "false"
            }},
                    step: 1,
                    toast: "",
                    floors: @js ($floors
                    ->map(fn($f) => ["id" => $f->floor_id, "label" => $f->floor_level])
                    ->values()),
                    form: Object.assign(
                        {
                            building_name: "",

                            building_logo: null,

                            building_address: null,

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

                                height:+room.dataset.height

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

                            room.style.left = original.x + "px";

                            room.style.top = original.y + "px";

                            room.style.width = original.width + "px";

                            room.style.height = original.height + "px";

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
                            rooms: [
                                {
                                    name: "",
                                    type: "Lecture Room",
                                    status: "Normal",
                                    equipment: [],
                                },
                            ],
                        });
                    },
                    generateFloors() {
                        const existingFloors = {};

                        this.form.floors.forEach((floor) => {
                            existingFloors[floor.level] = floor;
                        });

                        const newFloors = [];

                        for (
                            let floorNumber = this.form.minFloor;
                            floorNumber <= this.form.maxFloor;
                            floorNumber++
                        ) {
                            const level = this.floorLabel(floorNumber);

                            if (existingFloors[level]) {
                                newFloors.push(existingFloors[level]);
                            } else {
                                newFloors.push({
                                    id: null,

                                    level,

                                    rooms: [
                                        {
                                            id: null,

                                            name: "",

                                            type: "Lecture Room",

                                            status: "Normal",

                                            equipment: [],
                                        },
                                    ],
                                });
                            }
                        }

                        this.form.floors = newFloors;
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
                        this.form.floors[fi].rooms.push({
                            id: null,

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
                        this.form.floors[fi].rooms[ri].equipment.push({
                            id: null,

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
                    async loadCampus() {
                        try {
                            const response = await fetch(
                                @js (route("maintenance.infrastructure.campus.load")),
                            );

                            if (!response.ok) {
                                throw new Error();
                            }

                            const data = await response.json();

                            this.form = Object.assign(
                                {
                                    building_name: "",

                                    building_logo: null,

                                    building_address: null,

                                    minFloor: 2,

                                    maxFloor: 3,

                                    floors: [],
                                },
                                data,
                            );

                            if (this.form.floors.length > 0) {
                                const numbers = this.form.floors.map((floor) =>
                                    parseInt(floor.level),
                                );

                                this.form.minFloor = Math.min(...numbers);

                                this.form.maxFloor = Math.max(...numbers);
                            }
                        } catch (error) {
                            console.error(error);

                            this.toast = "Unable to load campus.";

                            setTimeout(() => (this.toast = ""), 3000);
                        }
                    },
                    zonePosition(zone) {
                        switch (zone) {
                            case "Front Wall":
                                return [20 + Math.random() * 60, 10];

                            case "Rear Wall":
                                return [60 + Math.random() * 30, 88];

                            case "Left Row Pods":
                                return [20, 35 + Math.random() * 40];

                            case "Right Row Pods":
                                return [80, 35 + Math.random() * 40];

                            case "Center Ceiling":
                                return [50, 45];

                            default:
                                return [50, 50];
                        }
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
                                    },
                                    end: () => {

                                        if (!this.editMode) return;

                                        this.layoutDirty = true;

                                    },
                                },
                            });

                        interact(".room-equipment-node").unset();
                        interact(".room-equipment-node")
                            .draggable({
                                inertia: false,
                                listeners: {
                                    start: (event) => {
                                        if (!this.roomLayout.edit) return;
                                        const node = event.target;
                                        const parentRect = node.parentElement.getBoundingClientRect();
                                        const x = (parseFloat(node.dataset.x) || 50) / 100 * parentRect.width;
                                        const y = (parseFloat(node.dataset.y) || 50) / 100 * parentRect.height;

                                        node.dataset.dragX = x;
                                        node.dataset.dragY = y;
                                        node.style.left = x + "px";
                                        node.style.top = y + "px";
                                        node.classList.add("dragging");
                                    },
                                    move: (event) => {
                                        if (!this.roomLayout.edit) return;

                                        const node = event.target;
                                        const parent = node.parentElement;
                                        const rect = parent.getBoundingClientRect();

                                        let x =
                                            (parseFloat(node.dataset.dragX) || 0) + event.dx;
                                        let y =
                                            (parseFloat(node.dataset.dragY) || 0) + event.dy;

                                        x = Math.min(rect.width - 12, Math.max(12, x));
                                        y = Math.min(rect.height - 12, Math.max(12, y));

                                        node.style.left = x + "px";
                                        node.style.top = y + "px";
                                        node.dataset.dragX = x;
                                        node.dataset.dragY = y;
                                        node.dataset.x = Math.round((x / rect.width) * 100);
                                        node.dataset.y = Math.round((y / rect.height) * 100);
                                    },
                                    end: (event) => {
                                        if (!this.roomLayout.edit) return;

                                        const node = event.target;
                                        const rect = node.parentElement.getBoundingClientRect();
                                        const x = Math.min(96, Math.max(4, Math.round(((parseFloat(node.dataset.dragX) || 0) / rect.width) * 100)));
                                        const y = Math.min(96, Math.max(4, Math.round(((parseFloat(node.dataset.dragY) || 0) / rect.height) * 100)));

                                        node.dataset.x = x;
                                        node.dataset.y = y;
                                        node.style.left = x + "%";
                                        node.style.top = y + "%";
                                        delete node.dataset.dragX;
                                        delete node.dataset.dragY;
                                        node.classList.remove("dragging");

                                        this.syncEquipmentZone(node);

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

                                        rooms: nodes.map((n) => ({
                                            id: +n.dataset.id,

                                            x: +n.dataset.x,

                                            y: +n.dataset.y,

                                            width: +n.dataset.width,

                                            height: +n.dataset.height,
                                        })),

                                        equipment: [
                                            ...document.querySelectorAll(
                                                ".room-equipment-node",
                                            ),
                                        ].map((node) => ({
                                            id: +node.dataset.equipmentId,

                                            x: +node.dataset.x,

                                            y: +node.dataset.y,

                                            zone: node.dataset.zone ||
                                                this.detectEquipmentZone(
                                                    +node.dataset.x,
                                                    +node.dataset.y,
                                                ),
                                        })),
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

                    // =====================================
                    // Place BELOW saveLayout()
                    // =====================================

                    zonePosition(location) {
                        const zones = {
                            "Front Wall": { x: 50, y: 12 },

                            "Rear Wall": { x: 50, y: 88 },

                            "Center Ceiling": { x: 50, y: 18 },

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
                        this.roomLayout = {
                            open: true,
                            edit: false,
                            id: room.id,
                            name: room.name,
                            equipment: room.equipment,
                        };
                        this.roomLayout.equipment.forEach((item)=>{

                            const hasSavedPosition =

                                item.x !== null &&
                                item.y !== null &&
                                !(item.x == 40 && item.y == 40);

                            if(!hasSavedPosition){

                                const [x,y] = this.zonePosition(

                                    item.placement_zone ||

                                    item.location

                                );

                                item.x = x;

                                item.y = y;

                                this.layoutDirty = true;

                            }else{

                                item.x = +item.x;

                                item.y = +item.y;

                            }

                        });
                        this.$nextTick(() => {
                            this.bindDragging();
                            if (window.lucide) lucide.createIcons();
                        });
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
