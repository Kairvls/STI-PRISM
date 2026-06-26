<aside
    class="min-h-[720px] overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-900/5"
>
    <div
        x-show="selectedRoom === null"
        class="flex h-full min-h-[720px] flex-col"
    >
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
                <p class="mt-5 text-sm font-bold text-slate-700">Choose a room on the map</p>
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

                    condition:'Good',

                    location:''

                },

                async storeEquipment(){

                    this.saving=true;

                    try{

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

                                    equipment_condition_status:this.addForm.condition,

                                    equipment_current_location:this.addForm.location

                                })

                            }

                        );

                        if(!response.ok){

                            throw new Error();

                        }

                        this.addForm={

                            room_id:this.addForm.room_id,

                            name:'',

                            category:'',

                            quantity:1,

                            condition:'Good',

                            location:''

                        };

                        this.addEquipmentModal=false;

                        location.reload();

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

                        this.editRoomModal = false;

                        location.reload();

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

                        location.reload();

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

                        location.reload();

                    }

                    catch(e){

                        alert('Unable to transfer asset.');

                    }

                    finally{

                        this.roomSaving=false;

                    }

                }

            }"
            class="min-h-[720px]"
        >
            <div class="relative overflow-hidden bg-slate-950 p-6 text-white">
                <div
                    class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-[#005EA6]/30 blur-2xl"
                ></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#FFF200]">{{ $room->floor->building->building_name ?? "STI Ormoc" }} · {{ $room->floor->floor_level }}</p>
                        <h2 class="mt-2 text-2xl font-extrabold">
                            {{ $room->room_name }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-400">{{
                            $room->room_type ?:
                                "Unclassified room"
                        }}</p>
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
                        ><span class="text-[10px] text-slate-400">Assets</span>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->monitoring[
                                "active_reports"
                            ]
                        }}</b
                        ><span class="text-[10px] text-slate-400"
                            >Active tickets</span
                        >
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->equipment
                                ->where("equipment_condition_status", "Good")
                                ->sum("equipment_quantity")
                        }}</b
                        ><span class="text-[10px] text-slate-400">Healthy</span>
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

            <div class="max-h-[475px] overflow-y-auto p-5">
                <div
                    x-show="tab === 'overview'"
                    x-cloak
                    class="space-y-5"
                >

                    <div class="rounded-2xl border border-slate-200 p-5">

                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">

                            Room Summary

                        </h3>

                        <dl class="mt-4 space-y-4">

                            <div class="flex justify-between">

                                <dt class="text-sm text-slate-500">

                                    Room

                                </dt>

                                <dd class="font-bold">

                                    {{ $room->room_name }}

                                </dd>

                            </div>

                            <div class="flex justify-between">

                                <dt class="text-sm text-slate-500">

                                    Floor

                                </dt>

                                <dd class="font-bold">

                                    {{ $room->floor->floor_level }}

                                </dd>

                            </div>

                            <div class="flex justify-between">

                                <dt class="text-sm text-slate-500">

                                    Type

                                </dt>

                                <dd class="font-bold">

                                    {{ $room->room_type }}

                                </dd>

                            </div>

                            <div class="flex justify-between">

                                <dt class="text-sm text-slate-500">

                                    Status

                                </dt>

                                <dd>

                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">

                                        {{ $room->room_status }}

                                    </span>

                                </dd>

                            </div>

                        </dl>

                    </div>

                    <div class="grid grid-cols-2 gap-3">

                        <div class="rounded-2xl bg-blue-50 p-4">

                            <p class="text-xs font-bold uppercase text-blue-500">

                                Assets

                            </p>

                            <h2 class="mt-2 text-3xl font-black text-blue-700">

                                {{ $room->monitoring['equipment_quantity'] }}

                            </h2>

                        </div>

                        <div class="rounded-2xl bg-emerald-50 p-4">

                            <p class="text-xs font-bold uppercase text-emerald-500">

                                Reports

                            </p>

                            <h2 class="mt-2 text-3xl font-black text-emerald-700">

                                {{ $room->monitoring['active_reports'] }}

                            </h2>

                        </div>

                    </div>

                    <div class="rounded-2xl border border-slate-200 p-5">

                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">

                            Equipment Health

                        </h3>

                        <div class="mt-5 space-y-3">

                            <div class="flex justify-between">

                                <span>

                                    Good

                                </span>

                                <span class="font-black text-emerald-600">

                                    {{ $room->monitoring['equipment_good'] }}

                                </span>

                            </div>

                            <div class="flex justify-between">

                                <span>

                                    Under Maintenance

                                </span>

                                <span class="font-black text-amber-500">

                                    {{ $room->monitoring['equipment_maintenance'] }}

                                </span>

                            </div>

                            <div class="flex justify-between">

                                <span>

                                    Damaged

                                </span>

                                <span class="font-black text-red-600">

                                    {{ $room->monitoring['equipment_damaged'] }}

                                </span>

                            </div>

                        </div>

                    </div>

                    {{-- QUICK ACTIONS --}}

                    <div class="rounded-2xl border border-slate-200 bg-white">

                        <div class="border-b border-slate-100 px-5 py-4">

                            <h3 class="text-xs font-extrabold uppercase tracking-[.2em] text-slate-400">

                                Quick Actions

                            </h3>

                        </div>

                        <div class="grid grid-cols-2 gap-3 p-5">

                            <button
                                type="button"
                                @click="addEquipmentModal=true"
                                
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-[#005EA6] hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >

                                <div class="flex items-center gap-3">

                                    <div class="rounded-xl bg-blue-100 p-2 text-[#005EA6]">

                                        <i data-lucide="plus" class="h-5 w-5"></i>

                                    </div>

                                    <div>

                                        <h4 class="text-sm font-bold">

                                            Add Equipment

                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">

                                            Provision new equipment into this room.

                                        </p>

                                    </div>

                                </div>

                            </button>

                            <button
                                type="button"
                                @click="editRoomModal = true"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-amber-500 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >

                                <div class="flex items-center gap-3">

                                    <div class="rounded-xl bg-amber-100 p-2 text-amber-600">

                                        <i data-lucide="pencil" class="h-5 w-5"></i>

                                    </div>

                                    <div>

                                        <h4 class="text-sm font-bold">

                                            Edit Room

                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">

                                            Modify room information and layout.

                                        </p>

                                    </div>

                                </div>

                            </button>

                            <button
                                type="button"
                                @click="transferAssetsModal=true"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-emerald-500 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >

                                <div class="flex items-center gap-3">

                                    <div class="rounded-xl bg-emerald-100 p-2 text-emerald-600">

                                        <i data-lucide="arrow-right-left" class="h-5 w-5"></i>

                                    </div>

                                    <div>

                                        <h4 class="text-sm font-bold">

                                            Transfer Assets

                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">

                                            Move equipment to another room.

                                        </p>

                                    </div>

                                </div>

                            </button>

                            <button
                                type="button"
                                title="Coming in Phase 3.3"
                                class="group rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-left transition hover:border-violet-500 hover:bg-violet-50 disabled:cursor-not-allowed disabled:opacity-70"
                            >

                                <div class="flex items-center gap-3">

                                    <div class="rounded-xl bg-violet-100 p-2 text-violet-600">

                                        <i data-lucide="history" class="h-5 w-5"></i>

                                    </div>

                                    <div>

                                        <h4 class="text-sm font-bold">

                                            View History

                                        </h4>

                                        <p class="mt-1 text-[11px] text-slate-500">

                                            Review room activity and changes.

                                        </p>

                                    </div>

                                </div>

                            </button>

                        </div>

                        <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">

                            <div class="flex items-center justify-between">

                                <span class="text-xs font-semibold text-slate-500">

                                    Blueprint Editor

                                </span>

                                <span class="rounded-full bg-[#005EA6]/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-[#005EA6]">

                                    Equipment CRUD

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Add Equipment Modal -->

                <div

                    x-show="addEquipmentModal"

                    x-transition

                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"

                >

                    <div

                       @click.outside="

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

                        class="w-full max-w-lg rounded-3xl bg-white p-6"

                    >

                        <h2 class="text-xl font-bold">

                            Add Equipment

                        </h2>

                        <div class="mt-5 space-y-4">

                            <input
                                x-model="addForm.name"
                                type="text"
                                placeholder="Equipment Name"
                                class="w-full rounded-xl border p-3"
                            >

                            <select
                                x-model="addForm.category"
                                class="w-full rounded-xl border p-3"
                            >

                                <option value="">

                                    Select Category

                                </option>

                                @foreach($categories as $category)

                                    <option value="{{ $category->equipment_category_id }}">

                                        {{ $category->equipment_category_name }}

                                    </option>

                                @endforeach

                            </select>

                            <input
                                x-model="addForm.quantity"
                                type="number"
                                min="1"
                                class="w-full rounded-xl border p-3"
                            >

                            <select
                                x-model="addForm.condition"
                                class="w-full rounded-xl border p-3"
                            >

                                <option>Good</option>
                                <option>Under Maintenance</option>
                                <option>Damaged</option>

                            </select>

                            <input
                                x-model="addForm.location"
                                type="text"
                                placeholder="Location"
                                class="w-full rounded-xl border p-3"
                            >

                        </div>

                        <div class="mt-6 flex justify-end gap-3">

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

                                class="rounded-xl border px-5 py-2"

                            >

                                Cancel

                            </button>

                            <button

                                @click="storeEquipment()"

                                :disabled="saving"

                                class="rounded-xl bg-[#005EA6] px-5 py-2 text-white"

                            >

                                <span x-text="saving ? 'Saving...' : 'Save'"></span>

                            </button>

                        </div>

                    </div>

                </div>

                <!-- ============================== -->
                <!-- Edit Room Modal -->
                <!-- Place AFTER Add Equipment Modal -->
                <!-- ============================== -->

                <div

                    x-show="editRoomModal"

                    x-transition

                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"

                >

                    <div

                        @click.outside="editRoomModal=false"

                        class="w-full max-w-lg rounded-3xl bg-white p-6"

                    >

                        <div class="flex items-center justify-between">

                            <div>

                                <h2 class="text-2xl font-bold">

                                    Edit Room

                                </h2>

                                <p class="mt-1 text-sm text-slate-500">

                                    Update this room's information.

                                </p>

                            </div>

                            <button

                                @click="editRoomModal=false"

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

                                >

                            </div>

                            <div>

                                <label class="mb-2 block text-sm font-semibold">

                                    Room Type

                                </label>

                                <select

                                    x-model="roomForm.type"

                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#005EA6] focus:outline-none"

                                >

                                    <option value="Lecture Room">Lecture Room</option>
                                    <option value="Computer Laboratory">Computer Laboratory</option>
                                    <option value="HM Room">HM Room  / Bar</option>
                                    <option value="Hotel Room Simulation">Hotel Room Simulation</option>

                                    <option value="Faculty Room">Faculty Room</option>
                                    <option value="Office">Office</option>
                                    <option value="Library">Library</option>
                                    <option value="School Clinic">School Clinic</option>

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

                                @click="editRoomModal=false"

                                class="rounded-xl border border-slate-300 w-full py-2.5"

                            >

                                Cancel

                            </button>

                            <button

                                @click="saveRoom()"

                                :disabled="roomSaving"

                                class="rounded-xl bg-[#005EA6] w-full py-2.5 font-semibold text-white hover:bg-[#004b86] disabled:opacity-60"

                            >

                                <span

                                    x-text="roomSaving ? 'Saving...' : 'Save Changes'"

                                ></span>

                            </button>

                        </div>

                        <hr class="my-6">

                        <div class="rounded-2xl border border-red-200 bg-red-50 p-5">

                            <h3 class="font-bold text-red-700">

                                Archive Room

                            </h3>

                            <p class="mt-2 text-sm text-red-600">

                                This removes the room from the active blueprint.

                                Reports remain in history.

                            </p>

                            <button

                               @click="

                                    editRoomModal = false;

                                    archiveRoomModal = true;

                                "

                                :disabled="roomSaving"

                                class="mt-5 w-full rounded-xl bg-red-600 py-3 font-semibold text-white hover:bg-red-700"

                            >

                                Archive Room

                            </button>

                        </div>

                    </div>

                </div>

                <!-- ===================================== -->
                <!-- Archive Room Confirmation Modal -->
                <!-- Place AFTER Edit Room Modal -->
                <!-- ===================================== -->

                <div

                    x-show="archiveRoomModal"

                    x-transition

                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"

                >

                    <div

                        @click.outside="archiveRoomModal=false"

                        class="w-full max-w-lg rounded-3xl bg-white p-6"

                    >

                        <div class="flex items-center justify-between">

                            <h2 class="text-2xl font-bold text-red-600">

                                Archive Room

                            </h2>

                            <button

                                @click="archiveRoomModal=false"

                                class="rounded-lg p-2 hover:bg-slate-100"

                            >

                                <i data-lucide="x" class="h-5 w-5"></i>

                            </button>

                        </div>

                        <p class="mt-4 text-sm text-slate-600">

                            You are about to archive this room.

                            Live equipment and schedules will be removed.

                            Historical reports will remain available.

                        </p>

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

                                @click="archiveRoomModal=false"

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

                                    x-text="roomSaving ? 'Archiving...' : 'Archive Room'"

                                ></span>

                            </button>

                        </div>

                    </div>

                </div>

                <div

                    x-show="transferAssetsModal"

                    x-transition

                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"

                >

                    <div

                        @click.outside="transferAssetsModal=false"

                        class="w-full max-w-lg rounded-3xl bg-white p-6"

                    >

                        <h2 class="text-2xl font-bold">

                            Transfer Asset

                        </h2>

                        <p class="mt-2 text-sm text-slate-500">

                            Select the equipment and destination room.

                        </p>

                        <div class="mt-6">

                            <label class="mb-2 block text-sm font-semibold">

                                Equipment

                            </label>

                            <select

                                x-model="selectedEquipment"

                                class="w-full rounded-xl border border-slate-300 px-4 py-3"

                            >

                                <option value="">

                                    Select Equipment

                                </option>

                                @foreach($room->equipment as $equipment)

                                    <option value="{{ $equipment->equipment_id }}">

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

                                <option value="">

                                    Select Room

                                </option>

                                @foreach($rooms as $destination)

                                    @if($destination->room_id != $room->room_id)

                                        <option value="{{ $destination->room_id }}">

                                            {{ $destination->room_name }}

                                        </option>

                                    @endif

                                @endforeach

                            </select>

                        </div>

                        <div class="mt-8 flex justify-end gap-3">

                            <button

                                @click="transferAssetsModal=false"

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
                
                <div
                    x-show="tab === 'equipment'"
                    x-data="{

                        search:'',

                        filter:'all'

                    }"

                    class="space-y-4"
                >
                <div class="rounded-2xl border border-slate-200 bg-white p-4">

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

                            >

                        </div>

                        <select

                            x-model="filter"

                            class="rounded-xl border border-slate-200 px-3 text-sm"

                        >

                            <option value="all">

                                All

                            </option>

                            <option value="good">

                                Good

                            </option>

                            <option value="maintenance">

                                Maintenance

                            </option>

                            <option value="damaged">

                                Damaged

                            </option>

                        </select>

                    </div>

                </div>
                    @forelse ($room->equipment as $item)
                        @php
                            $healthy = $item->equipment_condition_status === "Good";
                            $maintenance = $item->equipment_condition_status === "Under Maintenance";
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

                                    )

                            "
                                class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-slate-200 hover:bg-white hover:shadow-md"
                            >
                            <!-- Equipment Alpine Component -->
                            <div x-data="equipmentCard({

                                id:{{ $item->equipment_id }},

                                name:@js($item->equipment_name),

                                quantity:@js($item->equipment_quantity),

                                category:@js($item->equipment_category_id),

                                condition:@js($item->equipment_condition_status),

                                location:@js($item->equipment_current_location)

                            })">

                            <!-- Equipment Header -->

                                <div class="flex items-start justify-between gap-3">

                                    <div class="flex gap-3">

                                        <span
                                            class="mt-1 h-3 w-3 rounded-full
                                            {{
                                                $healthy
                                                    ? 'bg-emerald-500'
                                                    : ($maintenance
                                                        ? 'bg-amber-400'
                                                        : 'bg-red-500')
                                            }}"
                                        ></span>

                                        <div>

                                            <h3 class="text-sm font-bold text-slate-800">

                                                {{ $item->equipment_name }}

                                            </h3>

                                            <p class="mt-1 text-[11px] text-slate-500">

                                                {{ $item->category->equipment_category_name ?? 'Uncategorized' }}

                                            </p>

                                        </div>

                                    </div>

                                    <div class="relative">

                                        <button

                                            @click="menu=!menu"

                                            class="rounded-lg p-2 hover:bg-slate-100"

                                        >

                                            <i data-lucide="ellipsis-vertical" class="h-4 w-4"></i>

                                        </button>

                                        <div
                                            x-show="menu"
                                            @click.outside="menu=false"
                                            x-transition
                                            class="absolute right-2 top-8 z-20 w-44 max-h-[132px] overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
                                        >
                                            <button
                                                @click="
                                                    details=!details;
                                                    menu=false;
                                                "
                                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm hover:bg-slate-50"
                                            >
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                                View Details
                                            </button>

                                            <button

                                                @click="

                                                    editing=true;

                                                    menu=false;

                                                "

                                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm hover:bg-slate-50"

                                            >

                                                <i data-lucide="square-pen" class="h-4 w-4"></i>

                                                Edit Equipment

                                            </button>

                                            <button
                                                @click="

                                                    transferRoom='';

                                                    transferModal=true;

                                                    menu=false;

                                                "
                                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm hover:bg-slate-50"
                                            >
                                                <i data-lucide="move-right" class="h-4 w-4"></i>
                                                Transfer
                                            </button>

                                            <button

                                                @click="

                                                    archiveReason='';

                                                    archiveModal=true;

                                                    menu=false;

                                                "

                                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-red-600 hover:bg-red-50"

                                            >

                                                <i data-lucide="archive" class="h-4 w-4"></i>

                                                Archive

                                            </button>
                                        </div>

                                    </div>

                                </div>
                            
                            

                                <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4">

                                    <div>

                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                            Asset Tag

                                        </p>

                                        <p class="mt-1 font-semibold">

                                            {{ $item->equipment_asset_tag ?: 'N/A' }}

                                        </p>

                                    </div>

                                    <div>

                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                            Quantity

                                        </p>

                                        <p class="mt-1 font-semibold">

                                            {{ $item->equipment_quantity }}

                                        </p>

                                    </div>

                                    <div>

                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                            Condition

                                        </p>

                                        <span
                                            class="mt-1 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold
                                            {{
                                                $item->equipment_condition_status === 'Good'
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : ($item->equipment_condition_status === 'Under Maintenance'
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : 'bg-red-100 text-red-700')
                                            }}"
                                        >
                                            {{ $item->equipment_condition_status }}
                                        </span>

                                    </div>

                                    <div>

                                        <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                            Placement

                                        </p>

                                        <p class="mt-1">

                                            {{

                                                $item->equipment_placement_zone

                                                ?:

                                                ($item->equipment_current_location ?: 'Not plotted')

                                            }}

                                        </p>

                                    </div>

                                </div>

                                <div

                                    x-show="details"

                                    x-collapse

                                    class="mt-5 border-t border-slate-200 pt-5"

                                >

                                    <h4
                                        class="text-xs font-extrabold uppercase tracking-[.2em] text-slate-400"
                                    >

                                        Asset Details

                                    </h4>

                                    <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-5">

                                        <div>

                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                                Asset Tag

                                            </p>

                                            <p class="mt-1 font-semibold">

                                                {{ $item->equipment_asset_tag ?: 'Not Assigned' }}

                                            </p>

                                        </div>

                                        <div>

                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                                Serial Number

                                            </p>

                                            <p class="mt-1 font-semibold">

                                                {{ $item->equipment_serial_number ?? 'Unavailable' }}

                                            </p>

                                        </div>

                                        <div>

                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                                Purchase Date

                                            </p>

                                            <p class="mt-1">

                                                {{ $item->equipment_purchase_date ?? 'Unknown' }}

                                            </p>

                                        </div>

                                        <div>

                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                                Warranty

                                            </p>

                                            <p class="mt-1">

                                                {{ $item->equipment_warranty_until ?? 'Unknown' }}

                                            </p>

                                        </div>

                                        <div>

                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                                Supplier

                                            </p>

                                            <p class="mt-1">

                                                {{ $item->equipment_supplier ?? 'Not Assigned' }}

                                            </p>

                                        </div>

                                        <div>

                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">

                                                Assigned Technician

                                            </p>

                                            <p class="mt-1">

                                                {{ $item->equipment_assigned_to ?? 'Unassigned' }}

                                            </p>

                                        </div>

                                    </div>

                                    <div class="mt-6 rounded-2xl bg-slate-50 p-4">

                                        <div class="flex items-center justify-between">

                                            <div>

                                                <p class="text-xs font-bold text-slate-700">

                                                    QR Code

                                                </p>

                                                <p class="text-[11px] text-slate-400">

                                                    Coming in Asset Management Phase

                                                </p>

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

                                    x-show="editing"

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

                                            <label class="mb-1 block text-xs font-semibold text-slate-600">

                                                Equipment Name

                                            </label>

                                            <input

                                                x-model="form.name"

                                                type="text"

                                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5"

                                            >

                                        </div>

                                        <div>

                                            <label class="mb-1 block text-xs font-semibold text-slate-600">

                                                Category

                                            </label>

                                            <select

                                                x-model="form.category"

                                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5"

                                            >

                                                <option value="">

                                                    Select Category

                                                </option>

                                                @foreach($categories as $category)

                                                    <option value="{{ $category->equipment_category_id }}">

                                                        {{ $category->equipment_category_name }}

                                                    </option>

                                                @endforeach

                                            </select>

                                        </div>

                                        <div>

                                            <label class="mb-1 block text-xs font-semibold text-slate-600">

                                                Location

                                            </label>

                                            <input

                                                x-model="form.location"

                                                type="text"

                                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5"

                                            >

                                        </div>

                                        <div class="grid grid-cols-2 gap-4">

                                            <div>

                                                <label class="mb-1 block text-xs font-semibold text-slate-600">

                                                    Quantity

                                                </label>

                                                <input

                                                    x-model="form.quantity"

                                                    type="number"

                                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5"

                                                >

                                            </div>

                                            <div>

                                                <label class="mb-1 block text-xs font-semibold text-slate-600">

                                                    Condition

                                                </label>

                                                <select
                                                    x-model="form.condition"
                                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                                                >

                                                    <option
                                                        
                                                    >
                                                        Good
                                                    </option>

                                                    <option
                                                        
                                                    >
                                                        Under Maintenance
                                                    </option>

                                                    <option
                                                        
                                                    >
                                                        Damaged
                                                    </option>

                                                </select>

                                            </div>

                                        </div>

                                        <div class="flex justify-end gap-3">

                                            <button

                                                @click="editing=false"

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
                                                    x-text="saving ? 'Saving...' : 'Save Changes'"
                                                ></span>

                                            </button>

                                        </div>

                                    </div>

                                </div>

                                <!-- ============================== -->
                                <!-- Transfer Equipment Modal -->
                                <!-- Place BEFORE </article> -->
                                <!-- ============================== -->

                                <div

                                    x-show="transferModal"

                                    x-transition

                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"

                                >

                                    <div

                                        @click.outside="transferModal=false"

                                        class="w-full max-w-md rounded-3xl bg-white p-6"

                                    >

                                        <h3 class="text-xl font-bold">

                                            Transfer Equipment

                                        </h3>

                                        <p class="mt-2 text-sm text-slate-500">

                                            Select the destination room.

                                        </p>

                                        <select

                                            x-model="transferRoom"

                                            class="mt-6 w-full rounded-xl border border-slate-300 p-3"

                                        >

                                            <option value="">

                                                Select Room

                                            </option>

                                            @foreach($rooms as $destination)

                                                @if($destination->room_id != $room->room_id)

                                                    <option value="{{ $destination->room_id }}">

                                                        {{ $destination->room_name }}

                                                    </option>

                                                @endif

                                            @endforeach

                                        </select>

                                        <div class="mt-6 flex justify-end gap-3">

                                            <button

                                                @click="transferModal=false"

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

                                </div>


                                <!-- ============================== -->
                                <!-- Archive Equipment Modal -->
                                <!-- Place AFTER Transfer Modal -->
                                <!-- ============================== -->

                                <div

                                    x-show="archiveModal"

                                    x-transition

                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40"

                                >

                                    <div

                                        @click.outside="archiveModal=false"

                                        class="w-full max-w-md rounded-3xl bg-white p-6"

                                    >

                                        <h3 class="text-xl font-bold text-red-600">

                                            Archive Equipment

                                        </h3>

                                        <textarea

                                            x-model="archiveReason"

                                            rows="4"

                                            placeholder="Reason"

                                            class="mt-5 w-full rounded-xl border border-slate-300 p-3"

                                        ></textarea>

                                        <div class="mt-6 flex justify-end gap-3">

                                            <button

                                                @click="archiveModal=false"

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

                <div
                    x-show="tab === 'history'"
                    x-cloak
                >
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Room Activity
                    </h3>

                    <div class="mt-4 space-y-4">

                        @forelse($room->monitoring['history'] as $history)

                            <div
                                class="flex gap-4 rounded-2xl border border-slate-200 p-4"
                            >

                                <div
                                    class="mt-1 h-3 w-3 rounded-full bg-[#005EA6]"
                                ></div>

                                <div class="flex-1">

                                    <p class="font-semibold">

                                        {{ $history->title }}

                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">

                                        {{ $history->description }}

                                    </p>

                                    <p class="mt-2 text-[11px] text-slate-400">

                                        {{ $history->date }}

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

                                <p class="mt-4 text-sm text-slate-500">

                                    No room activity yet.

                                </p>

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

    Alpine.data('equipmentCard', (equipment) => ({

        menu:false,
        details:false,
        editing:false,
        saving:false,

        transferModal:false,
        archiveModal:false,

        transferRoom:'',
        archiveReason:'',

        equipmentId:equipment.id,

        form:{
            name:equipment.name,
            quantity:equipment.quantity,
            condition:equipment.condition,
            category:equipment.category,
            location:equipment.location
        },

        saveEquipment(){

            this.saving=true;

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

                    equipment_name:this.form.name,
                    equipment_category_id:this.form.category,
                    equipment_quantity:this.form.quantity,
                    equipment_condition_status:this.form.condition,
                    equipment_current_location:this.form.location

                })

            })

            .then(res=>{

                if(!res.ok){

                    throw new Error();

                }

                return res.json();

            })

            .then(()=>{

                this.editing=false;
                this.saving=false;
                location.reload();

            })

            .catch(()=>{

                this.saving=false;
                alert('Unable to save equipment.');

            });

        },

        transferEquipment(){

            if(this.transferRoom==='') return;

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

            .then(()=>{

                location.reload();

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

            .then(()=>{

                location.reload();

            })

            .catch(()=>{

                alert('Archive failed.');

            });

        }

    }));

});
</script>