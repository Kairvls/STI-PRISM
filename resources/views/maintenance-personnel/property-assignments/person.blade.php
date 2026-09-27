@extends('layouts.maintenance-layout')

@section('title', $person->custodian_full_name)

@section('content')
@php
    $formatDate = fn ($value) => filled($value) ? \Carbon\Carbon::parse($value)->format('M d, Y') : '—';
    $holdsProperty = $activeItems->isNotEmpty();
    $statusTone = [
        'Active' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'On Leave' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'Inactive' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $details = [
        ['Employee ID', $person->custodian_employee_id, 'id-card'],
        ['Position', $person->custodian_position, 'briefcase'],
        ['Department / office', $person->custodian_department, 'building-2'],
        ['Home room', $person->home_room_name, 'door-open'],
        ['Email', $person->custodian_email_address, 'mail'],
        ['Contact no.', $person->custodian_contact_number, 'phone'],
        ['System account', $person->linked_reporter_name ? $person->linked_reporter_name.' ('.$person->linked_employee_id.')' : 'Not linked', 'link'],
        ['In directory since', $formatDate($person->custodian_created_at), 'calendar'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-2 text-sm text-slate-400">
        <a href="{{ url('/maintenance/property-assignments') }}" class="transition hover:text-slate-700">Property Assignment</a>
        <i data-lucide="chevron-right" class="h-4 w-4"></i>
        <span class="font-medium text-slate-600">{{ $person->custodian_full_name }}</span>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-950">{{ $person->custodian_full_name }}</h2>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusTone[$person->custodian_status] ?? $statusTone['Inactive'] }}">{{ $person->custodian_status }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ implode(' · ', array_filter([$person->custodian_position, $person->custodian_department])) ?: 'No position recorded' }}
                </p>
                <p class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $holdsProperty ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-emerald-50 text-emerald-700 ring-emerald-100' }}">
                    <i data-lucide="{{ $holdsProperty ? 'package-open' : 'badge-check' }}" class="h-3.5 w-3.5"></i>
                    @if ($holdsProperty)
                        Not cleared · {{ $activeItems->count() }} {{ \Illuminate\Support\Str::plural('item', $activeItems->count()) }} to return before clearance
                    @else
                        Cleared · no property held
                    @endif
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                <a
                    href="{{ route('maintenance.property-assignments.people.edit', $person->custodian_id) }}"
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                    Edit details
                </a>
                <a
                    href="{{ route('maintenance.property-assignments.person.form', $person->custodian_id) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print accountability form
                </a>
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($details as [$label, $value, $icon])
                <div class="min-w-0">
                    <dt class="flex items-center gap-1.5 text-xs font-medium text-slate-400">
                        <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i>
                        {{ $label }}
                    </dt>
                    <dd class="mt-0.5 truncate font-medium {{ filled($value) ? 'text-slate-800' : 'text-slate-400' }}" title="{{ $value }}">{{ filled($value) ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>

        @if (filled($person->custodian_notes))
            <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/80">{{ $person->custodian_notes }}</p>
        @endif
    </section>

    @if (! $canReceive)
        <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-100">
            This person is marked <strong>{{ $person->custodian_status }}</strong>, so new items cannot be assigned to them. Change the status under Edit details to assign again.
        </p>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Currently accountable for</h2>
                <p class="mt-0.5 text-sm text-slate-500">Open an item to transfer or return it.</p>
            </div>
            <i data-lucide="package" class="h-5 w-5 text-slate-400"></i>
        </div>

        @if ($holdsProperty)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Item</th>
                            <th class="px-5 py-3">Asset tag / serial</th>
                            <th class="px-5 py-3">Room / desk</th>
                            <th class="px-5 py-3">Condition</th>
                            <th class="px-5 py-3">Document no.</th>
                            <th class="px-5 py-3">Since</th>
                            <th class="px-5 py-3">Last verified</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($activeItems as $item)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <a href="{{ \App\Support\EquipmentViewReturn::viewUrl((int) $item->equipment_id) }}#property-assignment" class="font-semibold text-[#0025cc] hover:underline">
                                        {{ $item->equipment_name }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ implode(' · ', array_filter([$item->equipment_category_name, $item->equipment_brand_name, $item->equipment_model])) ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">
                                    {{ $item->equipment_asset_tag ?: '—' }}<br>
                                    <span class="text-slate-400">{{ $item->equipment_serial_number ?: '—' }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    @if ($item->current_room_id)
                                        <a href="{{ url('/maintenance/property-assignments/rooms/'.$item->current_room_id) }}" class="hover:underline">{{ $item->current_room_name }}</a>
                                    @else
                                        —
                                    @endif
                                    @if ($item->workstation_slot_label)
                                        <span class="text-slate-400">· {{ $item->workstation_slot_label }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->equipment_condition_status ?: '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $item->assignment_document_no ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $formatDate($item->assignment_issued_at) }}</td>
                                <td class="px-5 py-3 {{ filled($item->assignment_verified_at ?? null) ? 'text-slate-700' : 'text-slate-400' }}" title="Confirmed with this person during a semester inspection">
                                    {{ filled($item->assignment_verified_at ?? null) ? $formatDate($item->assignment_verified_at) : 'Not yet' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="px-5 py-10 text-center text-sm text-slate-500">This person has no property assigned right now.</p>
        @endif
    </section>

    @if ($canReceive)
        @php
            $addOpen = ! $holdsProperty || $itemSearch !== '' || $itemRoomId || old('equipment_ids');
        @endphp
        <details id="add-items" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white" @if ($addOpen) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Add items</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Tick the items {{ $person->custodian_full_name }} uses and assign them in one go.</p>
                </div>
                <i data-lucide="chevron-down" class="h-5 w-5 text-slate-400 transition group-open:rotate-180"></i>
            </summary>

            <form method="GET" action="{{ url()->current() }}#add-items" class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center">
                <div class="relative min-w-0 flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        name="item_search"
                        value="{{ $itemSearch }}"
                        placeholder="Search item, asset tag, serial, or room"
                        class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>
                <select name="item_room" class="h-10 rounded-xl border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200/80" onchange="this.form.submit()">
                    <option value="">Offices and their current rooms</option>
                    @foreach ($assignableRooms as $roomOption)
                        <option value="{{ $roomOption->room_id }}" @selected((int) $itemRoomId === (int) $roomOption->room_id)>
                            {{ $roomOption->room_name }} ({{ $roomOption->item_count }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>
            </form>

            @if ($assignableItems->isNotEmpty())
                <div class="border-t border-slate-100">
                    @include('maintenance-personnel.property-assignments.partials.batch-assign-form', [
                        'items' => $assignableItems,
                        'people' => collect(),
                        'fixedPerson' => $person,
                        'showRoom' => true,
                    ])
                </div>
            @else
                <p class="border-t border-slate-100 px-5 py-10 text-center text-sm text-slate-500">
                    @if ($itemSearch !== '' || $itemRoomId)
                        No unassigned items match. Try another search or room.
                    @else
                        No unassigned items in offices yet. Pick a room above or search for an item.
                    @endif
                </p>
            @endif
        </details>
    @endif

    @if ($pastItems->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Previously held</h2>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($pastItems as $item)
                    <li class="flex flex-col gap-1 px-5 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ \App\Support\EquipmentViewReturn::viewUrl((int) $item->equipment_id) }}" class="font-semibold text-slate-800 hover:underline">
                            {{ $item->equipment_name }}
                            <span class="font-mono text-xs font-normal text-slate-400">{{ $item->equipment_asset_tag }}</span>
                        </a>
                        <p class="text-xs text-slate-500">
                            {{ $formatDate($item->assignment_issued_at) }} – {{ $formatDate($item->assignment_returned_at) }}
                            · {{ $item->assignment_status }}
                            @if ($item->assignment_return_condition) · {{ $item->assignment_return_condition }} @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
