{{--
    Expandable room → department destination picker (Transfer Asset modal).
    field: destination | all | apply
    apply supports multi-select (many departments / many equipment).
--}}
@php
    $field = $field ?? 'destination';
@endphp
<div class="space-y-3">
    <div class="relative">
        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
        <input
            type="text"
            x-model="transferRoomQuery"
            placeholder="Search room or department"
            class="h-10 w-full rounded-xl border-0 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900/10"
            autocomplete="off"
        >
    </div>
    <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
        <div class="max-h-64 overflow-y-auto">
            <ul class="divide-y divide-slate-100" x-show="shownTransferDestRooms().length">
                <template x-for="room in shownTransferDestRooms()" :key="'tdest-{{ $field }}-' + room.id">
                    <li>
                        <div class="flex items-center">
                            <button
                                type="button"
                                class="flex shrink-0 items-center self-stretch pl-3"
                                :title="transferPickerIsMulti('{{ $field }}') ? ('Select or clear destinations in ' + room.name) : ('Transfer to entire ' + room.name)"
                                @click="selectTransferDestination('{{ $field }}', room, { id: 0, name: 'Entire room' })"
                                :aria-pressed="isTransferRoomEntireSelected('{{ $field }}', room) ? 'true' : 'false'"
                                :aria-label="'Transfer to entire ' + room.name"
                            >
                                <span
                                    class="flex h-4 w-4 items-center justify-center border-2"
                                    :class="[
                                        transferPickerIsMulti('{{ $field }}') ? 'rounded' : 'rounded-full',
                                        isTransferRoomEntireSelected('{{ $field }}', room)
                                            ? 'border-[#005EA6] bg-[#005EA6] text-white'
                                            : 'border-slate-300 bg-white text-transparent'
                                    ]"
                                >
                                    <svg x-show="isTransferRoomEntireSelected('{{ $field }}', room)" class="h-3 w-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                        <path d="M2.5 6.2L4.8 8.5L9.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </button>
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
                                :aria-expanded="isTransferRoomOpen(room) ? 'true' : 'false'"
                                @click="toggleTransferRoomOpen(room)"
                            >
                                <span class="inline-flex h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="isTransferRoomOpen(room) ? 'rotate-90' : ''">
                                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">
                                        <span x-text="room.name"></span>
                                        <span class="ml-1 text-xs font-normal text-slate-400" x-text="((room.departments?.length || 1)) + ' option' + ((room.departments?.length || 1) === 1 ? '' : 's')"></span>
                                    </p>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-500" x-text="room.type || 'Room'"></p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="transferRoomSelectedLabel('{{ $field }}', room) !== 'None selected' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'"
                                    x-text="transferRoomSelectedLabel('{{ $field }}', room)"
                                ></span>
                            </button>
                        </div>
                        <ul x-show="isTransferRoomOpen(room)" x-cloak class="border-t border-slate-100 bg-slate-50/60 pl-8">
                            <template x-for="dept in orderedTransferDepartments('{{ $field }}', room)" :key="'tdept-{{ $field }}-' + room.id + '-' + dept.id">
                                <li class="border-l border-slate-200/90">
                                    <button
                                        type="button"
                                        class="flex w-full items-start gap-3 py-2 pl-3 pr-3 text-left transition"
                                        :class="isTransferDestinationSelected('{{ $field }}', room.id, dept.id) ? 'bg-[#005EA6]/5 ring-inset ring-1 ring-[#005EA6]/20' : 'hover:bg-white'"
                                        @click="selectTransferDestination('{{ $field }}', room, dept)"
                                        :aria-pressed="isTransferDestinationSelected('{{ $field }}', room.id, dept.id) ? 'true' : 'false'"
                                        :aria-label="'Transfer to ' + dept.name + ' in ' + room.name"
                                    >
                                        <span
                                            class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center border-2"
                                            :class="[
                                                transferPickerIsMulti('{{ $field }}') ? 'rounded' : 'rounded-full',
                                                isTransferDestinationSelected('{{ $field }}', room.id, dept.id)
                                                    ? 'border-[#005EA6] bg-[#005EA6] text-white'
                                                    : 'border-slate-300 bg-white text-transparent'
                                            ]"
                                        >
                                            <template x-if="transferPickerIsMulti('{{ $field }}')">
                                                <svg x-show="isTransferDestinationSelected('{{ $field }}', room.id, dept.id)" class="h-3 w-3" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                                                    <path d="M2.5 6.2L4.8 8.5L9.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </template>
                                            <template x-if="!transferPickerIsMulti('{{ $field }}')">
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-white"
                                                    x-show="isTransferDestinationSelected('{{ $field }}', room.id, dept.id)"
                                                ></span>
                                            </template>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-slate-900" x-text="dept.name"></p>
                                            <p x-show="dept.id === 0" class="mt-0.5 text-[11px] text-slate-400">Transfer to the whole room (no department scope)</p>
                                            <p x-show="dept.id > 0 && dept.equipment_count > 0" class="mt-0.5 text-[11px] text-slate-400">
                                                <span x-text="dept.equipment_count"></span> equipment in this room
                                            </p>
                                        </div>
                                        <span
                                            x-show="dept.id > 0"
                                            class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="isTransferDestinationSelected('{{ $field }}', room.id, dept.id) ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'"
                                            x-text="isTransferDestinationSelected('{{ $field }}', room.id, dept.id) ? 'Selected' : 'Available'"
                                        ></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </li>
                </template>
            </ul>
            <p x-show="!shownTransferDestRooms().length" class="px-4 py-6 text-center text-sm text-slate-400">No rooms match.</p>
        </div>
    </div>
    <p x-show="transferPickerIsMulti('{{ $field }}')" class="text-[11px] text-slate-500">
        Sequential fill: click a department to assign empty rows only. Checkmarks show destinations already used. Uncheck to clear those rows. Apply to selected fills checked empty rows with the last clicked destination.
    </p>
</div>
