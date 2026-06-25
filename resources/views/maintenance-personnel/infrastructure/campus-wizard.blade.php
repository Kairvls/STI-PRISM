<div
    x-show="wizardOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-6"
    role="dialog"
    aria-modal="true"
>
    <form
        method="POST"
        action="{{ route('maintenance.infrastructure.campus.store') }}"
        @click.outside="
            wizardOpen = false;

            await loadCampus();

            step = 1;
        "
        class="flex max-h-[80vh] w-full max-w-5xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl"
    >
        @csrf
        <header
            class="flex items-start justify-between border-b border-slate-100 px-6 py-5 sm:px-8"
        >
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#005EA6]">Unified creation cascade</p>
                <h2
                    class="mt-1 text-xl font-extrabold text-slate-950 sm:text-2xl"
                >
                    Campus configuration wizard
                </h2>
            </div>
            <button
                type="button"
                @click="
                    wizardOpen = false;

                    await loadCampus();

                    step = 1;
                "
                class="rounded-xl bg-slate-100 p-2 text-slate-500 hover:bg-slate-200"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </header>

        <div
            class="grid grid-cols-4 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-8"
        >
            <template
                x-for="
                    item in
                    [
                        { n: 1, l: 'Structure' },
                        { n: 2, l: 'Floors' },
                        { n: 3, l: 'Spaces & assets' },
                        { n: 4, l: 'Review' },
                    ]
                "
                :key="item.n"
            >
                <button
                    type="button"
                    @click="step = item.n"
                    class="flex items-center gap-2 py-1 text-left"
                    :class="step === item.n
                        ? 'text-[#005EA6]'
                        : 'text-slate-400'"
                >
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-extrabold"
                        :class="step === item.n
                            ? 'bg-[#005EA6] text-white'
                            : 'bg-white ring-1 ring-slate-200'"
                        x-text="item.n"
                    ></span>
                    <span
                        class="hidden text-[11px] font-bold sm:block"
                        x-text="item.l"
                    ></span>
                </button>
            </template>
        </div>

        @if ($errors->any())
            <div
                class="mx-6 mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700"
            >
                Please review the highlighted wizard information. {{ $errors->first() }}
            </div>
        @endif

        <div class="flex-1 overflow-y-auto p-6 sm:p-8">
            <section x-show="step === 1" x-transition>
                <div class="mx-auto max-w-xl py-8 text-center">
                    <span
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-[#005EA6]"
                        ><i data-lucide="building" class="h-8 w-8"></i
                    ></span>
                    <h3 class="mt-5 text-2xl font-extrabold">
                        Define the campus structure
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">Start with the building that will contain the monitored floors.</p>
                </div>
                <label
                    class="mx-auto block max-w-xl text-xs font-bold text-slate-700"
                    >Campus Name<input
                        name="building_name"
                        x-model="form.building_name"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#005EA6] focus:ring-[#005EA6]"
                        placeholder="e.g. STI College Ormoc Main Building"
                /></label>

                <div class="mx-auto mt-6 grid max-w-xl gap-5 md:grid-cols-2">

                <label class="text-xs font-bold text-slate-700">
                    Lowest Floor

                    <input
                        type="number"
                        min="1"
                        max="30"
                        x-model.number="form.minFloor"
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
                    >
                </label>

                <label class="text-xs font-bold text-slate-700">
                    Highest Floors

                    <input
                        type="number"
                        min="1"
                        max="30"
                        x-model.number="form.maxFloor"
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
                    >
                </label>

            </div>
            </section>

            <section x-show="step === 2" x-cloak>
                <div class="mb-5 flex items-center justify-between">
                    
                    <div class="mb-5">

                        <h3 class="text-xl font-extrabold">
                            Generated Floors
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            These floors were generated from the number of floors entered in Step 1.
                        </p>

                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <template x-for="(floor, fi) in form.floors" :key="fi"
                        ><div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FFF200] text-slate-900"
                                    ><i
                                        data-lucide="layers-3"
                                        class="h-5 w-5"
                                    ></i></span
                                ><input
                                    readonly
                                    x-model="floor.level"
                                    :name="`floors[${fi}][level]`"
                                    class="flex-1 rounded-xl border-slate-200 bg-slate-100 text-sm font-semibold"
                                />
                                <input
                                    type="hidden"
                                    :name="`floors[${fi}][id]`"
                                    :value="floor.id ?? ''"
                                >
                            </div>
                            <p
                                class="mt-3 text-xs text-slate-400"
                                x-text="
                                    `${floor.rooms.length} room workspace${floor.rooms.length === 1 ? '' : 's'}`
                                "
                            ></p></div
                    ></template>
                </div>
            </section>

            <section x-show="step === 3" x-cloak>
                <div class="mb-5">
                    <h3 class="text-xl font-extrabold">
                        Map rooms and initial assets
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Rooms start on an automatic grid and can be fine-tuned later in Layout Editor.</p>
                </div>
                <div class="space-y-5">
                    <template x-for="(floor, fi) in form.floors" :key="fi"
                        ><div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5"
                        >
                            <div class="mb-5 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full bg-[#005EA6]"></div>
                                <h4 
                                class="text-sm font-bold uppercase tracking-wider text-slate-700"
                                x-text="floor.level"
                                ></h4>
                            </div>
                            
                            <button
                                type="button"
                                @click="addRoom(fi)"
                                class="
                                inline-flex items-center gap-1.5 px-3 py-1.5
                                text-xs font-semibold rounded-lg text-[#005EA6] bg-blue-50/50
                                hover:bg-blue-100/70 hover:text-[#004b85] 
                                active:scale-95 transition-all duration-200
                                "
                            >
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                Add room
                            </button>
                            </div>
                            <div class="space-y-4">
                                <template
                                    x-for="(room, ri) in floor.rooms"
                                    :key="room.id"
                                    ><article
                                        class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100"
                                    >
                                        <div
                                            class="grid gap-3 md:grid-cols-[1.3fr_1fr_1fr_auto]"
                                        >
                                            <input
                                                type="hidden"
                                                :name="`floors[${fi}][rooms][${ri}][id]`"
                                                :value="room.id ?? ''"
                                            >
                                            <input
                                                x-model="room.name"
                                                :name="`floors[${fi}][rooms][${ri}][name]`"
                                                required
                                                placeholder="Room name / number"
                                                class="rounded-xl p-2 border border-slate-200 text-sm"
                                            /><select
                                                x-model="room.type"
                                                :name="`floors[${fi}][rooms][${ri}][type]`"
                                                class="rounded-xl p-2 border border-slate-200 text-sm focus:border-[#005EA6] focus:ring-1 focus:ring-blue-100"
                                            >
                                                <option value="Lecture Room">Lecture Room</option>
                                                <option value="Computer Laboratory">Computer Laboratory</option>
                                                <option value="HM Room">HM Room  / Bar</option>
                                                <option value="Hotel Room Simulation">Hotel Room Simulation</option>

                                                <option value="Faculty Room">Faculty Room</option>
                                                <option value="Office">Office</option>
                                                <option value="Library">Library</option>
                                                <option value="School Clinic">School Clinic</option>
                                            </select><select
                                                x-model="room.status"
                                                :name="`floors[${fi}][rooms][${ri}][status]`"
                                                class="rounded-xl p-2 border border-slate-200 text-sm"
                                            >
                                                <option>Normal</option>
                                                <option>
                                                    Maintenance Needed
                                                </option>
                                                <option>
                                                    Critical
                                                </option></select
                                            ><button
                                                x-show="floor.rooms.length > 1"
                                                type="button"
                                                @click="
                                                    floor.rooms.splice(ri, 1)
                                                "
                                                class="p-2 text-slate-400"
                                            >
                                                <i
                                                    data-lucide="trash-2"
                                                    class="h-4 w-4"
                                                ></i>
                                            </button>
                                        </div>
                                        <div
                                            class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4"
                                        >
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Initial AV / IT equipment</p>
                                            <button
                                                type="button"
                                                @click="addEquipment(fi, ri)"
                                                class="rounded-lg bg-slate-100 px-3 py-2 text-[11px] font-bold text-slate-700"
                                            >
                                                + Provision asset
                                            </button>
                                        </div>
                                        <div class="mt-3 space-y-3">
                                            <template
                                                x-for="
                                                    (eq, ei) in room.equipment
                                                "
                                                :key="eq.id"
                                                ><div
                                                    class="grid gap-3 rounded-xl border border-slate-100 p-3 lg:grid-cols-[1.2fr_1fr_90px_1fr_1.1fr_36px]"
                                                >
                                                    <input
                                                        type="hidden"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][id]`"
                                                        :value="eq.id ?? ''"
                                                    >
                                                    <input
                                                        x-model="eq.name"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][name]`"
                                                        required
                                                        placeholder="Equipment name"
                                                        class="rounded-lg p-2 border border-slate-200 text-xs"
                                                    />
                                                    <select
                                                        x-model="eq.category_id"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][category_id]`"
                                                        class="rounded-lg p-2 border border-slate-200 text-xs"
                                                    >
                                                        <option value="">
                                                            Category
                                                        </option>
                                                        @foreach ($categories as $category)
                                                            <option
                                                                value="{{ $category->equipment_category_id }}"
                                                            >
                                                                {{ $category->equipment_category_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        x-model="eq.quantity"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][quantity]`"
                                                        class="rounded-lg p-2 border border-slate-200 text-xs"
                                                        title="Quantity"
                                                    />
                                                    <select
                                                        x-model="eq.condition"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][condition]`"
                                                        class="rounded-lg p-2 border border-slate-200 text-xs"
                                                    >
                                                        <option>Good</option>
                                                        <option>Damaged</option>
                                                        <option>
                                                            Under Maintenance
                                                        </option>
                                                    </select>
                                                    <select
                                                        x-model="eq.zone"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][zone]`"
                                                        class="rounded-lg p-2 border border-slate-200 text-xs"
                                                    >
                                                        <option>
                                                            Front Wall
                                                        </option>
                                                        <option>
                                                            Center Ceiling
                                                        </option>
                                                        <option>
                                                            Left Row Pods
                                                        </option>
                                                        <option>
                                                            Right Row Pods
                                                        </option>
                                                        <option>
                                                            Rear Wall
                                                        </option>
                                                        <option>Storage</option>
                                                    </select>
                                                    <button
                                                        type="button"
                                                        @click="room.equipment.splice(ei, 1)"
                                                        class="flex items-center justify-center rounded-lg border border-red-200 p-2 text-red-500 transition hover:bg-red-50 hover:text-red-600"
                                                    >
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                    <div
                                                        class="relative col-span-full h-24 overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50"
                                                    >
                                                        <span
                                                            class="absolute left-1/2 top-1 -translate-x-1/2 text-[8px] font-bold uppercase tracking-widest text-slate-300"
                                                            >Front wall</span
                                                        >
                                                        <div
                                                            class="absolute inset-3 grid grid-cols-5 grid-rows-3 gap-1 opacity-40"
                                                        >
                                                            <template
                                                                x-for="n in 15"
                                                                ><i
                                                                    class="rounded  border border-slate-200 bg-white"
                                                                ></i
                                                            ></template>
                                                        </div>
                                                        <span
                                                            class="absolute h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#F39200] ring-4 ring-orange-100 transition-all"
                                                            :style="`left:${zonePosition(eq.zone)[0]}%;top:${zonePosition(eq.zone)[1]}%`"
                                                        ></span
                                                        ><span
                                                            class="absolute bottom-1 left-2 text-[9px] font-bold text-slate-400"
                                                            x-text="eq.zone"
                                                        ></span>
                                                    </div></div
                                            ></template>
                                        </div></article
                                ></template>
                            </div></div
                    ></template>
                </div>
            </section>

            <section x-show="step === 4" x-cloak>
                <div class="mx-auto max-w-2xl">
                    <div class="text-center">
                        <span
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600"
                            ><i
                                data-lucide="clipboard-check"
                                class="h-8 w-8"
                            ></i
                        ></span>
                        <h3 class="mt-4 text-2xl font-extrabold">
                            Ready to build the workspace
                        </h3>
                        <p class="mt-2 text-sm text-slate-500">Everything below is created together in one safe transaction.</p>
                    </div>
                    <div class="mt-7 rounded-2xl bg-slate-950 p-5 text-white">
                        <p class="text-xs text-slate-400">Building</p>
                        <h4
                            class="mt-1 text-lg font-bold"
                            x-text="form.building_name || 'Unnamed building'"
                        ></h4>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-white/5 p-4">
                                <b
                                    class="text-2xl"
                                    x-text="form.floors.length"
                                ></b>
                                <p class="text-xs text-slate-400">Floors</p>
                            </div>
                            <div class="rounded-xl bg-white/5 p-4">
                                <b
                                    class="text-2xl"
                                    x-text="
                                        form.floors.reduce(
                                            (n, f) => n + f.rooms.length,
                                            0,
                                        )
                                    "
                                ></b>
                                <p class="text-xs text-slate-400">Rooms</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <footer
            class="flex items-center justify-between border-t border-slate-100 bg-white px-6 py-4 sm:px-8"
        >
            <button
                type="button"
                @click="step = Math.max(1, step - 1)"
                :disabled="step === 1"
                class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-500 disabled:opacity-0"
            >
                Back
            </button>
            <button
                x-show="step < 4"
                type="button"
                @click="step++"
                class="rounded-xl bg-[#005EA6] px-6 py-2.5 text-sm font-bold text-white"
            >
                Continue
            </button>
            <button
                x-show="step === 4"
                type="submit"
                class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-900/15"
            >
                Create campus workspace
            </button>
        </footer>
    </form>
</div>
