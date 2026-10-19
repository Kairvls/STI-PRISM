@php
    $equipmentViewUrl = $equipmentViewUrl ?? fn ($id) => url('/maintenance/equipment/view/'.$id);
    $lifecycleReadOnly = $lifecycleReadOnly ?? false;
    $lp = $lifecycleProfile ?? null;
    $acqSource = $lp['acquisition_source'] ?? null;
    $isProcured = $acqSource === \App\Support\EquipmentAcquisition::PROCUREMENT
        || filled($lp['receiving_report_number'] ?? null)
        || filled($lp['purchase_order_number'] ?? null);
    $isManualSource = filled($acqSource) && ! $isProcured;
    $notRecorded = ! $isProcured && ! $isManualSource;
    $stockedMeta = array_filter([
        $lp['stocked_by_name'] ?? null,
        $lp['tracking_mode'] ?? null,
        ! empty($lp['stock_lot_code']) ? 'Lot '.$lp['stock_lot_code'] : null,
    ]);
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Procurement & lifecycle</h2>
        </div>
        <i data-lucide="git-branch" class="h-5 w-5 text-slate-400"></i>
    </div>
    @if ($notRecorded)
        <div class="mx-6 mt-5 flex items-start gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>
                This item was added without a Receiving Report or an acquisition source, so procurement details are blank.
                @if ($lifecycleReadOnly)
                    Maintenance can fill them in from the inventory record.
                @else
                    Fill them in from <span class="font-semibold">Inventory → Edit → More details → Acquisition</span>.
                @endif
            </p>
        </div>
    @endif
    <div class="grid grid-cols-1 gap-6 px-6 py-6 md:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Acquired via</p>
            <p class="mt-1 font-semibold text-slate-900">
                {{ $isProcured ? \App\Support\EquipmentAcquisition::sourceLabel(\App\Support\EquipmentAcquisition::PROCUREMENT) : $na($lp['acquisition_source_label'] ?? null) }}
            </p>
            @if (!empty($lp['acquisition_notes']))
                <p class="mt-0.5 text-xs text-slate-500">{{ $lp['acquisition_notes'] }}</p>
            @endif
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $acqSource === 'donation' ? 'Donor' : 'Supplier' }}</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $na($lp['supplier_name'] ?? null) }}</p>
        </div>
        @if ($isManualSource)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Reference no.</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $na($lp['reference_number'] ?? null) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Received</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $formatDate($lp['acquired_date'] ?? null) }}</p>
                <p class="mt-0.5 text-xs text-slate-500">No RR — not procured through PRISM</p>
            </div>
        @else
            @if (!empty($lp['purchase_order_number']))
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Purchase order</p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $lp['purchase_order_number'] }}
                    @if (!empty($lp['purchase_order_date']))
                        <span class="text-sm font-normal text-slate-500">· {{ $formatDate($lp['purchase_order_date']) }}</span>
                    @endif
                </p>
            </div>
            @endif
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">ATP / RIS</p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $lp['atp_number'] ?? '—' }}
                    <span class="text-slate-300">/</span>
                    {{ $lp['ris_number'] ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Received (RR)</p>
                <p class="mt-1 font-semibold text-slate-900">
                    {{ $na($lp['receiving_report_number'] ?? null) }}
                    @if (!empty($lp['receiving_report_date']))
                        <span class="text-sm font-normal text-slate-500">· {{ $formatDate($lp['receiving_report_date']) }}</span>
                    @endif
                </p>
                @if (!empty($lp['received_by']))
                    <p class="mt-0.5 text-xs text-slate-500">By {{ $lp['received_by'] }}</p>
                @endif
            </div>
        @endif
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Added to inventory</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $formatDate($lp['acquired_date'] ?? $equipment->equipment_acquired_date ?? null) }}</p>
            @if ($stockedMeta)
                <p class="mt-0.5 text-xs text-slate-500">{{ implode(' · ', $stockedMeta) }}</p>
            @endif
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Deployed</p>
            @if (!empty($lp['deployed_at']))
                <p class="mt-1 font-semibold text-slate-900">{{ $formatDate($lp['deployed_at']) }}</p>
                @if (!empty($lp['deployed_room']))
                    <p class="mt-0.5 text-xs text-slate-500">First placed in {{ $lp['deployed_room'] }}</p>
                @endif
            @else
                <p class="mt-1 font-semibold text-slate-900">Not yet deployed</p>
                <p class="mt-0.5 text-xs text-slate-500">Still in storage</p>
            @endif
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">QR issued</p>
            <p class="mt-1 font-semibold text-slate-900">{{ $formatDate($lp['qr_issued_at'] ?? null) }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ $na($lp['qr_code'] ?? $equipment->equipment_qr_code ?? null) }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Replacement link</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">
                @if (!empty($lp['replaces_id']))
                    Replaces <a class="text-[#0025cc] hover:underline" href="{{ $equipmentViewUrl($lp['replaces_id']) }}">#{{ $lp['replaces_id'] }}</a>
                @elseif (!empty($lp['replaced_by_id']))
                    Replaced by <a class="text-[#0025cc] hover:underline" href="{{ $equipmentViewUrl($lp['replaced_by_id']) }}">#{{ $lp['replaced_by_id'] }}</a>
                @else
                    —
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Repair cost (total)</p>
            <p class="mt-1 font-semibold text-slate-900">₱{{ number_format((float) ($repairCostTotal ?? 0), 2) }}</p>
        </div>
    </div>
    @if (($conditionHistory ?? collect())->isNotEmpty())
        <div class="border-t border-slate-100 px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Condition history</p>
            <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                @foreach ($conditionHistory as $ch)
                    <li>
                        <span class="font-medium">{{ $ch->condition_from ?: '—' }} → {{ $ch->condition_to }}</span>
                        <span class="text-slate-400">· {{ \Illuminate\Support\Carbon::parse($ch->created_at)->format('Y-m-d H:i') }}</span>
                        @if (!empty($ch->changed_by_name))
                            <span class="text-slate-400">· {{ $ch->changed_by_name }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Lifecycle timeline</h2>
        </div>
        <i data-lucide="history" class="h-5 w-5 text-slate-400"></i>
    </div>
    @php
        $events = $lifecycleEvents ?? collect();
        $counts = $lifecycleCounts ?? [];
        $typeColors = [
            'acquisition' => 'bg-indigo-500',
            'created' => 'bg-slate-400',
            'qr' => 'bg-sky-500',
            'transfer' => 'bg-violet-500',
            'borrow' => 'bg-cyan-500',
            'condition' => 'bg-amber-500',
            'maintenance' => 'bg-orange-500',
            'report' => 'bg-rose-500',
            'disposal' => 'bg-rose-700',
            'assignment' => 'bg-teal-500',
            'replacement' => 'bg-emerald-600',
        ];
    @endphp
    @if (!empty($reportSummary) && ($reportSummary['times_reported'] ?? 0) > 0)
        @php
            $stateTone = match ($reportSummary['state']) {
                'Needs replacement' => 'bg-orange-50 text-orange-700 ring-orange-200',
                'Under repair' => 'bg-sky-50 text-sky-700 ring-sky-200',
                'Malfunction reported' => 'bg-rose-50 text-rose-700 ring-rose-200',
                default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            };
        @endphp
        <div class="grid grid-cols-2 gap-3 border-b border-slate-100 px-6 py-4 sm:grid-cols-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Right now</p>
                <span class="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $stateTone }}">
                    {{ $reportSummary['state'] }}
                </span>
                @if (($reportSummary['open_count'] ?? 0) > 1)
                    <p class="mt-1 text-xs text-rose-600">{{ $reportSummary['open_count'] }} open tickets</p>
                @endif
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Times reported</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $reportSummary['times_reported'] }}</p>
                <p class="text-xs text-slate-500">Last: {{ $formatDate($reportSummary['last_reported_at']) }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Times fixed</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ $reportSummary['times_fixed'] }}</p>
                <p class="text-xs text-slate-500">Last: {{ $reportSummary['last_fixed_at'] ? $formatDate($reportSummary['last_fixed_at']) : '—' }}</p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Replacement</p>
                <p class="mt-1 text-sm font-semibold {{ $reportSummary['replacement'] ? 'text-orange-700' : 'text-slate-900' }}">
                    {{ $reportSummary['replacement'] ? 'Needed' : 'Not needed' }}
                </p>
            </div>
        </div>
    @endif
    @php
        $stageLabels = \App\Support\EquipmentTimeline::stages();
        $stageCounts = collect($events)->countBy(fn ($event) => $event['stage'] ?? 'repair');
    @endphp
    @if ($stageCounts->isNotEmpty())
        <div class="border-b border-slate-100 px-6 py-3" data-lifecycle-stage-filter>
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Filter by stage</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-stage="all" class="lifecycle-stage-chip rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition bg-[#0025cc] text-white ring-[#0025cc]">
                    All · {{ collect($events)->count() }}
                </button>
                @foreach ($stageLabels as $stageKey => $stageLabel)
                    @continue(! $stageCounts->has($stageKey))
                    <button type="button" data-stage="{{ $stageKey }}" class="lifecycle-stage-chip rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition bg-slate-50 text-slate-600 ring-slate-200/80 hover:bg-slate-100">
                        {{ $stageLabel }} · {{ $stageCounts[$stageKey] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
    @php
        $lifecycleLimit = 10;
        $olderCount = max(0, collect($events)->count() - $lifecycleLimit);
    @endphp
    <div class="px-6 py-5" data-lifecycle-events>
        @forelse ($events as $event)
            <div class="lifecycle-event relative flex gap-4 pb-5 last:pb-0 {{ $loop->index >= $lifecycleLimit ? 'hidden' : '' }}" data-stage="{{ $event['stage'] ?? 'repair' }}">
                <span class="lifecycle-line absolute left-[7px] top-4 bottom-0 w-px bg-slate-200 {{ $loop->last || $loop->iteration === $lifecycleLimit ? 'hidden' : '' }}"></span>
                <span class="relative z-10 mt-1.5 h-3.5 w-3.5 shrink-0 rounded-full ring-4 ring-white {{ $event['meta']['dot'] ?? ($typeColors[$event['type'] ?? ''] ?? 'bg-slate-400') }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ $event['title'] ?? 'Event' }}</p>
                        <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200/80">
                            {{ $event['stage_label'] ?? ($event['type_label'] ?? ($event['type'] ?? '')) }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ !empty($event['occurred_at']) ? $formatDate($event['occurred_at']) : '—' }}
                        @if (!empty($event['meta']['actor']))
                            · {{ $event['meta']['actor'] }}
                        @elseif (!empty($event['meta']['transferred_by']))
                            · {{ $event['meta']['transferred_by'] }}
                        @elseif (!empty($event['meta']['borrower']))
                            · {{ $event['meta']['borrower'] }}
                        @endif
                    </p>
                    @if (!empty($event['description']))
                        <p class="mt-1 text-sm text-slate-600">{{ $event['description'] }}</p>
                    @endif
                    @if (!empty($event['meta']['parts_used']) || isset($event['meta']['repair_cost']) || isset($event['meta']['downtime_hours']))
                        <p class="mt-1 text-xs text-slate-500">
                            @if (!empty($event['meta']['parts_used'])) Parts: {{ $event['meta']['parts_used'] }} @endif
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
            <p class="py-6 text-center text-sm text-slate-400">No lifecycle events recorded yet.</p>
        @endforelse
    </div>
    <div class="border-t border-slate-100 px-6 py-3 {{ $olderCount > 0 ? '' : 'hidden' }}" data-lifecycle-toggle>
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
        const LIFECYCLE_LIMIT = 10;

        function renderLifecycle(section) {
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
            toggle.querySelector("[data-lifecycle-toggle-label]").textContent = expanded
                ? "Show fewer"
                : "Show " + older + " older " + (older === 1 ? "event" : "events");
            toggle.querySelector("[data-lifecycle-toggle-icon]")?.classList.toggle("rotate-180", expanded);
        }

        document.addEventListener("click", function (event) {
            const toggleButton = event.target.closest("[data-lifecycle-toggle] button");
            if (toggleButton) {
                const section = toggleButton.closest("section");
                const collapsing = section.dataset.lifecycleExpanded === "1";
                section.dataset.lifecycleExpanded = collapsing ? "0" : "1";
                renderLifecycle(section);
                if (collapsing) {
                    section.querySelector("[data-lifecycle-toggle]").scrollIntoView({ block: "nearest", behavior: "smooth" });
                }
                return;
            }

            const chip = event.target.closest(".lifecycle-stage-chip");
            if (!chip) return;

            const section = chip.closest("section");
            const active = ["bg-[#0025cc]", "text-white", "ring-[#0025cc]"];
            const idle = ["bg-slate-50", "text-slate-600", "ring-slate-200/80", "hover:bg-slate-100"];

            section.querySelectorAll(".lifecycle-stage-chip").forEach(function (button) {
                const on = button === chip;
                button.classList.remove(...(on ? idle : active));
                button.classList.add(...(on ? active : idle));
            });

            section.dataset.lifecycleStage = chip.dataset.stage;
            section.dataset.lifecycleExpanded = "0";
            renderLifecycle(section);
        });
    })();
</script>
@endonce
