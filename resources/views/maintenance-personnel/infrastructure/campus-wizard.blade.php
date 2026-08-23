<div
    x-show="wizardOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-[1000] flex items-center justify-center bg-[#0b1220]/70"
    :class="wizardFullscreen ? 'p-0' : 'p-3 sm:p-6'"
    role="dialog"
    aria-modal="true"
>
    <form
        method="POST"
        action="{{ route('maintenance.infrastructure.campus.store') }}"
        @submit.prevent="submitCampusWizard($event)"
        @click.outside="closeCampusWizard()"
        class="flex w-full flex-col overflow-hidden bg-white shadow-2xl"
        :class="wizardFullscreen
            ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none'
            : 'max-h-[80vh] max-w-5xl rounded-[28px]'"
    >
        @csrf
        <header
            class="flex shrink-0 items-start justify-between border-b border-slate-100 px-6 py-5 sm:px-8"
        >
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#005EA6]">Unified creation cascade</p>
                <h2
                    class="mt-1 text-xl font-extrabold text-slate-950 sm:text-2xl"
                >
                    Campus configuration wizard
                </h2>
            </div>
            <div class="ml-3 flex shrink-0 items-center gap-1">
                <button
                    type="button"
                    @click="toggleWizardFullscreen()"
                    class="rounded-xl bg-slate-100 p-2 text-slate-500 hover:bg-slate-200"
                    :title="wizardFullscreen ? 'Exit full screen' : 'Full screen'"
                    :aria-label="wizardFullscreen ? 'Exit full screen' : 'Full screen'"
                >
                    <i :data-lucide="wizardFullscreen ? 'minimize-2' : 'maximize-2'" class="h-5 w-5"></i>
                </button>
                <button
                    type="button"
                    @click="closeCampusWizard()"
                    class="rounded-xl bg-slate-100 p-2 text-slate-500 hover:bg-slate-200"
                    aria-label="Close wizard"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
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
                        { n: 3, l: 'Rooms & Equipments' },
                        { n: 4, l: 'Review' },
                    ]
                "
                :key="item.n"
            >
                <button
                    type="button"
                    @click="goToWizardStep(item.n)"
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

        @if ($errors->getBag("campusWizard")->any())
            <div
                class="mx-6 mt-4 rounded-xl border border-red-200 bg-red-50/80 p-3"
            >
                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-red-700">Please fix the following</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($errors->getBag("campusWizard")->all() as $error)
                        <span class="inline-flex items-center rounded-full border border-red-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-red-700">{{ $error }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="min-h-0 flex-1 overflow-y-auto p-6 sm:p-8">
            <section x-show="step === 1" x-transition>
                <div
                    x-show="isWizardSetupLocked"
                    x-cloak
                    class="mx-auto mb-4 flex max-w-xl items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800"
                >
                    <span>Campus setup is locked after first registration.</span>
                    <button
                        type="button"
                        @click="openUnlockSetupPrompt()"
                        x-show="canManageCampusSetup"
                        class="rounded-lg border border-amber-300 bg-white px-2.5 py-1 font-semibold text-amber-800 hover:bg-amber-100"
                    >
                        Unlock setup
                    </button>
                </div>

                <div
                    x-show="!isWizardSetupLocked && form.setup_locked"
                    x-cloak
                    class="mx-auto mb-4 flex max-w-xl items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-900"
                >
                    <span>Setup is currently unlocked for editing.</span>
                    <button
                        type="button"
                        @click="lockWizardSetup()"
                        x-show="canManageCampusSetup"
                        class="rounded-lg border border-blue-300 bg-white px-2.5 py-1 font-semibold text-blue-900 hover:bg-blue-100"
                    >
                        Lock setup again
                    </button>
                </div>

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
                        :readonly="isWizardSetupLocked"
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#005EA6] focus:ring-[#005EA6]"
                        :class="isWizardSetupLocked ? 'bg-slate-100 text-slate-500' : ''"
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
                        :readonly="isWizardSetupLocked"
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
                        :class="isWizardSetupLocked ? 'bg-slate-100 text-slate-500' : ''"
                    >
                </label>

                <label class="text-xs font-bold text-slate-700">
                    Highest Floors

                    <input
                        type="number"
                        min="1"
                        max="30"
                        x-model.number="form.maxFloor"
                        :readonly="isWizardSetupLocked"
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3"
                        :class="isWizardSetupLocked ? 'bg-slate-100 text-slate-500' : ''"
                    >
                </label>

            </div>
            </section>

            <div
                x-show="unlockPromptOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1100] flex items-center justify-center bg-[#0b1220]/70 p-4"
                role="dialog"
                aria-modal="true"
            >
                <div
                    @click.outside="closeUnlockSetupPrompt()"
                    class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl"
                >
                    <h4 class="text-base font-bold text-slate-900">Verify Before Unlocking</h4>
                    <p class="mt-1 text-sm text-slate-600">
                        Enter your account password or unlock code to continue.
                    </p>

                    <label class="mt-4 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Password / Unlock Code
                        <input
                            type="password"
                            x-model="unlockCredential"
                            @keydown.enter.prevent="confirmUnlockSetup()"
                            class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                            placeholder="Enter credential"
                            autocomplete="current-password"
                        >
                    </label>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="closeUnlockSetupPrompt()"
                            :disabled="unlockVerifyBusy"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmUnlockSetup()"
                            :disabled="unlockVerifyBusy"
                            class="rounded-lg bg-[#005EA6] px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            x-text="unlockVerifyBusy ? 'Verifying...' : 'Verify & Unlock'"
                        ></button>
                    </div>
                </div>
            </div>

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
                        ><button
                            type="button"
                            @click="selectWizardFloor(fi)"
                            class="rounded-2xl border p-4 text-left transition"
                            :class="wizardFloorIndex === fi
                                ? 'border-[#005EA6] bg-blue-50 ring-2 ring-blue-100'
                                : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FFF200] text-slate-900"
                                    >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 3L3 7.5L12 12L21 7.5L12 3Z"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M3 12.5L12 17L21 12.5"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <path
                                            d="M3 17.5L12 22L21 17.5"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span
                                ><input
                                    readonly
                                    x-model="floor.level"
                                    :name="`floors[${fi}][level]`"
                                    class="flex-1 rounded-xl border-slate-200 bg-slate-100 text-sm font-normal text-black"
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
                                    `${countNamedRoomsForFloor(floor)} room workspace${countNamedRoomsForFloor(floor) === 1 ? '' : 's'}`
                                "
                            ></p>
                            <p
                                class="mt-2 text-[11px] font-normal uppercase tracking-wider"
                                :class="wizardFloorIndex === fi ? 'text-[#005EA6]' : 'text-slate-300'"
                                x-text="wizardFloorIndex === fi ? 'Selected for step 3' : 'Click to edit this floor'
                                "
                            ></p>
                        </button></template>
                    ></template>
                </div>
            </section>

            <section x-show="step === 3" x-cloak>
                <div class="mb-5">
                    <h3 class="text-xl font-extrabold">
                        Map rooms and initial assets
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Quick Add is for multiple rooms only. Advance Setup is for rooms with equipment.</p>
                </div>
                <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-700">
                            <span class="font-semibold" x-text="activeWizardFloor?.level || 'No floor selected'"></span>
                            <span class="ml-2 text-slate-500" x-text="`(${wizardFloorIndex + 1} / ${form.floors.length || 0})`"></span>
                        </div>

                        <div class="relative flex items-center gap-3" x-data="{ open: false }" @keydown.escape.window="open = false">
                            <!--<p class="text-[11px] font-semibold uppercase tracking-wider text-black whitespace-nowrap">Jump To Floor</p>-->
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex min-w-[220px] items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-black shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                                :aria-expanded="open"
                                aria-haspopup="listbox"
                            >
                                <span class="truncate" x-text="activeWizardFloor?.level || 'Select a floor'"></span>
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-slate-500 transition"
                                    :class="open ? 'rotate-180' : ''"
                                    aria-hidden="true"
                                >
                                    <path d="M5 8L10 13L15 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>

                            <div
                                x-show="open"
                                x-cloak
                                x-transition.origin.top.right
                                @click.outside="open = false"
                                class="absolute right-0 top-full z-30 mt-2 w-[220px] rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                role="listbox"
                                aria-label="Jump to floor"
                            >
                                <div class="max-h-[126px] overflow-y-auto pr-1">
                                    <template x-for="(floor, fi) in form.floors" :key="`picker-${fi}`">
                                        <button
                                            type="button"
                                            @click="selectWizardFloor(fi); open = false"
                                            class="mb-1 flex w-full items-center rounded-lg px-3 py-2 text-left text-sm transition last:mb-0"
                                            :class="wizardFloorIndex === fi
                                                ? 'bg-[#005EA6] text-white'
                                                : 'text-slate-700 hover:bg-slate-100'"
                                            x-text="floor.level"
                                            role="option"
                                            :aria-selected="wizardFloorIndex === fi"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-show="step3Mode === 'fast'" x-cloak class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <!--<p class="text-[11px] font-bold uppercase tracking-[.2em] text-[#005EA6]">Quick Add</p>-->
                            <h4 class="mt-1 text-sm font-semibold text-slate-900">Add multiple room forms quickly</h4>
                        </div>
                        <div class="inline-flex rounded-full border border-slate-200 bg-slate-100 p-1">
                            <button
                                type="button"
                                @click="step3Mode = 'fast'"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'fast' ? 'bg-[#005EA6] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Quick Add
                            </button>
                            <button
                                type="button"
                                @click="step3Mode = 'slow'"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'slow' ? 'bg-[#005EA6] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Advance Setup
                            </button>
                        </div>
                    </div>
                    <div class="space-y-5">
                    <template x-for="(floor, fi) in form.floors" :key="`fast-${fi}`">
                        <div
                            x-show="wizardFloorIndex === fi"
                            x-cloak
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="h-2 w-2 rounded-full bg-[#005EA6]"></div>
                                    <h4
                                        class="text-sm font-semibold uppercase tracking-wider text-black"
                                        x-text="floor.level"
                                    ></h4>
                                </div>

                                <button
                                    type="button"
                                    @click="addQuickRoom(fi)"
                                    class="
                                    inline-flex items-center gap-1.5 px-3 py-1.5
                                    text-xs font-semibold rounded-lg text-[#005EA6] bg-blue-50/70 border-dashed border border-[#005EA6]
                                    hover:bg-blue-100/70 hover:text-[#004b85] 
                                    active:scale-95 transition-all duration-200
                                    "
                                >
                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                    Add room
                                </button>
                            </div>
                            <div class="mb-3">
                                <!--<p class="text-xs text-slate-500">Add multiple room forms quickly. Equipment is handled in Advance Setup.</p>-->
                            </div>
                            <div class="space-y-4">
                                <div
                                    x-show="floor.rooms.length === 0"
                                    x-cloak
                                    class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-600"
                                >
                                    No room forms yet. Click <b>Add room</b> to create one.
                                </div>
                                
                                    <template
                                        x-for="(room, ri) in floor.rooms"
                                        :key="room.client_key || `fast-room-${fi}-${ri}`"
                                    >
                                        <article class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                                            <div class="grid gap-3 md:grid-cols-[1.3fr_1fr_1fr_auto]">
                                                <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                    Room Name
                                                    <input
                                                        x-model="room.name"
                                                        @input="handleStep3RoomNameInput(fi)"
                                                        placeholder="Room name / number"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 p-2 text-sm font-normal"
                                                        :class="getStep3Error('room-name', fi, ri) ? 'border-red-300 ring-1 ring-red-100' : ''"
                                                    />
                                                    <span
                                                        x-show="getStep3Error('room-name', fi, ri)"
                                                        x-cloak
                                                        x-text="getStep3Error('room-name', fi, ri)"
                                                        class="mt-1 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-[10px] font-semibold text-red-700"
                                                    ></span>
                                                </label>

                                                <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                    Room Type
                                                    <select
                                                        x-model="room.type"
                                                        class="mt-1 w-full rounded-xl border cursor-pointer border-slate-200 p-2 text-sm font-normal focus:border-[#005EA6] focus:ring-1 focus:ring-blue-100"
                                                    >
                                                        <option value="Lecture Room">Lecture Room</option>
                                                        <option value="Computer Laboratory">Computer Laboratory</option>
                                                        <option value="HM Room">HM Room / Bar</option>
                                                        <option value="Hotel Room Simulation">Hotel Room Simulation</option>
                                                        <option value="Faculty Room">Faculty Room</option>
                                                        <option value="Office">Office</option>
                                                        <option value="Library">Library</option>
                                                        <option value="School Clinic">School Clinic</option>
                                                    </select>
                                                </label>

                                                <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                    Room Status
                                                    <select
                                                        x-model="room.status"
                                                        class="mt-1 w-full rounded-xl border cursor-pointer border-slate-200 p-2 text-sm font-normal"
                                                    >
                                                        <option>Normal</option>
                                                        <option>Maintenance Needed</option>
                                                        <option>Critical</option>
                                                    </select>
                                                </label>

                                                <button
                                                    type="button"
                                                    @click="floor.rooms.splice(ri, 1)"
                                                    class="mt-[1.375rem] inline-flex h-[2.375rem] items-center justify-center rounded-xl p-2 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                                    aria-label="Remove room"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </article>
                                    </template>
                                
                                <!--<p class="text-xs text-slate-500">These are draft room forms only. Save on Step 4.</p>-->
                            </div>
                        </div>
                    </template>
                    </div>
                </div>

                <div x-show="step3Mode === 'slow'" x-cloak class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <!--<p class="text-[11px] font-bold uppercase tracking-[.2em] text-[#005EA6]">Advance Setup</p>-->
                            <h4 class="mt-1 text-sm font-semibold text-slate-900">Add rooms with equipment in one flow</h4>
                        </div>
                        <div class="inline-flex rounded-full border border-slate-200 bg-slate-100 p-1">
                            <button
                                type="button"
                                @click="step3Mode = 'fast'"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'fast' ? 'bg-[#005EA6] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Quick Add
                            </button>
                            <button
                                type="button"
                                @click="step3Mode = 'slow'"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'slow' ? 'bg-[#005EA6] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Advance Setup
                            </button>
                        </div>
                    </div>
                    <div class="space-y-5">
                        <template x-for="(floor, fi) in form.floors" :key="fi">
                            <div
                                x-show="wizardFloorIndex === fi"
                                x-cloak
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5"
                            >
                            <div class="mb-5 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="h-2 w-2 rounded-full bg-[#005EA6]"></div>
                                <h4 
                                class="text-sm font-semibold uppercase tracking-wider text-black"
                                x-text="floor.level"
                                ></h4>
                            </div>
                            
                            <button
                                type="button"
                                @click="addRoom(fi)"
                                class="
                                inline-flex items-center gap-1.5 px-3 py-1.5
                                text-xs font-semibold rounded-lg text-[#005EA6] bg-blue-50/70 border-dashed border border-[#005EA6]
                                hover:bg-blue-100/70 hover:text-[#004b85] 
                                active:scale-95 transition-all duration-200
                                "
                            >
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                Add room
                            </button>
                            </div>
                            <div class="space-y-4">
                                <div
                                    x-show="floor.rooms.length === 0"
                                    x-cloak
                                    class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-600"
                                >
                                    No room forms yet. Click <b>Add room</b> to create one.
                                </div>
                                <template
                                    x-for="(room, ri) in floor.rooms"
                                    :key="room.client_key || `room-${fi}-${ri}`"
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
                                            <label class="text-[11px] font-semibold uppercase  text-black">
                                                Room Name
                                                <input
                                                    x-model="room.name"
                                                    @input="handleStep3RoomNameInput(fi)"
                                                    :name="`floors[${fi}][rooms][${ri}][name]`"
                                                    placeholder="Room name / number"
                                                    class="mt-1 w-full rounded-xl border border-slate-200 p-2 text-sm font-normal"
                                                    :class="getStep3Error('room-name', fi, ri) ? 'border-red-300 ring-1 ring-red-100' : ''"
                                                />
                                                <span
                                                    x-show="getStep3Error('room-name', fi, ri)"
                                                    x-cloak
                                                    x-text="getStep3Error('room-name', fi, ri)"
                                                    class="mt-1 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-[10px] font-semibold text-red-700"
                                                ></span>
                                            </label>
                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Room Type
                                                <select
                                                    x-model="room.type"
                                                    :name="`floors[${fi}][rooms][${ri}][type]`"
                                                    class="mt-1 w-full rounded-xl cursor-pointer border border-slate-200 p-2 text-sm focus:border-[#005EA6] focus:ring-1 focus:ring-blue-100 font-normal"
                                                >
                                                    <option value="Lecture Room">Lecture Room</option>
                                                    <option value="Computer Laboratory">Computer Laboratory</option>
                                                    <option value="HM Room">HM Room / Bar</option>
                                                    <option value="Hotel Room Simulation">Hotel Room Simulation</option>
                                                    <option value="Faculty Room">Faculty Room</option>
                                                    <option value="Office">Office</option>
                                                    <option value="Library">Library</option>
                                                    <option value="School Clinic">School Clinic</option>
                                                </select>
                                            </label>
                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Status / Condition
                                                <select
                                                    x-model="room.status"
                                                    :name="`floors[${fi}][rooms][${ri}][status]`"
                                                    class="mt-1 w-full rounded-xl cursor-pointer border border-slate-200 p-2 text-sm font-normal"
                                                >
                                                    <option>Normal</option>
                                                    <option>
                                                        Maintenance Needed
                                                    </option>
                                                    <option>
                                                        Critical
                                                    </option>
                                                </select>
                                            </label>
                                            <button
                                                type="button"
                                                @click="floor.rooms.splice(ri, 1)"
                                                class="mt-[1.375rem] inline-flex h-[2.375rem] items-center justify-center rounded-xl p-2 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                                aria-label="Remove room"
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
                                            <div>
                                                
                                                <p class="text-[11px] text-black">Add one or more items for this room.</p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="addEquipment(fi, ri)"
                                                class="rounded-lg bg-slate-100 px-3 py-2 text-[11px] font-normal text-black hover:bg-slate-200"
                                            >
                                                + Add equipment
                                            </button>
                                        </div>
                                        <div class="mt-3 space-y-3">
                                            <div
                                                x-show="room.equipment.length === 0"
                                                x-cloak
                                                class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-xs text-black"
                                            >
                                                No equipment yet. Click <b>Add equipment</b> to create the first item.
                                            </div>
                                            <template
                                                x-for="
                                                    (eq, ei) in room.equipment
                                                "
                                                :key="eq.client_key || `eq-${fi}-${ri}-${ei}`"
                                                ><div
                                                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                                                >
                                                    <input
                                                        type="hidden"
                                                        :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][id]`"
                                                        :value="eq.id ?? ''"
                                                    >
                                                    <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
                                                        <p class="text-xs font-semibold uppercase tracking-wider text-black" x-text="`Equipment ${ei + 1}`"></p>
                                                        <button
                                                            type="button"
                                                            @click="room.equipment.splice(ei, 1)"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-2 py-1 text-[11px] font-normal text-red-600 transition hover:bg-red-50"
                                                        >
                                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                            Remove
                                                        </button>
                                                    </div>

                                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Equipment Name
                                                            <input
                                                                x-model="eq.name"
                                                                :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][name]`"
                                                                placeholder="e.g. Dell Monitor"
                                                                class="mt-1 w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
                                                                data-tooltip="Equipment name"
                                                                :class="getStep3Error('eq-name', fi, ri, ei) ? 'border-red-300 ring-1 ring-red-100' : ''"
                                                            />
                                                            <span
                                                                x-show="getStep3Error('eq-name', fi, ri, ei)"
                                                                x-cloak
                                                                x-text="getStep3Error('eq-name', fi, ri, ei)"
                                                                class="mt-1 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-[10px] font-semibold text-red-700"
                                                            ></span>
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Category
                                                            <select
                                                                x-model="eq.category_id"
                                                                :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][category_id]`"
                                                                class="mt-1 w-full rounded-lg cursor-pointer border border-slate-200 px-2.5 py-2 text-xs"
                                                                data-tooltip="Category"
                                                            >
                                                                <option value="">
                                                                    Select category
                                                                </option>
                                                                @foreach ($categories as $category)
                                                                    <option
                                                                        value="{{ $category->equipment_category_id }}"
                                                                    >
                                                                        {{ $category->equipment_category_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Quantity
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                x-model="eq.quantity"
                                                                :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][quantity]`"
                                                                class="mt-1 w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
                                                                data-tooltip="Quantity"
                                                            />
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Condition
                                                            <select
                                                                x-model="eq.condition"
                                                                :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][condition]`"
                                                                class="mt-1 w-full rounded-lg cursor-pointer border border-slate-200 px-2.5 py-2 text-xs"
                                                                data-tooltip="Condition"
                                                            >
                                                                <option>Good</option>
                                                                <option>Damaged</option>
                                                                <option>
                                                                    Under Maintenance
                                                                </option>
                                                            </select>
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black sm:col-span-2 lg:col-span-2">
                                                            Placement
                                                            <select
                                                                x-model="eq.zone"
                                                                :name="`floors[${fi}][rooms][${ri}][equipment][${ei}][zone]`"
                                                                class="mt-1 w-full rounded-lg cursor-pointer border border-slate-200 px-2.5 py-2 text-xs"
                                                                data-tooltip="Placement"
                                                            >
                                                                <option value="Holding">Holding Area</option>
                                                                <option value="Floor">Floor</option>
                                                                <option value="Row 1">Row 1</option>
                                                                <option value="Row 2">Row 2</option>
                                                                <option value="Row 3">Row 3</option>
                                                            </select>
                                                            <p class="mt-1 text-[10px] font-normal normal-case tracking-normal text-slate-400">Holding Area = unplaced. Floor = floor icon. Rows can be arranged later in Room interior layout.</p>
                                                        </label>
                                                    </div>

                                                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5">
                                                        <div class="mb-2 flex items-center justify-between px-1">
                                                            <p class="text-[10px] font-semibold uppercase tracking-widest text-black">Placement preview</p>
                                                            <p class="text-[10px] font-normal text-black">Zone: <span class="text-black font-normal" x-text="placementZoneLabel(eq.zone)"></span></p>
                                                        </div>

                                                        <div
                                                            class="relative h-28 overflow-hidden rounded-lg border border-dashed border-slate-300 bg-[linear-gradient(180deg,#f8fbff_0%,#f3f6fb_100%)]"
                                                        >
                                                            <div class="absolute inset-0 opacity-70" style="background-image: linear-gradient(to right, rgba(148,163,184,.25) 1px, transparent 1px), linear-gradient(to bottom, rgba(148,163,184,.2) 1px, transparent 1px); background-size: 20% 33.333%;"></div>

                                                            <span class="absolute left-1/2 top-1 -translate-x-1/2 text-[8px] font-black uppercase tracking-[.16em] text-slate-400">Front wall / board</span>
                                                            <span class="absolute left-2 top-[28%] text-[8px] font-bold uppercase tracking-wider text-slate-400">Row 1</span>
                                                            <span class="absolute left-2 top-[48%] text-[8px] font-bold uppercase tracking-wider text-slate-400">Row 2</span>
                                                            <span class="absolute left-2 top-[68%] text-[8px] font-bold uppercase tracking-wider text-slate-400">Row 3</span>
                                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[8px] font-bold uppercase tracking-wider text-slate-400">Floor</span>
                                                            <span class="absolute left-1/2 bottom-1 -translate-x-1/2 text-[8px] font-black uppercase tracking-[.16em] text-amber-600/80">Holding area</span>

                                                            <span
                                                                class="absolute h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-[#F39200] shadow-[0_0_0_4px_rgba(251,191,36,.25)] transition-all duration-200"
                                                                :style="`left:${zonePosition(eq.zone).x}%;top:${zonePosition(eq.zone).y}%`"
                                                            ></span>
                                                        </div>
                                                    </div></div
                                            ></template>
                                        </div></article
                                ></template>
                            </div></div>
                                    </template>
                                </div>
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
                            <span x-text="form.setup_locked ? 'Review campus updates' : 'Ready to build the workspace'"></span>
                        </h3>
                        <p class="mt-2 text-sm text-slate-500" x-text="form.setup_locked ? 'Your campus changes will be updated in one safe transaction.' : 'Everything below is created together in one safe transaction.'"></p>
                    </div>

                    <div
                        x-show="step4InlineErrors.length"
                        x-cloak
                        class="mt-4 rounded-xl border border-red-200 bg-red-50/80 p-3"
                    >
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-red-700">Resolve before saving</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(errorMessage, errorIndex) in step4InlineErrors" :key="`step4-error-${errorIndex}`">
                                <span
                                    x-text="errorMessage"
                                    class="inline-flex items-center rounded-full border border-red-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-red-700"
                                ></span>
                            </template>
                        </div>
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
                                    x-text="countDraftRooms()"
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
                @click="nextWizardStep()"
                class="rounded-xl bg-[#005EA6] px-6 py-2.5 text-sm font-bold text-white"
            >
                Continue
            </button>
            <button
                x-show="step === 4"
                type="submit"
                class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-900/15"
            >
                <span x-text="form.setup_locked ? 'Save campus updates' : 'Create campus workspace'"></span>
            </button>
        </footer>
    </form>
</div>
