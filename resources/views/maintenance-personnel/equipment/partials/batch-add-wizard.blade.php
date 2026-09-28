@php
    $batchRooms = \App\Support\EquipmentAcquisition::intakeRooms();
    $batchStorageRooms = $batchRooms['storage'];
    $batchDeployRooms = $batchRooms['deploy'];
    $batchAcquisitionReady = \App\Support\EquipmentAcquisition::ready();
    $batchSuppliers = $acquisitionSuppliers ?? \App\Support\EquipmentAcquisition::supplierOptions();
    $batchPeople = ($acquisitionPeople ?? collect())->isNotEmpty() ? $acquisitionPeople : \App\Support\Custodians::assignable();
    $batchOther = \App\Support\EquipmentAcquisition::OTHER_SUPPLIER;
    $bField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $bCell = 'h-9 w-full rounded-lg border-0 bg-white px-2.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200 placeholder:text-slate-300 focus:ring-2 focus:ring-slate-900/10';
    $bLabel = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
    $bError = 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200';
    $batchConfig = [
        'defaultStorageRoomId' => (string) (optional($batchStorageRooms->first())->room_id ?? ''),
        'storageRooms' => $batchStorageRooms->map(fn ($room) => ['id' => (string) $room->room_id, 'name' => $room->room_name])->values(),
        'deployRooms' => $batchDeployRooms->map(fn ($room) => ['id' => (string) $room->room_id, 'name' => $room->room_name])->values(),
        'people' => $batchPeople->map(fn ($person) => ['id' => (string) $person->custodian_id, 'name' => $person->custodian_full_name])->values(),
        'sources' => \App\Support\EquipmentAcquisition::SOURCES,
        'otherSupplier' => $batchOther,
        'acquisitionReady' => $batchAcquisitionReady,
    ];
@endphp

<div
    id="batchAddEquipmentModal"
    x-data="batchAddEquipment(@js($batchConfig))"
    x-show="open"
    x-cloak
    x-effect="if (open) document.body.style.overflow = 'hidden'"
    @keydown.escape.window="if (open && !saving) close()"
    class="fixed inset-0 z-50 hidden items-center justify-center overflow-hidden bg-[#0b1220]/70 p-4"
    :class="open ? '!flex' : 'hidden'"
>
    <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
        <div class="flex items-start justify-between px-6 pt-6">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">Batch add equipment</h2>
                <p class="mt-1 text-sm text-slate-500" x-text="stepHint()"></p>
            </div>
            <button type="button" @click="close()" :disabled="saving" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <ol class="mx-6 mt-4 flex flex-wrap items-center gap-x-1 gap-y-2 text-xs">
            <template x-for="(item, index) in steps" :key="item.key">
                <li class="flex items-center gap-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold transition"
                        :class="index === stepIndex()
                            ? 'bg-[#0025cc] text-white'
                            : (index < stepIndex() ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-white text-slate-400 ring-1 ring-slate-200')"
                        :disabled="index >= stepIndex() || saving"
                        @click="goTo(item.key)"
                    >
                        <span
                            class="inline-flex h-4 w-4 items-center justify-center rounded-full text-[10px]"
                            :class="index === stepIndex() ? 'bg-white/20' : (index < stepIndex() ? 'bg-emerald-500 text-white' : 'bg-slate-100')"
                            x-text="index < stepIndex() ? '✓' : (index + 1)"
                        ></span>
                        <span x-text="item.label"></span>
                    </button>
                    <span x-show="index < steps.length - 1" class="text-slate-300">›</span>
                </li>
            </template>
        </ol>

        <div x-show="formError" x-cloak class="mx-6 mt-4 flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
            <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p class="min-w-0 flex-1 leading-relaxed" x-text="formError"></p>
            <button type="button" @click="formError = ''" class="shrink-0 rounded-lg p-1 text-rose-400 transition hover:bg-rose-100 hover:text-rose-700" aria-label="Dismiss">
                <i data-lucide="x" class="h-3.5 w-3.5"></i>
            </button>
        </div>

        {{-- Step 1: shared source --}}
        <div class="eq-modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5" x-show="step === 'source'">
            <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Where it all came from</p>
                <p class="text-xs text-slate-500">Applies to every item in this batch. For delivered Receiving Report lines, use <span class="font-semibold">Stock all pending</span> instead.</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @if ($batchAcquisitionReady)
                        <div>
                            <label class="{{ $bLabel }}">How was it acquired? <span class="text-rose-500">*</span></label>
                            <select x-model="source.acquisition_source" @change="clearErr('source.acquisition_source')" class="{{ $bField }}" :class="err('source.acquisition_source') ? '{{ $bError }}' : ''">
                                <option value="">Select source</option>
                                @foreach (\App\Support\EquipmentAcquisition::SOURCES as $sourceKey => $sourceLabel)
                                    <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                                @endforeach
                            </select>
                            <p x-show="err('source.acquisition_source')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('source.acquisition_source')"></p>
                        </div>
                        <div>
                            <label class="{{ $bLabel }}" x-text="source.acquisition_source === 'donation' ? 'Donor' : 'Supplier / source'"></label>
                            <select x-model="source.acquisition_supplier" data-searchable="1" data-search-placeholder="Search suppliers…" class="{{ $bField }}">
                                <option value="">Not recorded</option>
                                <option value="{{ $batchOther }}">Other — type the name</option>
                                @foreach ($batchSuppliers as $supplierOption)
                                    <option value="{{ $supplierOption->supplier_id }}">{{ $supplierOption->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="source.acquisition_supplier === config.otherSupplier" x-cloak class="sm:col-span-2">
                            <label class="{{ $bLabel }}" x-text="source.acquisition_source === 'donation' ? 'Donor name' : 'Supplier / source name'"></label>
                            <input type="text" x-model="source.supplier_name" maxlength="255" placeholder="e.g. Alumni Association, PC Express" class="{{ $bField }}" />
                        </div>
                        <div>
                            <label class="{{ $bLabel }}">Reference no.</label>
                            <input type="text" x-model="source.reference_number" maxlength="120" placeholder="OR / invoice / DR / deed of donation no." class="{{ $bField }}" />
                        </div>
                    @endif
                    <div>
                        <label class="{{ $bLabel }}">Stock-in storage room <span class="text-rose-500">*</span></label>
                        <select x-model="storageRoomId" @change="clearErr('storage_room_id'); retagAll()" class="{{ $bField }}" :class="err('storage_room_id') ? '{{ $bError }}' : ''">
                            <option value="">Select storage room</option>
                            @foreach ($batchStorageRooms as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                        <p x-show="err('storage_room_id')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('storage_room_id')"></p>
                    </div>
                    <div>
                        <label class="{{ $bLabel }}" x-text="source.acquisition_source === 'donation' ? 'Donation date' : 'Purchase date'"></label>
                        <input type="date" x-model="source.purchase_date" class="{{ $bField }}" />
                    </div>
                    <div>
                        <label class="{{ $bLabel }}">Received on</label>
                        <input type="date" x-model="source.received_date" class="{{ $bField }}" />
                    </div>
                </div>
                @if ($batchAcquisitionReady)
                    <div>
                        <label class="{{ $bLabel }}">Notes</label>
                        <input type="text" x-model="source.notes" maxlength="500" placeholder="e.g. Donated by Batch 2019 alumni for the new laboratory" class="{{ $bField }}" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Step 2: item lines --}}
        <div class="eq-modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5" x-show="step === 'items'" x-cloak>
            <template x-for="(line, i) in lines" :key="line.key">
                <section class="rounded-2xl bg-slate-50/80 ring-1 ring-slate-200/80" :class="lineHasError(i) ? 'ring-2 ring-rose-300' : ''">
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-left" @click="line.open = !line.open">
                            <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition" :class="line.open ? '' : '-rotate-90'"></i>
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="'Item ' + (i + 1)"></span>
                            <span class="truncate text-sm font-semibold text-slate-900" x-text="line.name || 'Untitled'"></span>
                            <span class="shrink-0 text-xs text-slate-500" x-text="'· ' + (Number(line.quantity) || 0) + ' ' + line.tracking + ' · ' + roomName(line.destination_room_id)"></span>
                        </button>
                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" @click="duplicateLine(i)" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-white">Duplicate</button>
                            <button type="button" x-show="lines.length > 1" @click="removeLine(i)" class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Remove</button>
                        </div>
                    </div>
                    <div x-show="line.open" class="space-y-3 border-t border-slate-200/80 px-4 py-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="lg:col-span-2">
                                <label class="{{ $bLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="line.name" @input="clearErr('lines.' + i + '.name'); detectCategory(line)" @change="retagAll()" placeholder="e.g. Monitor" class="{{ $bField }}" :class="err('lines.' + i + '.name') ? '{{ $bError }}' : ''" />
                                <p x-show="err('lines.' + i + '.name')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('lines.' + i + '.name')"></p>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="{{ $bLabel }}">Category <span class="text-rose-500">*</span></label>
                                <select x-model="line.category_id" @change="line.categoryManual = true; clearErr('lines.' + i + '.category_id')" data-searchable="1" data-search-placeholder="Search categories…" class="{{ $bField }}" :class="err('lines.' + i + '.category_id') ? '{{ $bError }}' : ''">
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                    @endforeach
                                </select>
                                <p x-show="err('lines.' + i + '.category_id')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('lines.' + i + '.category_id')"></p>
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Qty <span class="text-rose-500">*</span></label>
                                <input type="number" min="1" max="200" x-model.number="line.quantity" @input="clearErr('lines.' + i + '.quantity')" class="{{ $bField }}" :class="err('lines.' + i + '.quantity') ? '{{ $bError }}' : ''" />
                                <p x-show="err('lines.' + i + '.quantity')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('lines.' + i + '.quantity')"></p>
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Tracking <span class="text-red-500">*</span></label>
                                <div class="flex h-11 rounded-xl bg-slate-100 p-1">
                                    <button type="button" @click="line.tracking = 'Bulk'; line.custodian_id = ''" class="flex-1 rounded-lg text-sm font-medium transition" :class="line.tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Bulk</button>
                                    <button type="button" @click="line.tracking = 'Individual'" class="flex-1 rounded-lg text-sm font-medium transition" :class="line.tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
                                </div>
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Condition</label>
                                <select x-model="line.condition" class="{{ $bField }}">
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Under Maintenance">Under maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                            <label class="flex h-11 items-center justify-between self-end rounded-xl bg-white px-3.5 ring-1 ring-slate-200/80">
                                <span class="text-sm font-medium text-slate-900">Borrowable</span>
                                <input type="checkbox" x-model="line.borrowable" class="peer sr-only">
                                <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-[#0025cc] after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                            </label>
                            <div>
                                <label class="{{ $bLabel }}">Brand</label>
                                <input type="text" x-model="line.brand" placeholder="e.g. Dell" class="{{ $bField }}" />
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Model</label>
                                <input type="text" x-model="line.model" placeholder="e.g. P2422H" class="{{ $bField }}" />
                            </div>
                            <div>
                                <label class="{{ $bLabel }}" x-text="source.acquisition_source === 'donation' ? 'Est. value / unit (₱)' : 'Cost / unit (₱)'"></label>
                                <input type="number" min="0" step="0.01" placeholder="0.00" x-model="line.cost" class="{{ $bField }}" />
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Warranty until</label>
                                <input type="date" x-model="line.warranty" class="{{ $bField }}" />
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Lifespan (years)</label>
                                <input type="number" min="1" max="50" step="1" placeholder="Default 5" x-model="line.useful_life_years" class="{{ $bField }}" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 rounded-xl bg-white p-3 ring-1 ring-slate-200/80 sm:grid-cols-2">
                            <div>
                                <label class="{{ $bLabel }}">Goes to</label>
                                <select x-model="line.destination_room_id" @change="clearErr('lines.' + i + '.destination_room_id'); onLineDestinationChange(line)" data-searchable="1" data-search-placeholder="Search rooms…" class="{{ $bField }}" :class="err('lines.' + i + '.destination_room_id') ? '{{ $bError }}' : ''">
                                    <option value="0">Keep in storage</option>
                                    @foreach ($batchDeployRooms as $room)
                                        <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-xs text-slate-400" x-text="line.tracking === 'Individual' && Number(line.quantity) > 1
                                    ? 'Default for every unit. You can send individual units to other rooms on the next step.'
                                    : 'Stocked in, then transferred here when a room is chosen.'"></p>
                            </div>
                            <div>
                                <label class="{{ $bLabel }}">Accountable person</label>
                                <select
                                    x-model="line.custodian_id"
                                    @change="clearErr('lines.' + i + '.custodian_id')"
                                    :disabled="line.tracking !== 'Individual'"
                                    data-searchable="1"
                                    data-search-placeholder="Search people…"
                                    class="{{ $bField }}"
                                    :class="err('lines.' + i + '.custodian_id') ? '{{ $bError }}' : ''"
                                >
                                    <option value="">No one yet</option>
                                    @foreach ($batchPeople as $person)
                                        <option value="{{ $person->custodian_id }}">{{ $person->custodian_full_name }}{{ $person->custodian_department ? ' · '.$person->custodian_department : '' }}</option>
                                    @endforeach
                                </select>
                                <p x-show="err('lines.' + i + '.custodian_id')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('lines.' + i + '.custodian_id')"></p>
                                <p x-show="!err('lines.' + i + '.custodian_id')" class="mt-1.5 text-xs text-slate-400" x-text="line.tracking === 'Individual'
                                    ? 'Issued for units deployed to a room. Units kept in storage are not assigned.'
                                    : 'Only Individual tracking can be assigned to a person.'"></p>
                            </div>
                        </div>
                    </div>
                </section>
            </template>
            <button type="button" @click="addLine()" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-[#0025cc] transition hover:bg-slate-50">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add another item
            </button>
        </div>

        {{-- Step 3: units & rooms --}}
        <div class="eq-modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5" x-show="step === 'units'" x-cloak>
            <template x-for="(line, i) in lines" :key="'u-' + line.key">
                <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900" x-text="line.name"></p>
                            <p class="text-xs text-slate-500" x-text="line.tracking === 'Bulk'
                                ? ('Bulk · ' + line.quantity + ' in one record → ' + roomName(line.destination_room_id))
                                : (line.units.length + ' individually tracked unit' + (line.units.length === 1 ? '' : 's') + ' · ' + unitRoomSummary(line))"></p>
                        </div>
                        <button type="button" x-show="line.tracking === 'Individual'" @click="resetLineTags(line)" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Regenerate asset tags</button>
                    </div>

                    <div x-show="line.tracking === 'Bulk'" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $bLabel }}">Asset tag</label>
                            <input type="text" x-model="line.asset_tag" @input="line._tagManual = true; clearErr('lines.' + i + '.asset_tag')" class="{{ $bField }}" :class="err('lines.' + i + '.asset_tag') ? '{{ $bError }}' : ''" />
                            <p x-show="err('lines.' + i + '.asset_tag')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="err('lines.' + i + '.asset_tag')"></p>
                        </div>
                        <div>
                            <label class="{{ $bLabel }}">Serial number</label>
                            <input type="text" x-model="line.serial" @input="clearErr('lines.' + i + '.serial')" class="{{ $bField }}" :class="err('lines.' + i + '.serial') ? '{{ $bError }}' : ''" />
                        </div>
                    </div>

                    <template x-if="line.tracking === 'Individual'">
                        <div class="mt-3 space-y-3">
                            <div x-show="line.units.length > 1" class="flex flex-wrap items-end gap-2 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80">
                                <div>
                                    <label class="{{ $bLabel }}">Units</label>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <input type="number" min="1" :max="line.units.length" x-model.number="line.split.from" class="{{ $bCell }} !w-16" />
                                        <span>to</span>
                                        <input type="number" min="1" :max="line.units.length" x-model.number="line.split.to" class="{{ $bCell }} !w-16" />
                                    </div>
                                </div>
                                <div class="min-w-[12rem] flex-1">
                                    <label class="{{ $bLabel }}">Send to</label>
                                    <select x-model="line.split.room" class="{{ $bCell }}">
                                        <option value="0">Keep in storage</option>
                                        @foreach ($batchDeployRooms as $room)
                                            <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" @click="applySplit(line)" class="h-9 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-blue-800">Apply to range</button>
                                <button type="button" @click="line.split.from = 1; line.split.to = line.units.length; applySplit(line)" class="h-9 rounded-lg px-3 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50">Apply to all</button>
                            </div>
                            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                                <table class="w-full table-fixed text-left text-sm">
                                    <colgroup>
                                        <col class="w-12">
                                        <col>
                                        <col>
                                        <col class="w-[30%]">
                                    </colgroup>
                                    <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">#</th>
                                            <th class="px-2 py-2">Asset tag</th>
                                            <th class="px-2 py-2">Serial number</th>
                                            <th class="px-2 py-2 pr-3">Room</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="(unit, u) in line.units" :key="u">
                                            <tr>
                                                <td class="px-3 py-1.5 text-xs text-slate-400" x-text="u + 1"></td>
                                                <td class="px-2 py-1.5">
                                                    <input type="text" x-model="unit.asset_tag" @input="unit._tagManual = true; clearErr('lines.' + i + '.units.' + u + '.asset_tag')" class="{{ $bCell }}" :class="err('lines.' + i + '.units.' + u + '.asset_tag') ? '{{ $bError }}' : ''" :title="err('lines.' + i + '.units.' + u + '.asset_tag')" />
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <input type="text" x-model="unit.serial" @input="clearErr('lines.' + i + '.units.' + u + '.serial')" placeholder="Optional" class="{{ $bCell }}" :class="err('lines.' + i + '.units.' + u + '.serial') ? '{{ $bError }}' : ''" :title="err('lines.' + i + '.units.' + u + '.serial')" />
                                                </td>
                                                <td class="px-2 py-1.5 pr-3">
                                                    <select x-model="unit.room_id" @change="unit._roomManual = true; clearErr('lines.' + i + '.units.' + u + '.room_id'); retagUnit(line, unit)" class="{{ $bCell }}" :class="err('lines.' + i + '.units.' + u + '.room_id') ? '{{ $bError }}' : ''">
                                                        <option value="0">Keep in storage</option>
                                                        @foreach ($batchDeployRooms as $room)
                                                            <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </section>
            </template>
        </div>

        {{-- Step 4: review --}}
        <div class="eq-modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5" x-show="step === 'review'" x-cloak>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50/80 px-4 py-3 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Items</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900" x-text="lines.length"></p>
                </div>
                <div class="rounded-xl bg-slate-50/80 px-4 py-3 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Total quantity</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900" x-text="totalQuantity()"></p>
                </div>
                <div class="rounded-xl bg-slate-50/80 px-4 py-3 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Rooms</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900" x-text="reviewByRoom().length"></p>
                </div>
                <div class="rounded-xl bg-slate-50/80 px-4 py-3 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Total value</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-slate-900" x-text="formatPeso(totalValue())"></p>
                </div>
            </div>
            <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Source</p>
                    <button type="button" class="text-xs font-semibold text-[#0025cc] hover:underline" @click="goTo('source')">Edit</button>
                </div>
                <p class="mt-2 text-sm text-slate-700" x-text="sourceSummary()"></p>
            </section>
            <template x-for="group in reviewByRoom()" :key="group.room">
                <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900" x-text="group.room"></p>
                        <button type="button" class="text-xs font-semibold text-[#0025cc] hover:underline" @click="goTo('units')">Edit</button>
                    </div>
                    <ul class="mt-2 divide-y divide-slate-200/70 text-sm">
                        <template x-for="row in group.rows" :key="row.key">
                            <li class="flex items-center justify-between gap-4 py-2">
                                <span class="min-w-0 truncate text-slate-700"><span class="font-semibold tabular-nums text-slate-900" x-text="row.qty + ' ×'"></span> <span x-text="row.name"></span> <span class="text-xs text-slate-400" x-text="row.tracking"></span></span>
                                <span class="shrink-0 text-xs text-slate-500" x-text="row.person ? ('Issued to ' + row.person) : ''"></span>
                            </li>
                        </template>
                    </ul>
                </section>
            </template>
        </div>

        <div class="flex items-center justify-between gap-2 px-6 py-4">
            <p class="text-xs text-slate-400" x-show="step !== 'source'" x-text="lines.length + ' item' + (lines.length === 1 ? '' : 's') + ' · ' + totalQuantity() + ' total'"></p>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" @click="back()" :disabled="saving" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100" x-text="step === 'source' ? 'Cancel' : 'Back'"></button>
                <button type="button" x-show="step !== 'review'" @click="next()" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Next</button>
                <button
                    type="button"
                    x-show="step === 'review'"
                    x-cloak
                    @click="submit()"
                    :disabled="saving"
                    class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    x-text="saving ? 'Saving…' : ('Add ' + totalRecords() + ' record' + (totalRecords() === 1 ? '' : 's'))"
                ></button>
            </div>
        </div>
    </div>
</div>

<script>
    function batchAddEquipment(config) {
        const todayLocal = () => {
            const now = new Date();
            return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        };
        let lineSeq = 0;
        const blankLine = () => ({
            key: ++lineSeq,
            open: true,
            name: '',
            category_id: '',
            categoryManual: false,
            tracking: 'Individual',
            quantity: 1,
            condition: 'Good',
            brand: '',
            model: '',
            cost: '',
            warranty: '',
            useful_life_years: '',
            borrowable: false,
            destination_room_id: '0',
            custodian_id: '',
            asset_tag: '',
            serial: '',
            _tagManual: false,
            units: [],
            split: { from: 1, to: 1, room: '0' },
        });
        const blankSource = () => ({
            acquisition_source: '',
            acquisition_supplier: '',
            supplier_name: '',
            reference_number: '',
            notes: '',
            purchase_date: '',
            received_date: todayLocal(),
        });

        return {
            config,
            open: false,
            saving: false,
            step: 'source',
            steps: [
                { key: 'source', label: 'Source', hint: 'Shared details for everything in this batch: where it came from and when it arrived.' },
                { key: 'items', label: 'Items', hint: 'One row per kind of equipment, each with its own quantity, details, room, and accountable person.' },
                { key: 'units', label: 'Units & rooms', hint: 'Asset tags and serial numbers per unit. Send any unit to a different room.' },
                { key: 'review', label: 'Review', hint: 'Everything grouped by room. Nothing is saved until you confirm.' },
            ],
            storageRoomId: config.defaultStorageRoomId,
            source: blankSource(),
            lines: [blankLine()],
            errors: {},
            formError: '',

            show() {
                this.reset();
                this.open = true;
                this.refreshIcons();
            },
            close() {
                this.open = false;
                document.body.style.overflow = '';
                this.reset();
            },
            reset() {
                this.step = 'source';
                this.saving = false;
                this.storageRoomId = config.defaultStorageRoomId;
                this.source = blankSource();
                this.lines = [blankLine()];
                this.errors = {};
                this.formError = '';
            },
            refreshIcons() {
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            stepIndex() {
                return this.steps.findIndex((s) => s.key === this.step);
            },
            stepHint() {
                return this.steps[this.stepIndex()]?.hint || '';
            },
            scrollTop() {
                this.$nextTick(() => {
                    document.querySelectorAll('#batchAddEquipmentModal .eq-modal-scroll').forEach((el) => { el.scrollTop = 0; });
                    if (window.lucide) window.lucide.createIcons();
                });
            },
            goTo(key) {
                const target = this.steps.findIndex((s) => s.key === key);
                if (target < 0 || target >= this.stepIndex()) return;
                this.errors = {};
                this.formError = '';
                this.step = key;
                this.scrollTop();
            },
            next() {
                const problems = this.stepErrors(this.step);
                if (!this.applyErrors(problems)) return;
                if (this.step === 'items') {
                    this.syncUnits();
                }
                this.step = this.steps[this.stepIndex() + 1].key;
                this.scrollTop();
            },
            back() {
                if (this.step === 'source') {
                    this.close();
                    return;
                }
                this.errors = {};
                this.formError = '';
                this.step = this.steps[this.stepIndex() - 1].key;
                this.scrollTop();
            },

            err(key) {
                return this.errors[key] || '';
            },
            clearErr(key) {
                if (!this.errors[key]) return;
                const next = { ...this.errors };
                delete next[key];
                this.errors = next;
                this.formError = '';
            },
            lineHasError(i) {
                const prefix = 'lines.' + i + '.';
                return Object.keys(this.errors).some((k) => k.startsWith(prefix) && !k.startsWith(prefix + 'units.'));
            },
            applyErrors(problems) {
                this.errors = problems;
                const messages = Object.values(problems);
                this.formError = messages.length === 1
                    ? messages[0]
                    : (messages.length ? messages.length + ' fields need attention. They are highlighted in red.' : '');
                if (messages.length) {
                    this.lines.forEach((line, i) => { if (this.lineHasError(i)) line.open = true; });
                    this.refreshIcons();
                }
                return messages.length === 0;
            },
            stepErrors(step) {
                const problems = {};
                if (step === 'source') {
                    if (config.acquisitionReady && !this.source.acquisition_source) {
                        problems['source.acquisition_source'] = 'Select how this batch was acquired.';
                    }
                    if (!String(this.storageRoomId || '')) {
                        problems['storage_room_id'] = 'Select the storage room for the stock-in.';
                    }
                }
                if (step === 'items') {
                    this.lines.forEach((line, i) => {
                        if (!String(line.name || '').trim()) problems['lines.' + i + '.name'] = 'Item ' + (i + 1) + ': enter the equipment name.';
                        if (!String(line.category_id || '')) problems['lines.' + i + '.category_id'] = 'Item ' + (i + 1) + ': choose a category.';
                        const qty = Number(line.quantity);
                        if (!Number.isInteger(qty) || qty < 1 || qty > 200) problems['lines.' + i + '.quantity'] = 'Item ' + (i + 1) + ': quantity must be 1 to 200.';
                    });
                    if (this.totalRecords() > 500) {
                        problems['lines'] = 'A batch can create at most 500 records. Split it into smaller batches.';
                    }
                }
                if (step === 'units') {
                    const tags = {};
                    const serials = {};
                    const check = (map, value, key, label) => {
                        const v = String(value || '').trim().toLowerCase();
                        if (!v) return;
                        if (map[v]) {
                            problems[key] = label + ' "' + value + '" is used twice in this batch.';
                            problems[map[v]] = problems[key];
                        } else {
                            map[v] = key;
                        }
                    };
                    this.lines.forEach((line, i) => {
                        if (line.tracking === 'Bulk') {
                            check(tags, line.asset_tag, 'lines.' + i + '.asset_tag', 'Asset tag');
                            check(serials, line.serial, 'lines.' + i + '.serial', 'Serial number');
                            return;
                        }
                        line.units.forEach((unit, u) => {
                            check(tags, unit.asset_tag, 'lines.' + i + '.units.' + u + '.asset_tag', 'Asset tag');
                            check(serials, unit.serial, 'lines.' + i + '.units.' + u + '.serial', 'Serial number');
                        });
                        if (line.custodian_id && !line.units.some((unit) => String(unit.room_id) !== '0')) {
                            problems['lines.' + i + '.custodian_id'] = (line.name || 'Item ' + (i + 1)) + ': deploy at least one unit to a room, or remove the accountable person.';
                        }
                    });
                }
                return problems;
            },

            addLine() {
                this.lines.push(blankLine());
                this.refreshIcons();
            },
            duplicateLine(i) {
                const source = this.lines[i];
                const copy = blankLine();
                ['name', 'category_id', 'categoryManual', 'tracking', 'quantity', 'condition', 'brand', 'model', 'cost', 'warranty', 'useful_life_years', 'borrowable', 'destination_room_id', 'custodian_id']
                    .forEach((field) => { copy[field] = source[field]; });
                source.open = false;
                this.lines.splice(i + 1, 0, copy);
                this.errors = {};
                this.formError = '';
                this.refreshIcons();
            },
            removeLine(i) {
                this.lines.splice(i, 1);
                this.errors = {};
                this.formError = '';
            },
            detectCategory(line) {
                if (!String(line.name || '').trim()) {
                    line.categoryManual = false;
                    line.category_id = '';
                    return;
                }
                if (line.categoryManual || typeof detectEquipmentCategoryId !== 'function') return;
                line.category_id = String(detectEquipmentCategoryId(line.name) || '');
            },
            onLineDestinationChange(line) {
                line.units.forEach((unit) => {
                    if (!unit._roomManual) unit.room_id = line.destination_room_id;
                });
                this.retagAll();
            },

            roomName(id) {
                const key = String(id ?? '0');
                if (key === '0' || key === '') {
                    return config.storageRooms.find((r) => r.id === String(this.storageRoomId))?.name || 'Storage';
                }
                return config.deployRooms.find((r) => r.id === key)?.name || 'Room';
            },
            personName(id) {
                return config.people.find((p) => p.id === String(id || ''))?.name || '';
            },
            unitRoomSummary(line) {
                const counts = {};
                line.units.forEach((unit) => {
                    const name = this.roomName(unit.room_id);
                    counts[name] = (counts[name] || 0) + 1;
                });
                return Object.entries(counts).map(([name, qty]) => name + ' (' + qty + ')').join(', ');
            },

            syncUnits() {
                this.lines.forEach((line) => {
                    if (line.tracking !== 'Individual') {
                        line.units = [];
                        return;
                    }
                    const qty = Math.min(200, Math.max(1, Number(line.quantity) || 1));
                    const units = line.units.slice(0, qty);
                    while (units.length < qty) {
                        units.push({ asset_tag: '', serial: '', room_id: line.destination_room_id, _tagManual: false, _roomManual: false });
                    }
                    units.forEach((unit) => { if (!unit._roomManual) unit.room_id = line.destination_room_id; });
                    line.units = units;
                    line.split = { from: 1, to: qty, room: line.destination_room_id };
                });
                this.retagAll();
            },
            applySplit(line) {
                const from = Math.max(1, Number(line.split.from) || 1);
                const to = Math.min(line.units.length, Number(line.split.to) || line.units.length);
                if (from > to) {
                    this.formError = 'The “from” unit must be before the “to” unit.';
                    return;
                }
                for (let u = from - 1; u < to; u++) {
                    line.units[u].room_id = line.split.room;
                    line.units[u]._roomManual = true;
                }
                this.formError = '';
                this.retagAll();
            },
            generateTag(roomId, name) {
                const gen = window.equipmentAssetTags;
                if (!gen || typeof gen.generate !== 'function' || !String(name || '').trim()) return '';
                return gen.generate(this.roomName(roomId), name, 1)[0] || '';
            },
            retagAll() {
                window.equipmentAssetTags?.resetReserved?.();
                this.lines.forEach((line) => {
                    if (line.tracking === 'Bulk') {
                        if (!line._tagManual) line.asset_tag = this.generateTag(line.destination_room_id, line.name);
                        return;
                    }
                    line.units.forEach((unit) => {
                        if (!unit._tagManual) unit.asset_tag = this.generateTag(unit.room_id, line.name);
                    });
                });
            },
            retagUnit(line, unit) {
                if (!unit._tagManual) this.retagAll();
            },
            resetLineTags(line) {
                line.units.forEach((unit) => { unit._tagManual = false; });
                this.retagAll();
            },

            totalQuantity() {
                return this.lines.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0);
            },
            totalRecords() {
                return this.lines.reduce((sum, line) => sum + (line.tracking === 'Bulk' ? 1 : (Number(line.quantity) || 0)), 0);
            },
            totalValue() {
                return this.lines.reduce((sum, line) => sum + (Number(line.cost) || 0) * (Number(line.quantity) || 0), 0);
            },
            formatPeso(value) {
                if (!value) return '—';
                return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            sourceSummary() {
                const parts = [];
                if (this.source.acquisition_source) parts.push(config.sources[this.source.acquisition_source] || this.source.acquisition_source);
                const supplier = this.source.acquisition_supplier === config.otherSupplier
                    ? this.source.supplier_name
                    : (document.querySelector('#batchAddEquipmentModal select[x-model="source.acquisition_supplier"]')?.selectedOptions?.[0]?.text || '');
                if (this.source.acquisition_supplier && supplier) parts.push(supplier);
                if (this.source.reference_number) parts.push('Ref ' + this.source.reference_number);
                if (this.source.received_date) parts.push('Received ' + this.source.received_date);
                parts.push('Stocked in ' + this.roomName('0'));
                return parts.join(' · ');
            },
            reviewByRoom() {
                const groups = {};
                const add = (room, row) => {
                    (groups[room] = groups[room] || []).push(row);
                };
                this.lines.forEach((line) => {
                    if (line.tracking === 'Bulk') {
                        add(this.roomName(line.destination_room_id), { key: line.key + '-b', qty: Number(line.quantity) || 0, name: line.name, tracking: 'Bulk', person: '' });
                        return;
                    }
                    const perRoom = {};
                    line.units.forEach((unit) => {
                        const id = String(unit.room_id || '0');
                        perRoom[id] = (perRoom[id] || 0) + 1;
                    });
                    Object.entries(perRoom).forEach(([roomId, qty]) => {
                        add(this.roomName(roomId), {
                            key: line.key + '-' + roomId,
                            qty,
                            name: line.name,
                            tracking: 'Individual',
                            person: roomId !== '0' ? this.personName(line.custodian_id) : '',
                        });
                    });
                });
                return Object.entries(groups).map(([room, rows]) => ({ room, rows }));
            },

            payload() {
                return {
                    storage_room_id: Number(this.storageRoomId) || null,
                    source: { ...this.source },
                    lines: this.lines.map((line) => ({
                        name: String(line.name || '').trim(),
                        category_id: Number(line.category_id) || null,
                        tracking: line.tracking,
                        quantity: Number(line.quantity) || 0,
                        condition: line.condition,
                        brand: line.brand || null,
                        model: line.model || null,
                        cost: line.cost !== '' ? line.cost : null,
                        warranty: line.warranty || null,
                        useful_life_years: line.useful_life_years || null,
                        borrowable: !!line.borrowable,
                        destination_room_id: Number(line.destination_room_id) || 0,
                        custodian_id: line.tracking === 'Individual' ? (Number(line.custodian_id) || null) : null,
                        asset_tag: line.tracking === 'Bulk' ? (line.asset_tag || null) : null,
                        serial: line.tracking === 'Bulk' ? (line.serial || null) : null,
                        units: line.tracking === 'Individual'
                            ? line.units.map((unit) => ({
                                asset_tag: unit.asset_tag || null,
                                serial: unit.serial || null,
                                room_id: Number(unit.room_id) || 0,
                            }))
                            : [],
                    })),
                };
            },
            stepForErrorKey(key) {
                if (key.startsWith('source.') || key === 'storage_room_id') return 'source';
                if (/^lines\.\d+\.(units\.|asset_tag|serial)/.test(key)) return 'units';
                if (key.startsWith('lines')) return 'items';
                return 'review';
            },
            async submit() {
                for (const step of ['source', 'items', 'units']) {
                    const problems = this.stepErrors(step);
                    if (Object.keys(problems).length) {
                        this.step = step;
                        this.applyErrors(problems);
                        this.scrollTop();
                        return;
                    }
                }
                this.saving = true;
                this.formError = '';
                try {
                    const response = await fetch('/maintenance/equipment/batch-store', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify(this.payload()),
                    });
                    const body = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const problems = {};
                        Object.entries(body.errors || {}).forEach(([key, messages]) => {
                            problems[key] = Array.isArray(messages) ? messages[0] : String(messages);
                        });
                        if (!Object.keys(problems).length) {
                            this.formError = body.message || 'Unable to save this batch. Please try again.';
                            this.refreshIcons();
                            return;
                        }
                        const order = ['source', 'items', 'units', 'review'];
                        this.step = Object.keys(problems)
                            .map((key) => this.stepForErrorKey(key))
                            .sort((a, b) => order.indexOf(a) - order.indexOf(b))[0];
                        this.applyErrors(problems);
                        this.scrollTop();
                        return;
                    }
                    const tags = [];
                    this.lines.forEach((line) => {
                        if (line.tracking === 'Bulk') tags.push(line.asset_tag);
                        else line.units.forEach((unit) => tags.push(unit.asset_tag));
                    });
                    window.equipmentAssetTags?.register?.(tags.filter(Boolean));
                    window.location.href = body.redirect || '/maintenance/equipment/inventory';
                } catch (e) {
                    this.formError = 'Unable to save this batch. Check your connection and try again.';
                    this.refreshIcons();
                } finally {
                    this.saving = false;
                }
            },
        };
    }

    function openBatchAddEquipmentModal() {
        const modal = document.getElementById('batchAddEquipmentModal');
        if (modal && modal._x_dataStack && modal._x_dataStack[0]) {
            modal._x_dataStack[0].show();
        }
    }
</script>
