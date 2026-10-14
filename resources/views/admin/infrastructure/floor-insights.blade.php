{{-- Floor health overview — styled after Learning Progress reference --}}
<section
    data-floor-insights
    class="flex h-full min-h-0 w-full flex-1 flex-col overflow-hidden rounded-[30px] border border-slate-200/80 bg-white"
    x-effect="activeFloor; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
>
    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 px-6 pb-4 pt-5">
        <h2 class="text-[15px] font-bold tracking-tight text-[#1A1A1A]">
            Floor Health Overview
        </h2>
        <button
            type="button"
            class="flex items-center gap-1 text-[13px] font-normal text-[#888888] transition hover:text-[#444444]"
        >
            <span x-text="activeFloorLabel + ' · ' + floorStats.rooms + ' rooms'"></span>
            <i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>
        </button>
    </div>

    <div class="mx-6 border-t border-[#DDDDDD]"></div>

    {{-- Main --}}
    <div class="grid min-h-0 flex-1 grid-cols-1 items-center gap-6 px-6 py-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-6">
        {{-- Left column --}}
        <div class="flex min-w-0 w-full flex-1 flex-col">
            <p class="text-[13px] font-normal text-[#999999]">Overall Progress</p>

            <div class="mt-1 flex flex-wrap items-center gap-2.5">
                <p class="text-[34px] font-bold leading-none tracking-tight text-[#1A1A1A]">
                    <span x-text="floorHealthAverage"></span>% <span class="font-bold" x-text="floorHealthAverage >= 70 ? 'Healthy' : floorHealthAverage >= 40 ? 'Fair' : 'At Risk'"></span>
                </p>
                <span
                    x-show="floorStats.todayReports > 0"
                    class="rounded-full bg-[#D4F574] px-2.5 py-[3px] text-[11px] font-semibold text-[#1A1A1A]"
                    x-text="'+' + floorStats.todayReports + ' today'"
                ></span>
            </div>

            <div class="mt-7 w-full space-y-[18px]">
                <div class="flex items-center gap-3 text-[13px]">
                    <span class="flex-1 text-[#888888]">Rooms needing attention</span>
                    <span class="text-[#DDDDDD]">|</span>
                    <span class="w-10 text-right font-bold text-[#1A1A1A]" x-text="attentionRooms.length"></span>
                </div>
                <div class="flex items-center gap-3 text-[13px]">
                    <span class="flex-1 text-[#888888]">Open report volume</span>
                    <span class="text-[#DDDDDD]">|</span>
                    <span class="w-10 text-right font-bold text-[#1A1A1A]" x-text="floorStats.reports"></span>
                </div>
                <div class="flex items-center gap-3 text-[13px]">
                    <span class="flex-1 text-[#888888]">Assets tracked</span>
                    <span class="text-[#DDDDDD]">|</span>
                    <span class="w-10 text-right font-bold text-[#1A1A1A]" x-text="floorStats.equipment"></span>
                </div>
            </div>

            <div class="min-h-0 flex-1"></div>

            {{-- Goal bar (reference style — gray pill, left column) --}}
            <div class="mt-8 flex w-full items-center gap-2.5 rounded-full bg-[#D8D8D8] px-4 py-[10px]">
                <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[4px] bg-black">
                    <i data-lucide="check" class="h-2.5 w-2.5 text-white"></i>
                </span>
                <p class="truncate text-[12px] font-normal text-[#666666]">
                    <span x-text="floorHealthAverage"></span>% of your floor health target reached
                </p>
            </div>
        </div>

        {{-- Right: radar chart --}}
        <div class="relative flex items-center justify-center lg:justify-end">
            <svg viewBox="0 0 240 240" class="h-[210px] w-[210px] lg:h-[230px] lg:w-[230px]" aria-hidden="true">
                <defs>
                    <radialGradient id="floorRadarFill" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#4DA3FF" stop-opacity="0.7" />
                        <stop offset="45%" stop-color="#7EC0FF" stop-opacity="0.35" />
                        <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0" />
                    </radialGradient>
                    <marker id="floorRadarArrow" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto">
                        <path d="M0,0 L6,3 L0,6 Z" fill="#4DA3FF" />
                    </marker>
                </defs>

                {{-- Grid hexagons --}}
                <g fill="none" stroke="#D0D0D0" stroke-width="0.6">
                    <polygon points="120,28 188,67 188,173 120,212 52,173 52,67" />
                    <polygon points="120,46 172,78 172,162 120,194 68,162 68,78" opacity="0.75" />
                    <polygon points="120,64 156,89 156,151 120,176 84,151 84,89" opacity="0.5" />
                </g>

                {{-- Axis spokes with arrows --}}
                <g stroke="#4DA3FF" stroke-width="1" marker-end="url(#floorRadarArrow)">
                    <line x1="120" y1="120" x2="120" y2="32" />
                    <line x1="120" y1="120" x2="196" y2="72" />
                    <line x1="120" y1="120" x2="196" y2="168" />
                    <line x1="120" y1="120" x2="120" y2="208" />
                    <line x1="120" y1="120" x2="44" y2="168" />
                    <line x1="120" y1="120" x2="44" y2="72" />
                </g>

                {{-- Data shape --}}
                <polygon
                    :points="floorRadarPolygon"
                    fill="url(#floorRadarFill)"
                    stroke="#3B8FE8"
                    stroke-width="1.5"
                    stroke-linejoin="round"
                />

                {{-- Axis labels --}}
                <g fill="#888888" font-size="9" font-family="system-ui, sans-serif" font-weight="500">
                    <text x="120" y="16" text-anchor="middle">Reports</text>
                    <text x="210" y="68" text-anchor="start">Attention</text>
                    <text x="210" y="178" text-anchor="start">Health</text>
                    <text x="120" y="232" text-anchor="middle">Assets</text>
                    <text x="30" y="178" text-anchor="end">Schedules</text>
                    <text x="30" y="68" text-anchor="end">Issues</text>
                </g>
            </svg>
        </div>
    </div>
</section>
