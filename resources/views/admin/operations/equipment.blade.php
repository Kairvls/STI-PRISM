@extends('layouts.admin-layout')

@section('title', 'Equipment Monitor')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $filters = [
        'all' => 'All',
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

    $isAlertCondition = fn ($status) => in_array((string) $status, ['Damaged', 'Critical', 'Under Maintenance'], true);
    $isAlertInventory = fn ($status) => in_array((string) $status, ['For Replacement', 'Under Maintenance', 'Disposed'], true);

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
                        href="{{ route('admin.operations.equipment.show', $alert->equipment_id) }}"
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
                            href="{{ route('admin.operations.equipment', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($filters as $key => $label)
                    <a
                        href="{{ route('admin.operations.equipment', ['filter' => $key, 'q' => $q]) }}"
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
                        <th>Room</th>
                        <th>Condition</th>
                        <th>Inventory</th>
                        <th>Warranty</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="transition hover:bg-gray-50/70">
                            <td>
                                <a
                                    href="{{ route('admin.operations.equipment.show', $row->equipment_id) }}"
                                    class="font-semibold text-gray-900 transition hover:text-[#0025cc]"
                                >{{ $row->equipment_name }}</a>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ $row->equipment_category_name ?: 'Uncategorized' }} · Qty {{ $row->equipment_quantity }}
                                </p>
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->room_name ?: '—' }}</td>
                            <td class="text-sm {{ $isAlertCondition($row->equipment_condition_status) ? 'font-semibold text-amber-700' : 'text-gray-600' }}">
                                {{ $row->equipment_condition_status ?: '—' }}
                            </td>
                            <td class="text-sm {{ $isAlertInventory($row->equipment_inventory_status) ? 'font-semibold text-amber-700' : 'text-gray-600' }}">
                                {{ $row->equipment_inventory_status ?: '—' }}
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-500">{{ $formatDate($row->equipment_warranty_expiration) }}</td>
                            <td class="text-right">
                                <x-view-action-button
                                    :href="route('admin.operations.equipment.show', $row->equipment_id)"
                                    label="View"
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
@endsection
