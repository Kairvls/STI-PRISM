{{-- Row detail: computer set carousel (labs) or equipment list (other rooms) --}}
<div class="shrink-0 border-b border-slate-100 px-4 py-3">
    <button type="button" @click="comlabNavTo('rows')" class="mb-1 inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 hover:text-slate-800">
        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
        Back to room layout
    </button>
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="isComputerLabLayout() ? 'Computer sets' : 'Row equipment'"></p>
            <p class="text-xs text-slate-500" x-text="roomLayout.selectedComlabRow"></p>
        </div>
        <span
            class="text-xs font-medium text-slate-500"
            x-show="isComputerLabLayout() && comlabSetsInSelectedRow().length"
            x-text="comlabSetCarouselPageLabel()"
        ></span>
        <span
            class="text-xs font-medium text-slate-500"
            x-show="!isComputerLabLayout()"
            x-text="comlabRowItemsLabel(roomLayout.selectedComlabRow)"
        ></span>
    </div>

    <div class="relative mt-3" x-show="isComputerLabLayout() && comlabSetsInSelectedRow().length > 0">
        <button
            type="button"
            x-show="comlabSetCarouselPageCount() > 1"
            @click="comlabCarouselPrev()"
            :disabled="comlabSetCarouselPage() <= 0"
            class="absolute left-0 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-35"
        >
            <i data-lucide="chevron-left" class="h-4 w-4"></i>
        </button>
        <button
            type="button"
            x-show="comlabSetCarouselPageCount() > 1"
            @click="comlabCarouselNext()"
            :disabled="comlabSetCarouselPage() >= comlabSetCarouselPageCount() - 1"
            class="absolute right-0 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-35"
        >
            <i data-lucide="chevron-right" class="h-4 w-4"></i>
        </button>
        <div
            class="overflow-hidden"
            :class="comlabSetCarouselPageCount() > 1 ? 'px-11' : 'px-0'"
        >
            <div
                class="flex justify-start will-change-transform"
                :style="'transform: translate3d(-' + (comlabSetCarouselPage() * 100) + '%, 0, 0); transition: transform 480ms cubic-bezier(0.22, 1, 0.36, 1);'"
            >
                <template x-for="(set, idx) in comlabSetsInSelectedRow()" :key="'carousel-' + set.index">
                    <div class="w-1/3 shrink-0 px-1">
                        <button
                            type="button"
                            @click="selectComlabSet(idx)"
                            class="w-full rounded-2xl border px-3 py-3 text-left transition-colors duration-200 sm:px-4 sm:py-4"
                            :class="roomLayout.comlabSetCarouselIndex === idx ? 'border-[#005EA6] bg-[#005EA6] text-white shadow-md' : 'border-slate-200 bg-slate-50 text-slate-600'"
                        >
                            <p class="truncate text-sm font-semibold sm:text-base" x-text="set.label"></p>
                            <p class="mt-1 text-[11px] opacity-80" x-text="set.members.length + (set.members.length === 1 ? ' component' : ' components')"></p>
                        </button>
                    </div>
                </template>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-center gap-1.5" x-show="comlabSetCarouselPageCount() > 1">
            <template x-for="pageIdx in comlabSetCarouselPageCount()" :key="'set-page-dot-' + pageIdx">
                <button
                    type="button"
                    @click="comlabCarouselGoToPage(pageIdx - 1)"
                    class="h-1.5 rounded-full transition-all duration-300 ease-out"
                    :class="comlabSetCarouselPage() === (pageIdx - 1) ? 'w-5 bg-[#005EA6]' : 'w-1.5 bg-slate-300'"
                ></button>
            </template>
        </div>
    </div>
    <p x-show="isComputerLabLayout() && comlabSetsInSelectedRow().length === 0" class="mt-3 text-xs text-slate-400">No computer sets in this row yet.</p>
</div>

<div class="min-h-0 flex-1 overflow-y-auto p-4">
    {{-- Computer lab: active set members --}}
    <template x-if="isComputerLabLayout() && activeComlabSet()">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="member in activeComlabSet().members" :key="'member-' + member.id">
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3" :class="roomLayout.selectedAssetId === member.id ? 'border-[#005EA6] ring-1 ring-[#005EA6]/20' : ''">
                    <button type="button" @click="selectComlabAsset(member.id)" class="flex min-w-0 flex-1 items-center gap-3 text-left transition hover:opacity-90">
                        <span class="inline-flex h-5 w-5 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(member.name)"></span>
                        <span class="min-w-0">
                            <p class="truncate text-sm font-semibold" x-text="member.name"></p>
                            <p class="truncate text-[11px] text-slate-400" x-text="member.asset_tag || 'No tag'"></p>
                        </span>
                    </button>
                    <button
                        type="button"
                        x-show="roomLayout.edit"
                        @click="sendEquipmentToHolding(member.id)"
                        class="shrink-0 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-50"
                        title="Remove from row to holding area"
                    >
                        To holding
                    </button>
                </div>
            </template>
        </div>
    </template>
    <template x-if="isComputerLabLayout() && comlabOtherInSelectedRow().length > 0">
        <div class="mt-4">
            <p class="mb-2 text-xs font-medium text-slate-500">Other equipment in row</p>
            <div class="flex flex-col gap-2">
                <template x-for="item in comlabOtherInSelectedRow()" :key="'other-' + item.id">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <button type="button" @click="selectComlabAsset(item.id)" class="inline-flex min-w-0 flex-1 items-center gap-2 text-left text-sm">
                            <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                            <span class="truncate" x-text="item.quantity > 1 ? (item.name + ' × ' + item.quantity) : item.name"></span>
                        </button>
                        <button
                            type="button"
                            x-show="roomLayout.edit"
                            @click="sendEquipmentToHolding(item.id)"
                            class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-semibold text-amber-700 hover:bg-amber-100"
                            title="Remove from row to holding area"
                        >
                            To holding
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Other room types: flat equipment list with icons --}}
    <template x-if="!isComputerLabLayout()">
        <div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" x-show="equipmentInComlabRow(roomLayout.selectedComlabRow).length">
                <template x-for="item in equipmentInComlabRow(roomLayout.selectedComlabRow)" :key="'row-eq-' + item.id">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3" :class="roomLayout.selectedAssetId === item.id ? 'border-[#005EA6] ring-1 ring-[#005EA6]/20' : ''">
                        <button type="button" @click="selectComlabAsset(item.id)" class="flex min-w-0 flex-1 items-center gap-3 text-left transition hover:opacity-90">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center [&_svg]:h-full [&_svg]:w-full" x-html="equipmentIcon(item.name)"></span>
                            <span class="min-w-0">
                                <p class="truncate text-sm font-semibold" x-text="item.quantity > 1 ? (shortEquipmentName(item.name) + ' × ' + item.quantity) : shortEquipmentName(item.name)"></p>
                                <p class="truncate text-[11px] text-slate-400" x-text="item.asset_tag || item.name"></p>
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
            <p x-show="!equipmentInComlabRow(roomLayout.selectedComlabRow).length" class="text-xs text-slate-400">No equipment in this row yet. Edit layout and drop items from the holding area onto this row.</p>
        </div>
    </template>
</div>
