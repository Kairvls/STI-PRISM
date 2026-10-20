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
        $ghostCount = (int) (($monitoring['ghost_count'] ?? null) ?? count($monitoring['ghost'] ?? []));
        $warrantyCount = (int) (($monitoring['warranty_count'] ?? null) ?? count($monitoring['warranty'] ?? []));
        $hasMonitoring = ! empty($monitoring) && ($ghostCount > 0 || $warrantyCount > 0 || ! empty($monitoring['ghost']) || ! empty($monitoring['warranty']));
        $hasOpenBalance = ! empty($obReports);
        $assetIdentity = function ($row) {
            $tag = trim((string) ($row->equipment_asset_tag ?? ''));
            if ($tag !== '') {
                return $tag;
            }
            $serial = trim((string) ($row->equipment_serial_number ?? ''));
            if ($serial !== '') {
                return 'S/N '.$serial;
            }

            return '';
        };
    @endphp
    @if ($obFocusRrId > 0)
        @include('partials.attention-focus-chip', [
            'focus' => \App\Support\RecordFocus::receivingReport($obFocusRrId, url()->current()),
            'total' => count($obReports),
        ])
    @endif
    @if ($hasOpenBalance || $hasMonitoring)
        <div
            id="inventoryOpenBalanceModal"
            class="fixed inset-0 z-[12000] hidden bg-[#0b1220]/70"
            :class="fullscreen ? 'items-stretch p-0' : 'items-center justify-center p-4'"
            onclick="if (event.target === this) closeInventoryOpenBalanceModal()"
            x-data="{
                fullscreen: false,
                openRr: @js((object) $obOpen),
                openLine: {},
                toggleRr(id) { this.openRr = { ...this.openRr, [id]: !this.openRr[id] }; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                toggleLine(id) { this.openLine = { ...this.openLine, [id]: !this.openLine[id] }; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                toggleFullscreen() { this.fullscreen = !this.fullscreen; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
            }"
        >
            <div
                class="ob-open-balance-panel flex w-full flex-col overflow-hidden bg-white shadow-2xl shadow-slate-950/15"
                :class="fullscreen ? 'ob-open-balance-panel--fullscreen h-full max-h-none max-w-none rounded-none' : 'max-h-[90vh] max-w-3xl rounded-2xl'"
                onclick="event.stopPropagation()"
            >
                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">
                            {{ $hasOpenBalance ? 'Open balance' : 'Monitoring' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $hasOpenBalance ? 'RR deliveries vs what is already in inventory' : 'Not RR related assets and warranty alerts' }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button
                            type="button"
                            @click="toggleFullscreen()"
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-800"
                            :aria-label="fullscreen ? 'Exit full screen' : 'Full screen'"
                            :title="fullscreen ? 'Exit full screen' : 'Full screen'"
                        >
                            <i x-show="!fullscreen" data-lucide="maximize-2" class="h-4 w-4"></i>
                            <i x-show="fullscreen" x-cloak data-lucide="minimize-2" class="h-4 w-4"></i>
                        </button>
                        <button
                            type="button"
                            onclick="closeInventoryOpenBalanceModal()"
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-800"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <div class="monitoring-scroll min-h-0 flex-1 overflow-y-auto">
                @if ($hasOpenBalance)
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
                                            @if (!empty($report['has_replacement']))
                                                @php $rrPendingReplacement = (int) ($report['pending_replacement'] ?? 0); @endphp
                                                <span
                                                    class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-800"
                                                    @if (!empty($report['replaces_label'])) title="Replaces {{ $report['replaces_label'] }}" @endif
                                                >
                                                    {{ $rrPendingReplacement > 0 ? $rrPendingReplacement.' replacement' : 'Replacement' }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="mt-0.5 block truncate text-[11px] text-slate-400">
                                            {{ $report['supplier_name'] ?: 'No supplier' }}
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
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="truncate font-medium text-slate-900">{{ $line['article'] }}</p>
                                                    @if (!empty($line['is_replacement']))
                                                        <span
                                                            class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-800"
                                                            @if (!empty($line['replaces_label'])) title="Replaces {{ $line['replaces_label'] }}" @endif
                                                        >Replacement</span>
                                                    @endif
                                                </div>
                                                @if (!empty($line['is_replacement']) && !empty($line['replaces_label']))
                                                    <p class="mt-0.5 truncate text-[11px] text-violet-700">Replaces {{ $line['replaces_label'] }}</p>
                                                @endif
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
                @endif

                @if ($hasMonitoring)
                    <div class="{{ $hasOpenBalance ? 'border-t border-slate-200' : '' }} px-5 py-4">
                        <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Monitoring</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                            <span>
                                <span class="text-slate-400">Not RR related</span>
                                <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ number_format($ghostCount) }}</span>
                            </span>
                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                            <span>
                                <span class="text-slate-400">Warranty</span>
                                <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ number_format($warrantyCount) }}</span>
                            </span>
                        </p>
                    </div>
                    <div class="grid gap-0 border-t border-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-slate-100">
                        <div class="px-5 py-4">
                            <div class="mb-3 flex items-baseline justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Not RR related</p>
                                    <p class="mt-0.5 text-xs text-slate-400">No receiving report link</p>
                                </div>
                                <p class="text-xl font-semibold tabular-nums tracking-tight text-slate-900">{{ number_format($ghostCount) }}</p>
                            </div>
                            <ul class="max-h-48 divide-y divide-slate-100 overflow-y-auto pr-1">
                                @forelse (($monitoring['ghost'] ?? []) as $g)
                                    <li>
                                        @php $ghostIdentity = $assetIdentity($g); @endphp
                                        <a href="{{ $g->view_url }}" class="block py-2.5 transition hover:bg-slate-50/80">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-900">{{ $g->equipment_name }}</p>
                                                @if ($ghostIdentity !== '' || ! empty($g->room_name))
                                                    <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400">
                                                        @if ($ghostIdentity !== '')
                                                            {{ $ghostIdentity }}
                                                        @endif
                                                        @if ($ghostIdentity !== '' && ! empty($g->room_name))
                                                            <span class="font-sans text-slate-300"> · </span>
                                                        @endif
                                                        @if (! empty($g->room_name))
                                                            <span class="font-sans">{{ $g->room_name }}</span>
                                                        @endif
                                                    </p>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @empty
                                    <li class="py-6 text-center text-sm text-slate-400">No assets without an RR link</li>
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
                            <ul class="max-h-48 divide-y divide-slate-100 overflow-y-auto pr-1">
                                @forelse (($monitoring['warranty'] ?? []) as $w)
                                    @php
                                        $expired = (bool) ($w->is_expired ?? ((int) ($w->days_remaining ?? 0) < 0));
                                        $expiryLabel = ! empty($w->equipment_warranty_expiration)
                                            ? \Illuminate\Support\Carbon::parse($w->equipment_warranty_expiration)->format('M j, Y')
                                            : null;
                                    @endphp
                                    @php $warrantyIdentity = $assetIdentity($w); @endphp
                                    <li>
                                        <a href="{{ $w->view_url }}" class="flex items-start justify-between gap-3 py-2.5 transition hover:bg-slate-50/80">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-slate-900">{{ $w->equipment_name }}</p>
                                                @if ($warrantyIdentity !== '' || $expiryLabel)
                                                    <p class="mt-0.5 truncate font-mono text-[11px] text-slate-400">
                                                        @if ($warrantyIdentity !== '')
                                                            {{ $warrantyIdentity }}
                                                        @endif
                                                        @if ($warrantyIdentity !== '' && $expiryLabel)
                                                            <span class="font-sans text-slate-300"> · </span>
                                                        @endif
                                                        @if ($expiryLabel)
                                                            <span class="font-sans">{{ $expiryLabel }}</span>
                                                        @endif
                                                    </p>
                                                @endif
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
                @endif
                </div>
            </div>
        </div>
        <style>
            #inventoryOpenBalanceModal .ob-open-balance-panel--fullscreen {
                zoom: 1.22;
            }
            @supports not (zoom: 1) {
                #inventoryOpenBalanceModal .ob-open-balance-panel--fullscreen {
                    font-size: 122%;
                }
                #inventoryOpenBalanceModal .ob-open-balance-panel--fullscreen .text-\[11px\] {
                    font-size: 0.85rem;
                }
                #inventoryOpenBalanceModal .ob-open-balance-panel--fullscreen .text-\[10px\] {
                    font-size: 0.8rem;
                }
                #inventoryOpenBalanceModal .ob-open-balance-panel--fullscreen .text-\[13px\] {
                    font-size: 1rem;
                }
            }
        </style>
        <script>
            window.openInventoryOpenBalanceModal = function () {
                var modal = document.getElementById('inventoryOpenBalanceModal');
                if (!modal) return;
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                try {
                    if (window.Alpine && typeof Alpine.$data === 'function') {
                        Alpine.$data(modal).fullscreen = false;
                    }
                } catch (e) {}
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            };
            window.closeInventoryOpenBalanceModal = function () {
                var modal = document.getElementById('inventoryOpenBalanceModal');
                if (!modal) return;
                try {
                    if (window.Alpine && typeof Alpine.$data === 'function') {
                        Alpine.$data(modal).fullscreen = false;
                    }
                } catch (e) {}
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };
            @if ($obFocusRrId > 0)
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.openInventoryOpenBalanceModal === 'function') {
                    window.openInventoryOpenBalanceModal();
                }
            });
            @endif
        </script>
    @endif
