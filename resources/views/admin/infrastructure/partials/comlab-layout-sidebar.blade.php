{{-- Comlab row layout sidebar --}}
<aside
    class="flex h-full min-h-0 flex-1 flex-col border-l border-slate-200 bg-white"
>
    <template x-if="roomLayout.selectedAssetId && selectedLayoutAsset()">
        <div class="flex min-h-0 flex-1 flex-col">
            <div class="border-b border-slate-100 px-4 py-3">
                <button type="button" @click="comlabBack(); roomLayout.lifecycle = { loading: false, data: null, equipmentId: null }" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-900">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                    Back
                </button>
                <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Asset details</p>
                <h3 class="mt-1 truncate text-base font-semibold text-slate-900" x-text="selectedLayoutAsset()?.asset_tag || selectedLayoutAsset()?.name"></h3>
                <p class="text-xs text-slate-500" x-text="comlabAssetPlacementHint(selectedLayoutAsset())"></p>
            </div>
            @include('admin.infrastructure.partials.layout-asset-details')
            <div
                x-show="roomLayout.edit && selectedLayoutAsset() && isComlabRowZone(selectedLayoutAsset()?.placement_zone || selectedLayoutAsset()?.location)"
                class="border-t border-slate-100 px-4 py-3"
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
    </template>

    {{-- Selected floor/holding computer set: members list in sidebar (canvas stays on room layout) --}}
    <template x-if="(!roomLayout.selectedAssetId || !selectedLayoutAsset()) && roomLayout.selectedFloorSetId && activeFloorComputerSet() && roomLayout.comlabNav === 'rows'">
        <div class="flex min-h-0 flex-1 flex-col">
            <div class="border-b border-slate-100 px-4 py-3">
                <button type="button" @click="clearFloorComputerSetSelection()" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-900">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                    Back
                </button>
                <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Computer set</p>
                <h3 class="mt-1 truncate text-base font-semibold text-slate-900" x-text="activeFloorComputerSet()?.label || activeFloorComputerSet()?.tag || 'Computer set'"></h3>
                <p class="truncate font-mono text-[11px] text-slate-400" x-show="activeFloorComputerSet()?.tag" x-text="activeFloorComputerSet()?.tag"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="(activeFloorComputerSet()?.member_count || (activeFloorComputerSet()?.members || []).length || 0) + ' components · ' + (computerSetMemberZone(activeFloorComputerSet()) === 'Holding' ? 'Holding' : 'Floor')"></p>
                <p class="mt-1 text-[11px] font-semibold text-rose-600" x-show="(activeFloorComputerSet()?.needs_replacement_count || 0) > 0" x-text="activeFloorComputerSet().needs_replacement_count + ' need replacement'"></p>
                <a
                    x-show="activeFloorComputerSet()?.manage_url"
                    :href="activeFloorComputerSet()?.manage_url"
                    target="_blank"
                    rel="noopener"
                    class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#005EA6] hover:underline"
                >
                    Open in Computer Sets
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                </a>
            </div>
            <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 py-2">
                <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Components</p>
                <template x-for="member in (activeFloorComputerSet()?.members || [])" :key="'sidebar-set-member-' + member.id">
                    <button
                        type="button"
                        @click="selectFloorSetMember(member.id)"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50"
                    >
                        <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(member.name)"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900" x-text="member.name"></span>
                            <span class="block truncate text-[11px] text-slate-400" x-text="(member.role_label ? member.role_label + ' · ' : '') + (member.asset_tag || 'No tag')"></span>
                            <span class="mt-0.5 block text-[10px] font-semibold text-rose-600" x-show="member.needs_replacement">Needs replacement</span>
                            <span
                                class="mt-0.5 block text-[10px] font-semibold text-amber-700"
                                x-show="comlabMemberPresenceHint(member)"
                                x-text="comlabMemberPresenceHint(member)"
                            ></span>
                        </span>
                    </button>
                </template>
                <p x-show="!(activeFloorComputerSet()?.members || []).length" class="px-3 py-2 text-[11px] text-slate-400">No components in this computer set.</p>
            </div>
            <div class="border-t border-slate-100 px-4 py-3" x-show="roomLayout.edit">
                <button
                    type="button"
                    @click="sendComputerSetToHolding(activeFloorComputerSet().id)"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100"
                >
                    <i data-lucide="inbox" class="h-4 w-4"></i>
                    Move set → Holding
                </button>
            </div>
        </div>
    </template>

    <template x-if="(!roomLayout.selectedAssetId || !selectedLayoutAsset()) && !(roomLayout.selectedFloorSetId && activeFloorComputerSet() && roomLayout.comlabNav === 'rows')">
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
                        ? (comlabHoldingUnitCount()
                            ? 'Drag Holding sets/items onto a row or the floor to place them.'
                            : 'Arrange row tables on the floor. Drop sets on rows or place icons on the floor.')
                        : (comlabHoldingUnitCount()
                            ? 'Turn on Edit layout, then drag Holding sets/items onto a row or the floor.'
                            : (isComputerLabLayout()
                                ? 'Click Open on a row to browse computer sets and equipment.'
                                : 'Click a floor computer set — components open here in the sidebar.')))
                    : (rowUsesComputerSetUi() ? 'Browse sets and equipment.' : 'Browse equipment in this row.')"></p>
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

                    <p class="mt-3 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Floor computer sets</p>
                    <template x-for="set in comlabFloorComputerSets()" :key="'sidebar-floor-set-' + set.id">
                        <div class="flex w-full items-center gap-1 rounded-xl px-2 py-1 transition hover:bg-slate-50" :class="Number(roomLayout.selectedFloorSetId) === Number(set.id) ? 'bg-[#0025cc]/8 ring-1 ring-[#0025cc]/20' : ''">
                            <button type="button" @click="openFloorComputerSet(set.id)" class="flex min-w-0 flex-1 items-center gap-3 px-1 py-1.5 text-left">
                                <span class="inline-flex h-5 w-5 items-center justify-center text-[#0025cc]">
                                    <i data-lucide="pc-case" class="h-5 w-5"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800" x-text="set.label || set.tag || 'Computer set'"></span>
                                    <span class="block truncate text-[11px] text-slate-400" x-text="(set.member_count || (set.members || []).length || 0) + ' components'"></span>
                                </span>
                            </button>
                            <button
                                type="button"
                                x-show="roomLayout.edit"
                                @click="sendComputerSetToHolding(set.id)"
                                class="shrink-0 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-50"
                                title="Move computer set to holding"
                            >
                                To holding
                            </button>
                        </div>
                    </template>
                    <p x-show="!comlabFloorComputerSets().length" class="px-3 py-2 text-[11px] text-slate-400">No computer sets on floor.</p>

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
                    <p x-show="!comlabCanvasEquipment().length" class="px-3 py-2 text-[11px] text-slate-400">No loose floor equipment.</p>

                    <div
                        class="mt-3 px-2"
                        x-show="roomLayout.edit"
                        x-cloak
                    >
                        <button
                            type="button"
                            @click="placeFromStorageModal = true"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-[#005EA6]/20 bg-[#005EA6]/5 px-3 py-2 text-[11px] font-semibold text-[#005EA6] hover:bg-[#005EA6]/10"
                            title="Assign RR-stocked storage equipment into Holding"
                        >
                            <i data-lucide="package-plus" class="h-3.5 w-3.5"></i>
                            Add from storage
                        </button>
                    </div>

                    <div x-show="comlabHoldingUnitCount() > 0 || roomLayout.edit" x-cloak>
                        <p class="mt-3 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Holding area</p>
                        <p class="px-3 pb-1 text-[11px] leading-4 text-slate-400" x-text="roomLayout.edit
                            ? (comlabHoldingUnitCount()
                                ? 'Drag a computer set or item onto a row or the floor to place it.'
                                : 'Add from storage — new items land here until you drag them onto the canvas.')
                            : 'Turn on Edit layout to drag these into the room.'"></p>
                        <p
                            x-show="roomLayout.edit && comlabHoldingUnitCount() === 0"
                            class="px-3 py-2 text-[11px] text-slate-400"
                        >
                            Holding is empty.
                        </p>
                        <template x-for="set in comlabHoldingComputerSets()" :key="'sidebar-hold-set-' + set.id">
                            <button
                                type="button"
                                @click="openFloorComputerSet(set.id)"
                                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition"
                                :class="Number(roomLayout.selectedFloorSetId) === Number(set.id) ? 'bg-[#0025cc]/8 ring-1 ring-[#0025cc]/20' : 'hover:bg-slate-50'"
                            >
                                <span class="inline-flex h-5 w-5 items-center justify-center text-[#0025cc]">
                                    <i data-lucide="pc-case" class="h-5 w-5"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-slate-800" x-text="set.label || set.tag || 'Computer set'"></span>
                                    <span class="block text-[11px] text-slate-400" x-text="(set.member_count || (set.members || []).length || 0) + ' components · click to open'"></span>
                                </span>
                            </button>
                        </template>
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
                                    <span class="block text-[11px] text-slate-400" x-text="roomLayout.edit ? 'Drag onto a row or the floor' : 'Edit layout to place'"></span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <p class="mt-3 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="isComputerLabLayout() ? 'Set parts (in rows)' : 'Equipment (in rows)'"></p>
                    <template x-for="item in (roomLayout.equipment || []).filter(i => isComlabRowAssignedSetItem(i) && !isEquipmentInComputerSet(i.id))" :key="'sidebar-setpart-' + item.id">
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
                    <template x-if="rowUsesComputerSetUi()">
                        <div>
                            <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Computer sets</p>
                            <template x-for="(set, idx) in comlabSetsInSelectedRow()" :key="'sidebar-set-' + (set.id || set.index)">
                                <button type="button" @click="selectComlabSet(idx)" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition" :class="roomLayout.comlabSetCarouselIndex === idx ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20' : 'hover:bg-slate-50'">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold text-slate-600" x-text="String(idx + 1)"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-slate-900" x-text="set.label"></span>
                                        <span class="block truncate font-mono text-[10px] text-slate-500" x-show="set.tag" x-text="set.tag"></span>
                                        <span class="block truncate text-[11px] text-slate-400" x-text="set.members.map(m => m.name).join(', ') || 'No members'"></span>
                                        <span class="mt-0.5 block text-[10px] font-semibold text-rose-600" x-show="set.needs_replacement_count > 0" x-text="set.needs_replacement_count + ' need replacement'"></span>
                                    </span>
                                </button>
                            </template>
                            <p
                                x-show="!comlabSetsInSelectedRow().length"
                                class="px-3 py-2 text-[11px] text-slate-400"
                            >
                                No computer sets for this row yet.
                            </p>
                            <template x-if="comlabOtherInSelectedRow().length > 0">
                                <div class="mt-3">
                                    <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Other in row</p>
                                    <template x-for="item in comlabOtherInSelectedRow()" :key="'sidebar-row-other-' + item.id">
                                        <button type="button" @click="selectComlabAsset(item.id)" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition" :class="roomLayout.selectedAssetId === item.id ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/20' : 'hover:bg-slate-50'">
                                            <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800" x-text="item.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!rowUsesComputerSetUi()">
                        <div>
                            <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">In this row</p>
                            <template x-for="item in looseEquipmentInComlabRow(roomLayout.selectedComlabRow)" :key="'sidebar-row-item-' + item.id">
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
                            <p x-show="!looseEquipmentInComlabRow(roomLayout.selectedComlabRow).length" class="px-3 py-2 text-[11px] text-slate-400">No equipment in this row yet.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</aside>
