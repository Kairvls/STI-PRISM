@extends('layouts.maintenance-layout')

@section('title', 'Rooms')

@section('content')
@php
    $nextDir = fn ($column) => ($sort === $column && $dir === 'asc') ? 'desc' : 'asc';
    $sortLink = function ($column, $label) use ($nextDir, $sort, $dir) {
        $arrow = $sort === $column ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
        $href = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir($column)]);
        return '<a href="'.e($href).'" class="hover:text-slate-800">'.e($label.$arrow).'</a>';
    };
    $eqField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $eqLabel = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-end gap-2">
            <form method="POST" action="{{ route('maintenance.rooms.merge') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-[13px] font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-50">
                    Merge duplicates
                </button>
            </form>
            <button
                type="button"
                onclick="openAddRoomModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add room
            </button>
    </div>

    @if (($duplicateRoomGroups ?? 0) > 0)
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $duplicateRoomGroups }} duplicate {{ \Illuminate\Support\Str::plural('room name', $duplicateRoomGroups) }} found.
            Use Merge duplicates to keep one room and move its equipment.
        </p>
    @endif

    @if (session('success'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
    @endif

    

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <form method="GET" class="flex flex-wrap items-center gap-2 m-5">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search room or floor"
                class="h-10 w-full max-w-xs rounded-xl border-0 bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80 outline-none focus:bg-white focus:ring-2 focus:ring-slate-900/10"
            />
            <input type="hidden" name="sort" value="{{ $sort }}" />
            <input type="hidden" name="dir" value="{{ $dir }}" />
            <button class="h-10 rounded-xl bg-neutral-100 px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-200/80">Search</button>
        </form>

        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-[12px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">{!! $sortLink('room_name', 'Room') !!}</th>
                    <th class="px-5 py-3">{!! $sortLink('type', 'Room type') !!}</th>
                    <th class="px-5 py-3">{!! $sortLink('floor', 'Floor') !!}</th>
                    <th class="px-5 py-3">{!! $sortLink('equipment', 'Equipment') !!}</th>
                    
                    <th class="px-5 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-4 font-medium text-slate-900">
                            <button
                                type="button"
                                class="text-left hover:underline"
                                onclick="openRoomPeek({{ $room->room_id }}, {{ json_encode($room->room_name) }})"
                            >
                                {{ $room->room_name }}
                            </button>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            {{ $roomTypes[$room->room_type] ?? ($room->room_type ?: '—') }}
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $room->floor_level ?: '—' }}</td>
                        <td class="px-5 py-4 text-slate-900">{{ number_format($room->equipment_count) }}</td>
                        
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onclick="openRoomPeek({{ $room->room_id }}, {{ json_encode($room->room_name) }})"
                                    class="inline-flex h-9 items-center rounded-xl px-3 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80 transition hover:bg-slate-50"
                                >
                                    Peek
                                </button>
                                <button
                                    type="button"
                                    onclick='openEditRoomModal(@json($room))'
                                    class="inline-flex h-9 items-center rounded-xl px-3 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80 transition hover:bg-slate-50"
                                >
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('maintenance.rooms.archive', $room->room_id) }}" onsubmit="return confirm('Archive this room? Equipment stays assigned until you move it.');">
                                    @csrf
                                    <button type="submit" class="inline-flex h-9 items-center rounded-xl px-3 text-xs font-medium text-rose-600 ring-1 ring-rose-100 transition hover:bg-rose-50">
                                        Archive
                                    </button>
                                </form>
                                <a
                                    href="{{ url('/maintenance/equipment/inventory') }}?room={{ $room->room_id }}"
                                    class="inline-flex h-9 items-center rounded-xl bg-slate-900 px-3 text-xs font-medium text-white transition hover:bg-slate-800"
                                >
                                    View equipment
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">No rooms found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="m-5">
        {{ $rooms->links() }}
        </div>
    </div>

    
</div>

@if ($errors->any())
<div
    id="roomValidationModal"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-[#0b1220]/70 p-4"
>
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/20">
        <div class="px-6 pt-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                <i data-lucide="alert-circle" class="h-6 w-6"></i>
            </div>
            <h2 class="mt-4 text-lg font-semibold tracking-tight text-slate-900">Room already exists</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $errors->first() }}</p>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-5">
            <button type="button" onclick="closeRoomValidationModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Close</button>
            <button type="button" onclick="retryAddRoomFromValidation()" class="h-10 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800">Change name</button>
        </div>
    </div>
</div>
@endif

<div
    id="addRoomModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <form
        action="{{ route('maintenance.rooms.store') }}"
        method="POST"
        class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
    >
        @csrf
        <div class="flex items-start justify-between px-6 pt-6">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Add room</h2>
                <p class="mt-1 text-sm text-slate-500">Name it, then pick the floor it belongs to.</p>
            </div>
            <button type="button" onclick="closeAddRoomModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 px-6 py-5 lg:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <label for="add_room_name" class="{{ $eqLabel }}">Room name <span class="text-rose-500">*</span></label>
                    <input id="add_room_name" type="text" name="room_name" required value="{{ old('room_name') }}" placeholder="e.g. ComLab 1" class="{{ $eqField }}" />
                </div>
                <div>
                    <label for="add_room_floor" class="{{ $eqLabel }}">Floor <span class="text-rose-500">*</span></label>
                    <select id="add_room_floor" name="room_floor_id" required class="{{ $eqField }}">
                        <option value="">Select floor</option>
                        @forelse ($floors as $floor)
                            <option value="{{ $floor->floor_id }}" @selected(old('room_floor_id') == $floor->floor_id)>
                                {{ $floor->building_name ? $floor->building_name.' · ' : '' }}{{ $floor->floor_level }}
                            </option>
                        @empty
                            <option value="" disabled>No floors yet. Add them in Buildings Layout.</option>
                        @endforelse
                    </select>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="add_room_type" class="{{ $eqLabel }}">Room type</label>
                    <select id="add_room_type" name="room_type" class="{{ $eqField }}">
                        @foreach ($roomTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('room_type', 'Lecture Room') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="add_room_status" class="{{ $eqLabel }}">Status</label>
                    <select id="add_room_status" name="room_status" class="{{ $eqField }}">
                        <option value="Normal" @selected(old('room_status', 'Normal') === 'Normal')>Normal</option>
                        <option value="Maintenance Needed" @selected(old('room_status') === 'Maintenance Needed')>Maintenance Needed</option>
                        <option value="Critical" @selected(old('room_status') === 'Critical')>Critical</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4">
            <button type="button" onclick="closeAddRoomModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
            <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800" @disabled($floors->isEmpty())>Add room</button>
        </div>
    </form>
</div>

<div
    id="editRoomModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <form
        id="editRoomForm"
        method="POST"
        class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
    >
        @csrf
        <div class="flex items-start justify-between px-6 pt-6">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit room</h2>
                <p class="mt-1 text-sm text-slate-500">Update the name, floor, or type.</p>
            </div>
            <button type="button" onclick="closeEditRoomModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 gap-6 px-6 py-5 lg:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <label for="edit_room_name" class="{{ $eqLabel }}">Room name <span class="text-rose-500">*</span></label>
                    <input id="edit_room_name" type="text" name="room_name" required class="{{ $eqField }}" />
                </div>
                <div>
                    <label for="edit_room_floor" class="{{ $eqLabel }}">Floor <span class="text-rose-500">*</span></label>
                    <select id="edit_room_floor" name="room_floor_id" required class="{{ $eqField }}">
                        <option value="">Select floor</option>
                        @foreach ($floors as $floor)
                            <option value="{{ $floor->floor_id }}">
                                {{ $floor->building_name ? $floor->building_name.' · ' : '' }}{{ $floor->floor_level }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="edit_room_type" class="{{ $eqLabel }}">Room type</label>
                    <select id="edit_room_type" name="room_type" class="{{ $eqField }}">
                        @foreach ($roomTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit_room_status" class="{{ $eqLabel }}">Status</label>
                    <select id="edit_room_status" name="room_status" class="{{ $eqField }}">
                        <option value="Normal">Normal</option>
                        <option value="Maintenance Needed">Maintenance Needed</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4">
            <button type="button" onclick="closeEditRoomModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
            <button type="submit" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white transition hover:bg-slate-800">Save changes</button>
        </div>
    </form>
</div>

<div
    id="roomPeekModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <div class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
        <div class="flex items-start justify-between px-6 pt-6">
            <div>
                <h2 id="roomPeekTitle" class="text-lg font-semibold tracking-tight text-slate-900">Room</h2>
                <p id="roomPeekCount" class="mt-1 text-sm text-slate-500"></p>
            </div>
            <button type="button" onclick="closeRoomPeek()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div id="roomPeekList" class="min-h-0 flex-1 overflow-y-auto pb-2"></div>
        <div class="flex items-center justify-end gap-2 px-6 py-4">
            <button type="button" onclick="closeRoomPeek()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Close</button>
            <a
                id="roomPeekInventory"
                href="/maintenance/equipment/inventory"
                class="inline-flex h-10 items-center rounded-xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800"
            >
                Open in Inventory
            </a>
        </div>
    </div>
</div>

<script>
    function openAddRoomModal() {
        const modal = document.getElementById('addRoomModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function closeAddRoomModal() {
        const modal = document.getElementById('addRoomModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditRoomModal(room) {
        const form = document.getElementById('editRoomForm');
        form.action = '/maintenance/rooms/' + room.room_id;
        document.getElementById('edit_room_name').value = room.room_name || '';
        document.getElementById('edit_room_floor').value = room.room_floor_id || '';
        document.getElementById('edit_room_type').value = room.room_type || 'Lecture Room';
        document.getElementById('edit_room_status').value = room.room_status || 'Normal';
        const modal = document.getElementById('editRoomModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function closeEditRoomModal() {
        const modal = document.getElementById('editRoomModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function closeRoomValidationModal() {
        const modal = document.getElementById('roomValidationModal');
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function retryAddRoomFromValidation() {
        closeRoomValidationModal();
        openAddRoomModal();
    }

    function closeRoomPeek() {
        const modal = document.getElementById('roomPeekModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openRoomPeek(roomId, roomName) {
        const modal = document.getElementById('roomPeekModal');
        const list = document.getElementById('roomPeekList');
        const title = document.getElementById('roomPeekTitle');
        const count = document.getElementById('roomPeekCount');
        const inventory = document.getElementById('roomPeekInventory');

        title.textContent = roomName;
        count.textContent = 'Loading…';
        list.innerHTML = '<p class="px-6 py-8 text-sm text-slate-400">Loading equipment…</p>';
        inventory.href = '/maintenance/equipment/inventory?room=' + roomId;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        if (window.lucide) {
            window.lucide.createIcons();
        }

        fetch('/maintenance/rooms/' + roomId + '/equipment', {
            headers: { Accept: 'application/json' },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Could not load equipment.');
                }
                return response.json();
            })
            .then(function (data) {
                count.textContent = data.count + (data.count === 1 ? ' item' : ' items');
                inventory.href = data.inventory_url;
                if (!data.items.length) {
                    list.innerHTML = '<p class="px-6 py-8 text-sm text-slate-400">No equipment in this room.</p>';
                    return;
                }
                list.innerHTML = data.items
                    .map(function (item) {
                        const qty = item.equipment_quantity ? ' × ' + item.equipment_quantity : '';
                        const category = item.equipment_category_name || 'Uncategorized';
                        const status = item.equipment_inventory_status || '';
                        return (
                            '<div class="flex items-start justify-between gap-4 border-t border-slate-100 px-6 py-3">' +
                            '<div class="min-w-0">' +
                            '<p class="truncate text-sm font-medium text-slate-900">' +
                            escapeHtml(item.equipment_name) +
                            qty +
                            '</p>' +
                            '<p class="mt-0.5 text-xs text-slate-400">' +
                            escapeHtml(category) +
                            '</p>' +
                            '</div>' +
                            '<span class="shrink-0 text-xs text-slate-500">' +
                            escapeHtml(status) +
                            '</span>' +
                            '</div>'
                        );
                    })
                    .join('');
            })
            .catch(function () {
                count.textContent = '';
                list.innerHTML = '<p class="px-6 py-8 text-sm text-rose-600">Could not load equipment.</p>';
            });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>
@endsection
