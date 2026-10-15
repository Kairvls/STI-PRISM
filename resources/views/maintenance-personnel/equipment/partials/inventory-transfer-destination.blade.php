{{--
    "Transfer to" fields shared by the Inventory and Deployed Equipment transfer modals.
    $prefix: element id prefix (e.g. inventoryTransfer) so each modal keeps its own inputs.
    $roomOptions: rooms offered as the destination (defaults to $deployRooms); $roomPlaceholder: its empty option.
--}}
@php
    $fieldClass = 'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100';
    $deployRoomIds = collect($deployRooms ?? [])->pluck('room_id')->map(fn ($id) => (int) $id)->all();
    $roomOptions = $roomOptions ?? ($deployRooms ?? collect());
@endphp

<div data-transfer-destination="{{ $prefix }}" class="space-y-5">
    @if ($transferNeedsApproval ?? false)
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs leading-5 text-amber-900">
            <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>
                <span class="font-semibold">Needs the Administrator's approval.</span>
                This sends a transfer request. The equipment stays where it is until the Administrator approves it.
            </p>
        </div>
    @endif

    <div>
        <span class="mb-2 block text-sm font-medium text-slate-700">Transfer type</span>
        <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1">
            @foreach (['room' => ['door-open', 'Room only'], 'person' => ['user-round', 'Issue to person'], 'department' => ['network', 'Charge to department']] as $type => [$icon, $label])
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="destination_type"
                        value="{{ $type }}"
                        class="peer sr-only"
                        @checked($type === 'room')
                        onchange="inventoryTransferDestinationChanged('{{ $prefix }}')"
                    />
                    <span class="flex h-full items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-center text-xs font-semibold leading-4 text-slate-500 transition peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm">
                        <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 shrink-0"></i>
                        {{ $label }}
                    </span>
                </label>
            @endforeach
        </div>
        <p id="{{ $prefix }}TypeHint" class="mt-1.5 text-xs text-slate-400">Moves the item to another room. Who owns it does not change.</p>
    </div>

    <div id="{{ $prefix }}PersonField" class="hidden">
        <label for="{{ $prefix }}CustodianId" class="mb-2 block text-sm font-medium text-slate-700">Person <span class="text-red-500">*</span></label>
        <select
            id="{{ $prefix }}CustodianId"
            name="custodian_id"
            class="{{ $fieldClass }}"
            onchange="inventoryTransferPersonChanged('{{ $prefix }}')"
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
        <p class="mt-1.5 text-xs text-slate-400">
            The item is issued to this person (property assignment) and tagged to their department.
            Only individually tracked items (quantity 1) can go to a person.
        </p>
    </div>

    <div id="{{ $prefix }}DepartmentField" class="hidden">
        <label for="{{ $prefix }}DepartmentId" class="mb-2 block text-sm font-medium text-slate-700">Department <span class="text-red-500">*</span></label>
        <select id="{{ $prefix }}DepartmentId" name="department_id" class="{{ $fieldClass }}" disabled>
            <option value="">Select a department</option>
            @foreach (($transferDepartments ?? collect()) as $department)
                <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-400">A department is an office, not a place, so the item still goes to the room below.</p>
    </div>

    <div>
        <label for="{{ $prefix }}RoomId" class="mb-2 block text-sm font-medium text-slate-700">
            <span id="{{ $prefix }}RoomLabel">Destination room</span> <span class="text-red-500">*</span>
        </label>
        <select id="{{ $prefix }}RoomId" name="room_id" required class="{{ $fieldClass }}">
            <option value="">{{ $roomPlaceholder ?? 'Select classroom / lab' }}</option>
            @foreach ($roomOptions as $room)
                <option value="{{ $room->room_id }}">{{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}</option>
            @endforeach
        </select>
    </div>
</div>

@once
<script>
    function inventoryTransferDestinationChanged(prefix) {
        const wrapper = document.querySelector(`[data-transfer-destination="${prefix}"]`);
        if (!wrapper) return;

        const type = wrapper.querySelector('input[name="destination_type"]:checked')?.value || 'room';
        const fields = {
            person: [document.getElementById(prefix + 'PersonField'), document.getElementById(prefix + 'CustodianId')],
            department: [document.getElementById(prefix + 'DepartmentField'), document.getElementById(prefix + 'DepartmentId')],
        };

        Object.entries(fields).forEach(([key, [field, select]]) => {
            const active = key === type;
            field?.classList.toggle('hidden', !active);
            if (select) {
                select.disabled = !active;
                select.required = active;
            }
        });

        const roomLabel = document.getElementById(prefix + 'RoomLabel');
        if (roomLabel) {
            roomLabel.textContent = type === 'room' ? 'Destination room' : 'Place it in (room)';
        }

        const hint = document.getElementById(prefix + 'TypeHint');
        if (hint) {
            hint.textContent = {
                room: 'Moves the item to another room. Who owns it does not change.',
                person: 'Moves the item and makes this person accountable for it.',
                department: 'Moves the item and charges it to an office for accountability.',
            }[type];
        }
    }

    function inventoryTransferPersonChanged(prefix) {
        const option = document.getElementById(prefix + 'CustodianId')?.selectedOptions[0];
        const roomSelect = document.getElementById(prefix + 'RoomId');
        const homeRoomId = option?.dataset.roomId || '';

        if (roomSelect && homeRoomId) {
            roomSelect.value = homeRoomId;
        }
    }

    function resetInventoryTransferDestination(prefix) {
        const wrapper = document.querySelector(`[data-transfer-destination="${prefix}"]`);
        if (!wrapper) return;

        const roomRadio = wrapper.querySelector('input[name="destination_type"][value="room"]');
        if (roomRadio) roomRadio.checked = true;
        ['RoomId', 'CustodianId', 'DepartmentId'].forEach((suffix) => {
            const select = document.getElementById(prefix + suffix);
            if (select) select.value = '';
        });
        inventoryTransferDestinationChanged(prefix);
    }
</script>
@endonce
