{{-- Bottom-right panel — Attendance Rate reference style --}}
<div class="shrink-0 rounded-[24px] border border-slate-200/80 bg-white p-5 shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-[15px] font-bold text-[#1A1A1A]">Room health rate</h3>
        <button
            type="button"
            class="flex h-7 w-7 items-center justify-center text-[#AAAAAA] transition hover:text-[#666666]"
            aria-label="More options"
        >
            <i data-lucide="ellipsis-vertical" class="h-4 w-4"></i>
        </button>
    </div>

    <div class="mt-4 space-y-4">
        <template x-if="floorHealthRooms.length === 0">
            <p class="py-3 text-center text-xs text-[#999999]">No rooms on this floor yet.</p>
        </template>

        <template x-for="room in floorHealthRooms" :key="'health-' + room.id">
            <button
                type="button"
                @click="focusInsightRoom(room.id)"
                class="flex w-full items-center gap-3 text-left transition hover:opacity-85"
            >
                <span
                    class="h-9 w-9 shrink-0 rounded-full ring-2 ring-white"
                    :style="`background:${room.color}`"
                ></span>
                <span class="flex min-w-0 flex-1 items-center gap-3">
                    <span class="w-[5.5rem] shrink-0 truncate text-[13px] font-medium text-[#333333]" x-text="room.name"></span>
                    <span class="h-[5px] min-w-0 flex-1 overflow-hidden rounded-full bg-[#E5E5E5]">
                        <span
                            class="block h-full rounded-full bg-[#3B82F6] transition-all duration-500"
                            :style="`width:${room.health}%`"
                        ></span>
                    </span>
                    <span class="w-9 shrink-0 text-right text-[13px] font-semibold text-[#1A1A1A]" x-text="room.health + '%'"></span>
                </span>
            </button>
        </template>
    </div>

    <div class="mt-5 flex items-center gap-4 border-t border-[#E0E0E0] pt-4">
        <p class="min-w-0 flex-1 text-[13px] font-bold text-[#1A1A1A]">
            <span x-text="floorStats.equipment"></span> assets tracked
        </p>
        <span class="h-4 w-px shrink-0 bg-[#DDDDDD]"></span>
        <p class="shrink-0 text-[13px] font-normal text-[#888888]">
            <span x-text="floorStats.todayReports"></span> reports today
        </p>
    </div>

    <button
        type="button"
        @click="scrollToFloorInsights()"
        class="mt-4 flex w-full items-center justify-center rounded-xl bg-[#E8E8E8] py-3.5 text-[13px] font-semibold text-[#333333] transition hover:bg-[#DDDDDD]"
    >
        View detailed report
    </button>
</div>
