@extends('layouts.admin-layout')

@section('title', 'Computer Sets')

@section('content')
@php
    $roomOptions = collect($rooms ?? [])->map(function ($room) {
        $label = trim(($room->floor_level ? $room->floor_level.' — ' : '').$room->room_name);

        return [
            'id' => (string) $room->room_id,
            'label' => $label,
        ];
    })->values();
@endphp

<div class="space-y-6" x-data="computerSetsIndex(@js($roomOptions), @js((string) ($roomId ?: '')))">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Computer set tables are missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this module.
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Computer sets</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Each set gets a <span class="font-mono text-xs">COMPSET-YYYYMMDD-#####</span> tag. Equipment inside keep their own QR / asset tags.
                    </p>
                </div>
                <button
                    type="button"
                    @click="openCreate = true"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    New computer set
                </button>
            </div>

            <form
                data-auto-filter
                method="GET"
                action="{{ \App\Support\ComputerSets::url() }}"
                class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:flex-wrap sm:items-end sm:px-5"
            >
                <div class="relative min-w-[220px] flex-1">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                    <div class="flex gap-2">
                        <div class="relative min-w-0 flex-1">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="COMPSET tag, name, room, row…"
                                class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                        </div>
                        <button
                            type="submit"
                            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-blue-800"
                        >
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Search
                        </button>
                    </div>
                </div>
                <div class="relative z-20 min-w-[220px]" @click.outside="filterRoomOpen = false">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Room</label>
                    <input type="hidden" name="room" x-ref="filterRoomInput" :value="filterRoomId">
                    <button
                        type="button"
                        @click="filterRoomOpen = !filterRoomOpen; if (filterRoomOpen) { filterRoomQuery = ''; $nextTick(() => $refs.filterRoomSearch?.focus()) }"
                        class="flex h-10 w-full min-w-[220px] items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                        <span class="min-w-0 truncate" :class="filterRoomId ? 'text-slate-800' : 'text-slate-400'" x-text="filterRoomLabel()"></span>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                    </button>
                    <div
                        x-show="filterRoomOpen"
                        x-cloak
                        class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                    >
                        <div class="border-b border-slate-100 p-2">
                            <input
                                type="search"
                                x-ref="filterRoomSearch"
                                x-model="filterRoomQuery"
                                placeholder="Search rooms…"
                                class="h-8 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                @keydown.escape.stop="filterRoomOpen = false"
                            >
                        </div>
                        <ul class="max-h-52 overflow-y-auto py-1">
                            <li>
                                <button type="button" class="flex w-full px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50" @click="pickFilterRoom('')">All rooms</button>
                            </li>
                            <template x-for="room in filteredFilterRooms()" :key="'filter-' + room.id">
                                <li>
                                    <button
                                        type="button"
                                        class="flex w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50"
                                        :class="filterRoomId === room.id ? 'bg-slate-50 font-medium' : ''"
                                        x-text="room.label"
                                        @click="pickFilterRoom(room.id)"
                                    ></button>
                                </li>
                            </template>
                            <li x-show="filteredFilterRooms().length === 0" class="px-3 py-3 text-center text-xs text-slate-400">No rooms match</li>
                        </ul>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select name="status" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                        <option value="Active" @selected($status === 'Active')>Active</option>
                        <option value="Inactive" @selected($status === 'Inactive')>Inactive</option>
                        <option value="all" @selected($status === 'all')>All</option>
                    </select>
                </div>
            </form>

            <div class="overflow-x-auto rounded-b-2xl">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Set tag</th>
                            <th class="px-5 py-3">Name / zone</th>
                            <th class="px-5 py-3">Room</th>
                            <th class="px-5 py-3">Members</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sets as $set)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <span class="font-mono text-xs font-semibold text-[#0025cc]">{{ $set->computer_set_tag }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900">{{ $set->computer_set_name ?: 'Computer set' }}</div>
                                    @if ($set->computer_set_placement_zone)
                                        <div class="text-xs text-slate-500">{{ $set->computer_set_placement_zone }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    @if ($set->room_name)
                                        {{ trim(($set->floor_level ? $set->floor_level.' — ' : '').$set->room_name) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ (int) $set->member_count }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $set->computer_set_status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $set->computer_set_status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a
                                        href="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id) }}"
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-[#0025cc] hover:underline"
                                    >
                                        Manage
                                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No computer sets yet. Create one and place monitor, keyboard, mouse, system unit, and AVR inside it.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Create modal: teleport to body so backdrop covers topbar + sidebar --}}
    <template x-teleport="body">
    <div
        x-show="openCreate"
        x-cloak
        class="fixed inset-0 z-[1000] flex items-end justify-center bg-slate-950/50 p-3 sm:items-center"
        @keydown.escape.window="openCreate = false"
    >
        <div class="absolute inset-0" @click="openCreate = false"></div>
        <div class="relative w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">New computer set</h3>
                    <p class="text-xs text-slate-500">Tag is auto-generated as COMPSET-YYYYMMDD-#####</p>
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" @click="openCreate = false">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form
                method="POST"
                action="{{ \App\Support\ComputerSets::url() }}"
                class="space-y-4 p-5"
                @submit="if (!roomId) { $event.preventDefault(); createRoomOpen = true; $nextTick(() => $refs.createRoomSearch?.focus()); }"
            >
                @csrf
                <div class="relative" @click.outside="createRoomOpen = false">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Room <span class="text-rose-500">*</span></label>
                    <input type="hidden" name="computer_set_room_id" x-model="roomId">
                    <button
                        type="button"
                        @click="createRoomOpen = !createRoomOpen; if (createRoomOpen) { createRoomQuery = ''; $nextTick(() => $refs.createRoomSearch?.focus()) }"
                        class="flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                        <span class="min-w-0 truncate" :class="roomId ? 'text-slate-800' : 'text-slate-400'" x-text="createRoomLabel()"></span>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                    </button>
                    <div
                        x-show="createRoomOpen"
                        x-cloak
                        class="absolute left-0 right-0 top-full z-40 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                    >
                        <div class="border-b border-slate-100 p-2">
                            <input
                                type="search"
                                x-ref="createRoomSearch"
                                x-model="createRoomQuery"
                                placeholder="Search rooms…"
                                class="h-8 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                @keydown.escape.stop="createRoomOpen = false"
                            >
                        </div>
                        <ul class="max-h-52 overflow-y-auto py-1">
                            <li>
                                <button type="button" class="flex w-full px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50" @click="pickCreateRoom('')">Select room</button>
                            </li>
                            <template x-for="room in filteredCreateRooms()" :key="'create-' + room.id">
                                <li>
                                    <button
                                        type="button"
                                        class="flex w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50"
                                        :class="roomId === room.id ? 'bg-slate-50 font-medium' : ''"
                                        x-text="room.label"
                                        @click="pickCreateRoom(room.id)"
                                    ></button>
                                </li>
                            </template>
                            <li x-show="filteredCreateRooms().length === 0" class="px-3 py-3 text-center text-xs text-slate-400">No rooms match</li>
                        </ul>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Display name</label>
                        <input
                            type="text"
                            name="computer_set_name"
                            placeholder="e.g. Computer Set 12"
                            class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Row / zone</label>
                        <select
                            name="computer_set_placement_zone"
                            x-model="zoneId"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            :disabled="!roomId || loadingZones"
                        >
                            <option value="" x-text="zonePlaceholder()"></option>
                            <template x-for="zone in zones" :key="'zone-' + zone">
                                <option :value="zone" x-text="zone"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">From campus layout rows / zones already used in this room</p>
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Place computer equipment inside (optional)</label>
                            <p class="mt-0.5 text-[11px] text-slate-400">Computer parts in this room (incl. TV) · tags/QR/serial shown for uniqueness</p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400" x-text="selectedCount() + ' selected'"></span>
                    </div>
                    <div class="max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3">
                        <template x-if="!roomId">
                            <p class="py-6 text-center text-sm text-slate-400">Select a room first.</p>
                        </template>
                        <template x-if="roomId && loading">
                            <p class="py-6 text-center text-sm text-slate-400">Loading equipment…</p>
                        </template>
                        <template x-if="roomId && !loading && available.length === 0">
                            <p class="py-6 text-center text-sm text-slate-400">No free computer equipment in this room (or all are already in a set).</p>
                        </template>
                        <template x-for="item in available" :key="item.equipment_id">
                            <label class="flex items-start gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                                <input
                                    type="checkbox"
                                    class="mt-1 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                                    :value="item.equipment_id"
                                    name="equipment_ids[]"
                                    x-model="selected"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-slate-900" x-text="item.equipment_name"></span>
                                    <span class="mt-0.5 block font-mono text-[11px] text-slate-600" x-text="equipmentUniqueId(item)"></span>
                                    <span class="mt-0.5 block text-[11px] text-slate-400" x-text="equipmentMeta(item)"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCreate = false" class="h-10 rounded-xl px-4 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Create set
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>

<script>
function computerSetsIndex(rooms, initialFilterRoomId) {
    return {
        openCreate: false,
        rooms: Array.isArray(rooms) ? rooms : [],
        roomId: '',
        zoneId: '',
        zones: [],
        available: [],
        selected: [],
        loading: false,
        loadingZones: false,
        createRoomOpen: false,
        createRoomQuery: '',
        filterRoomOpen: false,
        filterRoomQuery: '',
        filterRoomId: String(initialFilterRoomId || ''),
        selectedCount() {
            return this.selected.length;
        },
        zonePlaceholder() {
            if (!this.roomId) return 'Select a room first';
            if (this.loadingZones) return 'Loading rows…';
            if (!this.zones.length) return 'No rows set for this room';
            return 'Select row / zone';
        },
        roomLabel(id) {
            if (!id) return '';
            const match = this.rooms.find((room) => String(room.id) === String(id));
            return match ? match.label : '';
        },
        createRoomLabel() {
            return this.roomLabel(this.roomId) || 'Select room';
        },
        filterRoomLabel() {
            return this.roomLabel(this.filterRoomId) || 'All rooms';
        },
        filteredCreateRooms() {
            const q = this.createRoomQuery.trim().toLowerCase();
            if (!q) return this.rooms;
            return this.rooms.filter((room) => String(room.label || '').toLowerCase().includes(q));
        },
        filteredFilterRooms() {
            const q = this.filterRoomQuery.trim().toLowerCase();
            if (!q) return this.rooms;
            return this.rooms.filter((room) => String(room.label || '').toLowerCase().includes(q));
        },
        pickCreateRoom(id) {
            this.roomId = String(id || '');
            this.zoneId = '';
            this.createRoomOpen = false;
            this.createRoomQuery = '';
            this.loadZones();
            this.loadAvailable();
        },
        async loadZones() {
            this.zones = [];
            if (!this.roomId) return;
            this.loadingZones = true;
            try {
                const res = await fetch(`{{ \App\Support\ComputerSets::url('/available-zones') }}?room=${encodeURIComponent(this.roomId)}`);
                const data = await res.json();
                this.zones = Array.isArray(data) ? data : [];
            } catch (e) {
                this.zones = [];
            } finally {
                this.loadingZones = false;
            }
        },
        pickFilterRoom(id) {
            this.filterRoomId = String(id || '');
            this.filterRoomOpen = false;
            this.filterRoomQuery = '';
            this.$nextTick(() => {
                if (this.$refs.filterRoomInput) {
                    this.$refs.filterRoomInput.value = this.filterRoomId;
                }
                const form = this.$el.querySelector('form[data-auto-filter]');
                if (!form) return;
                // Auto-apply room filter immediately (preserve search + status).
                const params = new URLSearchParams(new FormData(form));
                if (!this.filterRoomId) {
                    params.delete('room');
                } else {
                    params.set('room', this.filterRoomId);
                }
                const qs = params.toString();
                window.location.assign(form.action + (qs ? '?' + qs : ''));
            });
        },
        equipmentUniqueId(item) {
            const assetTag = String(item.equipment_asset_tag || '').trim();
            const qr = String(item.equipment_qr_code || '').trim();
            const serial = String(item.equipment_serial_number || '').trim();
            if (assetTag) return 'Tag: ' + assetTag;
            if (qr) return 'QR: ' + qr;
            if (serial) return 'SN: ' + serial;
            return 'ID #' + item.equipment_id;
        },
        equipmentMeta(item) {
            const parts = [];
            const brandModel = [item.equipment_brand_name, item.equipment_model]
                .map((part) => String(part || '').trim())
                .filter(Boolean)
                .join(' ');
            if (brandModel) parts.push(brandModel);
            if (item.equipment_placement_zone) parts.push(item.equipment_placement_zone);
            if (item.equipment_condition_status) parts.push(item.equipment_condition_status);
            if (item.equipment_category_name) parts.push(item.equipment_category_name);
            // Prefer showing both tag and QR when both exist for uniqueness.
            const assetTag = String(item.equipment_asset_tag || '').trim();
            const qr = String(item.equipment_qr_code || '').trim();
            const serial = String(item.equipment_serial_number || '').trim();
            if (assetTag && qr) parts.unshift('QR: ' + qr);
            else if (assetTag && serial) parts.unshift('SN: ' + serial);
            else if (!assetTag && qr && serial) parts.unshift('SN: ' + serial);
            return parts.join(' · ');
        },
        async loadAvailable() {
            this.available = [];
            this.selected = [];
            if (!this.roomId) return;
            this.loading = true;
            try {
                const res = await fetch(`{{ \App\Support\ComputerSets::url('/available-equipment') }}?room=${encodeURIComponent(this.roomId)}`);
                this.available = await res.json();
            } catch (e) {
                this.available = [];
            } finally {
                this.loading = false;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },
    };
}
</script>
@endsection
