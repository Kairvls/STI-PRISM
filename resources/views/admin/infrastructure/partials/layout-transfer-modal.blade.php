{{-- Transfer the selected Room Interior Layout asset to another room --}}
<template x-if="layoutTransferModal">
    <div
        x-transition.opacity
        x-init="document.body.style.overflow = 'hidden'; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
        @click.self="closeLayoutTransferModal()"
        @keydown.escape.window="closeLayoutTransferModal()"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-[#0b1220]/70 p-4 sm:p-6"
    >
        <div
            class="flex max-h-[min(90dvh,32rem)] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            @click.stop
        >
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        <span x-text="layoutTransferSet ? 'Transfer computer set' : 'Transfer to another room'"></span>
                    </h2>
                    <p class="mt-1 truncate text-sm text-slate-500">
                        <template x-if="layoutTransferSet">
                            <span>
                                <span x-text="layoutTransferSet.tag || layoutTransferSet.name || 'Computer set'"></span>
                                <span x-show="layoutTransferSet.member_count" x-text="' · ' + layoutTransferSet.member_count + ' component' + (Number(layoutTransferSet.member_count) === 1 ? '' : 's')"></span>
                            </span>
                        </template>
                        <template x-if="!layoutTransferSet">
                            <span>
                                <span x-text="layoutTransferAsset?.name || 'Equipment'"></span>
                                <span x-show="layoutTransferAsset?.asset_tag" x-text="' · ' + layoutTransferAsset.asset_tag"></span>
                            </span>
                        </template>
                    </p>
                    <p
                        x-show="layoutTransferSet"
                        class="mt-2 text-xs leading-snug text-slate-500"
                    >
                        Moves the set’s room and every part still with the set. Deployed parts stay where they are.
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeLayoutTransferModal()"
                    aria-label="Close"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-5 py-4">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        x-model="layoutTransferQuery"
                        placeholder="Search room…"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#005EA6] focus:ring-2 focus:ring-blue-100"
                    />
                </div>

                <p x-show="layoutTransferError" x-cloak class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="layoutTransferError"></p>

                <div class="space-y-1">
                    <template x-if="layoutTransferDestRooms().length === 0">
                        <p class="py-8 text-center text-sm text-slate-500">No other rooms found.</p>
                    </template>
                    <template x-for="room in layoutTransferDestRooms()" :key="'layout-xfer-' + room.id">
                        <button
                            type="button"
                            @click="layoutTransferRoomId = room.id"
                            class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left transition"
                            :class="Number(layoutTransferRoomId) === Number(room.id)
                                ? 'bg-[#005EA6]/8 ring-1 ring-[#005EA6]/25'
                                : 'hover:bg-slate-50'"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-slate-900" x-text="room.name"></span>
                                <span class="block truncate text-[11px] text-slate-400" x-text="room.type || 'Room'"></span>
                            </span>
                            <i
                                data-lucide="check"
                                class="h-4 w-4 shrink-0 text-[#005EA6]"
                                x-show="Number(layoutTransferRoomId) === Number(room.id)"
                            ></i>
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-5 py-4">
                <button
                    type="button"
                    @click="closeLayoutTransferModal()"
                    class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="confirmLayoutTransfer()"
                    :disabled="layoutTransferring || !layoutTransferRoomId"
                    class="h-10 rounded-xl bg-[#005EA6] px-5 text-sm font-semibold text-white transition hover:bg-[#004b86] disabled:cursor-not-allowed disabled:opacity-50"
                    x-text="layoutTransferring ? 'Transferring…' : (layoutTransferSet ? 'Transfer set' : 'Transfer')"
                ></button>
            </div>
        </div>
    </div>
</template>
