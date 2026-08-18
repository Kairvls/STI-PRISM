{{-- Fills the leftover space under the rooms layout, left of the room drawer --}}
<section
    class="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain rounded-[28px] border border-slate-200 bg-white shadow-xl lg:overflow-hidden"
    x-effect="activeFloor; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
>
    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-400">
                Floor snapshot
            </p>
            <h2 class="mt-0.5 truncate text-base font-black text-slate-950">
                <span x-text="activeFloorLabel"></span>
                <span class="font-semibold text-slate-400">overview</span>
            </h2>
        </div>
        <span class="hidden shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500 sm:inline">
            <span x-text="floorStats.rooms"></span> rooms
        </span>
    </div>

    <div class="grid shrink-0 grid-cols-2 gap-2 border-b border-slate-100 p-4 lg:grid-cols-4">
        <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
            <p class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Attention</p>
            <p class="mt-1 text-xl font-black text-slate-950" x-text="floorStats.attention"></p>
        </div>
        <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
            <p class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Reports</p>
            <p class="mt-1 text-xl font-black text-slate-950" x-text="floorStats.reports"></p>
        </div>
        <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
            <p class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Today</p>
            <p class="mt-1 text-xl font-black text-slate-950" x-text="floorStats.todayReports"></p>
        </div>
        <div class="rounded-2xl bg-slate-50 px-3 py-2.5">
            <p class="text-[10px] font-bold uppercase tracking-[.14em] text-slate-400">Damaged</p>
            <p class="mt-1 text-xl font-black text-slate-950" x-text="floorStats.damaged"></p>
        </div>
    </div>

    <div class="grid min-h-0 flex-1 grid-cols-1 overflow-hidden lg:grid-cols-2 lg:grid-rows-[minmax(0,1fr)]">
        <div class="flex min-h-0 flex-col overflow-hidden border-b border-slate-100 lg:border-b-0 lg:border-r">
            <div class="flex shrink-0 items-center justify-between px-4 py-3">
                <h3 class="text-sm font-black text-slate-900">Needs attention</h3>
                <span class="text-[11px] font-bold text-slate-400" x-text="attentionRooms.length + ' rooms'"></span>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div x-show="attentionRooms.length === 0" class="px-4 py-8 text-center">
                    <p class="text-sm font-bold text-slate-800">This floor looks clear</p>
                    <p class="mt-1 text-xs text-slate-400">No critical rooms or open reports right now.</p>
                </div>

                <template x-for="room in attentionRooms" :key="'attention-' + room.id">
                    <button
                        type="button"
                        @click="focusInsightRoom(room.id)"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-50"
                        :class="selectedRoom === room.id ? 'bg-sky-50/70' : ''"
                    >
                        <span
                            class="h-8 w-1.5 shrink-0 rounded-full"
                            :class="{
                                'bg-red-500': room.tone === 'critical',
                                'bg-amber-400': room.tone === 'maintenance',
                                'bg-sky-500': room.tone === 'normal',
                            }"
                        ></span>
                        <span
                            class="h-8 w-8 shrink-0 rounded-xl border border-slate-200"
                            :style="`background:${room.color || '#E2E8F0'}`"
                        ></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-slate-900" x-text="room.name"></span>
                            <span class="block truncate text-[11px] font-medium text-slate-400" x-text="(room.reports || 0) + ' reports · ' + (room.status || 'Normal')"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        <div class="flex min-h-0 flex-col overflow-hidden">
            <div class="flex shrink-0 items-center justify-between px-4 py-3">
                <h3 class="text-sm font-black text-slate-900">Upcoming work</h3>
                <i data-lucide="calendar-clock" class="h-4 w-4 text-slate-400"></i>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                <div x-show="upcomingSchedules.length === 0" class="px-4 py-6 text-center">
                    <p class="text-sm font-bold text-slate-800">No scheduled work</p>
                    <p class="mt-1 text-xs text-slate-400">Nothing queued for this floor.</p>
                </div>

                <template x-for="item in upcomingSchedules" :key="'schedule-' + item.roomId + '-' + item.title + '-' + item.date">
                    <button
                        type="button"
                        @click="focusInsightRoom(item.roomId)"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-50"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-slate-900" x-text="item.title"></span>
                            <span class="block truncate text-[11px] font-medium text-slate-400" x-text="item.roomName"></span>
                        </span>
                        <span
                            class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold"
                            :class="scheduleIsOverdue(item.date) ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-500'"
                            x-text="formatInsightDate(item.date)"
                        ></span>
                    </button>
                </template>

                <div class="border-t border-slate-100 px-4 py-3" x-show="floorHotIssues.length > 0">
                    <p class="mb-2 text-[10px] font-extrabold uppercase tracking-[.16em] text-slate-400">Recurring issues</p>
                    <div class="space-y-1.5">
                        <template x-for="issue in floorHotIssues" :key="'issue-' + issue.label">
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                <p class="min-w-0 truncate text-xs font-semibold text-slate-800" x-text="issue.label"></p>
                                <span class="shrink-0 text-[10px] font-bold text-slate-500" x-text="issue.count + 'x'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
