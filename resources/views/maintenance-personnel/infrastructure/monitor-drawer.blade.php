<!--<aside class="flex h-[900px] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl">-->
<aside
    class="flex h-[700px] min-h-0 w-[420px] shrink-0 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl"
>
    <!-- Selected room container -->
    <div x-show="selectedRoom === null" class="flex h-full flex-col">
        <div class="bg-slate-950 p-7 text-white">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#005EA6]"
            >
                <i data-lucide="panel-right-open" class="h-6 w-6"></i>
            </div>
            <h2 class="mt-5 text-xl font-extrabold">Room intelligence</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">Select a room block to open its live monitoring workspace.</p>
        </div>
        <div class="flex flex-1 items-center justify-center p-8">
            <div class="text-center">
                <div
                    class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-slate-50 ring-1 ring-slate-100"
                >
                    <i
                        data-lucide="mouse-pointer-click"
                        class="h-10 w-10 text-slate-300"
                    ></i>
                </div>
                <p class="mt-5 text-sm font-bold text-slate-700">Select a room on the map</p>
                <p class="mt-2 text-xs leading-5 text-slate-400">Assets, ticket trends, recurring issues, and schedules will appear here.</p>
            </div>
        </div>
    </div>

    @foreach ($rooms as $room)
        <div
            x-show="selectedRoom === {{ $room->room_id }}"
            x-cloak
            x-data="{

                tab:'overview',

                addEquipmentModal:false,

                editRoomModal:false,

                archiveRoomModal:false,

                archiveReason:'',

                transferAssetsModal:false,

                equipmentRenderKey:0,

                initialEquipmentIds:@js($room->equipment->pluck('equipment_id')->values()),

                liveStackEquipment(){

                    const room = window.infrastructure?.roomCatalog?.find(
                        item => item.id === {{ $room->room_id }}
                    );

                    if(!room || !Array.isArray(room.equipment)){

                        return [];

                    }

                    return room.equipment
                        .filter(eq => !this.initialEquipmentIds.includes(eq.id))
                        .sort((a, b) => Number(b.id || 0) - Number(a.id || 0));

                },

                selectedEquipment:'',

                destinationRoom:'',

                roomSaving:false,

                roomForm:{
                    name:@js($room->room_name),
                    type:@js($room->room_type),
                    status:@js($room->room_status)
                },

                saving:false,

                addForm:{

                    room_id: {{ $room->room_id }},

                    name:'',

                    category:'',

                    quantity:1,

                    tracking:'Bulk',

                    condition:'Good',

                    location:''

                },

                async storeEquipment(){

                    this.saving=true;

                    try{
                    const position = this.zonePosition(
                        this.addForm.location
                    );

                        const response=await fetch(

                            '/maintenance/infrastructure/equipment',

                            {

                                method:'POST',

                                headers:{

                                    'Content-Type':'application/json',

                                    'Accept':'application/json',

                                    'X-CSRF-TOKEN':document.querySelector(
                                        'meta[name=csrf-token]'
                                    ).content

                                },

                                body:JSON.stringify({

                                    equipment_room_id:this.addForm.room_id,

                                    equipment_name:this.addForm.name,

                                    equipment_category_id:this.addForm.category,

                                    equipment_quantity:this.addForm.quantity,

                                    equipment_tracking_mode:this.addForm.tracking,

                                    equipment_condition_status:this.addForm.condition,

                                    equipment_current_location:this.addForm.location,

                                    equipment_position_x:position.x,

                                    equipment_position_y:position.y

                                })

                            }

                        );

                        if(!response.ok){

                            throw new Error();

                        }

                        this.addForm = {

                            room_id:this.addForm.room_id,

                            name:'',

                            category:'',

                            quantity:1,

                            condition:'Good',

                            location:''

                        };

                        this.addEquipmentModal = false;

                        await window.infrastructure.refreshRoomEquipment(this.addForm.room_id);

                        this.equipmentRenderKey += 1;

                    }

                    catch(e){

                        alert('Unable to create equipment.');

                    }

                    finally{

                        this.saving=false;

                    }

                },

                async saveRoom(){

                    this.roomSaving = true;

                    try{

                        const response = await fetch(
                            `/maintenance/infrastructure/rooms/{{ $room->room_id }}`,
                            {

                                method:'PUT',

                                headers:{
                                    'Content-Type':'application/json',
                                    'Accept':'application/json',
                                    'X-CSRF-TOKEN':document.querySelector(
                                        'meta[name=csrf-token]'
                                    ).content
                                },

                                body:JSON.stringify({

                                    room_name:this.roomForm.name,

                                    room_type:this.roomForm.type,

                                    room_status:this.roomForm.status

                                })

                            }
                        );

                        if(!response.ok){

                            throw new Error();

                        }

                        const payload = await response.json();

                        window.infrastructure.applyRoomUpdate(
                            {{ $room->room_id }},
                            {
                                name: payload?.room?.name ?? this.roomForm.name,
                                type: this.roomForm.type,
                                status: this.roomForm.status,
                            }
                        );

                        this.editRoomModal = false;

                        await window.infrastructure.refreshRoomEquipment({{ $room->room_id }});

                    }

                    catch(e){

                        alert('Unable to update room.');

                    }

                    finally{

                        this.roomSaving = false;

                    }

                },

                async archiveRoom(){

                    

                    this.roomSaving=true;

                    try{

                        const response=await fetch(

                            `/maintenance/infrastructure/rooms/{{ $room->room_id }}`,

                            {

                                method:'DELETE',

                                headers:{

                                    'Content-Type':'application/json',

                                    'Accept':'application/json',

                                    'X-CSRF-TOKEN':document.querySelector(
                                        'meta[name=csrf-token]'
                                    ).content

                                },

                                body:JSON.stringify({

                                    reason:this.archiveReason

                                })

                            }

                        );

                        if(!response.ok){

                            throw new Error();

                        }

                        this.archiveRoomModal=false;

                        await window.infrastructure.refreshRoomEquipment({{ $room->room_id }});

                    }

                    catch(e){

                        alert('Unable to archive room.');

                    }

                    finally{

                        this.roomSaving=false;

                    }

                },

                async transferAsset(){

                    if(this.selectedEquipment==='' || this.destinationRoom===''){

                        alert('Please complete all fields.');

                        return;

                    }

                    this.roomSaving=true;

                    try{

                        const response=await fetch(

                            `/maintenance/infrastructure/equipment/${this.selectedEquipment}/transfer`,

                            {

                                method:'POST',

                                headers:{

                                    'Content-Type':'application/json',

                                    'Accept':'application/json',

                                    'X-CSRF-TOKEN':document.querySelector(
                                        'meta[name=csrf-token]'
                                    ).content

                                },

                                body:JSON.stringify({

                                    room_id:this.destinationRoom

                                })

                            }

                        );

                        if(!response.ok){

                            throw new Error();

                        }

                        this.transferAssetsModal=false;

                        await window.infrastructure.refreshRoomEquipment({{ $room->room_id }});

                    }

                    catch(e){

                        alert('Unable to transfer asset.');

                    }

                    finally{

                        this.roomSaving=false;

                    }

                }

            }"
            class="flex h-full flex-col"
        >
            <div class="relative overflow-hidden bg-slate-950 p-6 text-white">
                <div
                    class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-[#005EA6]/30 blur-2xl"
                ></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#FFF200]">{{ $room->floor->building->building_name ?? "STI Ormoc" }} · {{ $room->floor->floor_level }}</p>
                        <h2
                            class="mt-2 text-2xl font-extrabold"
                            x-text="roomForm.name || 'Not Specified'"
                        ></h2>
                        <p
                            class="mt-1 text-sm text-slate-400"
                            x-text="roomForm.type || 'No Room Type'"
                        ></p>
                    </div>
                    <button
                        @click="selectedRoom = null"
                        class="rounded-xl bg-white/10 p-2 hover:bg-white/20"
                        aria-label="Close room details"
                    >
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="relative mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->equipment->sum(
                                "equipment_quantity",
                            )
                        }}</b
                        ><span class="text-[10px] text-slate-400">{{
                            $room->equipment->sum(
                                "equipment_quantity",
                            ) > 1
                                ? "Equipments"
                                : "Equipment"
                        }}</span>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->monitoring[
                                "active_reports"
                            ]
                        }}</b
                        ><span class="text-[10px] text-slate-400">{{
                            $room->monitoring["active_reports"] > 1
                                ? "Active Reports"
                                : "Active Report"
                        }}</span>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->equipment
                                ->where("equipment_condition_status", "Good")
                                ->sum("equipment_quantity")
                        }}</b
                        ><span class="text-[10px] text-slate-400">{{
                            $room->equipment
                                ->where("equipment_condition_status", "Good")
                                ->sum("equipment_quantity") > 1
                                ? "Good Conditions"
                                : "Good Condition"
                        }}</span>
                    </div>
                </div>
            </div>

            <div class="border-b border-slate-100 p-2">
                <div
                    class="grid grid-cols-5 gap-1 rounded-xl bg-slate-100 p-1 text-[11px] font-bold"
                >
                    <button
                        @click="tab = 'overview'"
                        :class="tab === 'overview'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Overview
                    </button>

                    <button
                        @click="tab = 'equipment'"
                        :class="tab === 'equipment'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Equipment
                    </button>

                    <button
                        @click="tab = 'analytics'"
                        :class="tab === 'analytics'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Analytics
                    </button>

                    <button
                        @click="tab = 'schedule'"
                        :class="tab === 'schedule'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Schedule
                    </button>

                    <button
                        @click="tab = 'history'"
                        :class="tab === 'history'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        History
                    </button>
                </div>
            </div>

            <div
                class="relative min-h-0 flex-1 overflow-y-auto overflow-x-hidden p-5"
            >
                <div x-show="tab === 'overview'" x-cloak class="space-y-5">
                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3
                            class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                        >
                            Room Summary
                        </h3>

                        <dl class="mt-4 space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Room</dt>

                                <dd
                                    class="font-semibold text-sm"
                                    :class="roomForm.name ? 'text-black' : 'text-gray-400'"
                                    x-text="roomForm.name || 'Not Specified'"
                                ></dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Floor</dt>

                                <dd
                                    @class ([
                                        "font-semibold text-sm text-black" => $room->floor->floor_level,
                                        "text-sm text-gray-400" => !$room->floor->floor_level
                                    ])
                                >
                                    {{
                                        $room->floor->floor_level ?:
                                            "Not Specified"
                                    }}
                                </dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Type</dt>

                                <dd
                                    class="font-semibold text-sm"
                                    :class="roomForm.type ? 'text-black' : 'text-gray-400'"
                                    x-text="roomForm.type || 'No Room Type'"
                                ></dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-sm text-slate-500">Status</dt>

                                <dd>
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="
                                            roomForm.status === 'Critical'
                                                ? 'bg-red-100 text-red-700'
                                                : roomForm.status === 'Maintenance Needed'
                                                    ? 'bg-amber-100 text-amber-700'
                                                    : 'bg-emerald-100 text-emerald-700'
                                        "
                                        x-text="roomForm.status || 'Normal'"
                                    ></span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-blue-50 p-4">
                            <p class="text-xs font-bold uppercase text-blue-500">
                                {{
                                    $room->monitoring["equipment_quantity"] >
                                    1
                                        ? "Equipments"
                                        : "Equipment"
                                }}
                            </p>

                            <h2 class="mt-2 text-3xl font-black text-blue-700">
                                {{
                                    $room->monitoring[
                                        "equipment_quantity"
                                    ]
                                }}
                            </h2>
                        </div>

                        <div class="rounded-2xl bg-yellow-50 p-4">
                            <p class="text-xs font-bold uppercase text-yellow-500">
                                {{
                                    $room->monitoring["active_reports"] > 1
                                        ? "Reports"
                                        : "Report"
                                }}
                            </p>

                            <h2
                                class="mt-2 text-3xl font-black text-yellow-700"
                            >
                                {{
                                    $room->monitoring[
                                        "active_reports"
                                    ]
                                }}
                            </h2>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">
                        <h3
                            class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                        >
                            Equipment Condition
                        </h3>

                        <div class="mt-5 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">
                                    Good
                                </span>

                                <span class="text-sm font-semibold text-black">
                                    {{
                                        $room->monitoring[
                                            "equipment_good"
                                        ]
                                    }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">
                                    Under Maintenance
                                </span>

                                <span class="text-sm font-semibold text-black">
                                    {{
                                        $room->monitoring[
                                            "equipment_maintenance"
                                        ]
                                    }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">
                                    Damaged
                                </span>

                                <span class="text-sm font-semibold text-black">
                                    {{
                                        $room->monitoring[
                                            "equipment_damaged"
                                        ]
                                    }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">
                                    Disposed
                                </span>

                                <span class="text-sm font-semibold text-black">
                                    {{
                                        $room->monitoring[
                                            "equipment_disposed"
                                        ]
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- QUICK ACTIONS --}}

                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h3
                                class="text-xs font-extrabold uppercase tracking-[.2em] text-slate-400"
                            >
                                Quick Actions
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-5">
                            <button
                                type="button"
                                @click="addEquipmentModal = true"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-[#005EA6] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-xl bg-blue-100 p-2 text-[#005EA6]"
                                    >
                                        <i
                                            data-lucide="plus"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>

                                    <div>
                                        <h4
                                            class="text-sm font-bold text-black"
                                        >
                                            Add Equipment
                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">Provision new equipment into this room.</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="editRoomModal = true"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-amber-500 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-xl bg-amber-100 p-2 text-amber-600"
                                    >
                                        <i
                                            data-lucide="pencil"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>

                                    <div>
                                        <h4
                                            class="text-sm font-bold text-black"
                                        >
                                            Edit Room
                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">Modify room information and layout.</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="transferAssetsModal = true"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-emerald-500 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-xl bg-emerald-100 p-2 text-emerald-600"
                                    >
                                        <i
                                            data-lucide="arrow-right-left"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>

                                    <div>
                                        <h4
                                            class="text-sm font-bold text-black"
                                        >
                                            Transfer Assets
                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">Move equipment to another room.</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="archiveRoomModal = true"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-red-500 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="rounded-xl bg-red-100 p-2 text-red-600"
                                    >
                                        <i
                                            data-lucide="archive"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>

                                    <div>
                                        <h4
                                            class="text-sm font-bold text-black"
                                        >
                                            Archive Room
                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">Archive this room and keep records.</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div
                            class="border-t border-slate-100 bg-slate-50 px-5 py-3"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-semibold text-slate-500"
                                >
                                    Layout Editor
                                </span>

                                <span
                                    class="rounded-full bg-[#005EA6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-[#005EA6]"
                                >
                                    Equipment CRUD
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================= -->
                <!-- Premium Add Equipment Modal -->
                <!-- Replace your current Add Equipment Modal -->
                <!-- ========================================= -->

                <template x-if="addEquipmentModal">
                <div
                    x-transition.opacity
                    @click.self="
                        addEquipmentModal=false;

                        addForm={
                            room_id:{{ $room->room_id }},
                            name:'',
                            category:'',
                            quantity:1,
                            condition:'Good',
                            location:''
                        }
                    "
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-6 backdrop-blur-sm"
                >
                    <div
                        class="w-full max-w-xl overflow-hidden rounded-[28px] bg-white shadow-2xl"
                    >
                        <!-- Header -->

                        <div
                            class="relative overflow-hidden bg-gradient-to-r from-[#005EA6] to-[#0A84FF] px-7 py-6 text-white"
                        >
                            <div
                                class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10 blur-2xl"
                            ></div>

                            <div class="relative flex items-center gap-4">
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur"
                                >
                                    <i
                                        data-lucide="package-plus"
                                        class="h-7 w-7"
                                    ></i>
                                </div>

                                <div>
                                    <h2 class="text-2xl font-extrabold">
                                        Add Equipment
                                    </h2>

                                    <p class="mt-1 text-sm text-blue-100">Register a new asset for this room.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Body -->

                        <div class="space-y-6 p-7">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Equipment Name
                                </label>

                                <input
                                    x-model="addForm.name"
                                    type="text"
                                    placeholder="Example: Split Type Air Conditioner"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition focus:border-[#005EA6] focus:bg-white focus:outline-none"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Category
                                    </label>

                                    <select
                                        x-model="addForm.category"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition focus:border-[#005EA6] focus:bg-white focus:outline-none"
                                    >
                                        <option value="">
                                            Select Category
                                        </option>

                                        @foreach ($categories as $category)
                                            <option
                                                value="{{ $category->equipment_category_id }}"
                                            >
                                                {{ $category->equipment_category_name }}
                                            </option>

                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Quantity
                                    </label>

                                    <input
                                        x-model="addForm.quantity"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition focus:border-[#005EA6] focus:bg-white focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold"
                                    >
                                        Tracking Mode
                                    </label>

                                    <select
                                        x-model="addForm.tracking"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                                    >
                                        <option value="Bulk">Bulk</option>

                                        <option value="Individual">
                                            Individual
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Condition
                                    </label>

                                    <select
                                        x-model="addForm.condition"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition focus:border-[#005EA6] focus:bg-white focus:outline-none"
                                    >
                                        <option>Good</option>
                                        <option>Under Maintenance</option>
                                        <option>Damaged</option>
                                        <option>Disposed</option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Placement
                                    </label>
                                    <select
                                        x-model="addForm.location"
                                        @change="updateEquipmentPlacement()"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition focus:border-[#005EA6] focus:bg-white focus:outline-none"
                                    >
                                        <option
                                            value=""
                                            disabled
                                            selected
                                            hidden
                                        >
                                            Select placement zone...
                                        </option>
                                        <option value="Front Wall">
                                            Front Wall
                                        </option>
                                        <option value="Center Ceiling">
                                            Center Ceiling
                                        </option>
                                        <option value="Left Row Pods">
                                            Left Row Pods
                                        </option>
                                        <option value="Right Row Pods">
                                            Right Row Pods
                                        </option>
                                        <option value="Rear Wall">
                                            Rear Wall
                                        </option>
                                        <option value="Storage">Storage</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Information Card -->

                            <div
                                class="rounded-2xl border border-blue-100 bg-blue-50 p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-xl bg-[#005EA6] p-2 text-white"
                                    >
                                        <i
                                            data-lucide="info"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>

                                    <div>
                                        <h4 class="font-bold text-slate-800">
                                            Equipment Registration
                                        </h4>

                                        <p class="mt-1 text-sm text-slate-600">The equipment will immediately appear in this room's inventory after saving.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->

                        <div
                            class="flex gap-4 border-t border-slate-100 bg-slate-50 px-7 py-5"
                        >
                            <button
                                @click="
                                    addEquipmentModal=false;

                                    addForm={
                                        room_id:{{ $room->room_id }},
                                        name:'',
                                        category:'',
                                        quantity:1,
                                        condition:'Good',
                                        location:''
                                    }
                                "
                                class="flex-1 rounded-2xl border border-slate-300 bg-white py-3 font-semibold text-slate-700 transition hover:bg-slate-100"
                            >
                                Cancel
                            </button>

                            <button
                                @click="storeEquipment()"
                                :disabled="saving"
                                class="flex-1 rounded-2xl bg-[#005EA6] py-3 font-semibold text-white transition hover:bg-[#004B86] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span
                                    x-text="
                                        saving
                                            ? 'Saving Equipment...'
                                            : 'Add Equipment'
                                    "
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
                </template>

                <!-- ============================== -->
                <!-- Edit Room Modal -->
                <!-- Place AFTER Add Equipment Modal -->
                <!-- ============================== -->

                <template x-if="editRoomModal">
                <div
                    x-transition
                    @click.self="editRoomModal = false"
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"
                >
                    <div
                        class="w-full max-w-lg rounded-3xl bg-white p-6"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold">Edit Room</h2>

                                <p class="mt-1 text-sm text-slate-500">Update this room's information.</p>
                            </div>

                            <button
                                @click="editRoomModal = false"
                                class="rounded-lg p-2 hover:bg-slate-100"
                            >
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>

                        <div class="mt-6 space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-semibold">
                                    Room Name
                                </label>

                                <input
                                    x-model="roomForm.name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#005EA6] focus:outline-none"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold">
                                    Room Type
                                </label>

                                <select
                                    x-model="roomForm.type"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#005EA6] focus:outline-none"
                                >
                                    <option value="Lecture Room">
                                        Lecture Room
                                    </option>
                                    <option value="Computer Laboratory">
                                        Computer Laboratory
                                    </option>
                                    <option value="HM Room">
                                        HM Room / Bar
                                    </option>
                                    <option value="Hotel Room Simulation">
                                        Hotel Room Simulation
                                    </option>

                                    <option value="Faculty Room">
                                        Faculty Room
                                    </option>
                                    <option value="Office">Office</option>
                                    <option value="Library">Library</option>
                                    <option value="School Clinic">
                                        School Clinic
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold">
                                    Room Status
                                </label>

                                <select
                                    x-model="roomForm.status"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#005EA6] focus:outline-none"
                                >
                                    <option>Normal</option>

                                    <option>Maintenance Needed</option>

                                    <option>Critical</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                            <button
                                @click="editRoomModal = false"
                                class="w-full rounded-xl border border-slate-300 py-2.5"
                            >
                                Cancel
                            </button>

                            <button
                                @click="saveRoom()"
                                :disabled="roomSaving"
                                class="w-full rounded-xl bg-[#005EA6] py-2.5 font-semibold text-white hover:bg-[#004b86] disabled:opacity-60"
                            >
                                <span
                                    x-text="
                                        roomSaving
                                            ? 'Saving...'
                                            : 'Save Changes'
                                    "
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
                </template>

                <!-- ===================================== -->
                <!-- Archive Room Confirmation Modal -->
                <!-- Place AFTER Edit Room Modal -->
                <!-- ===================================== -->

                <template x-if="archiveRoomModal">
                <div
                    x-transition
                    @click.self="archiveRoomModal = false"
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"
                >
                    <div
                        class="w-full max-w-lg rounded-3xl bg-white p-6"
                    >
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-red-600">
                                Archive Room
                            </h2>

                            <button
                                @click="archiveRoomModal = false"
                                class="rounded-lg p-2 hover:bg-slate-100"
                            >
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>

                        <p class="mt-4 text-sm text-slate-600">You are about to archive this room. Live equipment and schedules will be removed. Historical reports will remain available.</p>

                        <div class="mt-6 rounded-xl bg-slate-100 p-4">
                            <div class="text-xs uppercase text-slate-500">
                                Room
                            </div>

                            <div
                                class="mt-1 text-lg font-bold"
                                x-text="roomForm.name"
                            ></div>
                        </div>

                        <div class="mt-6">
                            <label class="mb-2 block text-sm font-semibold">
                                Reason
                            </label>

                            <textarea
                                x-model="archiveReason"
                                rows="4"
                                placeholder="Optional reason..."
                                class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            ></textarea>
                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                            <button
                                @click="archiveRoomModal = false"
                                class="rounded-xl border border-slate-300 px-5 py-2"
                            >
                                Cancel
                            </button>

                            <button
                                @click="archiveRoom()"
                                :disabled="roomSaving"
                                class="rounded-xl bg-red-600 px-6 py-2 text-white hover:bg-red-700"
                            >
                                <span
                                    x-text="
                                        roomSaving
                                            ? 'Archiving...'
                                            : 'Archive Room'
                                    "
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
                </template>

                <template x-if="transferAssetsModal">
                <div
                    x-transition
                    @click.self="transferAssetsModal = false"
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"
                >
                    <div
                        class="w-full max-w-lg rounded-3xl bg-white p-6"
                    >
                        <h2 class="text-2xl font-bold">Transfer Asset</h2>

                        <p class="mt-2 text-sm text-slate-500">Select the equipment and destination room.</p>

                        <div class="mt-6">
                            <label class="mb-2 block text-sm font-semibold">
                                Equipment
                            </label>

                            <select
                                x-model="selectedEquipment"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            >
                                <option value="">Select Equipment</option>

                                @foreach ($room->equipment as $equipment)
                                    <option
                                        value="{{ $equipment->equipment_id }}"
                                    >
                                        {{ $equipment->equipment_name }}
                                    </option>

                                @endforeach
                            </select>
                        </div>

                        <div class="mt-5">
                            <label class="mb-2 block text-sm font-semibold">
                                Destination Room
                            </label>

                            <select
                                x-model="destinationRoom"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            >
                                <option value="">Select Room</option>

                                @foreach ($rooms as $destination)
                                    @if ($destination->room_id != $room->room_id)
                                        <option
                                            value="{{ $destination->room_id }}"
                                        >
                                            {{ $destination->room_name }}
                                        </option>

                                    @endif

                                @endforeach
                            </select>
                        </div>

                        <div class="mt-8 flex justify-end gap-3">
                            <button
                                @click="transferAssetsModal = false"
                                class="rounded-xl border border-slate-300 px-5 py-2"
                            >
                                Cancel
                            </button>

                            <button
                                @click="transferAsset()"
                                class="rounded-xl bg-[#005EA6] px-6 py-2 text-white"
                            >
                                Transfer
                            </button>
                        </div>
                    </div>
                </div>
                </template>

                <div
                    x-show="tab === 'equipment'"
                    x-data="{
                        search: '',

                        filter: 'all',
                    }"
                    class="space-y-4"
                >
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-4"
                    >
                        <div class="flex gap-3">
                            <div class="relative flex-1">
                                <i
                                    data-lucide="search"
                                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                ></i>

                                <input
                                    x-model="search"
                                    type="text"
                                    placeholder="Search equipment..."
                                    class="w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-3 text-sm focus:border-[#005EA6] focus:outline-none"
                                />
                            </div>

                            <select
                                x-model="filter"
                                class="rounded-xl border border-slate-200 px-3 text-sm"
                            >
                                <option value="all">All</option>

                                <option value="good">Good</option>

                                <option value="maintenance">Maintenance</option>

                                <option value="damaged">Damaged</option>

                                <option value="disposed">Disposed</option>
                            </select>
                        </div>
                    </div>

                    <template
                        x-for="item in (equipmentRenderKey, liveStackEquipment())"
                        :key="'live-stack-' + item.id"
                    >
                        <article
                            x-show="
                                (search === '' || (item.name || '').toLowerCase().includes(search.toLowerCase()))
                                &&
                                (
                                    filter === 'all'
                                    || (filter === 'good' && (item.condition || '') === 'Good')
                                    || (filter === 'maintenance' && (item.condition || '') === 'Under Maintenance')
                                    || (filter === 'damaged' && (item.condition || '') === 'Damaged')
                                    || (filter === 'disposed' && (item.condition || '') === 'Disposed')
                                )
                            "
                            class="relative overflow-visible rounded-2xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-300"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                                        :class="
                                            (item.condition || '') === 'Good'
                                                ? 'bg-emerald-500'
                                                : (item.condition || '') === 'Under Maintenance'
                                                    ? 'bg-amber-400'
                                                    : (item.condition || '') === 'Damaged'
                                                        ? 'bg-red-600'
                                                        : (item.condition || '') === 'Disposed'
                                                            ? 'bg-zinc-500'
                                                            : 'bg-gray-300'
                                        "
                                    ></span>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-bold text-slate-800" x-text="item.name"></h3>
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            <span x-text="item.condition || 'Unknown'"></span>
                                            <span> · </span>
                                            <span x-text="item.location || item.placement_zone || 'No location'"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </template>

                    @forelse ($room->equipment->sortByDesc('equipment_id') as $item)
                        @php
                            $healthy = $item->equipment_condition_status === "Good";
                            $maintenance = $item->equipment_condition_status === "Under Maintenance";
                            $damaged = $item->equipment_condition_status === "Damaged";
                            $disposed = $item->equipment_condition_status === "Disposed";
                        @endphp
                        <article
                            x-show="

                                    (

                                        search === ''

                                        ||

                                        '{{ strtolower($item->equipment_name) }}'

                                        .includes(search.toLowerCase())

                                    )

                                    &&

                                    (

                                        filter === 'all'

                                        ||

                                        (

                                            filter === 'good'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Good'

                                        )

                                        ||

                                        (

                                            filter === 'maintenance'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Under Maintenance'

                                        )

                                        ||

                                        (

                                            filter === 'damaged'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Damaged'

                                        )

                                        ||

                                        (
                                            filter === 'disposed'

                                            &&

                                            '{{ $item->equipment_condition_status }}'

                                            === 'Disposed'
                                        )

                                    )

                            "
                            class="relative overflow-visible rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-slate-200 hover:bg-white hover:shadow-md"
                        >
                            <!-- Equipment Alpine Component -->
                            <div
                                x-data="equipmentCard(

                                    {{ $item->equipment_id }},

                                    {{ $room->room_id }}

                                )"
                            >
                                <!-- Equipment Header -->

                                <!-- ========================= -->
                                <!-- Equipment Header -->
                                <!-- Replace your current header + 4-column grid -->
                                <!-- ========================= -->

                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="flex min-w-0 gap-3">
                                        <span
                                            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full
                                            {{
                                                $healthy
                                                    ? 'bg-emerald-500'
                                                    : ($maintenance
                                                        ? 'bg-amber-400'
                                                        : ($damaged
                                                            ? 'bg-red-600'
                                                            : ($disposed
                                                                ? 'bg-zinc-500'
                                                                : 'bg-gray-300')))
                                            }}"
                                        ></span>

                                        <div class="min-w-0">
                                            <h3
                                                class="truncate text-sm font-bold text-slate-800"
                                            >
                                                {{ $item->equipment_name }}
                                            </h3>

                                            <p class="mt-1 text-[11px] text-slate-500">
                                                {{
                                                    $item->equipment_asset_tag ?:
                                                        "No Asset Tag"
                                                }}

                                                ·

                                                @if ($item->equipment_tracking_mode == "Bulk")
                                                    Qty {{ $item->equipment_quantity }}

                                                @else
                                                    Individual Asset
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full px-2 py-1 text-[9px] font-extrabold
                                            {{
                                                $healthy
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : ($maintenance
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : ($damaged
                                                            ? 'bg-red-100 text-red-700'
                                                            : ($disposed
                                                                ? 'bg-slate-200 text-slate-700'
                                                                : 'bg-gray-100 text-gray-700')))
                                            }}"
                                        >
                                            {{ $item->equipment_condition_status }}
                                        </span>

                                        <div
                                            x-data="{ menu: false }"
                                            :class="menu
                                                ? 'relative z-40'
                                                : 'relative z-10'"
                                            class="rounded-xl border border-slate-100 bg-white p-1 shadow-sm"
                                        >
                                            <div class="relative z-30">
                                                <button
                                                    @click="menu = !menu"
                                                    class="rounded-lg p-2 hover:bg-slate-100"
                                                >
                                                    <i
                                                        data-lucide="ellipsis-vertical"
                                                        class="h-4 w-4"
                                                    ></i>
                                                </button>

                                                <!-- your dropdown stays here -->

                                                <div
                                                    x-show="menu"
                                                    @click.outside="
                                                        menu = false
                                                    "
                                                    x-transition
                                                    class="absolute -right-1 top-full z-50 mt-2 w-48 rounded-lg border border-dashed border-slate-200 bg-white shadow-2xl"
                                                >
                                                    <button
                                                        @click="
                                                            panel =
                                                                panel ===
                                                                'details'
                                                                    ? ''
                                                                    : 'details';
                                                            menu = false;
                                                        "
                                                        class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm hover:bg-slate-50"
                                                    >
                                                        <i
                                                            data-lucide="eye"
                                                            class="h-4 w-4"
                                                        ></i>
                                                        View Details
                                                    </button>

                                                    <button
                                                        @click="
                                                            panel = 'edit';
                                                            menu = false;
                                                        "
                                                        class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm hover:bg-slate-50"
                                                    >
                                                        <i
                                                            data-lucide="square-pen"
                                                            class="h-4 w-4"
                                                        ></i>

                                                        Edit Equipment
                                                    </button>

                                                    <button
                                                        @click="
                                                            transferRoom = '';
                                                            panel = 'transfer';
                                                            menu = false;
                                                        "
                                                        class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm hover:bg-slate-50"
                                                    >
                                                        <i
                                                            data-lucide="arrow-right-left"
                                                            class="h-4 w-4"
                                                        ></i>
                                                        Transfer
                                                    </button>

                                                    <button
                                                        @click="
                                                            archiveReason = '';
                                                            panel = 'archive';
                                                            menu = false;
                                                        "
                                                        class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50"
                                                    >
                                                        <i
                                                            data-lucide="archive"
                                                            class="h-4 w-4"
                                                        ></i>

                                                        Archive
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-center gap-2 border-t border-slate-200 pt-3 text-[11px] text-slate-500"
                                >
                                    <i
                                        data-lucide="map-pin"
                                        class="h-3.5 w-3.5 text-[#005EA6]"
                                    ></i>

                                    <span>
                                        {{
                                            $item->equipment_placement_zone ?:
                                                ($item->equipment_current_location ?:
                                                    "Placement not plotted")
                                        }}
                                    </span>
                                </div>

                                <div
                                    x-show="panel === 'details'"
                                    x-collapse
                                    class="mt-6 border-t border-slate-200 pt-6"
                                >
                                    <h4
                                        class="text-xs font-extrabold uppercase tracking-[.2em] text-slate-400"
                                    >
                                        Equipment Information
                                    </h4>

                                    <div
                                        class="mt-4 grid grid-cols-2 gap-x-6 gap-y-5"
                                    >
                                        <div>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Asset Tag</p>

                                            <p class="mt-1 font-semibold">
                                                {{
                                                    $item->equipment_asset_tag ?:
                                                        "Not Assigned"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Serial Number</p>

                                            <p class="mt-1 font-semibold">
                                                {{
                                                    $item->equipment_serial_number ??
                                                        "Unavailable"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Warranty</p>

                                            <p class="mt-1 font-semibold">
                                                {{
                                                    $item->equipment_warranty_expiration ??
                                                        "Unknown"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Supplier</p>

                                            <p class="mt-1 font-semibold">
                                                {{
                                                    $item->equipment_supplier ??
                                                        "Not Assigned"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Purchase Date</p>

                                            <p class="mt-1 font-semibold">
                                                {{
                                                    $item->equipment_purchase_date ??
                                                        "Unknown"
                                                }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Assigned Technician</p>

                                            <p class="mt-1 font-semibold">
                                                {{
                                                    $item->equipment_assigned_to ??
                                                        "Unassigned"
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-6 rounded-2xl bg-slate-50 p-4"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div>
                                                <p class="text-xs font-bold text-slate-700">QR Code</p>

                                                <p class="text-[11px] text-slate-400">Coming in Asset Management Phase</p>
                                            </div>

                                            <div
                                                class="flex h-20 w-20 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white"
                                            >
                                                <i
                                                    data-lucide="qr-code"
                                                    class="h-8 w-8 text-slate-300"
                                                ></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    x-show="panel === 'edit'"
                                    x-transition
                                    class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-5"
                                >
                                    <h4
                                        class="text-xs font-extrabold uppercase tracking-[.2em] text-slate-500"
                                    >
                                        Edit Equipment
                                    </h4>

                                    <div class="mt-5 space-y-4">
                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-semibold text-slate-600"
                                            >
                                                Equipment Name
                                            </label>

                                            <input
                                                x-model="form.name"
                                                type="text"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                            />
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-semibold text-slate-600"
                                            >
                                                Category
                                            </label>

                                            <select
                                                x-model="form.category"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                            >
                                                <option value="">
                                                    Select Category
                                                </option>

                                                @foreach ($categories as $category)
                                                    <option
                                                        value="{{ $category->equipment_category_id }}"
                                                    >
                                                        {{ $category->equipment_category_name }}
                                                    </option>

                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-semibold text-slate-600"
                                            >
                                                Placement
                                            </label>
                                            <select
                                                x-model="form.location"
                                                @change="updateEquipmentPlacement()"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"
                                            >
                                                <option
                                                    value=""
                                                    disabled
                                                    selected
                                                >
                                                    Select equipment position...
                                                </option>
                                                <option value="Front Wall">
                                                    Front Wall
                                                </option>
                                                <option value="Center Ceiling">
                                                    Center Ceiling
                                                </option>
                                                <option value="Left Row Pods">
                                                    Left Row Pods
                                                </option>
                                                <option value="Right Row Pods">
                                                    Right Row Pods
                                                </option>
                                                <option value="Rear Wall">
                                                    Rear Wall
                                                </option>
                                                <option value="Storage">
                                                    Storage
                                                </option>
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-semibold text-slate-600"
                                                >
                                                    Quantity
                                                </label>

                                                @if ($item->equipment_tracking_mode == "Bulk")
                                                    <input
                                                        x-model="form.quantity"
                                                        type="number"
                                                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                                    />

                                                @endif
                                            </div>

                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-semibold text-slate-600"
                                                >
                                                    Condition
                                                </label>

                                                <select
                                                    x-model="form.condition"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                                >
                                                    <option>Good</option>

                                                    <option>
                                                        Under Maintenance
                                                    </option>

                                                    <option>Damaged</option>

                                                    <option>Disposed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-3">
                                            <button
                                                @click="panel = ''"
                                                class="rounded-xl border border-slate-300 px-4 py-2"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                @click="saveEquipment()"
                                                :disabled="saving"
                                                class="rounded-xl bg-[#005EA6] px-5 py-2 font-semibold text-white hover:bg-[#004b86] disabled:cursor-not-allowed disabled:opacity-60"
                                            >
                                                <span
                                                    x-text="
                                                        saving
                                                            ? 'Saving...'
                                                            : 'Save Changes'
                                                    "
                                                ></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    x-show="panel === 'transfer'"
                                    x-collapse
                                    class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                                >
                                    <h4
                                        class="text-xs font-extrabold uppercase tracking-[.2em] text-slate-500"
                                    >
                                        Transfer Equipment
                                    </h4>

                                    <select
                                        x-model="transferRoom"
                                        class="mt-5 w-full rounded-xl border border-slate-300 p-3"
                                    >
                                        <option value="">Select Room</option>

                                        @foreach ($rooms as $destination)
                                            @if ($destination->room_id != $room->room_id)
                                                <option
                                                    value="{{ $destination->room_id }}"
                                                >
                                                    {{ $destination->room_name }}
                                                </option>

                                            @endif

                                        @endforeach
                                    </select>

                                    <div class="mt-5 flex justify-end gap-3">
                                        <button
                                            @click="panel = ''"
                                            class="rounded-xl border px-5 py-2"
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            @click="transferEquipment()"
                                            class="rounded-xl bg-[#005EA6] px-5 py-2 text-white"
                                        >
                                            Transfer
                                        </button>
                                    </div>
                                </div>

                                <div
                                    x-show="panel === 'archive'"
                                    x-collapse
                                    class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-5"
                                >
                                    <h4
                                        class="text-xs font-extrabold uppercase tracking-[.2em] text-red-600"
                                    >
                                        Archive Equipment
                                    </h4>

                                    <textarea
                                        x-model="archiveReason"
                                        rows="4"
                                        placeholder="Reason"
                                        class="mt-5 w-full rounded-xl border border-slate-300 p-3"
                                    ></textarea>

                                    <div class="mt-5 flex justify-end gap-3">
                                        <button
                                            @click="panel = ''"
                                            class="rounded-xl border px-5 py-2"
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            @click="archiveEquipment()"
                                            class="rounded-xl bg-red-600 px-5 py-2 text-white"
                                        >
                                            Archive
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div
                            class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400"
                        >
                            No equipment provisioned in this room.
                        </div>
                    @endforelse
                </div>

                <div x-show="tab === 'analytics'" x-cloak>
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Report volume
                    </h3>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ([
                                "Today" => "today_reports",
                                "Weekly" => "week_reports",
                                "Monthly" => "month_reports"
                            ]
                            as $label => $key)
                            <div
                                class="rounded-2xl border border-slate-100 p-3 text-center"
                            >
                                <strong class="block text-2xl text-slate-900">{{
                                    $room->monitoring[
                                        $key
                                    ]
                                }}</strong
                                ><span
                                    class="text-[10px] font-semibold text-slate-400"
                                    >{{ $label }}</span
                                >
                            </div>
                        @endforeach
                    </div>
                    <h3
                        class="mt-6 text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Frequent problems
                    </h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($room->monitoring["frequent_problems"] as $problem)
                            <div
                                class="flex items-start justify-between gap-3 rounded-xl bg-red-50 p-3 text-xs"
                            >
                                <span
                                    class="leading-5 text-slate-700"
                                    >{{ $problem->report_problem_description }}</span
                                ><b
                                    class="rounded-md bg-white px-2 py-1 text-red-600"
                                    >{{ $problem->occurrences }}×</b
                                >
                            </div>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-xs text-slate-400">No recurring problems recorded.</p>
                        @endforelse
                    </div>
                </div>

                <div x-show="tab === 'schedule'" x-cloak>
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Upcoming maintenance
                    </h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($room->monitoring["schedules"] as $schedule)
                            <article
                                class="flex gap-3 rounded-2xl border border-slate-100 p-4"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-blue-50 text-[#005EA6]"
                                >
                                    <b class="text-sm">{{
                                        \Carbon\Carbon::parse(
                                            $schedule->maintenance_schedule_next_date,
                                        )->format("d")
                                    }}</b
                                    ><span
                                        class="text-[8px] font-bold uppercase"
                                        >{{
                                            \Carbon\Carbon::parse(
                                                $schedule->maintenance_schedule_next_date,
                                            )->format("M")
                                        }}</span
                                    >
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-bold text-slate-800"
                                    >
                                        {{ $schedule->maintenance_schedule_title }}
                                    </h4>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $schedule->equipment_name }} · {{ $schedule->maintenance_schedule_status }}</p>
                                </div>
                            </article>
                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-slate-200 p-8 text-center"
                            >
                                <i
                                    data-lucide="calendar-check"
                                    class="mx-auto h-8 w-8 text-slate-300"
                                ></i>
                                <p class="mt-3 text-sm text-slate-400">No active schedule.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div x-show="tab === 'history'" x-cloak>
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Room Activity
                    </h3>

                    <div class="mt-4 space-y-4">
                        @forelse ($room->monitoring["history"] as $history)
                            <div
                                class="relative flex gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                            >
                                <div class="mt-1 flex flex-col items-center">
                                    <div
                                        class="h-3 w-3 rounded-full bg-[#005EA6]"
                                    ></div>

                                    <div
                                        class="mt-1 h-full w-px bg-slate-200"
                                    ></div>
                                </div>

                                <div class="flex-1">
                                    <p class="font-semibold">
                                        {{ $history->activity_title }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $history->activity_description }}
                                    </p>

                                    <p class="mt-2 text-[11px] text-slate-400">
                                        {{
                                            \Carbon\Carbon::parse(
                                                $history->created_at,
                                            )->format("M d, Y h:i A")
                                        }}
                                    </p>
                                </div>
                            </div>

                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-slate-200 p-10 text-center"
                            >
                                <i
                                    data-lucide="history"
                                    class="mx-auto h-10 w-10 text-slate-300"
                                ></i>

                                <p class="mt-4 text-sm text-slate-500">No room activity yet.</p>
                            </div>

                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</aside>

<script>
    document.addEventListener('alpine:init', () => {

        Alpine.data('equipmentCard', (

            equipmentId,

            roomId

        ) => ({
            roomId: roomId,

            menu:false,
            saving:false,

            panel:'',

            transferRoom:'',
            archiveReason:'',

            equipmentId: equipmentId,

            form: null,

            equipment(){

                const layout = window.infrastructure.roomLayout;

                if(

                    layout.open &&

                    layout.roomId === this.roomId

                ){

                    return layout.equipment.find(

                        equipment => equipment.id === this.equipmentId

                    );

                }

                const room = window.infrastructure.roomCatalog.find(

                    room => room.id === this.roomId

                );

                if(!room){

                    return null;

                }

                return room.equipment.find(

                    equipment => equipment.id === this.equipmentId

                );

            },

            // =====================================
            // Keep edit form synced with live equipment
            // Place BELOW equipment()
            // =====================================

            

            
            
            updateEquipmentPlacement(){

                if(!this.form){

                    return;

                }

                const [x,y] = window.infrastructure.zonePosition(

                    this.form.location

                );

                this.form.x = x;

                this.form.y = y;

                this.form.placement_zone = this.form.location;

            },

            

            

            // inside Alpine.data('equipmentCard')

            saveEquipment(){

                this.saving = true;

                const equipment = this.equipment();

                fetch(`/maintenance/infrastructure/equipment/${this.equipmentId}`,{

                    method:'PUT',

                    headers:{
                        'Content-Type':'application/json',
                        'Accept':'application/json',
                        'X-CSRF-TOKEN':document
                            .querySelector('meta[name="csrf-token"]')
                            .content
                    },

                    

                    body:JSON.stringify({

                        equipment_name:equipment.name,

                        equipment_category_id:equipment.category,

                        equipment_quantity:equipment.quantity,

                        equipment_condition_status:equipment.condition,

                        equipment_current_location:equipment.location,

                        equipment_placement_zone:equipment.location,

                        equipment_position_x:equipment.x,

                        equipment_position_y:equipment.y

                    })

                })

                .then(res=>{

                    if(!res.ok){

                        throw new Error();

                    }

                    return res.json();

                })

                .then(async()=>{

                    

                    this.panel='';

                    this.saving=false;

                    await window.infrastructure.refreshRoomEquipment(this.roomId);

                })

                .catch(()=>{

                    this.saving=false;

                    alert('Unable to save equipment.');

                });

            },

            transferEquipment(){

                if(this.transferRoom===''){

                    alert('Please select a destination room.');

                    return;

                }

                fetch(`/maintenance/infrastructure/equipment/${this.equipmentId}/transfer`,{

                    method:'POST',

                    headers:{
                        'Content-Type':'application/json',
                        'Accept':'application/json',
                        'X-CSRF-TOKEN':document
                            .querySelector('meta[name="csrf-token"]')
                            .content
                    },

                    body:JSON.stringify({

                        room_id:this.transferRoom

                    })

                })

                .then(res => {

                    if(!res.ok){

                        throw new Error();

                    }

                    this.panel='';

                    return window.infrastructure.refreshRoomEquipment(this.roomId);

                })

                .catch(()=>{

                    alert('Transfer failed.');

                });

            },

            archiveEquipment(){

                fetch(`/maintenance/infrastructure/equipment/${this.equipmentId}`,{

                    method:'DELETE',

                    headers:{
                        'Content-Type':'application/json',
                        'Accept':'application/json',
                        'X-CSRF-TOKEN':document
                            .querySelector('meta[name="csrf-token"]')
                            .content
                    },

                    body:JSON.stringify({

                        reason:this.archiveReason

                    })

                })

                .then(res=>{

                    if(!res.ok){

                        throw new Error();

                    }

                    this.panel='';

                    return window.infrastructure.refreshRoomEquipment(this.roomId);

                })

                .catch(()=>{

                    alert('Archive failed.');

                });

            },

            

            init(){

                // Initial load
                this.form = this.equipment();

                // Keep following future object replacements
                this.$watch(

                    () => this.equipment(),

                    equipment => {

                        if(!equipment){

                            return;

                        }

                        if(!this.form){

                            this.form = equipment;

                            return;

                        }

                        Object.assign(this.form, equipment);

                    }

                );

            },

            

        }));

    });
</script>
