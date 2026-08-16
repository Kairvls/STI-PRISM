<div class="h-[700px] overflow-y-auto rounded-3xl bg-white p-6 shadow-lg">
    <h2 class="mb-6 text-2xl font-bold text-black">Room Monitoring</h2>

    <div x-show="selectedRoom == null">
        <div
            class="flex h-full items-center justify-center text-center text-black text-slate-400"
        >
            Select a room on the map.
        </div>
    </div>

    @foreach ($rooms as $room)
        <div
            x-show="
                selectedRoom ==
                {{ $room->room_id }}
            "
        >
            <h3 class="mb-2 text-2xl font-bold text-black">
                {{ $room->room_name }}
            </h3>

            <p class="mb-6 text-sm text-slate-500">
                {{
                    $room->room_type ??
                        "No Room Type"
                }}
            </p>

            <div class="space-y-4">
                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Equipment Count</div>

                    <div class="mt-2 text-2xl font-bold">0</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Active Reports</div>

                    <div class="mt-2 text-2xl font-bold">0</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Frequent Problems</div>

                    <div class="mt-2 text-slate-500">No Data</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Maintenance Schedule</div>

                    <div class="mt-2 text-slate-500">No Schedule</div>
                </div>

                <div class="rounded-xl bg-slate-50 p-4 text-black">
                    <div class="font-semibold">Transfer History</div>

                    <div class="mt-2 text-slate-500">No Transfers</div>
                </div>
            </div>
        </div>

    @endforeach
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

                                class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#0b1220]/70"

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

                                class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#0b1220]/70"

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