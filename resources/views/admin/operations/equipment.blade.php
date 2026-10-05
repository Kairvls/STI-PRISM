@extends('layouts.admin-layout')

@section('title', 'Equipment Monitor')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $filters = [
        'all' => 'All',
        'stock' => 'In stock',
        'deployed' => 'Deployed',
        'disposed' => 'Disposed',
        'maintenance' => 'Needs maintenance',
        'replacement' => 'For replacement',
        'damaged' => 'Damaged',
        'lifecycle' => 'Lifecycle',
    ];

    $formatDate = function ($value) {
        if (! filled($value)) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $eqImageUrl = function ($path) {
        if (! filled($path)) {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/storage/')) {
            return $path;
        }

        return asset('storage/'.$path);
    };

    $conditionPill = fn ($status) => match ((string) $status) {
        'Good' => 'bg-emerald-50 text-emerald-700',
        'Fair' => 'bg-sky-50 text-sky-700',
        'Damaged', 'Under Maintenance' => 'bg-amber-50 text-amber-700',
        'Critical', 'Disposed' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };
    $statusPill = fn ($status) => match ((string) $status) {
        'Active' => 'bg-emerald-50 text-emerald-700',
        'Borrowed' => 'bg-sky-50 text-sky-700',
        'Under Maintenance' => 'bg-amber-50 text-amber-700',
        'For Replacement' => 'bg-orange-50 text-orange-700',
        'Disposed' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };

    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();
@endphp

<div class="admin-page space-y-6">
    @if($lifecycleAlerts->isNotEmpty())
        <div class="pur-card">
            <div class="border-b border-gray-100 px-5 py-5">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-950">Lifecycle horizon</h2>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">{{ $lifecycleAlerts->count() }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">Within 1 year of useful life (default {{ $usefulLifeYears }} years), or already due.</p>
            </div>
            <div class="grid divide-y divide-gray-100 md:grid-cols-3 md:divide-x md:divide-y-0">
                @foreach($lifecycleAlerts as $alert)
                    @php
                        $remaining = (int) $alert->years_remaining;
                        $badge = $remaining < 0
                            ? 'Overdue for replacement'
                            : ($remaining === 0 ? 'Replace this year' : '~'.$remaining.'y left');
                    @endphp
                    <a
                        href="{{ \App\Support\AdminPortal::route('operations.equipment.show', $alert->equipment_id) }}"
                        class="block px-5 py-4 transition hover:bg-gray-50/70"
                    >
                        <p class="text-sm font-semibold text-gray-900">{{ $alert->equipment_name }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $alert->room_name ?: 'No room' }} · Age {{ (int) $alert->age_years }}y</p>
                        <p class="mt-1.5 text-xs font-semibold text-amber-700">{{ $badge }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Equipment records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Inventory status, replacement needs, and lifecycle alerts.</p>
                </div>

                <form method="GET" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Search equipment…"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if($q !== '' && $q !== null)
                        <a
                            href="{{ \App\Support\AdminPortal::route('operations.equipment', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    <a
                        href="{{ \App\Support\AdminPortal::route('operations.equipment', ['filter' => $key, 'q' => $q]) }}"
                        class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[900px]">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Next maintenance</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $assetPayload = array_merge(
                                \App\Support\LayoutEquipmentPayload::fromRow($row, $eqImageUrl($row->equipment_image ?? null)),
                                ['view_url' => \App\Support\AdminPortal::route('operations.equipment.show', $row->equipment_id)]
                            );
                            $placementZone = trim((string) ($row->equipment_placement_zone ?: $row->equipment_current_location ?: ''));
                            $isStorageStock = \App\Support\RoomCategories::isStorageType($row->room_type ?? null);
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a
                                        href="{{ \App\Support\AdminPortal::route('operations.equipment.show', $row->equipment_id) }}"
                                        class="font-semibold text-gray-900 transition hover:text-[#0025cc]"
                                    >{{ $row->equipment_name }}</a>
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $isStorageStock ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-sky-50 text-sky-700 ring-sky-200' }}">
                                        {{ $isStorageStock ? 'Stock' : 'Deployed' }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-400">{{ $row->equipment_asset_tag ?: 'No Asset Tag' }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ $row->equipment_category_name ?: 'Uncategorized' }} · Qty {{ $row->equipment_quantity }}
                                </p>
                            </td>
                            <td>
                                <p class="text-sm {{ filled($row->room_name) ? 'text-gray-600' : 'text-gray-400' }}">{{ $row->room_name ?: 'Unassigned' }}</p>
                                @if ($placementZone !== '')
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $placementZone }}</p>
                                @endif
                            </td>
                            <td>
                                @if (filled($row->equipment_condition_status))
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $conditionPill($row->equipment_condition_status) }}">
                                        {{ $row->equipment_condition_status }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if (filled($row->equipment_inventory_status))
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $statusPill($row->equipment_inventory_status) }}">
                                        {{ $row->equipment_inventory_status }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                @if (filled($row->next_maintenance_date ?? null))
                                    @php
                                        $maintenanceOverdue = \App\Support\EquipmentNextMaintenance::isOverdue($row);
                                    @endphp
                                    <a
                                        href="{{ \App\Support\AdminPortal::route('operations.schedules', ['q' => $row->equipment_name]) }}"
                                        class="text-sm transition hover:text-[#0025cc] {{ $maintenanceOverdue ? 'font-semibold text-rose-700' : 'text-gray-700' }}"
                                    >{{ $formatDate($row->next_maintenance_date) }}</a>
                                    @if ($maintenanceOverdue)
                                        <span class="ml-1.5 inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">Overdue</span>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-400">Not scheduled</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-view-action-button
                                    tag="button"
                                    label="View"
                                    :onclick="'openEquipmentModal('.json_encode($assetPayload).')'"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="pur-empty">No equipment found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>

@include('maintenance-personnel.equipment.partials.equipment-asset-drawer', [
    'assetLifecycleUrl' => \App\Support\AdminPortal::route('operations.equipment.lifecycle', '__ID__'),
])
@include('layouts.partials.equipment-layout-icons')
@include('layouts.partials.equipment-photo-viewer')
@endsection
