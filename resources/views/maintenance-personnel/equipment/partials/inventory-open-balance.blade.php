    @php
        $obTotals = $openBalance['totals'] ?? [];
        $obReports = $openBalance['reports'] ?? [];
        $obFocusRrId = (int) request('rr');
        if ($obFocusRrId > 0) {
            $obReports = array_values(array_filter($obReports, fn ($r) => (int) $r['receiving_report_id'] === $obFocusRrId));
        }
        $obOpen = collect($obReports)
            ->mapWithKeys(fn ($r) => [(string) $r['receiving_report_id'] => $obFocusRrId > 0 || (int) ($r['totals']['pending_stock'] ?? 0) > 0])
            ->all();
    @endphp
    @if ($obFocusRrId > 0)
        @include('partials.attention-focus-chip', [
            'focus' => \App\Support\RecordFocus::receivingReport($obFocusRrId, url()->current()),
            'total' => count($obReports),
        ])
    @endif
    @if (!empty($obReports))
        <div class="rounded-2xl border border-slate-200/80 bg-white">
            <div class="flex flex-wrap items-end justify-between gap-3 px-5 py-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Open balance</p>
                    <p class="mt-1 text-sm text-slate-500">RR deliveries vs what is already in inventory</p>
                </div>
            </div>

            <div class="grid grid-cols-2 border-t border-slate-100 sm:grid-cols-4 sm:divide-x sm:divide-slate-100">
                @foreach ([
                    'ordered' => 'Ordered',
                    'received' => 'Received',
                    'stocked' => 'In inventory',
                    'pending_stock' => 'Pending',
                ] as $key => $label)
                    <div class="px-5 py-4 {{ $loop->index >= 2 ? 'border-t border-slate-100 sm:border-t-0' : '' }}">
                        <p class="text-xs text-slate-400">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900">
                            {{ number_format((int) ($obTotals[$key] ?? 0)) }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div
                class="monitoring-scroll max-h-[28rem] overflow-y-auto border-t border-slate-100"
                x-data="{
                    openRr: @js((object) $obOpen),
                    openLine: {},
                    toggleRr(id) { this.openRr = { ...this.openRr, [id]: !this.openRr[id] }; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                    toggleLine(id) { this.openLine = { ...this.openLine, [id]: !this.openLine[id] }; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                }"
            >
                <div class="sticky top-0 z-10 grid grid-cols-[minmax(0,1fr)_3.5rem_3.5rem_4.5rem] items-center gap-2 border-b border-slate-100 bg-white px-5 py-2.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    <span>Receiving report</span>
                    <span class="text-right">Recv</span>
                    <span class="text-right" title="In inventory">Inv.</span>
                    <span class="text-right">Pending</span>
                </div>

                <div class="divide-y divide-slate-100 text-sm text-slate-700">
                    @foreach ($obReports as $report)
                        @php
                            $rrKey = (string) $report['receiving_report_id'];
                            $rrTotals = $report['totals'];
                            $rrPending = (int) ($rrTotals['pending_stock'] ?? 0);
                            $rrLineCount = count($report['lines']);
                            $rrPaperwork = implode(' · ', array_filter([
                                $report['atp_number'] ?? null,
                                !empty($report['dr_no']) ? 'DR '.$report['dr_no'] : null,
                                !empty($report['invoice_no']) ? 'Invoice '.$report['invoice_no'] : null,
                            ]));
                            $rrDate = null;
                            if (!empty($report['date'])) {
                                try {
                                    $rrDate = \Illuminate\Support\Carbon::parse($report['date'])->format('M j, Y');
                                } catch (\Throwable $e) {
                                    $rrDate = null;
                                }
                            }
                        @endphp
                        <div>
                            <button
                                type="button"
                                class="grid w-full grid-cols-[minmax(0,1fr)_3.5rem_3.5rem_4.5rem] items-center gap-2 px-5 py-3 text-left transition hover:bg-slate-50/80"
                                :aria-expanded="openRr['{{ $rrKey }}'] ? 'true' : 'false'"
                                @click="toggleRr('{{ $rrKey }}')"
                            >
                                <span class="flex min-w-0 items-start gap-2.5">
                                    <span class="mt-0.5 inline-flex h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="openRr['{{ $rrKey }}'] ? 'rotate-90' : ''">
                                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-[13px] font-semibold text-slate-900">{{ $report['rr_number'] }}</span>
                                            @if ($rrPending > 0)
                                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ $rrPending }} pending</span>
                                            @else
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">In inventory</span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block truncate text-[11px] text-slate-400">
                                            {{ $report['supplier_name'] ?: 'No supplier' }}
                                            <span class="text-slate-300">·</span>
                                            {{ !empty($report['po_number']) ? 'PO '.$report['po_number'] : 'No PO' }}
                                            @if ($rrDate)
                                                <span class="text-slate-300">·</span> {{ $rrDate }}
                                            @endif
                                            <span class="text-slate-300">·</span>
                                            {{ $rrLineCount }} {{ \Illuminate\Support\Str::plural('line', $rrLineCount) }}
                                            @if ((float) ($report['amount'] ?? 0) > 0)
                                                <span class="text-slate-300">·</span> ₱{{ number_format((float) $report['amount'], 2) }}
                                            @endif
                                        </span>
                                        @if ($rrPaperwork !== '')
                                            <span class="mt-0.5 block truncate text-[11px] text-slate-400">{{ $rrPaperwork }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="text-right tabular-nums">{{ (int) $rrTotals['received'] }}</span>
                                <span class="text-right tabular-nums">{{ (int) $rrTotals['stocked'] }}</span>
                                <span class="text-right font-semibold tabular-nums {{ $rrPending > 0 ? 'text-amber-700' : 'text-slate-400' }}">{{ $rrPending }}</span>
                            </button>

                            <div x-show="openRr['{{ $rrKey }}']" x-cloak class="border-t border-slate-100 bg-slate-50/60">
                                @foreach ($report['lines'] as $line)
                                    @php
                                        $lineId = (int) $line['receiving_report_item_id'];
                                        $pending = (int) $line['pending_stock'];
                                        $units = $line['units'] ?? [];
                                    @endphp
                                    <div class="border-b border-slate-100 last:border-b-0">
                                        <div class="grid grid-cols-[minmax(0,1fr)_3.5rem_3.5rem_4.5rem] items-center gap-2 py-2.5 pl-12 pr-5">
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-slate-900">{{ $line['article'] }}</p>
                                                @php
                                                    $lineCost = implode(' · ', array_filter([
                                                        !empty($line['unit']) ? (int) $line['received'].' '.$line['unit'] : null,
                                                        $line['unit_cost'] !== null ? '₱'.number_format((float) $line['unit_cost'], 2).' each' : null,
                                                        $line['amount'] !== null && (int) $line['received'] > 1 ? '₱'.number_format((float) $line['amount'], 2).' total' : null,
                                                    ]));
                                                @endphp
                                                @if ($lineCost !== '')
                                                    <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $lineCost }}</p>
                                                @endif
                                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400">
                                                    <span class="font-mono">L{{ $lineId }}</span>
                                                    <span>{{ (int) $line['in_storage'] }} in storage · {{ (int) $line['deployed'] }} deployed{{ (int) $line['disposed'] > 0 ? ' · '.(int) $line['disposed'].' disposed' : '' }}</span>
                                                    @if (!empty($units))
                                                        <button type="button" class="inline-flex items-center gap-1 font-semibold text-[#0025cc] hover:underline" @click="toggleLine('{{ $lineId }}')">
                                                            <span x-text="openLine['{{ $lineId }}'] ? 'Hide equipment' : 'View {{ count($units) }} {{ \Illuminate\Support\Str::plural('record', count($units)) }}'"></span>
                                                        </button>
                                                    @endif
                                                    @if ($pending > 0)
                                                        <button type="button" class="inline-flex items-center gap-1 font-semibold text-amber-700 hover:underline" onclick="openAddEquipmentModal({{ $lineId }})">
                                                            <i data-lucide="package-plus" class="h-3 w-3"></i>
                                                            Add {{ $pending }} to inventory
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="text-right tabular-nums">{{ (int) $line['received'] }}</span>
                                            <span class="text-right tabular-nums">{{ (int) $line['stocked'] }}</span>
                                            <span class="text-right font-semibold tabular-nums {{ $pending > 0 ? 'text-amber-700' : 'text-slate-400' }}">{{ $pending }}</span>
                                        </div>

                                        @if (!empty($units))
                                            <ul x-show="openLine['{{ $lineId }}']" x-cloak class="mb-2.5 ml-12 mr-5 divide-y divide-slate-100 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                                                @foreach ($units as $unit)
                                                    @php
                                                        $placementClass = match ($unit['placement']) {
                                                            'deployed' => 'bg-[#0025cc]/5 text-[#0025cc]',
                                                            'disposed' => 'bg-rose-50 text-rose-700',
                                                            'unplaced' => 'bg-amber-50 text-amber-700',
                                                            default => 'bg-slate-100 text-slate-600',
                                                        };
                                                    @endphp
                                                    <li>
                                                        <a href="{{ \App\Support\EquipmentViewReturn::viewUrl((int) $unit['equipment_id']) }}" class="flex items-center justify-between gap-3 px-3.5 py-2.5 transition hover:bg-slate-50">
                                                            <span class="min-w-0">
                                                                <span class="block truncate text-[13px] font-medium text-slate-900">
                                                                    {{ $unit['name'] }}
                                                                    @if ((int) $unit['quantity'] > 1)
                                                                        <span class="font-normal text-slate-400">× {{ (int) $unit['quantity'] }}</span>
                                                                    @endif
                                                                </span>
                                                                <span class="mt-0.5 block truncate text-[11px] text-slate-400">
                                                                    <span class="font-mono">{{ $unit['asset_tag'] ?: 'No asset tag' }}</span>
                                                                    @if (!empty($unit['serial']))
                                                                        <span class="text-slate-300">·</span> SN {{ $unit['serial'] }}
                                                                    @endif
                                                                    @if (!empty($unit['condition']))
                                                                        <span class="text-slate-300">·</span> {{ $unit['condition'] }}
                                                                    @endif
                                                                </span>
                                                            </span>
                                                            <span class="flex shrink-0 items-center gap-2">
                                                                <span class="hidden max-w-[10rem] truncate text-[11px] text-slate-500 sm:inline">{{ $unit['room_name'] ?: 'No room' }}</span>
                                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize {{ $placementClass }}">{{ $unit['placement'] }}</span>
                                                                <i data-lucide="chevron-right" class="h-3.5 w-3.5 text-slate-300"></i>
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    @if (!empty($monitoring))
        @php
            $ghostCount = (int) ($monitoring['ghost_count'] ?? count($monitoring['ghost'] ?? []));
            $warrantyCount = (int) ($monitoring['warranty_count'] ?? count($monitoring['warranty'] ?? []));
            $assetIdentity = function ($row) {
                $tag = trim((string) ($row->equipment_asset_tag ?? ''));
                if ($tag !== '') {
                    return $tag;
                }
                $serial = trim((string) ($row->equipment_serial_number ?? ''));
                if ($serial !== '') {
                    return 'S/N '.$serial;
                }

                return '#'.(int) ($row->equipment_id ?? 0);
            };
        @endphp
        <details class="group rounded-2xl border border-slate-200/80 bg-white">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 [&::-webkit-details-marker]:hidden">
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Monitoring</p>
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                        <span>
                            <span class="text-slate-400">Unlinked</span>
                            <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ number_format($ghostCount) }}</span>
                        </span>
                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                        <span>
                            <span class="text-slate-400">Warranty</span>
                            <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ number_format($warrantyCount) }}</span>
                        </span>
                    </p>
                </div>
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition group-open:rotate-180 group-open:bg-slate-50">
                    <i data-lucide="chevron-down" class="h-4 w-4"></i>
                </span>
            </summary>

            <div class="grid gap-0 border-t border-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-slate-100">
                <div class="px-5 py-4">
                    <div class="mb-3 flex items-baseline justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Unlinked assets</p>
                            <p class="mt-0.5 text-xs text-slate-400">No RR / PO link</p>
                        </div>
                        <p class="text-xl font-semibold tabular-nums tracking-tight text-slate-900">{{ number_format($ghostCount) }}</p>
                    </div>
                    <ul class="monitoring-scroll max-h-40 divide-y divide-slate-100 overflow-y-auto pr-3">
                        @forelse (($monitoring['ghost'] ?? []) as $g)
                            <li>
                                <a href="{{ $g->view_url }}" class="flex items-start justify-between gap-3 py-2.5 pr-1 transition hover:bg-slate-50/80">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ $g->equipment_name }}</p>
                                        <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400">
                                            {{ $assetIdentity($g) }}
                                            @if (!empty($g->room_name))
                                                <span class="font-sans text-slate-300"> · </span>{{ $g->room_name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="mt-0.5 shrink-0 text-[10px] font-medium uppercase tracking-wide text-slate-400">#{{ (int) $g->equipment_id }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-400">No unlinked assets</li>
                        @endforelse
                    </ul>
                </div>

                <div class="border-t border-slate-100 px-5 py-4 sm:border-t-0">
                    <div class="mb-3 flex items-baseline justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Warranty</p>
                            <p class="mt-0.5 text-xs text-slate-400">Within 90 days or expired</p>
                        </div>
                        <p class="text-xl font-semibold tabular-nums tracking-tight text-slate-900">{{ number_format($warrantyCount) }}</p>
                    </div>
                    <ul class="monitoring-scroll max-h-40 divide-y divide-slate-100 overflow-y-auto pr-3">
                        @forelse (($monitoring['warranty'] ?? []) as $w)
                            @php
                                $expired = (bool) ($w->is_expired ?? ((int) ($w->days_remaining ?? 0) < 0));
                                $expiryLabel = ! empty($w->equipment_warranty_expiration)
                                    ? \Illuminate\Support\Carbon::parse($w->equipment_warranty_expiration)->format('M j, Y')
                                    : null;
                            @endphp
                            <li>
                                <a href="{{ $w->view_url }}" class="flex items-start justify-between gap-3 py-2.5 pr-1 transition hover:bg-slate-50/80">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-900">{{ $w->equipment_name }}</p>
                                        <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400">
                                            {{ $assetIdentity($w) }}
                                            @if ($expiryLabel)
                                                <span class="font-sans text-slate-300"> · </span>
                                                <span class="font-sans">{{ $expiryLabel }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <span class="mt-0.5 shrink-0 text-[11px] font-medium {{ $expired ? 'text-rose-600' : 'text-amber-700' }}">
                                        {{ $w->suggest_action }}
                                    </span>
                                </a>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-400">No warranty alerts</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </details>
    @endif
