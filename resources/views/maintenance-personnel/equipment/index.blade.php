@extends ("layouts.maintenance-layout")

@section ("title", ($isStockPage ?? false) ? "Inventory" : "All Equipment")

@section ("content")
    @php
        $isStockPage = $isStockPage ?? (($scope ?? 'stock') === 'stock');
        $canMaintain = \App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::MAINTENANCE);
        $selectable = $isStockPage && $canMaintain;
        $acquisitionReady = \App\Support\EquipmentAcquisition::ready();
        $acquisitionSuppliers = \App\Support\EquipmentAcquisition::supplierOptions();
        $acquisitionPeople = $isStockPage ? \App\Support\Custodians::assignable() : collect();
        $replacementCandidates = $isStockPage ? \App\Support\EquipmentAcquisition::replacementCandidates() : collect();
        $eqImageUrl = function ($path) {
            if (!filled($path)) {
                return '';
            }

            if (
                str_starts_with($path, 'http://')
                || str_starts_with($path, 'https://')
                || str_starts_with($path, '/storage/')
            ) {
                return $path;
            }

            return asset('storage/'.$path);
        };
    @endphp

    <div class="space-y-6">
        <!-- PAGE HEADER -->
        @if ($isStockPage)
            <div class="flex flex-wrap items-center justify-end gap-2">
                @if ($canMaintain)
                <button
                    type="button"
                    id="inventoryTransferSelectedBtn"
                    onclick="openInventoryTransferSelectedModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled
                >
                    <i data-lucide="check-square" class="h-4 w-4"></i>
                    Transfer selected
                    <span id="inventoryTransferSelectedCount" class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-600">0</span>
                </button>
                <button
                    type="button"
                    onclick="openInventoryTransferAllModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (empty($stockTransferIds ?? [])) disabled @endif
                >
                    <i data-lucide="move" class="h-4 w-4"></i>
                    Transfer all
                </button>
                @endif
                <button
                    type="button"
                    id="stockPendingBtn"
                    onclick="openStockPendingModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-[#0025cc]/30 bg-[#0025cc]/5 px-4 py-2.5 text-[13px] font-semibold text-[#0025cc] transition hover:bg-[#0025cc]/10 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (!($defaultStorageRoomId ?? null) || ($pendingReceivableCount ?? 0) < 1) disabled @endif
                >
                    <i data-lucide="package-plus" class="h-4 w-4"></i>
                    Stock all pending
                    @if (($pendingReceivableCount ?? 0) > 0)
                        <span class="rounded-full bg-[#0025cc] px-1.5 py-0.5 text-[10px] font-bold text-white">{{ (int) $pendingReceivableCount }}</span>
                    @endif
                </button>
                <button
                    type="button"
                    onclick="openBatchAddEquipmentModal()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (!($defaultStorageRoomId ?? null)) disabled @endif
                >
                    <i data-lucide="layers" class="h-4 w-4"></i>
                    Batch add
                </button>
                <button
                    type="button"
                    onclick="openAddEquipmentModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 font-semibold font-sans-serif text-[13px] text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                    @if (!($defaultStorageRoomId ?? null)) disabled @endif
                >
                    <i data-lucide="plus" class="w-4 h-4"></i>

                    Add to stock
                </button>
            </div>
            @if (!($defaultStorageRoomId ?? null))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Create a room with type <span class="font-semibold">Storage / Stockroom</span> before adding inventory stock.
                </div>
            @endif
            @if (($pendingReceivableCount ?? 0) > 0)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-950">
                    <p>
                        <span class="font-semibold">{{ number_format($pendingReceivableCount) }}</span>
                        received RR line{{ $pendingReceivableCount === 1 ? '' : 's' }} ready to stock.
                        Import them in one click, or use <span class="font-semibold">Add to stock</span> for a single line.
                    </p>
                    <button
                        type="button"
                        onclick="openStockPendingModal()"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800"
                        @if (!($defaultStorageRoomId ?? null)) disabled @endif
                    >
                        <i data-lucide="package-plus" class="h-3.5 w-3.5"></i>
                        Stock all pending
                    </button>
                </div>
            @endif
            @php
                $obTotals = $openBalance['totals'] ?? [];
                $obReports = $openBalance['reports'] ?? [];
                $obOpen = collect($obReports)
                    ->mapWithKeys(fn ($r) => [(string) $r['receiving_report_id'] => (int) ($r['totals']['pending_stock'] ?? 0) > 0])
                    ->all();
            @endphp
            @if (!empty($obReports))
                <div class="rounded-2xl border border-slate-200/80 bg-white">
                    <div class="flex flex-wrap items-end justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Open balance</p>
                            <p class="mt-1 text-sm text-slate-500">RR delivery vs inventory stock</p>
                        </div>
                        <p class="text-xs text-slate-400">Ordered → Received → Stocked → Pending</p>
                    </div>

                    <div class="grid grid-cols-2 border-t border-slate-100 sm:grid-cols-4 sm:divide-x sm:divide-slate-100">
                        @foreach ([
                            'ordered' => 'Ordered',
                            'received' => 'Received',
                            'stocked' => 'Stocked',
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
                            <span class="text-right">Stock</span>
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
                                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Fully stocked</span>
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
                                                                    Stock {{ $pending }}
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
        @endif

        <!-- DASHBOARD CARDS -->
        @if ($isStockPage)
            @php
                $stockMonthlyHint = $equipmentMonthlyPercentage === null
                    ? 'New activity vs last month'
                    : (($equipmentMonthlyPercentage > 0 ? '+' : '') . number_format($equipmentMonthlyPercentage, 2) . '% vs last month');
            @endphp
            @include('layouts.partials.maintenance-stat-cards', [
                'cards' => [
                    ['label' => 'Items in storage', 'hint' => $stockMonthlyHint, 'value' => number_format($totalEquipment)],
                    ['label' => 'Total quantity', 'hint' => 'Combined qty in stockrooms', 'value' => number_format($stockTotalQuantity ?? 0)],
                    ['label' => 'Stock types', 'hint' => 'Distinct equipment names', 'value' => number_format($stockTypeCount ?? 0)],
                ],
            ])
        @else
            @php
                $equipmentMonthlyHint = $equipmentMonthlyPercentage === null
                    ? 'New activity vs last month'
                    : (($equipmentMonthlyPercentage > 0 ? '+' : '') . number_format($equipmentMonthlyPercentage, 2) . '% vs last month');
            @endphp
            @include('layouts.partials.maintenance-stat-cards', [
                'cards' => [
                    ['label' => 'Total Equipment', 'hint' => $equipmentMonthlyHint, 'value' => number_format($totalEquipment)],
                    ['label' => 'Active', 'hint' => number_format($activeEquipmentPercentage, 2) . '% of all equipment', 'value' => number_format($activeEquipment)],
                    ['label' => 'Under Maintenance', 'hint' => number_format($underMaintenanceEquipmentPercentage, 2) . '% of all equipment', 'value' => number_format($underMaintenanceEquipment)],
                ],
            ])
        @endif

        <!-- FILTER SECTION 
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <form method="GET">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search equipment..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <select
                            name="category"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Categories</option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->equipment_category_id }}"
                                    {{
                                        request("category") ==
                                        $category->equipment_category_id
                                            ? "selected"
                                            : ""
                                    }}
                                >
                                    {{ $category->equipment_category_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select
                            name="room"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Rooms</option>

                            @foreach ($rooms as $room)
                                <option
                                    value="{{ $room->room_id }}"
                                    {{
                                        request("room") == $room->room_id
                                            ? "selected"
                                            : ""
                                    }}
                                >
                                    {{ $room->room_name }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select
                            name="status"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-black"
                        >
                            <option value="">All Status</option>

                            <option
                                value="Active"
                                {{
                                    request("status") == "Active"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Active
                            </option>

                            <option
                                value="Under Maintenance"
                                {{
                                    request("status") == "Under Maintenance"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Under Maintenance
                            </option>

                            <option
                                value="Borrowed"
                                {{
                                    request("status") == "Borrowed"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Borrowed
                            </option>

                            <option
                                value="For Replacement"
                                {{
                                    request("status") == "For Replacement"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                For Replacement
                            </option>

                            <option
                                value="Disposed"
                                {{
                                    request("status") == "Disposed"
                                        ? "selected"
                                        : ""
                                }}
                            >
                                Disposed
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700"
                    >
                        Search
                    </button>
                </div>
            </form>
        </div>-->

        <!-- EQUIPMENT TABLE -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
        >
            <div class="overflow-x-auto">

                {{-- ===================================================== --}}
                {{-- TABLE TOOLBAR --}}
                {{-- STATUS TABS SHRINK AND SCROLL --}}
                {{-- FILTERS KEEP THEIR SPACE --}}
                {{-- ===================================================== --}}

                <div
                    class="flex flex-col gap-3 border-b border-slate-200
                        bg-white px-5 py-4
                        xl:flex-row xl:items-center"
                >

                    {{-- ================================================= --}}
                    {{-- LEFT SIDE --}}
                    {{-- STATUS TABS (All Equipment only) --}}
                    {{-- ================================================= --}}

                    <div class="min-w-0 flex-1">

                        @if ($isStockPage && ! $canMaintain)
                            <p class="text-sm text-slate-500">
                                Storage stock only. Transfers, edits and disposal are handled by Maintenance.
                            </p>
                        @elseif ($isStockPage)
                            <p class="text-sm text-slate-500">
                                Storage stock only. Check items to <span class="font-medium text-slate-800">Transfer selected</span>, use row <span class="font-medium text-slate-800">Transfer to</span>, or <span class="font-medium text-slate-800">Transfer all</span>.
                            </p>
                        @else
                        <div
                            class="flex items-center gap-1
                                overflow-x-auto whitespace-nowrap
                                [scrollbar-width:none]
                                [&::-webkit-scrollbar]:hidden"
                        >

                            {{-- ALL --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => null,
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        !request()->filled('status')
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                All
                            </a>


                            {{-- ACTIVE --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Active',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Active'
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Active
                            </a>


                            {{-- MAINTENANCE --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Under Maintenance',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Under Maintenance'
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Maintenance
                            </a>


                            {{-- BORROWED --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Borrowed',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Borrowed'
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Borrowed
                            </a>


                            {{-- FOR REPLACEMENT --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'For Replacement',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'For Replacement'
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                For Replacement
                            </a>


                            {{-- DISPOSED --}}

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'status' => 'Disposed',
                                    'page' => null,
                                ]) }}"

                                class="shrink-0 rounded-lg px-3 py-2
                                    text-sm transition
                                    {{
                                        request('status') === 'Disposed'
                                            ? 'bg-gray-100/80 font-medium text-black'
                                            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'
                                    }}"
                            >
                                Disposed
                            </a>

                        </div>
                        @endif

                    </div>


                    {{-- ================================================= --}}
                    {{-- RIGHT SIDE --}}
                    {{-- SEARCH AND FILTERS --}}
                    {{-- DOES NOT WRAP ON XL SCREENS --}}
                    {{-- ================================================= --}}

                    <form
                        method="GET"
                        action="{{ url()->current() }}"

                        class="flex shrink-0 flex-wrap items-center gap-2
                            xl:flex-nowrap"
                    >

                        {{-- ================================================= --}}
                        {{-- PRESERVE STATUS --}}
                        {{-- ================================================= --}}

                        @if (request()->filled('status'))

                            <input
                                type="hidden"
                                name="status"
                                value="{{ request('status') }}"
                            >

                        @endif

                        {{-- ================================================= --}}
                        {{-- SEARCH --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <i
                                data-lucide="search"

                                class="pointer-events-none absolute
                                    left-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400"
                            ></i>

                            <input
                                type="search"

                                name="search"

                                value="{{ request('search') }}"

                                placeholder="Search equipment..."

                                class="h-10 w-48 rounded-lg
                                    border border-slate-200
                                    bg-white pl-10 pr-3
                                    text-sm text-slate-700
                                    outline-none transition
                                    placeholder:text-slate-400
                                    focus:border-slate-400
                                    focus:ring-2 focus:ring-slate-100"
                            >

                        </div>


                        {{-- ================================================= --}}
                        {{-- CATEGORY --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <select
                                name="category"

                                class="h-10 w-40 appearance-none
                                    rounded-lg border border-slate-200
                                    bg-white pl-3 pr-9
                                    text-sm text-slate-600
                                    outline-none transition
                                    focus:border-slate-400
                                    focus:ring-2 focus:ring-slate-100"
                            >

                                <option value="">
                                    All Categories
                                </option>

                                @foreach ($categories as $category)

                                    <option
                                        value="{{ $category->equipment_category_id }}"

                                        @selected(
                                            request('category')
                                            == $category->equipment_category_id
                                        )
                                    >
                                        {{ $category->equipment_category_name }}
                                    </option>

                                @endforeach

                            </select>


                            <i
                                data-lucide="chevron-down"

                                class="pointer-events-none absolute
                                    right-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400"
                            ></i>

                        </div>


                        {{-- ================================================= --}}
                        {{-- ROOM --}}
                        {{-- ================================================= --}}

                        <div class="relative">

                            <select
                                name="room"

                                class="h-10 w-36 appearance-none
                                    rounded-lg border border-slate-200
                                    bg-white pl-3 pr-9
                                    text-sm text-slate-600
                                    outline-none transition
                                    focus:border-slate-400
                                    focus:ring-2 focus:ring-slate-100"
                            >

                                <option value="">
                                    {{ $isStockPage ? 'All storage rooms' : 'All Rooms' }}
                                </option>

                                @foreach ($rooms as $room)

                                    <option
                                        value="{{ $room->room_id }}"

                                        @selected(
                                            request('room') == $room->room_id
                                        )
                                    >
                                        {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                                    </option>

                                @endforeach

                            </select>


                            <i
                                data-lucide="chevron-down"

                                class="pointer-events-none absolute
                                    right-3 top-1/2 h-4 w-4
                                    -translate-y-1/2 text-slate-400"
                            ></i>

                        </div>


                        {{-- ================================================= --}}
                        {{-- APPLY --}}
                        {{-- ================================================= --}}

                        <button
                            type="submit"

                            class="inline-flex h-10 shrink-0
                                items-center justify-center gap-2
                                rounded-lg bg-[#0025cc] px-4
                                text-sm font-semibold text-white
                                transition hover:bg-blue-800"
                        >

                            <i
                                data-lucide="sliders-horizontal"
                                class="h-4 w-4"
                            ></i>

                            Apply

                        </button>


                        {{-- ================================================= --}}
                        {{-- CLEAR --}}
                        {{-- ================================================= --}}

                        @if (
                            request()->filled('search')
                            || request()->filled('category')
                            || request()->filled('room')
                        )

                            <a
                                href="{{ request()->fullUrlWithQuery([
                                    'search' => null,
                                    'category' => null,
                                    'room' => null,
                                    'page' => null,
                                ]) }}"

                                class="inline-flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-lg border border-slate-200
                                    bg-white text-slate-500
                                    transition
                                    hover:bg-slate-50
                                    hover:text-slate-900"

                                data-tooltip="Clear filters"
                            >

                                <i
                                    data-lucide="x"
                                    class="h-4 w-4"
                                ></i>

                            </a>

                        @endif

                    </form>

                </div>



                

                <table class="w-full" @if ($isStockPage) id="inventoryStockTable" @endif>
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr>
                            @if ($selectable)
                                <th class="w-12 px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        id="inventorySelectAllPage"
                                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                        onclick="toggleInventorySelectAllPage(this)"
                                        aria-label="Select all on this page"
                                    />
                                </th>
                            @endif
                            <th class="px-6 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black">
                                Equipment
                            </th>

                            <th class="px-4 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black">
                                Location
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Condition
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Status
                            </th>

                            <th class="px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                {{ $isStockPage ? 'Stocked' : 'Next maintenance' }}
                            </th>

                            <th class="w-32 px-4 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($equipment as $item)
                            @php
                                $conditionClass = match ($item->equipment_condition_status ?? "") {
                                    "Good" => "bg-emerald-50 text-emerald-700",
                                    "Fair" => "bg-sky-50 text-sky-700",
                                    "Damaged" => "bg-amber-50 text-amber-700",
                                    "Critical" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $inventoryClass = match ($item->equipment_inventory_status ?? "") {
                                    "Active" => "bg-emerald-50 text-emerald-700",
                                    "Borrowed" => "bg-sky-50 text-sky-700",
                                    "Under Maintenance" => "bg-amber-50 text-amber-700",
                                    "For Replacement" => "bg-orange-50 text-orange-700",
                                    "Disposed" => "bg-rose-50 text-rose-700",
                                    default => "bg-slate-100 text-slate-600",
                                };

                                $placementZone = trim((string) (
                                    $item->equipment_placement_zone
                                    ?: $item->equipment_current_location
                                    ?: ''
                                ));
                                $isStorageStock = \App\Support\RoomCategories::isStorageType($item->room_type ?? null);
                                $placementLabel = $isStorageStock ? 'Stock' : 'Deployed';
                                $placementClass = $isStorageStock
                                    ? 'bg-amber-50 text-amber-700 ring-amber-200'
                                    : 'bg-sky-50 text-sky-700 ring-sky-200';
                            @endphp
                            <tr class="border-b border-slate-100 transition duration-200 hover:bg-slate-50">
                                @if ($selectable)
                                    <td class="px-4 py-4 text-center">
                                        <input
                                            type="checkbox"
                                            class="inventory-stock-checkbox h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                            value="{{ (int) $item->equipment_id }}"
                                            data-name="{{ $item->equipment_name }}"
                                            onchange="syncInventoryTransferSelection()"
                                            aria-label="Select {{ $item->equipment_name }}"
                                        />
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                                            @if (filled($item->equipment_image))
                                                <button
                                                    type="button"
                                                    onclick="event.stopPropagation(); openEquipmentPhotoViewer({{ json_encode($eqImageUrl($item->equipment_image)) }}, {{ json_encode($item->equipment_name) }})"
                                                    class="group relative h-full w-full"
                                                    aria-label="View {{ $item->equipment_name }} photo fullscreen"
                                                >
                                                    <img
                                                        src="{{ $eqImageUrl($item->equipment_image) }}"
                                                        alt="{{ $item->equipment_name }}"
                                                        class="h-full w-full object-cover"
                                                    >
                                                    <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                                        <i data-lucide="expand" class="h-3.5 w-3.5 text-white opacity-0 transition group-hover:opacity-100"></i>
                                                    </span>
                                                </button>
                                            @else
                                                <span
                                                    class="inline-flex h-6 w-6 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                                    data-equipment-layout-icon="{{ $item->equipment_name }}"
                                                ></span>
                                            @endif
                                        </div>
                                        <div class="flex min-w-0 flex-col">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-semibold text-slate-900">
                                                    {{ $item->equipment_name }}
                                                </span>
                                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset {{ $placementClass }}">
                                                    {{ $placementLabel }}
                                                </span>
                                            </div>

                                            <span class="mt-1 text-xs text-slate-400">
                                                {{ $item->equipment_asset_tag ?: "No Asset Tag" }}
                                            </span>
                                            <span class="mt-0.5 text-xs text-slate-400">
                                                {{ $item->equipment_category_name ?: 'Uncategorized' }} · Qty {{ $item->equipment_quantity }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <p class="text-sm {{ filled($item->room_name) ? 'text-slate-700' : 'text-slate-400' }}">{{ $item->room_name ?: 'Unassigned' }}</p>
                                    @if ($placementZone !== '')
                                        <p class="mt-0.5 text-xs text-slate-400">{{ $placementZone }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $conditionClass }}">
                                        {{ $item->equipment_condition_status }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $inventoryClass }}">
                                        {{ $item->equipment_inventory_status }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-center">
                                    @if ($isStockPage)
                                        @php
                                            $stockedAt = $item->equipment_acquired_date ?: $item->equipment_purchase_date;
                                            $stockedDays = filled($stockedAt)
                                                ? (int) \Carbon\Carbon::parse($stockedAt)->startOfDay()->diffInDays(today(), false)
                                                : null;
                                        @endphp
                                        @if ($stockedDays !== null)
                                            <p class="text-sm text-slate-700">{{ \Carbon\Carbon::parse($stockedAt)->format('M j, Y') }}</p>
                                            <p class="mt-0.5 text-xs {{ $stockedDays > 90 ? 'font-medium text-amber-600' : 'text-slate-400' }}">
                                                @if ($stockedDays <= 0)
                                                    Stocked today
                                                @else
                                                    {{ $stockedDays }} {{ \Illuminate\Support\Str::plural('day', $stockedDays) }} in stock
                                                @endif
                                            </p>
                                        @else
                                            <span class="text-sm text-slate-400">Not recorded</span>
                                        @endif
                                    @elseif (filled($item->next_maintenance_date ?? null))
                                        @php
                                            $maintenanceOverdue = \App\Support\EquipmentNextMaintenance::isOverdue($item);
                                        @endphp
                                        <a
                                            href="{{ url('/maintenance/schedules').'?'.http_build_query(['search' => $item->equipment_name]) }}"
                                            class="text-sm transition hover:text-[#0025cc] {{ $maintenanceOverdue ? 'font-semibold text-rose-700' : 'text-slate-700' }}"
                                        >{{ \Carbon\Carbon::parse($item->next_maintenance_date)->format('M j, Y') }}</a>
                                        @if ($maintenanceOverdue)
                                            <span class="ml-1.5 inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-sm text-slate-400">Not scheduled</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex justify-center gap-2">

                                        <button
                                            type="button"
                                            onclick='openEquipmentModal(@json(\App\Support\LayoutEquipmentPayload::fromRow($item, $eqImageUrl($item->equipment_image))))'
                                            class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg  bg-slate-100 px-3 text-xs  text-slate-800 transition shadow-sm hover:bg-slate-200 hover:text-gray-600"
                                            data-tooltip="View equipment"
                                            aria-label="View equipment"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>

                                        @if ($isStockPage && $canMaintain)
                                            <button
                                                type="button"
                                                onclick="openInventoryTransferToModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }},
                                                    {{ json_encode($item->room_name ?? '') }},
                                                    {{ json_encode($item->equipment_asset_tag ?? '') }}
                                                )"
                                                class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg bg-sky-50 px-3 text-xs font-medium text-sky-800 transition hover:bg-sky-100"
                                                data-tooltip="Transfer to room"
                                                aria-label="Transfer to room"
                                            >
                                                <i data-lucide="move" class="h-4 w-4"></i>
                                            </button>
                                        @endif

                                        @if ($canMaintain)
                                        <button
                                            type="button"
                                            onclick="openEditEquipmentModal(

                                                '{{ $item->equipment_id }}',

                                                '{{ $item->equipment_category_id }}',

                                                '{{ $item->equipment_room_id }}',

                                                '{{ $item->equipment_asset_tag }}',

                                                '{{ $item->equipment_name }}',

                                                '{{ $item->equipment_brand_name }}',

                                                '{{ $item->equipment_model }}',

                                                '{{ $item->equipment_serial_number }}',

                                                '{{ $item->equipment_quantity }}',

                                                '{{ $item->equipment_condition_status }}',

                                                '{{ $item->equipment_inventory_status }}',

                                                '{{ $item->equipment_purchase_date ? \Carbon\Carbon::parse($item->equipment_purchase_date)->format('Y-m-d') : '' }}',

                                                '{{ $item->equipment_acquired_date ? \Carbon\Carbon::parse($item->equipment_acquired_date)->format('Y-m-d') : '' }}',

                                                '{{ $item->equipment_purchase_cost ?? '' }}',

                                                '{{ $item->equipment_warranty_expiration ? \Carbon\Carbon::parse($item->equipment_warranty_expiration)->format('Y-m-d') : '' }}',

                                                '{{ $item->equipment_useful_life_years ?? '' }}',

                                                '{{ $item->equipment_is_borrowable }}',

                                                {{ json_encode($eqImageUrl($item->equipment_image)) }},

                                                {{ json_encode(\App\Support\EquipmentAcquisition::formPayload($item)) }}

                                            )"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                            data-tooltip="Edit equipment"
                                            aria-label="Edit equipment"
                                        >
                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                        </button>

                                        @if (($item->equipment_inventory_status ?? '') === 'For Replacement')
                                            <button
                                                type="button"
                                                onclick="openInventoryDisposeModal(
                                                    {{ (int) $item->equipment_id }},
                                                    {{ json_encode($item->equipment_name) }},
                                                    {{ json_encode($item->room_name ?? '') }}
                                                )"
                                                class="flex h-9 items-center justify-center gap-x-1.5 rounded-lg bg-rose-600 px-3 text-xs font-medium text-white transition hover:bg-rose-700"
                                                data-tooltip="Dispose equipment"
                                                aria-label="Dispose equipment"
                                            >
                                                <i data-lucide="archive-x" class="h-4 w-4"></i>
                                                
                                            </button>
                                        @endif
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="{{ $selectable ? 7 : 6 }}"
                                    class="px-6 py-16 text-center"
                                >

                                    {{-- ===================================================== --}}
                                    {{-- EMPTY STATE --}}
                                    {{-- ===================================================== --}}

                                    <div class="mx-auto flex max-w-sm flex-col items-center">

                                        {{-- ================================================= --}}
                                        {{-- ICON --}}
                                        {{-- ================================================= --}}

                                        <div
                                            class="flex h-12 w-12 items-center justify-center
                                                rounded-2xl border border-slate-200
                                                bg-slate-50 text-slate-400"
                                        >
                                            <i
                                                data-lucide="{{
                                                    request()->filled('search')
                                                    || request()->filled('category')
                                                    || request()->filled('room')
                                                    || request()->filled('status')
                                                        ? 'search-x'
                                                        : 'package-open'
                                                }}"
                                                class="h-5 w-5"
                                            ></i>
                                        </div>


                                        {{-- ================================================= --}}
                                        {{-- TITLE --}}
                                        {{-- ================================================= --}}

                                        <h3 class="mt-4 text-sm font-semibold text-slate-800">

                                            {{
                                                request()->filled('search')
                                                || request()->filled('category')
                                                || request()->filled('room')
                                                || request()->filled('status')

                                                    ? 'No matching equipment'

                                                    : 'No equipment yet'
                                            }}

                                        </h3>


                                        {{-- ================================================= --}}
                                        {{-- DESCRIPTION --}}
                                        {{-- ================================================= --}}

                                        <p
                                            class="mt-1.5 max-w-xs text-xs leading-5
                                                text-slate-400"
                                        >

                                            {{
                                                request()->filled('search')
                                                || request()->filled('category')
                                                || request()->filled('room')
                                                || request()->filled('status')

                                                    ? 'No equipment matches your current search or filters. Try adjusting them.'

                                                    : 'Equipment added to the inventory will appear here.'
                                            }}

                                        </p>


                                        {{-- ================================================= --}}
                                        {{-- CLEAR FILTERS --}}
                                        {{-- ONLY SHOW WHEN FILTERING --}}
                                        {{-- ================================================= --}}

                                        @if (
                                            request()->filled('search')
                                            || request()->filled('category')
                                            || request()->filled('room')
                                            || request()->filled('status')
                                        )

                                            <a
                                                href="{{ url()->current() }}"

                                                class="mt-5 inline-flex h-9 items-center gap-2
                                                    rounded-lg border border-slate-200
                                                    bg-white px-3.5
                                                    text-xs font-semibold text-slate-600
                                                    shadow-sm transition
                                                    hover:border-slate-300
                                                    hover:bg-slate-50
                                                    hover:text-slate-900"
                                            >

                                                <i
                                                    data-lucide="rotate-ccw"
                                                    class="h-3.5 w-3.5"
                                                ></i>

                                                Clear filters

                                            </a>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ===================================================== --}}
            {{-- PAGINATION --}}
            {{-- PLACE INSIDE EQUIPMENT TABLE CARD --}}
            {{-- DIRECTLY BELOW TABLE CONTAINER --}}
            {{-- ===================================================== --}}

            @if ($equipment->hasPages())

                <div
                    class="flex flex-col gap-3
                        border-t border-slate-200
                        px-5 py-4
                        sm:flex-row
                        sm:items-center
                        sm:justify-between"
                >

                    {{-- ================================================= --}}
                    {{-- PAGINATION INFORMATION --}}
                    {{-- ================================================= --}}

                    <p class="text-xs text-slate-500">

                        Showing

                        <span class="font-semibold text-slate-700">
                            {{ $equipment->firstItem() }}
                        </span>

                        to

                        <span class="font-semibold text-slate-700">
                            {{ $equipment->lastItem() }}
                        </span>

                        of

                        <span class="font-semibold text-slate-700">
                            {{ $equipment->total() }}
                        </span>

                        equipment

                    </p>


                    {{-- ================================================= --}}
                    {{-- PAGINATION LINKS --}}
                    {{-- ================================================= --}}

                    <div>
                        {{ $equipment->links() }}
                    </div>

                </div>

            @endif
        </div>

        
    </div>

    @php
        $eqField = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
        $eqLabel = 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
    @endphp

        @include('maintenance-personnel.equipment.partials.equipment-asset-drawer')
    @if ($isStockPage)
        @include('maintenance-personnel.equipment.partials.add-equipment-wizard', ['isStockPage' => true, 'wizardTitle' => 'Add to stock'])
        @include('maintenance-personnel.equipment.partials.batch-add-wizard')
    @endif

    <div
        id="editEquipmentModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <form id="editEquipmentForm" method="POST" enctype="multipart/form-data" class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10">
            @csrf
            <div class="flex items-start justify-between px-6 pt-6">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900">Edit equipment</h2>
                    <p class="mt-1 text-sm text-slate-500">Identity on the left, status on the right.</p>
                </div>
                <button type="button" onclick="closeEditEquipmentModal()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div class="mb-5 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Photo (optional)</p>
                    <div class="mt-3 flex items-center gap-4">
                        <button
                            type="button"
                            id="edit_image_button"
                            onclick="openEquipmentPhotoViewer(document.getElementById('edit_image_preview')?.src, document.getElementById('edit_equipment_name')?.value || 'Equipment photo')"
                            class="group relative hidden h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80"
                            aria-label="View equipment photo fullscreen"
                        >
                            <img id="edit_image_preview" src="" alt="Equipment photo" class="h-full w-full object-cover">
                            <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/40">
                                <i data-lucide="expand" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100"></i>
                            </span>
                        </button>
                        <div id="edit_image_placeholder" class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                            <span id="edit_layout_icon" class="inline-flex h-8 w-8 items-center justify-center [&_svg]:h-full [&_svg]:w-full"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">Change equipment photo</p>
                            <p class="mt-0.5 text-xs text-slate-400">JPG, PNG, WebP, or GIF. Max 5 MB. Leave empty to keep the current photo.</p>
                            <p id="edit_image_hint" class="mt-0.5 hidden text-xs text-slate-500">Click the photo to view it full screen.</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <label class="inline-flex h-9 cursor-pointer items-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 ring-1 ring-slate-200/80 transition hover:bg-slate-50">
                                    Choose image
                                    <input
                                        id="edit_equipment_image"
                                        type="file"
                                        name="equipment_image"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        class="sr-only"
                                    >
                                </label>
                                <button
                                    type="button"
                                    id="edit_clear_image"
                                    class="hidden h-9 items-center rounded-lg px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                                >
                                    Remove
                                </button>
                                <input type="hidden" name="remove_equipment_image" id="edit_remove_image" value="0">
                            </div>
                            @error('equipment_image')
                                <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What & where</p>
                        <div>
                            <label for="edit_equipment_name" class="{{ $eqLabel }}">Equipment name <span class="text-rose-500">*</span></label>
                            <input id="edit_equipment_name" type="text" name="equipment_name" required class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_category" class="{{ $eqLabel }}">Category <span class="text-rose-500">*</span></label>
                            <select id="edit_category" name="equipment_category_id" required class="{{ $eqField }}">
                                <option value="">Select category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="edit_room" class="{{ $eqLabel }}">Room <span class="text-rose-500">*</span></label>
                            <select id="edit_room" name="equipment_room_id" required class="{{ $eqField }}">
                                <option value="">Select room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->room_id }}">
                                        {{ \App\Support\RoomCategories::isStorageType($room->room_type ?? null) ? 'Storage · '.$room->room_name : $room->room_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="edit_quantity" class="{{ $eqLabel }}">Qty</label>
                                <input id="edit_quantity" type="number" min="1" name="equipment_quantity" class="{{ $eqField }}" />
                            </div>
                            <div>
                                <label for="edit_condition" class="{{ $eqLabel }}">Condition</label>
                                <select id="edit_condition" name="equipment_condition_status" class="{{ $eqField }}">
                                    <option value="Good">Good</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Under Maintenance">Under maintenance</option>
                                    <option value="Disposed">Disposed</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="edit_status" class="{{ $eqLabel }}">Inventory status</label>
                            <select id="edit_status" name="equipment_inventory_status" class="{{ $eqField }}">
                                <option value="Active">Active</option>
                                <option value="Under Maintenance">Under maintenance</option>
                                <option value="Borrowed">Borrowed</option>
                                <option value="For Replacement">For replacement</option>
                                <option value="Disposed">Disposed</option>
                            </select>
                        </div>
                        <label class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200/80">
                            <span class="text-sm font-medium text-slate-900">Can be borrowed</span>
                            <input id="edit_equipment_borrowable" type="checkbox" name="equipment_is_borrowable" value="1" class="peer sr-only">
                            <span class="relative h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-[#0025cc] after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-5"></span>
                        </label>
                    </div>
                </div>
                <details class="mt-5 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">More details</summary>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="edit_asset_tag" class="{{ $eqLabel }}">Asset tag</label>
                            <input id="edit_asset_tag" type="text" name="equipment_asset_tag" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_brand" class="{{ $eqLabel }}">Brand name</label>
                            <input id="edit_brand" type="text" name="equipment_brand_name" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_model" class="{{ $eqLabel }}">Model</label>
                            <input id="edit_model" type="text" name="equipment_model" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_serial" class="{{ $eqLabel }}">Serial number</label>
                            <input id="edit_serial" type="text" name="equipment_serial_number" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_purchase_date" class="{{ $eqLabel }}">Purchased</label>
                            <input id="edit_purchase_date" type="date" name="equipment_purchase_date" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_acquired_date" class="{{ $eqLabel }}">Acquired</label>
                            <input id="edit_acquired_date" type="date" name="equipment_acquired_date" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label for="edit_purchase_cost" class="{{ $eqLabel }}">Purchase cost (₱)</label>
                            <input id="edit_purchase_cost" type="number" name="equipment_purchase_cost" min="0" step="0.01" placeholder="0.00" class="{{ $eqField }}" />
                        </div>
                        <div>
                            <label
                                for="edit_warranty_expiration"
                                class="{{ $eqLabel }}"
                            >
                                Warranty expiration
                            </label>

                            <input
                                id="edit_warranty_expiration"
                                type="date"
                                name="equipment_warranty_expiration"
                                class="{{ $eqField }}"
                            />
                        </div>
                        <div>
                            <label for="edit_useful_life_years" class="{{ $eqLabel }}">Useful lifespan (years)</label>
                            <input
                                id="edit_useful_life_years"
                                type="number"
                                name="equipment_useful_life_years"
                                min="1"
                                max="50"
                                step="1"
                                placeholder="Default 5"
                                class="{{ $eqField }}"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Used for replacement broadcasts when equipment nears end of life.</p>
                        </div>
                    </div>
                    @if ($acquisitionReady)
                        <div class="mt-5 border-t border-slate-200/80 pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Acquisition</p>
                            <p id="edit_acquisition_linked" class="mt-2 hidden rounded-xl bg-white px-3 py-2 text-xs text-slate-600 ring-1 ring-slate-200/80">
                                Linked to a Receiving Report. Supplier, PO, ATP / RIS and RR come from the procurement documents and cannot be edited here.
                            </p>
                            <div id="edit_acquisition_fields" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="edit_acquisition_source" class="{{ $eqLabel }}">How was it acquired?</label>
                                    <select id="edit_acquisition_source" name="equipment_acquisition_source" class="{{ $eqField }}">
                                        <option value="">Not recorded</option>
                                        @foreach (\App\Support\EquipmentAcquisition::SOURCES as $sourceKey => $sourceLabel)
                                            <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_acquisition_supplier" class="{{ $eqLabel }}">Supplier / donor</label>
                                    <select
                                        id="edit_acquisition_supplier"
                                        name="acquisition_supplier"
                                        data-searchable="1"
                                        data-search-placeholder="Search suppliers…"
                                        onchange="syncEditSupplierName()"
                                        class="{{ $eqField }}"
                                    >
                                        <option value="">Not recorded</option>
                                        <option value="{{ \App\Support\EquipmentAcquisition::OTHER_SUPPLIER }}">Other — type the name</option>
                                        @foreach ($acquisitionSuppliers as $supplierOption)
                                            <option value="{{ $supplierOption->supplier_id }}">{{ $supplierOption->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="edit_supplier_name_wrap" class="hidden">
                                    <label for="edit_supplier_name" class="{{ $eqLabel }}">Supplier / donor name</label>
                                    <input id="edit_supplier_name" type="text" name="equipment_supplier_name" maxlength="255" class="{{ $eqField }}" />
                                </div>
                                <div>
                                    <label for="edit_reference_number" class="{{ $eqLabel }}">Reference no.</label>
                                    <input id="edit_reference_number" type="text" name="equipment_reference_number" maxlength="120" placeholder="OR / invoice / DR no." class="{{ $eqField }}" />
                                </div>
                                <div class="sm:col-span-2 lg:col-span-4">
                                    <label for="edit_acquisition_notes" class="{{ $eqLabel }}">Notes</label>
                                    <input id="edit_acquisition_notes" type="text" name="equipment_acquisition_notes" maxlength="500" placeholder="e.g. Donated by Batch 2019 alumni" class="{{ $eqField }}" />
                                </div>
                            </div>
                        </div>
                    @endif
                </details>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button type="button" onclick="closeEditEquipmentModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Save changes</button>
            </div>
        </form>
    </div>



    <script>
        function openEditEquipmentModal(
            id,
            category,
            room,
            assetTag,
            name,
            brand,
            model,
            serial,
            quantity,
            condition,
            status,
            purchaseDate,
            acquiredDate,
            purchaseCost,
            warranty,
            usefulLifeYears,
            borrowable,
            imageUrl,
            acquisition
        ) {
            document.getElementById("editEquipmentForm").action =
                "/maintenance/equipment/update/" + id;

            document.getElementById("edit_equipment_name").value = name;

            document.getElementById("edit_asset_tag").value = assetTag;

            document.getElementById("edit_brand").value = brand;

            document.getElementById("edit_model").value = model;

            document.getElementById("edit_serial").value = serial;

            document.getElementById("edit_purchase_date").value = purchaseDate || "";

            document.getElementById("edit_acquired_date").value = acquiredDate || "";

            document.getElementById("edit_purchase_cost").value = purchaseCost || "";

            document.getElementById("edit_warranty_expiration").value = warranty;

            const usefulLifeInput = document.getElementById("edit_useful_life_years");
            if (usefulLifeInput) {
                usefulLifeInput.value = usefulLifeYears || "";
            }

            document.getElementById("edit_quantity").value = quantity;

            setEqSelectValue("edit_condition", condition);

            setEqSelectValue("edit_status", status);

            setEqSelectValue("edit_category", category);

            setEqSelectValue("edit_room", room);

            document.getElementById("edit_equipment_borrowable").checked =
                borrowable == 1;

            setEditEquipmentImage(imageUrl, name);

            setEditAcquisition(acquisition || {});

            document
                .getElementById("editEquipmentModal")
                .classList.remove("hidden");

            document.getElementById("editEquipmentModal").classList.add("flex");

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function setEditAcquisition(acquisition) {
            const fields = document.getElementById("edit_acquisition_fields");
            if (!fields) {
                return;
            }
            const linked = !!acquisition.linked_rr;
            document.getElementById("edit_acquisition_linked")?.classList.toggle("hidden", !linked);
            fields.classList.toggle("hidden", linked);
            fields.querySelectorAll("input, select").forEach((el) => {
                el.disabled = linked;
            });

            setEqSelectValue("edit_acquisition_source", acquisition.source || "");
            setEqSelectValue("edit_acquisition_supplier", acquisition.supplier || "");
            document.getElementById("edit_supplier_name").value = acquisition.supplier_name || "";
            document.getElementById("edit_reference_number").value = acquisition.reference || "";
            document.getElementById("edit_acquisition_notes").value = acquisition.notes || "";
            syncEditSupplierName();
        }

        function syncEditSupplierName() {
            const select = document.getElementById("edit_acquisition_supplier");
            const wrap = document.getElementById("edit_supplier_name_wrap");
            const input = document.getElementById("edit_supplier_name");
            if (!select || !wrap || !input) {
                return;
            }
            const isOther = select.value === @json(\App\Support\EquipmentAcquisition::OTHER_SUPPLIER);
            wrap.classList.toggle("hidden", !isOther);
            input.disabled = select.disabled || !isOther;
        }

        function setEditEquipmentImage(imageUrl, name) {
            const preview = document.getElementById("edit_image_preview");
            const previewButton = document.getElementById("edit_image_button");
            const placeholder = document.getElementById("edit_image_placeholder");
            const layoutIcon = document.getElementById("edit_layout_icon");
            const hint = document.getElementById("edit_image_hint");
            const clearButton = document.getElementById("edit_clear_image");
            const fileInput = document.getElementById("edit_equipment_image");
            const removeInput = document.getElementById("edit_remove_image");
            const equipmentName = name || document.getElementById("edit_equipment_name")?.value || "";

            if (fileInput) {
                fileInput.value = "";
            }
            if (removeInput) {
                removeInput.value = "0";
            }

            if (layoutIcon && window.PrismEquipmentIcons) {
                layoutIcon.innerHTML = window.PrismEquipmentIcons.svg(equipmentName);
            }

            if (imageUrl) {
                preview.src = imageUrl;
                previewButton.classList.remove("hidden");
                placeholder.classList.add("hidden");
                hint?.classList.remove("hidden");
                clearButton.classList.remove("hidden");
                clearButton.classList.add("inline-flex");
            } else {
                preview.src = "";
                previewButton.classList.add("hidden");
                placeholder.classList.remove("hidden");
                hint?.classList.add("hidden");
                clearButton.classList.add("hidden");
                clearButton.classList.remove("inline-flex");
            }

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        document.addEventListener("DOMContentLoaded", function () {
            const fileInput = document.getElementById("edit_equipment_image");
            const clearButton = document.getElementById("edit_clear_image");

            fileInput?.addEventListener("change", function (event) {
                const file = event.target.files?.[0];
                const preview = document.getElementById("edit_image_preview");
                const previewButton = document.getElementById("edit_image_button");
                const placeholder = document.getElementById("edit_image_placeholder");
                const removeInput = document.getElementById("edit_remove_image");
                const removeButton = document.getElementById("edit_clear_image");

                if (removeInput) {
                    removeInput.value = "0";
                }

                if (!file) {
                    return;
                }

                preview.src = URL.createObjectURL(file);
                previewButton.classList.remove("hidden");
                placeholder.classList.add("hidden");
                document.getElementById("edit_image_hint")?.classList.remove("hidden");
                removeButton.classList.remove("hidden");
                removeButton.classList.add("inline-flex");

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });

            clearButton?.addEventListener("click", function () {
                setEditEquipmentImage("");
                document.getElementById("edit_remove_image").value = "1";
            });

            document.getElementById("edit_equipment_name")?.addEventListener("input", function () {
                const previewButton = document.getElementById("edit_image_button");
                const layoutIcon = document.getElementById("edit_layout_icon");
                if (!layoutIcon || !window.PrismEquipmentIcons) {
                    return;
                }
                if (previewButton && !previewButton.classList.contains("hidden")) {
                    return;
                }
                layoutIcon.innerHTML = window.PrismEquipmentIcons.svg(this.value);
            });
        });

        function closeEditEquipmentModal() {
            if (window.closeEqSelectPanel) {
                closeEqSelectPanel();
            }
            document
                .getElementById("editEquipmentModal")
                .classList.add("hidden");

            document
                .getElementById("editEquipmentModal")
                .classList.remove("flex");
        }

        function openInventoryDisposeModal(equipmentId, equipmentName, roomName) {
            document.getElementById("inventoryDisposeEquipmentId").value = equipmentId;
            document.getElementById("inventoryDisposeEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryDisposeLocation").value = roomName || "";
            document.getElementById("inventoryDisposeReason").value = "";

            const modal = document.getElementById("inventoryDisposeModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeInventoryDisposeModal() {
            const modal = document.getElementById("inventoryDisposeModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        let stockPendingLinesCache = [];

        function openStockPendingModal() {
            const modal = document.getElementById('stockPendingModal');
            const loading = document.getElementById('stockPendingLoading');
            const empty = document.getElementById('stockPendingEmpty');
            const list = document.getElementById('stockPendingList');
            const meta = document.getElementById('stockPendingMeta');
            const errorEl = document.getElementById('stockPendingError');
            const confirmBtn = document.getElementById('stockPendingConfirmBtn');

            if (!modal) return;

            stockPendingLinesCache = [];
            loading?.classList.remove('hidden');
            empty?.classList.add('hidden');
            list?.classList.add('hidden');
            meta?.classList.add('hidden');
            errorEl?.classList.add('hidden');
            if (errorEl) errorEl.textContent = '';
            if (confirmBtn) confirmBtn.disabled = true;
            if (list) list.innerHTML = '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) window.lucide.createIcons();

            fetch('/maintenance/equipment/receivable-lines', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then((res) => res.json())
                .then((data) => {
                    loading?.classList.add('hidden');
                    const lines = Array.isArray(data.lines) ? data.lines : [];
                    stockPendingLinesCache = lines;
                    if (!lines.length) {
                        empty?.classList.remove('hidden');
                        return;
                    }
                    if (list) {
                        list.innerHTML = lines.map((line) => {
                            const qty = line.remaining_qty != null ? line.remaining_qty : line.quantity;
                            return `
                                <li class="rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5">
                                    <p class="text-sm font-semibold text-slate-900">${escapeStockPendingHtml(line.article || 'Item')}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                        ${escapeStockPendingHtml(line.rr_number || 'RR')}
                                        · ${line.po_number ? ('PO ' + escapeStockPendingHtml(line.po_number)) : 'No PO'}
                                        · Left <span class="font-semibold text-amber-700">${Number(qty) || 0}</span>
                                    </p>
                                </li>
                            `;
                        }).join('');
                        list.classList.remove('hidden');
                    }
                    meta?.classList.remove('hidden');
                    if (confirmBtn) confirmBtn.disabled = false;
                    if (window.lucide) window.lucide.createIcons();
                })
                .catch(() => {
                    loading?.classList.add('hidden');
                    if (errorEl) {
                        errorEl.textContent = 'Could not load pending RR lines.';
                        errorEl.classList.remove('hidden');
                    }
                });
        }

        function closeStockPendingModal() {
            const modal = document.getElementById('stockPendingModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function escapeStockPendingHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        async function confirmStockPending() {
            const confirmBtn = document.getElementById('stockPendingConfirmBtn');
            const errorEl = document.getElementById('stockPendingError');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Stocking…';
            }
            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.textContent = '';
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value
                || '';

            const body = {
                equipment_room_id: {{ (int) ($defaultStorageRoomId ?? 0) }},
                receiving_report_item_ids: stockPendingLinesCache.map((line) => line.id || line.receiving_report_item_id).filter(Boolean),
            };

            try {
                const res = await fetch('/maintenance/equipment/stock-pending', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || data.ok === false) {
                    throw new Error(data.message || 'Stocking failed.');
                }
                window.location.href = data.redirect || '/maintenance/equipment/inventory';
            } catch (err) {
                if (errorEl) {
                    errorEl.textContent = err.message || 'Stocking failed.';
                    errorEl.classList.remove('hidden');
                }
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i data-lucide="check" class="h-4 w-4"></i> Confirm stock';
                    if (window.lucide) window.lucide.createIcons();
                }
            }
        }

        function openInventoryTransferToModal(equipmentId, equipmentName, roomName, assetTag) {
            document.getElementById("inventoryTransferEquipmentId").value = equipmentId;
            document.getElementById("inventoryTransferEquipmentName").textContent = equipmentName || "Equipment";
            document.getElementById("inventoryTransferCurrentRoom").textContent = roomName || "—";
            document.getElementById("inventoryTransferAssetTag").textContent = assetTag || "No asset tag";
            document.getElementById("inventoryTransferRoomId").value = "";
            document.getElementById("inventoryTransferRemarks").value = "";

            const modal = document.getElementById("inventoryTransferToModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryTransferToModal() {
            const modal = document.getElementById("inventoryTransferToModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function openInventoryTransferAllModal() {
            const count = Number(document.getElementById("inventoryTransferAllCount")?.dataset.count || 0);
            if (!count) return;

            document.getElementById("inventoryTransferAllRoomId").value = "";
            document.getElementById("inventoryTransferAllRemarks").value = "";

            const modal = document.getElementById("inventoryTransferAllModal");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryTransferAllModal() {
            const modal = document.getElementById("inventoryTransferAllModal");
            modal.classList.add("hidden");
            modal.classList.remove("flex");
        }

        function getInventorySelectedIds() {
            return Array.from(document.querySelectorAll('.inventory-stock-checkbox:checked'))
                .map((input) => Number(input.value))
                .filter((id) => Number.isFinite(id) && id > 0);
        }

        function syncInventoryTransferSelection() {
            const selected = getInventorySelectedIds();
            const countEl = document.getElementById('inventoryTransferSelectedCount');
            const btn = document.getElementById('inventoryTransferSelectedBtn');
            const selectAll = document.getElementById('inventorySelectAllPage');
            const boxes = document.querySelectorAll('.inventory-stock-checkbox');

            if (countEl) countEl.textContent = String(selected.length);
            if (btn) btn.disabled = selected.length === 0;

            if (selectAll && boxes.length) {
                selectAll.checked = selected.length === boxes.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < boxes.length;
            }
        }

        function toggleInventorySelectAllPage(source) {
            document.querySelectorAll('.inventory-stock-checkbox').forEach((input) => {
                input.checked = !!source.checked;
            });
            syncInventoryTransferSelection();
        }

        function openInventoryTransferSelectedModal() {
            const ids = getInventorySelectedIds();
            if (!ids.length) return;

            const container = document.getElementById('inventoryTransferSelectedIds');
            if (!container) return;

            container.innerHTML = '';
            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'equipment_ids[]';
                input.value = String(id);
                container.appendChild(input);
            });

            const countLabel = document.getElementById('inventoryTransferSelectedModalCount');
            if (countLabel) countLabel.textContent = String(ids.length);

            const summary = document.getElementById('inventoryTransferSelectedSummary');
            if (summary) {
                const names = Array.from(document.querySelectorAll('.inventory-stock-checkbox:checked'))
                    .map((input) => input.dataset.name || 'Item')
                    .slice(0, 6);
                const extra = ids.length > names.length ? ` +${ids.length - names.length} more` : '';
                summary.textContent = names.join(', ') + extra;
            }

            document.getElementById('inventoryTransferSelectedRoomId').value = '';
            document.getElementById('inventoryTransferSelectedRemarks').value = '';

            const modal = document.getElementById('inventoryTransferSelectedModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeInventoryTransferSelectedModal() {
            const modal = document.getElementById('inventoryTransferSelectedModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('DOMContentLoaded', () => {
            syncInventoryTransferSelection();
        });
    </script>

    @if ($isStockPage)
    <div
        id="inventoryTransferSelectedModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="check-square" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer selected</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy
                        <span id="inventoryTransferSelectedModalCount" class="font-semibold text-slate-800">0</span>
                        selected item(s) to one classroom or lab. Then select another set for a different room.
                    </p>
                    <p id="inventoryTransferSelectedSummary" class="mt-2 text-xs text-slate-400"></p>
                </div>
                <button type="button" onclick="closeInventoryTransferSelectedModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/equipment/transfer-batch" method="POST">
                @csrf
                <div id="inventoryTransferSelectedIds"></div>

                <div class="border-y border-slate-100 px-6 py-5 space-y-5">
                    <div>
                        <label for="inventoryTransferSelectedRoomId" class="mb-2 block text-sm font-medium text-slate-700">Destination room <span class="text-red-500">*</span></label>
                        <select id="inventoryTransferSelectedRoomId" name="room_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">Select classroom / lab</option>
                            @foreach (($deployRooms ?? collect()) as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="inventoryTransferSelectedRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferSelectedRemarks" name="remarks" rows="3" placeholder="e.g. 5 mice for Room 204" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferSelectedModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Transfer selected</button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="inventoryTransferToModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="move" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer to room</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy this stock item to a classroom or lab. It lands in Holding until placed on the floor.
                    </p>
                </div>
                <button type="button" onclick="closeInventoryTransferToModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/equipment/transfer" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryTransferEquipmentId" value="" />

                <div class="border-y border-slate-100 px-6 py-5 space-y-5">
                    <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/60">
                        <div class="flex items-center justify-between gap-6 px-4 py-3.5">
                            <span class="shrink-0 text-sm text-slate-500">Equipment</span>
                            <span id="inventoryTransferEquipmentName" class="min-w-0 truncate text-right text-sm font-semibold text-slate-950"></span>
                        </div>
                        <div class="flex items-center justify-between gap-6 px-4 py-3.5">
                            <span class="shrink-0 text-sm text-slate-500">Asset tag</span>
                            <span id="inventoryTransferAssetTag" class="min-w-0 truncate text-right text-sm font-medium text-slate-800"></span>
                        </div>
                        <div class="flex items-center justify-between gap-6 px-4 py-3.5">
                            <span class="shrink-0 text-sm text-slate-500">Current room</span>
                            <span id="inventoryTransferCurrentRoom" class="min-w-0 truncate text-right text-sm font-medium text-slate-800"></span>
                        </div>
                    </div>

                    <div>
                        <label for="inventoryTransferRoomId" class="mb-2 block text-sm font-medium text-slate-700">Destination room <span class="text-red-500">*</span></label>
                        <select id="inventoryTransferRoomId" name="room_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">Select classroom / lab</option>
                            @foreach (($deployRooms ?? collect()) as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="inventoryTransferRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferRemarks" name="remarks" rows="3" placeholder="e.g. Deployed for Room 204 use" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferToModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="inventoryTransferAllModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                        <i data-lucide="boxes" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Transfer all stock</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Deploy all
                        <span
                            id="inventoryTransferAllCount"
                            data-count="{{ count($stockTransferIds ?? []) }}"
                            class="font-semibold text-slate-800"
                        >{{ number_format(count($stockTransferIds ?? [])) }}</span>
                        filtered storage item(s) to one classroom or lab.
                    </p>
                </div>
                <button type="button" onclick="closeInventoryTransferAllModal()" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/equipment/transfer-batch" method="POST">
                @csrf
                @foreach (($stockTransferIds ?? []) as $transferId)
                    <input type="hidden" name="equipment_ids[]" value="{{ $transferId }}" />
                @endforeach

                <div class="border-y border-slate-100 px-6 py-5 space-y-5">
                    <div>
                        <label for="inventoryTransferAllRoomId" class="mb-2 block text-sm font-medium text-slate-700">Destination room <span class="text-red-500">*</span></label>
                        <select id="inventoryTransferAllRoomId" name="room_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">Select classroom / lab</option>
                            @foreach (($deployRooms ?? collect()) as $room)
                                <option value="{{ $room->room_id }}">{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="inventoryTransferAllRemarks" class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
                        <textarea id="inventoryTransferAllRemarks" name="remarks" rows="3" placeholder="e.g. Deployed filtered stock to Computer Laboratory 1" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeInventoryTransferAllModal()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800">Transfer all</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div
        id="stockPendingModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
        onclick="if (event.target === this) closeStockPendingModal()"
    >
        <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex shrink-0 items-start justify-between gap-6 px-6 pb-4 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-[#0025cc]/10 text-[#0025cc]">
                        <i data-lucide="package-plus" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Stock all pending</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Import every completed RR line that still needs inventory into the stockroom as Bulk stock (linked to RR / PO).
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeStockPendingModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-4">
                <p id="stockPendingLoading" class="py-6 text-center text-sm text-slate-400">Loading pending lines…</p>
                <p id="stockPendingEmpty" class="hidden py-6 text-center text-sm text-slate-400">No pending RR lines to stock.</p>
                <p id="stockPendingError" class="mb-3 hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"></p>
                <ul id="stockPendingList" class="hidden max-h-64 space-y-2 overflow-y-auto"></ul>
                <div id="stockPendingMeta" class="mt-3 hidden text-xs text-slate-500">
                    Destination: <span class="font-semibold text-slate-700">{{ optional(($storageRooms ?? collect())->first())->room_name ?? 'Stock room' }}</span>
                    · Tracking: <span class="font-semibold text-slate-700">Bulk</span>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 px-6 py-4">
                <button
                    type="button"
                    onclick="closeStockPendingModal()"
                    class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    id="stockPendingConfirmBtn"
                    onclick="confirmStockPending()"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-5 text-sm font-medium text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled
                >
                    <i data-lucide="check" class="h-4 w-4"></i>
                    Confirm stock
                </button>
            </div>
        </div>
    </div>

    <div
        id="inventoryDisposeModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <i data-lucide="archive-x" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Dispose equipment
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Move this item from inventory into the Disposal module. This is permanent until restored from Disposal.
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeInventoryDisposeModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form action="/maintenance/disposal/store" method="POST">
                @csrf
                <input type="hidden" name="equipment_id" id="inventoryDisposeEquipmentId" value="" />

                <div class="border-y border-slate-100 px-6 py-5">
                    <div class="space-y-5">
                        <div>
                            <p class="mb-1 text-sm font-medium text-slate-700">Equipment</p>
                            <p id="inventoryDisposeEquipmentName" class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900"></p>
                        </div>

                        <div>
                            <label for="inventoryDisposeReason" class="mb-2 block text-sm font-medium text-slate-700">
                                Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="inventoryDisposeReason"
                                name="reason"
                                rows="3"
                                required
                                placeholder="Why is this equipment being disposed?"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            ></textarea>
                        </div>

                        <div>
                            <label for="inventoryDisposeMethod" class="mb-2 block text-sm font-medium text-slate-700">
                                Method
                            </label>
                            <select
                                id="inventoryDisposeMethod"
                                name="method"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                                <option value="">Select method (optional)</option>
                                <option value="Scrap">Scrap</option>
                                <option value="Donate">Donate</option>
                                <option value="Sell / Auction">Sell / Auction</option>
                                <option value="Return to supplier">Return to supplier</option>
                                <option value="Destroy">Destroy</option>
                            </select>
                        </div>

                        <div>
                            <label for="inventoryDisposeResidual" class="mb-2 block text-sm font-medium text-slate-700">
                                Residual value (₱)
                            </label>
                            <input
                                id="inventoryDisposeResidual"
                                name="residual_value"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="Optional salvage / residual amount"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            />
                        </div>

                        <div>
                            <label for="inventoryDisposeLocation" class="mb-2 block text-sm font-medium text-slate-700">
                                Location
                            </label>
                            <input
                                id="inventoryDisposeLocation"
                                name="location"
                                type="text"
                                placeholder="Area / room"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button
                        type="button"
                        onclick="closeInventoryDisposeModal()"
                        class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="h-10 rounded-xl bg-rose-600 px-5 text-sm font-medium text-white transition hover:bg-rose-700"
                    >
                        Dispose
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('layouts.partials.equipment-layout-icons')
    @include('layouts.partials.equipment-asset-tag')
    @include('layouts.partials.equipment-category-detect')
    @include('layouts.partials.equipment-photo-viewer')

    <style>
        .eq-modal-scroll {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 transparent;
        }

        .eq-modal-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .eq-modal-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .eq-modal-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .eq-modal-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .monitoring-scroll {
            scrollbar-gutter: stable;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .monitoring-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .monitoring-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .monitoring-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
    </style>

@endsection
