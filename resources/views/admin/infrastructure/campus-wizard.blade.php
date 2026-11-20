<template x-if="wizardOpen">
<div
    x-transition.opacity
    data-campus-wizard
    class="fixed inset-0 z-[1000] flex items-center justify-center bg-[#0b1220]/70"
    :class="wizardFullscreen ? 'p-0' : 'p-3 sm:p-6'"
    role="dialog"
    aria-modal="true"
    @click.self="closeCampusWizard()"
>
    <form
        x-ref="campusWizardForm"
        method="POST"
        action="{{ route('admin.infrastructure.campus.store') }}"
        @submit.prevent="submitCampusWizard($event)"
        class="flex w-full flex-col overflow-hidden bg-white shadow-2xl"
        :class="wizardFullscreen
            ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none'
            : 'max-h-[80vh] max-w-5xl rounded-[28px]'"
        @click.stop
    >
        @csrf
        <header
            class="flex shrink-0 items-start justify-between border-b border-slate-100 px-6 py-5 sm:px-8"
        >
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#0025cc]">Unified creation cascade</p>
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
            class="grid grid-cols-3 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-8"
        >
            <template
                x-for="
                    item in
                    [
                        { n: 1, l: 'Structure' },
                        { n: 2, l: 'Floors' },
                        { n: 3, l: 'Add room' },
                    ]
                "
                :key="item.n"
            >
                <button
                    type="button"
                    @click="goToWizardStep(item.n)"
                    class="flex items-center gap-2 py-1 text-left"
                    :class="step === item.n
                        ? 'text-[#0025cc]'
                        : 'text-slate-400'"
                >
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-extrabold"
                        :class="step === item.n
                            ? 'bg-[#0025cc] text-white'
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
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-[#0025cc]"
                        ><i data-lucide="building" class="h-8 w-8"></i
                    ></span>
                    <h3 class="mt-5 text-2xl font-extrabold">
                        Define the campus structure
                    </h3>
                </div>
                <label
                    class="mx-auto block max-w-xl text-xs font-bold text-slate-700"
                    >Campus Name <span class="text-red-500">*</span><input
                        name="building_name"
                        x-model="form.building_name"
                        maxlength="{{ \App\Support\InputLimits::BUILDING_NAME }}"
                        required
                        :readonly="isWizardSetupLocked"
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-[#0025cc] focus:ring-[#0025cc]"
                        :class="isWizardSetupLocked ? 'bg-slate-100 text-slate-500' : ''"
                        placeholder="e.g. STI College Ormoc Main Building"
                /></label>

                <div class="mx-auto mt-6 max-w-xl">
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-xs font-bold text-slate-700">
                            Occupied Floors <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] font-medium text-slate-500" x-text="selectedFloorSummary"></span>
                    </div>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                        Add only floors the school occupies. Skipped floors are fine for rented buildings.
                    </p>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                        <label class="block min-w-0 flex-1 text-xs font-bold text-slate-700">
                            Floor number
                            <input
                                type="number"
                                min="1"
                                max="30"
                                step="1"
                                x-model.number="customFloorNumber"
                                :disabled="isWizardSetupLocked"
                                @input="clampCustomFloorInput()"
                                @keydown.enter.prevent="addCustomFloor()"
                                class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"
                                :class="isWizardSetupLocked ? 'bg-slate-100 text-slate-500' : ''"
                                placeholder="e.g. 4"
                            >
                        </label>
                        <button
                            type="button"
                            @click="addCustomFloor()"
                            :disabled="isWizardSetupLocked"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-[#0025cc]/40 hover:text-[#0025cc] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Add floor
                        </button>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Occupied</p>

                        <template x-if="!(form.selectedFloorNumbers || []).length">
                            <p class="mt-2 text-sm text-slate-500">No occupied floors yet. Add a floor number above.</p>
                        </template>

                        <div
                            class="mt-2 flex flex-wrap gap-2"
                            x-show="(form.selectedFloorNumbers || []).length"
                        >
                            <template x-for="floorNumber in sortedSelectedFloorNumbers" :key="`occupied-floor-${floorNumber}`">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    <span x-text="floorLabel(floorNumber)"></span>
                                    <button
                                        type="button"
                                        @click="removeOccupiedFloor(floorNumber)"
                                        :disabled="isWizardSetupLocked"
                                        class="rounded-full px-1 text-base leading-none disabled:cursor-not-allowed disabled:opacity-40"
                                        :class="floorIsProtected(floorNumber)
                                            ? 'cursor-not-allowed text-slate-300'
                                            : 'text-slate-400 hover:text-red-500'"
                                        :aria-label="floorIsProtected(floorNumber)
                                            ? `Can't remove — ${floorLabel(floorNumber)} already has rooms`
                                            : `Remove ${floorLabel(floorNumber)}`"
                                        :title="floorIsProtected(floorNumber)
                                            ? `Can't remove — this floor already has rooms.`
                                            : `Remove ${floorLabel(floorNumber)}`"
                                    >
                                        ×
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>
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
                        Password / Unlock Code <span class="text-red-500">*</span>
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
                            class="rounded-lg bg-[#0025cc] px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            x-text="unlockVerifyBusy ? 'Verifying...' : 'Verify & Unlock'"
                        ></button>
                    </div>
                </div>
            </div>

            <section x-show="step === 2" x-cloak>
                <div class="mb-5 flex items-center justify-between">
                    
                    <div class="mb-5">

                        <h3 class="text-xl font-extrabold">
                            Occupied Floors
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            These are the floors the school occupies from Step 1.
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
                                ? 'border-[#0025cc] bg-blue-50 ring-2 ring-blue-100'
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
                                :class="wizardFloorIndex === fi ? 'text-[#0025cc]' : 'text-slate-300'"
                                x-text="wizardFloorIndex === fi ? 'Selected for Add room' : 'Click to select this floor'"
                            ></p>
                        </button>
                    </template>
                </div>
            </section>

            <section x-show="step === 3" x-cloak>
                <div class="mb-5">
                    <h3 class="text-xl font-extrabold">
                        Add room
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Optional. You can save floors now and add rooms later.</p>
                </div>

                <div class="mb-5 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900">Add rooms</p>
                        <p class="mt-0.5 text-xs text-slate-500">Turn on to map rooms and equipment for occupied floors.</p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="addRoomsEnabled ? 'true' : 'false'"
                        @click="addRoomsEnabled = !addRoomsEnabled"
                        class="relative h-7 w-12 shrink-0 rounded-full transition"
                        :class="addRoomsEnabled ? 'bg-[#0025cc]' : 'bg-slate-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 h-6 w-6 rounded-full bg-white shadow transition"
                            :class="addRoomsEnabled ? 'translate-x-5' : 'translate-x-0'"
                        ></span>
                    </button>
                </div>

                <div
                    x-show="!addRoomsEnabled"
                    x-cloak
                    class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center"
                >
                    <p class="text-sm font-semibold text-slate-700">Rooms skipped for now</p>
                    <p class="mt-1 text-xs text-slate-500">Your occupied floors will still be saved. You can add rooms later from Rooms Layout.</p>
                </div>

                <div x-show="addRoomsEnabled" x-cloak>
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
                                                ? 'bg-[#0025cc] text-white'
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
                <template x-if="step3Mode === 'fast'">
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h4 class="mt-1 text-sm font-semibold text-slate-900">Add multiple room forms quickly</h4>
                        </div>
                        <div class="inline-flex rounded-full border border-slate-200 bg-slate-100 p-1">
                            <button
                                type="button"
                                @click="step3Mode = 'fast'"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'fast' ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Quick Add
                            </button>
                            <button
                                type="button"
                                @click="step3Mode = 'slow'; $nextTick(() => refreshCampusWizardIcons())"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'slow' ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Advance Setup
                            </button>
                        </div>
                    </div>
                    <div class="space-y-5" x-show="activeWizardFloor" x-cloak>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="h-2 w-2 rounded-full bg-[#0025cc]"></div>
                                    <h4
                                        class="text-sm font-semibold uppercase tracking-wider text-black"
                                        x-text="activeWizardFloor.level"
                                    ></h4>
                                </div>

                                <button
                                    type="button"
                                    @click="addQuickRoom(wizardFloorIndex); $nextTick(() => refreshCampusWizardIcons())"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-[#0025cc] bg-blue-50/70 px-3 py-1.5 text-xs font-semibold text-[#0025cc] transition-all duration-200 hover:bg-blue-100/70 hover:text-[#004b85] active:scale-95"
                                >
                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                    Add room
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div
                                    x-show="!activeWizardFloor.rooms.length"
                                    x-cloak
                                    class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-600"
                                >
                                    No room forms yet. Click <b>Add room</b> to create one.
                                </div>

                                <template
                                    x-for="(room, ri) in activeWizardFloor.rooms"
                                    :key="room.client_key || `fast-room-${wizardFloorIndex}-${ri}`"
                                >
                                    <article class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                                        <div class="grid gap-3 md:grid-cols-[1.3fr_1fr_1fr_auto]">
                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Room Name <span class="text-red-500">*</span>
                                                <input
                                                    x-model="room.name"
                                                    maxlength="{{ \App\Support\InputLimits::ROOM_NAME }}"
                                                    @input="handleStep3RoomNameInput(wizardFloorIndex)"
                                                    :name="`floors[${wizardFloorIndex}][rooms][${ri}][name]`"
                                                    placeholder="Room name / number"
                                                    class="mt-1 w-full rounded-xl border border-slate-200 p-2 text-sm font-normal"
                                                    :class="getStep3Error('room-name', wizardFloorIndex, ri) ? 'border-red-300 ring-1 ring-red-100' : ''"
                                                />

                                                <span
                                                    x-show="getStep3Error('room-name', wizardFloorIndex, ri)"
                                                    x-cloak
                                                    x-text="getStep3Error('room-name', wizardFloorIndex, ri)"
                                                    class="mt-1 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-[10px] font-semibold text-red-700"
                                                ></span>
                                            </label>

                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Room Type <span class="text-red-500">*</span>
                                                <select
                                                    x-model="room.type"
                                                    :name="`floors[${wizardFloorIndex}][rooms][${ri}][type]`"
                                                    class="mt-1 w-full cursor-pointer rounded-xl border border-slate-200 p-2 text-sm font-normal focus:border-[#0025cc] focus:ring-1 focus:ring-blue-100"
                                                >
                                                    <option value="Lecture Room">Lecture Room</option>
                                                    <option value="Computer Laboratory">Computer Laboratory</option>
                                                    <option value="HM Room">HM Room / Bar</option>
                                                    <option value="Hotel Room Simulation">Hotel Room Simulation</option>
                                                    <option value="Faculty Room">Faculty Room</option>
                                                    <option value="Office">Office</option>
                                                    <option value="Library">Library</option>
                                                    <option value="School Clinic">School Clinic</option>
                                                    <option value="Storage / Stockroom">Storage / Stockroom</option>
                                                </select>
                                            </label>

                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Room Status <span class="text-red-500">*</span>
                                                <select
                                                    x-model="room.status"
                                                    :name="`floors[${wizardFloorIndex}][rooms][${ri}][status]`"
                                                    class="mt-1 w-full cursor-pointer rounded-xl border border-slate-200 p-2 text-sm font-normal"
                                                >
                                                    <option>Available</option>
                                                    <option>Unavailable</option>
                                                </select>
                                            </label>

                                            <button
                                                type="button"
                                                @click="activeWizardFloor.rooms.splice(ri, 1)"
                                                class="mt-[1.375rem] inline-flex h-[2.375rem] items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-red-700 hover:bg-slate-50"
                                                aria-label="Remove room"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                </template>

                <template x-if="step3Mode === 'slow'">
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h4 class="mt-1 text-sm font-semibold text-slate-900">Add rooms with equipment in one flow</h4>
                        </div>
                        <div class="inline-flex rounded-full border border-slate-200 bg-slate-100 p-1">
                            <button
                                type="button"
                                @click="step3Mode = 'fast'; $nextTick(() => refreshCampusWizardIcons())"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'fast' ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Quick Add
                            </button>
                            <button
                                type="button"
                                @click="step3Mode = 'slow'"
                                class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                                :class="step3Mode === 'slow' ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Advance Setup
                            </button>
                        </div>
                    </div>
                    <div class="space-y-5" x-show="activeWizardFloor" x-cloak>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="h-2 w-2 rounded-full bg-[#0025cc]"></div>
                                    <h4
                                        class="text-sm font-semibold uppercase tracking-wider text-black"
                                        x-text="activeWizardFloor.level"
                                    ></h4>
                                </div>

                                <button
                                    type="button"
                                    @click="addRoom(wizardFloorIndex); $nextTick(() => refreshCampusWizardIcons())"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-[#0025cc] bg-blue-50/70 px-3 py-1.5 text-xs font-semibold text-[#0025cc] transition-all duration-200 hover:bg-blue-100/70 hover:text-[#004b85] active:scale-95"
                                >
                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                    Add room
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div
                                    x-show="!activeWizardFloor.rooms.length"
                                    x-cloak
                                    class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-5 text-sm text-slate-600"
                                >
                                    No room forms yet. Click <b>Add room</b> to create one.
                                </div>
                                <template
                                    x-for="(room, ri) in activeWizardFloor.rooms"
                                    :key="room.client_key || `room-${wizardFloorIndex}-${ri}`"
                                >
                                    <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                                        <div class="grid gap-3 md:grid-cols-[1.3fr_1fr_1fr_auto]">
                                            <input
                                                type="hidden"
                                                :name="`floors[${wizardFloorIndex}][rooms][${ri}][id]`"
                                                :value="room.id ?? ''"
                                            >
                                            <label class="text-[11px] font-semibold uppercase text-black">
                                                Room Name <span class="text-red-500">*</span>
                                                <input
                                                    x-model="room.name"
                                                    maxlength="{{ \App\Support\InputLimits::ROOM_NAME }}"
                                                    @input="handleStep3RoomNameInput(wizardFloorIndex)"
                                                    :name="`floors[${wizardFloorIndex}][rooms][${ri}][name]`"
                                                    placeholder="Room name / number"
                                                    class="mt-1 w-full rounded-xl border border-slate-200 p-2 text-sm font-normal"
                                                    :class="getStep3Error('room-name', wizardFloorIndex, ri) ? 'border-red-300 ring-1 ring-red-100' : ''"
                                                />

                                                <span
                                                    x-show="getStep3Error('room-name', wizardFloorIndex, ri)"
                                                    x-cloak
                                                    x-text="getStep3Error('room-name', wizardFloorIndex, ri)"
                                                    class="mt-1 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-[10px] font-semibold text-red-700"
                                                ></span>
                                            </label>
                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Room Type <span class="text-red-500">*</span>
                                                <select
                                                    x-model="room.type"
                                                    :name="`floors[${wizardFloorIndex}][rooms][${ri}][type]`"
                                                    class="mt-1 w-full cursor-pointer rounded-xl border border-slate-200 p-2 text-sm font-normal focus:border-[#0025cc] focus:ring-1 focus:ring-blue-100"
                                                >
                                                    <option value="Lecture Room">Lecture Room</option>
                                                    <option value="Computer Laboratory">Computer Laboratory</option>
                                                    <option value="HM Room">HM Room / Bar</option>
                                                    <option value="Hotel Room Simulation">Hotel Room Simulation</option>
                                                    <option value="Faculty Room">Faculty Room</option>
                                                    <option value="Office">Office</option>
                                                    <option value="Library">Library</option>
                                                    <option value="School Clinic">School Clinic</option>
                                                    <option value="Storage / Stockroom">Storage / Stockroom</option>
                                                </select>
                                            </label>
                                            <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                Status / Condition <span class="text-red-500">*</span>
                                                <select
                                                    x-model="room.status"
                                                    :name="`floors[${wizardFloorIndex}][rooms][${ri}][status]`"
                                                    class="mt-1 w-full cursor-pointer rounded-xl border border-slate-200 p-2 text-sm font-normal"
                                                >
                                                    <option>Available</option>
                                                    <option>Unavailable</option>
                                                </select>
                                            </label>
                                            <button
                                                type="button"
                                                @click="activeWizardFloor.rooms.splice(ri, 1)"
                                                class="mt-[1.375rem] inline-flex h-[2.375rem] items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-red-700 hover:bg-slate-50"
                                                aria-label="Remove room"
                                            >
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                                            <p class="text-[11px] text-black">Add one or more items for this room.</p>
                                            <button
                                                type="button"
                                                @click="addEquipment(wizardFloorIndex, ri); $nextTick(() => refreshCampusWizardIcons())"
                                                class="rounded-lg bg-slate-100 px-3 py-2 text-[11px] font-normal text-black hover:bg-slate-200"
                                            >
                                                + Add equipment
                                            </button>
                                        </div>
                                        <div class="mt-3 space-y-3">
                                            <div
                                                x-show="!room.equipment.length"
                                                x-cloak
                                                class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-xs text-black"
                                            >
                                                No equipment yet. Click <b>Add equipment</b> to create the first item.
                                            </div>
                                            <template
                                                x-for="(eq, ei) in room.equipment"
                                                :key="eq.client_key || `eq-${wizardFloorIndex}-${ri}-${ei}`"
                                            >
                                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                                    <input
                                                        type="hidden"
                                                        :name="`floors[${wizardFloorIndex}][rooms][${ri}][equipment][${ei}][id]`"
                                                        :value="eq.id ?? ''"
                                                    >
                                                    <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
                                                        <p class="text-xs font-semibold uppercase tracking-wider text-black" x-text="`Equipment ${ei + 1}`"></p>
                                                        <button
                                                            type="button"
                                                            @click="room.equipment.splice(ei, 1)"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-normal text-red-700 transition hover:bg-slate-50"
                                                        >
                                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                            Remove
                                                        </button>
                                                    </div>

                                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Equipment Name <span class="text-red-500">*</span>
                                                            <input
                                                                x-model="eq.name"
                                                                maxlength="{{ \App\Support\InputLimits::NAME }}"
                                                                :name="`floors[${wizardFloorIndex}][rooms][${ri}][equipment][${ei}][name]`"
                                                                placeholder="e.g. Dell Monitor"
                                                                class="mt-1 w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
                                                                :class="getStep3Error('eq-name', wizardFloorIndex, ri, ei) ? 'border-red-300 ring-1 ring-red-100' : ''"
                                                            />
                                                            <span
                                                                x-show="getStep3Error('eq-name', wizardFloorIndex, ri, ei)"
                                                                x-cloak
                                                                x-text="getStep3Error('eq-name', wizardFloorIndex, ri, ei)"
                                                                class="mt-1 inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-0.5 text-[10px] font-semibold text-red-700"
                                                            ></span>
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Classification
                                                            <select
                                                                x-model="eq.category_id"
                                                                :name="`floors[${wizardFloorIndex}][rooms][${ri}][equipment][${ei}][category_id]`"
                                                                class="mt-1 w-full cursor-pointer rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
                                                            >
                                                                <option value="">Select classification</option>
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->equipment_category_id }}">
                                                                        {{ $category->equipment_category_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Quantity <span class="text-red-500">*</span>
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                x-model="eq.quantity"
                                                                :name="`floors[${wizardFloorIndex}][rooms][${ri}][equipment][${ei}][quantity]`"
                                                                class="mt-1 w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
                                                            />
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black">
                                                            Condition <span class="text-red-500">*</span>
                                                            <select
                                                                x-model="eq.condition"
                                                                :name="`floors[${wizardFloorIndex}][rooms][${ri}][equipment][${ei}][condition]`"
                                                                class="mt-1 w-full cursor-pointer rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
                                                            >
                                                                <option>Good</option>
                                                                <option>Under Maintenance</option>
                                                                <option>Defective</option>
                                                                <option>Damaged</option>
                                                                <option>Disposed</option>
                                                            </select>
                                                        </label>

                                                        <label class="text-[11px] font-semibold uppercase tracking-wider text-black sm:col-span-2 lg:col-span-2">
                                                            Placement <span class="text-red-500">*</span>
                                                            <select
                                                                x-model="eq.zone"
                                                                :name="`floors[${wizardFloorIndex}][rooms][${ri}][equipment][${ei}][zone]`"
                                                                class="mt-1 w-full cursor-pointer rounded-lg border border-slate-200 px-2.5 py-2 text-xs"
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
                                                </div>
                                            </template>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                </template>
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
                class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-950 disabled:opacity-0"
            >
                Previous
            </button>
            <button
                x-show="step < 3"
                type="button"
                @click="nextWizardStep()"
                class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-bold text-white"
            >
                Continue
            </button>
            <button
                x-show="step === 3"
                type="button"
                @click="openSaveConfirm()"
                class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-900/15"
            >
                Save
            </button>
        </footer>
    </form>

    <div
        x-show="saveConfirmOpen"
        x-cloak
        x-transition.opacity
        class="absolute inset-0 z-[1100] flex items-center justify-center bg-[#0b1220]/60 p-4"
        role="dialog"
        aria-modal="true"
        @click.self="closeSaveConfirm()"
    >
        <div
            @click.stop
            class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-extrabold text-slate-950">Confirm save</h3>
                <p class="mt-1 text-sm text-slate-500">Review the summary, then save your campus changes.</p>
            </div>

            <div class="space-y-3 px-5 py-4">
                <div
                    x-show="step4InlineErrors.length"
                    x-cloak
                    class="rounded-xl border border-red-200 bg-red-50/80 p-3"
                >
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-red-700">Resolve before saving</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(errorMessage, errorIndex) in step4InlineErrors" :key="`save-error-${errorIndex}`">
                            <span
                                x-text="errorMessage"
                                class="inline-flex items-center rounded-full border border-red-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-red-700"
                            ></span>
                        </template>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-950 p-4 text-white">
                    <p class="text-xs text-slate-400">Building</p>
                    <h4
                        class="mt-1 text-lg font-bold"
                        x-text="form.building_name || 'Unnamed building'"
                    ></h4>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-white/5 p-3">
                            <b class="text-xl" x-text="form.floors.length"></b>
                            <p class="text-xs text-slate-400">Floors</p>
                        </div>
                        <div class="rounded-xl bg-white/5 p-3">
                            <b class="text-xl" x-text="addRoomsEnabled ? countDraftRooms() : 0"></b>
                            <p class="text-xs text-slate-400">Rooms to save</p>
                        </div>
                    </div>
                    <p
                        x-show="!addRoomsEnabled"
                        x-cloak
                        class="mt-3 text-xs text-slate-400"
                    >
                        Rooms are skipped for this save.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                <button
                    type="button"
                    @click="closeSaveConfirm()"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="confirmSaveCampus()"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
</template>
