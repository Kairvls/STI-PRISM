@extends('layouts.maintenance-layout')

@section('title', 'Deployed Stocks')

@section('content')
@php
    $formatDate = function ($value) {
        if (! filled($value)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $hasFilters = filled($filters['search'] ?? null)
        || filled($filters['room'] ?? null);

    $groupCount = is_countable($groups) ? count($groups) : 0;
@endphp

<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

<div class="space-y-6" x-data="{ openKey: null }">
    {{-- Summary cards (purchaser pattern) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
        <div class="pur-stat-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Rooms with stock</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($roomsWithStock) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="door-open" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 text-xs font-medium text-gray-500">Rooms currently holding deployed units</div>
        </div>

        <div class="pur-stat-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total deployed qty</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($totalDeployedQty) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-[#0025cc]">
                    <i data-lucide="package" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 text-xs font-medium text-gray-500">Combined quantity across stock groups</div>
        </div>

        <div class="pur-stat-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Stock types</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($stockTypes) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                    <i data-lucide="layers" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 text-xs font-medium text-gray-500">Distinct equipment stocks on rooms</div>
        </div>

        <div class="pur-stat-card">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">On floor</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($onFloorQty) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="map-pin" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 text-xs font-medium text-gray-500">Excluding holding / storage staging</div>
        </div>
    </div>

    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Deployed stock groups</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ number_format($groupCount) }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Equipment assigned to classrooms and labs, grouped by stock type.</p>
                </div>

                <form
                    method="GET"
                    action="{{ url('/maintenance/equipment/deployed') }}"
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                >
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="search"
                            name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Search stocks..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-56"
                        >
                    </div>

                    <select name="room" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All rooms</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->room_id }}" @selected((string) $filters['room'] === (string) $room->room_id)>
                                {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                            </option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>

                    @if ($hasFilters)
                        <a
                            href="{{ url('/maintenance/equipment/deployed') }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Room / stock</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Brand / model</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Category</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Units</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Warranty</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Since</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($groups as $group)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="monitor" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-950">{{ $group['name'] }}</p>
                                        <p class="mt-0.5 truncate text-xs text-gray-500">{{ $group['room_name'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                <p>{{ $group['brand'] ?: '—' }}</p>
                                <p class="text-xs text-gray-400">{{ $group['model'] ?: '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $group['category'] ?: '—' }}</td>
                            <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">{{ number_format($group['total_quantity']) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums text-gray-600">{{ number_format($group['unit_count']) }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $formatDate($group['latest_warranty']) }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $formatDate($group['earliest_acquired']) }}</td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                    @click="openKey = openKey === @js($group['key']) ? null : @js($group['key']); $nextTick(() => window.lucide && window.lucide.createIcons())"
                                >
                                    <i data-lucide="list" class="h-3.5 w-3.5"></i>
                                    <span x-text="openKey === @js($group['key']) ? 'Hide' : 'Details'"></span>
                                </button>
                            </td>
                        </tr>
                        <tr x-show="openKey === @js($group['key'])" x-cloak class="bg-gray-50/50">
                            <td colspan="8" class="px-5 py-4">
                                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-950">
                                                {{ $group['name'] }}
                                                <span class="font-normal text-gray-400">× {{ $group['total_quantity'] }}</span>
                                            </p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $group['room_name'] }}
                                                · Active {{ $group['active_quantity'] }}
                                                · Maintenance {{ $group['maintenance_quantity'] }}
                                            </p>
                                        </div>
                                        <a
                                            href="{{ $group['inventory_url'] }}"
                                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0025cc] hover:underline"
                                        >
                                            Open in All Equipment
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50/70">
                                                <tr class="border-b border-gray-100">
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Asset</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Serial</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Qty</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Condition</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Status</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Zone</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Warranty</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-medium uppercase tracking-wide text-gray-500">Acquired</th>
                                                    <th class="px-4 py-2.5 text-right text-[10px] font-medium uppercase tracking-wide text-gray-500">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($group['units'] as $unit)
                                                    <tr class="hover:bg-gray-50/70">
                                                        <td class="px-4 py-2.5">
                                                            <p class="font-medium text-gray-900">{{ $unit['asset_tag'] ?: $group['name'] }}</p>
                                                            <p class="text-[11px] text-gray-400">{{ $unit['tracking_mode'] }}</p>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $unit['serial_number'] ?: '—' }}</td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $unit['quantity'] }}</td>
                                                        <td class="px-4 py-2.5">
                                                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                                                {{ $unit['condition'] ?: '—' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2.5">
                                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                                                                {{ $unit['inventory_status'] ?: '—' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $unit['placement_zone'] ?: '—' }}</td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $formatDate($unit['warranty_expiration']) }}</td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $formatDate($unit['acquired_date'] ?: $unit['created_at']) }}</td>
                                                        <td class="px-4 py-2.5 text-right">
                                                            <a
                                                                href="{{ $unit['view_url'] }}"
                                                                class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-950"
                                                            >
                                                                View
                                                                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-900">No deployed stocks match your filters</p>
                                <p class="mt-1 text-sm text-gray-500">Try another room, category, or clear the search.</p>
                                @if ($hasFilters)
                                    <a
                                        href="{{ url('/maintenance/equipment/deployed') }}"
                                        class="mt-4 inline-flex text-sm font-semibold text-[#0025cc] hover:underline"
                                    >
                                        Clear filters
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
