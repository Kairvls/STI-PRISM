{{-- Place RR-stocked storage equipment into the current room --}}
<template x-if="placeFromStorageModal">
    <div
        x-transition.opacity
        x-init="document.body.style.overflow = 'hidden'; openPlaceFromStorage()"
        @click.self="closePlaceFromStorage()"
        @keydown.escape.window="closePlaceFromStorage()"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#0b1220]/70 p-4 sm:p-6"
    >
        <div
            class="
                flex
                max-h-[min(90dvh,40rem)]
                w-full
                max-w-2xl
                flex-col
                overflow-hidden
                rounded-2xl
                border
                border-slate-200
                bg-white
                shadow-2xl
            "
            @click.stop
        >
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Add Equipment from storage
                    </h2>
                    <!--<p class="mt-1 text-sm text-slate-500">
                        Pick Active equipment already stocked from an RR. Stocking stays on Inventory.
                    </p>-->
                </div>
                <button
                    type="button"
                    @click="closePlaceFromStorage()"
                    aria-label="Close"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="shrink-0 border-b border-slate-100 px-5 py-3">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        x-model="placeQuery"
                        placeholder="Search name, tag, serial, RR…"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                    />
                </div>
                <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                    <span>
                        <span x-text="placeSelectedIds.length"></span>
                        selected
                        ·
                        <span x-text="filteredPlaceableStock().length"></span>
                        shown
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="selectAllPlaceableStock()"
                            class="font-semibold text-[#005EA6] transition hover:text-[#004b86]"
                        >
                            Select all
                        </button>
                        <span class="text-slate-300">|</span>
                        <button
                            type="button"
                            @click="placeSelectedIds = []"
                            class="font-semibold text-slate-500 transition hover:text-slate-800"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3">
                <template x-if="placeableStockLoading">
                    <div class="space-y-2" role="status" aria-label="Loading storage stock">
                        <template x-for="i in [1, 2, 3, 4, 5]" :key="'place-skel-' + i">
                            <div class="flex animate-pulse items-start gap-3 rounded-xl border border-slate-200 px-3 py-3">
                                <div class="mt-1 h-4 w-4 shrink-0 rounded border border-slate-200 bg-slate-200"></div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="h-4 w-28 rounded bg-slate-200"></div>
                                        <div class="h-4 w-20 rounded-md bg-slate-100"></div>
                                    </div>
                                    <div class="h-3 w-48 max-w-full rounded bg-slate-100"></div>
                                    <div class="h-3 w-32 rounded bg-slate-100"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!placeableStockLoading && placeableStockError">
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100" x-text="placeableStockError"></div>
                </template>

                <template x-if="!placeableStockLoading && !placeableStockError && filteredPlaceableStock().length === 0">
                    <div class="py-10 text-center">
                        <p class="text-sm font-medium text-slate-800">No RR-stocked items in storage</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Stock verified RR lines from Inventory first, then place them here.
                        </p>
                    </div>
                </template>

                <div class="space-y-2" x-show="!placeableStockLoading && filteredPlaceableStock().length">
                    <template x-for="item in filteredPlaceableStock()" :key="item.id">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 transition hover:border-slate-300 hover:bg-slate-50"
                            :class="placeSelectedIds.includes(item.id) ? 'border-[#005EA6]/40 bg-blue-50/50' : ''"
                        >
                            <input
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-[#005EA6] focus:ring-[#005EA6]"
                                :checked="placeSelectedIds.includes(item.id)"
                                @change="togglePlaceStockId(item.id)"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-900" x-text="item.name"></p>
                                    <span
                                        class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600"
                                        x-text="item.storage_room_name || 'Storage'"
                                    ></span>
                                </div>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                    <span x-show="item.asset_tag" x-text="'Tag ' + item.asset_tag"></span>
                                    <span x-show="item.asset_tag && item.serial_number"> · </span>
                                    <span x-show="item.serial_number" x-text="'S/N ' + item.serial_number"></span>
                                    <span x-show="!item.asset_tag && !item.serial_number" x-text="item.category_name || 'Equipment'"></span>
                                </p>
                                <p class="mt-1 text-[11px] font-medium text-[#005EA6]" x-show="item.receiving_report_number" x-text="item.receiving_report_number"></p>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                <button
                    type="button"
                    @click="closePlaceFromStorage()"
                    class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="confirmPlaceFromStorage()"
                    :disabled="placingFromStorage || placeSelectedIds.length === 0"
                    class="h-10 rounded-xl bg-[#005EA6] px-5 text-sm font-semibold text-white transition hover:bg-[#004b86] disabled:cursor-not-allowed disabled:opacity-50"
                    x-text="placingFromStorage
                        ? 'Placing…'
                        : (placeSelectedIds.length
                            ? ('Place ' + placeSelectedIds.length + (placeSelectedIds.length === 1 ? ' asset' : ' assets'))
                            : 'Place')"
                ></button>
            </div>
        </div>
    </div>
</template>
