<aside
    class="min-h-[720px] overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-xl shadow-slate-900/5"
>
    <div
        x-show="selectedRoom === null"
        class="flex h-full min-h-[720px] flex-col"
    >
        <div class="bg-slate-950 p-7 text-white">
            <div
                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#005EA6]"
            >
                <i data-lucide="panel-right-open" class="h-6 w-6"></i>
            </div>
            <h2 class="mt-5 text-xl font-extrabold">Room intelligence</h2>
            <p class="mt-2 text-sm leading-6 text-slate-400">Select a room block to open its live monitoring workspace.</p>
        </div>
        <div class="flex flex-1 items-center justify-center p-8">
            <div class="text-center">
                <div
                    class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-slate-50 ring-1 ring-slate-100"
                >
                    <i
                        data-lucide="mouse-pointer-click"
                        class="h-10 w-10 text-slate-300"
                    ></i>
                </div>
                <p class="mt-5 text-sm font-bold text-slate-700">Choose a room on the map</p>
                <p class="mt-2 text-xs leading-5 text-slate-400">Assets, ticket trends, recurring issues, and schedules will appear here.</p>
            </div>
        </div>
    </div>

    @foreach ($rooms as $room)
        <div
            x-show="selectedRoom === {{ $room->room_id }}"
            x-cloak
            x-data="{ tab: 'equipment' }"
            class="min-h-[720px]"
        >
            <div class="relative overflow-hidden bg-slate-950 p-6 text-white">
                <div
                    class="absolute -right-12 -top-12 h-36 w-36 rounded-full bg-[#005EA6]/30 blur-2xl"
                ></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#FFF200]">{{ $room->floor->building->building_name ?? "STI Ormoc" }} · {{ $room->floor->floor_level }}</p>
                        <h2 class="mt-2 text-2xl font-extrabold">
                            {{ $room->room_name }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-400">{{
                            $room->room_type ?:
                                "Unclassified room"
                        }}</p>
                    </div>
                    <button
                        @click="selectedRoom = null"
                        class="rounded-xl bg-white/10 p-2 hover:bg-white/20"
                        aria-label="Close room details"
                    >
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="relative mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->equipment->sum(
                                "equipment_quantity",
                            )
                        }}</b
                        ><span class="text-[10px] text-slate-400">Assets</span>
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->monitoring[
                                "active_reports"
                            ]
                        }}</b
                        ><span class="text-[10px] text-slate-400"
                            >Active tickets</span
                        >
                    </div>
                    <div class="rounded-xl bg-white/5 p-3">
                        <b class="block text-lg">{{
                            $room->equipment
                                ->where("equipment_condition_status", "Good")
                                ->sum("equipment_quantity")
                        }}</b
                        ><span class="text-[10px] text-slate-400">Healthy</span>
                    </div>
                </div>
            </div>

            <div class="border-b border-slate-100 p-2">
                <div
                    class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 text-[11px] font-bold"
                >
                    <button
                        @click="tab = 'equipment'"
                        :class="tab === 'equipment'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Equipment
                    </button>
                    <button
                        @click="tab = 'analytics'"
                        :class="tab === 'analytics'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Analytics
                    </button>
                    <button
                        @click="tab = 'schedule'"
                        :class="tab === 'schedule'
                            ? 'bg-white text-[#005EA6] shadow-sm'
                            : 'text-slate-500'"
                        class="rounded-lg px-2 py-2.5"
                    >
                        Schedule
                    </button>
                </div>
            </div>

            <div class="max-h-[475px] overflow-y-auto p-5">
                <div x-show="tab === 'equipment'" class="space-y-3">
                    @forelse ($room->equipment as $item)
                        @php
                            $healthy = $item->equipment_condition_status === "Good";
                            $maintenance = $item->equipment_condition_status === "Under Maintenance";
                        @endphp
                        <article
                            class="rounded-2xl border border-slate-100 bg-slate-50 p-4 transition hover:border-slate-200 hover:bg-white hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 gap-3">
                                    <span
                                        class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $healthy ? 'bg-emerald-500' : ($maintenance ? 'bg-amber-400' : 'bg-red-500') }}"
                                    ></span>
                                    <div class="min-w-0">
                                        <h3
                                            class="truncate text-sm font-bold text-slate-800"
                                        >
                                            {{ $item->equipment_name }}
                                        </h3>
                                        <p class="mt-1 text-[11px] text-slate-500">{{
                                            $item->equipment_asset_tag ?:
                                                "No asset tag"
                                        }} · Qty {{ $item->equipment_quantity }}</p>
                                    </div>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2 py-1 text-[9px] font-extrabold {{ $healthy ? 'bg-emerald-100 text-emerald-700' : ($maintenance ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}"
                                    >{{ $item->equipment_condition_status }}</span
                                >
                            </div>
                            <div
                                class="mt-3 flex items-center gap-2 border-t border-slate-200 pt-3 text-[10px] font-semibold text-slate-500"
                            >
                                <i
                                    data-lucide="map-pin"
                                    class="h-3 w-3 text-[#005EA6]"
                                ></i
                                >{{
                                    $item->equipment_placement_zone ?:
                                        ($item->equipment_current_location ?:
                                            "Placement not plotted")
                                }}
                            </div>
                        </article>
                    @empty
                        <div
                            class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400"
                        >
                            No equipment provisioned in this room.
                        </div>
                    @endforelse
                </div>

                <div x-show="tab === 'analytics'" x-cloak>
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Report volume
                    </h3>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ([
                                "Today" => "today_reports",
                                "Weekly" => "week_reports",
                                "Monthly" => "month_reports"
                            ]
                            as $label => $key)
                            <div
                                class="rounded-2xl border border-slate-100 p-3 text-center"
                            >
                                <strong class="block text-2xl text-slate-900">{{
                                    $room->monitoring[
                                        $key
                                    ]
                                }}</strong
                                ><span
                                    class="text-[10px] font-semibold text-slate-400"
                                    >{{ $label }}</span
                                >
                            </div>
                        @endforeach
                    </div>
                    <h3
                        class="mt-6 text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Frequent problems
                    </h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($room->monitoring["frequent_problems"] as $problem)
                            <div
                                class="flex items-start justify-between gap-3 rounded-xl bg-red-50 p-3 text-xs"
                            >
                                <span
                                    class="leading-5 text-slate-700"
                                    >{{ $problem->report_problem_description }}</span
                                ><b
                                    class="rounded-md bg-white px-2 py-1 text-red-600"
                                    >{{ $problem->occurrences }}×</b
                                >
                            </div>
                        @empty
                            <p class="rounded-xl bg-slate-50 p-4 text-xs text-slate-400">No recurring problems recorded.</p>
                        @endforelse
                    </div>
                </div>

                <div x-show="tab === 'schedule'" x-cloak>
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-slate-400"
                    >
                        Upcoming maintenance
                    </h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($room->monitoring["schedules"] as $schedule)
                            <article
                                class="flex gap-3 rounded-2xl border border-slate-100 p-4"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-blue-50 text-[#005EA6]"
                                >
                                    <b class="text-sm">{{
                                        \Carbon\Carbon::parse(
                                            $schedule->maintenance_schedule_next_date,
                                        )->format("d")
                                    }}</b
                                    ><span
                                        class="text-[8px] font-bold uppercase"
                                        >{{
                                            \Carbon\Carbon::parse(
                                                $schedule->maintenance_schedule_next_date,
                                            )->format("M")
                                        }}</span
                                    >
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-bold text-slate-800"
                                    >
                                        {{ $schedule->maintenance_schedule_title }}
                                    </h4>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $schedule->equipment_name }} · {{ $schedule->maintenance_schedule_status }}</p>
                                </div>
                            </article>
                        @empty
                            <div
                                class="rounded-2xl border border-dashed border-slate-200 p-8 text-center"
                            >
                                <i
                                    data-lucide="calendar-check"
                                    class="mx-auto h-8 w-8 text-slate-300"
                                ></i>
                                <p class="mt-3 text-sm text-slate-400">No active schedule.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</aside>
