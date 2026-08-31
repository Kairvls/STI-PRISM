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

    $eqField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
@endphp

<div
    class="space-y-6"
    x-data="{ openKey: null }"
>
    <div class="overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm">
        <div class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-4">
            <div class="px-8 py-6">
                <p class="text-sm font-medium text-slate-500">Rooms with stock</p>
                <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($roomsWithStock) }}</h2>
            </div>
            <div class="px-8 py-6">
                <p class="text-sm font-medium text-slate-500">Total deployed qty</p>
                <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($totalDeployedQty) }}</h2>
            </div>
            <div class="px-8 py-6">
                <p class="text-sm font-medium text-slate-500">Stock types</p>
                <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($stockTypes) }}</h2>
            </div>
            <div class="px-8 py-6">
                <p class="text-sm font-medium text-slate-500">On floor (excl. Holding)</p>
                <h2 class="mt-2 text-4xl font-medium text-slate-900">{{ number_format($onFloorQty) }}</h2>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ url('/maintenance/equipment/deployed') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="xl:col-span-2">
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    placeholder="Search name, brand, model, room, tag…"
                    class="{{ $eqField }}"
                />
            </div>
            <div>
                <select name="room" class="{{ $eqField }}">
                    <option value="">All Rooms</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->room_id }}" @selected((string) $filters['room'] === (string) $room->room_id)>
                            {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="category" class="{{ $eqField }}">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->equipment_category_id }}" @selected((string) $filters['category'] === (string) $category->equipment_category_id)>
                            {{ $category->equipment_category_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-2">
                <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                    <input type="checkbox" name="on_floor" value="1" class="rounded border-slate-300" @checked($filters['on_floor'])>
                    On floor only
                </label>
                <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                    <input type="checkbox" name="include_storage" value="1" class="rounded border-slate-300" @checked($filters['include_storage'] ?? false)>
                    Include storage rooms
                </label>
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-blue-800">
                    Apply
                </button>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Room / Stock</th>
                        <th class="px-4 py-3">Brand / Model</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Units</th>
                        <th class="px-4 py-3">Warranty</th>
                        <th class="px-4 py-3">Since</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($groups as $group)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $group['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $group['room_name'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <p>{{ $group['brand'] ?: '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $group['model'] ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $group['category'] ?: '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($group['total_quantity']) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($group['unit_count']) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $formatDate($group['latest_warranty']) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $formatDate($group['earliest_acquired']) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    @click="openKey = openKey === @js($group['key']) ? null : @js($group['key'])"
                                >
                                    <i data-lucide="list" class="h-3.5 w-3.5"></i>
                                    Details
                                </button>
                            </td>
                        </tr>
                        <tr x-show="openKey === @js($group['key'])" x-cloak class="bg-slate-50/70">
                            <td colspan="8" class="px-4 py-4">
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ $group['name'] }} × {{ $group['total_quantity'] }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ $group['room_name'] }}
                                                · Active {{ $group['active_quantity'] }}
                                                · Maintenance {{ $group['maintenance_quantity'] }}
                                            </p>
                                        </div>
                                        <a
                                            href="{{ $group['inventory_url'] }}"
                                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#0025cc] hover:underline"
                                        >
                                            Open in Inventory
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead class="bg-slate-50 text-left text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                                <tr>
                                                    <th class="px-4 py-2">Asset</th>
                                                    <th class="px-4 py-2">Serial</th>
                                                    <th class="px-4 py-2">Qty</th>
                                                    <th class="px-4 py-2">Condition</th>
                                                    <th class="px-4 py-2">Status</th>
                                                    <th class="px-4 py-2">Zone</th>
                                                    <th class="px-4 py-2">Warranty</th>
                                                    <th class="px-4 py-2">Acquired</th>
                                                    <th class="px-4 py-2"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($group['units'] as $unit)
                                                    <tr>
                                                        <td class="px-4 py-2.5">
                                                            <p class="font-medium text-slate-800">{{ $unit['asset_tag'] ?: $group['name'] }}</p>
                                                            <p class="text-[11px] text-slate-400">{{ $unit['tracking_mode'] }}</p>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $unit['serial_number'] ?: '—' }}</td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $unit['quantity'] }}</td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $unit['condition'] ?: '—' }}</td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $unit['inventory_status'] ?: '—' }}</td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $unit['placement_zone'] ?: '—' }}</td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $formatDate($unit['warranty_expiration']) }}</td>
                                                        <td class="px-4 py-2.5 text-slate-600">{{ $formatDate($unit['acquired_date'] ?: $unit['created_at']) }}</td>
                                                        <td class="px-4 py-2.5 text-right">
                                                            <a
                                                                href="{{ $unit['view_url'] }}"
                                                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-slate-950"
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
                            <td colspan="8" class="px-4 py-16 text-center text-slate-500">
                                No deployed stocks match your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
