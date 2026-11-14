@extends('layouts.admin-layout')

@section('title', $set->computer_set_tag)

@section('content')
@php
    $memberIds = $members->pluck('equipment_id')->map(fn ($id) => (int) $id)->all();
    $needsReplacementCount = $members->where('needs_replacement', true)->count();
    $memberHistory = $memberHistory ?? collect();
    $roomOptions = collect($rooms ?? [])->map(function ($room) {
        $label = trim(($room->floor_level ? $room->floor_level.' — ' : '').$room->room_name);

        return [
            'id' => (string) $room->room_id,
            'label' => $label,
        ];
    })->values();
    $storageByRole = $storageByRole ?? collect();
    $poolItems = $members->concat($available)->unique('equipment_id')->values()->map(function ($item) {
        return [
            'equipment_id' => (int) $item->equipment_id,
            'equipment_name' => $item->equipment_name,
            'suggested_role' => $item->suggested_role
                ?? $item->member_role
                ?? \App\Support\ComputerSets::inferRoleFromName($item->equipment_name)
                ?? 'other',
            'from_storage' => ! empty($item->from_storage),
            'is_member' => false,
        ];
    });
@endphp

<div
    class="space-y-6"
    x-data="computerSetShow(
        @js($roomOptions),
        @js((string) old('computer_set_room_id', $set->computer_set_room_id)),
        @js((string) old('computer_set_placement_zone', $set->computer_set_placement_zone)),
        @js($memberIds),
        @js($poolItems)
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
            @php
                $computerSetsFallback = \App\Support\ComputerSets::url();
                $computerSetsReferer = (string) (request()->headers->get('referer') ?? '');
                $computerSetsBackUrl = $computerSetsFallback;
                $computerSetsBackLabel = 'All computer sets';
                if ($computerSetsReferer !== '') {
                    $refHost = parse_url($computerSetsReferer, PHP_URL_HOST);
                    $refPath = rtrim((string) (parse_url($computerSetsReferer, PHP_URL_PATH) ?: ''), '/');
                    $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost();
                    $currentPath = rtrim('/'.ltrim(request()->path(), '/'), '/');
                    if ($refHost === $appHost && $refPath !== '' && $refPath !== $currentPath) {
                        $computerSetsBackUrl = $computerSetsReferer;
                        if (str_contains($refPath, '/qr-tools')) {
                            $computerSetsBackLabel = 'Back to Property Tagging';
                        } elseif (! preg_match('#/computer-sets/?$#', $refPath)) {
                            $computerSetsBackLabel = 'Back';
                        }
                    }
                }
            @endphp
            <a
                href="{{ $computerSetsBackUrl }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-[#0025cc] hover:underline"
                onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }"
            >
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                {{ $computerSetsBackLabel }}
            </a>
            <h1 class="mt-2 font-mono text-xl font-semibold text-slate-950">{{ $set->computer_set_tag }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Set tag for reporting · individual equipment keep their own tags (QR / asset tag).
            </p>
            @if (filled($set->computer_set_qr_code ?? null))
                <p class="mt-2 text-xs font-semibold text-slate-700">
                    Set QR {{ $set->computer_set_qr_code }}
                    <a href="{{ url('/equipment/'.$set->computer_set_qr_code) }}" target="_blank" class="ml-2 text-[#0025cc] hover:underline">Preview scan</a>
                    <a href="{{ url('/admin/qr-tools/'.$set->computer_set_qr_code.'/print') }}" target="_blank" class="ml-2 text-[#0025cc] hover:underline">Print label</a>
                </p>
            @elseif (\App\Support\ComputerSetQrCodes::ready())
                <form method="POST" action="{{ route('admin.qr-tools.generate-set', $set->computer_set_id) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#001fad]">
                        <i data-lucide="qr-code" class="h-3.5 w-3.5"></i>
                        Generate set QR
                    </button>
                </form>
            @endif
            @if ($needsReplacementCount > 0)
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                    <i data-lucide="alert-triangle" class="h-3.5 w-3.5"></i>
                    {{ $needsReplacementCount }} equipment component{{ $needsReplacementCount === 1 ? '' : 's' }} need{{ $needsReplacementCount === 1 ? 's' : '' }} replacement
                </p>
            @endif
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
            @php
                $setOwners = $setOwners ?? collect();
                $assignedMemberCount = (int) ($assignedMemberCount ?? 0);
                $memberTotal = $members->count();
                $primaryOwner = $setOwners->first();
            @endphp
            <div class="border-b border-slate-100 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Accountable person</p>
                @if ($primaryOwner)
                    <div class="mt-2 flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <i data-lucide="user-round" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            @if (! empty($primaryOwner['person_url']))
                                <a href="{{ $primaryOwner['person_url'] }}" class="text-sm font-semibold text-[#0025cc] hover:underline">
                                    {{ $primaryOwner['name'] ?: 'Person #'.$primaryOwner['custodian_id'] }}
                                </a>
                            @else
                                <p class="text-sm font-semibold text-slate-900">{{ $primaryOwner['name'] ?: '—' }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ implode(' · ', array_filter([
                                    $primaryOwner['employee_id'] ?? null,
                                    $primaryOwner['position'] ?? null,
                                    ((int) ($primaryOwner['held_count'] ?? 0)).' of '.$memberTotal.' '.(\Illuminate\Support\Str::plural('component', $memberTotal)),
                                ])) }}
                            </p>
                            @if ($setOwners->count() > 1)
                                <p class="mt-1 text-[11px] text-amber-700">
                                    Also held by
                                    {{ $setOwners->skip(1)->map(fn ($o) => $o['name'] ?: 'another person')->implode(', ') }}
                                </p>
                            @elseif ($assignedMemberCount < $memberTotal && $memberTotal > 0)
                                <p class="mt-1 text-[11px] text-slate-400">
                                    {{ $memberTotal - $assignedMemberCount }} {{ \Illuminate\Support\Str::plural('component', $memberTotal - $assignedMemberCount) }} still unassigned
                                </p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="mt-2 text-sm text-slate-500">
                        No accountable person yet.
                        @if ($memberTotal > 0)
                            Assign this set from
                            <a href="{{ \App\Support\PropertyAssignments::url() }}" class="font-semibold text-[#0025cc] hover:underline">Property Assignment</a>.
                        @endif
                    </p>
                @endif
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
                    <p class="mt-1 text-[11px] text-slate-400">Changing the room moves every part still with the set onto Floor in the new room (old row is cleared). Place the set into a row from Rooms Layout. Deployed parts stay where they are until returned.</p>
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
                <!--<p class="mb-3 text-xs text-slate-500">One monitor, keyboard, mouse, system unit, TV, or AVR per set. Parts marked Needs replacement can be swapped from storage below — that keeps history of the previous unit.</p>-->
                <p x-show="roleLimitMessage" x-text="roleLimitMessage" class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"></p>
                <div class="space-y-2">
                    @php
                        $pool = $members->concat($available)->unique('equipment_id')->values();
                    @endphp

                    @forelse ($pool as $item)
                        @php
                            $isMember = in_array((int) $item->equipment_id, $memberIds, true);
                            $role = $item->member_role
                                ?? $item->suggested_role
                                ?? \App\Support\ComputerSets::inferRoleFromName($item->equipment_name)
                                ?? 'other';
                            $fromStorage = ! empty($item->from_storage);
                            $uniqueBits = array_values(array_filter([
                                filled($item->equipment_asset_tag ?? null) ? 'Asset Tag: '.$item->equipment_asset_tag : null,
                                filled($item->equipment_qr_code ?? null) ? 'QR: '.$item->equipment_qr_code : null,
                                filled($item->equipment_serial_number ?? null) ? 'SN: '.$item->equipment_serial_number : null,
                            ]));
                            if ($uniqueBits === []) {
                                $uniqueBits[] = 'ID #'.$item->equipment_id;
                            }
                            $metaBits = array_values(array_filter([
                                trim(implode(' ', array_filter([$item->equipment_brand_name ?? null, $item->equipment_model ?? null]))),
                                $fromStorage ? ('Storage: '.($item->room_name ?? 'Holding')) : ($item->equipment_placement_zone ?? null),
                                $item->equipment_condition_status ?? null,
                                ($item->equipment_inventory_status ?? null) === 'For Replacement' ? 'Needs replacement' : null,
                            ]));
                        @endphp
                        <label
                            class="flex items-start gap-3 rounded-xl border px-3 py-3"
                            :class="isRoleBlocked({{ (int) $item->equipment_id }}) ? 'cursor-not-allowed border-slate-100 opacity-50' : 'border-slate-100 hover:bg-slate-50'"
                        >
                            <input
                                type="checkbox"
                                name="equipment_ids[]"
                                value="{{ $item->equipment_id }}"
                                class="mt-1 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]"
                                :disabled="isRoleBlocked({{ (int) $item->equipment_id }})"
                                :checked="isSelected({{ (int) $item->equipment_id }})"
                                @change="toggleEquipment({{ (int) $item->equipment_id }}, '{{ $role }}', $event.target.checked)"
                            >
                            <template x-if="isSelected({{ (int) $item->equipment_id }})">
                                <input type="hidden" name="equipment_roles[{{ $item->equipment_id }}]" value="{{ $role }}">
                            </template>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $item->equipment_name }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">{{ \App\Support\ComputerSets::roleLabel($role) }}</span>
                                    @if ($fromStorage)
                                        <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700">From storage</span>
                                    @endif
                                    @if (($item->equipment_inventory_status ?? null) === 'For Replacement' || ! empty($item->needs_replacement))
                                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-rose-700">Needs replacement</span>
                                    @endif
                                </span>
                                <span class="mt-1 block font-mono text-sm font-medium leading-snug text-slate-700">{{ implode(' · ', $uniqueBits) }}</span>
                                @if ($metaBits !== [])
                                    <span class="mt-0.5 block text-[11px] text-slate-400">{{ implode(' · ', $metaBits) }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                            No free computer equipment in this room or storage. Stock Computer Equipment first, then place them here.
                        </div>
                    @endforelse
                </div>

                @if ($pool->isNotEmpty())
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                            <i data-lucide="layers" class="h-4 w-4"></i>
                            Save equipment
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if ($members->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Current components</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Individual tag</th>
                            <th class="px-5 py-3">Location</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">QR</th>
                            <th class="px-5 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($members as $member)
                            @php
                                $roleKey = (string) ($member->member_role ?? 'other');
                                $roleStock = collect($storageByRole->get($roleKey, []));
                                $isDeployed = ! empty($member->is_deployed);
                            @endphp
                            <tr class="{{ ! empty($member->needs_replacement) ? 'bg-rose-50/30' : '' }}">
                                <td class="px-5 py-3 font-medium text-slate-800 align-top">
                                    {{ \App\Support\ComputerSets::roleLabel($member->member_role) }}
                                </td>
                                <td class="px-5 py-3 text-slate-900 align-top">
                                    <p>{{ $member->equipment_name }}</p>
                                    <p class="mt-0.5 text-[11px] font-medium text-[#0025cc]">Part of this computer set</p>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600 align-top">{{ $member->equipment_asset_tag ?: '—' }}</td>
                                <td class="px-5 py-3 align-top">
                                    <p class="text-sm text-slate-700">{{ $member->equipment_room_name ?: '—' }}</p>
                                    @if ($isDeployed)
                                        <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-100">Deployed away from set</span>
                                    @else
                                        <span class="mt-1 inline-flex rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">With set</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 align-top">
                                    @if (! empty($member->needs_replacement))
                                        <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Needs replacement</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $member->equipment_inventory_status ?: 'OK' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600 align-top">{{ $member->equipment_qr_code ?: '—' }}</td>
                                <td class="px-5 py-3 align-top">
                                    <div class="flex w-full max-w-lg flex-col gap-3">
                                        @if (! empty($member->needs_replacement))
                                            <div class="rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm">
                                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Replace from storage</p>
                                                @if ($roleStock->isNotEmpty())
                                                    <form
                                                        method="POST"
                                                        action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id.'/members/replace') }}"
                                                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                                                        @submit.prevent="confirmReplace($event.target)"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="old_equipment_id" value="{{ $member->equipment_id }}">
                                                        <select
                                                            name="new_equipment_id"
                                                            required
                                                            class="h-9 min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-2 text-xs text-slate-800 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                                            aria-label="Replacement from storage for {{ $member->equipment_name }}"
                                                        >
                                                            <option value="">Pick storage {{ strtolower(\App\Support\ComputerSets::roleLabel($roleKey)) }}…</option>
                                                            @foreach ($roleStock as $stock)
                                                                <option value="{{ $stock->equipment_id }}">
                                                                    {{ $stock->equipment_name }}
                                                                    · {{ $stock->equipment_asset_tag ?: ($stock->equipment_qr_code ?: ('ID #'.$stock->equipment_id)) }}
                                                                    · {{ $stock->room_name ?? 'Storage' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-[#001fad]">
                                                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                                            Replace
                                                        </button>
                                                    </form>
                                                @else
                                                    <p class="text-xs text-slate-500">No matching {{ strtolower(\App\Support\ComputerSets::roleLabel($roleKey)) }} in storage. Stock one first, then refresh this page.</p>
                                                @endif
                                            </div>
                                        @else
                                            @if ($isDeployed)
                                                <form
                                                    method="POST"
                                                    action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id.'/members/return') }}"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="equipment_id" value="{{ $member->equipment_id }}">
                                                    <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-[#0025cc] hover:bg-slate-50">
                                                        <i data-lucide="undo-2" class="h-3.5 w-3.5"></i>
                                                        Return to set room
                                                    </button>
                                                </form>
                                            @else
                                                <form
                                                    method="POST"
                                                    action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id.'/members/deploy') }}"
                                                    class="rounded-xl border border-slate-200 bg-slate-50/70 p-2.5"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="equipment_id" value="{{ $member->equipment_id }}">
                                                    <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Deploy / pull out</p>
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                        <select
                                                            name="to_room_id"
                                                            required
                                                            class="h-9 min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-2 text-xs text-slate-800 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                                        >
                                                            <option value="">Move to room…</option>
                                                            @foreach ($rooms as $room)
                                                                @if ((int) $room->room_id !== (int) ($set->computer_set_room_id ?? 0))
                                                                    <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-white">
                                                            <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                                                            Deploy
                                                        </button>
                                                    </div>
                                                    <p class="mt-1 text-[11px] text-slate-400">Stays part of this set. Changing the set room later will not move this part until you return it.</p>
                                                </form>
                                            @endif
                                            <form
                                                method="POST"
                                                action="{{ \App\Support\ComputerSets::url('/'.$set->computer_set_id.'/members/remove') }}"
                                                @submit.prevent="confirmRemove($event.target, '{{ strtolower(\App\Support\ComputerSets::roleLabel($roleKey)) }}')"
                                            >
                                                @csrf
                                                <input type="hidden" name="equipment_id" value="{{ $member->equipment_id }}">
                                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">
                                                    Remove from computer set
                                                </button>
                                                <p class="mt-0.5 text-[11px] text-slate-400">Confirm to unbind. Asset tag stays the same; only membership ends.</p>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($memberHistory->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Components history</h2>
                <p class="mt-1 text-sm text-slate-500">Previous and current equipment for this computer set (including replacements).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">When</th>
                            <th class="px-5 py-3">Event</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Replaced by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($memberHistory as $event)
                            <tr>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $event->performed_at ? \Illuminate\Support\Carbon::parse($event->performed_at)->format('M j, Y g:i A') : '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $event->event === 'replaced' ? 'bg-amber-50 text-amber-800' : ($event->event === 'removed' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700') }}">
                                        {{ ucfirst($event->event) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-800">{{ \App\Support\ComputerSets::roleLabel($event->member_role) }}</td>
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900">{{ $event->equipment_name ?: ('ID #'.$event->equipment_id) }}</div>
                                    <div class="font-mono text-[11px] text-slate-500">{{ $event->equipment_asset_tag ?: ($event->equipment_qr_code ?: '—') }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($event->replaced_by_equipment_id)
                                        <div class="font-medium text-slate-900">{{ $event->replacement_equipment_name ?: ('ID #'.$event->replaced_by_equipment_id) }}</div>
                                        <div class="font-mono text-[11px] text-slate-500">{{ $event->replacement_equipment_asset_tag ?: ($event->replacement_equipment_qr_code ?: '—') }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
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
function computerSetShow(rooms, initialRoomId, initialZoneId, initialSelected, poolItems) {
    return {
        rooms: Array.isArray(rooms) ? rooms : [],
        roomId: String(initialRoomId || ''),
        zoneId: String(initialZoneId || ''),
        zones: [],
        loadingZones: false,
        selected: (Array.isArray(initialSelected) ? initialSelected : []).map(String),
        pool: Array.isArray(poolItems) ? poolItems : [],
        roleLimitMessage: '',
        uniqueRoles: @js(\App\Support\ComputerSets::uniqueRoles()),
        roleLabels: @js(\App\Support\ComputerSets::ROLES),
        isSelected(id) {
            return this.selected.map(String).includes(String(id));
        },
        roleFor(id) {
            const item = this.pool.find((row) => String(row.equipment_id) === String(id));
            return String(item?.suggested_role || 'other');
        },
        selectedRoleMap() {
            const map = {};
            this.selected.forEach((id) => {
                const role = this.roleFor(id);
                if (!map[role]) map[role] = [];
                map[role].push(String(id));
            });
            return map;
        },
        isRoleBlocked(id) {
            if (this.isSelected(id)) return false;
            const role = this.roleFor(id);
            if (!this.uniqueRoles.includes(role)) return false;
            return (this.selectedRoleMap()[role] || []).length > 0;
        },
        toggleEquipment(id, role, checked) {
            const key = String(id);
            const resolvedRole = String(role || this.roleFor(id) || 'other');
            this.roleLimitMessage = '';
            if (checked) {
                if (this.uniqueRoles.includes(resolvedRole)) {
                    const taken = (this.selectedRoleMap()[resolvedRole] || []).filter((existing) => existing !== key);
                    if (taken.length > 0) {
                        this.roleLimitMessage = 'Only one ' + (this.roleLabels[resolvedRole] || resolvedRole) + ' is allowed per computer set. Uncheck the current one first to replace it.';
                        return;
                    }
                }
                if (!this.isSelected(key)) this.selected.push(key);
                return;
            }
            this.selected = this.selected.filter((existing) => String(existing) !== key);
        },
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
        swalDialog(options) {
            if (typeof Swal === 'undefined') {
                return Promise.resolve({ isConfirmed: window.confirm(options.text || options.title || 'Continue?') });
            }
            return Swal.fire({
                icon: false,
                title: options.title,
                html: options.html || undefined,
                text: options.html ? undefined : options.text,
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Confirm',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                buttonsStyling: false,
                background: '#ffffff',
                color: '#0f172a',
                backdrop: 'rgba(15, 23, 42, 0.35)',
                showClass: { popup: 'swal2-show prism-swal-confirm-in' },
                hideClass: { popup: 'swal2-hide' },
                customClass: {
                    container: 'prism-swal-container',
                    popup: 'prism-swal prism-swal-confirm' + (options.danger ? ' prism-swal-confirm--danger' : ''),
                    title: 'prism-swal-confirm__title' + (options.danger ? ' prism-swal-confirm__title--danger' : ''),
                    htmlContainer: 'prism-swal-confirm__body',
                    actions: 'prism-swal-confirm__actions',
                    closeButton: 'prism-swal-confirm__close',
                    confirmButton: options.danger
                        ? 'prism-swal-confirm__btn prism-swal-confirm__btn--danger'
                        : 'prism-swal-confirm__btn prism-swal-confirm__btn--primary',
                    cancelButton: 'prism-swal-confirm__btn prism-swal-confirm__btn--cancel',
                },
            });
        },
        async confirmReplace(form) {
            const result = await this.swalDialog({
                title: 'Replace this part?',
                html: 'The selected storage unit joins this set. The old unit returns to <strong>storage</strong> marked <strong>Replaced</strong> (swap complete — no longer For Replacement). The swap is logged in part history.',
                confirmText: 'Replace',
            });
            if (result.isConfirmed) form.submit();
        },
        async confirmRemove(form, roleLabel) {
            const role = roleLabel || 'part';
            const result = await this.swalDialog({
                title: 'Remove from computer set?',
                html: 'This <strong>' + role + '</strong> stays in this room under <strong>Holding</strong> for interior layout — not sent to Storage Room. The set will have no ' + role + ' until you add or replace one.',
                confirmText: 'Remove',
                danger: true,
            });
            if (result.isConfirmed) form.submit();
        },
    };
}
</script>

<style>
    .swal2-container.prism-swal-container {
        z-index: 100000 !important;
    }

    @keyframes prismSwalConfirmIn {
        from { opacity: 0; transform: translateY(10px) scale(0.98); }
        to { opacity: 1; transform: none; }
    }

    .prism-swal-confirm-in {
        animation: prismSwalConfirmIn 0.22s ease !important;
    }

    .prism-swal-confirm.swal2-popup {
        width: min(30rem, calc(100vw - 2rem)) !important;
        padding: 1.45rem 1.5rem 1.25rem !important;
        border-radius: 1rem !important;
        border: 1px solid rgba(226, 232, 240, 0.95) !important;
        background: #ffffff !important;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 16px 40px rgba(15, 23, 42, 0.1) !important;
        color: #0f172a !important;
        position: relative !important;
    }

    .prism-swal-confirm .swal2-icon {
        display: none !important;
    }

    .prism-swal-confirm .prism-swal-confirm__close,
    .prism-swal-confirm .swal2-close {
        position: absolute !important;
        top: 1rem !important;
        right: 1rem !important;
        width: 2.15rem !important;
        height: 2.15rem !important;
        margin: 0 !important;
        padding: 0 !important;
        border-radius: 999px !important;
        border: none !important;
        background: #f8fafc !important;
        color: #94a3b8 !important;
        font-size: 1.3rem !important;
        font-weight: 400 !important;
        line-height: 2.15rem !important;
        box-shadow: none !important;
        outline: none !important;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .prism-swal-confirm .prism-swal-confirm__close:hover,
    .prism-swal-confirm .swal2-close:hover {
        background: #f1f5f9 !important;
        color: #0f172a !important;
    }

    .prism-swal-confirm .prism-swal-confirm__title,
    .prism-swal-confirm .prism-swal-confirm__title--danger {
        margin: 0 !important;
        padding: 0 3.25rem 0 0 !important;
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
        letter-spacing: -0.025em !important;
        color: #0f172a !important;
        text-align: left !important;
        max-width: calc(100% - 0.25rem) !important;
    }

    .prism-swal-confirm .prism-swal-confirm__body,
    .prism-swal-confirm .swal2-html-container {
        margin: 0.6rem 0 0 !important;
        padding: 0 !important;
        font-size: 0.9375rem !important;
        font-weight: 400 !important;
        line-height: 1.55 !important;
        color: #64748b !important;
        text-align: left !important;
    }

    .prism-swal-confirm .prism-swal-confirm__body strong,
    .prism-swal-confirm .swal2-html-container strong {
        color: #475569 !important;
        font-weight: 600 !important;
    }

    .prism-swal-confirm .prism-swal-confirm__actions {
        width: 100% !important;
        margin: 1.25rem 0 0 !important;
        padding-top: 1rem !important;
        border-top: 1px solid #f1f5f9 !important;
        gap: 0.55rem !important;
        justify-content: flex-end !important;
    }

    .prism-swal-confirm .prism-swal-confirm__btn {
        margin: 0 !important;
        min-width: 5.5rem !important;
        border-radius: 0.7rem !important;
        padding: 0.6rem 1.05rem !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        box-shadow: none !important;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .prism-swal-confirm .prism-swal-confirm__btn:active {
        transform: scale(0.98);
    }

    .prism-swal-confirm .prism-swal-confirm__btn--primary {
        background: #0025cc !important;
        color: #ffffff !important;
        border: 1px solid #0025cc !important;
    }

    .prism-swal-confirm .prism-swal-confirm__btn--primary:hover {
        background: #001fad !important;
        border-color: #001fad !important;
    }

    .prism-swal-confirm .prism-swal-confirm__btn--danger {
        background: #dc2626 !important;
        color: #ffffff !important;
        border: 1px solid #dc2626 !important;
    }

    .prism-swal-confirm .prism-swal-confirm__btn--danger:hover {
        background: #b91c1c !important;
        border-color: #b91c1c !important;
    }

    .prism-swal-confirm .prism-swal-confirm__btn--cancel {
        background: #f8fafc !important;
        color: #475569 !important;
        border: 1px solid transparent !important;
    }

    .prism-swal-confirm .prism-swal-confirm__btn--cancel:hover {
        background: #f1f5f9 !important;
        color: #0f172a !important;
    }
</style>
@endsection
