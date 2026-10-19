@php
    $events = collect($lifecycleEvents ?? []);
    $summary = $lifecycleSummary ?? [];
    $stageCounts = collect($lifecycleStageCounts ?? []);
    $stageLabels = \App\Support\RoomTimeline::stages();
    $formatDate = function ($value) {
        if (! $value) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('M j, Y g:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $status = (string) ($summary['status'] ?? 'Normal');
    $statusTone = match ($status) {
        'Critical' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'Under Maintenance' => 'bg-amber-50 text-amber-700 ring-amber-200',
        default => 'bg-blue-50 text-blue-700 ring-blue-200',
    };
@endphp

<section
    class="room-lifecycle"
    data-lifecycle-stage="all"
    data-lifecycle-expanded="0"
>
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Lifecycle timeline</h3>
            <p class="mt-0.5 text-xs text-slate-400">
                Setup, equipment moves, inspections, maintenance, reports, and borrowing for this room
            </p>
        </div>
        <i data-lucide="history" class="h-5 w-5 text-slate-400"></i>
    </div>

    <div class="grid grid-cols-2 gap-3 border-b border-slate-100 px-5 py-4 sm:grid-cols-4 sm:px-6">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Right now</p>
            <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusTone }}">
                {{ $status }}
            </span>
            @if (($summary['open_reports'] ?? 0) > 0)
                <p class="mt-1 text-xs text-rose-600">{{ $summary['open_reports'] }} open {{ \Illuminate\Support\Str::plural('report', $summary['open_reports']) }}</p>
            @endif
        </div>
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Times reported</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($summary['times_reported'] ?? 0) }}</p>
            <p class="text-xs text-slate-500">Last: {{ $formatDate($summary['last_reported_at'] ?? null) }}</p>
        </div>
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Equipment here</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($summary['equipment_count'] ?? 0) }}</p>
            <p class="text-xs {{ ($summary['for_replacement'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-500' }}">
                {{ ($summary['for_replacement'] ?? 0) > 0
                    ? number_format($summary['for_replacement']).' for replacement'
                    : 'None marked for replacement' }}
            </p>
        </div>
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Last inspection</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">
                {{ ($summary['last_inspection_at'] ?? null) ? $formatDate($summary['last_inspection_at']) : 'None yet' }}
            </p>
        </div>
    </div>

    @if ($stageCounts->isNotEmpty())
        <div class="border-b border-slate-100 px-5 py-3 sm:px-6" data-lifecycle-stage-filter>
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Filter by stage</p>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    data-stage="all"
                    class="lifecycle-stage-chip rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition bg-[#0025cc] text-white ring-[#0025cc]"
                >
                    All · {{ $events->count() }}
                </button>
                @foreach ($stageLabels as $stageKey => $stageLabel)
                    @continue(! $stageCounts->has($stageKey) || (int) $stageCounts[$stageKey] === 0)
                    <button
                        type="button"
                        data-stage="{{ $stageKey }}"
                        class="lifecycle-stage-chip rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition bg-slate-50 text-slate-600 ring-slate-200/80 hover:bg-slate-100"
                    >
                        {{ $stageLabel }} · {{ $stageCounts[$stageKey] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @php
        $lifecycleLimit = 12;
        $olderCount = max(0, $events->count() - $lifecycleLimit);
    @endphp

    <div class="px-5 py-5 sm:px-6" data-lifecycle-events>
        @forelse ($events as $event)
            <div
                class="lifecycle-event relative flex gap-4 pb-5 last:pb-0 {{ $loop->index >= $lifecycleLimit ? 'hidden' : '' }}"
                data-stage="{{ $event['stage'] ?? 'setup' }}"
            >
                <span class="lifecycle-line absolute left-[7px] top-4 bottom-0 w-px bg-slate-200 {{ $loop->last || $loop->iteration === $lifecycleLimit ? 'hidden' : '' }}"></span>
                <span class="relative z-10 mt-1.5 h-3.5 w-3.5 shrink-0 rounded-full ring-4 ring-white {{ $event['meta']['dot'] ?? 'bg-slate-400' }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ $event['title'] ?? 'Event' }}</p>
                        <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200/80">
                            {{ $event['stage_label'] ?? ($event['type_label'] ?? ($event['type'] ?? '')) }}
                        </span>
                        @if (! empty($event['meta']['status']))
                            <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200/80">
                                {{ $event['meta']['status'] }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ ! empty($event['occurred_at']) ? $formatDate($event['occurred_at']) : '—' }}
                        @if (! empty($event['meta']['actor']))
                            · {{ $event['meta']['actor'] }}
                        @elseif (! empty($event['meta']['borrower']))
                            · {{ $event['meta']['borrower'] }}
                        @elseif (! empty($event['meta']['transferred_by']))
                            · {{ $event['meta']['transferred_by'] }}
                        @endif
                        @if (! empty($event['meta']['equipment']))
                            · {{ $event['meta']['equipment'] }}
                        @endif
                    </p>
                    @if (! empty($event['description']))
                        <p class="mt-1 text-sm text-slate-600">{{ $event['description'] }}</p>
                    @endif
                    @if (! empty($event['meta']['parts_used']) || isset($event['meta']['repair_cost']) || isset($event['meta']['downtime_hours']))
                        <p class="mt-1 text-xs text-slate-500">
                            @if (! empty($event['meta']['parts_used'])) Parts: {{ $event['meta']['parts_used'] }} @endif
                            @if (isset($event['meta']['repair_cost']) && $event['meta']['repair_cost'] !== null)
                                · Cost: ₱{{ number_format((float) $event['meta']['repair_cost'], 2) }}
                            @endif
                            @if (isset($event['meta']['downtime_hours']) && $event['meta']['downtime_hours'] !== null)
                                · Downtime: {{ $event['meta']['downtime_hours'] }}h
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-slate-400">No lifecycle events recorded for this filter yet.</p>
        @endforelse
    </div>

    <div class="border-t border-slate-100 px-5 py-3 sm:px-6 {{ $olderCount > 0 ? '' : 'hidden' }}" data-lifecycle-toggle>
        <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#0025cc] hover:underline">
            <span data-lifecycle-toggle-icon class="inline-flex transition-transform">
                <i data-lucide="chevron-down" class="h-4 w-4"></i>
            </span>
            <span data-lifecycle-toggle-label>Show {{ $olderCount }} older {{ \Illuminate\Support\Str::plural('event', $olderCount) }}</span>
        </button>
    </div>
</section>

@once
<script>
    (function () {
        const LIFECYCLE_LIMIT = 12;

        function renderRoomLifecycle(section) {
            const stage = section.dataset.lifecycleStage || "all";
            const expanded = section.dataset.lifecycleExpanded === "1";
            const rows = Array.from(section.querySelectorAll(".lifecycle-event"));
            const matching = rows.filter((row) => stage === "all" || row.dataset.stage === stage);
            const shown = expanded ? matching : matching.slice(0, LIFECYCLE_LIMIT);

            rows.forEach((row) => row.classList.add("hidden"));
            shown.forEach(function (row, index) {
                row.classList.remove("hidden");
                row.querySelector(".lifecycle-line")?.classList.toggle("hidden", index === shown.length - 1);
            });

            const toggle = section.querySelector("[data-lifecycle-toggle]");
            if (!toggle) return;
            const older = matching.length - LIFECYCLE_LIMIT;
            toggle.classList.toggle("hidden", older <= 0);
            const label = toggle.querySelector("[data-lifecycle-toggle-label]");
            if (label) {
                label.textContent = expanded
                    ? "Show fewer"
                    : "Show " + older + " older " + (older === 1 ? "event" : "events");
            }
            toggle.querySelector("[data-lifecycle-toggle-icon]")?.classList.toggle("rotate-180", expanded);

            section.querySelectorAll(".lifecycle-stage-chip").forEach(function (chip) {
                const active = chip.dataset.stage === stage;
                chip.classList.toggle("bg-[#0025cc]", active);
                chip.classList.toggle("text-white", active);
                chip.classList.toggle("ring-[#0025cc]", active);
                chip.classList.toggle("bg-slate-50", !active);
                chip.classList.toggle("text-slate-600", !active);
                chip.classList.toggle("ring-slate-200/80", !active);
            });
        }

        document.addEventListener("click", function (event) {
            const section = event.target.closest(".room-lifecycle");
            if (!section) return;

            const chip = event.target.closest(".lifecycle-stage-chip");
            if (chip) {
                section.dataset.lifecycleStage = chip.dataset.stage || "all";
                section.dataset.lifecycleExpanded = "0";
                renderRoomLifecycle(section);
                return;
            }

            const toggleButton = event.target.closest("[data-lifecycle-toggle] button");
            if (toggleButton) {
                const collapsing = section.dataset.lifecycleExpanded === "1";
                section.dataset.lifecycleExpanded = collapsing ? "0" : "1";
                renderRoomLifecycle(section);
                if (collapsing) {
                    section.querySelector("[data-lifecycle-toggle]")?.scrollIntoView({ block: "nearest", behavior: "smooth" });
                }
            }
        });

        document.querySelectorAll(".room-lifecycle").forEach(renderRoomLifecycle);
    })();
</script>
@endonce
