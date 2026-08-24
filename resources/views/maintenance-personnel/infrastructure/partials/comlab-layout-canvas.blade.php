{{-- Comlab room canvas: draggable row tables + floor equipment --}}
<div
    class="relative flex h-full min-h-0 w-full flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
>
    <div class="flex shrink-0 items-center gap-2 border-b border-slate-100 px-4 py-2.5 text-xs">
        <button
            type="button"
            @click="comlabNavTo('rows')"
            class="font-medium transition"
            :class="roomLayout.comlabNav === 'rows' ? 'text-[#005EA6]' : 'text-slate-500 hover:text-slate-800'"
        >
            Room layout
        </button>
        <template x-if="roomLayout.comlabNav !== 'rows' && roomLayout.selectedComlabRow">
            <span class="flex items-center gap-2 text-slate-300">
                <span>/</span>
                <button type="button" @click="comlabNavTo('row')" class="font-medium text-[#005EA6]" x-text="roomLayout.selectedComlabRow"></button>
            </span>
        </template>
        <template x-if="roomLayout.comlabNav === 'set' || roomLayout.comlabNav === 'asset'">
            <span class="flex items-center gap-2 text-slate-300">
                <span>/</span>
                <span class="font-medium text-[#005EA6]" x-text="activeComlabSet()?.label || 'Computer set'"></span>
            </span>
        </template>
        <div class="ml-auto flex items-center gap-2" x-show="roomLayout.edit && roomLayout.comlabNav === 'rows'">
            <button
                type="button"
                x-show="selectedComlabRowTable"
                @click="deleteComlabRow(selectedComlabRowTable)"
                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-[11px] font-medium text-red-600 hover:bg-red-50"
            >
                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                Delete row
            </button>
            <button type="button" @click="addComlabRow()" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-slate-800">
                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                Add row
            </button>
        </div>
    </div>

    {{-- Room floor plan --}}
    <div
        x-show="roomLayout.comlabNav === 'rows'"
        x-ref="comlabInteriorCanvas"
        class="room-interior-grid relative min-h-0 flex-1 overflow-hidden bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] bg-[size:28px_28px]"
        @pointerdown="clearComlabCanvasSelection()"
        @dragover.prevent="roomLayout.edit"
        @drop.prevent="comlabDropOnCanvas($event)"
    >
        <div class="pointer-events-none absolute inset-x-16 top-3 rounded-full border border-dashed border-slate-300 bg-white/70 px-4 py-1 text-center text-[10px] font-black uppercase tracking-[.2em] text-slate-400">
            Front wall / board
        </div>

        {{-- Row tables --}}
        <template x-for="row in (roomLayout.comlabRowLayouts || [])" :key="'comlab-row-' + row.name">
            <div
                class="comlab-row-node absolute z-10 flex flex-col overflow-visible rounded-xl border-2 bg-white/95 shadow-md"
                :class="{
                    'border-[#005EA6] ring-2 ring-[#005EA6]/30': selectedComlabRowTable === row.name,
                    'border-slate-400': selectedComlabRowTable !== row.name,
                    'cursor-move': roomLayout.edit,
                }"
                :data-row-name="row.name"
                :data-x="row.x"
                :data-y="row.y"
                :data-width="row.width || 280"
                :data-height="row.height || 56"
                :data-rotation="row.rotation || 0"
                @pointerdown.stop="handleComlabRowPointerDown($event, row.name)"
                @dragover.prevent.stop="roomLayout.edit && $event.currentTarget.classList.add('ring-2','ring-emerald-400')"
                @dragleave.stop="$event.currentTarget.classList.remove('ring-2','ring-emerald-400')"
                @drop.prevent.stop="
                    $event.currentTarget.classList.remove('ring-2','ring-emerald-400');
                    comlabDropOnRow($event, row.name);
                "
                :style="`
                    left:${row.x}%;
                    top:${row.y}%;
                    width:${row.width || 280}px;
                    height:${row.height || 56}px;
                    touch-action:none;
                    transform:translate(-50%,-50%) rotate(${(comlabRowIsRotating && selectedComlabRowTable === row.name && comlabRowLiveRotation != null) ? comlabRowLiveRotation : (row.rotation || 0)}deg);
                    transform-origin:center center;
                `"
            >
                <div class="flex h-full min-w-0 flex-col items-center justify-center gap-1 px-3 py-2 text-center">
                    <div class="flex w-full items-center justify-between gap-1">
                        <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold text-white" x-text="row.name"></span>
                        <button
                            type="button"
                            x-show="roomLayout.edit && selectedComlabRowTable === row.name"
                            @pointerdown.stop
                            @click.stop="deleteComlabRow(row.name)"
                            class="flex h-6 w-6 items-center justify-center rounded-md text-red-500 hover:bg-red-50"
                            title="Delete row"
                        >
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        </button>
                        <button
                            type="button"
                            x-show="!roomLayout.edit"
                            @click.stop="openComlabRowDetail(row.name)"
                            class="shrink-0 rounded bg-[#005EA6]/10 px-1.5 py-0.5 text-[9px] font-semibold text-[#005EA6] hover:bg-[#005EA6]/20"
                        >
                            Open
                        </button>
                    </div>
                    <p
                        class="text-[12px] font-semibold leading-tight text-slate-700"
                        x-text="comlabRowItemsLabel(row.name)"
                    ></p>
                </div>

                <template x-if="roomLayout.edit && selectedComlabRowTable === row.name">
                    <div>
                        <span class="resize-grip absolute -left-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nwse-resize" data-handle-x="left" data-handle-y="top"></span>
                        <span class="resize-grip absolute -right-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nesw-resize" data-handle-x="right" data-handle-y="top"></span>
                        <span class="resize-grip absolute -bottom-1.5 -left-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nesw-resize" data-handle-x="left" data-handle-y="bottom"></span>
                        <span class="resize-grip absolute -bottom-1.5 -right-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nwse-resize" data-handle-x="right" data-handle-y="bottom"></span>
                        <span class="resize-grip absolute -top-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ns-resize" data-handle-x="center" data-handle-y="top"></span>
                        <span class="resize-grip absolute -bottom-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ns-resize" data-handle-x="center" data-handle-y="bottom"></span>
                        <span class="resize-grip absolute -left-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ew-resize" data-handle-x="left" data-handle-y="center"></span>
                        <span class="resize-grip absolute -right-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ew-resize" data-handle-x="right" data-handle-y="center"></span>
                        <div
                            class="pointer-events-none absolute left-1/2 top-1/2 z-40"
                            :style="comlabRowRotateGimbalStyle(row)"
                        >
                            <button
                                type="button"
                                @pointerdown.stop.prevent="beginComlabRowRotation($event, row.name)"
                                class="rotate-equipment-handle-cursor pointer-events-auto absolute flex items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg transition hover:bg-slate-50"
                                :class="comlabRowIsRotating && selectedComlabRowTable === row.name ? 'h-11 min-w-[2.75rem] px-2' : 'h-9 w-9'"
                                :style="comlabRowRotateHandleStyle(row)"
                            >
                                <span
                                    x-show="!(comlabRowIsRotating && selectedComlabRowTable === row.name)"
                                    class="flex items-center justify-center"
                                >
                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                </span>
                                <span
                                    x-show="comlabRowIsRotating && selectedComlabRowTable === row.name"
                                    class="text-[11px] font-black leading-none text-slate-900"
                                    x-text="Math.round(comlabRowRotationDisplayAngle) + '°'"
                                ></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Floor equipment (projector, aircon, whiteboard, etc.) — thin markers --}}
        <template x-for="item in comlabCanvasEquipment()" :key="'comlab-floor-' + item.id">
            <div
                class="room-equipment-node comlab-floor-node absolute z-20 flex items-center justify-center overflow-visible rounded-xl border-2 border-slate-300 bg-white shadow-md"
                :class="{
                    'ring-2 ring-[#07319C] cursor-move border-[#005EA6]': roomLayout.edit && selectedEquipmentId === item.id,
                    'ring-2 ring-emerald-500 border-emerald-500': !roomLayout.edit && roomLayout.selectedAssetId === item.id,
                    'cursor-pointer': !roomLayout.edit,
                }"
                :data-equipment-id="item.id"
                :data-x="item.x"
                :data-y="item.y"
                :data-width="item.width || 64"
                :data-height="item.height || 64"
                :data-rotation="item.rotation || 0"
                :data-zone="item.placement_zone || item.location || ''"
                @pointerdown.stop="onComlabFloorPointerDown($event, item.id)"
                @click.stop="onComlabFloorClick(item.id)"
                :style="`
                    left:${item.x}%;
                    top:${item.y}%;
                    width:${item.width || 64}px;
                    height:${item.height || 64}px;
                    touch-action:none;
                    transform:translate(-50%,-50%) rotate(${(equipmentIsRotating && selectedEquipmentId === item.id && equipmentLiveRotation != null) ? equipmentLiveRotation : (item.rotation || 0)}deg);
                    transform-origin:center center;
                `"
            >
                <span class="pointer-events-none flex h-[78%] w-[78%] items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>

                {{-- Short name label outside the marker --}}
                <span
                    class="pointer-events-none absolute z-30 whitespace-nowrap rounded-md bg-white/95 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200"
                    :style="comlabFloorLabelStyle(item)"
                    x-text="shortEquipmentName(item.name)"
                ></span>

                <button
                    type="button"
                    x-show="roomLayout.edit && selectedEquipmentId === item.id"
                    @pointerdown.stop
                    @click.stop="sendEquipmentToHolding(item.id)"
                    class="absolute -right-2 -top-2 z-40 flex h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md hover:bg-amber-50 hover:text-amber-700"
                    title="Move to holding area"
                >
                    <i data-lucide="inbox" class="h-3 w-3"></i>
                </button>

                <template x-if="roomLayout.edit && selectedEquipmentId === item.id">
                    <div>
                        <span class="resize-grip absolute -left-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nwse-resize" data-handle-x="left" data-handle-y="top"></span>
                        <span class="resize-grip absolute -right-1.5 -top-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nesw-resize" data-handle-x="right" data-handle-y="top"></span>
                        <span class="resize-grip absolute -bottom-1.5 -left-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nesw-resize" data-handle-x="left" data-handle-y="bottom"></span>
                        <span class="resize-grip absolute -bottom-1.5 -right-1.5 z-30 h-3 w-3 rounded-sm border-2 border-[#005EA6] bg-white cursor-nwse-resize" data-handle-x="right" data-handle-y="bottom"></span>
                        <span class="resize-grip absolute -top-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ns-resize" data-handle-x="center" data-handle-y="top"></span>
                        <span class="resize-grip absolute -bottom-1.5 left-1/2 z-30 h-3 w-3 -translate-x-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ns-resize" data-handle-x="center" data-handle-y="bottom"></span>
                        <span class="resize-grip absolute -left-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ew-resize" data-handle-x="left" data-handle-y="center"></span>
                        <span class="resize-grip absolute -right-1.5 top-1/2 z-30 h-3 w-3 -translate-y-1/2 rounded-sm border-2 border-[#005EA6] bg-white cursor-ew-resize" data-handle-x="right" data-handle-y="center"></span>
                        <div class="equipment-rotate-gimbal pointer-events-none absolute left-1/2 top-1/2 z-40" :style="equipmentRotateGimbalStyle(item)">
                            <button type="button" @pointerdown.stop.prevent="beginEquipmentRotation($event, item.id)" class="rotate-equipment-handle-cursor pointer-events-auto absolute left-1/2 flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-lg" :style="equipmentRotateHandleStyle(item)">
                                <span x-show="!(equipmentIsRotating && selectedEquipmentId === item.id)" class="flex items-center justify-center">
                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                </span>
                                <span x-show="equipmentIsRotating && selectedEquipmentId === item.id" class="text-[10px] font-black" x-text="Math.round(equipmentRotationDisplayAngle) + '°'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- Row detail view (carousel) --}}
    <div x-show="roomLayout.comlabNav === 'row' || roomLayout.comlabNav === 'set' || roomLayout.comlabNav === 'asset'" x-cloak class="flex min-h-0 flex-1 flex-col">
        @include('maintenance-personnel.infrastructure.partials.comlab-row-detail')
    </div>

    {{-- Holding area (edit mode) — only when it has items --}}
    <div
        x-show="roomLayout.edit && roomLayout.comlabNav === 'rows' && comlabHoldingItems().length > 0"
        x-cloak
        class="shrink-0 border-t border-slate-200 bg-white px-4 py-3"
        @dragover.prevent="$event.currentTarget.classList.add('ring-2','ring-inset','ring-amber-300'); roomLayout.edit"
        @dragleave="$event.currentTarget.classList.remove('ring-2','ring-inset','ring-amber-300')"
        @drop.prevent="
            $event.currentTarget.classList.remove('ring-2','ring-inset','ring-amber-300');
            comlabDropOnHolding($event);
        "
    >
        <div class="mb-2 flex items-center justify-between gap-2">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="isComputerLabLayout()
                ? 'Holding area — drag onto a row or onto the floor'
                : 'Holding area — drag onto a row or the floor'">
            </p>
            <div class="flex items-center gap-1" x-show="comlabHoldingPageCount() > 1">
                <button type="button" @click="comlabHoldingPrev()" class="flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                </button>
                <span class="min-w-[3.5rem] text-center text-[11px] font-medium text-slate-500" x-text="(roomLayout.comlabHoldingPage + 1) + ' / ' + comlabHoldingPageCount()"></span>
                <button type="button" @click="comlabHoldingNext()" class="flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">
                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                </button>
            </div>
        </div>
        <div class="overflow-hidden">
            <div
                class="flex transition-transform duration-300 ease-out"
                :style="'transform: translateX(-' + ((roomLayout.comlabHoldingPage || 0) * 100) + '%)'"
            >
                <template x-for="(page, pageIdx) in comlabHoldingPages()" :key="'hold-page-' + pageIdx">
                    <div class="flex w-full shrink-0 flex-wrap gap-2">
                        <template x-for="item in page" :key="'hold-' + item.id">
                            <div
                                draggable="true"
                                @dragstart="comlabDragStart($event, item.id)"
                                @click="selectComlabHoldingItem(item.id)"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-dashed px-2.5 py-1.5 text-[11px] font-medium text-slate-700 transition active:cursor-grabbing"
                                :class="roomLayout.selectedAssetId === item.id
                                    ? 'border-[#005EA6] bg-[#005EA6]/10 ring-1 ring-[#005EA6]/25'
                                    : (isComlabFloorEquipment(item) ? 'border-slate-300 bg-slate-50 hover:border-slate-400' : 'border-[#005EA6]/40 bg-[#005EA6]/5 hover:border-[#005EA6]/60')"
                                title="Click for details · drag to place"
                            >
                                <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                                <span x-text="item.quantity > 1 ? (item.name + ' × ' + item.quantity) : item.name"></span>
                                <span
                                    class="rounded px-1 py-0.5 text-[9px] font-semibold bg-[#005EA6]/15 text-[#005EA6]"
                                    x-text="'Place'"
                                ></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
        <div class="mt-2 flex items-center justify-center gap-1.5" x-show="comlabHoldingPageCount() > 1">
            <template x-for="(_, idx) in comlabHoldingPages()" :key="'hold-dot-' + idx">
                <button
                    type="button"
                    @click="roomLayout.comlabHoldingPage = idx"
                    class="h-1.5 rounded-full transition-all"
                    :class="roomLayout.comlabHoldingPage === idx ? 'w-5 bg-[#005EA6]' : 'w-1.5 bg-slate-300'"
                ></button>
            </template>
        </div>
    </div>
</div>
