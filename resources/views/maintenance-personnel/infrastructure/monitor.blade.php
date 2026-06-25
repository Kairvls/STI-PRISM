@extends ("layouts.maintenance-layout")

@section ("title", "Infrastructure Monitoring | PRISM")

@section ("content")
    @php
        $initialFloor =
            $floors->firstWhere("floor_id", $requestedFloorId) ?? $floors->first();
    @endphp

    <div
        x-data="infrastructureMonitor({{ (int) optional($initialFloor)->floor_id }})"
        x-init="init()"
        @keydown.escape.window="
            wizardOpen = false;

            await loadCampus();

            step = 1;
        "
        class="mx-auto max-w-[1700px] text-slate-900"
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
            class="mb-6 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between"
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
                <button
                    @click="editMode = !editMode"
                    :class="editMode
                        ? 'bg-[#FFF200] text-slate-950 ring-4 ring-yellow-100'
                        : 'bg-white text-slate-700 ring-1 ring-slate-200'"
                    class="inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-bold shadow-sm transition"
                >
                    <i data-lucide="move-3d" class="h-4 w-4"></i
                    ><span
                        x-text="editMode ? 'Editing layout' : 'Layout editor'"
                    ></span>
                </button>
                <button
                    x-show="editMode"
                    x-transition
                    @click="saveLayout()"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/15 disabled:opacity-60"
                >
                    <i data-lucide="save" class="h-4 w-4"></i
                    ><span x-text="saving ? 'Saving...' : 'Save layout'"></span>
                </button>
            </div>
        </header>

        <section
            class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:flex-row lg:items-center lg:justify-between"
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

        <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_400px]">
            <section
                class="relative min-w-0 overflow-hidden rounded-[28px] border border-slate-200 bg-[#e8eef5] shadow-xl shadow-slate-900/5"
            >
                <div
                    class="absolute left-5 top-5 z-20 rounded-xl border border-white/70 bg-white/85 px-4 py-3 shadow-lg backdrop-blur"
                >
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">Active blueprint</p>
                    <p class="mt-1 flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="map" class="h-4 w-4 text-[#005EA6]"></i><span x-text="activeFloorLabel"></span></p>
                </div>
                <div
                    x-show="editMode"
                    x-transition
                    class="absolute right-5 top-5 z-20 rounded-xl bg-slate-950/85 px-4 py-3 text-xs font-semibold text-white backdrop-blur"
                >
                    <span class="flex items-center gap-2"
                        ><i
                            class="h-2 w-2 rounded-full"
                            :class="saving
                                ? 'animate-pulse bg-amber-400'
                                : 'bg-emerald-400'"
                        ></i
                        ><span
                            x-text="
                                saving
                                    ? 'Saving changes...'
                                    : 'Drag or resize · autosave on'
                            "
                        ></span
                    ></span>
                </div>

                <div class="overflow-auto p-5 pt-24 sm:p-8 sm:pt-24">
                    <div
                        class="blueprint-grid relative h-[720px] min-w-[1180px] overflow-hidden rounded-[24px] border border-white/70 bg-gradient-to-br from-[#dbe6f1] via-[#edf3f8] to-[#cbd9e7] shadow-inner"
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
                                        class="room-block room-card group absolute z-10 rounded-xl border-2 p-3 text-left shadow-[0_14px_22px_rgba(15,23,42,.18)] transition duration-200 hover:z-20 hover:-translate-y-1 hover:brightness-105 focus:outline-none focus:ring-4 focus:ring-[#005EA6]/25 {{ $room->room_status === 'Critical' ? 'critical-room' : '' }}"
                                        :class="{'cursor-move ring-4 ring-[#FFF200]/50': editMode, 'ring-4 ring-[#005EA6]/25': selectedRoom === {{ $room->room_id }}}"
                                        data-id="{{ $room->room_id }}"
                                        data-floor="{{ $floor->floor_id }}"
                                        data-x="{{ $room->room_x }}"
                                        data-y="{{ $room->room_y }}"
                                        data-width="{{ max(110, $room->room_width) }}"
                                        data-height="{{ max(82, $room->room_height) }}"
                                        data-name="{{ e($room->room_name) }}"
                                        data-type="{{ e($room->room_type ?: 'Room') }}"
                                        data-assets="{{ $room->equipment->sum("equipment_quantity") }}"
                                        data-active-reports="{{ $room->monitoring["active_reports"] }}"
                                        style="left:{{ $room->room_x }}px;top:{{ $room->room_y }}px;width:{{ max(110, $room->room_width) }}px;height:{{ max(82, $room->room_height) }}px;background:{{ $room->room_color ?: '#60A5FA' }};border-color:{{ $statusColor }};--room-depth:{{ $room->room_color ?: '#60A5FA' }}"
                                    >
                                        <span
                                            class="relative z-10 flex h-full flex-col justify-between"
                                        >
                                            <span
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <span
                                                    class="rounded-md bg-white/80 px-2 py-1 text-[9px] font-extrabold uppercase tracking-wider text-slate-600"
                                                    >{{
                                                        $room->room_type ?:
                                                            "Room"
                                                    }}</span
                                                >
                                                <i
                                                    class="mt-1 h-2.5 w-2.5 rounded-full shadow"
                                                    style="background:{{ $statusColor }}"
                                                ></i>
                                            </span>
                                            <span>
                                                <strong
                                                    data-room-name
                                                    class="block text-sm font-extrabold leading-tight text-slate-950"
                                                    >{{ $room->room_name }}</strong
                                                >
                                                <small
                                                    class="mt-1 block text-[10px] font-semibold text-slate-700"
                                                    >{{
                                                        $room->equipment->sum(
                                                            "equipment_quantity",
                                                        )
                                                    }} assets · {{
                                                        $room->monitoring[
                                                            "active_reports"
                                                        ]
                                                    }} active</small
                                                >
                                            </span>
                                        </span>
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
                            >
                                Layout editor
                            </p>
                            <h2 class="mt-1 text-2xl font-black">
                                Manage room
                            </h2>
                            <p class="mt-1 text-sm text-white/75">
                                Rename the room, or archive a mistaken room and
                                clear its live details.
                            </p>
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
                        >
                            Selected room
                        </p>
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
                            <span x-text="saving ? 'Saving...' : 'Save name'"></span>
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
                                <p class="mt-1 text-xs leading-5 text-red-700">
                                    This removes the room from the active
                                    blueprint and deletes live equipment and
                                    schedules inside it. Old reports/history are
                                    preserved for audit.
                                </p>
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
        .critical-room {
            animation: criticalPulse 1.8s ease-in-out infinite;
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
                    autoSaveTimer: null,
                    saveQueued: false,
                    roomManager: {
                        open: false,
                        id: null,
                        name: "",
                        originalName: "",
                        type: "",
                        assets: 0,
                        activeReports: 0,
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
                    form: Object.assign({

                        building_name: "",

                        building_logo: null,

                        building_address: null,

                        minFloor: 2,

                        maxFloor: 3,

                        floors: []

                    }, @js($wizardCampus ?? [])),
                    get activeFloorLabel() {
                        return (
                            this.floors.find((f) => f.id === this.activeFloor)?.label ||
                            "No floor selected"
                        );
                    },
                    init() {

                        if (this.form.floors.length === 0) {

                            this.generateFloors();

                        } else {

                            const numbers = this.form.floors.map(floor => {

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

                        this.$nextTick(() => {
                            this.bindDragging();

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

                        this.form.floors.forEach(floor => {

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

                                            equipment: []

                                        }

                                    ]

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
                                @js(route('maintenance.infrastructure.campus.load'))
                            );

                            if (!response.ok) {

                                throw new Error();

                            }

                            const data = await response.json();

                            this.form = Object.assign({

                                building_name: "",

                                building_logo: null,

                                building_address: null,

                                minFloor: 2,

                                maxFloor: 3,

                                floors: []

                            }, data);

                            if (this.form.floors.length > 0) {

                                const numbers = this.form.floors.map(floor =>

                                    parseInt(floor.level)

                                );

                                this.form.minFloor = Math.min(...numbers);

                                this.form.maxFloor = Math.max(...numbers);

                            }

                        } catch (error) {

                            console.error(error);

                            this.toast = "Unable to load campus.";

                            setTimeout(() => this.toast = "", 3000);

                        }

                    },
                    zonePosition(zone) {
                        return (
                            {
                                "Front Wall": [50, 12],
                                "Center Ceiling": [50, 48],
                                "Left Row Pods": [20, 55],
                                "Right Row Pods": [80, 55],
                                "Rear Wall": [50, 86],
                                Storage: [12, 86],
                            }[zone] || [50, 50]
                        );
                    },
                    scheduleAutoSave() {
                        clearTimeout(this.autoSaveTimer);
                        this.autoSaveTimer = setTimeout(() => this.saveLayout(false), 650);
                    },
                    bindDragging() {
                        if (!window.interact) return;
                        interact(".room-block")
                            .on("tap", (event) => {
                                if (!this.editMode) return;
                                event.preventDefault();
                                this.openRoomManager(event.currentTarget);
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
                                    end: () => {
                                        if (this.editMode) this.scheduleAutoSave();
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
                                        min: { width: 100, height: 70 },
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
                                        if (this.editMode) this.scheduleAutoSave();
                                    },
                                },
                            });
                    },
                    async saveLayout(manual = true) {
                        if (this.saving) {
                            this.saveQueued = true;
                            return;
                        }
                        clearTimeout(this.autoSaveTimer);
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
                                    }),
                                },
                            );
                            if (!response.ok) throw new Error();
                            if (manual) {
                                this.editMode = false;
                                this.toast = "Layout saved";
                                setTimeout(() => (this.toast = ""), 2500);
                            }
                        } catch (e) {
                            this.toast = "Could not save the layout";
                            setTimeout(() => (this.toast = ""), 3000);
                        } finally {
                            this.saving = false;
                            if (this.saveQueued) {
                                this.saveQueued = false;
                                this.scheduleAutoSave();
                            }
                        }
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
