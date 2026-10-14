@php
    $wizardRooms = \App\Support\EquipmentAcquisition::intakeRooms();
    $storageRooms = $storageRooms ?? $wizardRooms['storage'];
    $deployRooms = $deployRooms ?? $wizardRooms['deploy'];
    $defaultStorageRoomId = $defaultStorageRoomId ?? optional($storageRooms->first())->room_id;
    $acquisitionReady = $acquisitionReady ?? \App\Support\EquipmentAcquisition::ready();
    $acquisitionSuppliers = $acquisitionSuppliers ?? \App\Support\EquipmentAcquisition::supplierOptions();
    $acquisitionPeople = ($acquisitionPeople ?? collect())->isNotEmpty() ? $acquisitionPeople : \App\Support\Custodians::assignable();
    $replacementCandidates = ($replacementCandidates ?? collect())->isNotEmpty() ? $replacementCandidates : \App\Support\EquipmentAcquisition::replacementCandidates();
    $eqField = $eqField ?? 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $eqLabel = $eqLabel ?? 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
    $wizardTitle = $wizardTitle ?? 'Add to inventory';
    $wizardErrorKeys = [
        'equipment_name', 'equipment_category_id', 'equipment_room_id', 'equipment_quantity',
        'equipment_image', 'equipment_tracking_mode', 'equipment_asset_tag', 'equipment_serial_number',
        'items', 'receiving_report_item_id', 'intake_reason', 'equipment_acquisition_source',
        'deploy_room_id', 'custodian_id', 'replaces_equipment_id',
    ];
@endphp

    <div
        id="addEquipmentModal"
        x-data="inventoryAddEquipment()"
        x-show="open"
        x-cloak
        x-effect="document.body.style.overflow = open ? 'hidden' : ''"
        @keydown.escape.window="if (document.getElementById('equipmentPhotoViewer')?.classList.contains('flex')) { return; } if (open) { if (fullscreen) { fullscreen = false; } else { close(); } }"
        @if ($errors->hasAny($wizardErrorKeys))
        x-init="
            open = true;
            formError = {{ json_encode($errors->first()) }};
            errors = {
                @if ($errors->has('equipment_name')) name: {{ json_encode($errors->first('equipment_name')) }}, @endif
                @if ($errors->has('equipment_category_id')) category: {{ json_encode($errors->first('equipment_category_id')) }}, @endif
                @if ($errors->has('equipment_room_id')) room: {{ json_encode($errors->first('equipment_room_id')) }}, @endif
                @if ($errors->has('equipment_quantity')) quantity: {{ json_encode($errors->first('equipment_quantity')) }}, @endif
                @if ($errors->has('equipment_image')) image: {{ json_encode($errors->first('equipment_image')) }}, @endif
                @if ($errors->has('receiving_report_item_id')) rr: {{ json_encode($errors->first('receiving_report_item_id')) }}, @endif
                @if ($errors->has('intake_reason')) intake_reason: {{ json_encode($errors->first('intake_reason')) }}, @endif
                @if ($errors->has('equipment_acquisition_source')) acq_source: {{ json_encode($errors->first('equipment_acquisition_source')) }}, @endif
                @if ($errors->has('deploy_room_id')) deploy: {{ json_encode($errors->first('deploy_room_id')) }}, @endif
                @if ($errors->has('custodian_id')) custodian: {{ json_encode($errors->first('custodian_id')) }}, @endif
            };
            page = pageForErrors();
            $nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        "
        @endif
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-hidden bg-[#0b1220]/70"
        :class="[
            open ? '!flex' : 'hidden',
            fullscreen ? 'p-0' : 'p-4'
        ]"
    >
        <form
            action="/admin/inventory/store"
            method="POST"
            enctype="multipart/form-data"
            @submit="prepareSubmit($event)"
            class="flex w-full flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
            :class="fullscreen
                ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none border-0 shadow-none'
                : (step === 2
                    ? 'max-h-[90vh] w-[calc(93vw-1.5rem)] max-w-[calc(93vw-1.5rem)] rounded-2xl'
                    : 'max-h-[90vh] max-w-4xl rounded-2xl')"
        >
            @csrf
            <input type="hidden" name="equipment_tracking_mode" :value="tracking">
            <input type="hidden" name="equipment_quantity" :value="quantity">
            @if ($isStockPage)
                <input type="hidden" name="require_receiving_basis" value="1">
                <input type="hidden" name="intake_basis" :value="intakeBasis">
                <input type="hidden" name="receiving_report_item_id" :value="rrItemId || ''">
                <input type="hidden" name="equipment_purchase_date" :value="purchaseDate || ''" :disabled="intakeBasis === 'non_procurement'">
                <input type="hidden" name="equipment_purchase_cost" :value="purchaseCost || ''" :disabled="intakeBasis === 'non_procurement'">
            @endif

            <div class="flex items-center justify-between px-6 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">{{ $wizardTitle }}</h2>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        @click="fullscreen = !fullscreen; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        :title="fullscreen ? 'Exit full screen' : 'Full screen'"
                        :aria-label="fullscreen ? 'Exit full screen' : 'Full screen'"
                    >
                        <i :data-lucide="fullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                    </button>
                    <button type="button" @click="close()" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <ol class="mx-6 mt-4 flex flex-wrap items-center gap-x-1 gap-y-2 text-xs">
                <template x-for="(wizardStep, index) in wizardSteps()" :key="wizardStep.key">
                    <li class="flex items-center gap-1">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold transition"
                            :class="index === currentStepIndex()
                                ? 'bg-[#0025cc] text-white'
                                : (index < currentStepIndex() ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-white text-slate-400 ring-1 ring-slate-200')"
                            :disabled="index >= currentStepIndex()"
                            @click="goToStep(wizardStep.key)"
                        >
                            <span
                                class="inline-flex h-4 w-4 items-center justify-center rounded-full text-[10px]"
                                :class="index === currentStepIndex() ? 'bg-white/20' : (index < currentStepIndex() ? 'bg-emerald-500 text-white' : 'bg-slate-100')"
                                x-text="index < currentStepIndex() ? '✓' : (index + 1)"
                            ></span>
                            <span x-text="wizardStep.label"></span>
                        </button>
                        <span x-show="index < wizardSteps().length - 1" class="text-slate-300">›</span>
                    </li>
                </template>
            </ol>

            <div
                x-show="formError"
                x-cloak
                class="mx-6 mt-4 flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100"
            >
                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <p class="min-w-0 flex-1 leading-relaxed" x-text="formError"></p>
                <button type="button" @click="formError = ''" class="shrink-0 rounded-lg p-1 text-rose-400 transition hover:bg-rose-100 hover:text-rose-700" aria-label="Dismiss">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5" x-show="step === 1">
                @if ($isStockPage)
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80" x-show="page === 1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Procurement basis</p>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-semibold text-[#0025cc] hover:underline"
                            @click="setIntakeBasis(intakeBasis === 'non_procurement' ? 'receiving' : 'non_procurement')"
                            x-text="intakeBasis === 'non_procurement' ? 'Use received RR instead' : 'Non-procurement intake…'"
                        ></button>
                    </div>

                    <div x-show="intakeBasis !== 'non_procurement'" x-cloak class="space-y-3">
                        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Delivered lines (RR / PO)</p>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="text-[11px] font-semibold text-[#0025cc] hover:underline"
                                        @click="loadRrLines()"
                                        :disabled="rrLoading"
                                    >Refresh</button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
                                        @click="toggleAllRrSelection()"
                                        :disabled="rrLoading || allRrSelectableIds().length === 0"
                                        :title="isAllRrSelected() ? 'Unselect every ready line' : 'Select every ready line on all RRs'"
                                    >
                                        <i data-lucide="list-checks" class="h-3.5 w-3.5"></i>
                                        <span x-text="isAllRrSelected() ? 'Unselect all RR' : 'Select all RR'"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-y-auto" :class="fullscreen ? 'max-h-[calc(100dvh-24rem)] min-h-[16rem]' : 'max-h-52'">
                                <template x-if="rrLoading">
                                    <p class="px-3 py-4 text-xs text-slate-400">Loading delivered lines…</p>
                                </template>
                                <template x-if="!rrLoading && rrGuide.length === 0">
                                    <p class="px-3 py-4 text-xs text-amber-700">No completed receiving lines yet. Finish second count on an RR first.</p>
                                </template>
                                <ul class="divide-y divide-slate-100" x-show="!rrLoading && rrGuide.length > 0">
                                    <template x-for="group in rrGroups" :key="group.key">
                                        <li>
                                            <div class="flex items-center">
                                            <label class="flex shrink-0 items-center self-stretch pl-3" :class="group.ready > 0 ? 'cursor-pointer' : ''" :title="group.ready > 0 ? 'Select all ready lines on this RR' : ''">
                                                <input
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30 disabled:opacity-40"
                                                    :checked="isRrGroupSelected(group)"
                                                    x-effect="$el.indeterminate = isRrGroupPartial(group)"
                                                    :disabled="group.ready === 0"
                                                    @change="toggleRrGroupSelection(group)"
                                                    :aria-label="'Select all ready lines on ' + group.rr_number"
                                                >
                                            </label>
                                            <button
                                                type="button"
                                                class="flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
                                                :aria-expanded="isRrGroupOpen(group) ? 'true' : 'false'"
                                                @click="toggleRrGroup(group)"
                                            >
                                                <span class="inline-flex h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="isRrGroupOpen(group) ? 'rotate-90' : ''">
                                                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-semibold text-slate-900">
                                                        <span x-text="group.rr_number"></span>
                                                        <span class="ml-1 text-xs font-normal text-slate-400" x-text="group.lines.length + (group.lines.length === 1 ? ' line' : ' lines')"></span>
                                                    </p>
                                                    <p class="mt-0.5 truncate text-[11px] text-slate-500" x-text="rrGroupMeta(group)"></p>
                                                    <p x-show="rrGroupPaperwork(group)" class="mt-0.5 truncate text-[11px] text-slate-400" x-text="rrGroupPaperwork(group)"></p>
                                                </div>
                                                <span
                                                    class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                    :class="group.remaining > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                                                    x-text="group.remaining > 0
                                                        ? (group.remaining + ' equipment quantity available')
                                                        : 'All equipment in this RR already added'"
                                                ></span>
                                            </button>
                                            </div>
                                            <ul x-show="isRrGroupOpen(group)" x-cloak class="border-t border-slate-100 bg-slate-50/60">
                                                <template x-for="line in group.lines" :key="line.id">
                                                    <li>
                                                        <label
                                                            class="flex w-full items-start gap-3 py-2 pl-3 pr-3 text-left transition"
                                                            :class="isRrSelected(line)
                                                                ? 'bg-[#0025cc]/5 ring-inset ring-1 ring-[#0025cc]/20'
                                                                : (line.is_selectable ? 'cursor-pointer hover:bg-white' : 'opacity-70')"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30 disabled:opacity-40"
                                                                :checked="isRrSelected(line)"
                                                                :disabled="!line.is_selectable"
                                                                @change="toggleRrLine(line)"
                                                                :aria-label="'Select ' + line.article"
                                                            >
                                                            <span class="w-4 shrink-0"></span>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate text-sm font-medium text-slate-900" x-text="line.article"></p>
                                                                <p x-show="rrLineCost(line)" class="mt-0.5 truncate text-[11px] text-slate-500" x-text="rrLineCost(line)"></p>
                                                                <p class="mt-0.5 text-[11px] text-slate-400">
                                                                    <template x-if="line.ordered_qty != null">
                                                                        <span>Ordered <span x-text="line.ordered_qty"></span> · </span>
                                                                    </template>
                                                                    Received <span x-text="line.received_qty || 0"></span>
                                                                    · In inventory <span x-text="line.stocked_qty || 0"></span>
                                                                    · Left <span class="font-semibold" :class="(line.remaining_qty || 0) > 0 ? 'text-amber-700' : 'text-slate-500'" x-text="line.remaining_qty || 0"></span>
                                                                </p>
                                                            </div>
                                                            <span
                                                                x-show="group.remaining > 0"
                                                                class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                                :class="line.is_selectable ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                                                                x-text="line.status_label || (line.is_selectable ? 'Ready' : 'Done')"
                                                            ></span>
                                                        </label>
                                                    </li>
                                                </template>
                                            </ul>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div
                            x-show="!rrLoading && rrLines.length === 0"
                            x-cloak
                            class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white px-4 py-3 text-sm text-slate-600 ring-1"
                            :class="errors.rr ? 'ring-2 ring-rose-400' : 'ring-slate-200/80'"
                        >
                            <div class="flex min-w-0 flex-1 items-start gap-2.5">
                                <i data-lucide="package-check" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                                <p x-text="rrGuide.length > 0
                                    ? 'Nothing left to add: every delivered RR line above is already in inventory. New deliveries appear here after the next Receiving Report is completed.'
                                    : 'No completed Receiving Reports yet. Deliveries appear here after receiving finishes the second count on an RR.'"></p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-9 shrink-0 items-center rounded-lg bg-white px-3 text-xs font-semibold text-[#0025cc] ring-1 ring-slate-200 transition hover:bg-slate-50"
                                @click="setIntakeBasis('non_procurement')"
                            >Use non-procurement intake</button>
                        </div>

                        <div x-show="rrLoading || rrLines.length > 0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="{{ $eqLabel }} mb-0">
                                    Selected equipment <span class="text-red-500">*</span>
                                    <span x-show="rrSelected.length" x-cloak class="ml-1 font-normal text-slate-500" x-text="'(' + rrSelected.length + ' line' + (rrSelected.length === 1 ? '' : 's') + ' from ' + selectedRrCount + ' RR' + (selectedRrCount === 1 ? '' : 's') + ')'"></span>
                                </p>
                                <button
                                    type="button"
                                    x-show="rrSelected.length"
                                    x-cloak
                                    class="text-[11px] font-semibold text-slate-500 hover:text-slate-800"
                                    @click="clearRrSelection()"
                                >Clear</button>
                            </div>
                            <div
                                class="mt-1.5 overflow-hidden rounded-xl bg-white ring-1"
                                :class="errors.rr ? 'ring-2 ring-rose-400' : 'ring-slate-200/80'"
                            >
                                <p x-show="!rrSelected.length" class="px-3 py-3 text-xs text-slate-400">Nothing selected yet. Tick the equipment in the list above.</p>
                                <ul x-show="rrSelected.length" x-cloak class="divide-y divide-slate-100 overflow-y-auto" :class="fullscreen ? 'max-h-32' : 'max-h-48'">
                                    <template x-for="line in selectedRrLines" :key="'sel-' + line.id">
                                        <li class="flex items-center gap-3 px-3 py-2">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-slate-900" x-text="line.article"></p>
                                                <p class="truncate text-[11px] text-slate-500" x-text="[line.rr_number, line.po_number ? ('PO ' + line.po_number) : 'No PO', line.supplier_name, line.purchase_date ? ('Bought ' + line.purchase_date) : null].filter(Boolean).join(' · ')"></p>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600" x-text="(line.remaining_qty != null ? line.remaining_qty : line.quantity) + ' quantity available'"></span>
                                            <button
                                                type="button"
                                                class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                @click="toggleRrLine(line)"
                                                :aria-label="'Remove ' + line.article"
                                            ><i data-lucide="x" class="h-3.5 w-3.5"></i></button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <p x-show="errors.rr" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.rr"></p>
                        </div>
                    </div>

                    <div x-show="intakeBasis === 'non_procurement'" x-cloak class="space-y-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="add_acquisition_source" class="{{ $eqLabel }}">How was it acquired? <span class="text-red-500">*</span></label>
                                <select
                                    id="add_acquisition_source"
                                    name="equipment_acquisition_source"
                                    x-model="acqSource"
                                    @change="clearError('acq_source')"
                                    :disabled="intakeBasis !== 'non_procurement'"
                                    class="{{ $eqField }}"
                                    :class="errors.acq_source ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                >
                                    <option value="">Select source</option>
                                    @foreach (\App\Support\EquipmentAcquisition::SOURCES as $sourceKey => $sourceLabel)
                                        <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.acq_source" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.acq_source"></p>
                            </div>
                            <div>
                                <label for="add_acquisition_supplier" class="{{ $eqLabel }}" x-text="acqSource === 'donation' ? 'Donor' : 'Supplier / source'"></label>
                                <select
                                    id="add_acquisition_supplier"
                                    name="acquisition_supplier"
                                    x-model="acqSupplier"
                                    :disabled="intakeBasis !== 'non_procurement'"
                                    data-searchable="1"
                                    data-search-placeholder="Search suppliers…"
                                    data-add-url="{{ url('/admin/inventory/suppliers/quick-add') }}"
                                    data-add-label="Add new supplier"
                                    data-add-placeholder="Supplier name"
                                    class="{{ $eqField }}"
                                >
                                    <option value="">Not recorded</option>
                                    <option value="{{ \App\Support\EquipmentAcquisition::OTHER_SUPPLIER }}">Other — one-time name (not saved to the list)</option>
                                    @foreach ($acquisitionSuppliers as $supplierOption)
                                        <option value="{{ $supplierOption->supplier_id }}">{{ $supplierOption->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="acqSupplier === '{{ \App\Support\EquipmentAcquisition::OTHER_SUPPLIER }}'" x-cloak class="sm:col-span-2">
                                <label for="add_supplier_name" class="{{ $eqLabel }}" x-text="acqSource === 'donation' ? 'Donor name' : 'Supplier / source name'"></label>
                                <input
                                    id="add_supplier_name"
                                    type="text"
                                    name="equipment_supplier_name"
                                    x-model="acqSupplierName"
                                    :disabled="intakeBasis !== 'non_procurement' || acqSupplier !== '{{ \App\Support\EquipmentAcquisition::OTHER_SUPPLIER }}'"
                                    maxlength="255"
                                    placeholder="e.g. Alumni Association, STI Main Campus, PC Express"
                                    class="{{ $eqField }}"
                                />
                            </div>
                            <div>
                                <label for="add_reference_number" class="{{ $eqLabel }}">Reference no.</label>
                                <input
                                    id="add_reference_number"
                                    type="text"
                                    name="equipment_reference_number"
                                    x-model="acqReference"
                                    :disabled="intakeBasis !== 'non_procurement'"
                                    maxlength="120"
                                    placeholder="OR / invoice / DR / deed of donation no."
                                    class="{{ $eqField }}"
                                />
                            </div>
                        </div>
                        <div>
                            <label for="add_intake_reason" class="{{ $eqLabel }}">Reason / notes <span class="text-red-500">*</span></label>
                            <input
                                id="add_intake_reason"
                                type="text"
                                name="intake_reason"
                                x-model="intakeReason"
                                @input="clearError('intake_reason')"
                                maxlength="500"
                                placeholder="e.g. Donated by Batch 2019 alumni, inventory migration from old spreadsheet"
                                class="{{ $eqField }}"
                                :class="errors.intake_reason ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            />
                            <p x-show="errors.intake_reason" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.intake_reason"></p>
                        </div>
                    </div>
                </div>
                @endif
                <div x-show="page === 2" x-cloak>
                <div x-show="!isMulti()">
                <div
                    class="mb-5 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80"
                    x-show="!needsItemStep()"
                    x-cloak
                >
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Photo (optional)</p>
                    <div class="mt-3 flex items-center gap-4">
                        <button
                            type="button"
                            x-show="imagePreview"
                            x-cloak
                            @click="openEquipmentPhotoViewer(imagePreview, name || 'Equipment photo')"
                            class="group relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80"
                            aria-label="View equipment photo fullscreen"
                        >
                            <img :src="imagePreview" alt="Equipment photo preview" class="h-full w-full object-cover">
                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                <i data-lucide="expand" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100"></i>
                            </span>
                        </button>
                        <div
                            x-show="!imagePreview"
                            class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80"
                        >
                            <span
                                class="inline-flex h-8 w-8 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(name || '') : ''"
                            ></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">Add equipment photo</p>
                            <p class="mt-0.5 text-xs text-slate-400">JPG, PNG, WebP, or GIF. Max 5 MB.</p>
                            <p x-show="imagePreview" x-cloak class="mt-0.5 text-xs text-slate-500">Click the photo to view it full screen.</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-50">
                                    Choose image
                                    <input
                                        type="file"
                                        :name="needsItemStep() ? null : 'equipment_image'"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="sr-only"
                                        x-ref="imageInput"
                                        :disabled="needsItemStep()"
                                        @change="onImageChange($event)"
                                    >
                                </label>
                                <button
                                    type="button"
                                    x-show="imagePreview"
                                    x-cloak
                                    @click="clearImage()"
                                    class="inline-flex h-9 items-center rounded-lg px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                >
                                    Remove
                                </button>
                            </div>
                            <p x-show="errors.image" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="errors.image"></p>
                        </div>
                    </div>
                </div>
                <div
                    class="mb-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/80"
                    x-show="needsItemStep()"
                    x-cloak
                >
                    Photos are optional per unit on the next step — one shared photo isn’t used when creating multiple individually tracked assets.
                </div>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What it is</p>
                        <div>
                            <label for="add_equipment_name" class="{{ $eqLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                            <input
                                id="add_equipment_name"
                                type="text"
                                name="equipment_name"
                                x-model="name"
                                @input="clearError('name'); onNameInput(); syncAssetTag()"
                                placeholder="e.g. Mouse"
                                class="{{ $eqField }}"
                                :class="errors.name ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            />
                            <p x-show="errors.name" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label for="add_equipment_category" class="{{ $eqLabel }}">Category <span class="text-rose-500">*</span></label>
                            <select
                                id="add_equipment_category"
                                name="equipment_category_id"
                                x-model="category"
                                @change="clearError('category'); onCategoryChange()"
                                class="{{ $eqField }}"
                                :class="errors.category ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            >
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.category" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.category"></p>
                            <p x-show="!errors.category" class="mt-1.5 text-xs text-slate-400">Filled from the equipment name. You can still choose another category.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="add_equipment_brand" class="{{ $eqLabel }}">Brand name</label>
                                <input id="add_equipment_brand" type="text" name="equipment_brand_name" x-model="brand" placeholder="e.g. Logitech" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="add_equipment_model" class="{{ $eqLabel }}">Model</label>
                                <input id="add_equipment_model" type="text" name="equipment_model" x-model="model" placeholder="e.g. B100" class="{{ $eqField }}" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3" x-show="!needsItemStep()">
                            <div>
                                <label for="add_equipment_asset_tag" class="{{ $eqLabel }}">Asset tag</label>
                                <input id="add_equipment_asset_tag" type="text" name="equipment_asset_tag" x-model="assetTag" @input="assetTagManual = true" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="add_equipment_serial" class="{{ $eqLabel }}">Serial number</label>
                                <input id="add_equipment_serial" type="text" name="equipment_serial_number" x-model="serial" class="{{ $eqField }}" />
                            </div>
                        </div>
                        <p x-show="needsItemStep()" x-cloak class="text-xs text-slate-400">Asset tag and serial number are entered per unit on the Units step.</p>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="add_equipment_quantity" class="{{ $eqLabel }}">Qty <span class="text-red-500">*</span></label>
                                <input
                                    id="add_equipment_quantity"
                                    type="number"
                                    min="1"
                                    max="200"
                                    x-model.number="quantity"
                                    @input="clearError('quantity'); syncAssetTag(); onSharedPhotoModeChange()"
                                    class="{{ $eqField }}"
                                    :class="errors.quantity ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                />
                                <p x-show="errors.quantity" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.quantity"></p>
                            </div>
                            <div>
                                <label for="add_equipment_condition" class="{{ $eqLabel }}">Condition</label>
                                <div
                                    x-show="!manualIntake()"
                                    class="flex h-11 items-center justify-between gap-2 rounded-xl bg-white px-3 text-sm ring-1 ring-slate-200/80"
                                    title="New deliveries from a Receiving Report are always recorded as Good."
                                >
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-900">
                                        <i data-lucide="lock" class="h-3.5 w-3.5 text-slate-400"></i>
                                        Good
                                    </span>
                                    <span class="text-[11px] text-slate-400">New delivery</span>
                                </div>
                                <select
                                    id="add_equipment_condition"
                                    name="equipment_condition_status"
                                    x-model="condition"
                                    x-show="manualIntake()"
                                    x-cloak
                                    :disabled="!manualIntake()"
                                    class="{{ $eqField }}"
                                >
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Under Maintenance">Under maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $eqLabel }}">Tracking mode</label>
                            <div class="flex h-11 rounded-xl bg-slate-100 p-1">
                                <button type="button" @click="tracking = 'Bulk'; assetTagManual = false; syncAssetTag(); onSharedPhotoModeChange()" class="flex-1 rounded-lg text-sm font-medium transition" :class="tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Bulk</button>
                                <button type="button" @click="tracking = 'Individual'; assetTagManual = false; syncAssetTag(); onSharedPhotoModeChange()" class="flex-1 rounded-lg text-sm font-medium transition" :class="tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-400" x-text="tracking === 'Bulk'
                                ? 'One record with the combined quantity.'
                                : 'Creates separate trackable assets (asset tag, serial, QR per unit).'"></p>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                            <input id="add_equipment_borrowable" type="checkbox" name="equipment_is_borrowable" value="1" class="peer sr-only">
                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-[#0025cc] after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </div>
                </div>
                @if ($isStockPage)
                    <button
                        type="button"
                        x-show="manualIntake()"
                        x-cloak
                        @click="startManualMulti()"
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-[#0025cc] transition hover:border-[#0025cc]/50 hover:bg-[#0025cc]/5"
                    >
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Add another equipment
                        <span class="font-normal text-slate-400">(e.g. monitor + mouse + keyboard at once)</span>
                    </button>
                @endif
                </div>

                <div x-show="isMulti()" x-cloak class="space-y-4">
                    <div x-show="isMultiManual()" class="flex items-start gap-2.5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/80">
                        <i data-lucide="layers" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                        <p>
                            Adding <span class="font-semibold text-slate-900" x-text="multiLines.length + ' different equipment'"></span>
                            in one go. They share the acquisition details from the first step; set cost, warranty, and lifespan per item on the next page.
                        </p>
                    </div>
                    <div x-show="isMultiRr()" class="flex items-start gap-2.5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200/80">
                        <i data-lucide="package-check" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                        <p>
                            <span class="font-semibold text-slate-900" x-text="multiLines.length + ' items'"></span>
                            fetched from
                            <span class="font-semibold text-slate-900" x-text="selectedRrCount + ' RR' + (selectedRrCount === 1 ? '' : 's')"></span>.
                            Names come from the RR line; check the category and add brand or model if you have them.
                            Condition is <span class="font-semibold text-slate-900">Good</span> because these are new deliveries.
                        </p>
                    </div>
                    <template x-for="(row, index) in multiLines" :key="'multi-' + row.id">
                        <div class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                            <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="'Item ' + (index + 1) + ' of ' + multiLines.length"></p>
                                    <p x-show="row.source" class="mt-0.5 truncate text-[11px] text-slate-500" x-text="row.source"></p>
                                </div>
                                <span x-show="isMultiRr()" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200/60">
                                    <i data-lucide="lock" class="h-3 w-3"></i>
                                    Good · new
                                </span>
                                <div x-show="isMultiManual()" class="flex items-center gap-2">
                                    <label class="sr-only" :for="'multi_condition_' + row.id">Condition</label>
                                    <select
                                        :id="'multi_condition_' + row.id"
                                        x-model="row.condition"
                                        class="h-8 rounded-lg border-0 bg-white py-0 pl-2.5 pr-8 text-xs font-medium text-slate-700 ring-1 ring-slate-200/80 focus:ring-2 focus:ring-[#0025cc]/30"
                                    >
                                        <option value="Good">Good</option>
                                        <option value="Damaged">Damaged</option>
                                        <option value="Under Maintenance">Under maintenance</option>
                                        <option value="Disposed">Disposed</option>
                                    </select>
                                    <button
                                        type="button"
                                        @click="removeManualLine(index)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 ring-1 ring-slate-200/80 transition hover:bg-rose-50 hover:text-rose-600"
                                        :aria-label="'Remove ' + (row.name || 'item ' + (index + 1))"
                                        title="Remove this equipment"
                                    ><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $eqLabel }}" :for="'multi_name_' + row.id">Equipment name <span class="text-rose-500">*</span></label>
                                    <input
                                        :id="'multi_name_' + row.id"
                                        type="text"
                                        x-model="row.name"
                                        @input="clearMultiError(row, 'name'); onMultiNameInput(row)"
                                        class="{{ $eqField }}"
                                        :class="multiError(row, 'name') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    />
                                    <p x-show="multiError(row, 'name')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'name')"></p>
                                </div>
                                <div>
                                    <label class="{{ $eqLabel }}" :for="'multi_category_' + row.id">Category <span class="text-rose-500">*</span></label>
                                    <select
                                        :id="'multi_category_' + row.id"
                                        x-model="row.category"
                                        @change="row.categoryManual = true; clearMultiError(row, 'category')"
                                        class="{{ $eqField }}"
                                        :class="multiError(row, 'category') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    >
                                        <option value="">Select category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                        @endforeach
                                    </select>
                                    <p x-show="multiError(row, 'category')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'category')"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $eqLabel }}" :for="'multi_brand_' + row.id" x-text="hasPieces(row) ? 'Brand (all pcs)' : 'Brand name'"></label>
                                        <input :id="'multi_brand_' + row.id" type="text" x-model="row.brand" placeholder="e.g. Logitech" class="{{ $eqField }}" />
                                    </div>
                                    <div>
                                        <label class="{{ $eqLabel }}" :for="'multi_model_' + row.id" x-text="hasPieces(row) ? 'Model (all pcs)' : 'Model'"></label>
                                        <input :id="'multi_model_' + row.id" type="text" x-model="row.model" placeholder="e.g. B100" class="{{ $eqField }}" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $eqLabel }}" :for="'multi_qty_' + row.id">Qty <span class="text-red-500">*</span></label>
                                        <input
                                            :id="'multi_qty_' + row.id"
                                            type="number"
                                            min="1"
                                            :max="row.max"
                                            x-model.number="row.quantity"
                                            @input="clearMultiError(row, 'quantity'); syncPieces(row)"
                                            class="{{ $eqField }}"
                                            :class="multiError(row, 'quantity') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        />
                                        <p x-show="multiError(row, 'quantity')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'quantity')"></p>
                                        <p x-show="!multiError(row, 'quantity')" class="mt-1.5 text-[11px] text-slate-400" x-text="isMultiManual() ? 'Up to ' + row.max : (row.max + ' left on this RR line')"></p>
                                    </div>
                                    <div>
                                        <p class="{{ $eqLabel }}">Tracking</p>
                                        <div class="flex h-11 rounded-xl bg-slate-100 p-1">
                                            <button type="button" @click="row.tracking = 'Bulk'" class="flex-1 rounded-lg text-xs font-medium transition" :class="row.tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Bulk</button>
                                            <button type="button" @click="row.tracking = 'Individual'; syncPieces(row)" class="flex-1 rounded-lg text-xs font-medium transition" :class="row.tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                x-show="row.tracking === 'Individual' && Number(row.quantity) > 1"
                                x-cloak
                                data-pieces
                                class="mt-3 rounded-xl bg-white p-3 ring-1 ring-slate-200/80"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900">Brand &amp; model per piece</p>
                                        <p class="text-[11px] text-slate-500" x-text="row.perPiece
                                            ? 'Fill only the pieces that differ. Blank fields use the brand and model above.'
                                            : ('All ' + (Number(row.quantity) || 0) + ' pcs use the brand and model above.')"></p>
                                    </div>
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700">
                                        <input
                                            type="checkbox"
                                            x-model="row.perPiece"
                                            @change="syncPieces(row)"
                                            class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30"
                                        >
                                        Different per piece
                                    </label>
                                </div>
                                <div x-show="hasPieces(row)" x-cloak class="mt-3">
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <p class="text-[11px] text-slate-400" x-text="(row.pieces || []).length + ' pieces · slide sideways'"></p>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-800"
                                                @click="scrollPieces($el, -1)"
                                                aria-label="Previous pieces"
                                            ><i data-lucide="chevron-left" class="h-4 w-4"></i></button>
                                            <button
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-slate-800"
                                                @click="scrollPieces($el, 1)"
                                                aria-label="Next pieces"
                                            ><i data-lucide="chevron-right" class="h-4 w-4"></i></button>
                                        </div>
                                    </div>
                                    <div data-track class="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth pb-2">
                                        <template x-for="(piece, pieceIndex) in (row.pieces || [])" :key="row.id + '-pc-' + pieceIndex">
                                            <div class="w-52 shrink-0 snap-start rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200/80">
                                                <div class="mb-2 flex items-center justify-between gap-2">
                                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500" x-text="'Pc ' + (pieceIndex + 1) + ' of ' + row.pieces.length"></p>
                                                    <span
                                                        class="text-[10px] font-semibold"
                                                        :class="(piece.brand || piece.model) ? 'text-[#0025cc]' : 'text-slate-400'"
                                                        x-text="(piece.brand || piece.model) ? 'Custom' : 'Same as all'"
                                                    ></span>
                                                </div>
                                                <label class="sr-only" :for="'pc_brand_' + row.id + '_' + pieceIndex">Brand</label>
                                                <input
                                                    :id="'pc_brand_' + row.id + '_' + pieceIndex"
                                                    type="text"
                                                    x-model="piece.brand"
                                                    :placeholder="row.brand || 'Brand'"
                                                    class="h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                                                />
                                                <label class="sr-only" :for="'pc_model_' + row.id + '_' + pieceIndex">Model</label>
                                                <input
                                                    :id="'pc_model_' + row.id + '_' + pieceIndex"
                                                    type="text"
                                                    x-model="piece.model"
                                                    :placeholder="row.model || 'Model'"
                                                    class="mt-2 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                                                />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-[11px] text-slate-400" x-text="row.tracking === 'Bulk'
                                    ? 'One record with the combined quantity.'
                                    : ('Creates ' + (Number(row.quantity) || 0) + ' separate assets, each with its own asset tag and QR.')"></p>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" x-model="row.borrowable" class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30">
                                    Can be borrowed
                                </label>
                            </div>
                        </div>
                    </template>
                    <button
                        type="button"
                        x-show="isMultiManual()"
                        @click="addManualLine()"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-[#0025cc] transition hover:border-[#0025cc]/50 hover:bg-[#0025cc]/5"
                    >
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Add another equipment
                    </button>
                </div>
                </div>

                <div x-show="page === 3" x-cloak class="space-y-5">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Purchase</p>
                        <template x-if="!manualIntake()">
                            <div class="space-y-3">
                                <p class="text-xs text-slate-500" x-text="isMulti()
                                    ? 'Taken from each Receiving Report line. Correct it on the RR if something is wrong.'
                                    : 'Taken from the Receiving Report line. Correct it on the RR if something is wrong.'"></p>
                                <div x-show="isMulti()" class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                                    <template x-for="row in multiLines" :key="'buy-' + row.id">
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-3 py-2.5 last:border-b-0">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-900" x-text="row.name || row.article"></p>
                                                <p class="truncate text-[11px] text-slate-500" x-text="[row.rr_number, row.supplier_name].filter(Boolean).join(' · ')"></p>
                                            </div>
                                            <p class="shrink-0 text-xs text-slate-500">
                                                <span x-text="row.purchase_date || 'No date'"></span>
                                                <span class="mx-1 text-slate-300">·</span>
                                                <span class="font-semibold text-slate-900" x-text="formatPeso(row.unit_cost)"></span> each
                                            </p>
                                        </div>
                                    </template>
                                </div>
                                <dl x-show="!isMulti()" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200/80">
                                        <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Purchase date</dt>
                                        <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="purchaseDate || '—'"></dd>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200/80">
                                        <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Cost per unit</dt>
                                        <dd class="mt-1 text-sm font-semibold text-slate-900" x-text="formatPeso(purchaseCost)"></dd>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-2.5 ring-1 ring-slate-200/80">
                                        <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Supplier</dt>
                                        <dd class="mt-1 truncate text-sm font-semibold text-slate-900" x-text="selectedRrLine?.supplier_name || '—'"></dd>
                                    </div>
                                </dl>
                                <p class="text-xs text-slate-400">The date added to inventory is recorded as today when you save.</p>
                            </div>
                        </template>
                        <div x-show="manualIntake()" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label for="add_manual_purchase_date" class="{{ $eqLabel }}" x-text="acqSource === 'donation' ? 'Donation date' : 'Purchase date'"></label>
                                <input
                                    id="add_manual_purchase_date"
                                    type="date"
                                    name="equipment_purchase_date"
                                    x-model="purchaseDate"
                                    :disabled="!manualIntake()"
                                    class="{{ $eqField }}"
                                />
                            </div>
                            <div x-show="!isMultiManual()">
                                <label for="add_manual_purchase_cost" class="{{ $eqLabel }}" x-text="acqSource === 'donation' ? 'Estimated value per unit (₱)' : 'Cost per unit (₱)'"></label>
                                <input
                                    id="add_manual_purchase_cost"
                                    type="number"
                                    name="equipment_purchase_cost"
                                    x-model="purchaseCost"
                                    :disabled="!manualIntake()"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="{{ $eqField }}"
                                />
                            </div>
                            <div>
                                <label for="add_received_date" class="{{ $eqLabel }}">Received on</label>
                                <input
                                    id="add_received_date"
                                    type="date"
                                    name="equipment_acquired_date"
                                    x-model="receivedDate"
                                    :disabled="!manualIntake()"
                                    class="{{ $eqField }}"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Warranty &amp; lifespan</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="add_warranty_expiration" class="{{ $eqLabel }}" x-text="isMulti() ? 'Warranty expiration (all items)' : 'Warranty expiration'"></label>
                                <input id="add_warranty_expiration" type="date" name="equipment_warranty_expiration" x-model="warranty" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="add_useful_life_years" class="{{ $eqLabel }}" x-text="isMulti() ? 'Useful lifespan, years (all items)' : 'Useful lifespan (years)'"></label>
                                <input
                                    id="add_useful_life_years"
                                    type="number"
                                    name="equipment_useful_life_years"
                                    min="1"
                                    max="50"
                                    step="1"
                                    x-model="usefulLifeYears"
                                    placeholder="Default 5"
                                    class="{{ $eqField }}"
                                />
                                <p class="mt-1.5 text-xs text-slate-400">Used for replacement alerts when the equipment nears end of life.</p>
                            </div>
                        </div>
                        <div x-show="isMulti()" x-cloak class="space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs text-slate-500">Per item. Leave a field blank to use the value for all items above.</p>
                                <button type="button" @click="applyWarrantyToAll()" class="text-xs font-semibold text-[#0025cc] hover:underline">Copy to every item</button>
                            </div>
                            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                                <div
                                    class="hidden gap-3 border-b border-slate-100 bg-slate-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400 sm:grid"
                                    :class="isMultiManual() ? 'grid-cols-[minmax(0,1fr)_8rem_11rem_7rem]' : 'grid-cols-[minmax(0,1fr)_11rem_8rem]'"
                                >
                                    <span>Item</span>
                                    <span x-show="isMultiManual()" x-text="acqSource === 'donation' ? 'Value / unit (₱)' : 'Cost / unit (₱)'"></span>
                                    <span>Warranty expiration</span>
                                    <span>Lifespan (yrs)</span>
                                </div>
                                <template x-for="row in multiLines" :key="'warranty-' + row.id">
                                    <div
                                        class="grid grid-cols-1 items-center gap-2 border-b border-slate-100 px-3 py-2.5 last:border-b-0 sm:gap-3"
                                        :class="isMultiManual() ? 'sm:grid-cols-[minmax(0,1fr)_8rem_11rem_7rem]' : 'sm:grid-cols-[minmax(0,1fr)_11rem_8rem]'"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-900" x-text="row.name || row.article || 'Unnamed item'"></p>
                                            <p class="truncate text-[11px] text-slate-500" x-text="[row.tracking === 'Bulk' ? 'Bulk · ' + row.quantity : (row.quantity + ' units'), row.rr_number].filter(Boolean).join(' · ')"></p>
                                        </div>
                                        <input
                                            x-show="isMultiManual()"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            x-model="row.cost"
                                            placeholder="0.00"
                                            class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                            :aria-label="'Cost per unit for ' + (row.name || 'item')"
                                        />
                                        <input
                                            type="date"
                                            x-model="row.warranty"
                                            class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                            :class="row.warranty ? 'text-slate-900' : 'text-slate-400'"
                                            :aria-label="'Warranty expiration for ' + (row.name || row.article)"
                                        />
                                        <input
                                            type="number"
                                            min="1"
                                            max="50"
                                            step="1"
                                            x-model="row.useful_life_years"
                                            :placeholder="usefulLifeYears ? String(usefulLifeYears) : '5'"
                                            class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                            :aria-label="'Useful lifespan in years for ' + (row.name || row.article)"
                                        />
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-slate-400">Individually tracked items can still get a different warranty per unit on the Units step.</p>
                        </div>
                    </div>
                </div>

                <div x-show="page === 4" x-cloak class="space-y-5">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Where it goes</p>
                        @if ($isStockPage)
                            <div>
                                <label class="{{ $eqLabel }}">After adding to inventory</label>
                                <div class="flex h-11 rounded-xl bg-slate-100 p-1">
                                    <button type="button" @click="setPlacement('storage')" class="flex-1 rounded-lg text-sm font-medium transition" :class="placement === 'storage' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Keep in storage</button>
                                    <button type="button" @click="setPlacement('deploy')" class="flex-1 rounded-lg text-sm font-medium transition" :class="placement === 'deploy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Deploy to a room now</button>
                                </div>
                                <p class="mt-1.5 text-xs text-slate-400" x-text="placement === 'deploy'
                                    ? ('Recorded in ' + (optionText('add_equipment_room') || 'the storage room') + ' first, then transferred to the room you choose.')
                                    : 'Stays in storage until you transfer it to a classroom, lab, or office.'"></p>
                            </div>
                        @endif
                        <div x-show="placement !== 'deploy'">
                            <label for="add_equipment_room" class="{{ $eqLabel }}">{{ $isStockPage ? 'Storage room' : 'Room' }} <span class="text-rose-500">*</span></label>
                            <select
                                id="add_equipment_room"
                                name="equipment_room_id"
                                x-model="room"
                                @change="clearError('room'); syncAssetTag()"
                                class="{{ $eqField }}"
                                :class="errors.room ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            >
                                <option value="">{{ $isStockPage ? 'Select storage room' : 'Select room' }}</option>
                                @foreach (($isStockPage ? ($storageRooms ?? $rooms) : $rooms) as $room)
                                    @php
                                        $isStorageRoom = \App\Support\RoomCategories::isStorageType($room->room_type ?? null);
                                        $roomLabel = $isStockPage
                                            ? $room->room_name
                                            : ($isStorageRoom ? 'Storage · '.$room->room_name : $room->room_name);
                                    @endphp
                                    <option value="{{ $room->room_id }}">{{ $roomLabel }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.room" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.room"></p>
                        </div>
                        @if ($isStockPage)
                            <div x-show="placement === 'deploy'" x-cloak>
                                <label for="add_deploy_room" class="{{ $eqLabel }}" x-text="hasUnitsStep() ? 'Deploy to (default room)' : 'Deploy to'"></label>
                                <select
                                    id="add_deploy_room"
                                    name="deploy_room_id"
                                    x-model="deployRoom"
                                    @change="clearError('deploy'); syncAssetTag()"
                                    :disabled="placement !== 'deploy'"
                                    data-searchable="1"
                                    data-search-placeholder="Search rooms…"
                                    class="{{ $eqField }}"
                                    :class="errors.deploy ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                >
                                    <option value="">Select room</option>
                                    @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                        <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.deploy || errors.room" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.deploy || errors.room"></p>
                                <p x-show="!errors.deploy && !errors.room && hasUnitsStep()" class="mt-1.5 text-xs text-slate-400">Every unit goes here unless you pick another room for it on the Units step, so you can split the equipment across several rooms.</p>
                            </div>
                        @endif
                    </div>
                    @if ($isStockPage)
                        <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Accountability (optional)</p>
                            <div>
                                <label for="add_custodian" class="{{ $eqLabel }}" x-text="isMulti() ? 'Accountable person (default for all units)' : 'Accountable person'"></label>
                                <select
                                    id="add_custodian"
                                    name="custodian_id"
                                    x-model="custodianId"
                                    @change="clearError('custodian')"
                                    :disabled="!canAssignPerson()"
                                    data-searchable="1"
                                    data-search-placeholder="Search people…"
                                    class="{{ $eqField }}"
                                    :class="errors.custodian ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                >
                                    <option value="">No one yet</option>
                                    @foreach ($acquisitionPeople as $person)
                                        <option value="{{ $person->custodian_id }}">{{ $person->custodian_full_name }}{{ $person->custodian_department ? ' · '.$person->custodian_department : '' }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.custodian" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.custodian"></p>
                                <p x-show="!errors.custodian" class="mt-1.5 text-xs text-slate-400" x-text="custodianHint()"></p>
                            </div>
                            <p x-show="isMulti()" x-cloak class="text-xs text-slate-500">
                                Replacement links are set per item from the equipment page.<span x-show="isMultiRr()"> RR lines raised from a replacement request still link automatically.</span>
                            </p>
                            <div x-show="!isMulti()">
                                <label for="add_replaces" class="{{ $eqLabel }}">Replaces equipment</label>
                                <select
                                    id="add_replaces"
                                    name="replaces_equipment_id"
                                    x-model="replacesId"
                                    data-searchable="1"
                                    data-search-placeholder="Search equipment…"
                                    class="{{ $eqField }}"
                                >
                                    <option value="">Not a replacement</option>
                                    @foreach ($replacementCandidates as $candidate)
                                        <option value="{{ $candidate->equipment_id }}">{{ $candidate->equipment_name }}{{ $candidate->equipment_asset_tag ? ' · '.$candidate->equipment_asset_tag : '' }}{{ $candidate->room_name ? ' · '.$candidate->room_name : '' }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-xs text-slate-400">Links the old unit marked “For replacement” to this new one. RR deliveries from a replacement request link automatically.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5" x-show="step === 2" x-cloak>
                <div x-show="!isMulti()">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <div class="text-sm text-slate-600">
                        <span class="font-medium text-slate-900" x-text="name"></span>
                        <span class="text-slate-400"> · </span>
                        <span x-text="items.length + ' individually tracked units'"></span>
                        <span class="text-slate-400"> · </span>
                        <span class="text-slate-500" x-text="deploying() ? ('Going to ' + roomCount(items.map((item) => item.deploy_room_id)) + ' room(s)') : 'Optional photo per unit'"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="regenerateAssetTags()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Regenerate asset tags</button>
                        <button type="button" @click="applyDefaults()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Re-apply shared defaults</button>
                    </div>
                </div>
                <div class="rounded-xl ring-1 ring-slate-200">
                    <table class="w-full table-fixed divide-y divide-slate-200 text-left text-sm">
                        <colgroup>
                            <col class="w-[3%]">
                            <col class="w-[13%]">
                            <col class="w-[18%]">
                            <col class="w-[15%]">
                            <col class="w-[11%]">
                            <col class="w-[10%]">
                            <col class="w-[9%]">
                            <col class="w-[10%]">
                            <col class="w-[11%]">
                        </colgroup>
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-2 py-2.5">#</th>
                                <th class="px-2 py-2.5">Photo</th>
                                <th class="px-2 py-2.5">Asset tag</th>
                                <th class="px-2 py-2.5">Room</th>
                                <th class="px-2 py-2.5">Serial</th>
                                <th class="px-2 py-2.5">Brand</th>
                                <th class="px-2 py-2.5">Model</th>
                                <th class="px-2 py-2.5">Condition</th>
                                <th class="px-2 py-2.5">Warranty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-2 py-2 text-slate-400" x-text="index + 1"></td>
                                    <td class="px-2 py-2">
                                        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                            <button
                                                type="button"
                                                x-show="item._imagePreview"
                                                x-cloak
                                                @click="openEquipmentPhotoViewer(item._imagePreview, (item.equipment_asset_tag || name || 'Equipment') + ' photo')"
                                                class="group relative h-9 w-9 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-slate-200"
                                                aria-label="View unit photo"
                                            >
                                                <img :src="item._imagePreview" alt="" class="h-full w-full object-cover">
                                            </button>
                                            <div
                                                x-show="!item._imagePreview"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-slate-50 ring-1 ring-slate-200"
                                            >
                                                <span
                                                    class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                    x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(name || '') : ''"
                                                ></span>
                                            </div>
                                            <label class="inline-flex h-8 cursor-pointer items-center rounded-md bg-white px-2 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                                <span x-text="item._imagePreview ? 'Change' : 'Add'"></span>
                                                <input
                                                    type="file"
                                                    :name="'items[' + index + '][equipment_image]'"
                                                    :data-item-image-index="index"
                                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                                    class="sr-only"
                                                    @change="onItemImageChange(index, $event)"
                                                >
                                            </label>
                                            <button
                                                type="button"
                                                x-show="item._imagePreview"
                                                x-cloak
                                                @click="clearItemImage(index)"
                                                class="text-[11px] font-semibold text-rose-600 hover:text-rose-700"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_asset_tag]'" x-model="item.equipment_asset_tag" @input="item._tagManual = true" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_asset_tag || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-medium leading-snug text-slate-700 shadow-[0_8px_24px_rgba(15,23,42,0.14)] opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_asset_tag"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <select
                                            x-show="deploying()"
                                            :name="'items[' + index + '][deploy_room_id]'"
                                            x-model="item.deploy_room_id"
                                            :disabled="!deploying()"
                                            @change="onItemRoomChange(item)"
                                            class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                        >
                                            @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                                <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="!deploying()" class="truncate text-xs text-slate-500" x-text="optionText('add_equipment_room') || 'Storage'"></p>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_serial_number]'" x-model="item.equipment_serial_number" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_serial_number || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-medium leading-snug text-slate-700 shadow-[0_8px_24px_rgba(15,23,42,0.14)] opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_serial_number"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_brand_name]'" x-model="item.equipment_brand_name" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_brand_name || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-medium leading-snug text-slate-700 shadow-[0_8px_24px_rgba(15,23,42,0.14)] opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_brand_name"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_model]'" x-model="item.equipment_model" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            <div
                                                x-show="String(item.equipment_model || '').trim()"
                                                x-cloak
                                                class="pointer-events-none absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-xs font-medium leading-snug text-slate-700 shadow-[0_8px_24px_rgba(15,23,42,0.14)] opacity-0 invisible transition group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_model"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div
                                            x-show="!manualIntake()"
                                            class="flex h-9 items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 text-sm text-slate-700"
                                            title="New deliveries from a Receiving Report are always recorded as Good."
                                        >
                                            <i data-lucide="lock" class="h-3 w-3 shrink-0 text-slate-400"></i>
                                            Good
                                        </div>
                                        <select
                                            :name="'items[' + index + '][equipment_condition_status]'"
                                            x-model="item.equipment_condition_status"
                                            x-show="manualIntake()"
                                            :disabled="!manualIntake()"
                                            class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                        >
                                            <option value="Good">Good</option>
                                            <option value="Damaged">Damaged</option>
                                            <option value="Under Maintenance">Under Maintenance</option>
                                            <option value="Disposed">Disposed</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="date" :name="'items[' + index + '][equipment_warranty_expiration]'" x-model="item.equipment_warranty_expiration" class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm" />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                </div>

                <div x-show="isMulti()" x-cloak class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                        <div class="text-sm text-slate-600">
                            <span class="font-medium text-slate-900" x-text="multiLines.length + ' items'"></span>
                            <span class="text-slate-400"> · </span>
                            <span x-text="multiUnitCount() + ' records to create'"></span>
                            <span class="text-slate-400"> · </span>
                            <span class="text-slate-500" x-text="deploying() ? ('Going to ' + roomCount(multiUnits().map((unit) => unit.deploy_room_id)) + ' room(s)') : 'Optional photo per unit'"></span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="fillMultiTags(true)" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Regenerate asset tags</button>
                            <button type="button" x-show="deploying()" @click="useDefaultRoomForAll()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Send all to default room</button>
                            <button type="button" x-show="canAssignPerson()" @click="useDefaultCustodianForAll()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50" x-text="custodianId ? 'Assign all to ' + custodianName(custodianId) : 'Clear all assignments'"></button>
                        </div>
                    </div>
                    <template x-for="row in multiLines" :key="'units-' + row.id">
                        <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900" x-text="row.name || row.article"></p>
                                    <p class="truncate text-[11px] text-slate-500" x-text="row.source || [row.brand, row.model].filter(Boolean).join(' ') || (isMultiManual() ? row.condition : '')"></p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200"
                                    x-text="row.tracking === 'Bulk' ? ('Bulk · ' + row.quantity + ' in one record') : (row.quantity + ' individual unit' + (Number(row.quantity) === 1 ? '' : 's'))"
                                ></span>
                            </div>
                            <table class="w-full table-fixed divide-y divide-slate-100 text-left text-sm">
                                <colgroup>
                                    <col class="w-[4%]">
                                    <col class="w-[14%]">
                                    <col class="w-[21%]">
                                    <col class="w-[13%]">
                                    <col class="w-[14%]">
                                    <col class="w-[17%]">
                                    <col class="w-[17%]">
                                </colgroup>
                                <thead class="bg-white text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="px-2 py-2">#</th>
                                        <th class="px-2 py-2">Photo</th>
                                        <th class="px-2 py-2">Asset tag</th>
                                        <th class="px-2 py-2">Serial</th>
                                        <th class="px-2 py-2">Warranty</th>
                                        <th class="px-2 py-2">Room</th>
                                        <th class="px-2 py-2">Assign to</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="(unit, unitIndex) in row.units" :key="row.id + '-' + unitIndex">
                                        <tr>
                                            <td class="px-2 py-2 text-slate-400" x-text="unitIndex + 1"></td>
                                            <td class="px-2 py-2">
                                                <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        x-show="unit._imagePreview"
                                                        x-cloak
                                                        @click="openEquipmentPhotoViewer(unit._imagePreview, (unit.asset_tag || row.name || 'Equipment') + ' photo')"
                                                        class="h-9 w-9 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-slate-200"
                                                        aria-label="View unit photo"
                                                    >
                                                        <img :src="unit._imagePreview" alt="" class="h-full w-full object-cover">
                                                    </button>
                                                    <div x-show="!unit._imagePreview" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-50 ring-1 ring-slate-200">
                                                        <span
                                                            class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                            x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(row.name || '') : ''"
                                                        ></span>
                                                    </div>
                                                    <label class="inline-flex h-8 cursor-pointer items-center rounded-md bg-white px-2 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                                        <span x-text="unit._imagePreview ? 'Change' : 'Add'"></span>
                                                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="onMultiUnitImage(unit, $event)">
                                                    </label>
                                                    <button
                                                        type="button"
                                                        x-show="unit._imagePreview"
                                                        x-cloak
                                                        @click="clearMultiUnitImage(unit)"
                                                        class="text-[11px] font-semibold text-rose-600 hover:text-rose-700"
                                                    >Remove</button>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="text" x-model="unit.asset_tag" @input="unit._tagManual = true" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" :title="unit.asset_tag" />
                                                <p
                                                    x-show="pieceBrandModel(row, unitIndex)"
                                                    class="mt-1 truncate text-[11px] text-slate-500"
                                                    x-text="pieceBrandModel(row, unitIndex)"
                                                ></p>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="text" x-model="unit.serial" placeholder="Optional" class="h-9 w-full min-w-0 truncate rounded-md border border-slate-200 px-2 text-sm" />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    type="date"
                                                    x-model="unit.warranty"
                                                    @input="unit._warrantyManual = true"
                                                    class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-1.5 text-sm"
                                                    :aria-label="'Warranty expiration for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <select
                                                    x-show="deploying()"
                                                    x-model="unit.deploy_room_id"
                                                    @change="onMultiUnitRoomChange(unit)"
                                                    class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                                >
                                                    @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                                        <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                                    @endforeach
                                                </select>
                                                <p x-show="!deploying()" class="truncate text-xs text-slate-500" x-text="optionText('add_equipment_room') || 'Storage'"></p>
                                            </td>
                                            <td class="px-2 py-2">
                                                <select
                                                    x-show="unitCanAssign(row)"
                                                    x-model="unit.custodian_id"
                                                    @change="unit._custodianManual = true"
                                                    class="h-9 w-full min-w-0 rounded-md border border-slate-200 px-2 text-sm"
                                                    :class="unit.custodian_id ? 'text-slate-900' : 'text-slate-400'"
                                                    :aria-label="'Accountable person for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                >
                                                    <option value="">No one yet</option>
                                                    @foreach ($acquisitionPeople as $person)
                                                        <option value="{{ $person->custodian_id }}">{{ $person->custodian_full_name }}</option>
                                                    @endforeach
                                                </select>
                                                <p
                                                    x-show="!unitCanAssign(row)"
                                                    class="truncate text-xs text-slate-400"
                                                    :title="unitAssignHint(row)"
                                                    x-text="unitAssignHint(row)"
                                                ></p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-6 py-5" x-show="step === 3" x-cloak>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Identity</p>
                            <button type="button" class="text-xs font-semibold text-[#0025cc] hover:underline" @click="goToStep('p2')">Edit</button>
                        </div>
                        <dl class="mt-3 space-y-2 text-sm">
                            <template x-for="row in reviewIdentity()" :key="row[0]">
                                <div class="flex justify-between gap-4"><dt class="text-slate-400" x-text="row[0]"></dt><dd class="text-right font-medium text-slate-900" x-text="row[1]"></dd></div>
                            </template>
                        </dl>
                    </section>
                    @if ($isStockPage)
                        <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Source</p>
                                <button type="button" class="text-xs font-semibold text-[#0025cc] hover:underline" @click="goToStep('p1')">Edit</button>
                            </div>
                            <dl class="mt-3 space-y-2 text-sm">
                                <template x-for="row in reviewSource()" :key="row[0]">
                                    <div class="flex justify-between gap-4"><dt class="text-slate-400" x-text="row[0]"></dt><dd class="text-right font-medium text-slate-900" x-text="row[1]"></dd></div>
                                </template>
                            </dl>
                        </section>
                    @endif
                    <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Purchase &amp; warranty</p>
                            <button type="button" class="text-xs font-semibold text-[#0025cc] hover:underline" @click="goToStep('p3')">Edit</button>
                        </div>
                        <dl class="mt-3 space-y-2 text-sm">
                            <template x-for="row in reviewPurchase()" :key="row[0]">
                                <div class="flex justify-between gap-4"><dt class="text-slate-400" x-text="row[0]"></dt><dd class="text-right font-medium text-slate-900" x-text="row[1]"></dd></div>
                            </template>
                        </dl>
                    </section>
                    <section class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Placement &amp; accountability</p>
                            <button type="button" class="text-xs font-semibold text-[#0025cc] hover:underline" @click="goToStep('p4')">Edit</button>
                        </div>
                        <dl class="mt-3 space-y-2 text-sm">
                            <template x-for="row in reviewPlacement()" :key="row[0]">
                                <div class="flex justify-between gap-4"><dt class="text-slate-400" x-text="row[0]"></dt><dd class="text-right font-medium text-slate-900" x-text="row[1]"></dd></div>
                            </template>
                        </dl>
                    </section>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" @click="back()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100" x-text="isFirstStep() ? 'Cancel' : 'Back'"></button>
                <button
                    type="button"
                    x-show="step !== 3"
                    @click="next()"
                    class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800"
                >
                    Next
                </button>
                <button
                    type="submit"
                    x-show="step === 3"
                    x-cloak
                    :disabled="submitting"
                    class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                    x-text="submitting
                        ? 'Saving…'
                        : (isMulti()
                            ? ('Add ' + multiLines.length + ' items to inventory')
                            : (needsItemStep() ? ('Create ' + items.length + ' assets') : {{ json_encode($wizardTitle) }}))"
                ></button>
            </div>
        </form>
    </div>

    <script>
        function inventoryAddEquipment() {
            const isStockPage = @json((bool) $isStockPage);
            const todayLocal = () => {
                const now = new Date();
                return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
            };
            const firstPage = isStockPage ? 1 : 2;
            return {
                open: false,
                step: 1,
                page: firstPage,
                placement: 'storage',
                deployRoom: '',
                custodianId: '',
                replacesId: '',
                fullscreen: false,
                tracking: 'Individual',
                name: '',
                category: '',
                categoryManual: false,
                room: @json((string) (old('equipment_room_id', $defaultStorageRoomId ?? ''))),
                quantity: 1,
                condition: 'Good',
                brand: '',
                model: '',
                warranty: '',
                usefulLifeYears: '',
                assetTag: '',
                assetTagManual: false,
                serial: '',
                items: [],
                errors: {},
                formError: '',
                imagePreview: null,
                intakeBasis: 'receiving',
                intakeReason: '',
                acqSource: '',
                acqSupplier: '',
                acqSupplierName: '',
                acqReference: '',
                receivedDate: todayLocal(),
                rrItemId: '',
                rrLines: [],
                rrGuide: [],
                rrLoading: false,
                rrExpanded: {},
                rrSelected: [],
                rrMultiLines: [],
                manualLines: [],
                manualSeq: 0,
                multiErrors: {},
                submitting: false,
                purchaseDate: '',
                purchaseCost: '',
                get rrGroups() {
                    const groups = [];
                    const byKey = {};
                    (this.rrGuide || []).forEach((line) => {
                        const key = String(line.receiving_report_id || line.rr_number || 'rr');
                        if (!byKey[key]) {
                            byKey[key] = {
                                key,
                                rr_number: line.rr_number || 'RR',
                                po_number: line.po_number || null,
                                supplier_name: line.supplier_name || null,
                                atp_number: line.atp_number || null,
                                rr_date: line.rr_date || null,
                                invoice_no: line.invoice_no || null,
                                dr_no: line.dr_no || null,
                                remaining: 0,
                                ready: 0,
                                lines: [],
                            };
                            groups.push(byKey[key]);
                        }
                        const group = byKey[key];
                        group.po_number = group.po_number || line.po_number || null;
                        group.supplier_name = group.supplier_name || line.supplier_name || null;
                        group.remaining += Number(line.remaining_qty) || 0;
                        if (line.is_selectable) group.ready += 1;
                        group.lines.push(line);
                    });
                    return groups;
                },
                rrGroupMeta(group) {
                    let date = '';
                    if (group.rr_date) {
                        const parsed = new Date(String(group.rr_date).slice(0, 10) + 'T00:00:00');
                        date = Number.isNaN(parsed.getTime())
                            ? String(group.rr_date)
                            : parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    }
                    return [
                        group.po_number ? ('PO ' + group.po_number) : 'No PO',
                        group.supplier_name || 'No supplier',
                        date,
                    ].filter(Boolean).join(' · ');
                },
                rrGroupPaperwork(group) {
                    return [
                        group.atp_number || '',
                        group.dr_no ? ('DR ' + group.dr_no) : '',
                        group.invoice_no ? ('Invoice ' + group.invoice_no) : '',
                    ].filter(Boolean).join(' · ');
                },
                rrLineCost(line) {
                    const qty = Number(line.received_qty) || 0;
                    const unit = line.unit ? String(line.unit) : '';
                    const hasCost = line.unit_cost != null && line.unit_cost !== '';
                    const total = line.amount != null && line.amount !== ''
                        ? Number(line.amount)
                        : (hasCost ? Number(line.unit_cost) * qty : null);
                    return [
                        unit ? (qty + ' ' + unit) : '',
                        hasCost ? (this.formatPeso(line.unit_cost) + ' each') : '',
                        total != null && qty > 1 ? (this.formatPeso(total) + ' total') : '',
                    ].filter(Boolean).join(' · ');
                },
                isRrGroupOpen(group) {
                    if (Object.prototype.hasOwnProperty.call(this.rrExpanded, group.key)) {
                        return this.rrExpanded[group.key];
                    }
                    return group.ready > 0 || group.lines.some((line) => this.isRrSelected(line));
                },
                toggleRrGroup(group) {
                    this.rrExpanded = { ...this.rrExpanded, [group.key]: !this.isRrGroupOpen(group) };
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                get selectedRrLine() {
                    const id = String(this.rrItemId || '');
                    if (!id) return null;
                    return (this.rrLines || []).find((line) => String(line.id) === id)
                        || (this.rrGuide || []).find((line) => String(line.id) === id)
                        || null;
                },
                async loadRrLines() {
                    if (!isStockPage) return;
                    this.rrLoading = true;
                    try {
                        const res = await fetch('/admin/inventory/receivable-lines', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const data = await res.json();
                        this.rrGuide = Array.isArray(data.guide) ? data.guide : (Array.isArray(data.lines) ? data.lines : []);
                        this.rrLines = Array.isArray(data.lines) ? data.lines : this.rrGuide.filter((line) => line.is_selectable);
                    } catch (e) {
                        this.rrLines = [];
                        this.rrGuide = [];
                    } finally {
                        this.rrLoading = false;
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    }
                },
                get selectedRrLines() {
                    return (this.rrSelected || [])
                        .map((id) => (this.rrGuide || []).find((line) => String(line.id) === id))
                        .filter(Boolean);
                },
                get selectedRrCount() {
                    return new Set(this.selectedRrLines.map((line) => String(line.receiving_report_id || line.rr_number))).size;
                },
                get multiLines() {
                    return this.isMultiManual() ? this.manualLines : this.rrMultiLines;
                },
                isMultiRr() {
                    return isStockPage && this.intakeBasis !== 'non_procurement' && (this.rrSelected || []).length > 1;
                },
                isMultiManual() {
                    return isStockPage && this.intakeBasis === 'non_procurement' && (this.manualLines || []).length > 0;
                },
                isMulti() {
                    return this.isMultiRr() || this.isMultiManual();
                },
                newManualLine(values = {}) {
                    this.manualSeq += 1;
                    return {
                        id: 'm' + this.manualSeq,
                        article: '',
                        source: '',
                        rr_number: '',
                        supplier_name: '',
                        purchase_date: '',
                        unit_cost: null,
                        name: '',
                        category: '',
                        categoryManual: false,
                        brand: '',
                        model: '',
                        quantity: 1,
                        max: 200,
                        tracking: 'Individual',
                        condition: 'Good',
                        cost: '',
                        borrowable: false,
                        warranty: '',
                        useful_life_years: '',
                        perPiece: false,
                        pieces: [],
                        ...values,
                    };
                },
                startManualMulti() {
                    const borrowable = document.getElementById('add_equipment_borrowable');
                    const first = this.newManualLine({
                        name: this.name || '',
                        category: this.category ? String(this.category) : '',
                        categoryManual: this.categoryManual,
                        brand: this.brand || '',
                        model: this.model || '',
                        quantity: Math.min(200, Math.max(1, Number(this.quantity) || 1)),
                        tracking: this.tracking,
                        condition: this.condition || 'Good',
                        cost: this.purchaseCost || '',
                        borrowable: !!borrowable?.checked,
                    });
                    this.manualLines = [first, this.newManualLine()];
                    this.clearImage();
                    this.clearErrors();
                    this.$nextTick(() => {
                        document.getElementById('multi_name_' + this.manualLines[1].id)?.focus();
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                addManualLine() {
                    const line = this.newManualLine();
                    this.manualLines = [...this.manualLines, line];
                    this.$nextTick(() => {
                        const input = document.getElementById('multi_name_' + line.id);
                        input?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        input?.focus({ preventScroll: true });
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                removeManualLine(index) {
                    const removed = this.manualLines[index];
                    (removed?.units || []).forEach((unit) => {
                        if (unit._imagePreview) URL.revokeObjectURL(unit._imagePreview);
                    });
                    const rest = this.manualLines.filter((_, i) => i !== index);
                    if (rest.length > 1) {
                        this.manualLines = rest;
                        return;
                    }
                    const only = rest[0];
                    this.manualLines = [];
                    if (!only) return;
                    this.name = only.name;
                    this.category = only.category;
                    this.categoryManual = !!only.categoryManual;
                    this.brand = only.brand;
                    this.model = only.model;
                    this.quantity = Number(only.quantity) || 1;
                    this.tracking = only.tracking;
                    this.condition = only.condition || 'Good';
                    this.purchaseCost = only.cost || '';
                    const borrowable = document.getElementById('add_equipment_borrowable');
                    if (borrowable) borrowable.checked = !!only.borrowable;
                    this.syncAssetTag();
                },
                isRrSelected(line) {
                    return !!line && (this.rrSelected || []).includes(String(line.id));
                },
                rrGroupSelectableIds(group) {
                    return group.lines.filter((line) => line.is_selectable).map((line) => String(line.id));
                },
                isRrGroupSelected(group) {
                    const ids = this.rrGroupSelectableIds(group);
                    return ids.length > 0 && ids.every((id) => this.rrSelected.includes(id));
                },
                isRrGroupPartial(group) {
                    const ids = this.rrGroupSelectableIds(group);
                    const picked = ids.filter((id) => this.rrSelected.includes(id)).length;
                    return picked > 0 && picked < ids.length;
                },
                toggleRrLine(line) {
                    if (!line || !line.is_selectable) return;
                    const id = String(line.id);
                    this.rrSelected = this.rrSelected.includes(id)
                        ? this.rrSelected.filter((value) => value !== id)
                        : [...this.rrSelected, id];
                    this.afterRrSelectionChange();
                },
                toggleRrGroupSelection(group) {
                    const ids = this.rrGroupSelectableIds(group);
                    if (!ids.length) return;
                    this.rrSelected = this.isRrGroupSelected(group)
                        ? this.rrSelected.filter((id) => !ids.includes(id))
                        : [...new Set([...this.rrSelected, ...ids])];
                    this.afterRrSelectionChange();
                },
                allRrSelectableIds() {
                    return this.rrGroups.flatMap((group) => this.rrGroupSelectableIds(group));
                },
                isAllRrSelected() {
                    const ids = this.allRrSelectableIds();
                    return ids.length > 0 && ids.every((id) => this.rrSelected.includes(id));
                },
                toggleAllRrSelection() {
                    const ids = this.allRrSelectableIds();
                    if (!ids.length) return;
                    this.rrSelected = this.isAllRrSelected()
                        ? this.rrSelected.filter((id) => !ids.includes(id))
                        : [...new Set([...this.rrSelected, ...ids])];
                    this.afterRrSelectionChange();
                },
                clearRrSelection() {
                    this.rrSelected = [];
                    this.afterRrSelectionChange();
                },
                selectRrLine(line) {
                    if (!line || !line.is_selectable) return;
                    this.rrSelected = [String(line.id)];
                    this.afterRrSelectionChange();
                },
                afterRrSelectionChange() {
                    this.clearError('rr');
                    this.condition = 'Good';
                    if (this.rrSelected.length === 1) {
                        if (String(this.rrItemId) !== this.rrSelected[0]) {
                            this.rrItemId = this.rrSelected[0];
                            this.name = '';
                            this.categoryManual = false;
                            this.onRrLineChange();
                        }
                    } else {
                        this.rrItemId = '';
                        this.purchaseDate = '';
                        this.purchaseCost = '';
                    }
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                setIntakeBasis(basis) {
                    this.intakeBasis = basis;
                    if (basis !== 'non_procurement') {
                        this.condition = 'Good';
                    }
                    this.clearError('rr');
                    this.clearError('intake_reason');
                },
                syncMultiLines() {
                    const previous = {};
                    (this.rrMultiLines || []).forEach((row) => { previous[row.id] = row; });
                    this.rrMultiLines = this.selectedRrLines.map((line) => {
                        const id = String(line.id);
                        if (previous[id]) return previous[id];
                        const article = String(line.article || '').trim();
                        const remaining = Number(line.remaining_qty != null ? line.remaining_qty : line.quantity) || 1;
                        const max = Math.min(200, Math.max(1, remaining));
                        const detected = typeof detectEquipmentCategoryId === 'function' ? detectEquipmentCategoryId(article) : '';
                        return {
                            id,
                            article,
                            source: [line.rr_number, line.po_number ? ('PO ' + line.po_number) : 'No PO', line.supplier_name].filter(Boolean).join(' · '),
                            rr_number: line.rr_number || '',
                            supplier_name: line.supplier_name || '',
                            purchase_date: line.purchase_date || '',
                            unit_cost: line.unit_cost,
                            name: article,
                            category: detected ? String(detected) : '',
                            categoryManual: false,
                            brand: '',
                            model: '',
                            quantity: max,
                            max,
                            tracking: 'Individual',
                            borrowable: false,
                            warranty: '',
                            useful_life_years: '',
                            perPiece: false,
                            pieces: [],
                        };
                    });
                    const ids = this.multiLines.map((row) => row.id);
                    const errors = {};
                    Object.keys(this.multiErrors || {}).forEach((id) => {
                        if (ids.includes(id)) errors[id] = this.multiErrors[id];
                    });
                    this.multiErrors = errors;
                },
                onMultiNameInput(row) {
                    if (row.categoryManual || typeof detectEquipmentCategoryId !== 'function') return;
                    const detected = detectEquipmentCategoryId(row.name);
                    if (detected) {
                        row.category = String(detected);
                        this.clearMultiError(row, 'category');
                    }
                },
                multiError(row, field) {
                    return (this.multiErrors[row.id] || {})[field] || '';
                },
                clearMultiError(row, field) {
                    const rowErrors = this.multiErrors[row.id];
                    if (!rowErrors || !rowErrors[field]) return;
                    const nextRow = { ...rowErrors };
                    delete nextRow[field];
                    const next = { ...this.multiErrors };
                    if (Object.keys(nextRow).length) {
                        next[row.id] = nextRow;
                    } else {
                        delete next[row.id];
                    }
                    this.multiErrors = next;
                    if (!Object.keys(next).length) this.clearError('multi');
                },
                onRrLineChange() {
                    const line = this.selectedRrLine;
                    if (!line) {
                        this.purchaseDate = '';
                        this.purchaseCost = '';
                        return;
                    }
                    if (!String(this.name || '').trim()) {
                        this.name = line.article || '';
                        this.onNameInput();
                    }
                    const qty = Number(line.remaining_qty != null ? line.remaining_qty : line.quantity) || 1;
                    this.quantity = Math.min(200, Math.max(1, qty));
                    this.purchaseDate = line.purchase_date || '';
                    this.purchaseCost = line.unit_cost != null ? String(line.unit_cost) : '';
                    this.clearError('quantity');
                    this.syncAssetTag();
                },
                onImageChange(event) {
                    const file = event.target.files?.[0];
                    if (this.imagePreview) {
                        URL.revokeObjectURL(this.imagePreview);
                    }
                    this.imagePreview = file ? URL.createObjectURL(file) : null;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clearImage() {
                    if (this.imagePreview) {
                        URL.revokeObjectURL(this.imagePreview);
                    }
                    this.imagePreview = null;
                    if (this.$refs.imageInput) {
                        this.$refs.imageInput.value = '';
                    }
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                onSharedPhotoModeChange() {
                    if (this.needsItemStep()) {
                        this.clearImage();
                    }
                },
                onItemImageChange(index, event) {
                    const item = this.items[index];
                    if (!item) return;
                    const file = event.target.files?.[0] || null;
                    if (item._imagePreview) {
                        URL.revokeObjectURL(item._imagePreview);
                    }
                    item._imageFile = file;
                    item._imagePreview = file ? URL.createObjectURL(file) : null;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clearItemImage(index) {
                    const item = this.items[index];
                    if (!item) return;
                    if (item._imagePreview) {
                        URL.revokeObjectURL(item._imagePreview);
                    }
                    item._imagePreview = null;
                    item._imageFile = null;
                    const input = document.querySelector(`[data-item-image-index="${index}"]`);
                    if (input) input.value = '';
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                clearAllItemImages() {
                    (this.items || []).forEach((item, index) => {
                        if (item?._imagePreview) {
                            URL.revokeObjectURL(item._imagePreview);
                        }
                        if (item) {
                            item._imagePreview = null;
                            item._imageFile = null;
                        }
                        const input = document.querySelector(`[data-item-image-index="${index}"]`);
                        if (input) input.value = '';
                    });
                },
                restoreItemImageInputs() {
                    (this.items || []).forEach((item, index) => {
                        if (!item?._imageFile) return;
                        const input = document.querySelector(`[data-item-image-index="${index}"]`);
                        if (!input) return;
                        try {
                            const transfer = new DataTransfer();
                            transfer.items.add(item._imageFile);
                            input.files = transfer.files;
                        } catch (e) {
                            // Browser may reject DataTransfer assignment; native input value still used when unchanged.
                        }
                    });
                },
                needsItemStep() {
                    return !this.isMulti() && this.tracking === 'Individual' && Number(this.quantity) > 1;
                },
                hasUnitsStep() {
                    return this.isMulti() || this.needsItemStep();
                },
                deploying() {
                    return isStockPage && this.placement === 'deploy';
                },
                setPlacement(placement) {
                    this.placement = placement;
                    if (placement === 'storage') {
                        this.deployRoom = '';
                        this.custodianId = '';
                        this.clearError('deploy');
                    } else if (!String(this.room || '').trim()) {
                        const first = document.querySelector('#add_equipment_room option:not([value=""])');
                        if (first) this.room = first.value;
                    }
                    this.clearError('room');
                    this.syncAssetTag();
                },
                deployRoomName(roomId) {
                    const id = String(roomId || '');
                    if (!id) return '';
                    const option = document.querySelector('#add_deploy_room option[value="' + id + '"]');
                    return option ? (option.text || '').trim() : '';
                },
                unitRoomName(roomId) {
                    return this.deploying()
                        ? this.deployRoomName(roomId || this.deployRoom)
                        : this.optionText('add_equipment_room');
                },
                roomCount(roomIds) {
                    return new Set((roomIds || []).map((id) => String(id || this.deployRoom || '')).filter(Boolean)).size;
                },
                generateTag(roomName, equipmentName) {
                    const name = String(equipmentName || '').trim();
                    if (!roomName || !name || typeof window.equipmentAssetTags?.generate !== 'function') return '';
                    return window.equipmentAssetTags.generate(roomName, name, 1)[0] || '';
                },
                fillItemTags(force) {
                    window.equipmentAssetTags?.resetReserved?.();
                    (this.items || []).forEach((item) => {
                        if (item._tagManual && !force) return;
                        item.equipment_asset_tag = this.generateTag(this.unitRoomName(item.deploy_room_id), this.name);
                        if (force) item._tagManual = false;
                    });
                },
                onItemRoomChange(item) {
                    item._roomManual = true;
                    this.fillItemTags(false);
                },
                multiUnits() {
                    return (this.multiLines || []).flatMap((row) => row.units || []);
                },
                multiUnitCount() {
                    return (this.multiLines || []).reduce((sum, row) => sum + (row.tracking === 'Bulk' ? 1 : (Number(row.quantity) || 0)), 0);
                },
                buildMultiUnits() {
                    const deploying = this.deploying();
                    this.multiLines.forEach((row) => {
                        const count = row.tracking === 'Bulk' ? 1 : Math.min(200, Math.max(1, Number(row.quantity) || 1));
                        const previous = Array.isArray(row.units) ? row.units : [];
                        previous.slice(count).forEach((unit) => {
                            if (unit._imagePreview) URL.revokeObjectURL(unit._imagePreview);
                        });
                        row.units = Array.from({ length: count }, (_, i) => {
                            const prev = previous[i] || {};
                            const roomManual = deploying && !!prev._roomManual;
                            return {
                                asset_tag: prev._tagManual ? prev.asset_tag : '',
                                serial: prev.serial || '',
                                warranty: prev._warrantyManual ? prev.warranty : (row.warranty || this.warranty || ''),
                                _warrantyManual: !!prev._warrantyManual,
                                deploy_room_id: roomManual ? prev.deploy_room_id : (deploying ? String(this.deployRoom || '') : ''),
                                _roomManual: roomManual,
                                custodian_id: prev._custodianManual ? prev.custodian_id : String(this.custodianId || ''),
                                _custodianManual: !!prev._custodianManual,
                                _tagManual: !!prev._tagManual,
                                _imageFile: prev._imageFile || null,
                                _imagePreview: prev._imagePreview || null,
                            };
                        });
                    });
                    this.fillMultiTags(false);
                },
                fillMultiTags(force) {
                    window.equipmentAssetTags?.resetReserved?.();
                    (this.multiLines || []).forEach((row) => {
                        (row.units || []).forEach((unit) => {
                            if (unit._tagManual && !force) return;
                            unit.asset_tag = this.generateTag(this.unitRoomName(unit.deploy_room_id), row.name);
                            if (force) unit._tagManual = false;
                        });
                    });
                },
                onMultiUnitRoomChange(unit) {
                    unit._roomManual = true;
                    this.fillMultiTags(false);
                },
                useDefaultRoomForAll() {
                    this.multiUnits().forEach((unit) => {
                        unit.deploy_room_id = String(this.deployRoom || '');
                        unit._roomManual = false;
                    });
                    this.fillMultiTags(false);
                },
                useDefaultCustodianForAll() {
                    this.multiUnits().forEach((unit) => {
                        unit.custodian_id = String(this.custodianId || '');
                        unit._custodianManual = false;
                    });
                },
                hasPieces(row) {
                    return !!row.perPiece && row.tracking === 'Individual' && Number(row.quantity) > 1;
                },
                syncPieces(row) {
                    if (!row.perPiece) return;
                    const count = Math.min(200, Math.max(1, Number(row.quantity) || 1));
                    const pieces = Array.isArray(row.pieces) ? row.pieces.slice(0, count) : [];
                    while (pieces.length < count) pieces.push({ brand: '', model: '' });
                    row.pieces = pieces;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                scrollPieces(el, direction) {
                    const track = el.closest('[data-pieces]')?.querySelector('[data-track]');
                    if (track) track.scrollBy({ left: direction * Math.max(220, track.clientWidth * 0.8), behavior: 'smooth' });
                },
                pieceValues(row, index) {
                    const piece = this.hasPieces(row) ? (row.pieces || [])[index] : null;
                    return {
                        brand: String((piece && piece.brand) || row.brand || '').trim(),
                        model: String((piece && piece.model) || row.model || '').trim(),
                    };
                },
                pieceBrandModel(row, index) {
                    if (!this.hasPieces(row)) return '';
                    const values = this.pieceValues(row, index);
                    return [values.brand, values.model].filter(Boolean).join(' · ');
                },
                applyWarrantyToAll() {
                    (this.multiLines || []).forEach((row) => {
                        row.warranty = this.warranty || '';
                        row.useful_life_years = this.usefulLifeYears || '';
                        (row.units || []).forEach((unit) => {
                            unit.warranty = this.warranty || '';
                            unit._warrantyManual = false;
                        });
                    });
                },
                multiWarrantySummary() {
                    const values = new Set();
                    (this.multiLines || []).forEach((row) => {
                        const units = Array.isArray(row.units) && row.units.length ? row.units : [{}];
                        units.forEach((unit) => values.add(unit.warranty || row.warranty || this.warranty || ''));
                    });
                    if (values.size > 1) return 'Varies per item';
                    return [...values][0] || '—';
                },
                multiLifespanSummary() {
                    const values = new Set((this.multiLines || []).map((row) => String(row.useful_life_years || this.usefulLifeYears || '')));
                    if (values.size > 1) return 'Varies per item';
                    const value = [...values][0];
                    return value ? value + ' years' : 'Default 5 years';
                },
                onMultiUnitImage(unit, event) {
                    const file = event.target.files?.[0] || null;
                    if (unit._imagePreview) URL.revokeObjectURL(unit._imagePreview);
                    unit._imageFile = file;
                    unit._imagePreview = file ? URL.createObjectURL(file) : null;
                    event.target.value = '';
                },
                clearMultiUnitImage(unit) {
                    if (unit._imagePreview) URL.revokeObjectURL(unit._imagePreview);
                    unit._imageFile = null;
                    unit._imagePreview = null;
                },
                validateMultiUnits() {
                    const tags = {};
                    const serials = {};
                    for (const row of this.multiLines) {
                        for (let i = 0; i < (row.units || []).length; i++) {
                            const unit = row.units[i];
                            const label = (row.name || row.article) + ' #' + (i + 1);
                            const tag = String(unit.asset_tag || '').trim().toLowerCase();
                            const serial = String(unit.serial || '').trim().toLowerCase();
                            if (tag) {
                                if (tags[tag]) {
                                    this.formError = 'Asset tag "' + unit.asset_tag.trim() + '" is used by both ' + tags[tag] + ' and ' + label + '.';
                                    return false;
                                }
                                tags[tag] = label;
                            }
                            if (serial) {
                                if (serials[serial]) {
                                    this.formError = 'Serial number "' + unit.serial.trim() + '" is used by both ' + serials[serial] + ' and ' + label + '.';
                                    return false;
                                }
                                serials[serial] = label;
                            }
                            if (this.deploying() && !String(unit.deploy_room_id || this.deployRoom || '').trim()) {
                                this.formError = 'Choose a room for ' + label + '.';
                                return false;
                            }
                        }
                    }
                    this.formError = '';
                    return true;
                },
                clearError(field) {
                    if (!this.errors[field]) return;
                    const next = { ...this.errors };
                    delete next[field];
                    this.errors = next;
                    this.formError = '';
                },
                clearErrors() {
                    this.errors = {};
                    this.formError = '';
                },
                manualIntake() {
                    return !isStockPage || this.intakeBasis === 'non_procurement';
                },
                canAssignPerson() {
                    if (!isStockPage || this.placement !== 'deploy') return false;
                    if (this.isMulti()) return this.multiLines.some((row) => this.unitCanAssign(row));
                    return this.tracking === 'Individual' && this.condition !== 'Disposed';
                },
                unitCanAssign(row) {
                    return this.deploying()
                        && row.tracking === 'Individual'
                        && (!this.isMultiManual() || row.condition !== 'Disposed');
                },
                unitAssignHint(row) {
                    if (!this.deploying()) return 'Stays in storage';
                    if (row.tracking !== 'Individual') return 'Bulk stays with the room';
                    if (row.condition === 'Disposed') return 'Disposed items can’t be assigned';
                    return '';
                },
                custodianHint() {
                    if (this.canAssignPerson()) {
                        return this.isMulti()
                            ? 'Every individually tracked unit is issued to this person. Change it per unit on the Units step to split items across several people.'
                            : 'Issues every unit to this person with a property assignment document.';
                    }
                    if (this.placement !== 'deploy') return 'Choose “Deploy to a room now” to assign a person.';
                    return this.isMulti()
                        ? 'Only individually tracked items can be assigned to a person. Bulk items stay with the room.'
                        : (this.tracking !== 'Individual' ? 'Only Individual tracking can be assigned to a person.' : 'Disposed equipment can’t be assigned.');
                },
                multiCustodianSummary() {
                    const counts = {};
                    this.multiLines.forEach((row) => {
                        if (!this.unitCanAssign(row)) return;
                        (row.units || []).forEach((unit) => {
                            if (!unit.custodian_id) return;
                            const name = this.custodianName(unit.custodian_id);
                            counts[name] = (counts[name] || 0) + 1;
                        });
                    });
                    const names = Object.keys(counts);
                    if (!names.length) return 'No one yet';
                    return names.map((name) => name + ' (' + counts[name] + ')').join(', ');
                },
                custodianName(id) {
                    const option = document.querySelector('#add_custodian option[value="' + String(id) + '"]');
                    return option ? option.text.split(' · ')[0].trim() : 'Person #' + id;
                },
                wizardSteps() {
                    const steps = [];
                    if (isStockPage) {
                        steps.push({ key: 'p1', label: 'Source', hint: 'Where the equipment came from: a delivered Receiving Report line, or a non-procurement source.' });
                    }
                    steps.push(
                        { key: 'p2', label: 'Identity', hint: 'What the equipment is: name, category, brand, model, and how it is tracked.' },
                        { key: 'p3', label: 'Purchase & warranty', hint: 'When it was bought or received, what it cost, and how long it is covered.' },
                        { key: 'p4', label: 'Placement', hint: 'Where it goes after it is added to inventory, who is accountable for it, and what it replaces.' },
                    );
                    if (this.hasUnitsStep()) {
                        steps.push({
                            key: 'units',
                            label: 'Units',
                            hint: this.deploying()
                                ? 'Asset tag, serial number, optional photo, and the room each unit goes to.'
                                : 'Unique asset tag, serial number, and optional photo per unit.',
                        });
                    }
                    steps.push({ key: 'review', label: 'Review', hint: 'Check everything before saving. These fill the equipment page.' });
                    return steps;
                },
                currentStepKey() {
                    if (this.step === 2) return 'units';
                    if (this.step === 3) return 'review';
                    return 'p' + this.page;
                },
                currentStepIndex() {
                    return Math.max(0, this.wizardSteps().findIndex((s) => s.key === this.currentStepKey()));
                },
                currentStep() {
                    return this.wizardSteps()[this.currentStepIndex()] || { hint: '' };
                },
                isFirstStep() {
                    return this.step === 1 && this.page === firstPage;
                },
                scrollToTop() {
                    this.$nextTick(() => {
                        document.querySelectorAll('#addEquipmentModal .eq-modal-scroll').forEach((el) => { el.scrollTop = 0; });
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                goToStep(key) {
                    const target = this.wizardSteps().findIndex((s) => s.key === key);
                    if (target < 0 || target >= this.currentStepIndex()) return;
                    this.clearErrors();
                    if (key === 'units') {
                        this.step = 2;
                    } else if (key === 'review') {
                        this.step = 3;
                    } else {
                        this.step = 1;
                        this.page = Number(key.slice(1));
                    }
                    this.scrollToTop();
                },
                next() {
                    if (this.step === 1) {
                        if (!this.validatePage(this.page)) return;
                        if (this.page === 1 && this.isMultiRr()) {
                            this.syncMultiLines();
                        }
                        if (this.page < 4) {
                            this.page += 1;
                            this.scrollToTop();
                            return;
                        }
                        if (this.hasUnitsStep()) {
                            this.goToItems();
                            return;
                        }
                        this.items = [];
                        this.syncAssetTag();
                        this.step = 3;
                        this.scrollToTop();
                        return;
                    }
                    if (this.step === 2) {
                        if (!(this.isMulti() ? this.validateMultiUnits() : this.validateItems())) return;
                        this.step = 3;
                        this.scrollToTop();
                    }
                },
                back() {
                    this.clearErrors();
                    if (this.step === 3) {
                        if (this.hasUnitsStep()) {
                            this.step = 2;
                        } else {
                            this.step = 1;
                            this.page = 4;
                        }
                    } else if (this.step === 2) {
                        this.step = 1;
                        this.page = 4;
                    } else if (this.page > firstPage) {
                        this.page -= 1;
                    } else {
                        this.close();
                        return;
                    }
                    this.scrollToTop();
                },
                pageErrors(page) {
                    const next = {};
                    if (page === 1 && isStockPage) {
                        if (this.intakeBasis === 'non_procurement') {
                            if (!String(this.intakeReason || '').trim()) {
                                next.intake_reason = 'Enter a reason for non-procurement intake.';
                            }
                            if (@json($acquisitionReady) && !String(this.acqSource || '').trim()) {
                                next.acq_source = 'Select how this equipment was acquired.';
                            }
                        } else if (!(this.rrSelected || []).length) {
                            next.rr = (this.rrLines || []).length === 0
                                ? 'There is no RR line left to add. Click “Use non-procurement intake” to add equipment without a Receiving Report.'
                                : 'Tick at least one received equipment line from the RR list.';
                        }
                    }
                    if (page === 2 && this.isMulti()) {
                        if (this.isMultiRr() && !this.multiLines.length) this.syncMultiLines();
                        const rowErrors = {};
                        this.multiLines.forEach((row) => {
                            const rowError = {};
                            if (!String(row.name || '').trim()) rowError.name = 'Equipment name is required.';
                            if (!String(row.category || '').trim()) rowError.category = 'Please select a category.';
                            const qty = Number(row.quantity);
                            if (!Number.isInteger(qty) || qty < 1) {
                                rowError.quantity = 'Quantity must be at least 1.';
                            } else if (qty > row.max) {
                                rowError.quantity = this.isMultiManual()
                                    ? 'Quantity cannot exceed ' + row.max + '.'
                                    : 'Only ' + row.max + ' left on this RR line.';
                            }
                            if (Object.keys(rowError).length) rowErrors[row.id] = rowError;
                        });
                        this.multiErrors = rowErrors;
                        const count = Object.keys(rowErrors).length;
                        if (count) {
                            next.multi = count === 1
                                ? 'Fix the highlighted item before continuing.'
                                : ('Fix the ' + count + ' highlighted items before continuing.');
                        }
                    } else if (page === 2) {
                        if (!String(this.name || '').trim()) {
                            next.name = 'Equipment name is required.';
                        }
                        if (!String(this.category || '').trim()) {
                            next.category = 'Please select a category.';
                        }
                        const qty = Number(this.quantity);
                        if (!Number.isFinite(qty) || qty < 1) {
                            next.quantity = 'Quantity must be at least 1.';
                        } else if (qty > 200) {
                            next.quantity = 'Quantity cannot exceed 200.';
                        } else if (isStockPage && this.intakeBasis !== 'non_procurement' && this.selectedRrLine) {
                            const maxQty = Number(this.selectedRrLine.quantity) || 0;
                            if (maxQty > 0 && qty > maxQty) {
                                next.quantity = 'Quantity cannot exceed received qty (' + maxQty + ').';
                            }
                        }
                    }
                    if (page === 3) {
                        const badLife = (value) => {
                            if (value === '' || value === null || value === undefined) return false;
                            const years = Number(value);
                            return !Number.isInteger(years) || years < 1 || years > 50;
                        };
                        if (badLife(this.usefulLifeYears)) {
                            next.useful_life = 'Useful lifespan must be a whole number from 1 to 50 years.';
                        } else if (this.isMulti()) {
                            const row = this.multiLines.find((line) => badLife(line.useful_life_years));
                            if (row) next.useful_life = 'Lifespan for ' + (row.name || row.article) + ' must be a whole number from 1 to 50 years.';
                        }
                        if (this.isMultiManual()) {
                            const row = this.multiLines.find((line) => line.cost !== '' && line.cost !== null && (!Number.isFinite(Number(line.cost)) || Number(line.cost) < 0));
                            if (row) next.useful_life = 'Cost for ' + (row.name || 'an item') + ' must be zero or more.';
                        }
                    }
                    if (page === 4) {
                        if (this.deploying() && !String(this.room || '').trim()) {
                            const first = document.querySelector('#add_equipment_room option:not([value=""])');
                            if (first) this.room = first.value;
                        }
                        if (!String(this.room || '').trim()) {
                            next.room = 'Please select a room.';
                        }
                        if (isStockPage && this.placement === 'deploy' && !String(this.deployRoom || '').trim()) {
                            next.deploy = 'Choose the room to deploy to.';
                        }
                    }
                    return next;
                },
                pageForErrors() {
                    const keys = Object.keys(this.errors || {});
                    if (isStockPage && keys.some((k) => ['rr', 'intake_reason', 'acq_source'].includes(k))) return 1;
                    if (keys.some((k) => ['name', 'category', 'quantity', 'image', 'multi'].includes(k))) return 2;
                    if (keys.includes('useful_life')) return 3;
                    if (keys.some((k) => ['room', 'deploy', 'custodian'].includes(k))) return 4;
                    return firstPage;
                },
                validatePage(page) {
                    return this.applyErrors(this.pageErrors(page));
                },
                validateStep1() {
                    for (let page = firstPage; page <= 4; page++) {
                        const next = this.pageErrors(page);
                        if (Object.keys(next).length) {
                            this.step = 1;
                            this.page = page;
                            return this.applyErrors(next);
                        }
                    }
                    return this.applyErrors({});
                },
                applyErrors(next) {
                    this.errors = next;
                    const messages = Object.values(next);
                    this.formError = messages.length === 1
                        ? messages[0]
                        : (messages.length ? 'Please fix the highlighted fields before continuing.' : '');
                    if (Object.keys(next).length) {
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                    }
                    return Object.keys(next).length === 0;
                },
                validateItems() {
                    const tags = {};
                    const serials = {};
                    for (let i = 0; i < this.items.length; i++) {
                        const tag = String(this.items[i].equipment_asset_tag || '').trim().toLowerCase();
                        const serial = String(this.items[i].equipment_serial_number || '').trim().toLowerCase();
                        if (tag) {
                            if (tags[tag] !== undefined) {
                                this.formError = `Duplicate asset tag on rows ${tags[tag] + 1} and ${i + 1}.`;
                                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                                return false;
                            }
                            tags[tag] = i;
                        }
                        if (serial) {
                            if (serials[serial] !== undefined) {
                                this.formError = `Duplicate serial number on rows ${serials[serial] + 1} and ${i + 1}.`;
                                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                                return false;
                            }
                            serials[serial] = i;
                        }
                    }
                    this.formError = '';
                    return true;
                },
                onNameInput() {
                    if (!String(this.name || '').trim()) {
                        this.categoryManual = false;
                        this.category = '';
                        return;
                    }
                    if (this.categoryManual) {
                        return;
                    }
                    if (typeof detectEquipmentCategoryId === 'function') {
                        this.category = detectEquipmentCategoryId(this.name) || '';
                        if (this.category) this.clearError('category');
                    }
                },
                onCategoryChange() {
                    if (
                        typeof detectEquipmentCategoryId === 'function'
                        && String(this.category) === String(detectEquipmentCategoryId(this.name) || '')
                    ) {
                        return;
                    }
                    this.categoryManual = true;
                },
                reset() {
                    this.step = 1;
                    this.page = firstPage;
                    this.placement = 'storage';
                    this.deployRoom = '';
                    this.custodianId = '';
                    this.replacesId = '';
                    this.fullscreen = false;
                    this.tracking = 'Individual';
                    this.name = '';
                    this.category = '';
                    this.categoryManual = false;
                    this.room = @json((string) ($defaultStorageRoomId ?? ''));
                    this.quantity = 1;
                    this.condition = 'Good';
                    this.brand = '';
                    this.model = '';
                    this.warranty = '';
                    this.usefulLifeYears = '';
                    this.assetTag = '';
                    this.assetTagManual = false;
                    this.serial = '';
                    this.intakeBasis = 'receiving';
                    this.intakeReason = '';
                    this.acqSource = '';
                    this.acqSupplier = '';
                    this.acqSupplierName = '';
                    this.acqReference = '';
                    this.receivedDate = todayLocal();
                    this.rrItemId = '';
                    this.rrExpanded = {};
                    this.rrSelected = [];
                    this.multiUnits().forEach((unit) => {
                        if (unit._imagePreview) URL.revokeObjectURL(unit._imagePreview);
                    });
                    this.rrMultiLines = [];
                    this.manualLines = [];
                    this.multiErrors = {};
                    this.submitting = false;
                    this.purchaseDate = '';
                    this.purchaseCost = '';
                    this.clearAllItemImages();
                    this.items = [];
                    this.clearImage();
                    this.clearErrors();
                    const borrowable = document.getElementById('add_equipment_borrowable');
                    if (borrowable) borrowable.checked = false;
                },
                show(rrItemId = null) {
                    this.reset();
                    @if (old('equipment_room_id'))
                        this.room = @json((string) old('equipment_room_id'));
                    @endif
                    this.open = true;
                    this.loadRrLines().then(() => {
                        if (!rrItemId) return;
                        const line = (this.rrGuide || []).find((row) => String(row.id) === String(rrItemId));
                        if (line) this.selectRrLine(line);
                    });
                    this.$nextTick(() => {
                        document.getElementById('add_equipment_name')?.dispatchEvent(new Event('equipment-category-reset'));
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                close() {
                    this.open = false;
                    this.reset();
                    document.body.style.overflow = '';
                },
                slug() {
                    return this.assetTagPart(this.name, 'EQ');
                },
                assetTagPart(value, fallback) {
                    return String(value || '')
                        .toUpperCase()
                        .replace(/[^A-Z0-9]+/g, '')
                        || fallback;
                },
                selectedRoomName() {
                    const deploying = this.placement === 'deploy' && String(this.deployRoom || '') !== '';
                    return this.optionText(deploying ? 'add_deploy_room' : 'add_equipment_room');
                },
                optionText(selectId) {
                    const select = document.getElementById(selectId);
                    const option = select?.selectedOptions?.[0];
                    return option && option.value !== '' ? (option.text || '').trim() : '';
                },
                formatPeso(value) {
                    if (value === '' || value === null || value === undefined || Number.isNaN(Number(value))) return '—';
                    return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                categoryName(id) {
                    const option = document.querySelector('#add_equipment_category option[value="' + String(id || '') + '"]');
                    return option && option.value !== '' ? (option.text || '').trim() : '';
                },
                reviewIdentity() {
                    if (this.isMulti()) {
                        const rows = this.multiLines.map((row, index) => [
                            (index + 1) + '. ' + (row.name || row.article || 'Item'),
                            [
                                row.quantity + ' · ' + row.tracking,
                                this.categoryName(row.category),
                                this.hasPieces(row) ? 'Brand/model per piece' : [row.brand, row.model].filter(Boolean).join(' '),
                                this.isMultiManual() ? row.condition : '',
                                row.borrowable ? 'Borrowable' : '',
                            ]
                                .filter(Boolean).join(' · '),
                        ]);
                        const total = this.multiLines.reduce((sum, row) => sum + (Number(row.quantity) || 0), 0);
                        rows.push(['Total units', String(total)]);
                        if (this.isMultiRr()) rows.push(['Condition', 'Good (new delivery)']);
                        return rows;
                    }
                    const rows = [
                        ['Name', this.name || '—'],
                        ['Category', this.optionText('add_equipment_category') || '—'],
                        ['Brand / model', [this.brand, this.model].filter(Boolean).join(' · ') || '—'],
                        ['Quantity', this.quantity + ' · ' + this.tracking],
                        ['Condition', this.manualIntake() ? this.condition : 'Good (new delivery)'],
                        ['Can be borrowed', document.getElementById('add_equipment_borrowable')?.checked ? 'Yes' : 'No'],
                    ];
                    if (this.needsItemStep()) {
                        rows.push(['Asset tags / serials', this.items.length + ' units (Units step)']);
                    } else {
                        rows.push(['Asset tag', this.assetTag || '—'], ['Serial number', this.serial || '—']);
                    }
                    return rows;
                },
                reviewSource() {
                    if (this.isMultiRr()) {
                        const lines = this.selectedRrLines;
                        const unique = (values) => [...new Set(values.filter(Boolean))].join(', ');
                        return [
                            ['Acquired via', 'Procurement (Receiving Report)'],
                            ['Receiving reports', unique(lines.map((line) => line.rr_number)) || '—'],
                            ['Purchase orders', unique(lines.map((line) => line.po_number)) || 'Not linked'],
                            ['Suppliers', unique(lines.map((line) => line.supplier_name)) || '—'],
                            ['RR lines', String(lines.length)],
                        ];
                    }
                    if (!this.manualIntake()) {
                        const line = this.selectedRrLine;
                        return [
                            ['Acquired via', 'Procurement (Receiving Report)'],
                            ['Receiving report', line?.rr_number || '—'],
                            ['Purchase order', line?.po_number || 'Not linked'],
                            ['Supplier', line?.supplier_name || '—'],
                        ];
                    }
                    const supplier = this.acqSupplier === @json(\App\Support\EquipmentAcquisition::OTHER_SUPPLIER)
                        ? this.acqSupplierName
                        : this.optionText('add_acquisition_supplier');
                    return [
                        ['Acquired via', this.optionText('add_acquisition_source') || '—'],
                        [this.acqSource === 'donation' ? 'Donor' : 'Supplier / source', supplier || '—'],
                        ['Reference no.', this.acqReference || '—'],
                        ['Notes', this.intakeReason || '—'],
                    ];
                },
                reviewPurchase() {
                    if (this.isMultiManual()) {
                        return [
                            [this.acqSource === 'donation' ? 'Donation date' : 'Purchase date', this.purchaseDate || '—'],
                            [this.acqSource === 'donation' ? 'Estimated value / unit' : 'Cost / unit', 'Set per item'],
                            ['Received on', this.receivedDate || '—'],
                            ['Warranty expiration', this.multiWarrantySummary()],
                            ['Useful lifespan', this.multiLifespanSummary()],
                        ];
                    }
                    if (this.isMulti()) {
                        return [
                            ['Purchase date / cost', 'From each RR line'],
                            ['Received on', 'Today (date added)'],
                            ['Warranty expiration', this.multiWarrantySummary()],
                            ['Useful lifespan', this.multiLifespanSummary()],
                        ];
                    }
                    return [
                        [this.acqSource === 'donation' ? 'Donation date' : 'Purchase date', this.purchaseDate || '—'],
                        [this.acqSource === 'donation' ? 'Estimated value / unit' : 'Cost / unit', this.formatPeso(this.purchaseCost)],
                        ['Received on', this.manualIntake() ? (this.receivedDate || '—') : 'Today (date added)'],
                        ['Warranty expiration', this.warranty || '—'],
                        ['Useful lifespan', this.usefulLifeYears ? this.usefulLifeYears + ' years' : 'Default 5 years'],
                    ];
                },
                reviewPlacement() {
                    const rows = [[isStockPage ? 'Storage room' : 'Room', this.optionText('add_equipment_room') || '—']];
                    if (isStockPage) {
                        rows.push(['Deployed to', this.deployedToSummary()]);
                        if (this.isMulti()) {
                            rows.push(['Accountable person', this.multiCustodianSummary()]);
                            return rows;
                        }
                        rows.push(['Accountable person', this.canAssignPerson() ? (this.optionText('add_custodian') || 'No one yet') : 'No one yet']);
                        rows.push(['Replaces', this.optionText('add_replaces') || 'Not a replacement']);
                    }
                    return rows;
                },
                deployedToSummary() {
                    if (!this.deploying()) return 'Stays in storage';
                    const units = this.isMulti() ? this.multiUnits() : (this.needsItemStep() ? this.items : []);
                    if (!units.length) return this.optionText('add_deploy_room') || '—';
                    const counts = {};
                    units.forEach((unit) => {
                        const name = this.deployRoomName(unit.deploy_room_id || this.deployRoom) || '—';
                        counts[name] = (counts[name] || 0) + 1;
                    });
                    const names = Object.keys(counts);
                    return names.length === 1
                        ? names[0]
                        : names.map((name) => name + ' (' + counts[name] + ')').join(', ');
                },
                shouldAutoAssetTag() {
                    return this.tracking === 'Bulk' || Number(this.quantity) === 1;
                },
                syncAssetTag() {
                    if (this.assetTagManual || !this.shouldAutoAssetTag()) {
                        return;
                    }
                    const roomName = this.selectedRoomName();
                    const equipmentName = String(this.name || '').trim();
                    if (!roomName || !equipmentName || typeof window.equipmentAssetTags?.generate !== 'function') {
                        this.assetTag = '';
                        return;
                    }
                    window.equipmentAssetTags.resetReserved();
                    const tags = window.equipmentAssetTags.generate(roomName, equipmentName, 1);
                    this.assetTag = tags[0] || '';
                },
                buildItems() {
                    const qty = Math.min(200, Math.max(1, Number(this.quantity) || 1));
                    this.quantity = qty;
                    const previous = this.items || [];
                    const deploying = this.deploying();

                    this.items = Array.from({ length: qty }, (_, i) => {
                        const prev = previous[i] || {};
                        const roomManual = deploying && !!prev._roomManual;
                        return {
                            equipment_asset_tag: prev._tagManual ? prev.equipment_asset_tag : '',
                            equipment_serial_number: prev.equipment_serial_number ?? '',
                            equipment_brand_name: this.brand || '',
                            equipment_model: this.model || '',
                            equipment_condition_status: this.manualIntake() ? this.condition : 'Good',
                            equipment_warranty_expiration: this.warranty || '',
                            deploy_room_id: roomManual ? prev.deploy_room_id : (deploying ? String(this.deployRoom || '') : ''),
                            _roomManual: roomManual,
                            _tagManual: prev._tagManual || false,
                            _imagePreview: prev._imagePreview || null,
                            _imageFile: prev._imageFile || null,
                        };
                    });
                    this.fillItemTags(false);
                },
                regenerateAssetTags() {
                    this.fillItemTags(true);
                    this.$nextTick(() => this.restoreItemImageInputs());
                },
                applyDefaults() {
                    const deploying = this.deploying();
                    this.items = this.items.map((item) => ({
                        ...item,
                        equipment_brand_name: this.brand || '',
                        equipment_model: this.model || '',
                        equipment_condition_status: this.manualIntake() ? this.condition : 'Good',
                        equipment_warranty_expiration: this.warranty || '',
                        deploy_room_id: deploying ? String(this.deployRoom || '') : '',
                        _roomManual: false,
                    }));
                    this.fillItemTags(false);
                },
                goToItems() {
                    if (!this.validateStep1()) {
                        return;
                    }
                    if (this.isMulti()) {
                        this.buildMultiUnits();
                        this.step = 2;
                        this.clearErrors();
                        this.scrollToTop();
                        return;
                    }
                    this.clearImage();
                    this.buildItems();
                    this.step = 2;
                    this.clearErrors();
                    this.$nextTick(() => {
                        this.restoreItemImageInputs();
                        if (window.lucide) window.lucide.createIcons();
                    });
                    this.scrollToTop();
                },
                prepareSubmit(event) {
                    if (this.step !== 3) {
                        event.preventDefault();
                        this.next();
                        return;
                    }
                    if (this.isMulti()) {
                        event.preventDefault();
                        this.submitMulti();
                        return;
                    }
                    if (!this.validateStep1()) {
                        event.preventDefault();
                        return;
                    }
                    if (this.needsItemStep()) {
                        if (!this.validateItems()) {
                            event.preventDefault();
                            this.step = 2;
                            return;
                        }
                        this.restoreItemImageInputs();
                    } else {
                        this.syncAssetTag();
                    }
                },
                async submitMulti() {
                    if (this.submitting) return;
                    if (!this.validateStep1()) return;
                    if (this.multiLines.some((row) => !Array.isArray(row.units))) this.buildMultiUnits();
                    if (!this.validateMultiUnits()) {
                        this.step = 2;
                        return;
                    }
                    const form = this.isMultiManual() ? this.manualBatchForm() : this.rrLinesForm();
                    const url = this.isMultiManual() ? '/admin/inventory/batch-store' : '/admin/inventory/store-rr-lines';
                    const token = document.querySelector('#addEquipmentModal input[name="_token"]')?.value
                        || document.querySelector('meta[name="csrf-token"]')?.content
                        || '';
                    this.submitting = true;
                    this.formError = '';
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                            },
                            credentials: 'same-origin',
                            body: form,
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || data.success === false) {
                            const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                            this.formError = first || data.message || 'Could not add the equipment. Please try again.';
                            this.submitting = false;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return;
                        }
                        window.location.href = data.redirect || '/admin/inventory';
                    } catch (e) {
                        this.formError = 'Network error. Check your connection and try again.';
                        this.submitting = false;
                    }
                },
                manualBatchForm() {
                    const deploying = this.deploying();
                    const form = new FormData();
                    form.append('storage_room_id', String(this.room));
                    form.append('source[acquisition_source]', this.acqSource || '');
                    form.append('source[acquisition_supplier]', this.acqSupplier || '');
                    form.append('source[supplier_name]', this.acqSupplierName || '');
                    form.append('source[reference_number]', this.acqReference || '');
                    form.append('source[notes]', this.intakeReason || '');
                    if (this.purchaseDate) form.append('source[purchase_date]', this.purchaseDate);
                    if (this.receivedDate) form.append('source[received_date]', this.receivedDate);
                    this.multiLines.forEach((row, i) => {
                        const key = 'lines[' + i + ']';
                        form.append(key + '[name]', String(row.name || '').trim());
                        form.append(key + '[category_id]', String(row.category));
                        form.append(key + '[tracking]', row.tracking);
                        form.append(key + '[quantity]', String(Number(row.quantity)));
                        form.append(key + '[condition]', row.condition || 'Good');
                        form.append(key + '[brand]', String(row.brand || '').trim());
                        form.append(key + '[model]', String(row.model || '').trim());
                        if (row.cost !== '' && row.cost !== null && row.cost !== undefined) form.append(key + '[cost]', String(row.cost));
                        form.append(key + '[borrowable]', row.borrowable ? '1' : '0');
                        const warranty = row.warranty || this.warranty;
                        const life = row.useful_life_years || this.usefulLifeYears;
                        if (warranty) form.append(key + '[warranty]', warranty);
                        if (life) form.append(key + '[useful_life_years]', String(life));
                        row.units.forEach((unit, u) => {
                            const unitKey = key + '[units][' + u + ']';
                            form.append(unitKey + '[asset_tag]', String(unit.asset_tag || '').trim());
                            form.append(unitKey + '[serial]', String(unit.serial || '').trim());
                            if (this.hasPieces(row)) {
                                const values = this.pieceValues(row, u);
                                form.append(unitKey + '[brand]', values.brand);
                                form.append(unitKey + '[model]', values.model);
                            }
                            if (unit.warranty) form.append(unitKey + '[warranty]', unit.warranty);
                            if (deploying) form.append(unitKey + '[room_id]', String(unit.deploy_room_id || this.deployRoom || ''));
                            if (this.unitCanAssign(row) && unit.custodian_id) form.append(unitKey + '[custodian_id]', String(unit.custodian_id));
                            if (unit._imageFile) form.append(unitKey + '[image]', unit._imageFile);
                        });
                    });
                    return form;
                },
                rrLinesForm() {
                    const deploying = this.deploying();
                    const form = new FormData();
                    form.append('storage_room_id', String(this.room));
                    if (deploying && this.deployRoom) form.append('deploy_room_id', String(this.deployRoom));
                    if (this.warranty) form.append('warranty', this.warranty);
                    if (this.usefulLifeYears) form.append('useful_life_years', String(this.usefulLifeYears));
                    this.multiLines.forEach((row, i) => {
                        const key = 'lines[' + i + ']';
                        form.append(key + '[receiving_report_item_id]', String(row.id));
                        form.append(key + '[name]', String(row.name || '').trim());
                        form.append(key + '[category_id]', String(row.category));
                        form.append(key + '[tracking]', row.tracking);
                        form.append(key + '[quantity]', String(Number(row.quantity)));
                        form.append(key + '[brand]', String(row.brand || '').trim());
                        form.append(key + '[model]', String(row.model || '').trim());
                        form.append(key + '[borrowable]', row.borrowable ? '1' : '0');
                        if (row.warranty) form.append(key + '[warranty]', row.warranty);
                        if (row.useful_life_years) form.append(key + '[useful_life_years]', String(row.useful_life_years));
                        row.units.forEach((unit, u) => {
                            const unitKey = key + '[units][' + u + ']';
                            form.append(unitKey + '[asset_tag]', String(unit.asset_tag || '').trim());
                            form.append(unitKey + '[serial]', String(unit.serial || '').trim());
                            if (this.hasPieces(row)) {
                                const values = this.pieceValues(row, u);
                                form.append(unitKey + '[brand]', values.brand);
                                form.append(unitKey + '[model]', values.model);
                            }
                            if (unit.warranty) form.append(unitKey + '[warranty]', unit.warranty);
                            if (deploying) form.append(unitKey + '[deploy_room_id]', String(unit.deploy_room_id || this.deployRoom || ''));
                            if (this.unitCanAssign(row) && unit.custodian_id) form.append(unitKey + '[custodian_id]', String(unit.custodian_id));
                            if (unit._imageFile) form.append(unitKey + '[image]', unit._imageFile);
                        });
                    });
                    return form;
                },
            };
        }

        function openAddEquipmentModal(rrItemId = null) {
            const modal = document.getElementById('addEquipmentModal');
            if (modal && modal._x_dataStack && modal._x_dataStack[0]) {
                modal._x_dataStack[0].show(rrItemId);
                return;
            }
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        }

        function closeAddEquipmentModal() {
            const modal = document.getElementById('addEquipmentModal');
            if (modal && modal._x_dataStack && modal._x_dataStack[0]) {
                modal._x_dataStack[0].close();
                return;
            }
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }
    </script>
