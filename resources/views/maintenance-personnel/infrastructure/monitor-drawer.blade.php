<!--<aside class="flex h-[900px] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl">-->
<!--<aside
    class="flex h-auto min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl xl:h-[700px] xl:w-[420px]"
>-->
<aside
    class="flex h-auto min-h-0 w-full shrink-0 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl xl:h-[calc(100vh-160px)] xl:max-h-[900px] xl:min-h-[700px] xl:w-[22vw] xl:min-w-[360px] xl:max-w-[460px]"
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
                    color:@js($room->room_color ?: '#60A5FA'),
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

                                    room_color:this.roomForm.color,

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
                                color: payload?.room?.color ?? this.roomForm.color,
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
                <div
                    class="relative mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3"
                >
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
                                ? "Reports"
                                : "Report"
                        }}</span>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->equipment
                                ->where("equipment_condition_status", "Good")
                                ->sum("equipment_quantity")
                        }}</b
                        ><span
                            class="mt-1.5 block truncate text-[10px] text-slate-400"
                            title="Good Condition"
                        >
                            {{
                                $room->equipment
                                    ->where("equipment_condition_status", "Good")
                                    ->sum("equipment_quantity") > 1
                                    ? "Good Conditions"
                                    : "Good Condition"
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="border-b border-slate-100 p-2">
                <div class="grid grid-cols-5 gap-1 rounded-xl bg-slate-100 p-1">
                    <button
                        @click="tab = 'overview'"
                        :class="tab === 'overview'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500 hover:bg-white/60'"
                        class="flex min-w-0 items-center justify-center rounded-lg px-1 py-2 text-[9px] font-semibold leading-tight transition sm:px-2 sm:py-2.5 sm:text-[11px]"
                    >
                        <span class="break-words text-center"> Overview </span>
                    </button>

                    <button
                        @click="tab = 'equipment'"
                        :class="tab === 'equipment'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500 hover:bg-white/60'"
                        class="flex min-w-0 items-center justify-center rounded-lg px-1 py-2 text-[9px] font-semibold leading-tight transition sm:px-2 sm:py-2.5 sm:text-[11px]"
                    >
                        <span class="break-words text-center"> Equipment </span>
                    </button>

                    <button
                        @click="tab = 'analytics'"
                        :class="tab === 'analytics'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500 hover:bg-white/60'"
                        class="flex min-w-0 items-center justify-center rounded-lg px-1 py-2 text-[9px] font-semibold leading-tight transition sm:px-2 sm:py-2.5 sm:text-[11px]"
                    >
                        <span class="break-words text-center"> Analytics </span>
                    </button>

                    <button
                        @click="tab = 'schedule'"
                        :class="tab === 'schedule'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500 hover:bg-white/60'"
                        class="flex min-w-0 items-center justify-center rounded-lg px-1 py-2 text-[9px] font-semibold leading-tight transition sm:px-2 sm:py-2.5 sm:text-[11px]"
                    >
                        <span class="break-words text-center"> Schedule </span>
                    </button>

                    <button
                        @click="tab = 'history'"
                        :class="tab === 'history'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500 hover:bg-white/60'"
                        class="flex min-w-0 items-center justify-center rounded-lg px-1 py-2 text-[9px] font-semibold leading-tight transition sm:px-2 sm:py-2.5 sm:text-[11px]"
                    >
                        <span class="break-words text-center"> History </span>
                    </button>
                </div>
            </div>

            <div
                class="relative min-h-0 flex-1 overflow-y-auto overflow-x-hidden p-5"
            >
                <div x-show="tab === 'overview'" x-cloak class="space-y-5">
                    <div
                        class="
                            overflow-hidden
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                        "
                    >

                        <!-- ===================================================== -->
                        <!-- CARD HEADER -->
                        <!-- ===================================================== -->

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                px-5
                                py-4
                            "
                        >

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Room Information
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Inspection, maintenance, and room status details.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="info"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ===================================================== -->
                        <!-- INFORMATION LIST -->
                        <!-- ===================================================== -->

                        <div class="px-5">


                            <!-- ================================================= -->
                            <!-- LAST INSPECTION -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    py-3.5
                                "
                            >

                                <div class="flex min-w-0 items-center gap-3">

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="clipboard-check"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </div>


                                    <span class="text-sm text-slate-500">
                                        Last Inspection
                                    </span>

                                </div>


                                <span
                                    class="
                                        shrink-0
                                        text-right
                                        text-sm
                                        font-medium
                                        text-slate-800
                                    "
                                    x-text="
                                        currentRoom?.monitoring
                                            ?.room_information?.last_inspection
                                            ? formatDate(
                                                currentRoom.monitoring
                                                    .room_information
                                                    .last_inspection
                                            )
                                            : 'Never'
                                    "
                                ></span>

                            </div>



                            <!-- ================================================= -->
                            <!-- NEXT MAINTENANCE -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    py-3.5
                                "
                            >

                                <div class="flex min-w-0 items-center gap-3">

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="calendar-clock"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </div>


                                    <span class="text-sm text-slate-500">
                                        Next Maintenance
                                    </span>

                                </div>


                                <span
                                    class="
                                        shrink-0
                                        text-right
                                        text-sm
                                        font-medium
                                        text-slate-800
                                    "
                                    x-text="
                                        currentRoom?.monitoring
                                            ?.room_information?.next_maintenance
                                            ? formatDate(
                                                currentRoom.monitoring
                                                    .room_information
                                                    .next_maintenance
                                            )
                                            : 'No Schedule'
                                    "
                                ></span>

                            </div>



                            <!-- ================================================= -->
                            <!-- LAST MODIFIED -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    py-3.5
                                "
                            >

                                <div class="flex min-w-0 items-center gap-3">

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="history"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </div>


                                    <span class="text-sm text-slate-500">
                                        Last Modified
                                    </span>

                                </div>


                                <span
                                    class="
                                        min-w-0
                                        truncate
                                        text-right
                                        text-sm
                                        font-medium
                                        text-slate-800
                                    "
                                    x-text="
                                        currentRoom?.monitoring
                                            ?.room_information?.last_updated
                                            ? timeAgo(
                                                currentRoom.monitoring
                                                    .room_information
                                                    .last_updated
                                            )
                                            : 'Unknown'
                                    "
                                ></span>

                            </div>



                            <!-- ================================================= -->
                            <!-- ROOM STATUS -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    py-3.5
                                "
                            >

                                <div class="flex min-w-0 items-center gap-3">

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="activity"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </div>


                                    <span class="text-sm text-slate-500">
                                        Room Status
                                    </span>

                                </div>


                                <span
                                    class="
                                        inline-flex
                                        shrink-0
                                        items-center
                                        gap-1.5
                                        rounded-md
                                        px-2
                                        py-1
                                        text-[11px]
                                        font-medium
                                    "
                                    :class="
                                        currentRoom?.monitoring
                                            ?.room_information?.status === 'Critical'
                                            ? 'bg-red-50 text-red-700'
                                            : currentRoom?.monitoring
                                                ?.room_information?.status ===
                                            'Maintenance Needed'
                                                ? 'bg-amber-50 text-amber-700'
                                                : 'bg-emerald-50 text-emerald-700'
                                    "
                                >

                                    <!-- STATUS DOT -->

                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            currentRoom?.monitoring
                                                ?.room_information?.status === 'Critical'
                                                ? 'bg-red-500'
                                                : currentRoom?.monitoring
                                                    ?.room_information?.status ===
                                                'Maintenance Needed'
                                                    ? 'bg-amber-500'
                                                    : 'bg-emerald-500'
                                        "
                                    ></span>


                                    <span
                                        x-text="
                                            currentRoom?.monitoring
                                                ?.room_information?.status || 'Normal'
                                        "
                                    ></span>

                                </span>

                            </div>

                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- ROOM SUMMARY STATS -->
                    <!-- ===================================================== -->

                    <div class="grid grid-cols-2 gap-3">


                        <!-- ================================================= -->
                        <!-- EQUIPMENT -->
                        <!-- ================================================= -->

                        <div
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-4
                            "
                        >

                            <!-- HEADER -->

                            <div class="flex items-center justify-between gap-3">

                                <p
                                    class="
                                        text-xs
                                        font-medium
                                        text-slate-500
                                    "
                                >
                                    Assets
                                </p>


                                <div
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-blue-50
                                        text-[#005EA6]
                                    "
                                >
                                    <i
                                        data-lucide="monitor-cog"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                            </div>


                            <!-- VALUE -->

                            <div class="mt-3 flex items-end gap-2">

                                <p
                                    class="
                                        text-2xl
                                        font-semibold
                                        tracking-tight
                                        text-slate-900
                                    "
                                >
                                    {{ $room->monitoring["equipment_quantity"] }}
                                </p>


                                <span
                                    class="
                                        mb-0.5
                                        text-[11px]
                                        text-slate-400
                                    "
                                >
                                    registered
                                </span>

                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- ACTIVE REPORTS -->
                        <!-- ================================================= -->

                        <div
                            class="
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-4
                            "
                        >

                            <!-- HEADER -->

                            <div class="flex items-center justify-between gap-3">

                                <p
                                    class="
                                        text-xs
                                        font-medium
                                        text-slate-500
                                    "
                                >
                                    Active Reports
                                </p>


                                <div
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-lg
                                        bg-amber-50
                                        text-amber-600
                                    "
                                >
                                    <i
                                        data-lucide="triangle-alert"
                                        class="h-4 w-4"
                                    ></i>
                                </div>

                            </div>


                            <!-- VALUE -->

                            <div class="mt-3 flex items-end gap-2">

                                <p
                                    class="
                                        text-2xl
                                        font-semibold
                                        tracking-tight
                                        text-slate-900
                                    "
                                >
                                    {{ $room->monitoring["active_reports"] }}
                                </p>


                                <span
                                    class="
                                        mb-0.5
                                        text-[11px]
                                        text-slate-400
                                    "
                                >
                                    unresolved
                                </span>

                            </div>

                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- EQUIPMENT CONDITION -->
                    <!-- ===================================================== -->

                    <div
                        class="
                            overflow-hidden
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                        "
                    >

                        <!-- ================================================= -->
                        <!-- HEADER -->
                        <!-- ================================================= -->

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                px-5
                                py-4
                            "
                        >

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Equipment Condition
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Current condition of equipment in this room.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="activity"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- CONDITION LIST -->
                        <!-- ================================================= -->

                        <div class="px-5">


                            <!-- ================================================= -->
                            <!-- GOOD -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    py-3.5
                                "
                            >

                                <div class="flex items-center gap-2.5">

                                    <!--<span
                                        class="
                                            h-2
                                            w-2
                                            shrink-0
                                            rounded-full
                                            bg-emerald-500
                                        "
                                    ></span>-->


                                    <span class="text-sm text-slate-600">
                                        Good
                                    </span>

                                </div>


                                <span
                                    class="
                                        min-w-7
                                        rounded-md
                                        bg-emerald-50
                                        px-2
                                        py-1
                                        text-center
                                        text-xs
                                        font-semibold
                                        text-emerald-700
                                    "
                                >
                                    {{ $room->monitoring["equipment_good"] }}
                                </span>

                            </div>



                            <!-- ================================================= -->
                            <!-- UNDER MAINTENANCE -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    py-3.5
                                "
                            >

                                <div class="flex items-center gap-2.5">

                                    <!--<span
                                        class="
                                            h-2
                                            w-2
                                            shrink-0
                                            rounded-full
                                            bg-amber-500
                                        "
                                    ></span>-->


                                    <span class="text-sm text-slate-600">
                                        Under Maintenance
                                    </span>

                                </div>


                                <span
                                    class="
                                        min-w-7
                                        rounded-md
                                        bg-amber-50
                                        px-2
                                        py-1
                                        text-center
                                        text-xs
                                        font-semibold
                                        text-amber-700
                                    "
                                >
                                    {{ $room->monitoring["equipment_maintenance"] }}
                                </span>

                            </div>



                            <!-- ================================================= -->
                            <!-- DAMAGED -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-slate-100
                                    py-3.5
                                "
                            >

                                <div class="flex items-center gap-2.5">

                                    <!--<span
                                        class="
                                            h-2
                                            w-2
                                            shrink-0
                                            rounded-full
                                            bg-red-500
                                        "
                                    ></span>-->


                                    <span class="text-sm text-slate-600">
                                        Damaged
                                    </span>

                                </div>


                                <span
                                    class="
                                        min-w-7
                                        rounded-md
                                        bg-red-50
                                        px-2
                                        py-1
                                        text-center
                                        text-xs
                                        font-semibold
                                        text-red-700
                                    "
                                >
                                    {{ $room->monitoring["equipment_damaged"] }}
                                </span>

                            </div>



                            <!-- ================================================= -->
                            <!-- DISPOSED -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    py-3.5
                                "
                            >

                                <div class="flex items-center gap-2.5">

                                    <!--<span
                                        class="
                                            h-2
                                            w-2
                                            shrink-0
                                            rounded-full
                                            bg-slate-400
                                        "
                                    ></span>-->


                                    <span class="text-sm text-slate-600">
                                        Disposed
                                    </span>

                                </div>


                                <span
                                    class="
                                        min-w-7
                                        rounded-md
                                        bg-slate-100
                                        px-2
                                        py-1
                                        text-center
                                        text-xs
                                        font-semibold
                                        text-slate-600
                                    "
                                >
                                    {{ $room->monitoring["equipment_disposed"] }}
                                </span>

                            </div>

                        </div>

                    </div>

                    {{-- QUICK ACTIONS --}}

                    <!-- ===================================================== -->
                    <!-- QUICK ACTIONS -->
                    <!-- ===================================================== -->

                    <div
                        class="
                            overflow-hidden
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                        "
                    >

                        <!-- ================================================= -->
                        <!-- HEADER -->
                        <!-- ================================================= -->

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                px-5
                                py-4
                            "
                        >

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Quick Actions
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Manage this room and its equipment.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="zap"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- ACTION GRID -->
                        <!-- ================================================= -->

                        <div
                            class="
                                grid
                                grid-cols-1
                                gap-3
                                p-4
                                sm:grid-cols-2
                            "
                        >


                            <button
                                type="button"
                                @click="addEquipmentModal = true"
                                class="group h-full w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-left transition hover:border-[#005EA6] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-70 sm:p-4"
                            >
                                <div class="flex flex-col items-start gap-3">
                                    <div
                                        class="rounded-xl bg-blue-100 p-2 text-[#005EA6] sm:p-2.5"
                                    >
                                        <i
                                            data-lucide="plus"
                                            class="h-4 w-4 sm:h-5 sm:w-5"
                                        ></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h4
                                            class="text-xs font-bold text-black sm:text-[13px]"
                                        >
                                            Add Equipment
                                        </h4>

                                        <p class="mt-1 break-words text-[10px] leading-4 text-slate-500">Provision new equipment into this room.</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="editRoomModal = true"
                                class="group h-full w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-left transition hover:border-[#005EA6] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-70 sm:p-4"
                            >
                                <div class="flex flex-col items-start gap-3">
                                    <div
                                        class="rounded-xl bg-amber-100 p-2 text-amber-600 sm:p-2.5"
                                    >
                                        <i
                                            data-lucide="pencil"
                                            class="h-4 w-4 sm:h-5 sm:w-5"
                                        ></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h4
                                            class="text-xs font-bold text-black sm:text-[13px]"
                                        >
                                            Edit Room
                                        </h4>

                                        <p class="mt-1 break-words text-[10px] leading-4 text-slate-500">Modify room information and layout.</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="transferAssetsModal = true"
                                class="group h-full w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-left transition hover:border-[#005EA6] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-70 sm:p-4"
                            >
                                <div class="flex flex-col items-start gap-3">
                                    <div
                                        class="rounded-xl bg-emerald-100 p-2 text-emerald-600 sm:p-2.5"
                                    >
                                        <i
                                            data-lucide="arrow-right-left"
                                            class="h-4 w-4 sm:h-5 sm:w-5"
                                        ></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h4
                                            class="text-xs font-bold text-black sm:text-[13px]"
                                        >
                                            Transfer Assets
                                        </h4>

                                        <p class="mt-1 break-words text-[10px] leading-4 text-slate-500">Move equipment to another room.</p>
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="archiveRoomModal = true"
                                class="group h-full w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-left transition hover:border-[#005EA6] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-70 sm:p-4"
                            >
                                <div class="flex flex-col items-start gap-3">
                                    <div
                                        class="rounded-xl bg-red-100 p-2 text-red-600 sm:p-2.5"
                                    >
                                        <i
                                            data-lucide="archive"
                                            class="h-4 w-4 sm:h-5 sm:w-5"
                                        ></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h4
                                            class="text-xs font-bold text-black sm:text-[13px]"
                                        >
                                            Archive Room
                                        </h4>

                                        <p class="mt-1 break-words text-[10px] leading-4 text-slate-500">Archive this room and keep records.</p>
                                    </div>
                                </div>
                            </button>

                        </div>



                        <!-- ================================================= -->
                        <!-- FOOTER -->
                        <!-- ================================================= -->

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                gap-4
                                border-t
                                border-slate-100
                                bg-slate-50/50
                                px-5
                                py-3
                            "
                        >

                            <div class="flex items-center gap-2">

                                <i
                                    data-lucide="panels-top-left"
                                    class="h-3.5 w-3.5 text-slate-400"
                                ></i>


                                <span
                                    class="
                                        text-xs
                                        font-medium
                                        text-slate-500
                                    "
                                >
                                    Layout Editor
                                </span>

                            </div>


                            <span
                                class="
                                    rounded-md
                                    border
                                    border-slate-200
                                    bg-white
                                    px-2
                                    py-1
                                    text-[10px]
                                    font-medium
                                    text-slate-500
                                "
                            >
                                Equipment Management
                            </span>

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
                            addEquipmentModal = false;

                            addForm = {
                                room_id: {{ $room->room_id }},
                                name: '',
                                category: '',
                                quantity: 1,
                                tracking: 'Bulk',
                                condition: 'Good',
                                location: ''
                            }
                        "
                        class="
                            fixed inset-0 z-[9999]
                            flex items-center justify-center
                            bg-slate-950/40
                            p-4
                            backdrop-blur-sm
                            sm:p-6
                        "
                    >

                        <!-- ===================================================== -->
                        <!-- MODAL CONTAINER -->
                        <!-- ===================================================== -->

                        <div
                            class="
                                flex
                                max-h-[calc(100dvh-2rem)]
                                w-full
                                max-w-xl
                                flex-col
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-2xl
                            "
                        >


                            <!-- ================================================= -->
                            <!-- HEADER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    shrink-0
                                    items-center
                                    justify-between
                                    border-b
                                    border-slate-100
                                    px-6
                                    py-5
                                "
                            >

                                <div>

                                    <h2
                                        class="
                                            text-lg
                                            font-semibold
                                            tracking-tight
                                            text-slate-950
                                        "
                                    >
                                        Add Equipment
                                    </h2>


                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            text-slate-500
                                        "
                                    >
                                        Register a new asset for this room.
                                    </p>

                                </div>


                                <!-- CLOSE BUTTON -->

                                <button
                                    type="button"
                                    @click="addEquipmentModal = false"
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-lg
                                        text-black
                                        transition

                                        hover:bg-slate-100
                                        hover:text-slate-700
                                    "
                                >

                                    <i
                                        data-lucide="x"
                                        class="h-4 w-4"
                                    ></i>

                                </button>

                            </div>



                            <!-- ================================================= -->
                            <!-- SCROLLABLE BODY -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    min-h-0
                                    flex-1
                                    overflow-y-auto
                                    overscroll-contain
                                    px-6
                                    py-6
                                "
                            >

                                <!-- ================================================= -->
                                <!-- FORM GRID -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        grid
                                        grid-cols-1
                                        gap-x-6
                                        gap-y-6
                                        sm:grid-cols-2
                                    "
                                >


                                    <!-- ================================================= -->
                                    <!-- NAME -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Name
                                        </label>


                                        <input
                                            x-model="addForm.name"
                                            type="text"
                                            placeholder="e.g. Server Rack A1"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-900
                                                outline-none
                                                transition

                                                placeholder:text-slate-400

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        />

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- CATEGORY -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Category
                                        </label>


                                        <select
                                            x-model="addForm.category"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
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



                                    <!-- ================================================= -->
                                    <!-- QUANTITY -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Quantity
                                        </label>


                                        <input
                                            x-model="addForm.quantity"
                                            type="number"
                                            min="1"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-900
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        />

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- TRACKING MODE -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Tracking Mode
                                        </label>


                                        <!-- ================================================= -->
                                        <!-- SEGMENTED CONTROL -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                flex
                                                h-11
                                                rounded-lg
                                                bg-slate-100
                                                p-1
                                            "
                                        >


                                            <!-- BULK -->

                                            <button
                                                type="button"
                                                @click="addForm.tracking = 'Bulk'"
                                                :class="
                                                    addForm.tracking === 'Bulk'
                                                        ? 'bg-white text-slate-900 shadow-sm'
                                                        : 'text-slate-500 hover:text-slate-700'
                                                "
                                                class="
                                                    flex-1
                                                    rounded-md
                                                    text-sm
                                                    font-medium
                                                    transition
                                                "
                                            >
                                                Bulk
                                            </button>



                                            <!-- INDIVIDUAL -->

                                            <button
                                                type="button"
                                                @click="addForm.tracking = 'Individual'"
                                                :class="
                                                    addForm.tracking === 'Individual'
                                                        ? 'bg-white text-slate-900 shadow-sm'
                                                        : 'text-slate-500 hover:text-slate-700'
                                                "
                                                class="
                                                    flex-1
                                                    rounded-md
                                                    text-sm
                                                    font-medium
                                                    transition
                                                "
                                            >
                                                Individual
                                            </button>

                                        </div>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- CONDITION -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Condition
                                        </label>


                                        <select
                                            x-model="addForm.condition"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        >

                                            <option>
                                                Good
                                            </option>

                                            <option>
                                                Under Maintenance
                                            </option>

                                            <option>
                                                Damaged
                                            </option>

                                            <option>
                                                Disposed
                                            </option>

                                        </select>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- PLACEMENT -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Placement
                                        </label>


                                        <div class="relative">

                                            <!-- MAP PIN ICON -->

                                            <div
                                                class="
                                                    pointer-events-none
                                                    absolute
                                                    left-3
                                                    top-1/2
                                                    -translate-y-1/2
                                                    text-slate-400
                                                "
                                            >
                                                <i
                                                    data-lucide="map-pin"
                                                    class="h-4 w-4"
                                                ></i>
                                            </div>


                                            <select
                                                x-model="addForm.location"
                                                @change="updateEquipmentPlacement()"
                                                class="
                                                    h-11
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    pl-9
                                                    pr-3
                                                    text-sm
                                                    text-slate-700
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
                                            >

                                                <option
                                                    value=""
                                                    disabled
                                                    hidden
                                                >
                                                    Select placement
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

                                    </div>

                                </div>

                            </div>



                            <!-- ================================================= -->
                            <!-- FOOTER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    shrink-0
                                    border-t
                                    border-slate-100
                                    bg-white
                                    px-6
                                    py-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        flex-col-reverse
                                        justify-end
                                        gap-3
                                        sm:flex-row
                                    "
                                >


                                    <!-- CANCEL BUTTON -->

                                    <button
                                        type="button"
                                        @click="
                                            addEquipmentModal = false;

                                            addForm = {
                                                room_id: {{ $room->room_id }},
                                                name: '',
                                                category: '',
                                                quantity: 1,
                                                tracking: 'Bulk',
                                                condition: 'Good',
                                                location: ''
                                            }
                                        "
                                        class="
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-medium
                                            text-slate-700
                                            transition

                                            hover:bg-slate-50
                                            hover:text-slate-950
                                        "
                                    >
                                        Cancel
                                    </button>



                                    <!-- ADD EQUIPMENT BUTTON -->

                                    <button
                                        type="button"
                                        @click="storeEquipment()"
                                        :disabled="saving"
                                        class="
                                            rounded-lg
                                            
                                            
                                            bg-[rgba(0,55,199,0.85)]
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-medium
                                            text-white
                                            transition

                                            hover:bg-[rgba(0, 44, 155, 0.85)]

                                            disabled:cursor-not-allowed
                                            disabled:opacity-50
                                        "
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

                    </div>
                </template>

                <!-- ============================== -->
                <!-- Edit Room Modal -->
                <!-- Place AFTER Add Equipment Modal -->
                <!-- ============================== -->

                <template x-if="editRoomModal">
                    <div
                        x-transition.opacity
                        @click.self="editRoomModal = false"
                        class="
                            fixed inset-0 z-[9999]
                            flex items-center justify-center
                            bg-slate-950/40
                            p-4
                            backdrop-blur-sm
                            sm:p-6
                        "
                    >

                        <!-- ===================================================== -->
                        <!-- MODAL CONTAINER -->
                        <!-- ===================================================== -->

                        <div
                            class="
                                flex
                                max-h-[calc(100dvh-2rem)]
                                w-full
                                max-w-2xl
                                flex-col
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-2xl
                            "
                        >


                            <!-- ================================================= -->
                            <!-- HEADER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    shrink-0
                                    items-start
                                    justify-between
                                    border-b
                                    border-slate-100
                                    px-6
                                    py-4
                                "
                            >

                                <div>

                                    <h2
                                        class="
                                            text-lg
                                            font-semibold
                                            tracking-tight
                                            text-slate-950
                                        "
                                    >
                                        Edit Room
                                    </h2>


                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Modify room details and environment configuration.
                                    </p>

                                </div>


                                <!-- CLOSE BUTTON -->

                                <button
                                    type="button"
                                    @click="editRoomModal = false"
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        items-center
                                        justify-center
                                        rounded-lg
                                        text-slate-400
                                        transition

                                        hover:bg-slate-100
                                        hover:text-slate-700
                                    "
                                >
                                    <i
                                        data-lucide="x"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                            </div>



                            <!-- ================================================= -->
                            <!-- SCROLLABLE BODY -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    min-h-0
                                    flex-1
                                    overflow-y-auto
                                    overscroll-contain
                                    px-6
                                    py-5
                                "
                            >


                                <!-- ================================================= -->
                                <!-- FORM GRID -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        grid
                                        grid-cols-1
                                        gap-x-6
                                        gap-y-5
                                        sm:grid-cols-2
                                    "
                                >


                                    <!-- ================================================= -->
                                    <!-- ROOM NAME -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Room Name
                                        </label>


                                        <input
                                            x-model="roomForm.name"
                                            type="text"
                                            placeholder="e.g. Lab 204"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-900
                                                outline-none
                                                transition

                                                placeholder:text-slate-400

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        />

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- OPERATIONAL STATUS -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Operational Status
                                        </label>


                                        <select
                                            x-model="roomForm.status"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        >

                                            <option>
                                                Normal
                                            </option>

                                            <option>
                                                Maintenance Needed
                                            </option>

                                            <option>
                                                Critical
                                            </option>

                                        </select>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- ROOM TYPE -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Room Type
                                        </label>


                                        <select
                                            x-model="roomForm.type"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
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

                                            <option value="Office">
                                                Office
                                            </option>

                                            <option value="Library">
                                                Library
                                            </option>

                                            <option value="School Clinic">
                                                School Clinic
                                            </option>

                                        </select>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- BLUEPRINT IDENTITY -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Blueprint Identity
                                        </label>


                                        <!-- COLOR CONTROL -->

                                        <div
                                            class="
                                                flex
                                                h-11
                                                items-center
                                                gap-3
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-2.5
                                                transition

                                                hover:border-slate-300

                                                focus-within:border-[#005EA6]
                                                focus-within:ring-2
                                                focus-within:ring-blue-100
                                            "
                                        >


                                            <!-- COLOR PICKER -->

                                            <input
                                                x-model="roomForm.color"
                                                type="color"
                                                class="
                                                    h-8
                                                    w-10
                                                    cursor-pointer
                                                    rounded-md
                                                    border-0
                                                    bg-transparent
                                                    p-0
                                                "
                                            />


                                            <!-- HEX VALUE -->

                                            <input
                                                x-model="roomForm.color"
                                                type="text"
                                                placeholder="#005EA6"
                                                class="
                                                    min-w-0
                                                    flex-1
                                                    border-0
                                                    bg-transparent
                                                    p-0
                                                    text-sm
                                                    font-medium
                                                    uppercase
                                                    tracking-wide
                                                    text-slate-700
                                                    outline-none
                                                "
                                            />

                                        </div>


                                        <!-- COLOR DESCRIPTION -->

                                        <div
                                            class="
                                                mt-2
                                                flex
                                                items-start
                                                gap-1.5
                                                text-xs
                                                leading-4
                                                text-slate-500
                                            "
                                        >

                                            <i
                                                data-lucide="info"
                                                class="
                                                    mt-0.5
                                                    h-3.5
                                                    w-3.5
                                                    shrink-0
                                                "
                                            ></i>


                                            <p>
                                                Custom color mapping for the management dashboard.
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </div>



                            <!-- ================================================= -->
                            <!-- FOOTER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    shrink-0
                                    border-t
                                    border-slate-100
                                    bg-white
                                    px-6
                                    py-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        flex-col-reverse
                                        justify-end
                                        gap-3
                                        sm:flex-row
                                    "
                                >


                                    <!-- DISCARD CHANGES -->

                                    <button
                                        type="button"
                                        @click="editRoomModal = false"
                                        class="
                                            min-w-[160px]
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-medium
                                            text-slate-600
                                            transition

                                            hover:border-slate-300
                                            hover:bg-slate-50
                                            hover:text-slate-900
                                        "
                                    >
                                        Discard Changes
                                    </button>



                                    <!-- UPDATE RECORDS -->

                                    <button
                                        type="button"
                                        @click="saveRoom()"
                                        :disabled="roomSaving"
                                        class="
                                            min-w-[160px]
                                            rounded-lg
                                            bg-[rgba(0,55,199,0.85)]
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-semibold
                                            text-white
                                            shadow-sm
                                            transition

                                            hover:bg-[rgba(0, 44, 155, 0.85)]

                                            disabled:cursor-not-allowed
                                            disabled:opacity-60
                                        "
                                    >

                                        <span
                                            x-text="
                                                roomSaving
                                                    ? 'Saving...'
                                                    : 'Update Records'
                                            "
                                        ></span>

                                    </button>

                                </div>

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
                        x-transition.opacity
                        @click.self="archiveRoomModal = false"
                        class="
                            fixed inset-0 z-[9999]
                            flex items-center justify-center
                            bg-slate-950/40
                            p-4
                            backdrop-blur-sm
                            sm:p-6
                        "
                    >

                        <!-- ===================================================== -->
                        <!-- MODAL CONTAINER -->
                        <!-- ===================================================== -->

                        <div
                            class="
                                flex
                                max-h-[calc(100dvh-2rem)]
                                w-full
                                max-w-lg
                                flex-col
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-2xl
                            "
                        >


                            <!-- ================================================= -->
                            <!-- HEADER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    shrink-0
                                    items-start
                                    justify-between
                                    border-b
                                    border-slate-100
                                    px-6
                                    py-4
                                "
                            >

                                <div class="flex items-center gap-3">

                                    <!-- WARNING ICON -->

                                    <!--<div
                                        class="
                                            flex
                                            h-9
                                            w-9
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-red-50
                                            text-red-600
                                        "
                                    >
                                        <i
                                            data-lucide="archive"
                                            class="h-4 w-4"
                                        ></i>
                                    </div>-->


                                    <!-- HEADER INFORMATION -->

                                    <div>

                                        <h2
                                            class="
                                                text-lg
                                                font-semibold
                                                tracking-tight
                                                text-slate-950
                                            "
                                        >
                                            Archive Room
                                        </h2>


                                        <p
                                            class="
                                                mt-0.5
                                                text-xs
                                                text-slate-500
                                            "
                                        >
                                            Remove this room from active infrastructure.
                                        </p>

                                    </div>

                                </div>


                                <!-- CLOSE BUTTON -->

                                <button
                                    type="button"
                                    @click="archiveRoomModal = false"
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-lg
                                        text-slate-400
                                        transition

                                        hover:bg-slate-100
                                        hover:text-slate-700
                                    "
                                >
                                    <i
                                        data-lucide="x"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                            </div>



                            <!-- ================================================= -->
                            <!-- SCROLLABLE BODY -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    min-h-0
                                    flex-1
                                    overflow-y-auto
                                    overscroll-contain
                                    px-6
                                    py-5
                                "
                            >


                                <!-- ================================================= -->
                                <!-- WARNING MESSAGE -->
                                <!-- ================================================= -->

                                <p
                                    class="
                                        text-sm
                                        leading-6
                                        text-slate-600
                                    "
                                >
                                    You are about to archive this room. Live equipment and
                                    schedules will be removed. Historical reports will remain
                                    available.
                                </p>



                                <!-- ================================================= -->
                                <!-- ROOM INFORMATION -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        mt-5
                                        flex
                                        items-center
                                        justify-between
                                        gap-4
                                        rounded-lg
                                        border
                                        border-slate-200
                                        bg-slate-50
                                        px-4
                                        py-3
                                    "
                                >

                                    <div class="min-w-0">

                                        <p
                                            class="
                                                text-xs
                                                font-medium
                                                text-slate-500
                                            "
                                        >
                                            Room
                                        </p>


                                        <p
                                            class="
                                                mt-0.5
                                                truncate
                                                text-sm
                                                font-semibold
                                                text-slate-900
                                            "
                                            x-text="roomForm.name"
                                        ></p>

                                    </div>


                                    <div
                                        class="
                                            shrink-0
                                            rounded-md
                                            border
                                            border-red-100
                                            bg-red-50
                                            px-2.5
                                            py-1
                                            text-xs
                                            font-medium
                                            text-red-600
                                        "
                                    >
                                        Will be archived
                                    </div>

                                </div>



                                <!-- ================================================= -->
                                <!-- ARCHIVE REASON -->
                                <!-- ================================================= -->

                                <div class="mt-5">

                                    <div
                                        class="
                                            mb-2
                                            flex
                                            items-center
                                            justify-between
                                        "
                                    >

                                        <label
                                            class="
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Reason
                                        </label>


                                        <span
                                            class="
                                                text-xs
                                                text-slate-400
                                            "
                                        >
                                            Optional
                                        </span>

                                    </div>


                                    <textarea
                                        x-model="archiveReason"
                                        rows="3"
                                        placeholder="Add a reason for archiving this room..."
                                        class="
                                            w-full
                                            resize-none
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-3.5
                                            py-3
                                            text-sm
                                            leading-5
                                            text-slate-900
                                            outline-none
                                            transition

                                            placeholder:text-slate-400

                                            hover:border-slate-300

                                            focus:border-red-400
                                            focus:ring-2
                                            focus:ring-red-100
                                        "
                                    ></textarea>

                                </div>

                            </div>



                            <!-- ================================================= -->
                            <!-- FOOTER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    shrink-0
                                    border-t
                                    border-slate-100
                                    bg-white
                                    px-6
                                    py-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        flex-col-reverse
                                        justify-end
                                        gap-3
                                        sm:flex-row
                                    "
                                >


                                    <!-- CANCEL -->

                                    <button
                                        type="button"
                                        @click="archiveRoomModal = false"
                                        class="
                                            min-w-[110px]
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-medium
                                            text-slate-600
                                            transition

                                            hover:border-slate-300
                                            hover:bg-slate-50
                                            hover:text-slate-900
                                        "
                                    >
                                        Cancel
                                    </button>



                                    <!-- ARCHIVE ROOM -->

                                    <button
                                        type="button"
                                        @click="archiveRoom()"
                                        :disabled="roomSaving"
                                        class="
                                            min-w-[145px]
                                            rounded-lg
                                            bg-red-600
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-semibold
                                            text-white
                                            shadow-sm
                                            transition

                                            hover:bg-red-700

                                            disabled:cursor-not-allowed
                                            disabled:opacity-60
                                        "
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

                    </div>
                </template>

                <template x-if="transferAssetsModal">
                    <div
                        x-transition.opacity
                        @click.self="transferAssetsModal = false"
                        class="
                            fixed inset-0 z-[9999]
                            flex items-center justify-center
                            bg-slate-950/40
                            p-4
                            backdrop-blur-sm
                            sm:p-6
                        "
                    >

                        <!-- ===================================================== -->
                        <!-- MODAL CONTAINER -->
                        <!-- ===================================================== -->

                        <div
                            class="
                                flex
                                max-h-[calc(100dvh-2rem)]
                                w-full
                                max-w-2xl
                                flex-col
                                overflow-hidden
                                rounded-2xl
                                border
                                border-slate-200
                                bg-white
                                shadow-2xl
                            "
                        >


                            <!-- ================================================= -->
                            <!-- HEADER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    flex
                                    shrink-0
                                    items-start
                                    justify-between
                                    border-b
                                    border-slate-100
                                    px-6
                                    py-4
                                "
                            >

                                <!-- HEADER INFORMATION -->

                                <div>

                                    <h2
                                        class="
                                            text-lg
                                            font-semibold
                                            tracking-tight
                                            text-slate-950
                                        "
                                    >
                                        Transfer Asset
                                    </h2>


                                    <p
                                        class="
                                            mt-0.5
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Select the equipment and destination room.
                                    </p>

                                </div>


                                <!-- CLOSE BUTTON -->

                                <button
                                    type="button"
                                    @click="transferAssetsModal = false"
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-lg
                                        text-slate-400
                                        transition

                                        hover:bg-slate-100
                                        hover:text-slate-700
                                    "
                                >

                                    <i
                                        data-lucide="x"
                                        class="h-4 w-4"
                                    ></i>

                                </button>

                            </div>



                            <!-- ================================================= -->
                            <!-- BODY -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    min-h-0
                                    flex-1
                                    overflow-y-auto
                                    overscroll-contain
                                    px-6
                                    py-6
                                "
                            >


                                <!-- ================================================= -->
                                <!-- FORM GRID -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        grid
                                        grid-cols-1
                                        gap-x-6
                                        gap-y-5
                                        sm:grid-cols-2
                                    "
                                >


                                    <!-- ================================================= -->
                                    <!-- EQUIPMENT -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Equipment
                                        </label>


                                        <select
                                            x-model="selectedEquipment"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        >

                                            <option value="">
                                                Select Equipment
                                            </option>


                                            @foreach ($room->equipment as $equipment)

                                                <option
                                                    value="{{ $equipment->equipment_id }}"
                                                >
                                                    {{ $equipment->equipment_name }}
                                                </option>

                                            @endforeach

                                        </select>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- DESTINATION ROOM -->
                                    <!-- ================================================= -->

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block
                                                text-xs
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Destination Room
                                        </label>


                                        <select
                                            x-model="destinationRoom"
                                            class="
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3.5
                                                text-sm
                                                text-slate-700
                                                outline-none
                                                transition

                                                hover:border-slate-300

                                                focus:border-[#005EA6]
                                                focus:ring-2
                                                focus:ring-blue-100
                                            "
                                        >

                                            <option value="">
                                                Select Room
                                            </option>


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

                                </div>


                                <!-- ================================================= -->
                                <!-- TRANSFER INFORMATION -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        mt-6
                                        flex
                                        items-start
                                        gap-3
                                        rounded-lg
                                        border
                                        border-slate-200
                                        bg-slate-50
                                        p-4
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-white
                                            text-slate-500
                                            shadow-sm
                                            ring-1
                                            ring-slate-200
                                        "
                                    >

                                        <i
                                            data-lucide="arrow-right-left"
                                            class="h-4 w-4"
                                        ></i>

                                    </div>


                                    <div>

                                        <h4
                                            class="
                                                text-sm
                                                font-medium
                                                text-slate-800
                                            "
                                        >
                                            Asset Transfer
                                        </h4>


                                        <p
                                            class="
                                                mt-1
                                                text-xs
                                                leading-5
                                                text-slate-500
                                            "
                                        >
                                            The selected equipment will be moved from the current
                                            room to the destination room.
                                        </p>

                                    </div>

                                </div>

                            </div>



                            <!-- ================================================= -->
                            <!-- FOOTER -->
                            <!-- ================================================= -->

                            <div
                                class="
                                    shrink-0
                                    border-t
                                    border-slate-100
                                    bg-white
                                    px-6
                                    py-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        flex-col-reverse
                                        justify-end
                                        gap-3
                                        sm:flex-row
                                    "
                                >


                                    <!-- CANCEL -->

                                    <button
                                        type="button"
                                        @click="transferAssetsModal = false"
                                        class="
                                            min-w-[120px]
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-white
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-medium
                                            text-slate-600
                                            transition

                                            hover:border-slate-300
                                            hover:bg-slate-50
                                            hover:text-slate-900
                                        "
                                    >
                                        Cancel
                                    </button>



                                    <!-- TRANSFER -->

                                    <button
                                        type="button"
                                        @click="transferAsset()"
                                        class="
                                            min-w-[140px]
                                            rounded-lg
                                            bg-[rgba(0,55,199,0.85)]
                                            px-5
                                            py-2.5
                                            text-sm
                                            font-semibold
                                            text-white
                                            shadow-sm
                                            transition

                                            hover:bg-[rgba(0, 44, 155, 0.85)]
                                        "
                                    >
                                        Transfer Asset
                                    </button>

                                </div>

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
                    <!-- ===================================================== -->
                    <!-- SEARCH & FILTER -->
                    <!-- ===================================================== -->

                    <div
                        class="
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                        "
                    >

                        <!-- ================================================= -->
                        <!-- TOOLBAR HEADER -->
                        <!-- ================================================= -->

                        <div
                            class="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-slate-100
                                px-5
                                py-4
                            "
                        >

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Equipment List
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Search and filter equipment in this room.
                                </p>

                            </div>

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="search"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- SEARCH CONTROLS -->
                        <!-- ================================================= -->

                        <div class="p-4">

                            <div
                                class="
                                    flex
                                    flex-col
                                    gap-3

                                    sm:flex-row
                                "
                            >

                                <!-- SEARCH -->

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
                                <option value="all">All Conditions</option>

                                <option value="good">Good</option>

                                <option value="maintenance">Maintenance</option>

                                <option value="damaged">Damaged</option>

                                <option value="disposed">Disposed</option>
                            </select>

                        </div>

                    </div>

                </div>

                    <template
                        x-for="
                            item in (equipmentRenderKey, liveStackEquipment())
                        "
                        :key="'live-stack-' + item.id"
                    >
                        <article
                            x-show="
                                (search === '' ||
                                    (item.name || '')
                                        .toLowerCase()
                                        .includes(search.toLowerCase())) &&
                                (filter === 'all' ||
                                    (filter === 'good' &&
                                        (item.condition || '') === 'Good') ||
                                    (filter === 'maintenance' &&
                                        (item.condition || '') === 'Under Maintenance') ||
                                    (filter === 'damaged' &&
                                        (item.condition || '') === 'Damaged') ||
                                    (filter === 'disposed' &&
                                        (item.condition || '') === 'Disposed'))
                            "
                            class="
                                group
                                overflow-hidden
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                transition

                                hover:border-slate-300
                                hover:shadow-sm
                            "
                        >

                            <!-- ================================================= -->
                            <!-- CARD -->
                            <!-- ================================================= -->

                            <div class="flex items-start justify-between gap-4 p-4">

                                <!-- LEFT -->

                                <div class="flex min-w-0 flex-1 gap-3">

                                    <!-- ICON -->

                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100"
                                    >
                                        <i
                                            data-lucide="monitor-cog"
                                            class="h-5 w-5 text-slate-500"
                                        ></i>
                                    </div>


                                    <!-- CONTENT -->

                                    <div class="min-w-0 flex-1">

                                        <!-- NAME -->

                                        <h3
                                            class="truncate text-sm font-semibold text-slate-900"
                                            x-text="item.name"
                                        ></h3>


                                        <!-- CATEGORY -->

                                        <p
                                            class="mt-1 truncate text-xs text-slate-500"
                                            x-text="item.category || 'Uncategorized'"
                                        ></p>


                                        <!-- LOCATION -->

                                        <div
                                            class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-400"
                                        >

                                            <i
                                                data-lucide="map-pin"
                                                class="h-3 w-3"
                                            ></i>

                                            <span
                                                x-text="
                                                    item.location ||
                                                    item.placement_zone ||
                                                    'No location'
                                                "
                                            ></span>

                                        </div>

                                    </div>

                                </div>



                                <!-- RIGHT -->

                                <div class="flex shrink-0 flex-col items-end gap-2">

                                    <!-- STATUS -->

                                    <span
                                        class="
                                            inline-flex
                                            items-center
                                            gap-1.5
                                            rounded-md
                                            px-2.5
                                            py-1
                                            text-[11px]
                                            font-medium
                                        "
                                        :class="
                                            (item.condition || '') === 'Good'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : (item.condition || '') === 'Under Maintenance'
                                                    ? 'bg-amber-50 text-amber-700'
                                                    : (item.condition || '') === 'Damaged'
                                                        ? 'bg-red-50 text-red-700'
                                                        : (item.condition || '') === 'Disposed'
                                                            ? 'bg-slate-100 text-slate-600'
                                                            : 'bg-slate-100 text-slate-500'
                                        "
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                (item.condition || '') === 'Good'
                                                    ? 'bg-emerald-500'
                                                    : (item.condition || '') === 'Under Maintenance'
                                                        ? 'bg-amber-500'
                                                        : (item.condition || '') === 'Damaged'
                                                            ? 'bg-red-500'
                                                            : (item.condition || '') === 'Disposed'
                                                                ? 'bg-slate-500'
                                                                : 'bg-slate-400'
                                            "
                                        ></span>

                                        <span
                                            x-text="item.condition || 'Unknown'"
                                        ></span>

                                    </span>


                                    <!-- QUANTITY -->

                                    <template x-if="item.quantity">

                                        <span
                                            class="
                                                rounded-md
                                                bg-slate-100
                                                px-2
                                                py-1
                                                text-[11px]
                                                font-medium
                                                text-slate-600
                                            "
                                        >
                                            Qty:
                                            <span x-text="item.quantity"></span>
                                        </span>

                                    </template>

                                </div>

                            </div>

                        </article>
                    </template>

                    @forelse ($room->equipment->sortByDesc("equipment_id") as $item)
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
                                                    @click.outside="menu = false"
                                                    x-transition.origin.top.right
                                                    class="
                                                        absolute
                                                        right-0
                                                        top-full
                                                        z-50
                                                        
                                                        w-[180px]
                                                        overflow-hidden
                                                        rounded-xl
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        shadow-xl
                                                    "
                                                >

                                                    <!-- ================================================= -->
                                                    <!-- VIEW DETAILS -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            panel = panel === 'details'
                                                                ? ''
                                                                : 'details';

                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-slate-700
                                                            transition

                                                            hover:bg-slate-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="eye"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <span class="flex-1">
                                                            View Details
                                                        </span>

                                                    </button>



                                                    <!-- ================================================= -->
                                                    <!-- EDIT -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            panel = 'edit';
                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-slate-700
                                                            transition

                                                            hover:bg-slate-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="square-pen"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <span class="flex-1">
                                                            Edit Equipment
                                                        </span>

                                                    </button>



                                                    <!-- ================================================= -->
                                                    <!-- TRANSFER -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            transferRoom = '';
                                                            panel = 'transfer';
                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-slate-700
                                                            transition

                                                            hover:bg-slate-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="arrow-right-left"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <span class="flex-1">
                                                            Transfer Equipment
                                                        </span>

                                                    </button>



                                                    <!-- DIVIDER -->

                                                    <div class="mx-2 border-t border-slate-100"></div>



                                                    <!-- ================================================= -->
                                                    <!-- ARCHIVE -->
                                                    <!-- ================================================= -->

                                                    <button
                                                        @click="
                                                            archiveReason = '';
                                                            panel = 'archive';
                                                            menu = false;
                                                        "
                                                        class="
                                                            flex
                                                            w-full
                                                            items-center
                                                            gap-3
                                                            px-4
                                                            py-3
                                                            text-left
                                                            text-[12px]
                                                            text-red-600
                                                            transition

                                                            hover:bg-red-50
                                                        "
                                                    >

                                                        <i
                                                            data-lucide="archive"
                                                            class="h-4 w-4"
                                                        ></i>

                                                        <span class="flex-1">
                                                            Archive Equipment
                                                        </span>

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
                                    class="
                                        mt-5
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <!-- HEADER INFORMATION -->

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                Equipment Information
                                            </h4>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Asset identification, purchase, and assignment details.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- PANEL BODY -->
                                    <!-- ===================================================== -->

                                    <div class="p-5">


                                        <!-- ================================================= -->
                                        <!-- INFORMATION GRID -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                grid
                                                grid-cols-2
                                                gap-x-6
                                                gap-y-5
                                            "
                                        >


                                            <!-- ASSET TAG -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Asset Tag
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_asset_tag
                                                            ?: "Not Assigned"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- SERIAL NUMBER -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Serial Number
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_serial_number
                                                            ?? "Unavailable"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- WARRANTY -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Warranty
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_warranty_expiration
                                                            ?? "Unknown"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- SUPPLIER -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Supplier
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_supplier
                                                            ?? "Not Assigned"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- PURCHASE DATE -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Purchase Date
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_purchase_date
                                                            ?? "Unknown"
                                                    }}
                                                </p>

                                            </div>



                                            <!-- ASSIGNED TECHNICIAN -->

                                            <div class="min-w-0">

                                                <p
                                                    class="
                                                        text-[10px]
                                                        font-medium
                                                        uppercase
                                                        tracking-wide
                                                        text-slate-400
                                                    "
                                                >
                                                    Assigned Technician
                                                </p>

                                                <p
                                                    class="
                                                        mt-1
                                                        truncate
                                                        text-sm
                                                        font-medium
                                                        text-slate-800
                                                    "
                                                >
                                                    {{
                                                        $item->equipment_assigned_to
                                                            ?? "Unassigned"
                                                    }}
                                                </p>

                                            </div>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- QR CODE SECTION -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                mt-6
                                                border-t
                                                border-slate-100
                                                pt-5
                                            "
                                        >

                                            <div
                                                class="
                                                    flex
                                                    items-center
                                                    justify-between
                                                    gap-4
                                                "
                                            >

                                                <!-- QR INFORMATION -->

                                                <div class="min-w-0">

                                                    <div class="flex items-center gap-2">

                                                        <i
                                                            data-lucide="qr-code"
                                                            class="h-4 w-4 text-slate-400"
                                                        ></i>

                                                        <p
                                                            class="
                                                                text-sm
                                                                font-medium
                                                                text-slate-800
                                                            "
                                                        >
                                                            QR Code
                                                        </p>

                                                    </div>


                                                    <p
                                                        class="
                                                            mt-1
                                                            max-w-[220px]
                                                            text-xs
                                                            leading-5
                                                            text-slate-500
                                                        "
                                                    >
                                                        Asset QR identification will be available in the Asset
                                                        Management phase.
                                                    </p>

                                                </div>



                                                <!-- QR PLACEHOLDER -->

                                                <div
                                                    class="
                                                        flex
                                                        h-20
                                                        w-20
                                                        shrink-0
                                                        items-center
                                                        justify-center
                                                        rounded-lg
                                                        border
                                                        border-dashed
                                                        border-slate-200
                                                        bg-slate-50
                                                    "
                                                >

                                                    <i
                                                        data-lucide="qr-code"
                                                        class="h-7 w-7 text-slate-300"
                                                    ></i>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'edit'"
                                    x-transition
                                    class="
                                        mt-5
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                Edit Equipment
                                            </h4>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Update equipment information and placement.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- FORM BODY -->
                                    <!-- ===================================================== -->

                                    <div class="space-y-5 p-5">


                                        <!-- ================================================= -->
                                        <!-- EQUIPMENT NAME -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Equipment Name
                                            </label>


                                            <input
                                                x-model="form.name"
                                                type="text"
                                                class="
                                                    h-10
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    text-slate-900
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
                                            />

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- CATEGORY -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Category
                                            </label>


                                            <select
                                                x-model="form.category"
                                                class="
                                                    h-10
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    text-slate-700
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
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



                                        <!-- ================================================= -->
                                        <!-- PLACEMENT -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Placement
                                            </label>


                                            <div class="relative">

                                                <!-- LOCATION ICON -->

                                                <div
                                                    class="
                                                        pointer-events-none
                                                        absolute
                                                        left-3
                                                        top-1/2
                                                        -translate-y-1/2
                                                        text-slate-400
                                                    "
                                                >
                                                    <i
                                                        data-lucide="map-pin"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>


                                                <select
                                                    x-model="form.location"
                                                    @change="updateEquipmentPlacement()"
                                                    class="
                                                        h-10
                                                        w-full
                                                        rounded-lg
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        pl-8
                                                        pr-3
                                                        text-sm
                                                        text-slate-700
                                                        outline-none
                                                        transition

                                                        hover:border-slate-300

                                                        focus:border-[#005EA6]
                                                        focus:ring-2
                                                        focus:ring-blue-100
                                                    "
                                                >

                                                    <option
                                                        value=""
                                                        disabled
                                                    >
                                                        Select equipment position
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

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- QUANTITY -->
                                        <!-- Only visible for Bulk equipment -->
                                        <!-- ================================================= -->

                                        @if ($item->equipment_tracking_mode == "Bulk")

                                            <div>

                                                <label
                                                    class="
                                                        mb-2
                                                        block
                                                        text-xs
                                                        font-medium
                                                        text-slate-600
                                                    "
                                                >
                                                    Quantity
                                                </label>


                                                <input
                                                    x-model="form.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="
                                                        h-10
                                                        w-full
                                                        rounded-lg
                                                        border
                                                        border-slate-200
                                                        bg-white
                                                        px-3
                                                        text-sm
                                                        text-slate-900
                                                        outline-none
                                                        transition

                                                        hover:border-slate-300

                                                        focus:border-[#005EA6]
                                                        focus:ring-2
                                                        focus:ring-blue-100
                                                    "
                                                />

                                            </div>

                                        @endif



                                        <!-- ================================================= -->
                                        <!-- CONDITION -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Condition
                                            </label>


                                            <select
                                                x-model="form.condition"
                                                class="
                                                    h-10
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    text-slate-700
                                                    outline-none
                                                    transition

                                                    hover:border-slate-300

                                                    focus:border-[#005EA6]
                                                    focus:ring-2
                                                    focus:ring-blue-100
                                                "
                                            >

                                                <option>
                                                    Good
                                                </option>

                                                <option>
                                                    Under Maintenance
                                                </option>

                                                <option>
                                                    Damaged
                                                </option>

                                                <option>
                                                    Disposed
                                                </option>

                                            </select>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ACTIONS -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                flex
                                                justify-end
                                                gap-3
                                                border-t
                                                border-slate-100
                                                pt-4
                                            "
                                        >

                                            <!-- CANCEL -->

                                            <button
                                                type="button"
                                                @click="panel = ''"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-4
                                                    py-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                    transition

                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900
                                                "
                                            >
                                                Cancel
                                            </button>


                                            <!-- SAVE CHANGES -->

                                            <button
                                                type="button"
                                                @click="saveEquipment()"
                                                :disabled="saving"
                                                class="
                                                    rounded-lg
                                                    bg-[rgba(0,55,199,0.85)]
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    shadow-sm
                                                    transition

                                                    hover:bg-[rgba(0, 44, 155, 0.85)]

                                                    disabled:cursor-not-allowed
                                                    disabled:opacity-60
                                                "
                                            >

                                                <span
                                                    x-text="
                                                        saving
                                                            ? 'Saving...'
                                                            : 'Save'
                                                    "
                                                ></span>

                                            </button>

                                        </div>

                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'transfer'"
                                    x-collapse
                                    class="
                                        mt-5
                                        
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <!-- HEADER INFORMATION -->

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-900">
                                                Transfer Equipment
                                            </h4>

                                            <p class="mt-0.5 text-xs text-slate-500">
                                                Move this equipment to another room.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- PANEL BODY -->
                                    <!-- ===================================================== -->

                                    <div class="p-5">


                                        <!-- ================================================= -->
                                        <!-- DESTINATION ROOM -->
                                        <!-- ================================================= -->

                                        <div>

                                            <label
                                                class="
                                                    mb-2
                                                    block
                                                    text-xs
                                                    font-medium
                                                    text-slate-600
                                                "
                                            >
                                                Destination Room
                                            </label>


                                            <div class="relative">

                                                <!-- LOCATION ICON -->

                                                <div
                                                    class="
                                                        pointer-events-none
                                                        absolute
                                                        left-3
                                                        top-1/2
                                                        -translate-y-1/2
                                                        text-slate-400
                                                    "
                                                >
                                                    <i
                                                        data-lucide="map-pin"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>


                                                <div 
                                                    x-data="{ 
                                                        open: false, 
                                                        transferRoom: '', 
                                                        rooms: [
                                                            {{-- We can pass the PHP rooms data straight to JS if needed, or just let Alpine handle the click --}}
                                                        ]
                                                    }" 
                                                    class="relative w-full"
                                                    @click.outside="open = false"
                                                >
                                                    <button 
                                                        type="button"
                                                        @click="open = !open"
                                                        class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-8 pr-10 text-left text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                                                    >
                                                        <span x-text="transferRoom ? document.getElementById('room-opt-' + transferRoom)?.innerText : 'Select destination room'"></span>
                                                        
                                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                        </span>
                                                    </button>

                                                    <input type="hidden" name="transferRoom" x-model="transferRoom">

                                                    <div 
                                                        x-show="open" 
                                                        x-transition
                                                        class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg py-1 text-sm text-slate-700 max-h-[160px] overflow-y-auto"
                                                        style="display: none;"
                                                    >
                                                        <div 
                                                            @click="transferRoom = ''; open = false" 
                                                            class="cursor-pointer px-4 py-2 hover:bg-slate-100 text-slate-400"
                                                        >
                                                            Select destination room
                                                        </div>

                                                        @foreach ($rooms as $destination)
                                                            @if ($destination->room_id != $room->room_id)
                                                                <div 
                                                                    id="room-opt-{{ $destination->room_id }}"
                                                                    @click="transferRoom = '{{ $destination->room_id }}'; open = false"
                                                                    class="cursor-pointer px-4 py-2 hover:bg-blue-50 hover:text-[#005EA6] transition-colors"
                                                                    :class="transferRoom == '{{ $destination->room_id }}' ? 'bg-blue-50 text-[#005EA6] font-medium' : ''"
                                                                >
                                                                    {{ $destination->room_name }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>

                                            </div>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ACTIONS -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                mt-5
                                                flex
                                                justify-end
                                                gap-3
                                                border-t
                                                border-slate-100
                                                pt-4
                                            "
                                        >

                                            <!-- CANCEL -->

                                            <button
                                                type="button"
                                                @click="panel = ''"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-4
                                                    py-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                    transition

                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900
                                                "
                                            >
                                                Cancel
                                            </button>


                                            <!-- TRANSFER -->

                                            <button
                                                type="button"
                                                @click="transferEquipment()"
                                                :disabled="!transferRoom"
                                                class="
                                                    rounded-lg
                                                    bg-[rgba(0,55,199,0.85)]
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    shadow-sm
                                                    transition

                                                    hover:bg-[rgba(0, 44, 155, 0.85)]

                                                    disabled:cursor-not-allowed
                                                    disabled:opacity-50
                                                "
                                            >
                                                Transfer
                                            </button>

                                        </div>

                                    </div>

                                </div>

                                <div
                                    x-show="panel === 'archive'"
                                    x-collapse
                                    class="
                                        mt-5
                                        overflow-hidden
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                    "
                                >

                                    <!-- ===================================================== -->
                                    <!-- PANEL HEADER -->
                                    <!-- ===================================================== -->

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            border-b
                                            border-slate-100
                                            px-5
                                            py-4
                                        "
                                    >

                                        <!-- HEADER INFORMATION -->

                                        <div>

                                            <div class="flex items-center gap-2">

                                                <div
                                                    class="
                                                        flex
                                                        h-7
                                                        w-7
                                                        items-center
                                                        justify-center
                                                        rounded-lg
                                                        bg-red-50
                                                        text-red-600
                                                    "
                                                >
                                                    <i
                                                        data-lucide="archive"
                                                        class="h-3.5 w-3.5"
                                                    ></i>
                                                </div>


                                                <h4 class="text-sm font-semibold text-slate-900">
                                                    Archive Equipment
                                                </h4>

                                            </div>


                                            <p class="mt-1.5 text-xs leading-5 text-slate-500">
                                                Remove this equipment from the active room inventory.
                                            </p>

                                        </div>


                                        <!-- CLOSE PANEL -->

                                        <button
                                            type="button"
                                            @click="panel = ''"
                                            class="
                                                flex
                                                h-8
                                                w-8
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                text-slate-400
                                                transition

                                                hover:bg-slate-100
                                                hover:text-slate-700
                                            "
                                        >
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>

                                    </div>



                                    <!-- ===================================================== -->
                                    <!-- PANEL BODY -->
                                    <!-- ===================================================== -->

                                    <div class="p-5">


                                        <!-- ================================================= -->
                                        <!-- ARCHIVE WARNING -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                flex
                                                items-start
                                                gap-3
                                                rounded-lg
                                                border
                                                border-red-100
                                                bg-red-50/60
                                                p-3.5
                                            "
                                        >

                                            <i
                                                data-lucide="triangle-alert"
                                                class="
                                                    mt-0.5
                                                    h-4
                                                    w-4
                                                    shrink-0
                                                    text-red-500
                                                "
                                            ></i>


                                            <p class="text-xs leading-5 text-slate-600">
                                                This equipment will no longer appear in the active inventory.
                                                Historical maintenance and report records will remain available.
                                            </p>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ARCHIVE REASON -->
                                        <!-- ================================================= -->

                                        <div class="mt-5">

                                            <div
                                                class="
                                                    mb-2
                                                    flex
                                                    items-center
                                                    justify-between
                                                "
                                            >

                                                <label
                                                    class="
                                                        text-xs
                                                        font-medium
                                                        text-slate-600
                                                    "
                                                >
                                                    Reason
                                                </label>


                                                <span class="text-xs text-slate-400">
                                                    Optional
                                                </span>

                                            </div>


                                            <textarea
                                                x-model="archiveReason"
                                                rows="3"
                                                placeholder="Add a reason for archiving this equipment..."
                                                class="
                                                    w-full
                                                    resize-none
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-3
                                                    py-2.5
                                                    text-sm
                                                    leading-5
                                                    text-slate-900
                                                    outline-none
                                                    transition

                                                    placeholder:text-slate-400

                                                    hover:border-slate-300

                                                    focus:border-red-400
                                                    focus:ring-2
                                                    focus:ring-red-100
                                                "
                                            ></textarea>

                                        </div>



                                        <!-- ================================================= -->
                                        <!-- ACTIONS -->
                                        <!-- ================================================= -->

                                        <div
                                            class="
                                                mt-5
                                                flex
                                                justify-end
                                                gap-3
                                                border-t
                                                border-slate-100
                                                pt-4
                                            "
                                        >

                                            <!-- CANCEL -->

                                            <button
                                                type="button"
                                                @click="panel = ''"
                                                class="
                                                    rounded-lg
                                                    border
                                                    border-slate-200
                                                    bg-white
                                                    px-4
                                                    py-2
                                                    text-sm
                                                    font-medium
                                                    text-slate-600
                                                    transition

                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900
                                                "
                                            >
                                                Cancel
                                            </button>


                                            <!-- ARCHIVE -->

                                            <button
                                                type="button"
                                                @click="archiveEquipment()"
                                                class="
                                                    rounded-lg
                                                    bg-red-600
                                                    px-5
                                                    py-2
                                                    text-sm
                                                    font-semibold
                                                    text-white
                                                    shadow-sm
                                                    transition

                                                    hover:bg-red-700
                                                "
                                            >
                                                Archive
                                            </button>

                                        </div>

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

                <div
                    x-show="tab === 'analytics'"
                    x-cloak
                >

                    <!-- ===================================================== -->
                    <!-- REPORT VOLUME -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Report Volume
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Reports recorded for this room.
                                </p>

                            </div>


                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="chart-no-axes-column"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- REPORT STATISTICS -->
                        <!-- ================================================= -->

                        <div class="mt-4 grid grid-cols-3 gap-3">

                            @foreach ([
                                "Today" => "today_reports",
                                "Weekly" => "week_reports",
                                "Monthly" => "month_reports"
                            ] as $label => $key)

                                <div
                                    class="
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        px-3
                                        py-4
                                        text-center
                                        transition

                                        hover:border-slate-300
                                        hover:shadow-sm
                                    "
                                >

                                    <!-- REPORT COUNT -->

                                    <p
                                        class="
                                            text-xl
                                            font-semibold
                                            tracking-tight
                                            text-slate-900
                                        "
                                    >
                                        {{ $room->monitoring[$key] }}
                                    </p>


                                    <!-- REPORT PERIOD -->

                                    <p
                                        class="
                                            mt-1
                                            text-[11px]
                                            font-medium
                                            text-slate-500
                                        "
                                    >
                                        {{ $label }}
                                    </p>

                                </div>

                            @endforeach

                        </div>

                    </div>



                    <!-- ===================================================== -->
                    <!-- SECTION DIVIDER -->
                    <!-- ===================================================== -->

                    <div class="my-6 border-t border-slate-100"></div>



                    <!-- ===================================================== -->
                    <!-- FREQUENT PROBLEMS -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div>

                            <h3 class="text-sm font-semibold text-slate-900">
                                Frequent Problems
                            </h3>

                            <p class="mt-0.5 text-xs text-slate-500">
                                Issues reported repeatedly in this room.
                            </p>

                        </div>



                        <!-- ================================================= -->
                        <!-- PROBLEM LIST -->
                        <!-- ================================================= -->

                        <div class="mt-4">

                            @forelse ($room->monitoring["frequent_problems"] as $problem)

                                <div
                                    class="
                                        flex
                                        items-start
                                        gap-3
                                        border-b
                                        border-slate-100
                                        py-3.5

                                        first:pt-0
                                        last:border-b-0
                                        last:pb-0
                                    "
                                >

                                    <!-- PROBLEM ICON -->

                                    <div
                                        class="
                                            mt-0.5
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-red-50
                                            text-red-500
                                        "
                                    >
                                        <i
                                            data-lucide="triangle-alert"
                                            class="h-3.5 w-3.5"
                                        ></i>
                                    </div>



                                    <!-- PROBLEM INFORMATION -->

                                    <div class="min-w-0 flex-1">

                                        <p
                                            class="
                                                text-xs
                                                leading-5
                                                text-slate-700
                                            "
                                        >
                                            {{ $problem->report_problem_description }}
                                        </p>


                                        <p
                                            class="
                                                mt-1
                                                text-[11px]
                                                text-slate-400
                                            "
                                        >
                                            Recurring issue
                                        </p>

                                    </div>



                                    <!-- OCCURRENCE COUNT -->

                                    <div
                                        class="
                                            shrink-0
                                            rounded-md
                                            bg-slate-100
                                            px-2
                                            py-1
                                            text-[11px]
                                            font-semibold
                                            text-slate-600
                                        "
                                    >
                                        {{ $problem->occurrences }}×
                                    </div>

                                </div>


                            @empty

                                <!-- ================================================= -->
                                <!-- EMPTY STATE -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-200
                                        px-5
                                        py-8
                                        text-center
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-9
                                            w-9
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="circle-check"
                                            class="h-4 w-4"
                                        ></i>
                                    </div>


                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        No recurring problems
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            max-w-[240px]
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        No frequently reported issues have been recorded for
                                        this room.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>

                <div
                    x-show="tab === 'schedule'"
                    x-cloak
                >

                    <!-- ===================================================== -->
                    <!-- UPCOMING MAINTENANCE -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Upcoming Maintenance
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Scheduled maintenance activities for this room.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="calendar-days"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- SCHEDULE LIST -->
                        <!-- ================================================= -->

                        <div class="mt-4">

                            @forelse ($room->monitoring["schedules"] as $schedule)

                                <article
                                    class="
                                        flex
                                        items-start
                                        gap-3
                                        border-b
                                        border-slate-100
                                        py-4

                                        first:pt-0
                                        last:border-b-0
                                        last:pb-0
                                    "
                                >


                                    <!-- ================================================= -->
                                    <!-- DATE BLOCK -->
                                    <!-- ================================================= -->

                                    <div
                                        class="
                                            flex
                                            h-12
                                            w-12
                                            shrink-0
                                            flex-col
                                            items-center
                                            justify-center
                                            rounded-lg
                                            border
                                            border-slate-200
                                            bg-slate-50
                                        "
                                    >

                                        <!-- DAY -->

                                        <span
                                            class="
                                                text-base
                                                font-semibold
                                                leading-none
                                                text-slate-900
                                            "
                                        >
                                            {{
                                                \Carbon\Carbon::parse(
                                                    $schedule->maintenance_schedule_next_date
                                                )->format("d")
                                            }}
                                        </span>


                                        <!-- MONTH -->

                                        <span
                                            class="
                                                mt-1
                                                text-[9px]
                                                font-semibold
                                                uppercase
                                                tracking-wide
                                                text-slate-400
                                            "
                                        >
                                            {{
                                                \Carbon\Carbon::parse(
                                                    $schedule->maintenance_schedule_next_date
                                                )->format("M")
                                            }}
                                        </span>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- SCHEDULE INFORMATION -->
                                    <!-- ================================================= -->

                                    <div class="min-w-0 flex-1">

                                        <!-- TITLE -->

                                        <h4
                                            class="
                                                truncate
                                                text-sm
                                                font-medium
                                                text-slate-800
                                            "
                                        >
                                            {{ $schedule->maintenance_schedule_title }}
                                        </h4>



                                        <!-- EQUIPMENT -->

                                        <div
                                            class="
                                                mt-1.5
                                                flex
                                                items-center
                                                gap-1.5
                                                text-xs
                                                text-slate-500
                                            "
                                        >

                                            <i
                                                data-lucide="wrench"
                                                class="
                                                    h-3
                                                    w-3
                                                    shrink-0
                                                    text-slate-400
                                                "
                                            ></i>


                                            <span class="truncate">
                                                {{ $schedule->equipment_name }}
                                            </span>

                                        </div>



                                        <!-- STATUS -->

                                        <div class="mt-2">

                                            <span
                                                class="
                                                    inline-flex
                                                    items-center
                                                    gap-1.5
                                                    rounded-md
                                                    bg-blue-50
                                                    px-2
                                                    py-1
                                                    text-[10px]
                                                    font-medium
                                                    text-[#005EA6]
                                                "
                                            >

                                                <span
                                                    class="
                                                        h-1.5
                                                        w-1.5
                                                        rounded-full
                                                        bg-[#005EA6]
                                                    "
                                                ></span>


                                                {{ $schedule->maintenance_schedule_status }}

                                            </span>

                                        </div>

                                    </div>

                                </article>


                            @empty

                                <!-- ================================================= -->
                                <!-- EMPTY STATE -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-200
                                        px-5
                                        py-8
                                        text-center
                                    "
                                >

                                    <!-- EMPTY STATE ICON -->

                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >

                                        <i
                                            data-lucide="calendar-check"
                                            class="h-5 w-5"
                                        ></i>

                                    </div>


                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        No upcoming maintenance
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            max-w-[240px]
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        There are no active maintenance schedules for equipment
                                        in this room.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>

                <div
                    x-show="tab === 'history'"
                    x-cloak
                >

                    <!-- ===================================================== -->
                    <!-- ROOM ACTIVITY -->
                    <!-- ===================================================== -->

                    <div>

                        <!-- SECTION HEADER -->

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="text-sm font-semibold text-slate-900">
                                    Room Activity
                                </h3>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    Recent changes and events recorded for this room.
                                </p>

                            </div>


                            <!-- HEADER ICON -->

                            <div
                                class="
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    bg-slate-50
                                    text-slate-400
                                "
                            >
                                <i
                                    data-lucide="history"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                        </div>



                        <!-- ================================================= -->
                        <!-- ACTIVITY TIMELINE -->
                        <!-- ================================================= -->

                        <div class="mt-5">

                            @forelse ($room->monitoring["history"] as $history)

                                <div
                                    class="
                                        group
                                        relative
                                        flex
                                        gap-4
                                        pb-6

                                        last:pb-0
                                    "
                                >

                                    <!-- ================================================= -->
                                    <!-- TIMELINE -->
                                    <!-- ================================================= -->

                                    <div
                                        class="
                                            relative
                                            flex
                                            w-5
                                            shrink-0
                                            justify-center
                                        "
                                    >

                                        <!-- TIMELINE LINE -->

                                        <div
                                            class="
                                                absolute
                                                bottom-0
                                                top-3
                                                w-px
                                                bg-slate-200

                                                group-last:hidden
                                            "
                                        ></div>


                                        <!-- TIMELINE DOT -->

                                        <div
                                            class="
                                                relative
                                                z-10
                                                mt-1
                                                flex
                                                h-3
                                                w-3
                                                items-center
                                                justify-center
                                                rounded-full
                                                bg-white
                                                ring-2
                                                ring-[#005EA6]
                                            "
                                        >

                                            <div
                                                class="
                                                    h-1
                                                    w-1
                                                    rounded-full
                                                    bg-[#005EA6]
                                                "
                                            ></div>

                                        </div>

                                    </div>



                                    <!-- ================================================= -->
                                    <!-- ACTIVITY INFORMATION -->
                                    <!-- ================================================= -->

                                    <div
                                        class="
                                            min-w-0
                                            flex-1
                                            border-b
                                            border-slate-100
                                            pb-5

                                            group-last:border-b-0
                                            group-last:pb-0
                                        "
                                    >


                                        <!-- ACTIVITY TITLE -->

                                        <p
                                            class="
                                                text-sm
                                                font-medium
                                                text-slate-800
                                            "
                                        >
                                            {{ $history->activity_title }}
                                        </p>



                                        <!-- ACTIVITY DESCRIPTION -->

                                        <p
                                            class="
                                                mt-1
                                                text-xs
                                                leading-5
                                                text-slate-500
                                            "
                                        >
                                            {{ $history->activity_description }}
                                        </p>



                                        <!-- ACTIVITY DATE -->

                                        <div
                                            class="
                                                mt-2
                                                flex
                                                items-center
                                                gap-1.5
                                                text-[11px]
                                                text-slate-400
                                            "
                                        >

                                            <i
                                                data-lucide="clock-3"
                                                class="h-3 w-3 shrink-0"
                                            ></i>


                                            <span>
                                                {{
                                                    \Carbon\Carbon::parse(
                                                        $history->created_at
                                                    )->format("M d, Y · h:i A")
                                                }}
                                            </span>

                                        </div>

                                    </div>

                                </div>


                            @empty

                                <!-- ================================================= -->
                                <!-- EMPTY STATE -->
                                <!-- ================================================= -->

                                <div
                                    class="
                                        flex
                                        flex-col
                                        items-center
                                        justify-center
                                        rounded-xl
                                        border
                                        border-dashed
                                        border-slate-200
                                        px-5
                                        py-8
                                        text-center
                                    "
                                >

                                    <!-- EMPTY ICON -->

                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-slate-50
                                            text-slate-400
                                        "
                                    >
                                        <i
                                            data-lucide="history"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>


                                    <!-- EMPTY TITLE -->

                                    <p
                                        class="
                                            mt-3
                                            text-sm
                                            font-medium
                                            text-slate-700
                                        "
                                    >
                                        No room activity
                                    </p>


                                    <!-- EMPTY DESCRIPTION -->

                                    <p
                                        class="
                                            mt-1
                                            max-w-[240px]
                                            text-xs
                                            leading-5
                                            text-slate-400
                                        "
                                    >
                                        Changes and events recorded for this room will appear
                                        here.
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>
            </div>
        </div>
    @endforeach
</aside>

<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data(
            "equipmentCard",
            (
                equipmentId,

                roomId,
            ) => ({
                roomId: roomId,

                menu: false,
                saving: false,

                panel: "",

                transferRoom: "",
                archiveReason: "",

                equipmentId: equipmentId,

                form: null,

                equipment() {
                    const layout = window.infrastructure.roomLayout;

                    if (layout.open && layout.id === this.roomId) {
                        return layout.equipment.find(
                            (equipment) => equipment.id === this.equipmentId,
                        );
                    }

                    const room = window.infrastructure.roomCatalog.find(
                        (room) => room.id === this.roomId,
                    );

                    if (!room) {
                        return null;
                    }

                    return room.equipment.find(
                        (equipment) => equipment.id === this.equipmentId,
                    );
                },

                // =====================================
                // Keep edit form synced with live equipment
                // Place BELOW equipment()
                // =====================================

                updateEquipmentPlacement() {
                    if (!this.form) {
                        return;
                    }

                    const [x, y] = window.infrastructure.zonePosition(
                        this.form.location,
                    );

                    this.form.x = x;

                    this.form.y = y;

                    this.form.placement_zone = this.form.location;
                },

                // inside Alpine.data('equipmentCard')

                saveEquipment() {
                    this.saving = true;

                    const equipment = this.equipment();

                    fetch(
                        `/maintenance/infrastructure/equipment/${this.equipmentId}`,
                        {
                            method: "PUT",

                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": document.querySelector(
                                    'meta[name="csrf-token"]',
                                ).content,
                            },

                            body: JSON.stringify({
                                equipment_name: equipment.name,

                                equipment_category_id: equipment.category,

                                equipment_quantity: equipment.quantity,

                                equipment_condition_status: equipment.condition,

                                equipment_current_location: equipment.location,

                                equipment_placement_zone: equipment.location,

                                equipment_position_x: equipment.x,

                                equipment_position_y: equipment.y,
                            }),
                        },
                    )
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error();
                            }

                            return res.json();
                        })

                        .then(async () => {
                            this.panel = "";

                            this.saving = false;

                            await window.infrastructure.refreshRoomEquipment(
                                this.roomId,
                            );
                        })

                        .catch(() => {
                            this.saving = false;

                            alert("Unable to save equipment.");
                        });
                },

                transferEquipment() {
                    if (this.transferRoom === "") {
                        alert("Please select a destination room.");

                        return;
                    }

                    fetch(
                        `/maintenance/infrastructure/equipment/${this.equipmentId}/transfer`,
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
                                room_id: this.transferRoom,
                            }),
                        },
                    )
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error();
                            }

                            this.panel = "";

                            return window.infrastructure.refreshRoomEquipment(
                                this.roomId,
                            );
                        })

                        .catch(() => {
                            alert("Transfer failed.");
                        });
                },

                archiveEquipment() {
                    fetch(
                        `/maintenance/infrastructure/equipment/${this.equipmentId}`,
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
                                reason: this.archiveReason,
                            }),
                        },
                    )
                        .then((res) => {
                            if (!res.ok) {
                                throw new Error();
                            }

                            this.panel = "";

                            return window.infrastructure.refreshRoomEquipment(
                                this.roomId,
                            );
                        })

                        .catch(() => {
                            alert("Archive failed.");
                        });
                },

                init() {
                    // Initial load
                    this.form = this.equipment();

                    // Keep following future object replacements
                    this.$watch(
                        () => this.equipment(),

                        (equipment) => {
                            if (!equipment) {
                                return;
                            }

                            if (!this.form) {
                                this.form = equipment;

                                return;
                            }

                            Object.assign(this.form, equipment);
                        },
                    );
                },
            }),
        );
    });
</script>
