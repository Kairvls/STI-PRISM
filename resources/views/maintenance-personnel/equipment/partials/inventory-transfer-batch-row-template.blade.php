{{--
    Cloned once per selected item in Transfer selected modals.
    Placeholders: __INDEX__, __PREFIX__
    $templateId, $prefixBase (e.g. invBatch), optional $roomOptions / $roomPlaceholder
--}}
@php
    $fieldClass = 'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100';
    $deployRoomIds = collect($deployRooms ?? [])->pluck('room_id')->map(fn ($id) => (int) $id)->all();
    $roomOptions = $roomOptions ?? ($deployRooms ?? collect());
    $prefixBase = $prefixBase ?? 'batchRow';
@endphp

<template id="{{ $templateId }}" data-prefix-base="{{ $prefixBase }}">
    <div
        class="space-y-4 rounded-xl border border-slate-200 bg-slate-50/80 p-4"
        data-transfer-destination="__PREFIX__"
        data-batch-row
    >
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900" data-item-name>Equipment</p>
            <p class="mt-0.5 text-xs text-slate-500">Set this item's destination</p>
        </div>
        <input type="hidden" name="transfers[__INDEX__][equipment_id]" value="" data-equipment-id>

        <div>
            <span class="mb-2 block text-sm font-medium text-slate-700">Transfer type</span>
            <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1">
                @foreach (['room' => ['door-open', 'Room only'], 'person' => ['user-round', 'Person'], 'department' => ['network', 'Department']] as $type => [$icon, $label])
                    <label class="cursor-pointer">
                        <input
                            type="radio"
                            name="transfers[__INDEX__][destination_type]"
                            value="{{ $type }}"
                            class="peer sr-only"
                            @checked($type === 'room')
                            onchange="inventoryTransferDestinationChanged('__PREFIX__')"
                        />
                        <span class="flex h-full items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-center text-xs font-semibold leading-4 text-slate-500 transition peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm">
                            <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 shrink-0"></i>
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div id="__PREFIX__PersonField" class="hidden">
            <label for="__PREFIX__CustodianId" class="mb-2 block text-sm font-medium text-slate-700">Person <span class="text-red-500">*</span></label>
            <select
                id="__PREFIX__CustodianId"
                name="transfers[__INDEX__][custodian_id]"
                class="{{ $fieldClass }}"
                onchange="inventoryTransferPersonChanged('__PREFIX__')"
                disabled
            >
                <option value="">Select a person</option>
                @foreach (($transferPeople ?? collect()) as $person)
                    <option
                        value="{{ $person->custodian_id }}"
                        data-room-id="{{ in_array((int) ($person->custodian_room_id ?? 0), $deployRoomIds, true) ? (int) $person->custodian_room_id : '' }}"
                        data-department-id="{{ $person->custodian_department_id ?? '' }}"
                    >{{ \App\Support\Custodians::optionLabel($person) }}{{ ! empty($person->custodian_department) ? ' · '.$person->custodian_department : '' }}</option>
                @endforeach
            </select>
        </div>

        <div id="__PREFIX__DepartmentField" class="hidden">
            <label for="__PREFIX__DepartmentId" class="mb-2 block text-sm font-medium text-slate-700">Department <span class="text-red-500">*</span></label>
            <select id="__PREFIX__DepartmentId" name="transfers[__INDEX__][department_id]" class="{{ $fieldClass }}" disabled>
                <option value="">Select a department</option>
                @foreach (($transferDepartments ?? collect()) as $department)
                    <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="__PREFIX__RoomId" class="mb-2 block text-sm font-medium text-slate-700">
                <span id="__PREFIX__RoomLabel">Destination room</span> <span class="text-red-500">*</span>
            </label>
            <select id="__PREFIX__RoomId" name="transfers[__INDEX__][room_id]" required class="{{ $fieldClass }}">
                <option value="">{{ $roomPlaceholder ?? 'Select classroom / lab' }}</option>
                @foreach ($roomOptions as $room)
                    <option value="{{ $room->room_id }}">{{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</template>

@include('maintenance-personnel.equipment.partials.inventory-transfer-destination-script')
