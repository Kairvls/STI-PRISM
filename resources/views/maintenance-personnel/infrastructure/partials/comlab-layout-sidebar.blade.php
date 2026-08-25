{{-- Comlab row layout sidebar --}}
<aside
    class="flex h-full min-h-0 flex-1 flex-col border-l border-slate-200 bg-white"
>
    <template x-if="roomLayout.selectedAssetId && selectedLayoutAsset()">
        <div class="flex min-h-0 flex-1 flex-col">
            <div class="border-b border-slate-100 px-4 py-3">
                <button type="button" @click="comlabBack()" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-900">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                    Back
                </button>
                <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Asset details</p>
                <h3 class="mt-1 truncate text-base font-semibold text-slate-900" x-text="selectedLayoutAsset()?.asset_tag || selectedLayoutAsset()?.name"></h3>
                <p class="text-xs text-slate-500" x-text="comlabAssetPlacementHint(selectedLayoutAsset())"></p>
            </div>
            <div class="min-h-0 flex-1 space-y-0 overflow-y-auto px-4 py-2 text-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
                    <span class="text-slate-400">Name</span>
                    <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.name"></span>
                </div>
                <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
                    <span class="text-slate-400">Brand</span>
                    <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.brand || '—'"></span>
                </div>
                <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
                    <span class="text-slate-400">Model</span>
                    <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.model || '—'"></span>
                </div>
                <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
                    <span class="text-slate-400">Serial</span>
                    <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.serial_number || '—'"></span>
                </div>
                <div class="flex items-center justify-between gap-3 border-b border-slate-50 py-3">
                    <span class="text-slate-400">Condition</span>
                    <span class="font-medium text-slate-800" x-text="selectedLayoutAsset()?.condition || '—'"></span>
                </div>
                <div class="flex items-center justify-between gap-3 py-3">
                    <span class="text-slate-400">Placement</span>
                    <span class="font-medium text-slate-800" x-text="comlabAssetPlacementLabel(selectedLayoutAsset())"></span>
                </div>
                <div
                    x-show="roomLayout.edit && selectedLayoutAsset() && isComlabRowZone(selectedLayoutAsset()?.placement_zone || selectedLayoutAsset()?.location)"
                    class="border-t border-slate-100 pt-3"
                >
                    <button
                        type="button"
                        @click="sendEquipmentToHolding(selectedLayoutAsset().id)"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100"
                    >
                        <i data-lucide="inbox" class="h-4 w-4"></i>
                        Remove from row → Holding
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-if="!roomLayout.selectedAssetId || !selectedLayoutAsset()">
        <div class="flex min-h-0 flex-1 flex-col">
            <div class="border-b border-slate-100 px-4 py-3">
                <template x-if="roomLayout.comlabNav !== 'rows'">
                    <button type="button" @click="comlabNavTo('rows')" class="mb-2 inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-900">
                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                        Room layout
                    </button>
                </template>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400" x-text="roomLayout.comlabNav === 'rows' ? 'Equipment' : roomLayout.selectedComlabRow"></p>
                <p class="mt-1 text-sm text-slate-500" x-text="roomLayout.comlabNav === 'rows'
                    ? (roomLayout.edit
                        ? 'Arrange row tables on the floor. Drop any equipment on rows or place icons on the floor.'
                        : (isComputerLabLayout()
                            ? 'Click Open on a row to browse computer sets and equipment.'
                            : 'Click Open on a row to browse equipment, or select a floor icon.'))
                    : (isComputerLabLayout() ? 'Browse sets and equipment.' : 'Browse equipment in this row.')"></p>
            </div>

            <div x-show="roomLayout.comlabNav === 'rows'" class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 py-2">
                    <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Rows</p>
                    <p x-show="!(roomLayout.comlabRowLayouts || []).length" class="px-3 py-2 text-[11px] text-slate-400">
                        No rows yet. Turn on Edit layout, then click Add row.
                    </p>
                    <template x-for="row in (roomLayout.comlabRowLayouts || [])" :key="'sidebar-row-' + row.name">
                        <div class="flex items-center gap-1 rounded-xl px-2 py-1 transition" :class="selectedComlabRowTable === row.name ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20' : 'hover:bg-slate-50'">
                            <button type="button" @click="selectComlabRowTable(row.name)" class="min-w-0 flex-1 px-1 py-1.5 text-left">
                                <span class="text-sm font-semibold text-slate-900" x-text="row.name"></span>
                            <span class="mt-0.5 block text-[11px] text-slate-400" x-text="comlabRowItemsLabel(row.name)"></span>
                            </button>
                            <button type="button" @click="openComlabRowDetail(row.name)" class="shrink-0 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-[#005EA6] hover:bg-[#005EA6]/10">
                                Open
                            </button>
                        </div>
                    </template>

                    <p class="mt-3 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Floor equipment</p>
                    <template x-for="item in comlabCanvasEquipment()" :key="'sidebar-floor-' + item.id">
                        <div class="flex w-full items-center gap-1 rounded-xl px-2 py-1 transition hover:bg-slate-50">
                            <button type="button" @click="selectComlabAsset(item.id)" class="flex min-w-0 flex-1 items-center gap-3 px-1 py-1.5 text-left">
                                <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800" x-text="shortEquipmentName(item.name)"></span>
                                    <span class="block truncate text-[11px] text-slate-400" x-text="item.name"></span>
                                </span>
                            </button>
                            <button
                                type="button"
                                x-show="roomLayout.edit"
                                @click="sendEquipmentToHolding(item.id)"
                                class="shrink-0 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-50"
                                title="Move to holding area"
                            >
                                To holding
                            </button>
                        </div>
                    </template>
                    <p x-show="!comlabCanvasEquipment().length" class="px-3 py-2 text-[11px] text-slate-400">None placed on floor yet.</p>

                    <div x-show="comlabHoldingItems().length > 0" x-cloak>
                        <p class="mt-3 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Holding area</p>
                        <template x-for="item in comlabHoldingItems()" :key="'sidebar-hold-' + item.id">
                            <button
                                type="button"
                                @click="selectComlabHoldingItem(item.id)"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition"
                                :class="roomLayout.selectedAssetId === item.id ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20' : 'hover:bg-slate-50'"
                            >
                                <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800" x-text="item.quantity > 1 ? (item.name + ' × ' + item.quantity) : item.name"></span>
                                    <span class="block text-[11px] text-slate-400">Drop on a row or the floor</span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <p class="mt-3 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="isComputerLabLayout() ? 'Set parts (in rows)' : 'Equipment (in rows)'"></p>
                    <template x-for="item in (roomLayout.equipment || []).filter(i => isComlabRowAssignedSetItem(i))" :key="'sidebar-setpart-' + item.id">
                        <div class="flex w-full items-center gap-1 rounded-xl px-2 py-1 transition hover:bg-slate-50">
                            <button type="button" @click="selectComlabAsset(item.id)" class="flex min-w-0 flex-1 items-center gap-3 px-1 py-1.5 text-left">
                                <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800" x-text="item.name"></span>
                                    <span class="block text-[11px] text-slate-400" x-text="item.placement_zone || item.location"></span>
                                </span>
                            </button>
                            <button
                                type="button"
                                x-show="roomLayout.edit"
                                @click="sendEquipmentToHolding(item.id)"
                                class="shrink-0 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-50"
                                title="Remove from row to holding area"
                            >
                                To holding
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="roomLayout.comlabNav !== 'rows'" x-cloak class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 py-2">
                    <template x-if="isComputerLabLayout()">
                        <div>
                            <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Computer sets</p>
                            <template x-for="(set, idx) in comlabSetsInSelectedRow()" :key="'sidebar-set-' + set.index">
                                <button type="button" @click="selectComlabSet(idx)" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition" :class="roomLayout.comlabSetCarouselIndex === idx ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20' : 'hover:bg-slate-50'">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold text-slate-600" x-text="set.label.replace(/^Computer Set\s+/i, '')"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-slate-900" x-text="set.label"></span>
                                        <span class="block truncate text-[11px] text-slate-400" x-text="set.members.map(m => m.name).join(', ')"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </template>
                    <template x-if="!isComputerLabLayout()">
                        <div>
                            <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">In this row</p>
                            <template x-for="item in equipmentInComlabRow(roomLayout.selectedComlabRow)" :key="'sidebar-row-item-' + item.id">
                                <div class="flex w-full items-center gap-1 rounded-xl px-2 py-1 transition hover:bg-slate-50" :class="roomLayout.selectedAssetId === item.id ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20' : ''">
                                    <button type="button" @click="selectComlabAsset(item.id)" class="flex min-w-0 flex-1 items-center gap-3 px-1 py-1.5 text-left">
                                        <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-slate-900" x-text="item.quantity > 1 ? (item.name + ' × ' + item.quantity) : item.name"></span>
                                            <span class="block truncate text-[11px] text-slate-400" x-text="item.asset_tag || shortEquipmentName(item.name)"></span>
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        x-show="roomLayout.edit"
                                        @click="sendEquipmentToHolding(item.id)"
                                        class="shrink-0 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-50"
                                        title="Remove from row to holding area"
                                    >
                                        To holding
                                    </button>
                                </div>
                            </template>
                            <p x-show="!equipmentInComlabRow(roomLayout.selectedComlabRow).length" class="px-3 py-2 text-[11px] text-slate-400">No equipment in this row yet.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</aside>
