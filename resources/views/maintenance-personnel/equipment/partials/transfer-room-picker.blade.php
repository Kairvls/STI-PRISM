@php
    $pickerId = $pickerId ?? 'transferRoomPicker';
    $inputName = $inputName ?? 'room_id';
    $label = $label ?? 'Transfer to';
    $roomsPayload = collect($rooms ?? [])
        ->map(fn ($room) => [
            'id' => (int) $room->room_id,
            'name' => (string) $room->room_name,
        ])
        ->values();
@endphp

<div
    id="{{ $pickerId }}"
    x-data="transferRoomPicker(@js($roomsPayload))"
    class="relative"
    @reset-room-picker.window="if ($event.detail?.id === '{{ $pickerId }}') clear()"
>
    <label
        for="{{ $pickerId }}_search"
        class="mb-2 block text-sm font-semibold text-slate-700"
    >
        {{ $label }}
    </label>

    <input
        type="hidden"
        name="{{ $inputName }}"
        x-model="selectedId"
        required
    >

    <div class="relative">
        <i
            data-lucide="search"
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
        ></i>

        <input
            id="{{ $pickerId }}_search"
            type="text"
            x-model="query"
            @focus="open = true"
            @click="open = true"
            @keydown.escape.prevent="open = false"
            placeholder="Search destination room..."
            autocomplete="off"
            class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
        >

        <button
            type="button"
            x-show="query || selectedId"
            x-cloak
            @click="clear()"
            class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            aria-label="Clear room search"
        >
            <i data-lucide="x" class="h-3.5 w-3.5"></i>
        </button>
    </div>

    <p
        x-show="selectedName"
        x-cloak
        class="mt-2 text-xs text-slate-500"
    >
        Selected:
        <span class="font-semibold text-slate-700" x-text="selectedName"></span>
    </p>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition
        class="absolute z-50 mt-1 max-h-52 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg shadow-slate-900/10"
    >
        <template x-for="room in filteredRooms" :key="room.id">
            <button
                type="button"
                @click="select(room)"
                class="flex w-full items-center px-3 py-2.5 text-left text-sm transition hover:bg-slate-50"
                :class="String(selectedId) === String(room.id) ? 'bg-sky-50 font-semibold text-[#0025cc]' : 'text-slate-700'"
            >
                <span x-text="room.name"></span>
            </button>
        </template>

        <p
            x-show="filteredRooms.length === 0"
            x-cloak
            class="px-3 py-6 text-center text-sm text-slate-500"
        >
            No rooms match your search.
        </p>
    </div>
</div>
