    @php
        $obTotals = $openBalance['totals'] ?? [];
        $obReports = $openBalance['reports'] ?? [];
        $obFocusRrId = (int) request('rr');
        if ($obFocusRrId > 0) {
            $obReports = array_values(array_filter($obReports, fn ($r) => (int) $r['receiving_report_id'] === $obFocusRrId));
        }
        $obReportsSorted = collect($obReports)->sortByDesc(fn ($r) => (int) ($r['totals']['pending_stock'] ?? 0))->values();
        $obPendingReports = $obReportsSorted->filter(fn ($r) => (int) ($r['totals']['pending_stock'] ?? 0) > 0)->values();
        $obCompletedReports = $obReportsSorted->filter(fn ($r) => (int) ($r['totals']['pending_stock'] ?? 0) <= 0)->values();
        $obOpen = $obReportsSorted
            ->mapWithKeys(fn ($r) => [(string) $r['receiving_report_id'] => $obFocusRrId > 0 || (int) ($r['totals']['pending_stock'] ?? 0) > 0])
            ->all();
        $hasOpenBalance = ! empty($obReports);
    @endphp
    @if ($obFocusRrId > 0)
        @include('partials.attention-focus-chip', [
            'focus' => \App\Support\RecordFocus::receivingReport($obFocusRrId, url()->current()),
            'total' => count($obReports),
        ])
    @endif
    @if ($hasOpenBalance)
        <div
            id="inventoryOpenBalanceModal"
            class="fixed inset-0 z-[12000] hidden bg-[#0b1220]/70"
            :class="fullscreen ? 'items-stretch p-0' : 'items-center justify-center p-4'"
            onclick="if (event.target === this) closeInventoryOpenBalanceModal()"
            x-data="{
                fullscreen: false,
                showCompletedRr: {{ $obPendingReports->isEmpty() && $obCompletedReports->isNotEmpty() ? 'true' : 'false' }},
                openRr: @js((object) $obOpen),
                openLine: {},
                toggleRr(id) { this.openRr = { ...this.openRr, [id]: !this.openRr[id] }; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                toggleLine(id) { this.openLine = { ...this.openLine, [id]: !this.openLine[id] }; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                toggleFullscreen() { this.fullscreen = !this.fullscreen; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                toggleCompletedRr() { this.showCompletedRr = !this.showCompletedRr; this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
            }"
        >
            <div
                class="ob-open-balance-panel flex w-full flex-col overflow-hidden bg-white shadow-2xl shadow-slate-950/15"
                :class="fullscreen ? 'ob-open-balance-panel--fullscreen h-full max-h-none max-w-none rounded-none' : 'max-h-[90vh] max-w-3xl rounded-2xl'"
                onclick="event.stopPropagation()"
            >
                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-[13px] font-medium uppercase tracking-[0.14em] text-slate-600">Ready to stock</p>
                        <p class="mt-1 text-sm text-slate-500">Received deliveries waiting to be added to inventory</p>
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
                <div class="sticky top-0 z-10 grid grid-cols-[minmax(0,1fr)_3.5rem_3.5rem_4.5rem] items-center gap-2 border-b border-slate-100 bg-white px-5 py-2.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    <span>Receiving report</span>
                    <span class="text-right">Recv</span>
                    <span class="text-right" title="In inventory">Inv.</span>
                    <span class="text-right">Pending</span>
                </div>

                <div class="divide-y divide-slate-100 text-sm text-slate-700">
                    @foreach ($obPendingReports as $report)
                        @include('maintenance-personnel.equipment.partials.inventory-open-balance-rr', ['report' => $report])
                    @endforeach

                    @if ($obCompletedReports->isNotEmpty())
                        <div class="bg-white">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 px-5 py-3 text-left transition hover:bg-slate-50"
                                @click="toggleCompletedRr()"
                                :aria-expanded="showCompletedRr ? 'true' : 'false'"
                            >
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span class="inline-flex h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="showCompletedRr ? 'rotate-90' : ''">
                                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-slate-800" x-text="showCompletedRr ? 'Hide previous completed RRs' : 'Show previous completed RRs'"></span>
                                        <span class="mt-0.5 block text-[12px] text-slate-500">{{ $obCompletedReports->count() }} {{ \Illuminate\Support\Str::plural('receiving report', $obCompletedReports->count()) }} already in inventory</span>
                                    </span>
                                </span>
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-700">{{ $obCompletedReports->count() }} done</span>
                            </button>
                            <div x-show="showCompletedRr" x-cloak class="divide-y divide-slate-100 border-t border-slate-100">
                                @foreach ($obCompletedReports as $report)
                                    @include('maintenance-personnel.equipment.partials.inventory-open-balance-rr', ['report' => $report])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
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
