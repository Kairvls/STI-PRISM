@php
    $wizardRooms = \App\Support\EquipmentAcquisition::intakeRooms();
    $storageRooms = $storageRooms ?? $wizardRooms['storage'];
    $deployRooms = $deployRooms ?? $wizardRooms['deploy'];
    $defaultStorageRoom = $storageRooms->first();
    $defaultStorageRoomId = $defaultStorageRoomId ?? optional($defaultStorageRoom)->room_id;
    $defaultStorageRoomName = optional($defaultStorageRoom)->room_name ?: 'Storage Room';
    $acquisitionReady = $acquisitionReady ?? \App\Support\EquipmentAcquisition::ready();
    $acquisitionSuppliers = $acquisitionSuppliers ?? \App\Support\EquipmentAcquisition::supplierOptions();
    $equipmentBrands = ($equipmentBrands ?? collect())->isNotEmpty() ? $equipmentBrands : \App\Support\EquipmentBrands::options();
    $equipmentModels = ($equipmentModels ?? collect())->isNotEmpty() ? $equipmentModels : \App\Support\EquipmentModels::options();
    $equipmentTypeOptions = ($equipmentTypeOptions ?? collect())->isNotEmpty()
        ? $equipmentTypeOptions
        : \App\Support\EquipmentCategoryTypes::options();
    $acquisitionPeople = ($acquisitionPeople ?? collect())->isNotEmpty() ? $acquisitionPeople : \App\Support\Custodians::assignable();
    $replacementCandidates = ($replacementCandidates ?? collect())->isNotEmpty() ? $replacementCandidates : \App\Support\EquipmentAcquisition::replacementCandidates();
    $eqField = $eqField ?? 'h-9 w-full rounded-lg border-0 bg-slate-50 px-3 text-xs text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $eqLabel = $eqLabel ?? 'mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500';
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
        @keydown.escape.window="if (document.getElementById('equipmentPhotoViewer')?.classList.contains('flex')) { return; } if (open) { if (discardPromptOpen) { discardPromptOpen = false; } else if (reviewOpen) { closeReview(); } else if (fullscreen) { fullscreen = false; } else { requestClose(); } }"
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
            novalidate
            @submit="prepareSubmit($event)"
            class="flex w-full flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
            :class="fullscreen
                ? 'h-[100dvh] max-h-[100dvh] max-w-none rounded-none border-0 shadow-none'
                : (step === 2
                    ? 'max-h-[85vh] w-[calc(93vw-1.5rem)] max-w-[calc(93vw-1.5rem)] rounded-2xl'
                    : 'max-h-[85vh] max-w-4xl rounded-2xl')"
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

            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-slate-900">{{ $wizardTitle }}</h2>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        @click="fullscreen = !fullscreen; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                        :title="fullscreen ? 'Exit full screen' : 'Full screen'"
                        :aria-label="fullscreen ? 'Exit full screen' : 'Full screen'"
                    >
                        <i :data-lucide="fullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                    </button>
                    <button type="button" @click="requestClose()" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <ol class="mx-5 mt-3 flex flex-wrap items-center gap-x-1 gap-y-1.5 text-xs">
                <template x-for="(wizardStep, index) in wizardSteps()" :key="wizardStep.key">
                    <li class="flex items-center gap-1">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold transition"
                            :class="index === currentStepIndex()
                                ? 'bg-[#0025cc] text-white'
                                : (index <= furthestStepIndex ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-white text-slate-400 ring-1 ring-slate-200')"
                            :disabled="index > furthestStepIndex || index === currentStepIndex()"
                            @click="goToStep(wizardStep.key)"
                        >
                            <span
                                class="inline-flex h-4 w-4 items-center justify-center rounded-full text-[10px]"
                                :class="index === currentStepIndex() ? 'bg-white/20' : (index <= furthestStepIndex ? 'bg-emerald-500 text-white' : 'bg-slate-100')"
                                x-text="index <= furthestStepIndex && index !== currentStepIndex() ? '✓' : (index + 1)"
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
                class="mx-5 mt-3 flex items-start gap-2.5 rounded-xl bg-rose-50 px-3.5 py-2.5 text-xs text-rose-700 ring-1 ring-rose-100"
            >
                <i data-lucide="circle-alert" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i>
                <p class="min-w-0 flex-1 leading-relaxed" x-text="formError"></p>
                <button type="button" @click="formError = ''" class="shrink-0 rounded-lg p-1 text-rose-400 transition hover:bg-rose-100 hover:text-rose-700" aria-label="Dismiss">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-y-auto px-5 py-4" x-show="step === 1">
                @if ($isStockPage)
                <div class="space-y-3" x-show="page === 1">
                <div class="space-y-3 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Procurement basis</p>
                        </div>
                        <div class="inline-flex rounded-lg bg-slate-200/70 p-0.5 text-[11px] font-semibold">
                            <button
                                type="button"
                                @click="setIntakeBasis('receiving')"
                                class="rounded-md px-2.5 py-1 transition"
                                :class="intakeBasis !== 'non_procurement' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >Delivered RR</button>
                            <button
                                type="button"
                                @click="setIntakeBasis('non_procurement')"
                                class="rounded-md px-2.5 py-1 transition"
                                :class="intakeBasis === 'non_procurement' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                            >Non-procurement</button>
                        </div>
                    </div>

                    <div x-show="intakeBasis !== 'non_procurement'" x-cloak class="space-y-3">
                        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Delivered lines (RR)</p>
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
                            <div class="overflow-y-auto" :class="fullscreen ? 'max-h-[calc(100dvh-22rem)] min-h-[16rem]' : 'max-h-[min(20rem,calc(85vh-24rem))] min-h-[12rem]'">
                                <template x-if="rrLoading">
                                    <p class="px-3 py-4 text-xs text-slate-400">Loading delivered lines…</p>
                                </template>
                                <template x-if="!rrLoading && rrGuide.length === 0">
                                    <p class="px-3 py-4 text-xs text-amber-700">No completed receiving lines yet. Finish second count on an RR first.</p>
                                </template>
                                <ul class="divide-y divide-slate-100" x-show="!rrLoading && rrGuide.length > 0">
                                    <template x-for="group in rrGroupsPending" :key="group.key">
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
                                                        <span class="ml-1 text-xs font-normal text-slate-500" x-text="group.lines.length + (group.lines.length === 1 ? ' equipment' : ' equipments')"></span>
                                                    </p>
                                                    <p class="mt-0.5 truncate text-[11px] text-slate-600" x-text="rrGroupMeta(group)"></p>
                                                    <p x-show="rrGroupPaperwork(group)" class="mt-0.5 truncate text-[11px] text-slate-600" x-text="rrGroupPaperwork(group)"></p>
                                                    <p
                                                        x-show="(group.backorder || 0) > 0"
                                                        x-cloak
                                                        class="mt-1 text-[11px] font-medium text-rose-700"
                                                        x-text="(group.backorder || 0) + ' still pending on back order (ordered but not yet received)'"
                                                    ></p>
                                                </div>
                                                <div class="flex shrink-0 flex-col items-end gap-1">
                                                    <span
                                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                        :class="group.remaining > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                                                        x-text="group.remaining > 0
                                                            ? (group.remaining + ' equipment quantity available')
                                                            : 'All equipment in this RR already added'"
                                                    ></span>
                                                    <span
                                                        x-show="(group.backorder || 0) > 0"
                                                        x-cloak
                                                        class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-800 ring-1 ring-rose-200/80"
                                                        x-text="(group.backorder || 0) + ' back order'"
                                                    ></span>
                                                </div>
                                            </button>
                                            </div>
                                            <ul x-show="isRrGroupOpen(group)" x-cloak class="border-t border-slate-100 bg-white">
                                                <template x-for="(line, lineIndex) in group.lines" :key="line.id">
                                                    <li class="relative border-b border-slate-100 last:border-b-0">
                                                        {{-- Tree connector: |-- under RR chevron --}}
                                                        <span class="pointer-events-none absolute left-[2.75rem] top-0 bottom-0 w-5" aria-hidden="true">
                                                            <span
                                                                class="absolute left-0 w-px bg-slate-300"
                                                                :class="lineIndex === group.lines.length - 1 ? 'top-0 h-1/2' : 'inset-y-0'"
                                                            ></span>
                                                            <span class="absolute left-0 top-1/2 h-px w-full -translate-y-px bg-slate-300"></span>
                                                        </span>
                                                        <label
                                                            class="flex w-full items-start gap-3 py-2 pl-14 pr-3 text-left transition"
                                                            :class="isRrSelected(line)
                                                                ? 'bg-[#0025cc]/5 ring-inset ring-1 ring-[#0025cc]/20'
                                                                : (line.is_selectable ? 'cursor-pointer hover:bg-slate-50' : 'bg-white')"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30 disabled:opacity-40"
                                                                :checked="isRrSelected(line)"
                                                                :disabled="!line.is_selectable"
                                                                @change="toggleRrLine(line)"
                                                                :aria-label="'Select ' + line.article"
                                                            >
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate text-sm font-medium text-slate-900" x-text="line.article"></p>
                                                                <p x-show="rrLineCost(line)" class="mt-0.5 truncate text-[13px] font-medium text-slate-700" x-text="rrLineCost(line)"></p>
                                                                <p class="mt-0.5 text-xs text-slate-600">
                                                                    <template x-if="line.ordered_qty != null">
                                                                        <span>Ordered <span x-text="line.ordered_qty"></span> · </span>
                                                                    </template>
                                                                    Received <span x-text="line.received_qty || 0"></span>
                                                                    · In inventory <span x-text="line.stocked_qty || 0"></span>
                                                                    · Left <span class="font-semibold" :class="(line.remaining_qty || 0) > 0 ? 'text-amber-700' : 'text-slate-600'" x-text="line.remaining_qty || 0"></span>
                                                                </p>
                                                                <p
                                                                    x-show="(line.backorder_qty || 0) > 0"
                                                                    x-cloak
                                                                    class="mt-0.5 text-[11px] font-medium text-rose-700"
                                                                    x-text="'Still pending on back order: ' + (line.backorder_qty || 0) + ' (this RR line only covers what was received)'"
                                                                ></p>
                                                            </div>
                                                            <div class="mt-0.5 flex shrink-0 flex-col items-end gap-1">
                                                                <span
                                                                    x-show="group.remaining > 0"
                                                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                                    :class="line.is_selectable ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                                                                    x-text="line.status_label || (line.is_selectable ? 'Ready' : 'Done')"
                                                                ></span>
                                                                <span
                                                                    x-show="(line.backorder_qty || 0) > 0"
                                                                    x-cloak
                                                                    class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-800 ring-1 ring-rose-200/80"
                                                                    x-text="(line.backorder_qty || 0) + ' back order'"
                                                                ></span>
                                                            </div>
                                                        </label>
                                                    </li>
                                                </template>
                                            </ul>
                                        </li>
                                    </template>
                                    <template x-if="rrGroupsCompleted.length > 0">
                                        <li>
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left transition hover:bg-slate-50"
                                                @click="showCompletedRr = !showCompletedRr; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                                :aria-expanded="showCompletedRr ? 'true' : 'false'"
                                            >
                                                <span class="flex min-w-0 items-center gap-2.5">
                                                    <span class="inline-flex h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="showCompletedRr ? 'rotate-90' : ''">
                                                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                                    </span>
                                                    <span class="min-w-0">
                                                        <span class="block text-sm font-semibold text-slate-800" x-text="showCompletedRr ? 'Hide previous completed RRs' : 'Show previous completed RRs'"></span>
                                                        <span class="mt-0.5 block text-[11px] text-slate-500" x-text="rrGroupsCompleted.length + ' receiving report' + (rrGroupsCompleted.length === 1 ? '' : 's') + ' already in inventory'"></span>
                                                    </span>
                                                </span>
                                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-700" x-text="rrGroupsCompleted.length + ' done'"></span>
                                            </button>
                                            <ul x-show="showCompletedRr" x-cloak class="divide-y divide-slate-100 border-t border-slate-100">
                                                <template x-for="group in rrGroupsCompleted" :key="'done-' + group.key">
                                                    <li>
                                                        <div class="flex items-center">
                                                            <label class="flex shrink-0 items-center self-stretch pl-3 opacity-60" title="Already in inventory">
                                                                <input
                                                                    type="checkbox"
                                                                    class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30 disabled:opacity-40"
                                                                    disabled
                                                                    :aria-label="'Already completed ' + group.rr_number"
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
                                                                        <span class="ml-1 text-xs font-normal text-slate-500" x-text="group.lines.length + (group.lines.length === 1 ? ' equipment' : ' equipments')"></span>
                                                                    </p>
                                                                    <p class="mt-0.5 truncate text-[11px] text-slate-600" x-text="rrGroupMeta(group)"></p>
                                                                    <p x-show="rrGroupPaperwork(group)" class="mt-0.5 truncate text-[11px] text-slate-600" x-text="rrGroupPaperwork(group)"></p>
                                                                    <p
                                                                        x-show="(group.backorder || 0) > 0"
                                                                        x-cloak
                                                                        class="mt-1 text-[11px] font-medium text-rose-700"
                                                                        x-text="(group.backorder || 0) + ' still pending on back order (ordered but not yet received)'"
                                                                    ></p>
                                                                </div>
                                                                <div class="flex shrink-0 flex-col items-end gap-1">
                                                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">All equipment in this RR already added</span>
                                                                    <span
                                                                        x-show="(group.backorder || 0) > 0"
                                                                        x-cloak
                                                                        class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-800 ring-1 ring-rose-200/80"
                                                                        x-text="(group.backorder || 0) + ' back order'"
                                                                    ></span>
                                                                </div>
                                                            </button>
                                                        </div>
                                                        <ul x-show="isRrGroupOpen(group)" x-cloak class="border-t border-slate-100 bg-white">
                                                            <template x-for="(line, lineIndex) in group.lines" :key="line.id">
                                                                <li class="relative border-b border-slate-100 last:border-b-0">
                                                                    <span class="pointer-events-none absolute left-[2.75rem] top-0 bottom-0 w-5" aria-hidden="true">
                                                                        <span
                                                                            class="absolute left-0 w-px bg-slate-300"
                                                                            :class="lineIndex === group.lines.length - 1 ? 'top-0 h-1/2' : 'inset-y-0'"
                                                                        ></span>
                                                                        <span class="absolute left-0 top-1/2 h-px w-full -translate-y-px bg-slate-300"></span>
                                                                    </span>
                                                                    <label class="flex w-full items-start gap-3 bg-white py-2 pl-14 pr-3 text-left">
                                                                        <input
                                                                            type="checkbox"
                                                                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30 disabled:opacity-40"
                                                                            disabled
                                                                            :aria-label="line.article"
                                                                        >
                                                                        <div class="min-w-0 flex-1">
                                                                            <p class="truncate text-sm font-medium text-slate-900" x-text="line.article"></p>
                                                                            <p x-show="rrLineCost(line)" class="mt-0.5 truncate text-[13px] font-medium text-slate-700" x-text="rrLineCost(line)"></p>
                                                                            <p class="mt-0.5 text-xs text-slate-600">
                                                                                <template x-if="line.ordered_qty != null">
                                                                                    <span>Ordered <span x-text="line.ordered_qty"></span> · </span>
                                                                                </template>
                                                                                Received <span x-text="line.received_qty || 0"></span>
                                                                                · In inventory <span x-text="line.stocked_qty || 0"></span>
                                                                                · Left <span class="font-semibold text-slate-600" x-text="line.remaining_qty || 0"></span>
                                                                            </p>
                                                                            <p
                                                                                x-show="(line.backorder_qty || 0) > 0"
                                                                                x-cloak
                                                                                class="mt-0.5 text-[11px] font-medium text-rose-700"
                                                                                x-text="'Still pending on back order: ' + (line.backorder_qty || 0) + ' (this RR line only covers what was received)'"
                                                                            ></p>
                                                                        </div>
                                                                        <div class="mt-0.5 flex shrink-0 flex-col items-end gap-1">
                                                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700" x-text="line.status_label || 'Done'"></span>
                                                                            <span
                                                                                x-show="(line.backorder_qty || 0) > 0"
                                                                                x-cloak
                                                                                class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-800 ring-1 ring-rose-200/80"
                                                                                x-text="(line.backorder_qty || 0) + ' back order'"
                                                                            ></span>
                                                                        </div>
                                                                    </label>
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </li>
                                                </template>
                                            </ul>
                                        </li>
                                    </template>
                                </ul>
                            </div>
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
                                                <p class="truncate text-[11px] text-slate-500" x-text="[line.rr_number, line.supplier_name, line.purchase_date ? ('Bought ' + line.purchase_date) : null].filter(Boolean).join(' · ')"></p>
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
                            <div
                                x-show="rrSelected.length && intakeBasis !== 'non_procurement'"
                                x-cloak
                                class="mt-3 flex flex-col gap-2 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p class="text-xs leading-relaxed text-slate-700">
                                    Stocks from the RR into storage when classification is known — skips the other steps.
                                </p>
                                <button
                                    type="button"
                                    @click="expressStock()"
                                    :disabled="submitting || !rrSelected.length"
                                    class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                                >
                                    <i data-lucide="zap" class="h-4 w-4"></i>
                                    <span x-text="submitting ? 'Stocking…' : ('Express stock' + (rrSelected.length > 1 ? (' (' + rrSelected.length + ')') : ''))"></span>
                                </button>
                            </div>
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
                                    maxlength="{{ \App\Support\InputLimits::NAME }}"
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
                                    maxlength="{{ \App\Support\InputLimits::DOC_REF_NO }}"
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
                                maxlength="{{ \App\Support\InputLimits::PURPOSE }}"
                                placeholder="e.g. Donated by Batch 2019 alumni, inventory migration from old spreadsheet"
                                class="{{ $eqField }}"
                                :class="errors.intake_reason ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            />
                            <p x-show="errors.intake_reason" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.intake_reason"></p>
                        </div>
                    </div>
                </div>

                <div
                    x-show="intakeBasis !== 'non_procurement' && !rrLoading && rrLines.length === 0"
                    x-cloak
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white px-3.5 py-2.5 text-xs text-slate-600 ring-1"
                    :class="errors.rr ? 'ring-2 ring-rose-400' : 'ring-slate-200/80'"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <i data-lucide="package-check" class="h-4 w-4 shrink-0 text-slate-400"></i>
                        <p x-text="rrGuide.length > 0
                            ? 'All delivered RR lines are already in inventory.'
                            : 'No completed Receiving Reports yet.'"></p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-8 shrink-0 items-center rounded-lg bg-white px-3 text-xs font-semibold text-[#0025cc] ring-1 ring-slate-200 transition hover:bg-slate-50"
                        @click="setIntakeBasis('non_procurement')"
                    >Use non-procurement intake</button>
                </div>
                </div>
                @endif
                <div x-show="page === 2" x-cloak>
                <div x-show="!isMulti()" class="space-y-3.5">
                {{-- Auto-filled procurement summary strip (RR / ATP / PO / Supplier / Date / Cost / Qty) --}}
                <div
                    x-show="!manualIntake() && selectedRrLine"
                    x-cloak
                    class="rounded-xl border border-blue-100 bg-blue-50/50 px-3.5 py-2.5"
                >
                    <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#0025cc]">
                            <i data-lucide="file-check-2" class="h-3.5 w-3.5"></i>
                            Fetched from Procurement Documents
                        </span>
                        <span class="text-[11px] text-slate-500" x-text="selectedRrLine?.rr_number || ''"></span>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs sm:grid-cols-3 lg:grid-cols-6">
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">RR #</dt>
                            <dd class="truncate font-mono text-[11px] font-semibold text-slate-900" x-text="selectedRrLine?.rr_number || '—'"></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">ATP / PO #</dt>
                            <dd class="truncate font-mono text-[11px] font-semibold text-slate-900" x-text="[selectedRrLine?.atp_number, selectedRrLine?.po_number].filter(Boolean).join(' · ') || '—'"></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Supplier</dt>
                            <dd class="truncate text-[11px] font-semibold text-slate-900" x-text="selectedRrLine?.supplier_name || '—'"></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Date</dt>
                            <dd class="truncate text-[11px] font-semibold text-slate-900" x-text="purchaseDate || selectedRrLine?.rr_date || '—'"></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Unit Cost</dt>
                            <dd class="truncate text-[11px] font-semibold text-slate-900" x-text="formatPeso(purchaseCost)"></dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Available Qty</dt>
                            <dd class="truncate text-[11px] font-semibold text-[#0025cc]" x-text="quantity + ' pcs'"></dd>
                        </div>
                    </dl>
                </div>

                <div
                    class="rounded-xl bg-slate-50/80 px-3.5 py-2.5 ring-1 ring-slate-200/80"
                    x-show="!needsItemStep()"
                    x-cloak
                >
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            x-show="imagePreview"
                            x-cloak
                            @click="openEquipmentPhotoViewer(imagePreview, name || 'Equipment photo')"
                            class="group relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-slate-200/80"
                            aria-label="View equipment photo fullscreen"
                        >
                            <img :src="imagePreview" alt="Equipment photo preview" class="h-full w-full object-cover">
                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                <i data-lucide="expand" class="h-3.5 w-3.5 text-white opacity-0 transition group-hover:opacity-100"></i>
                            </span>
                        </button>
                        <div
                            x-show="!imagePreview"
                            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-slate-200/80"
                        >
                            <span
                                class="inline-flex h-6 w-6 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(name || '') : ''"
                            ></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-slate-800">Equipment photo</p>
                                    <p x-show="errors.image" x-cloak class="mt-0.5 text-xs font-medium text-rose-600" x-text="errors.image"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="inline-flex h-8 cursor-pointer items-center rounded-lg bg-white px-2.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-50">
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
                                        class="inline-flex h-8 items-center rounded-lg px-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                    <div class="space-y-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Identity &amp; specifications</p>
                        <div>
                            <label for="add_equipment_name" class="{{ $eqLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                            <div
                                x-show="!manualIntake()"
                                x-cloak
                                class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                title="The equipment name comes from the Receiving Report line."
                            >
                                <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                    <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                    <span class="truncate" x-text="name || '—'"></span>
                                </span>
                                <span class="shrink-0 text-[10px] font-medium text-slate-400">From RR</span>
                            </div>
                            <input type="hidden" name="equipment_name" :value="name" :disabled="manualIntake()">
                            <select
                                id="add_equipment_name"
                                x-show="manualIntake()"
                                x-cloak
                                :name="manualIntake() ? 'equipment_name' : null"
                                :disabled="!manualIntake()"
                                x-model="name"
                                data-searchable="1"
                                data-search-placeholder="Search equipment…"
                                data-add-url="{{ url('/admin/inventory/equipment-types/quick-add') }}"
                                data-add-label="Add new equipment"
                                data-add-placeholder="New equipment name"
                                data-add-requires-category="1"
                                data-link-category-select="add_equipment_category"
                                @change="clearError('name'); onNameInput(); syncAssetTag()"
                                class="{{ $eqField }}"
                                :class="errors.name ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            >
                                <option value="">Select equipment</option>
                                @foreach ($equipmentTypeOptions->groupBy('equipment_category_name') as $groupLabel => $typeRows)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($typeRows as $typeRow)
                                            <option
                                                value="{{ $typeRow->type_name }}"
                                                data-category-id="{{ $typeRow->equipment_category_id }}"
                                            >{{ $typeRow->type_name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p x-show="errors.name" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label for="add_equipment_category" class="{{ $eqLabel }}">Classification <span class="text-rose-500">*</span></label>
                            <select
                                id="add_equipment_category"
                                name="equipment_category_id"
                                x-model="category"
                                data-searchable="1"
                                data-search-placeholder="Search classifications…"
                                data-add-url="{{ url('/admin/inventory/classifications/quick-add') }}"
                                data-add-label="Add new classification"
                                data-add-placeholder="New classification name"
                                @change="clearError('category'); onCategoryChange()"
                                class="{{ $eqField }}"
                                :class="errors.category ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                            >
                                <option value="">Select classification</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.category" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.category"></p>
                            <!--<p x-show="!errors.category" class="mt-1.5 text-xs text-slate-400">Filled from the equipment name list. You can still choose another category.</p>-->
                        </div>
                        <div class="grid grid-cols-2 gap-3" x-show="!needsItemStep()" x-cloak>
                            <div>
                                <label for="add_equipment_brand" class="{{ $eqLabel }}">Brand name <span class="text-red-500">*</span></label>
                                <div
                                    x-show="!manualIntake() && _brandFromRis"
                                    x-cloak
                                    class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                    title="The brand was recorded on the RIS for this equipment."
                                >
                                    <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                        <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                        <span class="truncate" x-text="brand || '—'"></span>
                                    </span>
                                    <span class="shrink-0 text-[10px] font-medium text-slate-400">From RIS</span>
                                </div>
                                <input type="hidden" name="equipment_brand_name" :value="brand" :disabled="manualIntake() || !_brandFromRis">
                                <select
                                    id="add_equipment_brand"
                                    x-show="manualIntake() || !_brandFromRis"
                                    x-cloak
                                    :name="(manualIntake() || !_brandFromRis) ? 'equipment_brand_name' : null"
                                    :disabled="!manualIntake() && _brandFromRis"
                                    x-model="brand"
                                    data-searchable="1"
                                    data-search-placeholder="Search brands…"
                                    data-add-url="{{ url('/admin/inventory/brands/quick-add') }}"
                                    data-add-label="Add new brand"
                                    data-add-placeholder="New brand name"
                                    @change="clearError('brand'); syncCatalogOption($el, brand)"
                                    :class="errors.brand ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    class="{{ $eqField }}"
                                >
                                    <option value="">Select brand</option>
                                    @foreach ($equipmentBrands as $brandOption)
                                        <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.brand" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.brand"></p>
                            </div>
                            <div>
                                <label for="add_equipment_model" class="{{ $eqLabel }}">Model <span class="text-red-500">*</span></label>
                                <select
                                    id="add_equipment_model"
                                    name="equipment_model"
                                    x-model="model"
                                    data-searchable="1"
                                    data-search-placeholder="Search models…"
                                    data-add-url="{{ url('/admin/inventory/models/quick-add') }}"
                                    data-add-label="Add new model"
                                    data-add-placeholder="New model name"
                                    @change="clearError('model'); syncCatalogOption($el, model)"
                                    :class="errors.model ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    class="{{ $eqField }}"
                                >
                                    <option value="">Select model</option>
                                    @foreach ($equipmentModels as $modelOption)
                                        <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.model" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.model"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3" x-show="!needsItemStep()" x-cloak>
                            <div class="relative group">
                                <label for="add_equipment_asset_tag" class="{{ $eqLabel }}">Asset tag <span class="text-red-500">*</span></label>
                                <input
                                    id="add_equipment_asset_tag"
                                    type="text"
                                    name="equipment_asset_tag"
                                    x-model="assetTag"
                                    maxlength="{{ \App\Support\InputLimits::ASSET_TAG }}"
                                    @input="assetTagManual = true; clearError('asset_tag')"
                                    :class="errors.asset_tag ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    class="{{ $eqField }} font-mono"
                                />
                                <div
                                    x-show="String(assetTag || '').trim() !== ''"
                                    x-cloak
                                    class="pointer-events-none invisible absolute bottom-full left-0 z-30 mb-1.5 max-w-[min(24rem,80vw)] whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 font-mono text-[11px] font-medium text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:visible group-hover:opacity-100"
                                >
                                    <span x-text="assetTag"></span>
                                </div>
                                <p x-show="errors.asset_tag" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.asset_tag"></p>
                            </div>
                            <div>
                                <label for="add_equipment_serial" class="{{ $eqLabel }}">Serial number <span class="text-red-500">*</span></label>
                                <input
                                    id="add_equipment_serial"
                                    type="text"
                                    name="equipment_serial_number"
                                    x-model="serial"
                                    maxlength="{{ \App\Support\InputLimits::SERIAL }}"
                                    placeholder="e.g. SN-ABC/12+34"
                                    @input="serial = String(serial || '').slice(0, {{ \App\Support\InputLimits::SERIAL }}); clearError('serial')"
                                    :class="errors.serial ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    class="{{ $eqField }}"
                                />
                                <p x-show="errors.serial" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="errors.serial"></p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Quantity, condition &amp; tracking</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="add_equipment_quantity" class="{{ $eqLabel }}">Qty <span class="text-red-500">*</span></label>
                                <div
                                    x-show="!manualIntake()"
                                    x-cloak
                                    class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                    title="Quantity is fetched from the selected Receiving Report line."
                                >
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-900">
                                        <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                        <span x-text="quantity"></span>
                                    </span>
                                    <span class="shrink-0 text-[10px] font-medium text-slate-400">From RR</span>
                                </div>
                                <input
                                    id="add_equipment_quantity"
                                    type="number"
                                    x-show="manualIntake()"
                                    x-cloak
                                    :disabled="!manualIntake()"
                                    data-qty
                                    min="1"
                                    :max="maxAddQty()"
                                    x-model.number="quantity"
                                    @input="onQuantityInput()"
                                    class="{{ $eqField }}"
                                    :class="errors.quantity ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                />
                                <p x-show="errors.quantity" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.quantity"></p>
                            </div>
                            <div>
                                <label for="add_equipment_condition" class="{{ $eqLabel }}">Condition</label>
                                <div
                                    x-show="!manualIntake()"
                                    class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                    title="New deliveries from a Receiving Report are always recorded as Good."
                                >
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-slate-900">
                                        <i data-lucide="lock" class="h-3.5 w-3.5 text-slate-400"></i>
                                        Good
                                    </span>
                                    <span class="text-[10px] font-medium text-slate-400">New delivery</span>
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
                                    <option value="Under Maintenance">Under Maintenance</option>
                                    <option value="Defective">Defective</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $eqLabel }}">Tracking mode</label>
                            <div class="flex h-9 rounded-lg bg-slate-100 p-1">
                                <button
                                    type="button"
                                    @click="if (categoryLocksIndividual()) return; tracking = 'Bulk'; assetTagManual = false; syncAssetTag(); onSharedPhotoModeChange()"
                                    class="flex-1 rounded-md text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-40"
                                    :class="tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                    :disabled="categoryLocksIndividual()"
                                >Bulk</button>
                                <button type="button" @click="tracking = 'Individual'; assetTagManual = false; syncAssetTag(); onSharedPhotoModeChange(); if (needsItemStep()) buildItems()" class="flex-1 rounded-md text-xs font-semibold transition" :class="tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PER-UNIT HORIZONTAL CAROUSEL (Single RR / Manual item with Qty > 1 Individual) --}}
                <div x-show="needsItemStep()" x-cloak class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                            <template x-for="(item, uIdx) in items" :key="'unit-pill-' + uIdx">
                                <button
                                    type="button"
                                    @click="goToUnitSlide(uIdx)"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition"
                                    :class="activeUnitIndex === uIdx
                                        ? 'bg-[#0025cc] text-white shadow-sm'
                                        : (isUnitComplete(item)
                                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80 hover:bg-emerald-100'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200')"
                                >
                                    <span
                                        class="inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold"
                                        :class="activeUnitIndex === uIdx
                                            ? 'bg-white/20 text-white'
                                            : (isUnitComplete(item) ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600')"
                                        x-text="isUnitComplete(item) ? '✓' : (uIdx + 1)"
                                    ></span>
                                    <span x-text="'Unit ' + (uIdx + 1)"></span>
                                </button>
                            </template>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="copyActiveUnitToAll()"
                                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-[#0025cc] transition hover:bg-blue-50"
                                title="Copy Model, Warranty, Lifespan, and Placement from this unit to all units"
                            >
                                <i data-lucide="copy" class="h-3 w-3"></i>
                                <span>Copy to all</span>
                            </button>
                            <span class="text-[11px] font-semibold text-slate-500" x-text="'Unit ' + (activeUnitIndex + 1) + ' of ' + items.length"></span>
                            <button
                                type="button"
                                @click="prevUnitSlide()"
                                :disabled="activeUnitIndex <= 0"
                                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Previous unit"
                            >
                                <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                                <span>Prev</span>
                            </button>
                            <button
                                type="button"
                                @click="nextUnitSlide()"
                                :disabled="activeUnitIndex >= items.length - 1"
                                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Next unit"
                            >
                                <span>Next</span>
                                <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <template x-for="(item, index) in items" :key="'unit-slide-' + index">
                        <div
                            x-show="activeUnitIndex === index"
                            class="rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80"
                        >
                            <input type="hidden" :name="'items[' + index + '][equipment_purchase_date]'" :value="item.equipment_purchase_date || purchaseDate || ''">
                            <input type="hidden" :name="'items[' + index + '][equipment_purchase_cost]'" :value="item.equipment_purchase_cost !== '' && item.equipment_purchase_cost != null ? item.equipment_purchase_cost : (purchaseCost ?? '')">
                            <input type="hidden" :name="'items[' + index + '][equipment_condition_status]'" :value="manualIntake() ? (item.equipment_condition_status || condition || 'Good') : 'Good'">

                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500" x-text="'Unit ' + (index + 1) + ' of ' + items.length"></p>
                                    <span class="text-slate-300">·</span>
                                    <span class="text-xs font-semibold text-slate-900" x-text="name || 'Equipment'"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        x-show="item._imagePreview"
                                        x-cloak
                                        @click="openEquipmentPhotoViewer(item._imagePreview, (item.equipment_asset_tag || name || 'Equipment') + ' photo')"
                                        class="h-7 w-7 overflow-hidden rounded-md bg-white ring-1 ring-slate-200"
                                    >
                                        <img :src="item._imagePreview" alt="" class="h-full w-full object-cover">
                                    </button>
                                    <label class="inline-flex h-7 cursor-pointer items-center gap-1 rounded-lg bg-white px-2 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                        <i data-lucide="image" class="h-3 w-3 text-slate-400"></i>
                                        <span x-text="item._imagePreview ? 'Change photo' : 'Photo'"></span>
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
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                                    >×</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                                {{-- Left: Unit Identity --}}
                                <div class="space-y-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="{{ $eqLabel }}">Brand name <span class="text-rose-500">*</span></label>
                                            <div
                                                x-show="!manualIntake() && _brandFromRis"
                                                x-cloak
                                                class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                            >
                                                <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                                    <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                    <span class="truncate" x-text="item.equipment_brand_name || brand || '—'"></span>
                                                </span>
                                                <span class="shrink-0 text-[10px] font-medium text-slate-400">From RIS</span>
                                            </div>
                                            <input
                                                type="hidden"
                                                :name="'items[' + index + '][equipment_brand_name]'"
                                                :value="item.equipment_brand_name || brand"
                                                :disabled="manualIntake() || !_brandFromRis"
                                            >
                                            <select
                                                x-show="manualIntake() || !_brandFromRis"
                                                x-cloak
                                                :name="(manualIntake() || !_brandFromRis) ? ('items[' + index + '][equipment_brand_name]') : null"
                                                :disabled="!manualIntake() && _brandFromRis"
                                                :id="'carousel_unit_brand_' + index"
                                                x-model="item.equipment_brand_name"
                                                data-searchable="1"
                                                data-search-placeholder="Search brands…"
                                                data-add-url="{{ url('/admin/inventory/brands/quick-add') }}"
                                                data-add-label="Add new brand"
                                                data-add-placeholder="New brand name"
                                                @change="item._brandManual = true; syncCatalogOption($el, item.equipment_brand_name)"
                                                class="{{ $eqField }}"
                                            >
                                                <option value="">Select brand</option>
                                                @foreach ($equipmentBrands as $brandOption)
                                                    <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="{{ $eqLabel }}" :for="'carousel_unit_model_' + index">Model <span class="text-rose-500">*</span></label>
                                            <select
                                                :id="'carousel_unit_model_' + index"
                                                :name="'items[' + index + '][equipment_model]'"
                                                x-model="item.equipment_model"
                                                data-searchable="1"
                                                data-search-placeholder="Search models…"
                                                data-add-url="{{ url('/admin/inventory/models/quick-add') }}"
                                                data-add-label="Add new model"
                                                data-add-placeholder="New model name"
                                                @change="syncCatalogOption($el, item.equipment_model)"
                                                class="{{ $eqField }}"
                                            >
                                                <option value="">Select model</option>
                                                @foreach ($equipmentModels as $modelOption)
                                                    <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="group relative">
                                            <label class="{{ $eqLabel }}" :for="'carousel_unit_tag_' + index">Asset tag <span class="text-rose-500">*</span></label>
                                            <input
                                                :id="'carousel_unit_tag_' + index"
                                                type="text"
                                                :name="'items[' + index + '][equipment_asset_tag]'"
                                                x-model="item.equipment_asset_tag"
                                                maxlength="{{ \App\Support\InputLimits::ASSET_TAG }}"
                                                @input="item._tagManual = true"
                                                class="{{ $eqField }} font-mono"
                                            />
                                            <div
                                                x-show="String(item.equipment_asset_tag || '').trim() !== ''"
                                                x-cloak
                                                class="pointer-events-none invisible absolute bottom-full left-0 z-30 mb-1.5 max-w-[min(24rem,80vw)] whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 font-mono text-[11px] font-medium text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:visible group-hover:opacity-100"
                                            >
                                                <span x-text="item.equipment_asset_tag"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="{{ $eqLabel }}" :for="'carousel_unit_serial_' + index">Serial number <span class="text-rose-500">*</span></label>
                                            <input
                                                :id="'carousel_unit_serial_' + index"
                                                type="text"
                                                :name="'items[' + index + '][equipment_serial_number]'"
                                                x-model="item.equipment_serial_number"
                                                maxlength="{{ \App\Support\InputLimits::SERIAL }}"
                                                placeholder="Enter serial number"
                                                @input="item.equipment_serial_number = String(item.equipment_serial_number || '').slice(0, {{ \App\Support\InputLimits::SERIAL }})"
                                                class="{{ $eqField }}"
                                            />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 items-end gap-3">
                                        <div>
                                            <label class="{{ $eqLabel }} whitespace-nowrap" :for="'carousel_unit_warranty_' + index">Warranty expiration <span class="text-rose-500">*</span></label>
                                            <input
                                                :id="'carousel_unit_warranty_' + index"
                                                type="date"
                                                :name="'items[' + index + '][equipment_warranty_expiration]'"
                                                x-model="item.equipment_warranty_expiration"
                                                @input="item._warrantyManual = true"
                                                class="{{ $eqField }}"
                                            />
                                        </div>
                                        <div>
                                            <label class="{{ $eqLabel }} whitespace-nowrap" :for="'carousel_unit_life_' + index">Lifespan (years) <span class="text-rose-500">*</span></label>
                                            <input
                                                :id="'carousel_unit_life_' + index"
                                                type="number"
                                                min="1"
                                                max="{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                                step="1"
                                                :name="'items[' + index + '][equipment_useful_life_years]'"
                                                x-model="item.equipment_useful_life_years"
                                                @input="item._lifeManual = true; item.equipment_useful_life_years = clampUsefulLife(item.equipment_useful_life_years)"
                                                placeholder="5"
                                                class="{{ $eqField }}"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {{-- Right: Unit Placement, Accountability & Replacement --}}
                                @if ($isStockPage)
                                    <div class="space-y-3">
                                        <div>
                                            <label class="{{ $eqLabel }}">Placement</label>
                                            <div class="flex h-9 rounded-lg bg-slate-100 p-1">
                                                <button
                                                    type="button"
                                                    @click="setItemPlacement(item, 'storage')"
                                                    class="flex-1 rounded-md text-xs font-semibold transition"
                                                    :class="item.placement !== 'deploy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                >Keep in storage</button>
                                                <button
                                                    type="button"
                                                    @click="setItemPlacement(item, 'deploy')"
                                                    class="flex-1 rounded-md text-xs font-semibold transition"
                                                    :class="item.placement === 'deploy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                >Deploy to a room now</button>
                                            </div>
                                        </div>

                                        <div x-show="item.placement !== 'deploy'">
                                            <label class="{{ $eqLabel }}">Storage room</label>
                                            <div class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80">
                                                <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                                    <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                    <span class="truncate" x-text="defaultStorageRoomName"></span>
                                                </span>
                                                <span class="shrink-0 text-[10px] font-medium text-slate-400">Auto</span>
                                            </div>
                                        </div>

                                        <div x-show="item.placement === 'deploy'" x-cloak>
                                            <label class="{{ $eqLabel }}" :for="'carousel_unit_deploy_room_' + index">Deploy to <span class="text-rose-500">*</span></label>
                                            <select
                                                :id="'carousel_unit_deploy_room_' + index"
                                                :name="item.placement === 'deploy' ? ('items[' + index + '][deploy_room_id]') : null"
                                                :disabled="item.placement !== 'deploy'"
                                                x-model="item.deploy_room_id"
                                                @change="onItemRoomChange(item); syncItemReplacementSelection(item)"
                                                data-searchable="1"
                                                data-search-placeholder="Search rooms…"
                                                class="{{ $eqField }}"
                                            >
                                                <option value="">Select room</option>
                                                @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                                    <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="{{ $eqLabel }}" :for="'carousel_unit_custodian_' + index">Accountable person</label>
                                                <select
                                                    :id="'carousel_unit_custodian_' + index"
                                                    :name="item.placement === 'deploy' ? ('items[' + index + '][custodian_id]') : null"
                                                    :disabled="item.placement !== 'deploy'"
                                                    x-model="item.custodian_id"
                                                    data-searchable="1"
                                                    data-search-placeholder="Search people…"
                                                    class="{{ $eqField }} disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    <option value="">No one yet</option>
                                                    @foreach ($acquisitionPeople as $person)
                                                        <option value="{{ $person->custodian_id }}">{{ $person->custodian_full_name }}{{ $person->custodian_department ? ' · '.$person->custodian_department : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div x-effect="syncItemReplacementSelection(item)">
                                                <label class="{{ $eqLabel }}" :for="'carousel_unit_replaces_' + index">Replaces equipment</label>
                                                <div
                                                    x-show="item.placement !== 'deploy' || itemReplacementCandidates(item).length === 0"
                                                    x-cloak
                                                    class="flex h-9 cursor-not-allowed items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 text-xs opacity-60 ring-1 ring-slate-200/80"
                                                >
                                                    <span class="inline-flex min-w-0 items-center gap-1.5 font-medium text-slate-500">
                                                        <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                        <span class="truncate">Not a replacement</span>
                                                    </span>
                                                    <span class="shrink-0 text-[10px] font-medium text-slate-400" x-text="item.placement !== 'deploy' ? 'In storage' : (!item.deploy_room_id ? 'Select room' : 'None found')"></span>
                                                </div>
                                                <select
                                                    :id="'carousel_unit_replaces_' + index"
                                                    x-show="item.placement === 'deploy' && itemReplacementCandidates(item).length > 0"
                                                    x-cloak
                                                    :name="(item.placement === 'deploy' && itemReplacementCandidates(item).length > 0) ? ('items[' + index + '][replaces_equipment_id]') : null"
                                                    :disabled="item.placement !== 'deploy' || itemReplacementCandidates(item).length === 0"
                                                    x-model="item.replaces_equipment_id"
                                                    data-searchable="1"
                                                    data-search-placeholder="Search matching equipment…"
                                                    class="{{ $eqField }}"
                                                >
                                                    <option value="">Not a replacement</option>
                                                    <template x-for="candidate in itemReplacementCandidates(item)" :key="'unit-rep-' + index + '-' + candidate.equipment_id">
                                                        <option
                                                            :value="String(candidate.equipment_id)"
                                                            :selected="String(item.replaces_equipment_id || '') === String(candidate.equipment_id)"
                                                            x-text="[candidate.room_name, candidate.equipment_name, candidate.equipment_asset_tag].filter(Boolean).join(' · ')"
                                                        ></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </template>
                </div>
                @if ($isStockPage)
                    <button
                        type="button"
                        x-show="manualIntake()"
                        x-cloak
                        @click="startManualMulti()"
                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-[#0025cc] transition hover:border-[#0025cc]/50 hover:bg-[#0025cc]/5"
                    >
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Add another equipment
                    </button>
                @endif
                </div>

                <div x-show="isMulti()" x-cloak class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-900">
                                <i :data-lucide="isMultiRr() ? 'package-check' : 'layers'" class="h-3.5 w-3.5 text-slate-400"></i>
                                <span x-text="multiLines.length + ' items'"></span>
                            </span>
                            <template x-if="isMultiRr()">
                                <span class="text-slate-400">·</span>
                            </template>
                            <span x-show="isMultiRr()" class="text-slate-500" x-text="selectedRrCount + ' RR' + (selectedRrCount === 1 ? '' : 's')"></span>
                            <span x-show="isMultiRr()" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-200/70">
                                <i data-lucide="lock" class="h-2.5 w-2.5"></i>
                                Good · new
                            </span>
                        </div>
                        <button
                            type="button"
                            x-show="canExpressResume()"
                            x-cloak
                            @click="expressStock()"
                            :disabled="submitting"
                            class="inline-flex h-7 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-[#0025cc] px-2.5 text-[11px] font-semibold text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                        >
                            <i data-lucide="zap" class="h-3 w-3"></i>
                            <span x-text="submitting ? 'Stocking…' : 'Express stock'"></span>
                        </button>
                    </div>

                    {{-- Carousel navigation bar (clickable item pills + Previous / Next) --}}
                    <div
                        x-show="multiLines.length > 1"
                        x-cloak
                        class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2"
                    >
                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                            <template x-for="(row, idx) in multiLines" :key="'pill-' + row.id">
                                <button
                                    type="button"
                                    @click="goToMultiSlide(idx)"
                                    class="inline-flex max-w-[13rem] items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition"
                                    :class="activeMultiIndex === idx
                                        ? (multiRowHasError(row)
                                            ? 'bg-rose-600 text-white shadow-sm'
                                            : 'bg-[#0025cc] text-white shadow-sm')
                                        : (multiRowHasError(row)
                                            ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100'
                                            : (isMultiRowComplete(row)
                                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80 hover:bg-emerald-100'
                                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'))"
                                >
                                    <span
                                        class="inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold"
                                        :class="activeMultiIndex === idx
                                            ? 'bg-white/20 text-white'
                                            : (multiRowHasError(row)
                                                ? 'bg-rose-500 text-white'
                                                : (isMultiRowComplete(row)
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'bg-slate-200 text-slate-600'))"
                                        x-text="multiRowHasError(row) ? '!' : (isMultiRowComplete(row) ? '✓' : (idx + 1))"
                                    ></span>
                                    <span class="truncate" x-text="row.name || row.article || ('Item ' + (idx + 1))"></span>
                                    <span
                                        class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none"
                                        :class="activeMultiIndex === idx
                                            ? 'bg-white/20 text-white'
                                            : (multiRowHasError(row)
                                                ? 'bg-rose-200/70 text-rose-800'
                                                : (isMultiRowComplete(row)
                                                    ? 'bg-emerald-200/70 text-emerald-800'
                                                    : 'bg-slate-200/80 text-slate-600'))"
                                        x-text="'×' + (Number(row.quantity) || 1)"
                                    ></span>
                                </button>
                            </template>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <span class="text-[11px] font-semibold text-slate-500" x-text="'Item ' + (activeMultiIndex + 1) + ' of ' + multiLines.length"></span>
                            <button
                                type="button"
                                @click="prevMultiSlide()"
                                :disabled="activeMultiIndex <= 0"
                                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Previous item"
                            >
                                <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                                <span>Prev</span>
                            </button>
                            <button
                                type="button"
                                @click="nextMultiSlide()"
                                :disabled="activeMultiIndex >= multiLines.length - 1"
                                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Next item"
                            >
                                <span>Next</span>
                                <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                            </button>
                        </div>
                    </div>

                    <template x-for="(row, index) in multiLines" :key="'multi-' + row.id">
                        <div
                            x-show="activeMultiIndex === index"
                            class="rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80"
                        >
                            <div class="mb-2.5 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="'Item ' + (index + 1) + ' of ' + multiLines.length"></p>
                                    <span x-show="row.source" class="text-slate-300">·</span>
                                    <p x-show="row.source" class="truncate text-[11px] text-slate-500" x-text="row.source"></p>
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
                                        <option value="Under Maintenance">Under Maintenance</option>
                                        <option value="Defective">Defective</option>
                                        <option value="Damaged">Damaged</option>
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
                                    <div
                                        x-show="isMultiRr()"
                                        x-cloak
                                        class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                        title="The equipment name comes from the Receiving Report line."
                                    >
                                        <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                            <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                            <span class="truncate" x-text="row.name || row.article || '—'"></span>
                                        </span>
                                        <span class="shrink-0 text-[10px] font-medium text-slate-400">From RR</span>
                                    </div>
                                    <select
                                        :id="'multi_name_' + row.id"
                                        x-show="isMultiManual()"
                                        x-cloak
                                        :disabled="!isMultiManual()"
                                        x-model="row.name"
                                        data-searchable="1"
                                        data-search-placeholder="Search equipment…"
                                        data-add-url="{{ url('/admin/inventory/equipment-types/quick-add') }}"
                                        data-add-label="Add new equipment"
                                        data-add-placeholder="New equipment name"
                                        data-add-requires-category="1"
                                        :data-link-category-select="'multi_category_' + row.id"
                                        @change="clearMultiError(row, 'name'); onMultiNameInput(row); syncCatalogOption($el, row.name)"
                                        class="{{ $eqField }}"
                                        :class="multiError(row, 'name') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    >
                                        <option value="">Select equipment</option>
                                        @foreach ($equipmentTypeOptions->groupBy('equipment_category_name') as $groupLabel => $typeRows)
                                            <optgroup label="{{ $groupLabel }}">
                                                @foreach ($typeRows as $typeRow)
                                                    <option
                                                        value="{{ $typeRow->type_name }}"
                                                        data-category-id="{{ $typeRow->equipment_category_id }}"
                                                    >{{ $typeRow->type_name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <p x-show="multiError(row, 'name')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'name')"></p>
                                </div>
                                <div>
                                    <label class="{{ $eqLabel }}" :for="'multi_category_' + row.id">Classification <span class="text-rose-500">*</span></label>
                                    <select
                                        :id="'multi_category_' + row.id"
                                        x-model="row.category"
                                        data-searchable="1"
                                        data-search-placeholder="Search classifications…"
                                        data-add-url="{{ url('/admin/inventory/classifications/quick-add') }}"
                                        data-add-label="Add new classification"
                                        data-add-placeholder="New classification name"
                                        @change="row.categoryManual = true; clearMultiError(row, 'category'); applyExpressDefaultsToRow(row, true); if (!rowAllowsBulk(row)) { row.tracking = 'Individual'; syncPieces(row); } $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                        class="{{ $eqField }}"
                                        :class="multiError(row, 'category') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    >
                                        <option value="">Select classification</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                        @endforeach
                                    </select>
                                    <p x-show="multiError(row, 'category')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'category')"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $eqLabel }}" :for="'multi_brand_' + row.id">
                                            <span x-text="hasPieces(row) ? 'Brand (all pcs)' : 'Brand name'"></span> <span class="text-red-500">*</span>
                                        </label>
                                        <div
                                            x-show="isMultiRr() && row._brandFromRis"
                                            x-cloak
                                            class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                            title="The brand was recorded on the RIS for this equipment."
                                        >
                                            <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                                <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                <span class="truncate" x-text="row.brand || '—'"></span>
                                            </span>
                                            <span class="shrink-0 text-[10px] font-medium text-slate-400">From RIS</span>
                                        </div>
                                        <select
                                            :id="'multi_brand_' + row.id"
                                            x-show="!isMultiRr() || !row._brandFromRis"
                                            x-cloak
                                            :disabled="isMultiRr() && !!row._brandFromRis"
                                            x-model="row.brand"
                                            data-searchable="1"
                                            data-search-placeholder="Search brands…"
                                            data-add-url="{{ url('/admin/inventory/brands/quick-add') }}"
                                            data-add-label="Add new brand"
                                            data-add-placeholder="New brand name"
                                            @change="clearMultiError(row, 'brand'); syncCatalogOption($el, row.brand)"
                                            class="{{ $eqField }}"
                                            :class="multiError(row, 'brand') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        >
                                            <option value="">Select brand</option>
                                            @foreach ($equipmentBrands as $brandOption)
                                                <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="multiError(row, 'brand')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'brand')"></p>
                                    </div>
                                    <div>
                                        <label class="{{ $eqLabel }}" :for="'multi_model_' + row.id">
                                            <span x-text="hasPieces(row) ? 'Model (all pcs)' : 'Model'"></span> <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            :id="'multi_model_' + row.id"
                                            x-model="row.model"
                                            data-searchable="1"
                                            data-search-placeholder="Search models…"
                                            data-add-url="{{ url('/admin/inventory/models/quick-add') }}"
                                            data-add-label="Add new model"
                                            data-add-placeholder="New model name"
                                            @change="clearMultiError(row, 'model'); syncCatalogOption($el, row.model)"
                                            class="{{ $eqField }}"
                                            :class="multiError(row, 'model') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        >
                                            <option value="">Select model</option>
                                            @foreach ($equipmentModels as $modelOption)
                                                <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                            @endforeach
                                        </select>
                                        <p x-show="multiError(row, 'model')" x-cloak class="mt-1.5 text-xs font-medium text-rose-600" x-text="multiError(row, 'model')"></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $eqLabel }}" :for="'multi_qty_' + row.id">Qty <span class="text-red-500">*</span></label>
                                        <div
                                            x-show="isMultiRr()"
                                            x-cloak
                                            class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80"
                                            title="Quantity is fetched from the selected Receiving Report line."
                                        >
                                            <span class="inline-flex items-center gap-1.5 font-semibold text-slate-900">
                                                <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                <span x-text="row.quantity"></span>
                                            </span>
                                            <span class="shrink-0 text-[10px] font-medium text-slate-400">From RR</span>
                                        </div>
                                        <input
                                            :id="'multi_qty_' + row.id"
                                            type="number"
                                            x-show="isMultiManual()"
                                            x-cloak
                                            :disabled="!isMultiManual()"
                                            data-qty
                                            min="1"
                                            :max="Math.min(row.max || {{ \App\Support\InputLimits::QTY }}, {{ \App\Support\InputLimits::QTY }})"
                                            x-model.number="row.quantity"
                                            @input="row.quantity = Number((window.PrismInputLimits && PrismInputLimits.clampQty(row.quantity)) || Math.min(Number(row.quantity) || 0, row.max || {{ \App\Support\InputLimits::QTY }})) || ''; clearMultiError(row, 'quantity'); syncPieces(row)"
                                            class="{{ $eqField }}"
                                            :class="multiError(row, 'quantity') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        />
                                        <p x-show="multiError(row, 'quantity')" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="multiError(row, 'quantity')"></p>
                                        <p x-show="!multiError(row, 'quantity') && isMultiManual()" class="mt-1 text-[11px] text-slate-400" x-text="'Up to ' + row.max"></p>
                                    </div>
                                    <div>
                                        <p class="{{ $eqLabel }}">Tracking</p>
                                        <div class="flex h-9 rounded-lg bg-slate-100 p-1">
                                            <button
                                                type="button"
                                                @click="if (!rowAllowsBulk(row)) return; row.tracking = 'Bulk'"
                                                class="flex-1 rounded-md text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-40"
                                                :class="row.tracking === 'Bulk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                :disabled="!rowAllowsBulk(row)"
                                            >Bulk</button>
                                            <button type="button" @click="row.tracking = 'Individual'; syncPieces(row)" class="flex-1 rounded-md text-xs font-semibold transition" :class="row.tracking === 'Individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Individual</button>
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
                                        <p class="text-[11px] text-slate-500" x-text="isMultiRr() && row._brandFromRis
                                            ? 'Brand is taken from the RIS. Set a model only on pieces that differ.'
                                            : (row.perPiece
                                                ? 'Fill only the pieces that differ. Blank fields use the brand and model above.'
                                                : ('All ' + (Number(row.quantity) || 0) + ' pcs use the brand and model above.'))"></p>
                                    </div>
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700">
                                        <input
                                            type="checkbox"
                                            x-model="row.perPiece"
                                            :disabled="isMultiRr() && row._brandFromRis && risBrandCount(row) > 1"
                                            @change="syncPieces(row)"
                                            class="h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30 disabled:cursor-not-allowed disabled:opacity-40"
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
                                                        :class="((isMultiRr() && row._brandFromRis) ? piece.model : (piece.brand || piece.model)) ? 'text-[#0025cc]' : 'text-slate-400'"
                                                        x-text="((isMultiRr() && row._brandFromRis) ? piece.model : (piece.brand || piece.model)) ? 'Custom' : 'Same as all'"
                                                    ></span>
                                                </div>
                                                <label class="sr-only" :for="'pc_brand_' + row.id + '_' + pieceIndex">Brand</label>
                                                <div
                                                    x-show="isMultiRr() && row._brandFromRis"
                                                    x-cloak
                                                    class="flex h-9 items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2 text-sm text-slate-700"
                                                    title="The brand was recorded on the RIS for this equipment."
                                                >
                                                    <i data-lucide="lock" class="h-3 w-3 shrink-0 text-slate-400"></i>
                                                    <span class="truncate" x-text="piece.brand || row.brand || '—'"></span>
                                                </div>
                                                <select
                                                    :id="'pc_brand_' + row.id + '_' + pieceIndex"
                                                    x-show="!(isMultiRr() && row._brandFromRis)"
                                                    x-cloak
                                                    :disabled="isMultiRr() && !!row._brandFromRis"
                                                    x-model="piece.brand"
                                                    data-searchable="1"
                                                    data-search-placeholder="Search brands…"
                                                    data-add-url="{{ url('/admin/inventory/brands/quick-add') }}"
                                                    data-add-label="Add new brand"
                                                    data-add-placeholder="New brand name"
                                                    @change="row._piecesManual = true; syncCatalogOption($el, piece.brand)"
                                                    class="h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                                                >
                                                    <option value="" x-text="row.brand || 'Brand'"></option>
                                                    @foreach ($equipmentBrands as $brandOption)
                                                        <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                                                    @endforeach
                                                </select>
                                                <label class="sr-only" :for="'pc_model_' + row.id + '_' + pieceIndex">Model</label>
                                                <select
                                                    :id="'pc_model_' + row.id + '_' + pieceIndex"
                                                    x-model="piece.model"
                                                    data-searchable="1"
                                                    data-search-placeholder="Search models…"
                                                    data-add-url="{{ url('/admin/inventory/models/quick-add') }}"
                                                    data-add-label="Add new model"
                                                    data-add-placeholder="New model name"
                                                    @change="syncCatalogOption($el, piece.model)"
                                                    class="mt-2 h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm"
                                                >
                                                    <option value="" x-text="row.model || 'Model'"></option>
                                                    @foreach ($equipmentModels as $modelOption)
                                                        <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            @if ($isStockPage)
                                <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-200/70 pt-3 sm:grid-cols-2">
                                    {{-- Per-item Placement & Room --}}
                                    <div class="space-y-2.5">
                                        <div>
                                            <label class="{{ $eqLabel }}">Placement</label>
                                            <div class="flex h-8 rounded-lg bg-slate-100 p-0.5">
                                                <button
                                                    type="button"
                                                    @click="setRowPlacement(row, 'storage')"
                                                    class="flex-1 rounded-md text-[11px] font-semibold transition"
                                                    :class="row.placement !== 'deploy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                >Keep in storage</button>
                                                <button
                                                    type="button"
                                                    @click="setRowPlacement(row, 'deploy')"
                                                    class="flex-1 rounded-md text-[11px] font-semibold transition"
                                                    :class="row.placement === 'deploy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                                                >Deploy to a room now</button>
                                            </div>
                                        </div>
                                        <div x-show="row.placement !== 'deploy'">
                                            <label class="{{ $eqLabel }}">Storage room</label>
                                            <div class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80">
                                                <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                                    <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                    <span class="truncate" x-text="defaultStorageRoomName"></span>
                                                </span>
                                                <span class="shrink-0 text-[10px] font-medium text-slate-400">Auto</span>
                                            </div>
                                        </div>
                                        <div x-show="row.placement === 'deploy'" x-cloak>
                                            <label class="{{ $eqLabel }}" :for="'multi_deploy_room_' + row.id">Deploy to <span class="text-rose-500">*</span></label>
                                            <select
                                                :id="'multi_deploy_room_' + row.id"
                                                x-model="row.deployRoom"
                                                @change="clearMultiError(row, 'deploy'); syncRowReplacementSelection(row)"
                                                data-searchable="1"
                                                data-search-placeholder="Search rooms…"
                                                class="{{ $eqField }}"
                                                :class="multiError(row, 'deploy') ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                            >
                                                <option value="">Select room</option>
                                                @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                                    <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                                @endforeach
                                            </select>
                                            <p x-show="multiError(row, 'deploy')" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="multiError(row, 'deploy')"></p>
                                        </div>
                                    </div>

                                    {{-- Per-item Accountability & Replacement --}}
                                    <div class="space-y-2.5">
                                        <div>
                                            <label class="{{ $eqLabel }}" :for="'multi_custodian_' + row.id">Accountable person</label>
                                            <select
                                                :id="'multi_custodian_' + row.id"
                                                x-model="row.custodianId"
                                                :disabled="!rowCanAssign(row)"
                                                data-searchable="1"
                                                data-search-placeholder="Search people…"
                                                class="{{ $eqField }} disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <option value="">No one yet</option>
                                                @foreach ($acquisitionPeople as $person)
                                                    <option value="{{ $person->custodian_id }}">{{ $person->custodian_full_name }}{{ $person->custodian_department ? ' · '.$person->custodian_department : '' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div x-effect="syncRowReplacementSelection(row)">
                                            <label class="{{ $eqLabel }}" :for="'multi_replaces_' + row.id">Replaces equipment</label>
                                            <div
                                                x-show="row.placement !== 'deploy' || rowReplacementCandidates(row).length === 0"
                                                x-cloak
                                                class="flex h-9 cursor-not-allowed items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 text-xs opacity-60 ring-1 ring-slate-200/80"
                                            >
                                                <span class="inline-flex min-w-0 items-center gap-1.5 font-medium text-slate-500">
                                                    <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                    <span class="truncate">Not a replacement</span>
                                                </span>
                                                <span class="shrink-0 text-[10px] font-medium text-slate-400" x-text="row.placement !== 'deploy' ? 'In storage' : (!row.deployRoom ? 'Select room' : 'None found')"></span>
                                            </div>
                                            <select
                                                :id="'multi_replaces_' + row.id"
                                                x-show="row.placement === 'deploy' && rowReplacementCandidates(row).length > 0"
                                                x-cloak
                                                :disabled="row.placement !== 'deploy' || rowReplacementCandidates(row).length === 0"
                                                x-model="row.replacesId"
                                                data-searchable="1"
                                                data-search-placeholder="Search matching equipment…"
                                                class="{{ $eqField }}"
                                            >
                                                <option value="">Not a replacement</option>
                                                <template x-for="candidate in rowReplacementCandidates(row)" :key="'rep-' + row.id + '-' + candidate.equipment_id">
                                                    <option
                                                        :value="String(candidate.equipment_id)"
                                                        :selected="String(row.replacesId || '') === String(candidate.equipment_id)"
                                                        x-text="[candidate.room_name, candidate.equipment_name, candidate.equipment_asset_tag].filter(Boolean).join(' · ')"
                                                    ></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endif
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

                <div x-show="page === 2 && (isMulti() || !needsItemStep())" x-cloak class="mt-3.5 grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                    <div :class="isMulti() ? 'lg:col-span-2 grid grid-cols-1 gap-3.5 lg:grid-cols-2' : 'space-y-3.5'">
                        <div x-show="manualIntake() || isMulti()" x-cloak class="space-y-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Purchase</p>
                            <template x-if="!manualIntake()">
                                <div class="space-y-2.5">
                                    <div x-show="isMulti()" class="overflow-hidden rounded-lg bg-white ring-1 ring-slate-200/80">
                                        <template x-for="row in multiLines" :key="'buy-' + row.id">
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 last:border-b-0">
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-semibold text-slate-900" x-text="row.name || row.article"></p>
                                                    <p class="truncate text-[11px] text-slate-500" x-text="[row.rr_number, row.supplier_name].filter(Boolean).join(' · ')"></p>
                                                </div>
                                                <p class="shrink-0 text-[11px] text-slate-500">
                                                    <span x-text="row.purchase_date || 'No date'"></span>
                                                    <span class="mx-1 text-slate-300">·</span>
                                                    <span class="font-semibold text-slate-900" x-text="formatPeso(row.unit_cost)"></span> each
                                                </p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div x-show="manualIntake()" class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                <div>
                                    <label for="add_manual_purchase_date" class="{{ $eqLabel }}" x-text="acqSource === 'donation' ? 'Donation date' : 'Purchase date'"></label>
                                    <input
                                        id="add_manual_purchase_date"
                                        type="date"
                                        name="equipment_purchase_date"
                                        x-model="purchaseDate"
                                        :max="todayIso()"
                                        :disabled="!manualIntake()"
                                        @input="clearError('purchase_date'); clearError('received_date')"
                                        :class="errors.purchase_date ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        class="{{ $eqField }}"
                                    />
                                    <p x-show="errors.purchase_date" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.purchase_date"></p>
                                </div>
                                <div x-show="!isMultiManual()">
                                    <label for="add_manual_purchase_cost" class="{{ $eqLabel }}">
                                        <span x-text="acqSource === 'donation' ? 'Est. value / unit (₱)' : 'Cost / unit (₱)'"></span> <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="add_manual_purchase_cost"
                                        type="number"
                                        name="equipment_purchase_cost"
                                        x-model="purchaseCost"
                                        :disabled="!manualIntake()"
                                        min="0"
                                        max="{{ \App\Support\InputLimits::COST }}"
                                        step="0.01"
                                        inputmode="decimal"
                                        data-money
                                        placeholder="0.00"
                                        @input="clearError('purchase_cost')"
                                        :class="errors.purchase_cost ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        class="{{ $eqField }}"
                                    />
                                    <p x-show="errors.purchase_cost" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.purchase_cost"></p>
                                </div>
                                <div>
                                    <label for="add_received_date" class="{{ $eqLabel }}">Received on</label>
                                    <input
                                        id="add_received_date"
                                        type="date"
                                        name="equipment_acquired_date"
                                        x-model="receivedDate"
                                        :max="todayIso()"
                                        :min="purchaseDate || null"
                                        :disabled="!manualIntake()"
                                        @input="clearError('received_date')"
                                        :class="errors.received_date ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        class="{{ $eqField }}"
                                    />
                                    <p x-show="errors.received_date" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.received_date"></p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Warranty &amp; lifespan</p>
                            <div x-show="!isMulti()" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="add_warranty_expiration" class="{{ $eqLabel }} whitespace-nowrap">
                                        <span>Warranty expiration</span> <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="add_warranty_expiration"
                                        type="date"
                                        name="equipment_warranty_expiration"
                                        x-model="warranty"
                                        @input="clearError('warranty')"
                                        :class="errors.warranty ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        class="{{ $eqField }}"
                                    />
                                    <p x-show="errors.warranty" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.warranty"></p>
                                </div>
                                <div>
                                    <label for="add_useful_life_years" class="{{ $eqLabel }} whitespace-nowrap">
                                        <span>Lifespan (years)</span> <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="add_useful_life_years"
                                        type="number"
                                        name="equipment_useful_life_years"
                                        min="1"
                                        max="{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                        step="1"
                                        x-model="usefulLifeYears"
                                        placeholder="1–{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                        @input="onUsefulLifeInput()"
                                        :class="(errors.useful_life || isInvalidUsefulLife(usefulLifeYears)) ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                        class="{{ $eqField }}"
                                    />
                                    <p x-show="errors.useful_life" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.useful_life"></p>
                                    <p x-show="!errors.useful_life && isInvalidUsefulLife(usefulLifeYears)" x-cloak class="mt-1 text-xs font-medium text-rose-600">Enter a whole number from 1 to {{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}.</p>
                                </div>
                            </div>
                            <div x-show="isMulti()" x-cloak class="space-y-2">
                                <div class="overflow-hidden rounded-lg bg-white ring-1 ring-slate-200/80">
                                    <div
                                        class="hidden gap-2 border-b border-slate-100 bg-slate-50 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400 sm:grid"
                                        :class="isMultiManual() ? 'grid-cols-[minmax(0,1fr)_6.5rem_9.5rem_5.5rem]' : 'grid-cols-[minmax(0,1fr)_9.5rem_6rem]'"
                                    >
                                        <span>Item</span>
                                        <span x-show="isMultiManual()" x-text="acqSource === 'donation' ? 'Value (₱)' : 'Cost (₱)'"></span>
                                        <span>Warranty <span class="text-rose-500">*</span></span>
                                        <span>Lifespan <span class="text-rose-500">*</span></span>
                                    </div>
                                    <template x-for="row in multiLines" :key="'warranty-' + row.id">
                                        <div
                                            class="grid grid-cols-1 items-center gap-2 border-b border-slate-100 px-2.5 py-2 last:border-b-0"
                                            :class="isMultiManual() ? 'sm:grid-cols-[minmax(0,1fr)_6.5rem_9.5rem_5.5rem]' : 'sm:grid-cols-[minmax(0,1fr)_9.5rem_6rem]'"
                                        >
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-semibold text-slate-900" x-text="row.name || row.article || 'Unnamed item'"></p>
                                                <p class="truncate text-[10px] text-slate-500" x-text="[row.tracking === 'Bulk' ? 'Bulk · ' + row.quantity : (row.quantity + ' units'), row.rr_number].filter(Boolean).join(' · ')"></p>
                                            </div>
                                            <template x-if="isMultiManual()">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="{{ \App\Support\InputLimits::COST }}"
                                                    step="0.01"
                                                    inputmode="decimal"
                                                    data-money
                                                    x-model="row.cost"
                                                    placeholder="0.00"
                                                    class="h-8 w-full min-w-0 rounded-md border border-slate-200 px-2 text-xs"
                                                    :aria-label="'Cost per unit for ' + (row.name || 'item')"
                                                />
                                            </template>
                                            <input
                                                type="date"
                                                x-model="row.warranty"
                                                @input="clearError('warranty')"
                                                class="h-8 w-full min-w-0 rounded-md border border-slate-200 px-2 text-xs"
                                                :class="row.warranty ? 'text-slate-900' : 'text-slate-400'"
                                                :aria-label="'Warranty expiration for ' + (row.name || row.article)"
                                            />
                                            <input
                                                type="number"
                                                min="1"
                                                max="{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                                step="1"
                                                x-model="row.useful_life_years"
                                                @input="row.useful_life_years = clampUsefulLife(row.useful_life_years); clearError('useful_life')"
                                                placeholder="5"
                                                class="h-8 w-full min-w-0 rounded-md border border-slate-200 px-2 text-xs"
                                                :class="isInvalidUsefulLife(row.useful_life_years) ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                                :aria-label="'Useful lifespan in years for ' + (row.name || row.article)"
                                            />
                                        </div>
                                    </template>
                                </div>
                                <p x-show="errors.warranty" x-cloak class="text-xs font-medium text-rose-600" x-text="errors.warranty"></p>
                                <p x-show="errors.useful_life" x-cloak class="text-xs font-medium text-rose-600" x-text="errors.useful_life"></p>
                            </div>
                        </div>
                    </div>

                    <div x-show="!isMulti()" x-cloak class="space-y-3.5">
                        <div class="space-y-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Placement &amp; room</p>
                            @if ($isStockPage)
                                <div>
                                    <label class="{{ $eqLabel }}">After adding to inventory</label>
                                    <div class="flex h-9 rounded-lg bg-slate-100 p-1">
                                        <button type="button" @click="setPlacement('storage')" class="flex-1 rounded-md text-xs font-semibold transition" :class="placement === 'storage' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Keep in storage</button>
                                        <button type="button" @click="setPlacement('deploy')" class="flex-1 rounded-md text-xs font-semibold transition" :class="placement === 'deploy' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'">Deploy to a room now</button>
                                    </div>
                                </div>
                            @endif
                            <div x-show="placement !== 'deploy'">
                                @if ($isStockPage)
                                    <label class="{{ $eqLabel }}">Storage room</label>
                                    <input type="hidden" id="add_equipment_room" name="equipment_room_id" :value="room">
                                    <div class="flex h-9 items-center justify-between gap-2 rounded-lg bg-white px-3 text-xs ring-1 ring-slate-200/80">
                                        <span class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-slate-900">
                                            <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                            <span class="truncate" x-text="defaultStorageRoomName"></span>
                                        </span>
                                        <span class="shrink-0 text-[10px] font-medium text-slate-400">Auto</span>
                                    </div>
                                @else
                                    <label for="add_equipment_room" class="{{ $eqLabel }}">Room <span class="text-rose-500">*</span></label>
                                    <select
                                        id="add_equipment_room"
                                        name="equipment_room_id"
                                        x-model="room"
                                        @change="clearError('room'); syncAssetTag()"
                                        class="{{ $eqField }}"
                                        :class="errors.room ? 'bg-rose-50/50 ring-rose-300 focus:ring-rose-200' : ''"
                                    >
                                        <option value="">Select room</option>
                                        @foreach ($rooms as $room)
                                            @php
                                                $isStorageRoom = \App\Support\RoomCategories::isStorageType($room->room_type ?? null);
                                                $roomLabel = $isStorageRoom ? 'Storage · '.$room->room_name : $room->room_name;
                                            @endphp
                                            <option value="{{ $room->room_id }}">{{ $roomLabel }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <p x-show="errors.room" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.room"></p>
                            </div>
                            @if ($isStockPage)
                                <div x-show="placement === 'deploy'" x-cloak>
                                    <label for="add_deploy_room" class="{{ $eqLabel }}" x-text="hasUnitsStep() ? 'Deploy to (default room)' : 'Deploy to'"></label>
                                    <select
                                        id="add_deploy_room"
                                        name="deploy_room_id"
                                        x-model="deployRoom"
                                        @change="clearError('deploy'); syncAssetTag(); syncReplacementSelection()"
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
                                    <p x-show="errors.deploy || errors.room" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.deploy || errors.room"></p>
                                </div>
                            @endif
                        </div>
                        @if ($isStockPage)
                            <div class="space-y-3 rounded-xl bg-slate-50/80 p-3.5 ring-1 ring-slate-200/80">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Accountability &amp; replacement</p>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div :class="isMulti() ? 'sm:col-span-2' : ''">
                                        <label for="add_custodian" class="{{ $eqLabel }}" x-text="isMulti() ? 'Accountable person (default)' : 'Accountable person'"></label>
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
                                        <p x-show="errors.custodian" x-cloak class="mt-1 text-xs font-medium text-rose-600" x-text="errors.custodian"></p>
                                    </div>
                                    <div x-show="!isMulti()" x-effect="syncReplacementSelection()">
                                        <label for="add_replaces" class="{{ $eqLabel }}">Replaces equipment</label>
                                        <div
                                            x-show="(!stockAndLinkMode && placement !== 'deploy') || filteredReplacementCandidates().length === 0"
                                            x-cloak
                                            class="flex h-9 cursor-not-allowed items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 text-xs opacity-60 ring-1 ring-slate-200/80"
                                        >
                                            <span class="inline-flex min-w-0 items-center gap-1.5 font-medium text-slate-500">
                                                <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                                <span class="truncate">Not a replacement</span>
                                            </span>
                                            <span class="shrink-0 text-[10px] font-medium text-slate-400" x-text="placement !== 'deploy' ? 'In storage' : (!deployRoom ? 'Select room' : 'None found')"></span>
                                        </div>
                                        <input
                                            type="hidden"
                                            name="replaces_equipment_id"
                                            value=""
                                            :disabled="(stockAndLinkMode || placement === 'deploy') && filteredReplacementCandidates().length > 0"
                                        >
                                        <select
                                            id="add_replaces"
                                            x-show="(stockAndLinkMode || placement === 'deploy') && filteredReplacementCandidates().length > 0"
                                            x-cloak
                                            :name="((stockAndLinkMode || placement === 'deploy') && filteredReplacementCandidates().length > 0) ? 'replaces_equipment_id' : null"
                                            :disabled="(!stockAndLinkMode && placement !== 'deploy') || filteredReplacementCandidates().length === 0"
                                            x-model="replacesId"
                                            data-searchable="1"
                                            data-search-placeholder="Search matching equipment…"
                                            class="{{ $eqField }}"
                                        >
                                            <option value="">Not a replacement</option>
                                            <template x-for="candidate in filteredReplacementCandidates()" :key="candidate.equipment_id">
                                                <option
                                                    :value="String(candidate.equipment_id)"
                                                    :selected="String(replacesId) === String(candidate.equipment_id)"
                                                    x-text="[candidate.room_name, candidate.equipment_name, candidate.equipment_asset_tag].filter(Boolean).join(' · ')"
                                                ></option>
                                            </template>
                                        </select>
                                        <p
                                            x-show="stockAndLinkMode || replacesId"
                                            x-cloak
                                            class="mt-1 text-[11px] font-medium text-violet-700"
                                        >Stock &amp; Link: tied to this replaced equipment.</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="eq-modal-scroll min-h-0 flex-1 overflow-auto px-5 py-4" x-show="step === 2" x-cloak>
                <template x-if="step === 2 && !isMulti()">
                <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="font-semibold text-slate-900" x-text="name"></span>
                        <span class="text-slate-300">·</span>
                        <span x-text="items.length + ' units'"></span>
                        <span class="text-slate-300">·</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="deploying() ? (roomCount(items.map((item) => item.deploy_room_id)) + ' room(s)') : (optionText('add_equipment_room') || 'Storage')"></span>
                        <span x-show="purchaseCost !== '' && purchaseCost != null" class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="formatPeso(purchaseCost) + ' each'"></span>
                        <span x-show="warranty" class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="'Warranty ' + warranty"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button" @click="regenerateAssetTags()" class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">Regenerate tags</button>
                        <button type="button" @click="applyDefaults()" x-show="showUnitOverrides" x-cloak class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">Re-apply defaults</button>
                        <button
                            type="button"
                            @click="showUnitOverrides = !showUnitOverrides; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                            class="inline-flex h-7 items-center gap-1 rounded-lg border px-2.5 text-[11px] font-semibold transition"
                            :class="showUnitOverrides ? 'border-blue-200 bg-blue-50 text-[#0025cc]' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        >
                            <i data-lucide="sliders-horizontal" class="h-3 w-3"></i>
                            <span x-text="showUnitOverrides ? 'Hide overrides' : 'Overrides'"></span>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="w-full divide-y divide-slate-200 text-left text-xs" :class="showUnitOverrides ? 'min-w-[72rem]' : ''">
                        <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="w-10 px-3 py-2">#</th>
                                <th class="w-32 px-2.5 py-2">Photo</th>
                                <th class="min-w-[14rem] px-2.5 py-2">Asset tag <span class="text-rose-500">*</span></th>
                                <th class="min-w-[12rem] px-2.5 py-2">Serial number <span class="text-rose-500">*</span></th>
                                <th x-show="deploying()" class="min-w-[10rem] px-2.5 py-2">Deploy room</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Brand</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Model</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Condition</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Purchase</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Cost</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Life (yrs)</th>
                                <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Warranty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, index) in items" :key="index">
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-3 py-2 font-medium text-slate-400" x-text="index + 1"></td>
                                    <td class="px-2.5 py-2">
                                        <div class="flex min-w-0 items-center gap-1.5">
                                            <button
                                                type="button"
                                                x-show="item._imagePreview"
                                                x-cloak
                                                @click="openEquipmentPhotoViewer(item._imagePreview, (item.equipment_asset_tag || name || 'Equipment') + ' photo')"
                                                class="group relative h-8 w-8 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-slate-200"
                                                aria-label="View unit photo"
                                            >
                                                <img :src="item._imagePreview" alt="" class="h-full w-full object-cover">
                                            </button>
                                            <div
                                                x-show="!item._imagePreview"
                                                class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-slate-50 ring-1 ring-slate-200"
                                            >
                                                <span
                                                    class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                    x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(name || '') : ''"
                                                ></span>
                                            </div>
                                            <label class="inline-flex h-7 cursor-pointer items-center rounded-md bg-white px-2 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
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
                                                ×
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-2.5 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input type="text" :name="'items[' + index + '][equipment_asset_tag]'" x-model="item.equipment_asset_tag" @input="item._tagManual = true" class="h-8 w-full min-w-0 truncate rounded-lg border border-slate-200 px-2.5 font-mono text-xs text-slate-900 focus:border-[#0025cc] focus:outline-none focus:ring-2 focus:ring-[#0025cc]/15" />
                                            <div
                                                x-show="String(item.equipment_asset_tag || '').trim()"
                                                x-cloak
                                                class="pointer-events-none invisible absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 font-mono text-[11px] font-medium leading-snug text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_asset_tag"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-2.5 py-2">
                                        <div class="group/eqtip relative min-w-0">
                                            <input
                                                type="text"
                                                :name="'items[' + index + '][equipment_serial_number]'"
                                                x-model="item.equipment_serial_number"
                                                maxlength="{{ \App\Support\InputLimits::SERIAL }}"
                                                placeholder="Enter serial number"
                                                @input="item.equipment_serial_number = String(item.equipment_serial_number || '').slice(0, {{ \App\Support\InputLimits::SERIAL }})"
                                                class="h-8 w-full min-w-0 truncate rounded-lg border border-slate-200 px-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-[#0025cc] focus:outline-none focus:ring-2 focus:ring-[#0025cc]/15"
                                            />
                                            <div
                                                x-show="String(item.equipment_serial_number || '').trim()"
                                                x-cloak
                                                class="pointer-events-none invisible absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 font-mono text-[11px] font-medium leading-snug text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                x-text="item.equipment_serial_number"
                                            ></div>
                                        </div>
                                    </td>
                                    <td x-show="deploying()" class="px-2.5 py-2">
                                        <select
                                            :name="'items[' + index + '][deploy_room_id]'"
                                            x-model="item.deploy_room_id"
                                            :disabled="!deploying()"
                                            @change="onItemRoomChange(item)"
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-2 text-xs"
                                        >
                                            @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                                <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <div
                                            x-show="!manualIntake() && _brandFromRis"
                                            x-cloak
                                            class="flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 text-xs text-slate-700"
                                            title="The brand was recorded on the RIS for this equipment."
                                        >
                                            <i data-lucide="lock" class="h-3 w-3 shrink-0 text-slate-400"></i>
                                            <span class="truncate" x-text="item.equipment_brand_name || brand || '—'"></span>
                                        </div>
                                        <input
                                            type="hidden"
                                            :name="'items[' + index + '][equipment_brand_name]'"
                                            :value="item.equipment_brand_name || brand"
                                            :disabled="manualIntake() || !_brandFromRis"
                                        >
                                        <select
                                            x-show="manualIntake() || !_brandFromRis"
                                            x-cloak
                                            :name="(manualIntake() || !_brandFromRis) ? ('items[' + index + '][equipment_brand_name]') : null"
                                            :disabled="!manualIntake() && _brandFromRis"
                                            :id="'unit_brand_' + index"
                                            x-model="item.equipment_brand_name"
                                            data-searchable="1"
                                            data-search-placeholder="Search brands…"
                                            data-add-url="{{ url('/admin/inventory/brands/quick-add') }}"
                                            data-add-label="Add new brand"
                                            data-add-placeholder="New brand name"
                                            @change="item._brandManual = true; syncCatalogOption($el, item.equipment_brand_name)"
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-2 text-xs"
                                        >
                                            <option value="">Select brand</option>
                                            @foreach ($equipmentBrands as $brandOption)
                                                <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <select
                                            :name="'items[' + index + '][equipment_model]'"
                                            :id="'unit_model_' + index"
                                            x-model="item.equipment_model"
                                            data-searchable="1"
                                            data-search-placeholder="Search models…"
                                            data-add-url="{{ url('/admin/inventory/models/quick-add') }}"
                                            data-add-label="Add new model"
                                            data-add-placeholder="New model name"
                                            @change="syncCatalogOption($el, item.equipment_model)"
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-2 text-xs"
                                        >
                                            <option value="">Select model</option>
                                            @foreach ($equipmentModels as $modelOption)
                                                <option value="{{ $modelOption }}">{{ $modelOption }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <div
                                            x-show="!manualIntake()"
                                            class="flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2 text-xs text-slate-700"
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
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-2 text-xs"
                                        >
                                            <option value="Good">Good</option>
                                            <option value="Under Maintenance">Under Maintenance</option>
                                            <option value="Defective">Defective</option>
                                            <option value="Damaged">Damaged</option>
                                        </select>
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <input
                                            type="date"
                                            :name="'items[' + index + '][equipment_purchase_date]'"
                                            x-model="item.equipment_purchase_date"
                                            :max="todayIso()"
                                            @input="item._purchaseManual = true"
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-1.5 text-xs"
                                        />
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ \App\Support\InputLimits::COST }}"
                                            step="0.01"
                                            inputmode="decimal"
                                            data-money
                                            :name="'items[' + index + '][equipment_purchase_cost]'"
                                            x-model="item.equipment_purchase_cost"
                                            @input="item._costManual = true"
                                            placeholder="0.00"
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-1.5 text-xs"
                                        />
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <input
                                            type="number"
                                            min="1"
                                            max="{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                            step="1"
                                            :name="'items[' + index + '][equipment_useful_life_years]'"
                                            x-model="item.equipment_useful_life_years"
                                            @input="item._lifeManual = true; item.equipment_useful_life_years = clampUsefulLife(item.equipment_useful_life_years)"
                                            placeholder="1–{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                            class="h-8 w-full min-w-0 rounded-lg border px-1.5 text-xs"
                                            :class="isInvalidUsefulLife(item.equipment_useful_life_years) ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200'"
                                        />
                                    </td>
                                    <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                        <input
                                            type="date"
                                            :name="'items[' + index + '][equipment_warranty_expiration]'"
                                            x-model="item.equipment_warranty_expiration"
                                            @input="item._warrantyManual = true"
                                            class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-1.5 text-xs"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                </div>
                </template>

                <div x-show="isMulti()" x-cloak class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                            <span class="font-semibold text-slate-900" x-text="multiLines.length + ' items'"></span>
                            <span class="text-slate-300">·</span>
                            <span x-text="multiUnitCount() + ' records'"></span>
                            <span class="text-slate-300">·</span>
                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="deploying() ? (roomCount(multiUnits().map((unit) => unit.deploy_room_id)) + ' room(s)') : (optionText('add_equipment_room') || 'Storage')"></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" @click="fillMultiTags(true)" class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">Regenerate tags</button>
                            <button type="button" x-show="deploying()" @click="useDefaultRoomForAll()" class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50">Default room for all</button>
                            <button type="button" x-show="canAssignPerson()" @click="useDefaultCustodianForAll()" class="h-7 rounded-lg border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-50" x-text="custodianId ? 'Assign all to ' + custodianName(custodianId) : 'Clear assignments'"></button>
                            <button
                                type="button"
                                @click="showUnitOverrides = !showUnitOverrides; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
                                class="inline-flex h-7 items-center gap-1 rounded-lg border px-2.5 text-[11px] font-semibold transition"
                                :class="showUnitOverrides ? 'border-blue-200 bg-blue-50 text-[#0025cc]' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                            >
                                <i data-lucide="sliders-horizontal" class="h-3 w-3"></i>
                                <span x-text="showUnitOverrides ? 'Hide overrides' : 'Overrides'"></span>
                            </button>
                        </div>
                    </div>
                    <template x-for="row in multiLines" :key="'units-' + row.id">
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/80 px-3.5 py-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-xs font-semibold text-slate-900" x-text="row.name || row.article"></p>
                                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200" x-text="row.tracking === 'Bulk' ? ('Bulk · ' + row.quantity) : (row.quantity + ' unit' + (Number(row.quantity) === 1 ? '' : 's'))"></span>
                                    </div>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-500" x-text="row.source || [row.brand, row.model].filter(Boolean).join(' ') || (isMultiManual() ? row.condition : '')"></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-600">
                                    <span x-show="row.cost !== '' && row.cost != null" class="rounded-md bg-white px-2 py-0.5 font-medium ring-1 ring-slate-200/80" x-text="formatPeso(row.cost)"></span>
                                    <span class="rounded-md bg-white px-2 py-0.5 font-medium ring-1 ring-slate-200/80" x-text="(row.useful_life_years || usefulLifeYears || 5) + ' yrs'"></span>
                                    <span x-show="row.warranty || warranty" class="rounded-md bg-white px-2 py-0.5 font-medium ring-1 ring-slate-200/80" x-text="'Warranty ' + (row.warranty || warranty)"></span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-slate-100 text-left text-xs" :class="showUnitOverrides ? 'min-w-[64rem]' : ''">
                                <thead class="bg-white text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <th class="w-10 px-3 py-2">#</th>
                                        <th class="w-32 px-2.5 py-2">Photo</th>
                                        <th class="min-w-[14rem] px-2.5 py-2">Asset tag <span class="text-rose-500">*</span></th>
                                        <th class="min-w-[12rem] px-2.5 py-2">Serial number <span class="text-rose-500">*</span></th>
                                        <th x-show="deploying()" class="min-w-[10rem] px-2.5 py-2">Deploy room</th>
                                        <th x-show="canAssignPerson()" class="min-w-[10rem] px-2.5 py-2">Assign to</th>
                                        <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Purchase</th>
                                        <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Cost</th>
                                        <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Life</th>
                                        <th x-show="showUnitOverrides" x-cloak class="px-2 py-2">Warranty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="(unit, unitIndex) in row.units" :key="row.id + '-' + unitIndex">
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-3 py-2 font-medium text-slate-400" x-text="unitIndex + 1"></td>
                                            <td class="px-2.5 py-2">
                                                <div class="flex min-w-0 items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        x-show="unit._imagePreview"
                                                        x-cloak
                                                        @click="openEquipmentPhotoViewer(unit._imagePreview, (unit.asset_tag || row.name || 'Equipment') + ' photo')"
                                                        class="h-8 w-8 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-slate-200"
                                                        aria-label="View unit photo"
                                                    >
                                                        <img :src="unit._imagePreview" alt="" class="h-full w-full object-cover">
                                                    </button>
                                                    <div x-show="!unit._imagePreview" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-slate-50 ring-1 ring-slate-200">
                                                        <span
                                                            class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                            x-html="window.PrismEquipmentIcons ? window.PrismEquipmentIcons.svg(row.name || '') : ''"
                                                        ></span>
                                                    </div>
                                                    <label class="inline-flex h-7 cursor-pointer items-center rounded-md bg-white px-2 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                                        <span x-text="unit._imagePreview ? 'Change' : 'Add'"></span>
                                                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" @change="onMultiUnitImage(unit, $event)">
                                                    </label>
                                                    <button
                                                        type="button"
                                                        x-show="unit._imagePreview"
                                                        x-cloak
                                                        @click="clearMultiUnitImage(unit)"
                                                        class="text-[11px] font-semibold text-rose-600 hover:text-rose-700"
                                                    >×</button>
                                                </div>
                                            </td>
                                            <td class="px-2.5 py-2">
                                                <div class="group/eqtip relative min-w-0">
                                                    <input type="text" x-model="unit.asset_tag" @input="unit._tagManual = true" class="h-8 w-full min-w-0 truncate rounded-lg border border-slate-200 px-2.5 font-mono text-xs text-slate-900 focus:border-[#0025cc] focus:outline-none focus:ring-2 focus:ring-[#0025cc]/15" />
                                                    <div
                                                        x-show="String(unit.asset_tag || '').trim()"
                                                        x-cloak
                                                        class="pointer-events-none invisible absolute left-0 top-[calc(100%+0.35rem)] z-30 max-w-[min(28rem,70vw)] whitespace-normal break-all rounded-lg bg-slate-900 px-2.5 py-1.5 font-mono text-[11px] font-medium leading-snug text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover/eqtip:visible group-hover/eqtip:opacity-100"
                                                        x-text="unit.asset_tag"
                                                    ></div>
                                                </div>
                                                <p
                                                    x-show="pieceBrandModel(row, unitIndex)"
                                                    class="mt-1 truncate text-[11px] text-slate-500"
                                                    x-text="pieceBrandModel(row, unitIndex)"
                                                ></p>
                                            </td>
                                            <td class="px-2.5 py-2">
                                                <input type="text" x-model="unit.serial" placeholder="Enter serial number" class="h-8 w-full min-w-0 truncate rounded-lg border border-slate-200 px-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-[#0025cc] focus:outline-none focus:ring-2 focus:ring-[#0025cc]/15" />
                                            </td>
                                            <td x-show="deploying()" class="px-2.5 py-2">
                                                <select
                                                    x-show="rowDeploying(row)"
                                                    x-model="unit.deploy_room_id"
                                                    @change="onMultiUnitRoomChange(unit)"
                                                    class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-2 text-xs"
                                                >
                                                    @foreach (($deployRooms ?? collect()) as $deployRoomOption)
                                                        <option value="{{ $deployRoomOption->room_id }}">{{ $deployRoomOption->room_name }}</option>
                                                    @endforeach
                                                </select>
                                                <span x-show="!rowDeploying(row)" class="text-[11px] text-slate-500" x-text="storageRoomName(row.room || room) || 'Storage'"></span>
                                            </td>
                                            <td x-show="canAssignPerson()" class="px-2.5 py-2">
                                                <select
                                                    x-show="unitCanAssign(row)"
                                                    x-model="unit.custodian_id"
                                                    @change="unit._custodianManual = true"
                                                    class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-2 text-xs"
                                                    :class="unit.custodian_id ? 'text-slate-900' : 'text-slate-400'"
                                                    :aria-label="'Accountable person for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                >
                                                    <option value="">No one yet</option>
                                                    @foreach ($acquisitionPeople as $person)
                                                        <option value="{{ $person->custodian_id }}">{{ $person->custodian_full_name }}</option>
                                                    @endforeach
                                                </select>
                                                <span x-show="!unitCanAssign(row)" class="text-[11px] text-slate-400">—</span>
                                            </td>
                                            <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                                <input
                                                    type="date"
                                                    x-model="unit.purchase_date"
                                                    :max="todayIso()"
                                                    @input="unit._purchaseManual = true"
                                                    class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-1.5 text-xs"
                                                    :aria-label="'Purchase date for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                />
                                            </td>
                                            <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="{{ \App\Support\InputLimits::COST }}"
                                                    step="0.01"
                                                    inputmode="decimal"
                                                    data-money
                                                    x-model="unit.cost"
                                                    @input="unit._costManual = true"
                                                    placeholder="0.00"
                                                    class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-1.5 text-xs"
                                                    :aria-label="'Cost for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                />
                                            </td>
                                            <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                                    step="1"
                                                    x-model="unit.useful_life_years"
                                                    @input="unit._lifeManual = true; unit.useful_life_years = clampUsefulLife(unit.useful_life_years)"
                                                    placeholder="1–{{ \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }}"
                                                    class="h-8 w-full min-w-0 rounded-lg border px-1.5 text-xs"
                                                    :class="isInvalidUsefulLife(unit.useful_life_years) ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200'"
                                                    :aria-label="'Useful lifespan for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                />
                                            </td>
                                            <td x-show="showUnitOverrides" x-cloak class="px-2 py-2">
                                                <input
                                                    type="date"
                                                    x-model="unit.warranty"
                                                    @input="unit._warrantyManual = true"
                                                    class="h-8 w-full min-w-0 rounded-lg border border-slate-200 px-1.5 text-xs"
                                                    :aria-label="'Warranty expiration for ' + (row.name || row.article) + ' #' + (unitIndex + 1)"
                                                />
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
                <button type="button" @click="back()" class="h-9 rounded-lg px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-100" x-text="isFirstStep() ? 'Cancel' : 'Back'"></button>
                <button
                    type="button"
                    x-show="!canSaveNow()"
                    @click="next()"
                    class="h-9 rounded-lg bg-[#0025cc] px-4 text-xs font-semibold text-white transition hover:bg-blue-800"
                >
                    Next
                </button>
                <button
                    type="button"
                    x-show="canSaveNow()"
                    x-cloak
                    @click="saveNow()"
                    :disabled="submitting"
                    class="h-9 rounded-lg bg-[#0025cc] px-4 text-xs font-semibold text-white transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                    x-text="submitting
                        ? 'Saving…'
                        : (isMulti()
                            ? ('Save ' + multiLines.length + ' items to Inventory')
                            : (needsItemStep() ? ('Save ' + items.length + ' assets to Inventory') : 'Save to Inventory'))"
                ></button>
            </div>
        </form>

        <div
            x-show="reviewOpen"
            x-cloak
            class="absolute inset-0 z-30 flex items-center justify-center bg-[#0b1220]/55 p-4 backdrop-blur-[2px] sm:p-6"
            @click.self="closeReview()"
        >
            <div class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl shadow-slate-950/25" role="dialog" aria-modal="true" aria-labelledby="addEquipmentReviewHeading">
                {{-- Header --}}
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 bg-white px-6 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#0025cc] ring-1 ring-blue-100">
                            <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 id="addEquipmentReviewHeading" class="text-base font-semibold tracking-tight text-slate-900">Review &amp; confirm</h2>
                                <span
                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-[#0025cc] ring-1 ring-blue-100"
                                    x-text="reviewTotalBadge()"
                                ></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="closeReview()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close review" :disabled="submitting">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                {{-- Error Banner --}}
                <div
                    x-show="formError"
                    x-cloak
                    class="mx-6 mt-4 flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100"
                >
                    <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <p class="min-w-0 flex-1 leading-relaxed" x-text="formError"></p>
                </div>

                {{-- Scrollable Review Content --}}
                <div class="eq-modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50/40 px-6 py-5">
                    @if ($isStockPage)
                        {{-- Shared Source Strip --}}
                        <section class="rounded-xl border border-blue-100/90 bg-gradient-to-r from-blue-50/60 via-white to-white px-4 py-3 shadow-xs">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="file-text" class="h-3.5 w-3.5 text-[#0025cc]"></i>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Source</span>
                                </div>
                                <button type="button" class="text-xs font-semibold text-[#0025cc] transition hover:text-blue-800 hover:underline" @click="editFromReview('p1')">Edit source</button>
                            </div>
                            <div class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-3">
                                <template x-for="row in reviewSource()" :key="row[0]">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400" x-text="row[0]"></p>
                                        <p class="truncate text-xs font-semibold text-slate-800" :title="row[1]" x-text="row[1]"></p>
                                    </div>
                                </template>
                            </div>
                        </section>
                    @endif

                    {{-- Horizontal Carousel Bar (shown when more than 1 unit/item) --}}
                    <div
                        x-show="reviewSlides().length > 1"
                        x-cloak
                        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white px-3.5 py-2.5 shadow-xs"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-1.5 overflow-x-auto pb-0.5">
                            <template x-for="(slide, idx) in reviewSlides()" :key="slide.key">
                                <button
                                    type="button"
                                    @click="goToReviewSlide(idx)"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition"
                                    :class="idx === reviewSlideIndex
                                        ? 'bg-[#0025cc] text-white shadow-sm shadow-blue-900/20'
                                        : 'bg-slate-100/90 text-slate-600 hover:bg-slate-200/70 hover:text-slate-900'"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="idx === reviewSlideIndex
                                            ? 'bg-blue-200'
                                            : (slide.deploying ? 'bg-sky-500' : 'bg-amber-500')"
                                    ></span>
                                    <span x-text="slide.label"></span>
                                </button>
                            </template>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-xs font-medium tabular-nums text-slate-500" x-text="(reviewSlideIndex + 1) + ' of ' + reviewSlides().length"></span>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="prevReviewSlide()"
                                    :disabled="reviewSlideIndex <= 0"
                                    class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                                    <span>Prev</span>
                                </button>
                                <button
                                    type="button"
                                    @click="nextReviewSlide()"
                                    :disabled="reviewSlideIndex >= reviewSlides().length - 1"
                                    class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <span>Next</span>
                                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Active Asset Detail Card --}}
                    <template x-if="activeReviewSlide()">
                        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm">
                            {{-- Asset Hero Header --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                                <div class="flex min-w-0 items-center gap-3.5">
                                    <template x-if="activeReviewSlide().image">
                                        <img
                                            :src="activeReviewSlide().image"
                                            :alt="activeReviewSlide().name"
                                            class="h-12 w-12 shrink-0 rounded-xl object-cover ring-1 ring-slate-200"
                                        >
                                    </template>
                                    <template x-if="!activeReviewSlide().image">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-[#0025cc] shadow-xs ring-1 ring-slate-200/90">
                                            <i data-lucide="package" class="h-5 w-5"></i>
                                        </div>
                                    </template>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-[16px] font-semibold tracking-tight text-slate-900" x-text="activeReviewSlide().name || 'Unnamed equipment'"></h3>
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200/70"
                                                x-text="activeReviewSlide().condition"
                                            ></span>
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200/80"
                                                x-text="activeReviewSlide().tracking"
                                            ></span>
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            <span x-text="activeReviewSlide().classification || 'No classification'"></span>
                                            <template x-if="activeReviewSlide().source">
                                                <span x-text="' · ' + activeReviewSlide().source"></span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="editSlideFromReview(activeReviewSlide())"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-[#0025cc] shadow-2xs transition hover:border-blue-200 hover:bg-blue-50/60"
                                >
                                    <i data-lucide="pencil" class="h-3 w-3"></i>
                                    <span>Edit</span>
                                </button>
                            </div>

                            {{-- Asset Tag & Serial Highlight Strip --}}
                            <div class="grid grid-cols-1 gap-3 border-b border-slate-100 bg-white px-5 py-3.5 sm:grid-cols-2">
                                <div class="min-w-0 rounded-xl bg-slate-50/90 px-3.5 py-2.5 ring-1 ring-slate-200/70">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Asset tag</p>
                                    <p
                                        class="mt-1 truncate font-mono text-xs font-semibold text-slate-900"
                                        :title="activeReviewSlide().asset_tag"
                                        x-text="activeReviewSlide().asset_tag || '—'"
                                    ></p>
                                </div>
                                <div class="min-w-0 rounded-xl bg-slate-50/90 px-3.5 py-2.5 ring-1 ring-slate-200/70">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Serial number</p>
                                    <p
                                        class="mt-1 truncate font-mono text-xs font-medium"
                                        :class="activeReviewSlide().serial && activeReviewSlide().serial !== '—' ? 'text-slate-800' : 'text-slate-400'"
                                        :title="activeReviewSlide().serial"
                                        x-text="activeReviewSlide().serial || '—'"
                                    ></p>
                                </div>
                            </div>

                            {{-- Specifications & Placement Grid --}}
                            <div class="grid grid-cols-1 divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                                {{-- Specifications & Coverage --}}
                                <div class="p-5">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Specifications &amp; coverage</p>
                                    <dl class="mt-3 space-y-2.5 text-sm">
                                        <div class="flex items-center justify-between gap-4">
                                            <dt class="text-slate-500">Brand</dt>
                                            <dd class="text-right font-medium text-slate-900" x-text="activeReviewSlide().brand || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <dt class="text-slate-500">Model</dt>
                                            <dd class="text-right font-medium text-slate-900" x-text="activeReviewSlide().model || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <dt class="text-slate-500" x-text="acqSource === 'donation' ? 'Est. value / unit' : 'Cost / unit'"></dt>
                                            <dd class="text-right font-semibold tabular-nums text-slate-900" x-text="activeReviewSlide().cost || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <dt class="text-slate-500" x-text="acqSource === 'donation' ? 'Donation date' : 'Purchase date'"></dt>
                                            <dd class="text-right font-medium tabular-nums text-slate-900" x-text="activeReviewSlide().purchase_date || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <dt class="text-slate-500">Warranty expiration</dt>
                                            <dd class="text-right font-medium tabular-nums text-slate-900" x-text="activeReviewSlide().warranty || '—'"></dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <dt class="text-slate-500">Useful lifespan</dt>
                                            <dd class="text-right font-medium text-slate-900" x-text="activeReviewSlide().lifespan || '5 years'"></dd>
                                        </div>
                                    </dl>
                                </div>

                                {{-- Placement & Accountability --}}
                                <div class="p-5">
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Placement &amp; accountability</p>
                                    <div class="mt-3 space-y-3">
                                        {{-- Destination Room Card --}}
                                        <div
                                            class="flex items-center justify-between gap-3 rounded-xl px-3.5 py-2.5 ring-1"
                                            :class="activeReviewSlide().deploying
                                                ? 'bg-sky-50/70 ring-sky-200/80'
                                                : 'bg-amber-50/70 ring-amber-200/80'"
                                        >
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-semibold uppercase tracking-wider"
                                                   :class="activeReviewSlide().deploying ? 'text-sky-600' : 'text-amber-700'"
                                                   x-text="activeReviewSlide().deploying ? 'Deployed to room' : 'Kept in storage'"></p>
                                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-900" x-text="activeReviewSlide().room || '—'"></p>
                                            </div>
                                            <span
                                                class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                :class="activeReviewSlide().deploying
                                                    ? 'bg-sky-100 text-sky-700'
                                                    : 'bg-amber-100 text-amber-800'"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full" :class="activeReviewSlide().deploying ? 'bg-sky-500' : 'bg-amber-500'"></span>
                                                <span x-text="activeReviewSlide().deploying ? 'Deployed' : 'In storage'"></span>
                                            </span>
                                        </div>

                                        <dl class="space-y-2.5 text-sm">
                                            <div class="flex items-center justify-between gap-4">
                                                <dt class="text-slate-500">Accountable person</dt>
                                                <dd
                                                    class="text-right font-medium"
                                                    :class="activeReviewSlide().custodian && activeReviewSlide().custodian !== 'No one yet' ? 'text-teal-700 font-semibold' : 'text-slate-400'"
                                                    x-text="activeReviewSlide().custodian || 'No one yet'"
                                                ></dd>
                                            </div>
                                            <div class="flex items-center justify-between gap-4">
                                                <dt class="text-slate-500">Replaces equipment</dt>
                                                <dd
                                                    class="max-w-[220px] truncate text-right font-medium"
                                                    :class="activeReviewSlide().replaces && activeReviewSlide().replaces !== 'Not a replacement' ? 'text-violet-700 font-semibold' : 'text-slate-400'"
                                                    :title="activeReviewSlide().replaces"
                                                    x-text="activeReviewSlide().replaces || 'Not a replacement'"
                                                ></dd>
                                            </div>
                                            <div class="flex items-center justify-between gap-4">
                                                <dt class="text-slate-500">Received on</dt>
                                                <dd class="text-right font-medium text-slate-900" x-text="activeReviewSlide().received_on || 'Today (date added)'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white px-6 py-4">
                    <p class="text-xs text-slate-500" x-show="reviewSlides().length > 1" x-cloak>
                        Viewing <span class="font-semibold text-slate-800" x-text="activeReviewSlide()?.label || ''"></span>
                        (<span class="tabular-nums" x-text="(reviewSlideIndex + 1) + ' of ' + reviewSlides().length"></span>)
                    </p>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" @click="closeReview()" :disabled="submitting" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100 disabled:opacity-60">Back</button>
                        <button
                            type="button"
                            @click="confirmReview()"
                            :disabled="submitting"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-5 text-sm font-semibold text-white shadow-sm shadow-blue-900/20 transition hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                        >
                            <i data-lucide="check" class="h-4 w-4" x-show="!submitting"></i>
                            <span x-text="submitting ? 'Adding…' : 'Add to inventory'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        {{-- Conditional discard changes confirmation dialog --}}
        <div
            x-show="discardPromptOpen"
            x-cloak
            class="absolute inset-0 z-40 flex items-center justify-center bg-[#0b1220]/50 p-4"
            @click.self="discardPromptOpen = false"
        >
            <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-950/20" role="dialog" aria-modal="true" aria-labelledby="discardEquipmentChangesTitle">
                <h3 id="discardEquipmentChangesTitle" class="text-sm font-semibold text-slate-900">Discard unsaved changes?</h3>
                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Your current selections and entered details will be lost if you close now.</p>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="discardPromptOpen = false"
                        class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Keep editing
                    </button>
                    <button
                        type="button"
                        @click="close()"
                        class="h-8 rounded-lg bg-rose-600 px-3 text-xs font-semibold text-white transition hover:bg-rose-700"
                    >
                        Discard &amp; close
                    </button>
                </div>
            </div>
        </div>
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
                reviewOpen: false,
                reviewConfirmed: false,
                reviewSlideIndex: 0,
                discardPromptOpen: false,
                showUnitOverrides: false,
                replacementCandidates: @json(($replacementCandidates ?? collect())->values()),
                step: 1,
                page: firstPage,
                furthestStepIndex: 0,
                todayIso() {
                    return todayLocal();
                },
                isFutureDate(value) {
                    const day = String(value || '').trim();
                    return day !== '' && day > todayLocal();
                },
                isBeforeDate(value, other) {
                    const a = String(value || '').trim();
                    const b = String(other || '').trim();
                    return a !== '' && b !== '' && a < b;
                },
                placement: 'storage',
                deployRoom: '',
                custodianId: '',
                replacesId: '',
                stockAndLinkMode: false,
                fullscreen: false,
                tracking: 'Individual',
                consumableNeedles: @json(\App\Support\EquipmentTrackingModes::CONSUMABLE_NEEDLES),
                name: '',
                category: '',
                categoryManual: false,
                defaultStorageRoomId: @json((string) ($defaultStorageRoomId ?? '')),
                defaultStorageRoomName: @json($defaultStorageRoomName),
                room: @json((string) (old('equipment_room_id', $defaultStorageRoomId ?? ''))),
                quantity: 1,
                condition: 'Good',
                brand: '',
                model: '',
                warranty: '',
                usefulLifeYears: 5,
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
                showCompletedRr: false,
                rrMultiLines: [],
                manualLines: [],
                manualSeq: 0,
                activeMultiIndex: 0,
                activeUnitIndex: 0,
                multiErrors: {},
                submitting: false,
                purchaseDate: '',
                purchaseCost: '',
                brandPresets: [],
                rrSerialNumbers: [],
                _brandFromRis: false,
                _brandManual: false,
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
                                backorder: 0,
                                ready: 0,
                                lines: [],
                            };
                            groups.push(byKey[key]);
                        }
                        const group = byKey[key];
                        group.po_number = group.po_number || line.po_number || null;
                        group.supplier_name = group.supplier_name || line.supplier_name || null;
                        group.remaining += Number(line.remaining_qty) || 0;
                        group.backorder += Number(line.backorder_qty) || 0;
                        if (line.is_selectable) group.ready += 1;
                        group.lines.push(line);
                    });
                    return groups;
                },
                get rrGroupsPending() {
                    return this.rrGroups.filter((group) => (Number(group.remaining) || 0) > 0);
                },
                get rrGroupsCompleted() {
                    return this.rrGroups.filter((group) => (Number(group.remaining) || 0) <= 0);
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
                        this.showCompletedRr = this.rrGroupsPending.length === 0 && this.rrGroupsCompleted.length > 0;
                    } catch (e) {
                        this.rrLines = [];
                        this.rrGuide = [];
                        this.showCompletedRr = false;
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
                        warranty: '',
                        useful_life_years: 5,
                        placement: 'storage',
                        room: String(this.room || @json((string) ($defaultStorageRoomId ?? ''))),
                        deployRoom: '',
                        custodianId: '',
                        replacesId: '',
                        perPiece: false,
                        pieces: [],
                        ...values,
                    };
                },
                startManualMulti() {
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
                    this.activeMultiIndex = Math.max(0, this.manualLines.length - 1);
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
                        this.activeMultiIndex = Math.min(this.activeMultiIndex, rest.length - 1);
                        return;
                    }
                    this.activeMultiIndex = 0;
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
                    this.syncAssetTag();
                    this.ensureBrandModelOptions();
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
                        if (previous[id]) {
                            const article = String(line.article || '').trim();
                            if (article) previous[id].name = article;
                            previous[id].brand_presets = Array.isArray(line.brand_presets) ? line.brand_presets : (previous[id].brand_presets || []);
                            previous[id].serial_number = line.serial_number || previous[id].serial_number || '';
                            previous[id].serial_numbers = Array.isArray(line.serial_numbers) ? line.serial_numbers : (previous[id].serial_numbers || []);
                            previous[id]._brandManual = false;
                            this.applyExpressDefaultsToRow(previous[id], false);
                            return previous[id];
                        }
                        const article = String(line.article || '').trim();
                        const remaining = Number(line.remaining_qty != null ? line.remaining_qty : line.quantity) || 1;
                        const max = Math.min(200, Math.max(1, remaining));
                        const detected = typeof detectEquipmentCategoryId === 'function' ? detectEquipmentCategoryId(article) : '';
                        const row = {
                            id,
                            article,
                            source: [line.rr_number, line.supplier_name].filter(Boolean).join(' · '),
                            rr_number: line.rr_number || '',
                            supplier_name: line.supplier_name || '',
                            purchase_date: line.purchase_date || '',
                            unit_cost: line.unit_cost,
                            name: article,
                            category: detected ? String(detected) : '',
                            categoryManual: false,
                            brand: line.brand || '',
                            brand_presets: Array.isArray(line.brand_presets) ? line.brand_presets : [],
                            serial_number: line.serial_number || '',
                            serial_numbers: Array.isArray(line.serial_numbers) ? line.serial_numbers : [],
                            _brandFromRis: false,
                            _brandManual: false,
                            _piecesManual: false,
                            model: '',
                            quantity: max,
                            max,
                            tracking: 'Individual',
                            warranty: '',
                            useful_life_years: 5,
                            cost: line.unit_cost != null ? String(line.unit_cost) : '',
                            placement: 'storage',
                            room: String(this.room || @json((string) ($defaultStorageRoomId ?? ''))),
                            deployRoom: '',
                            custodianId: '',
                            replacesId: line.replaces_equipment_id ? String(line.replaces_equipment_id) : '',
                            perPiece: false,
                            pieces: [],
                        };
                        this.applyExpressDefaultsToRow(row, true);
                        return row;
                    });
                    const ids = this.rrMultiLines.map((row) => row.id);
                    const errors = {};
                    Object.keys(this.multiErrors || {}).forEach((id) => {
                        if (ids.includes(id)) errors[id] = this.multiErrors[id];
                    });
                    this.multiErrors = errors;
                    if (this.activeMultiIndex >= this.rrMultiLines.length) {
                        this.activeMultiIndex = Math.max(0, this.rrMultiLines.length - 1);
                    }
                },
                goToMultiSlide(index) {
                    const len = (this.multiLines || []).length;
                    if (!len) {
                        this.activeMultiIndex = 0;
                        return;
                    }
                    this.activeMultiIndex = Math.max(0, Math.min(len - 1, Number(index) || 0));
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                prevMultiSlide() {
                    this.goToMultiSlide(this.activeMultiIndex - 1);
                },
                nextMultiSlide() {
                    this.goToMultiSlide(this.activeMultiIndex + 1);
                },
                multiRowHasError(row) {
                    if (!row || !this.multiErrors) return false;
                    const err = this.multiErrors[row.id];
                    return !!(err && Object.keys(err).length > 0);
                },
                isMultiRowComplete(row) {
                    if (!row || this.multiRowHasError(row)) return false;
                    if (!String(row.name || row.article || '').trim()) return false;
                    if (!String(row.category || '').trim()) return false;
                    if (!String(row.brand || '').trim()) return false;
                    if (!String(row.model || '').trim()) return false;
                    const qty = Number(row.quantity);
                    if (!Number.isInteger(qty) || qty < 1) return false;
                    if (isStockPage) {
                        if (this.rowDeploying(row) && !String(row.deployRoom || '').trim()) return false;
                        if (!this.rowDeploying(row) && !String(row.room || this.room || '').trim()) return false;
                    }
                    return true;
                },
                applyExpressDefaultsToRow(row, forceTracking = true) {
                    if (!row) return;
                    if (!String(row.category || '').trim() && !row.categoryManual && typeof detectEquipmentCategoryId === 'function') {
                        const detected = detectEquipmentCategoryId(row.name || row.article);
                        if (detected) row.category = String(detected);
                    }
                    if (forceTracking || !row.tracking) {
                        row.tracking = this.rowAllowsBulk(row) ? 'Bulk' : 'Individual';
                    } else if (!this.rowAllowsBulk(row)) {
                        row.tracking = 'Individual';
                    }
                    if ((row.cost === '' || row.cost == null) && row.unit_cost != null) {
                        row.cost = String(row.unit_cost);
                    }
                    this.applyRisBrand(row);
                },
                expandedBrandNames(presets) {
                    const names = [];
                    (presets || []).forEach((preset) => {
                        const name = String((preset && preset.name) || '').trim();
                        if (!name) return;
                        const count = Math.max(1, Number(preset.quantity) || 1);
                        for (let i = 0; i < count && names.length < 200; i++) names.push(name);
                    });
                    return names;
                },
                risBrandCount(row) {
                    return new Set(this.expandedBrandNames(row?.brand_presets)).size;
                },
                applyRisBrand(row) {
                    const names = this.expandedBrandNames(row.brand_presets);
                    if (!names.length) return;
                    const unique = [...new Set(names)];
                    row.brand = unique.length === 1 ? unique[0] : names[0];
                    row._brandFromRis = true;
                    row._brandManual = false;
                    if (unique.length < 2 || row.tracking !== 'Individual' || Number(row.quantity) <= 1 || row._piecesManual) return;
                    const qty = Math.min(200, Math.max(1, Number(row.quantity) || 1));
                    row.perPiece = true;
                    row.pieces = Array.from({ length: qty }, (_, i) => ({
                        brand: names[i] || row.brand || '',
                        model: (row.pieces && row.pieces[i] && row.pieces[i].model) || '',
                    }));
                },
                presetBrandAt(index) {
                    const names = this.expandedBrandNames(this.brandPresets);
                    if (new Set(names).size < 2) return '';
                    return names[index] || names[0] || '';
                },
                applyExpressDefaults() {
                    (this.rrMultiLines || []).forEach((row) => this.applyExpressDefaultsToRow(row, true));
                    this.applyRrTrackingLock();
                    this.ensureBrandModelOptions();
                },
                onMultiNameInput(row) {
                    if (row.categoryManual || typeof detectEquipmentCategoryId !== 'function') return;
                    const detected = detectEquipmentCategoryId(row.name);
                    if (detected) {
                        row.category = String(detected);
                        this.clearMultiError(row, 'category');
                        if (!this.rowAllowsBulk(row)) {
                            row.tracking = 'Individual';
                            this.syncPieces(row);
                        }
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
                    if (
                        field === 'category'
                        && (this.rrMultiLines || []).length
                        && this.rrMultiLines.every((line) => String(line.category || '').trim() !== '')
                    ) {
                        this.formError = '';
                        this.clearError('multi');
                    }
                },
                onRrLineChange() {
                    const line = this.selectedRrLine;
                    if (!line) {
                        this.purchaseDate = '';
                        this.purchaseCost = '';
                        this.brandPresets = [];
                        this.rrSerialNumbers = [];
                        this.serial = '';
                        this.applyRrTrackingLock();
                        return;
                    }
                    this.name = line.article || '';
                    this.onNameInput();
                    const qty = Number(line.remaining_qty != null ? line.remaining_qty : line.quantity) || 1;
                    this.quantity = Math.min(200, Math.max(1, qty));
                    this.purchaseDate = line.purchase_date || '';
                    this.purchaseCost = line.unit_cost != null ? String(line.unit_cost) : '';
                    this.brandPresets = Array.isArray(line.brand_presets) ? line.brand_presets : [];
                    this.rrSerialNumbers = Array.isArray(line.serial_numbers) ? line.serial_numbers : [];
                    this.serial = this.rrSerialNumbers[0] || line.serial_number || '';
                    this._brandManual = false;
                    if (line.brand) {
                        this.brand = line.brand;
                        this._brandFromRis = true;
                    } else {
                        this.brand = '';
                        this._brandFromRis = false;
                    }
                    if (line.replaces_equipment_id && !this.replacesId) {
                        this.replacesId = String(line.replaces_equipment_id);
                    }
                    this.clearError('quantity');
                    this.syncAssetTag();
                    this.applyRrTrackingLock();
                    this.ensureBrandModelOptions();
                    if (this.categoryAllowsBulk()) {
                        this.tracking = 'Bulk';
                        this.onSharedPhotoModeChange();
                    }
                },
                isConsumableCategory(categoryId) {
                    const name = String(this.categoryName(categoryId) || '').toLowerCase();
                    if (!name) return false;
                    return (this.consumableNeedles || []).some((needle) => name.includes(String(needle)));
                },
                isComputerEquipmentCategory(categoryId) {
                    const name = String(this.categoryName(categoryId) || '').toLowerCase();
                    return name.includes('computer equipment');
                },
                categoryAllowsBulk() {
                    return !this.isComputerEquipmentCategory(this.category);
                },
                categoryLocksIndividual() {
                    return this.isComputerEquipmentCategory(this.category);
                },
                rrLocksIndividual() {
                    return this.categoryLocksIndividual();
                },
                rowAllowsBulk(row) {
                    return !this.isComputerEquipmentCategory(row?.category);
                },
                applyRrTrackingLock() {
                    if (this.categoryLocksIndividual() && this.tracking !== 'Individual') {
                        this.tracking = 'Individual';
                        this.assetTagManual = false;
                        this.syncAssetTag();
                        this.onSharedPhotoModeChange();
                    }
                    (this.multiLines || []).forEach((row) => {
                        if (!this.rowAllowsBulk(row) && row.tracking !== 'Individual') {
                            row.tracking = 'Individual';
                            this.syncPieces(row);
                        }
                    });
                    (this.rrMultiLines || []).forEach((row) => {
                        if (!this.rowAllowsBulk(row) && row.tracking !== 'Individual') {
                            row.tracking = 'Individual';
                            this.syncPieces(row);
                        }
                    });
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
                    return this.isMulti();
                },
                maxUsefulLife() {
                    return {{ (int) \App\Support\EquipmentLifecycle::MAX_USEFUL_LIFE_YEARS }};
                },
                isInvalidUsefulLife(value) {
                    if (value === '' || value === null || value === undefined) return false;
                    const years = Number(value);
                    return !Number.isInteger(years) || years < 1 || years > this.maxUsefulLife();
                },
                clampUsefulLife(value) {
                    if (value === '' || value === null || value === undefined) return value;
                    const years = Number(value);
                    if (!Number.isFinite(years)) return value;
                    if (years > this.maxUsefulLife()) return this.maxUsefulLife();
                    if (years < 0) return 0;
                    return Math.trunc(years);
                },
                onUsefulLifeInput() {
                    this.usefulLifeYears = this.clampUsefulLife(this.usefulLifeYears);
                    if (this.isInvalidUsefulLife(this.usefulLifeYears)) {
                        this.errors = {
                            ...this.errors,
                            useful_life: 'Useful lifespan must be a whole number from 1 to ' + this.maxUsefulLife() + ' years.',
                        };
                    } else {
                        this.clearError('useful_life');
                    }
                },
                maxAddQty() {
                    if (isStockPage && this.intakeBasis !== 'non_procurement' && this.selectedRrLine) {
                        const rem = Number(this.selectedRrLine.remaining_qty ?? this.selectedRrLine.quantity) || 0;
                        return Math.max(1, Math.min(200, rem));
                    }
                    return Math.min(200, {{ (int) min(200, \App\Support\InputLimits::QTY) }});
                },
                onQuantityInput() {
                    const max = this.maxAddQty();
                    let qty = Number(this.quantity);
                    if (this.quantity === '' || this.quantity === null || this.quantity === undefined) {
                        this.clearError('quantity');
                        this.syncAssetTag();
                        this.onSharedPhotoModeChange();
                        return;
                    }
                    if (!Number.isFinite(qty)) {
                        this.quantity = '';
                        this.clearError('quantity');
                        return;
                    }
                    // Hard-cap to RR remaining / max with no subtitle — value at max is valid.
                    this.quantity = Math.max(1, Math.min(Math.trunc(qty), max));
                    this.clearError('quantity');
                    this.syncAssetTag();
                    this.onSharedPhotoModeChange();
                },
                clampQuantityToRr() {
                    if (!(isStockPage && this.intakeBasis !== 'non_procurement' && this.selectedRrLine)) return;
                    const max = this.maxAddQty();
                    if (Number(this.quantity) > max) {
                        this.quantity = max;
                    }
                },
                rowDeploying(row) {
                    return isStockPage && row && row.placement === 'deploy';
                },
                setRowPlacement(row, placement) {
                    if (!row) return;
                    row.placement = placement;
                    if (placement === 'storage') {
                        row.deployRoom = '';
                        row.custodianId = '';
                        row.replacesId = '';
                        this.clearMultiError(row, 'deploy');
                    } else if (!String(row.room || '').trim()) {
                        const first = document.querySelector('#add_equipment_room option:not([value=""])');
                        row.room = String(this.room || (first ? first.value : ''));
                    }
                    this.clearMultiError(row, 'room');
                },
                storageRoomName(roomId) {
                    if (isStockPage) return this.defaultStorageRoomName || 'Storage Room';
                    const id = String(roomId || '');
                    if (!id) return this.optionText('add_equipment_room');
                    const option = document.querySelector('#add_equipment_room option[value="' + id + '"]');
                    return option ? (option.text || '').trim() : this.optionText('add_equipment_room');
                },
                deploying() {
                    if (this.isMulti()) {
                        return isStockPage && (this.multiLines || []).some((row) => this.rowDeploying(row));
                    }
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
                        const roomLabel = item.placement === 'deploy'
                            ? this.deployRoomName(item.deploy_room_id)
                            : this.storageRoomName(this.room);
                        item.equipment_asset_tag = this.generateTag(roomLabel || this.storageRoomName(this.room), this.name);
                        if (force) item._tagManual = false;
                    });
                },
                onItemRoomChange(item) {
                    item._roomManual = true;
                    this.fillItemTags(false);
                },
                goToUnitSlide(index) {
                    const len = (this.items || []).length;
                    if (!len) {
                        this.activeUnitIndex = 0;
                        return;
                    }
                    this.activeUnitIndex = Math.max(0, Math.min(len - 1, Number(index) || 0));
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                prevUnitSlide() {
                    this.goToUnitSlide(this.activeUnitIndex - 1);
                },
                nextUnitSlide() {
                    this.goToUnitSlide(this.activeUnitIndex + 1);
                },
                setItemPlacement(item, placement) {
                    if (!item) return;
                    item.placement = placement;
                    if (placement !== 'deploy') {
                        item.deploy_room_id = '';
                        item.custodian_id = '';
                        item.replaces_equipment_id = '';
                    }
                    this.fillItemTags(false);
                },
                itemReplacementCandidates(item) {
                    if (!item) return [];
                    return this.filteredReplacementCandidatesFor(this.name, this.category, item.replaces_equipment_id, item.placement || 'storage', item.deploy_room_id || '');
                },
                syncItemReplacementSelection(item) {
                    if (!item || !item.replaces_equipment_id) return;
                    const allowed = this.itemReplacementCandidates(item);
                    if (!allowed.some((c) => String(c.equipment_id) === String(item.replaces_equipment_id))) {
                        item.replaces_equipment_id = '';
                    }
                },
                isUnitComplete(item) {
                    if (!item) return false;
                    if (!String(item.equipment_asset_tag || '').trim()) return false;
                    if (!String(item.equipment_serial_number || '').trim()) return false;
                    if (!String(item.equipment_brand_name || this.brand || '').trim()) return false;
                    if (!String(item.equipment_model || '').trim()) return false;
                    if (!String(item.equipment_warranty_expiration || '').trim()) return false;
                    const life = Number(item.equipment_useful_life_years);
                    if (!Number.isInteger(life) || life < 1 || life > this.maxUsefulLife()) return false;
                    if (item.placement === 'deploy' && !String(item.deploy_room_id || '').trim()) return false;
                    return true;
                },
                copyActiveUnitToAll() {
                    const src = (this.items || [])[this.activeUnitIndex];
                    if (!src) return;
                    this.items.forEach((item, idx) => {
                        if (idx === this.activeUnitIndex) return;
                        if (this.manualIntake() || !this._brandFromRis) {
                            item.equipment_brand_name = src.equipment_brand_name || '';
                            item._brandManual = true;
                        }
                        item.equipment_model = src.equipment_model || '';
                        item.equipment_warranty_expiration = src.equipment_warranty_expiration || '';
                        item._warrantyManual = true;
                        item.equipment_useful_life_years = src.equipment_useful_life_years || 5;
                        item._lifeManual = true;
                        item.placement = src.placement || 'storage';
                        item.deploy_room_id = src.placement === 'deploy' ? String(src.deploy_room_id || '') : '';
                        item._roomManual = src.placement === 'deploy';
                        item.custodian_id = src.placement === 'deploy' ? String(src.custodian_id || '') : '';
                        if (src.placement !== 'deploy') item.replaces_equipment_id = '';
                    });
                    this.fillItemTags(false);
                },
                syncTopLevelFromItems() {
                    if (this.isMulti() || !this.needsItemStep() || !(this.items || []).length) return;
                    const first = this.items[0];
                    if (first.equipment_brand_name) this.brand = first.equipment_brand_name;
                    if (first.equipment_model) this.model = first.equipment_model;
                    if (first.equipment_warranty_expiration) this.warranty = first.equipment_warranty_expiration;
                    if (first.equipment_useful_life_years) this.usefulLifeYears = first.equipment_useful_life_years;
                },
                multiUnits() {
                    return (this.multiLines || []).flatMap((row) => row.units || []);
                },
                multiUnitCount() {
                    return (this.multiLines || []).reduce((sum, row) => sum + (row.tracking === 'Bulk' ? 1 : (Number(row.quantity) || 0)), 0);
                },
                buildMultiUnits() {
                    this.multiLines.forEach((row) => {
                        const rowDeploy = this.rowDeploying(row);
                        const count = row.tracking === 'Bulk' ? 1 : Math.min(200, Math.max(1, Number(row.quantity) || 1));
                        const previous = Array.isArray(row.units) ? row.units : [];
                        previous.slice(count).forEach((unit) => {
                            if (unit._imagePreview) URL.revokeObjectURL(unit._imagePreview);
                        });
                        row.units = Array.from({ length: count }, (_, i) => {
                            const prev = previous[i] || {};
<<<<<<< HEAD
                            const roomManual = rowDeploy && !!prev._roomManual;
                            return {
                                asset_tag: prev._tagManual ? prev.asset_tag : '',
                                serial: prev.serial || '',
                                purchase_date: prev._purchaseManual ? (prev.purchase_date || '') : (row.purchase_date || this.purchaseDate || ''),
=======
                            const roomManual = deploying && !!prev._roomManual;
                            const presetSerial = (Array.isArray(row.serial_numbers) && row.serial_numbers[i])
                                || (i === 0 ? (row.serial_number || '') : '');
                            return {
                                asset_tag: prev._tagManual ? prev.asset_tag : '',
                                serial: prev.serial || presetSerial,
                                purchase_date: prev._purchaseManual ? (prev.purchase_date || '') : (this.purchaseDate || ''),
>>>>>>> bda3ca10f31195123df88d54c088ad0a09915eb6
                                cost: prev._costManual ? (prev.cost ?? '') : (row.cost || this.purchaseCost || ''),
                                useful_life_years: prev._lifeManual ? (prev.useful_life_years ?? '') : (row.useful_life_years || this.usefulLifeYears || ''),
                                warranty: prev._warrantyManual ? prev.warranty : (row.warranty || this.warranty || ''),
                                _purchaseManual: !!prev._purchaseManual,
                                _costManual: !!prev._costManual,
                                _lifeManual: !!prev._lifeManual,
                                _warrantyManual: !!prev._warrantyManual,
                                deploy_room_id: roomManual ? prev.deploy_room_id : (rowDeploy ? String(row.deployRoom || '') : ''),
                                _roomManual: roomManual,
                                custodian_id: prev._custodianManual ? prev.custodian_id : (this.rowCanAssign(row) ? String(row.custodianId || '') : ''),
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
                        const rowDeploy = this.rowDeploying(row);
                        (row.units || []).forEach((unit) => {
                            if (unit._tagManual && !force) return;
                            const roomLabel = rowDeploy
                                ? this.deployRoomName(unit.deploy_room_id || row.deployRoom)
                                : this.storageRoomName(row.room || this.room);
                            unit.asset_tag = this.generateTag(roomLabel, row.name);
                            if (force) unit._tagManual = false;
                        });
                    });
                },
                onMultiUnitRoomChange(unit) {
                    unit._roomManual = true;
                    this.fillMultiTags(false);
                },
                useDefaultRoomForAll() {
                    (this.multiLines || []).forEach((row) => {
                        if (!this.rowDeploying(row)) return;
                        (row.units || []).forEach((unit) => {
                            unit.deploy_room_id = String(row.deployRoom || '');
                            unit._roomManual = false;
                        });
                    });
                    this.fillMultiTags(false);
                },
                useDefaultCustodianForAll() {
                    (this.multiLines || []).forEach((row) => {
                        if (!this.rowCanAssign(row)) return;
                        (row.units || []).forEach((unit) => {
                            unit.custodian_id = String(row.custodianId || '');
                            unit._custodianManual = false;
                        });
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
                        if (!String(row.brand || '').trim()) {
                            this.formError = 'Brand name is required for ' + (row.name || row.article || 'each item') + '.';
                            return false;
                        }
                        if (!String(row.model || '').trim()) {
                            this.formError = 'Model is required for ' + (row.name || row.article || 'each item') + '.';
                            return false;
                        }
                        for (let i = 0; i < (row.units || []).length; i++) {
                            const unit = row.units[i];
                            const label = (row.name || row.article) + ' #' + (i + 1);
                            const tag = String(unit.asset_tag || '').trim();
                            const serial = String(unit.serial || '').trim();
                            const brand = String((this.hasPieces(row) ? this.pieceValues(row, i).brand : row.brand) || row.brand || '').trim();
                            const model = String((this.hasPieces(row) ? this.pieceValues(row, i).model : row.model) || row.model || '').trim();
                            const warranty = String(unit.warranty || row.warranty || this.warranty || '').trim();
                            const lifeRaw = unit.useful_life_years !== '' && unit.useful_life_years != null
                                ? unit.useful_life_years
                                : (row.useful_life_years !== '' && row.useful_life_years != null ? row.useful_life_years : this.usefulLifeYears);
                            const life = Number(lifeRaw);
                            const costRaw = unit.cost !== '' && unit.cost != null
                                ? unit.cost
                                : (row.cost !== '' && row.cost != null ? row.cost : this.purchaseCost);
                            if (!tag) {
                                this.formError = 'Asset tag is required for ' + label + '.';
                                return false;
                            }
                            if (!serial) {
                                this.formError = 'Serial number is required for ' + label + '.';
                                return false;
                            }
                            if (!brand) {
                                this.formError = 'Brand name is required for ' + label + '.';
                                return false;
                            }
                            if (!model) {
                                this.formError = 'Model is required for ' + label + '.';
                                return false;
                            }
                            if (!warranty) {
                                this.formError = 'Warranty expiration is required for ' + label + '.';
                                return false;
                            }
                            if (!Number.isInteger(life) || life < 1 || life > this.maxUsefulLife()) {
                                this.formError = 'Useful lifespan for ' + label + ' must be a whole number from 1 to ' + this.maxUsefulLife() + ' years.';
                                return false;
                            }
                            if (costRaw === '' || costRaw === null || costRaw === undefined || !Number.isFinite(Number(costRaw)) || Number(costRaw) < 0) {
                                this.formError = 'Cost is required for ' + label + '.';
                                return false;
                            }
                            const tagKey = tag.toLowerCase();
                            const serialKey = serial.toLowerCase();
                            if (tags[tagKey]) {
                                this.formError = 'Asset tag "' + tag + '" is used by both ' + tags[tagKey] + ' and ' + label + '.';
                                return false;
                            }
                            tags[tagKey] = label;
                            if (serials[serialKey]) {
                                this.formError = 'Serial number "' + serial + '" is used by both ' + serials[serialKey] + ' and ' + label + '.';
                                return false;
                            }
                            serials[serialKey] = label;
                            if (this.isFutureDate(unit.purchase_date)) {
                                this.formError = 'Purchase date for ' + label + ' cannot be in the future.';
                                return false;
                            }
                            if (this.rowDeploying(row) && !String(unit.deploy_room_id || row.deployRoom || '').trim()) {
                                this.formError = 'Choose a room for ' + label + '.';
                                return false;
                            }
                            // Persist resolved values so server validation receives them.
                            unit.warranty = warranty;
                            unit.useful_life_years = life;
                            unit.cost = costRaw;
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
                syncCatalogOption(selectEl, value) {
                    const label = String(value || '').trim();
                    if (!selectEl || !label) return;
                    const addUrl = selectEl.dataset?.addUrl || '';
                    document.querySelectorAll('select[data-searchable="1"]').forEach((peer) => {
                        if (peer !== selectEl && (peer.dataset.addUrl || '') !== addUrl) return;
                        if (!Array.from(peer.options).some((opt) => opt.value === label)) {
                            peer.add(new Option(label, label));
                        }
                    });
                    if (selectEl.value !== label) {
                        selectEl.value = label;
                    }
                },
                ensureBrandModelOptions() {
                    this.$nextTick(() => {
                        const nameEl = document.getElementById('add_equipment_name');
                        const brandEl = document.getElementById('add_equipment_brand');
                        const modelEl = document.getElementById('add_equipment_model');
                        if (nameEl) this.syncCatalogOption(nameEl, this.name);
                        if (brandEl) this.syncCatalogOption(brandEl, this.brand);
                        if (modelEl) this.syncCatalogOption(modelEl, this.model);
                        (this.multiLines || []).forEach((row) => {
                            const n = document.getElementById('multi_name_' + row.id);
                            const b = document.getElementById('multi_brand_' + row.id);
                            const m = document.getElementById('multi_model_' + row.id);
                            if (n) this.syncCatalogOption(n, row.name);
                            if (b) this.syncCatalogOption(b, row.brand);
                            if (m) this.syncCatalogOption(m, row.model);
                        });
                        if (window.mpSelect?.scan) window.mpSelect.scan();
                    });
                },
                clearErrors() {
                    this.errors = {};
                    this.formError = '';
                },
                manualIntake() {
                    return !isStockPage || this.intakeBasis === 'non_procurement';
                },
                rowCanAssign(row) {
                    return this.rowDeploying(row)
                        && row.tracking === 'Individual'
                        && (!this.isMultiManual() || row.condition !== 'Disposed');
                },
                canAssignPerson() {
                    if (!isStockPage) return false;
                    if (this.isMulti()) return (this.multiLines || []).some((row) => this.rowCanAssign(row));
                    if (this.placement !== 'deploy') return false;
                    return this.tracking === 'Individual' && this.condition !== 'Disposed';
                },
                unitCanAssign(row) {
                    return this.rowCanAssign(row);
                },
                unitAssignHint(row) {
                    if (!this.rowDeploying(row)) return 'Stays in storage';
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
                        steps.push({ key: 'p1', label: 'Source', hint: 'Select a delivered Receiving Report line or non-procurement source.' });
                    }
                    steps.push({
                        key: 'p2',
                        label: 'Details & placement',
                        hint: 'Confirm fetched procurement fields, fill identity & warranty details, and set placement.',
                    });
                    if (this.hasUnitsStep()) {
                        steps.push({
                            key: 'units',
                            label: 'Units',
                            hint: this.deploying()
                                ? 'Asset tag, serial number, optional photo, and the room each unit goes to.'
                                : 'Unique asset tag, serial number, and optional photo per unit.',
                        });
                    }
                    return steps;
                },
                currentStepKey() {
                    if (this.step === 2) return 'units';
                    return 'p' + this.page;
                },
                currentStepIndex() {
                    return Math.max(0, this.wizardSteps().findIndex((s) => s.key === this.currentStepKey()));
                },
                currentStep() {
                    return this.wizardSteps()[this.currentStepIndex()] || { hint: '' };
                },
                markFurthest() {
                    const max = Math.max(0, this.wizardSteps().length - 1);
                    this.furthestStepIndex = Math.min(max, Math.max(this.furthestStepIndex, this.currentStepIndex()));
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
                    if (target < 0 || target > this.furthestStepIndex || target === this.currentStepIndex()) return;
                    this.clearErrors();
                    if (key === 'units') {
                        this.step = 2;
                    } else {
                        this.step = 1;
                        this.page = Number(key.slice(1));
                    }
                    this.markFurthest();
                    this.scrollToTop();
                },
                next() {
                    if (this.step === 1) {
                        if (!this.validatePage(this.page)) return;
                        if (this.page === 1 && this.intakeBasis !== 'non_procurement' && (this.rrSelected || []).length) {
                            this.syncMultiLines();
                            this.applyExpressDefaults();
                            if (this.rrSelected.length === 1) {
                                this.onRrLineChange();
                            }
                        }
                        if (this.page < 2) {
                            this.page = 2;
                            if (!this.isMulti() && this.needsItemStep()) {
                                this.buildItems();
                            }
                            this.markFurthest();
                            this.scrollToTop();
                            return;
                        }
                        if (this.hasUnitsStep()) {
                            this.goToItems();
                            return;
                        }
                        this.saveNow();
                        return;
                    }
                    if (this.step === 2) {
                        if (!(this.isMulti() ? this.validateMultiUnits() : this.validateItems())) return;
                        this.saveNow();
                    }
                },
                back() {
                    this.clearErrors();
                    if (this.step === 2) {
                        this.step = 1;
                        this.page = 2;
                    } else if (this.page > firstPage) {
                        this.page = firstPage;
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
                    if (page === 2) {
                        if (this.isMulti()) {
                            if (this.isMultiRr() && !this.multiLines.length) this.syncMultiLines();
                            const rowErrors = {};
                            this.multiLines.forEach((row) => {
                                const rowError = {};
                                if (!String(row.name || '').trim()) rowError.name = 'Equipment name is required.';
                                if (!String(row.category || '').trim()) rowError.category = 'Please select a classification.';
                                if (!String(row.brand || '').trim()) rowError.brand = 'Brand name is required.';
                                if (!String(row.model || '').trim()) rowError.model = 'Model is required.';
                                const qty = Number(row.quantity);
                                if (!Number.isInteger(qty) || qty < 1) {
                                    rowError.quantity = 'Quantity must be at least 1.';
                                } else if (qty > row.max) {
                                    rowError.quantity = this.isMultiManual()
                                        ? 'Quantity cannot exceed ' + row.max + '.'
                                        : 'Only ' + row.max + ' left on this RR line.';
                                }
                                if (isStockPage) {
                                    if (this.rowDeploying(row)) {
                                        if (!String(row.deployRoom || '').trim()) {
                                            rowError.deploy = 'Choose the room to deploy to.';
                                        }
                                    } else if (!String(row.room || this.room || '').trim()) {
                                        rowError.room = 'Please select a storage room.';
                                    }
                                }
                                if (Object.keys(rowError).length) rowErrors[row.id] = rowError;
                            });
                            this.multiErrors = rowErrors;
                            const count = Object.keys(rowErrors).length;
                            if (count) {
                                const firstErrIdx = this.multiLines.findIndex((row) => !!rowErrors[row.id]);
                                if (firstErrIdx >= 0) {
                                    this.activeMultiIndex = firstErrIdx;
                                }
                                next.multi = count === 1
                                    ? 'Fix the highlighted item before continuing.'
                                    : ('Fix the ' + count + ' highlighted items before continuing.');
                            }
                        } else {
                            if (!String(this.name || '').trim()) {
                                next.name = 'Equipment name is required.';
                            }
                            if (!String(this.category || '').trim()) {
                                next.category = 'Please select a classification.';
                            }
                            if (!this.needsItemStep()) {
                                if (!String(this.brand || '').trim()) {
                                    next.brand = 'Brand name is required.';
                                }
                                if (!String(this.model || '').trim()) {
                                    next.model = 'Model is required.';
                                }
                                if (!String(this.assetTag || '').trim()) {
                                    next.asset_tag = 'Asset tag is required.';
                                }
                                if (!String(this.serial || '').trim()) {
                                    next.serial = 'Serial number is required.';
                                }
                            }
                            const qty = Number(this.quantity);
                            const maxQty = this.maxAddQty();
                            if (!Number.isFinite(qty) || qty < 1) {
                                next.quantity = 'Quantity must be at least 1.';
                            } else if (qty > maxQty) {
                                next.quantity = isStockPage && this.intakeBasis !== 'non_procurement'
                                    ? ('Quantity cannot exceed ' + maxQty + ' left on this RR line.')
                                    : ('Quantity cannot exceed ' + maxQty + '.');
                            }
                        }

                        if (this.manualIntake()) {
                            const purchaseLabel = this.acqSource === 'donation' ? 'Donation date' : 'Purchase date';
                            if (this.isFutureDate(this.purchaseDate)) {
                                next.purchase_date = purchaseLabel + ' cannot be in the future.';
                            }
                            if (this.isFutureDate(this.receivedDate)) {
                                next.received_date = 'Received on cannot be in the future.';
                            } else if (this.isBeforeDate(this.receivedDate, this.purchaseDate)) {
                                next.received_date = 'Received on cannot be before the ' + purchaseLabel.toLowerCase() + '.';
                            }
                        }
                        const lifeMax = this.maxUsefulLife();
                        if (this.isMulti()) {
                            const missingWarranty = this.multiLines.find((line) => !String(line.warranty || '').trim());
                            if (missingWarranty) {
                                next.warranty = 'Warranty expiration is required for ' + (missingWarranty.name || missingWarranty.article || 'each item') + '.';
                            }
                            const badLife = this.multiLines.find((line) => {
                                if (line.useful_life_years === '' || line.useful_life_years === null || line.useful_life_years === undefined) return true;
                                const years = Number(line.useful_life_years);
                                return !Number.isInteger(years) || years < 1 || years > lifeMax;
                            });
                            if (badLife) {
                                next.useful_life = 'Lifespan for ' + (badLife.name || badLife.article || 'each item') + ' must be a whole number from 1 to ' + lifeMax + ' years.';
                            }
                        } else if (!this.needsItemStep()) {
                            if (!String(this.warranty || '').trim()) {
                                next.warranty = 'Warranty expiration is required.';
                            }
                            const lifeMissing = this.usefulLifeYears === '' || this.usefulLifeYears === null || this.usefulLifeYears === undefined;
                            const life = Number(this.usefulLifeYears);
                            if (lifeMissing || !Number.isInteger(life) || life < 1 || life > lifeMax) {
                                next.useful_life = 'Useful lifespan must be a whole number from 1 to ' + lifeMax + ' years.';
                            }
                        }
                        if (this.isMulti()) {
                            const row = this.multiLines.find((line) => {
                                const cost = line.cost !== '' && line.cost != null ? line.cost : line.unit_cost;
                                return cost === '' || cost === null || cost === undefined
                                    || !Number.isFinite(Number(cost)) || Number(cost) < 0;
                            });
                            if (row) next.purchase_cost = 'Cost for ' + (row.name || 'an item') + ' is required.';
                        } else if (this.purchaseCost === '' || this.purchaseCost === null || this.purchaseCost === undefined || !Number.isFinite(Number(this.purchaseCost)) || Number(this.purchaseCost) < 0) {
                            next.purchase_cost = 'Cost per unit is required.';
                        }

                        if (!this.isMulti()) {
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
                        } else if (!String(this.room || '').trim()) {
                            const firstRowRoom = (this.multiLines || []).find((r) => String(r.room || '').trim())?.room;
                            const firstOpt = document.querySelector('#add_equipment_room option:not([value=""])');
                            this.room = String(firstRowRoom || (firstOpt ? firstOpt.value : ''));
                        }
                    }
                    return next;
                },
                pageForErrors() {
                    const keys = Object.keys(this.errors || {});
                    if (isStockPage && keys.some((k) => ['rr', 'intake_reason', 'acq_source'].includes(k))) return 1;
                    if (keys.length) return 2;
                    return firstPage;
                },
                validatePage(page) {
                    return this.applyErrors(this.pageErrors(page));
                },
                validateStep1() {
                    for (let page = firstPage; page <= 2; page++) {
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
                        const item = this.items[i];
                        const row = i + 1;
                        const tag = String(item.equipment_asset_tag || '').trim();
                        const serial = String(item.equipment_serial_number || '').trim();
                        const brand = String(item.equipment_brand_name || this.brand || '').trim();
                        const model = String(item.equipment_model || this.model || '').trim();
                        const warranty = String(item.equipment_warranty_expiration || this.warranty || '').trim();
                        const lifeRaw = item.equipment_useful_life_years !== '' && item.equipment_useful_life_years != null
                            ? item.equipment_useful_life_years
                            : this.usefulLifeYears;
                        const life = Number(lifeRaw);
                        const costRaw = item.equipment_purchase_cost !== '' && item.equipment_purchase_cost != null
                            ? item.equipment_purchase_cost
                            : this.purchaseCost;
                        if (!tag) {
                            this.activeUnitIndex = i;
                            this.formError = `Asset tag is required on Unit ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (!serial) {
                            this.activeUnitIndex = i;
                            this.formError = `Serial number is required on Unit ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (!brand) {
                            this.activeUnitIndex = i;
                            this.formError = `Brand name is required on Unit ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (!model) {
                            this.activeUnitIndex = i;
                            this.formError = `Model is required on Unit ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (!warranty) {
                            this.activeUnitIndex = i;
                            this.formError = `Warranty expiration is required on Unit ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (item.placement === 'deploy' && !String(item.deploy_room_id || '').trim()) {
                            this.activeUnitIndex = i;
                            this.formError = `Choose a room to deploy to on Unit ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (!Number.isInteger(life) || life < 1 || life > this.maxUsefulLife()) {
                            this.formError = `Useful lifespan on row ${row} must be a whole number from 1 to ${this.maxUsefulLife()} years.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        if (costRaw === '' || costRaw === null || costRaw === undefined || !Number.isFinite(Number(costRaw)) || Number(costRaw) < 0) {
                            this.formError = `Cost is required on row ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        const tagKey = tag.toLowerCase();
                        const serialKey = serial.toLowerCase();
                        if (tags[tagKey] !== undefined) {
                            this.formError = `Duplicate asset tag on rows ${tags[tagKey] + 1} and ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        tags[tagKey] = i;
                        if (serials[serialKey] !== undefined) {
                            this.formError = `Duplicate serial number on rows ${serials[serialKey] + 1} and ${row}.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        serials[serialKey] = i;
                        if (this.isFutureDate(item.equipment_purchase_date)) {
                            this.formError = `Purchase date on row ${row} cannot be in the future.`;
                            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                            return false;
                        }
                        item.equipment_brand_name = brand;
                        item.equipment_model = model;
                        item.equipment_warranty_expiration = warranty;
                        item.equipment_useful_life_years = life;
                        item.equipment_purchase_cost = costRaw;
                    }
                    this.formError = '';
                    return true;
                },
                onNameInput() {
                    if (!String(this.name || '').trim()) {
                        this.categoryManual = false;
                        this.category = '';
                        this.applyRrTrackingLock();
                        return;
                    }
                    if (this.categoryManual) {
                        this.applyRrTrackingLock();
                        return;
                    }
                    if (typeof detectEquipmentCategoryId === 'function') {
                        this.category = detectEquipmentCategoryId(this.name) || '';
                        if (this.category) this.clearError('category');
                    }
                    this.applyRrTrackingLock();
                    if (
                        isStockPage
                        && this.intakeBasis !== 'non_procurement'
                        && this.categoryAllowsBulk()
                        && this.tracking !== 'Bulk'
                    ) {
                        this.tracking = 'Bulk';
                        this.onSharedPhotoModeChange();
                    }
                },
                onCategoryChange() {
                    if (
                        typeof detectEquipmentCategoryId === 'function'
                        && String(this.category) === String(detectEquipmentCategoryId(this.name) || '')
                    ) {
                        this.applyRrTrackingLock();
                        return;
                    }
                    this.categoryManual = true;
                    this.applyRrTrackingLock();
                },
                reset() {
                    this.reviewOpen = false;
                    this.reviewConfirmed = false;
                    this.reviewSlideIndex = 0;
                    this.step = 1;
                    this.page = firstPage;
                    this.furthestStepIndex = 0;
                    this.placement = 'storage';
                    this.deployRoom = '';
                    this.custodianId = '';
                    this.replacesId = '';
                    this.stockAndLinkMode = false;
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
                    this.usefulLifeYears = 5;
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
                    this.activeMultiIndex = 0;
                    this.activeUnitIndex = 0;
                    this.multiErrors = {};
                    this.submitting = false;
                    this.purchaseDate = '';
                    this.purchaseCost = '';
                    this.brandPresets = [];
                    this.rrSerialNumbers = [];
                    this._brandFromRis = false;
                    this._brandManual = false;
                    this.clearAllItemImages();
                    this.items = [];
                    this.clearImage();
                    this.clearErrors();
                },
                show(rrItemId = null, options = {}) {
                    this.reset();
                    @if (old('equipment_room_id'))
                        this.room = @json((string) old('equipment_room_id'));
                    @endif
                    this.stockAndLinkMode = !!(options && options.stockAndLink);
                    if (options && options.replacesId != null && String(options.replacesId) !== '') {
                        this.replacesId = String(options.replacesId);
                    }
                    this.open = true;
                    this.loadRrLines().then(() => {
                        if (!rrItemId) return;
                        const line = (this.rrGuide || []).find((row) => String(row.id) === String(rrItemId));
                        if (line) {
                            this.selectRrLine(line);
                            if (!this.replacesId && line.replaces_equipment_id) {
                                this.replacesId = String(line.replaces_equipment_id);
                            }
                        }
                        this.applyRrTrackingLock();
                    });
                    this.$nextTick(() => {
                        document.getElementById('add_equipment_name')?.dispatchEvent(new Event('equipment-category-reset'));
                        if (window.lucide) window.lucide.createIcons();
                    });
                },
                filteredReplacementCandidatesFor(rawName, rawCategory, rawSelectedId = '', rawPlacement = 'storage', rawDeployRoomId = '') {
                    const all = Array.isArray(this.replacementCandidates) ? this.replacementCandidates : [];
                    const targetName = String(rawName || '').trim().toLowerCase();
                    const targetCategory = String(rawCategory || '').trim();
                    const selectedId = String(rawSelectedId || '').trim();
                    const isDeploy = rawPlacement === 'deploy';
                    const deployRoomId = String(rawDeployRoomId || '').trim();

                    if (!isDeploy && !this.stockAndLinkMode) {
                        return [];
                    }
                    if (isDeploy && !deployRoomId) {
                        return [];
                    }

                    return all.filter((candidate) => {
                        if (this.stockAndLinkMode && selectedId && String(candidate.equipment_id) === selectedId) {
                            return true;
                        }
                        if (isDeploy && deployRoomId) {
                            if (String(candidate.equipment_room_id || '').trim() !== deployRoomId) {
                                return false;
                            }
                        }
                        const candName = String(candidate.equipment_name || '').trim().toLowerCase();
                        const candCat = String(candidate.equipment_category_id || '').trim();

                        if (targetName) {
                            const nameMatch = candName === targetName
                                || candName.includes(targetName)
                                || targetName.includes(candName);
                            if (!nameMatch) return false;
                            if (targetCategory && candCat && candCat !== targetCategory) return false;
                            return true;
                        }
                        if (targetCategory) {
                            return candCat === targetCategory;
                        }
                        return true;
                    });
                },
                filteredReplacementCandidates() {
                    return this.filteredReplacementCandidatesFor(this.name, this.category, this.replacesId, this.placement, this.deployRoom);
                },
                rowReplacementCandidates(row) {
                    if (!row) return [];
                    return this.filteredReplacementCandidatesFor(row.name || row.article, row.category, row.replacesId, row.placement, row.deployRoom);
                },
                syncRowReplacementSelection(row) {
                    if (!row || !row.replacesId) return;
                    const allowed = this.rowReplacementCandidates(row);
                    if (!allowed.some((c) => String(c.equipment_id) === String(row.replacesId))) {
                        row.replacesId = '';
                    }
                },
                syncReplacementSelection() {
                    if (!this.replacesId || this.stockAndLinkMode) return;
                    const allowed = this.filteredReplacementCandidates();
                    if (!allowed.some((c) => String(c.equipment_id) === String(this.replacesId))) {
                        this.replacesId = '';
                    }
                },
                hasUnsavedChanges() {
                    if (this.step > 1 || this.page > firstPage) return true;
                    if ((this.rrSelected || []).length > 0) return true;
                    if (this.intakeBasis === 'non_procurement') return true;
                    if (this.placement !== 'storage') return true;
                    if (String(this.name || '').trim() !== '') return true;
                    if (String(this.category || '').trim() !== '') return true;
                    if (String(this.brand || '').trim() !== '') return true;
                    if (String(this.model || '').trim() !== '') return true;
                    if (String(this.serial || '').trim() !== '') return true;
                    if (String(this.warranty || '').trim() !== '') return true;
                    if (String(this.deployRoom || '').trim() !== '') return true;
                    if (String(this.custodianId || '').trim() !== '') return true;
                    if (String(this.replacesId || '').trim() !== '' && !this.stockAndLinkMode) return true;
                    if (String(this.intakeReason || '').trim() !== '') return true;
                    if (String(this.acqSource || '').trim() !== '') return true;
                    if (this.assetTagManual) return true;
                    if (this.imagePreview) return true;
                    if ((this.manualLines || []).length > 0) return true;
                    return false;
                },
                requestClose() {
                    if (this.submitting) return;
                    if (!this.hasUnsavedChanges()) {
                        this.close();
                        return;
                    }
                    this.discardPromptOpen = true;
                },
                close() {
                    this.discardPromptOpen = false;
                    this.showUnitOverrides = false;
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
                    if (deploying) return this.optionText('add_deploy_room');
                    return isStockPage ? (this.defaultStorageRoomName || 'Storage Room') : this.optionText('add_equipment_room');
                },
                optionText(selectId) {
                    if (isStockPage && selectId === 'add_equipment_room') {
                        return this.defaultStorageRoomName || 'Storage Room';
                    }
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
                        ['Classification', this.optionText('add_equipment_category') || '—'],
                        ['Brand / model', [this.brand, this.model].filter(Boolean).join(' · ') || '—'],
                        ['Quantity', this.quantity + ' · ' + this.tracking],
                        ['Condition', this.manualIntake() ? this.condition : 'Good (new delivery)'],
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
                            ['Suppliers', unique(lines.map((line) => line.supplier_name)) || '—'],
                            ['RR lines', String(lines.length)],
                        ];
                    }
                    if (!this.manualIntake()) {
                        const line = this.selectedRrLine;
                        return [
                            ['Acquired via', 'Procurement (Receiving Report)'],
                            ['Receiving report', line?.rr_number || '—'],
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

                    const lockedBrand = !this.manualIntake() && this._brandFromRis;
                    this.items = Array.from({ length: qty }, (_, i) => {
                        const prev = previous[i] || {};
<<<<<<< HEAD
                        const unitPlacement = prev.placement || (deploying ? 'deploy' : 'storage');
                        const roomManual = unitPlacement === 'deploy' && !!prev._roomManual;
=======
                        const roomManual = deploying && !!prev._roomManual;
                        const presetSerial = (Array.isArray(this.rrSerialNumbers) && this.rrSerialNumbers[i]) || '';
>>>>>>> bda3ca10f31195123df88d54c088ad0a09915eb6
                        return {
                            equipment_asset_tag: prev._tagManual ? prev.equipment_asset_tag : '',
                            equipment_serial_number: prev.equipment_serial_number || presetSerial,
                            equipment_brand_name: lockedBrand
                                ? (this.presetBrandAt(i) || this.brand || '')
                                : (prev._brandManual
                                    ? (prev.equipment_brand_name || '')
                                    : (this.presetBrandAt(i) || this.brand || '')),
                            _brandManual: lockedBrand ? false : !!prev._brandManual,
                            equipment_model: prev.equipment_model ?? (this.model || ''),
                            equipment_condition_status: this.manualIntake() ? (prev.equipment_condition_status || this.condition) : 'Good',
                            equipment_purchase_date: prev._purchaseManual ? (prev.equipment_purchase_date || '') : (this.purchaseDate || ''),
                            equipment_purchase_cost: prev._costManual ? (prev.equipment_purchase_cost ?? '') : (this.purchaseCost ?? ''),
                            equipment_useful_life_years: prev._lifeManual ? (prev.equipment_useful_life_years ?? 5) : (this.usefulLifeYears || 5),
                            equipment_warranty_expiration: prev._warrantyManual ? (prev.equipment_warranty_expiration || '') : (this.warranty || ''),
                            placement: unitPlacement,
                            deploy_room_id: roomManual ? prev.deploy_room_id : (unitPlacement === 'deploy' ? String(this.deployRoom || '') : ''),
                            custodian_id: prev.custodian_id || '',
                            replaces_equipment_id: prev.replaces_equipment_id || '',
                            _roomManual: roomManual,
                            _tagManual: prev._tagManual || false,
                            _purchaseManual: !!prev._purchaseManual,
                            _costManual: !!prev._costManual,
                            _lifeManual: !!prev._lifeManual,
                            _warrantyManual: !!prev._warrantyManual,
                            _imagePreview: prev._imagePreview || null,
                            _imageFile: prev._imageFile || null,
                        };
                    });
                    if (this.activeUnitIndex >= this.items.length) {
                        this.activeUnitIndex = Math.max(0, this.items.length - 1);
                    }
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
                        equipment_purchase_date: this.purchaseDate || '',
                        equipment_purchase_cost: this.purchaseCost ?? '',
                        equipment_useful_life_years: this.usefulLifeYears ?? '',
                        equipment_warranty_expiration: this.warranty || '',
                        deploy_room_id: deploying ? String(this.deployRoom || '') : '',
                        _roomManual: false,
                        _purchaseManual: false,
                        _costManual: false,
                        _lifeManual: false,
                        _warrantyManual: false,
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
                        this.markFurthest();
                        this.clearErrors();
                        this.scrollToTop();
                        return;
                    }
                    this.clearImage();
                    this.buildItems();
                    this.step = 2;
                    this.markFurthest();
                    this.clearErrors();
                    this.$nextTick(() => {
                        this.restoreItemImageInputs();
                        if (window.lucide) window.lucide.createIcons();
                    });
                    this.scrollToTop();
                },
                canSaveNow() {
                    if (this.submitting) return false;
                    if (this.step === 2) return true;
                    return this.step === 1 && this.page === 2 && !this.hasUnitsStep();
                },
                canExpressResume() {
                    if (!isStockPage || this.submitting) return false;
                    if (this.intakeBasis === 'non_procurement') return false;
                    if (this.step !== 1 || this.page !== 2) return false;
                    if (!(this.rrSelected || []).length) return false;
                    if (!(this.rrMultiLines || []).length) return false;
                    return this.rrMultiLines.every((row) => String(row.category || '').trim() !== '');
                },
                replacementCandidateLabel(id) {
                    const target = String(id || '').trim();
                    if (!target) return 'Not a replacement';
                    const found = (this.replacementCandidates || []).find((c) => String(c.equipment_id) === target);
                    if (!found) return 'Not a replacement';
                    const parts = [found.equipment_name || 'Equipment'];
                    if (found.equipment_asset_tag) parts.push(found.equipment_asset_tag);
                    if (found.room_name) parts.push(found.room_name);
                    return parts.join(' · ');
                },
                reviewTotalBadge() {
                    const slides = this.reviewSlides();
                    if (this.isMulti()) {
                        const totalUnits = this.multiLines.reduce((sum, row) => sum + (Number(row.quantity) || 0), 0);
                        return this.multiLines.length + ' items · ' + totalUnits + (totalUnits === 1 ? ' unit' : ' units');
                    }
                    if (this.needsItemStep()) {
                        return slides.length + (slides.length === 1 ? ' asset' : ' assets');
                    }
                    const qty = Number(this.quantity) || 1;
                    return qty > 1 ? ('1 bulk record · Qty ' + qty) : '1 asset';
                },
                reviewSlides() {
                    if (this.isMulti()) {
                        const slides = [];
                        (this.multiLines || []).forEach((row, rowIndex) => {
                            const units = Array.isArray(row.units) && row.units.length ? row.units : [{}];
                            const isBulk = row.tracking === 'Bulk';
                            const deploying = this.rowDeploying(row);
                            const baseName = row.name || row.article || ('Item ' + (rowIndex + 1));
                            units.forEach((unit, unitIndex) => {
                                const brandVal = this.hasPieces(row)
                                    ? (this.pieceValues(row, unitIndex).brand || row.brand || '—')
                                    : (row.brand || '—');
                                const modelVal = this.hasPieces(row)
                                    ? (this.pieceValues(row, unitIndex).model || row.model || '—')
                                    : (row.model || '—');
                                const rawCost = unit.cost !== '' && unit.cost != null ? unit.cost : row.cost;
                                const rawLife = unit.useful_life_years !== '' && unit.useful_life_years != null
                                    ? unit.useful_life_years
                                    : (row.useful_life_years || 5);
                                const custodianId = unit.custodian_id || row.custodianId;
                                const label = isBulk
                                    ? (baseName + ' ×' + (Number(row.quantity) || 1))
                                    : (units.length > 1 ? (baseName + ' #' + (unitIndex + 1)) : baseName);
                                slides.push({
                                    key: 'm-' + row.id + '-' + unitIndex,
                                    label,
                                    name: baseName,
                                    classification: this.categoryName(row.category) || '—',
                                    tracking: isBulk ? ('Bulk · Qty ' + (Number(row.quantity) || 1)) : 'Individual',
                                    condition: this.isMultiRr() ? 'Good (new delivery)' : (row.condition || 'Good'),
                                    brand: brandVal,
                                    model: modelVal,
                                    asset_tag: unit.asset_tag || '—',
                                    serial: unit.serial || '—',
                                    cost: this.formatPeso(rawCost),
                                    purchase_date: unit.purchase_date || row.purchase_date || this.purchaseDate || '—',
                                    warranty: unit.warranty || row.warranty || this.warranty || '—',
                                    lifespan: rawLife ? (rawLife + ' years') : '5 years',
                                    deploying,
                                    room: deploying
                                        ? (this.deployRoomName(unit.deploy_room_id || row.deployRoom) || '—')
                                        : (this.storageRoomName(row.room || this.room) || 'Storage Room'),
                                    custodian: (this.rowCanAssign(row) && custodianId)
                                        ? (this.custodianName(custodianId) || 'No one yet')
                                        : 'No one yet',
                                    replaces: this.replacementCandidateLabel(row.replacesId),
                                    received_on: this.isMultiManual() ? (this.receivedDate || '—') : 'Today (date added)',
                                    image: unit._imagePreview || null,
                                    source: row.source || '',
                                    editStep: 'p2',
                                    editMultiIndex: rowIndex,
                                    editUnitIndex: null,
                                });
                            });
                        });
                        return slides;
                    }

                    if (this.needsItemStep() && Array.isArray(this.items) && this.items.length) {
                        return this.items.map((item, idx) => {
                            const deploying = item.placement === 'deploy';
                            const rawCost = item.equipment_purchase_cost !== '' && item.equipment_purchase_cost != null
                                ? item.equipment_purchase_cost
                                : this.purchaseCost;
                            const rawLife = item.equipment_useful_life_years !== '' && item.equipment_useful_life_years != null
                                ? item.equipment_useful_life_years
                                : (this.usefulLifeYears || 5);
                            return {
                                key: 'u-' + idx,
                                label: 'Unit ' + (idx + 1),
                                name: this.name || '—',
                                classification: this.optionText('add_equipment_category') || this.categoryName(this.category) || '—',
                                tracking: 'Individual',
                                condition: this.manualIntake() ? (item.equipment_condition_status || this.condition || 'Good') : 'Good (new delivery)',
                                brand: item.equipment_brand_name || this.brand || '—',
                                model: item.equipment_model || this.model || '—',
                                asset_tag: item.equipment_asset_tag || '—',
                                serial: item.equipment_serial_number || '—',
                                cost: this.formatPeso(rawCost),
                                purchase_date: item.equipment_purchase_date || this.purchaseDate || '—',
                                warranty: item.equipment_warranty_expiration || this.warranty || '—',
                                lifespan: rawLife ? (rawLife + ' years') : '5 years',
                                deploying,
                                room: deploying
                                    ? (this.deployRoomName(item.deploy_room_id) || '—')
                                    : (this.defaultStorageRoomName || 'Storage Room'),
                                custodian: (deploying && item.custodian_id)
                                    ? (this.custodianName(item.custodian_id) || 'No one yet')
                                    : 'No one yet',
                                replaces: this.replacementCandidateLabel(item.replaces_equipment_id),
                                received_on: this.manualIntake() ? (this.receivedDate || '—') : 'Today (date added)',
                                image: item._imagePreview || null,
                                source: '',
                                editStep: 'p2',
                                editMultiIndex: null,
                                editUnitIndex: idx,
                            };
                        });
                    }

                    const deploying = this.deploying();
                    const qty = Number(this.quantity) || 1;
                    return [{
                        key: 'single',
                        label: this.name || 'Asset',
                        name: this.name || '—',
                        classification: this.optionText('add_equipment_category') || this.categoryName(this.category) || '—',
                        tracking: this.tracking === 'Bulk' ? ('Bulk · Qty ' + qty) : 'Individual',
                        condition: this.manualIntake() ? (this.condition || 'Good') : 'Good (new delivery)',
                        brand: this.brand || '—',
                        model: this.model || '—',
                        asset_tag: this.assetTag || '—',
                        serial: this.serial || '—',
                        cost: this.formatPeso(this.purchaseCost),
                        purchase_date: this.purchaseDate || '—',
                        warranty: this.warranty || '—',
                        lifespan: this.usefulLifeYears ? (this.usefulLifeYears + ' years') : '5 years',
                        deploying,
                        room: deploying
                            ? (this.deployRoomName(this.deployRoom) || this.optionText('add_deploy_room') || '—')
                            : (isStockPage ? (this.defaultStorageRoomName || 'Storage Room') : (this.optionText('add_equipment_room') || '—')),
                        custodian: (this.canAssignPerson() && this.custodianId)
                            ? (this.custodianName(this.custodianId) || this.optionText('add_custodian') || 'No one yet')
                            : 'No one yet',
                        replaces: this.replacementCandidateLabel(this.replacesId),
                        received_on: this.manualIntake() ? (this.receivedDate || '—') : 'Today (date added)',
                        image: this.imagePreview || null,
                        source: '',
                        editStep: 'p2',
                        editMultiIndex: null,
                        editUnitIndex: null,
                    }];
                },
                activeReviewSlide() {
                    const slides = this.reviewSlides();
                    if (!slides.length) return null;
                    const idx = Math.min(Math.max(0, this.reviewSlideIndex), slides.length - 1);
                    return slides[idx] || slides[0];
                },
                goToReviewSlide(index) {
                    const slides = this.reviewSlides();
                    if (!slides.length) return;
                    this.reviewSlideIndex = Math.min(Math.max(0, Number(index) || 0), slides.length - 1);
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                prevReviewSlide() {
                    if (this.reviewSlideIndex > 0) this.goToReviewSlide(this.reviewSlideIndex - 1);
                },
                nextReviewSlide() {
                    const slides = this.reviewSlides();
                    if (this.reviewSlideIndex < slides.length - 1) this.goToReviewSlide(this.reviewSlideIndex + 1);
                },
                editSlideFromReview(slide) {
                    if (this.submitting || !slide) return;
                    this.reviewOpen = false;
                    if (slide.editMultiIndex !== null && slide.editMultiIndex !== undefined) {
                        this.activeMultiIndex = slide.editMultiIndex;
                    }
                    if (slide.editUnitIndex !== null && slide.editUnitIndex !== undefined) {
                        this.activeUnitIndex = slide.editUnitIndex;
                    }
                    this.goToStep(slide.editStep || 'p2');
                },
                openReview() {
                    this.formError = '';
                    this.reviewSlideIndex = 0;
                    this.reviewOpen = true;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                },
                closeReview() {
                    if (this.submitting) return;
                    this.reviewOpen = false;
                },
                editFromReview(key) {
                    if (this.submitting) return;
                    this.reviewOpen = false;
                    this.goToStep(key);
                },
                confirmReview() {
                    if (this.submitting || !this.reviewOpen) return;
                    if (this.isMulti()) {
                        this.submitMulti();
                        return;
                    }
                    this.reviewConfirmed = true;
                    const form = document.querySelector('#addEquipmentModal form');
                    if (form) {
                        form.requestSubmit();
                    } else {
                        this.reviewConfirmed = false;
                    }
                },
                async saveNow() {
                    if (this.submitting) return;
                    if (this.step === 1 && this.page === 2) {
                        if (!this.validatePage(2)) return;
                        if (this.hasUnitsStep()) {
                            this.goToItems();
                            return;
                        }
                    }
                    if (this.isMulti()) {
                        if (!this.validateStep1()) return;
                        if (this.multiLines.some((row) => !Array.isArray(row.units))) this.buildMultiUnits();
                        if (!this.validateMultiUnits()) {
                            this.step = 2;
                            this.markFurthest();
                            this.scrollToTop();
                            return;
                        }
                        this.openReview();
                        return;
                    }
                    if (!this.validateStep1()) return;
                    if (this.needsItemStep()) {
                        if (!Array.isArray(this.items) || !this.items.length) this.buildItems();
                        if (!this.validateItems()) {
                            return;
                        }
                        this.syncTopLevelFromItems();
                    } else {
                        this.items = [];
                        this.syncAssetTag();
                    }
                    this.openReview();
                },
                async expressStock() {
                    if (this.submitting) return;
                    if (!isStockPage || this.intakeBasis === 'non_procurement') {
                        this.formError = 'Express stock is only for delivered Receiving Report lines.';
                        return;
                    }
                    if (!this.validatePage(1)) return;

                    this.placement = 'storage';
                    this.deployRoom = '';
                    this.custodianId = '';
                    if (!String(this.room || '').trim()) {
                        const first = document.querySelector('#add_equipment_room option:not([value=""])');
                        if (first) this.room = first.value;
                    }
                    if (!String(this.room || '').trim()) {
                        this.formError = 'Choose a storage room before Express stock.';
                        this.page = 2;
                        this.markFurthest();
                        this.scrollToTop();
                        return;
                    }

                    this.syncMultiLines();
                    this.applyExpressDefaults();

                    // Prefill single-line fields too (guided path / review).
                    if (this.rrSelected.length === 1) {
                        this.rrItemId = this.rrSelected[0];
                        this.onRrLineChange();
                        const row = this.rrMultiLines[0];
                        if (row) {
                            this.name = row.name || this.name;
                            this.category = row.category || this.category;
                            this.quantity = row.quantity || this.quantity;
                            this.tracking = row.tracking || this.tracking;
                            this.purchaseDate = row.purchase_date || this.purchaseDate;
                            this.purchaseCost = row.cost || this.purchaseCost;
                        }
                    }

                    const missing = (this.rrMultiLines || []).filter((row) => !String(row.category || '').trim());
                    if (missing.length) {
                        const rowErrors = {};
                        missing.forEach((row) => {
                            rowErrors[row.id] = { category: 'Please select a classification.' };
                        });
                        this.multiErrors = { ...this.multiErrors, ...rowErrors };
                        this.errors = { ...this.errors, multi: 'Classification is required for every line.' };
                        const firstMissingIdx = (this.rrMultiLines || []).findIndex((row) => !String(row.category || '').trim());
                        if (firstMissingIdx >= 0) {
                            this.activeMultiIndex = firstMissingIdx;
                        }
                        this.formError = missing.length === 1
                            ? 'Classification is required for “' + (missing[0].name || missing[0].article) + '”. Select it below, then Express stock now.'
                            : ('Classification is required for ' + missing.length + ' lines. Select each classification below, then Express stock now.');
                        this.step = 1;
                        this.page = 2;
                        this.furthestStepIndex = Math.max(this.furthestStepIndex, this.currentStepIndex());
                        this.scrollToTop();
                        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                        return;
                    }

                    // Brand / model / warranty / lifespan / cost / placement are required before Express can finish.
                    if (!this.validatePage(2)) {
                        this.step = 1;
                        this.page = 2;
                        this.furthestStepIndex = Math.max(this.furthestStepIndex, this.currentStepIndex());
                        this.scrollToTop();
                        return;
                    }

                    this.buildMultiUnits();
                    if (!this.validateMultiUnits()) {
                        this.step = 2;
                        this.markFurthest();
                        this.scrollToTop();
                        return;
                    }
                    await this.submitExpressRrLines();
                },
                async submitExpressRrLines() {
                    if (this.submitting) return;
                    if (!(this.rrMultiLines || []).length) {
                        this.formError = 'Tick at least one received equipment line.';
                        return;
                    }
                    if (!this.validateMultiUnits()) {
                        this.step = 2;
                        this.markFurthest();
                        this.scrollToTop();
                        return;
                    }

                    const form = this.rrLinesFormFrom(this.rrMultiLines);
                    const token = document.querySelector('#addEquipmentModal input[name="_token"]')?.value
                        || document.querySelector('meta[name="csrf-token"]')?.content
                        || '';
                    this.submitting = true;
                    this.formError = '';
                    try {
                        const res = await fetch('/admin/inventory/store-rr-lines', {
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
                rrLinesFormFrom(lines) {
                    const form = new FormData();
                    const fallbackStorage = String(this.room || (lines && lines[0] && lines[0].room) || '');
                    const fallbackWarranty = String((lines && lines[0] && lines[0].warranty) || this.warranty || '');
                    const fallbackLife = String((lines && lines[0] && lines[0].useful_life_years) || this.usefulLifeYears || 5);
                    form.append('storage_room_id', fallbackStorage);
                    form.append('warranty', fallbackWarranty);
                    form.append('useful_life_years', fallbackLife);
                    (lines || []).forEach((row, i) => {
                        const rowDeploy = this.rowDeploying(row);
                        const key = 'lines[' + i + ']';
                        form.append(key + '[receiving_report_item_id]', String(row.id));
                        form.append(key + '[name]', String(row.name || '').trim());
                        form.append(key + '[category_id]', String(row.category));
                        form.append(key + '[tracking]', row.tracking);
                        form.append(key + '[quantity]', String(Number(row.quantity)));
                        form.append(key + '[brand]', String(row.brand || '').trim());
                        form.append(key + '[model]', String(row.model || '').trim());
                        form.append(key + '[warranty]', String(row.warranty || this.warranty || ''));
                        form.append(key + '[useful_life_years]', String(
                            row.useful_life_years !== '' && row.useful_life_years != null
                                ? row.useful_life_years
                                : (this.usefulLifeYears || '')
                        ));
                        if (row.room) form.append(key + '[storage_room_id]', String(row.room));
                        if (row.replacesId) form.append(key + '[replaces_equipment_id]', String(row.replacesId));
                        (row.units || []).forEach((unit, u) => {
                            const unitKey = key + '[units][' + u + ']';
                            const warranty = String(unit.warranty || row.warranty || this.warranty || '').trim();
                            const life = unit.useful_life_years !== '' && unit.useful_life_years != null
                                ? unit.useful_life_years
                                : (row.useful_life_years !== '' && row.useful_life_years != null ? row.useful_life_years : this.usefulLifeYears);
                            const cost = unit.cost !== '' && unit.cost != null
                                ? unit.cost
                                : (row.cost !== '' && row.cost != null ? row.cost : this.purchaseCost);
                            form.append(unitKey + '[asset_tag]', String(unit.asset_tag || '').trim());
                            form.append(unitKey + '[serial]', String(unit.serial || '').trim());
                            if (this.hasPieces(row)) {
                                const values = this.pieceValues(row, u);
                                form.append(unitKey + '[brand]', values.brand || String(row.brand || '').trim());
                                form.append(unitKey + '[model]', values.model || String(row.model || '').trim());
                            } else {
                                form.append(unitKey + '[brand]', String(row.brand || '').trim());
                                form.append(unitKey + '[model]', String(row.model || '').trim());
                            }
                            form.append(unitKey + '[warranty]', warranty);
                            if (unit.purchase_date) form.append(unitKey + '[purchase_date]', unit.purchase_date);
                            form.append(unitKey + '[cost]', String(cost ?? ''));
                            form.append(unitKey + '[useful_life_years]', String(life ?? ''));
                            if (rowDeploy) form.append(unitKey + '[deploy_room_id]', String(unit.deploy_room_id || row.deployRoom || ''));
                            if (this.rowCanAssign(row) && unit.custodian_id) form.append(unitKey + '[custodian_id]', String(unit.custodian_id));
                            if (unit._imageFile) form.append(unitKey + '[image]', unit._imageFile);
                        });
                    });
                    return form;
                },
                prepareSubmit(event) {
                    if (!this.reviewConfirmed) {
                        event.preventDefault();
                        if (!this.reviewOpen) this.next();
                        return;
                    }
                    this.reviewConfirmed = false;
                    if (this.isMulti()) {
                        event.preventDefault();
                        this.submitMulti();
                        return;
                    }
                    if (!this.validateStep1()) {
                        event.preventDefault();
                        this.reviewOpen = false;
                        return;
                    }
                    if (this.needsItemStep()) {
                        if (!this.validateItems()) {
                            event.preventDefault();
                            this.reviewOpen = false;
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
                    if (!this.validateStep1()) {
                        this.reviewOpen = false;
                        return;
                    }
                    if (this.multiLines.some((row) => !Array.isArray(row.units))) this.buildMultiUnits();
                    if (!this.validateMultiUnits()) {
                        this.reviewOpen = false;
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
                            if (unit.purchase_date) form.append(unitKey + '[purchase_date]', unit.purchase_date);
                            if (unit.cost !== '' && unit.cost !== null && unit.cost !== undefined) form.append(unitKey + '[cost]', String(unit.cost));
                            if (unit.useful_life_years !== '' && unit.useful_life_years !== null && unit.useful_life_years !== undefined) {
                                form.append(unitKey + '[useful_life_years]', String(unit.useful_life_years));
                            }
                            if (this.rowDeploying(row)) form.append(unitKey + '[room_id]', String(unit.deploy_room_id || row.deployRoom || ''));
                            if (this.rowCanAssign(row) && unit.custodian_id) form.append(unitKey + '[custodian_id]', String(unit.custodian_id));
                            if (unit._imageFile) form.append(unitKey + '[image]', unit._imageFile);
                        });
                    });
                    return form;
                },
                rrLinesForm() {
                    return this.rrLinesFormFrom(this.multiLines);
                },
            };
        }

        function openAddEquipmentModal(rrItemId = null, options = {}) {
            const modal = document.getElementById('addEquipmentModal');
            if (modal && modal._x_dataStack && modal._x_dataStack[0]) {
                modal._x_dataStack[0].show(rrItemId, options || {});
                return;
            }
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        }

        function openStockAndLinkModal(rrItemId, replacesEquipmentId = null) {
            openAddEquipmentModal(rrItemId, {
                replacesId: replacesEquipmentId || '',
                stockAndLink: true,
            });
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
