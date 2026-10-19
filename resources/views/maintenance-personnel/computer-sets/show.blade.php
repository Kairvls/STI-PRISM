@extends('layouts.admin-layout')

@section('title', $set->computer_set_tag)

@section('content')
@php
    $memberIds = $members->pluck('equipment_id')->map(fn ($id) => (int) $id)->all();
    $roomOptions = collect($rooms ?? [])->map(function ($room) {
        $label = trim(($room->floor_level ? $room->floor_level.' — ' : '').$room->room_name);

        return [
            'id' => (string) $room->room_id,
            'label' => $label,
        ];
    })->values();
@endphp

<div
    class="space-y-6"
    x-data="computerSetShow(
        @js($roomOptions),
        @js((string) old('computer_set_room_id', $set->computer_set_room_id)),
        @js((string) old('computer_set_placement_zone', $set->computer_set_placement_zone))
    )"
>
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ \App\Support\ComputerSets::url() }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0025cc] hover:underline">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                All computer sets
            </a>
            <h1 class="mt-2 font-mono text-xl font-semibold text-slate-950">{{ $set->computer_set_tag }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Set tag for reporting · individual equipment keep their own tags (QR / asset tag).
            </p>
        </div>
        <span class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold {{ $set->computer_set_status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
            {{ $set->computer_set_status }}
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Set details</h2>
            </div>
            <form method="POST" action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id) }}" class="space-y-4 p-5">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Display name</label>
                    <input
                        type="text"
                        name="computer_set_name"
                        value="{{ old('computer_set_name', $set->computer_set_name) }}"
                        class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                </div>
                <div class="relative" @click.outside="roomOpen = false">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Room <span class="text-rose-500">*</span></label>
                    <input type="hidden" name="computer_set_room_id" :value="roomId">
                    <button
                        type="button"
                        @click="roomOpen = !roomOpen; if (roomOpen) { roomQuery = ''; $nextTick(() => $refs.roomSearch?.focus()) }"
                        class="flex h-10 w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 text-left text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >
                        <span class="min-w-0 truncate" :class="roomId ? 'text-slate-800' : 'text-slate-400'" x-text="roomLabel()"></span>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400"></i>
                    </button>
                    <div
                        x-show="roomOpen"
                        x-cloak
                        class="absolute left-0 right-0 top-full z-30 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                    >
                        <div class="border-b border-slate-100 p-2">
                            <input
                                type="search"
                                x-ref="roomSearch"
                                x-model="roomQuery"
                                placeholder="Search rooms…"
                                class="h-8 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                                @keydown.escape.stop="roomOpen = false"
                            >
                        </div>
                        <ul class="max-h-52 overflow-y-auto py-1">
                            <template x-for="room in filteredRooms()" :key="'show-' + room.id">
                                <li>
                                    <button
                                        type="button"
                                        class="flex w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-50"
                                        :class="roomId === room.id ? 'bg-slate-50 font-medium' : ''"
                                        x-text="room.label"
                                        @click="roomId = String(room.id); roomOpen = false; roomQuery = ''"
                                    ></button>
                                </li>
                            </template>
                            <li x-show="filteredRooms().length === 0" class="px-3 py-3 text-center text-xs text-slate-400">No rooms match</li>
                        </ul>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Row / zone</label>
                    <select
                        name="computer_set_placement_zone"
                        x-model="zoneId"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        :disabled="loadingZones"
                    >
                        <option value="" x-text="zonePlaceholder()"></option>
                        <template x-for="zone in zones" :key="'show-zone-' + zone">
                            <option :value="zone" x-text="zone"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">From campus layout rows / zones already used in this room</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select name="computer_set_status" class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                        <option value="Active" @selected($set->computer_set_status === 'Active')>Active</option>
                        <option value="Inactive" @selected($set->computer_set_status === 'Inactive')>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    Save details
                </button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Equipment inside this set</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Computer parts from this room (Monitor, TV, Keyboard, Mouse, System Unit, AVR). Tags / QR / serial identify each unique unit.
                </p>
            </div>

            <form method="POST" action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id.'/members') }}" class="p-5">
                @csrf
                <div class="space-y-2">
                    @php
                        $pool = $members->concat($available)->unique('equipment_id')->values();
                    @endphp

                    @forelse ($pool as $item)
                        @php
                            $isMember = in_array((int) $item->equipment_id, $memberIds, true);
                            $uniqueBits = array_values(array_filter([
                                filled($item->equipment_asset_tag ?? null) ? 'Tag: '.$item->equipment_asset_tag : null,
                                filled($item->equipment_qr_code ?? null) ? 'QR: '.$item->equipment_qr_code : null,
                                filled($item->equipment_serial_number ?? null) ? 'SN: '.$item->equipment_serial_number : null,
                            ]));
                            if ($uniqueBits === []) {
                                $uniqueBits[] = 'ID #'.$item->equipment_id;
                            }
                            $metaBits = array_values(array_filter([
                                trim(implode(' ', array_filter([$item->equipment_brand_name ?? null, $item->equipment_model ?? null]))),
                                $item->equipment_placement_zone ?? null,
                                $item->equipment_condition_status ?? null,
                            ]));
                        @endphp
                        <label class="flex items-start gap-3 rounded-xl border border-slate-100 px-3 py-3 hover:bg-slate-50">
                            <input
                                type="checkbox"
                                name="equipment_ids[]"
                                value="{{ $item->equipment_id }}"
                                class="mt-1 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                                @checked($isMember)
                            >
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900">{{ $item->equipment_name }}</span>
                                <span class="mt-0.5 block font-mono text-[11px] text-slate-600">{{ implode(' · ', $uniqueBits) }}</span>
                                @if ($metaBits !== [])
                                    <span class="mt-0.5 block text-[11px] text-slate-400">{{ implode(' · ', $metaBits) }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                            No free computer equipment in this room. Stock / deploy Computer Equipment first, then place them here.
                        </div>
                    @endforelse
                </div>

                @if ($pool->isNotEmpty())
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                            <i data-lucide="layers" class="h-4 w-4"></i>
                            Update members
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if ($members->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Current membership</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Individual tag</th>
                            <th class="px-5 py-3">QR</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($members as $member)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-800">
                                    {{ \App\Support\ComputerSets::roleLabel($member->member_role) }}
                                </td>
                                <td class="px-5 py-3 text-slate-900">{{ $member->equipment_name }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $member->equipment_asset_tag ?: '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $member->equipment_qr_code ?: '—' }}</td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id.'/members/remove') }}" onsubmit="return confirm('Remove this equipment from the set?')">
                                        @csrf
                                        <input type="hidden" name="equipment_id" value="{{ $member->equipment_id }}">
                                        <button type="submit" class="text-sm font-semibold text-rose-600 hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
function computerSetShow(rooms, initialRoomId, initialZoneId) {
    return {
        rooms: Array.isArray(rooms) ? rooms : [],
        roomId: String(initialRoomId || ''),
        zoneId: String(initialZoneId || ''),
        zones: [],
        loadingZones: false,
        roomOpen: false,
        roomQuery: '',
        init() {
            this.loadZones();
            this.$watch('roomId', () => {
                this.zoneId = '';
                this.loadZones();
            });
        },
        zonePlaceholder() {
            if (!this.roomId) return 'Select a room first';
            if (this.loadingZones) return 'Loading rows…';
            if (!this.zones.length) return 'No rows set for this room';
            return 'Select row / zone';
        },
        roomLabel() {
            const match = this.rooms.find((room) => String(room.id) === String(this.roomId));
            return match ? match.label : 'Select room';
        },
        filteredRooms() {
            const q = this.roomQuery.trim().toLowerCase();
            if (!q) return this.rooms;
            return this.rooms.filter((room) => String(room.label || '').toLowerCase().includes(q));
        },
        async loadZones() {
            this.zones = [];
            if (!this.roomId) return;
            this.loadingZones = true;
            const keep = String(this.zoneId || '');
            try {
                const res = await fetch(`{{ \App\Support\ComputerSets::url('/available-zones') }}?room=${encodeURIComponent(this.roomId)}`);
                const data = await res.json();
                this.zones = Array.isArray(data) ? data : [];
                if (keep && this.zones.includes(keep)) {
                    this.zoneId = keep;
                } else if (keep && !this.zones.includes(keep)) {
                    // Keep a previously saved custom zone visible even if not in layout list.
                    this.zones = [keep, ...this.zones];
                    this.zoneId = keep;
                }
            } catch (e) {
                this.zones = keep ? [keep] : [];
            } finally {
                this.loadingZones = false;
            }
        },
    };
}
</script>
@endsection
