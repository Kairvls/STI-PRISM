@extends('layouts.maintenance-layout')

@section('title', $room->room_name)

@section('content')
@php
    $formatDate = fn ($value) => filled($value) ? \Carbon\Carbon::parse($value)->format('M d, Y') : '—';
    $location = implode(' · ', array_filter([
        $room->room_type,
        $room->building_name,
        $room->floor_level ? 'Floor '.$room->floor_level : null,
    ]));
    $readOnly = \App\Support\PropertyAssignments::isReadOnly();
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-2 text-sm text-slate-400">
        <a href="{{ url('/maintenance/property-assignments?view=offices') }}" class="transition hover:text-slate-700">Property Assignment</a>
        <i data-lucide="chevron-right" class="h-4 w-4"></i>
        <span class="font-medium text-slate-600">{{ $room->room_name }}</span>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <h2 class="text-xl font-semibold text-slate-950">{{ $room->room_name }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $location ?: '—' }}</p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
            <span class="rounded-full bg-teal-50 px-3 py-1 text-teal-700 ring-1 ring-teal-100">
                {{ $custodians->count() }} {{ \Illuminate\Support\Str::plural('person', $custodians->count()) }} ·
                {{ $custodians->flatten(1)->count() }} assigned
            </span>
            @if ($unassignedItems->isNotEmpty())
                <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700 ring-1 ring-amber-100">
                    {{ $unassignedItems->count() }} unassigned
                </span>
            @endif
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @forelse ($custodians as $items)
            @php $first = $items->first(); @endphp
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <a href="{{ url('/maintenance/property-assignments/people/'.$first->assignment_custodian_id) }}" class="truncate text-sm font-semibold text-slate-900 hover:underline">
                            {{ $first->custodian_full_name ?? 'Unknown person' }}
                        </a>
                        <p class="text-xs text-slate-400">
                            {{ implode(' · ', array_filter([$first->custodian_position ?? null, $first->custodian_employee_id ?? null])) ?: '—' }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700 ring-1 ring-teal-100">
                        {{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}
                    </span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0">
                                <a href="{{ \App\Support\EquipmentViewReturn::viewUrl((int) $item->equipment_id) }}#property-assignment" class="font-semibold text-slate-800 hover:underline">
                                    {{ $item->equipment_name }}
                                </a>
                                <p class="truncate text-xs text-slate-500">
                                    {{ implode(' · ', array_filter([$item->equipment_asset_tag, $item->workstation_slot_label, $item->equipment_condition_status])) ?: '—' }}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-400">Since {{ $formatDate($item->assignment_issued_at) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-12 text-center xl:col-span-2">
                <p class="text-sm font-semibold text-slate-900">No one is accountable for items in this room yet</p>
                <p class="mt-1 text-sm text-slate-500">{{ $readOnly ? 'Maintenance has not assigned the items here yet.' : 'Assign the items below to the people who use them.' }}</p>
            </div>
        @endforelse
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Unassigned items</h2>
                <p class="mt-0.5 text-sm text-slate-500">Individually tracked items in this room with no accountable person.</p>
            </div>
            <i data-lucide="package-search" class="h-5 w-5 text-slate-400"></i>
        </div>
        @if ($unassignedItems->isNotEmpty() && $readOnly)
            <ul class="divide-y divide-slate-100">
                @foreach ($unassignedItems as $item)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-800">{{ $item->equipment_name }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ implode(' · ', array_filter([$item->equipment_category_name ?? null, $item->equipment_asset_tag ?? null, $item->equipment_condition_status ?? null])) ?: '—' }}
                            </p>
                        </div>
                        <a href="{{ \App\Support\EquipmentViewReturn::viewUrl((int) $item->equipment_id, request()->fullUrl()) }}" class="shrink-0 text-xs font-semibold text-slate-400 hover:text-slate-700">Details</a>
                    </li>
                @endforeach
            </ul>
        @elseif ($unassignedItems->isNotEmpty() && $isStorageRoom)
            <p class="px-5 py-10 text-center text-sm text-slate-500">
                This is a storage room. Deploy items to the person's office before assigning them.
            </p>
        @elseif ($unassignedItems->isNotEmpty())
            @include('maintenance-personnel.property-assignments.partials.batch-assign-form', [
                'items' => $unassignedItems,
                'people' => $assignablePeople,
                'fixedPerson' => null,
                'showRoom' => false,
                'roomId' => $room->room_id,
            ])
        @else
            <p class="px-5 py-10 text-center text-sm text-slate-500">Every individually tracked item here has an accountable person.</p>
        @endif
    </section>
</div>
@endsection
