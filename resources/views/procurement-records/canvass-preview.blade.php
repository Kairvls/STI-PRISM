<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canvass — {{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
        }
    </style>
</head>
<body class="p-4 sm:p-5">
    @php
        $displayNo = \App\Support\CanvassWorkflow::displayNumber($canvass);
        $preparedItems = collect($items)->values()->map(function ($it, $idx) {
            $qty = max(1, (int) ($it->canvass_item_quantity ?? 1));
            $uom = (string) ($it->uom_name ?? 'pcs');
            $status = (string) ($it->canvass_item_status ?? 'Pending');
            $rawOffers = collect($it->comparison_offers ?? []);
            $minEffective = $rawOffers
                ->filter(fn ($o) => empty($o->supplier_is_blacklisted) && (float) ($o->effective_unit_cost ?? 0) > 0)
                ->min(fn ($o) => (float) $o->effective_unit_cost);

            $allocatedUnits = 0;
            $estimatedTotal = 0.0;
            $offers = $rawOffers->map(function ($o) use ($minEffective, &$allocatedUnits, &$estimatedTotal) {
                $checked = !empty($o->is_selected);
                $approvedQty = $checked ? max(0, (int) ($o->approved_offer_units ?? $o->computed_offer_units ?? 0)) : 0;
                $unitCost = (float) ($o->offer_unit_price ?? 0);
                $amount = $checked ? (float) ($o->approved_amount ?? ($approvedQty * $unitCost)) : 0.0;
                if ($checked) {
                    $allocatedUnits += $approvedQty;
                    $estimatedTotal += $amount;
                }
                $isLowest = $minEffective !== null
                    && empty($o->supplier_is_blacklisted)
                    && abs((float) ($o->effective_unit_cost ?? 0) - (float) $minEffective) < 0.0001;

                return [
                    'supplier' => (string) ($o->supplier_display_name ?? 'Supplier'),
                    'brand' => trim((string) ($o->brand_name ?? '')) ?: '—',
                    'unit' => (string) ($o->offer_uom_name ?? 'pcs'),
                    'approvedQty' => $approvedQty,
                    'unitCost' => $unitCost,
                    'amount' => $amount,
                    'checked' => $checked,
                    'isLowest' => $isLowest,
                    'blacklisted' => !empty($o->supplier_is_blacklisted),
                    'caution' => !empty($o->supplier_caution_risk),
                    'brandAvoid' => !empty($o->brand_avoid),
                ];
            })->values()->all();

            return [
                'index' => $idx,
                'name' => (string) ($it->canvass_item_name ?: ($it->catalog_item_name ?? 'Item')),
                'qty' => $qty,
                'uom' => $uom,
                'status' => $status,
                'reviewNote' => trim((string) ($it->canvass_item_review_note ?? '')),
                'allocatedUnits' => $allocatedUnits,
                'estimatedTotal' => round($estimatedTotal, 2),
                'offers' => $offers,
            ];
        })->all();

        $preparedAttachments = collect($attachments ?? [])->map(function ($att) {
            $bytes = (int) ($att->canvass_attachment_size ?? $att->canvass_attachment_size_bytes ?? 0);
            $sizeKb = $bytes > 0 ? number_format($bytes / 1024, 1) : null;

            return [
                'name' => (string) ($att->canvass_attachment_original_name ?? 'Attachment'),
                'sizeKb' => $sizeKb,
                'url' => route('procurement.canvass-attachments.preview', (int) $att->canvass_attachment_id),
            ];
        })->values()->all();
    @endphp

    <div class="mx-auto max-w-5xl space-y-4">
        @if(count($preparedItems) === 0)
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500 shadow-sm">
                No supplier quotations recorded for {{ $displayNo }}.
            </div>
        @else
            {{-- Supplier Quotations White Card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                {{-- Top Section Title --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6 pb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="h-5 w-1 shrink-0 rounded-full" style="background:#2563eb;"></span>
                        <h4 class="text-lg font-bold leading-tight text-slate-900">Supplier Quotations</h4>
                    </div>
                </div>

                {{-- Item Switcher Pills --}}
                <div class="flex flex-wrap items-center gap-2.5 px-6 pb-6" id="cvsItemPills">
                    @foreach($preparedItems as $idx => $it)
                        @php
                            $isApproved = $it['status'] === 'Approved';
                            $isRejected = $it['status'] === 'Rejected';
                            $isReturned = $it['status'] === 'Returned';
                            $badgeBg = $isApproved ? '#16a34a' : ($isRejected ? '#dc2626' : ($isReturned ? '#d97706' : ($idx === 0 ? '#dbeafe' : '#f1f5f9')));
                            $badgeColor = ($isApproved || $isRejected || $isReturned) ? '#ffffff' : ($idx === 0 ? '#2563eb' : '#475569');
                            $badgeSymbol = $isApproved ? '✓' : ($isRejected ? '✕' : ($isReturned ? '↺' : ($idx + 1)));
                        @endphp
                        <button
                            type="button"
                            data-cvs-pill="{{ $idx }}"
                            class="inline-flex items-center gap-2 rounded-md px-3.5 py-2 text-xs font-semibold transition"
                            style="{{ $idx === 0 ? 'border:1.5px solid #2563eb;background:#ffffff;color:#2563eb;' : 'border:1px solid #e2e8f0;background:#ffffff;color:#475569;' }}"
                        >
                            <span
                                class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                                style="background:{{ $badgeBg }};color:{{ $badgeColor }};"
                            >{{ $badgeSymbol }}</span>
                            <span class="max-w-[190px] truncate">Item {{ $idx + 1 }}: {{ $it['name'] }}</span>
                            <span
                                data-cvs-pill-qty
                                class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                style="{{ $idx === 0 ? 'background:#eff6ff;color:#2563eb;' : 'background:#f1f5f9;color:#475569;' }}"
                            >{{ $it['qty'] }} {{ $it['uom'] }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Per-Item Panels --}}
                @foreach($preparedItems as $idx => $it)
                    @php
                        $alloc = $it['allocatedUnits'];
                        $req = $it['qty'];
                        $diff = $req - $alloc;
                        $allocReady = $alloc > 0 && $alloc <= $req;
                        if ($alloc <= 0) {
                            $allocStyle = 'background:#f8fafc;border-color:#e2e8f0;color:#64748b;';
                            $allocNote = '(No allocation)';
                        } elseif ($alloc < $req) {
                            $allocStyle = 'background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;';
                            $allocNote = '(Partial approval · '.$diff.' '.$it['uom'].' below requested)';
                        } elseif ($alloc === $req) {
                            $allocStyle = 'background:#ecfdf5;border-color:#a7f3d0;color:#059669;';
                            $allocNote = '(Full allocation)';
                        } else {
                            $allocStyle = 'background:#fff7ed;border-color:#fed7aa;color:#c2410c;';
                            $allocNote = '(Exceeds requested by '.($alloc - $req).' '.$it['uom'].')';
                        }

                        $statusStyle = match ($it['status']) {
                            'Approved' => 'background:#ecfdf5;border-color:#a7f3d0;color:#059669;',
                            'Rejected' => 'background:#fef2f2;border-color:#fecaca;color:#dc2626;',
                            'Returned' => 'background:#fff7ed;border-color:#fed7aa;color:#c2410c;',
                            default => 'background:#f8fafc;border-color:#e2e8f0;color:#475569;',
                        };
                        $statusDot = match ($it['status']) {
                            'Approved' => '#10b981',
                            'Rejected' => '#dc2626',
                            'Returned' => '#ea580c',
                            default => '#64748b',
                        };
                    @endphp
                    <div data-cvs-panel="{{ $idx }}" style="{{ $idx === 0 ? '' : 'display:none;' }}">
                        {{-- Item Header Row --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-1 pb-4">
                            <div class="text-[15px] font-bold text-slate-900">
                                Item {{ $idx + 1 }}: {{ $it['name'] }}
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs">
                                <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                    <span>Total Requested:</span>
                                    <span class="font-semibold text-slate-900">{{ $it['qty'] }} Qty</span>
                                </span>
                                @if(in_array($it['status'], ['Approved', 'Rejected', 'Returned'], true))
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold" style="{{ $statusStyle }}">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background:{{ $statusDot }};"></span>
                                        <span>{{ $it['status'] }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Quotations Table --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-y border-slate-100 bg-[#f8fafc] text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                        <th class="py-3 pl-6 pr-3">Supplier</th>
                                        <th class="px-3 py-3">Brand</th>
                                        <th class="px-3 py-3">Unit</th>
                                        <th class="px-3 py-3 text-center">Approved Qty</th>
                                        <th class="px-3 py-3 text-right">Unit Cost</th>
                                        <th class="px-3 py-3 text-right">Total Amount</th>
                                        <th class="py-3 pl-3 pr-6 text-center">Approval</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($it['offers'] as $offer)
                                        <tr class="{{ $offer['checked'] ? 'bg-blue-50/20' : '' }}">
                                            <td class="py-4 pl-6 pr-3 align-middle">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-[13px] font-bold text-slate-900">{{ $offer['supplier'] }}</span>
                                                    @if($offer['isLowest'])
                                                        <span class="rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" style="background:#ecfdf5;border-color:#a7f3d0;color:#059669;">
                                                            Lowest Price
                                                        </span>
                                                    @endif
                                                    @if($offer['blacklisted'])
                                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" style="background:#ffe4e6;color:#be123c;">Blacklisted</span>
                                                    @elseif($offer['caution'])
                                                        <span class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" style="background:#fef3c7;color:#92400e;">Caution</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-4 align-middle text-[13px] font-medium text-slate-800">
                                                <span>{{ $offer['brand'] }}</span>
                                                @if($offer['brandAvoid'])
                                                    <span class="ml-1 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" style="background:#ffe4e6;color:#be123c;">Avoid</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-4 align-middle">
                                                <span class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600">
                                                    {{ $offer['unit'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-center align-middle">
                                                <div class="inline-flex items-center gap-1.5">
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border" style="background:#f8fafc;border-color:#e2e8f0;color:#cbd5e1;">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                                    </span>
                                                    <span class="inline-flex h-8 w-12 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-semibold tabular-nums text-slate-600">
                                                        {{ $offer['approvedQty'] }}
                                                    </span>
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border" style="background:#f8fafc;border-color:#e2e8f0;color:#cbd5e1;">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-3 py-4 text-right align-middle text-[13px] font-semibold tabular-nums text-slate-900">
                                                ₱{{ number_format($offer['unitCost'], 2) }}
                                            </td>
                                            <td class="px-3 py-4 text-right align-middle text-[13px] font-semibold tabular-nums {{ $offer['amount'] > 0 ? 'text-slate-900' : 'text-slate-400' }}">
                                                ₱{{ number_format($offer['amount'], 2) }}
                                            </td>
                                            <td class="py-4 pl-3 pr-6 text-center align-middle">
                                                <input
                                                    type="checkbox"
                                                    disabled
                                                    @checked($offer['checked'])
                                                    class="h-4 w-4 cursor-not-allowed rounded border-slate-300 text-blue-600 opacity-50"
                                                >
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-8 text-center text-xs text-slate-400">No supplier quotations for this item.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Allocation Pill + Estimated Total --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-[#f8fafc] px-6 py-3.5 text-xs">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium" style="{{ $allocStyle }}">
                                @if($allocReady)
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @else
                                    <span class="h-2 w-2 shrink-0 rounded-full" style="background:{{ $alloc <= 0 ? '#94a3b8' : '#ea580c' }};"></span>
                                @endif
                                <span>Allocated:</span>
                                <span class="font-bold">{{ $alloc }}</span>
                                <span>/ {{ $req }} {{ $it['uom'] }}</span>
                                <span class="ml-0.5 opacity-85">{{ $allocNote }}</span>
                            </span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-xs font-medium text-slate-600">Estimated total:</span>
                                <span class="text-sm font-bold tabular-nums text-slate-900">₱{{ number_format($it['estimatedTotal'], 2) }}</span>
                            </div>
                        </div>

                        @if($it['reviewNote'] !== '')
                            <div class="border-t border-red-100 px-6 py-2.5 text-xs" style="background:#fef2f2;color:#991b1b;">
                                <span class="font-semibold">Admin note:</span> {{ $it['reviewNote'] }}
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Pager Footer --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white px-6 py-4">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            id="cvsPrevBtn"
                            disabled
                            title="Previous item"
                            aria-label="Previous item"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-45"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                            </svg>
                        </button>
                        <span id="cvsPagerText" class="text-xs font-semibold text-slate-700">Item 1 of {{ count($preparedItems) }}</span>
                        <button
                            type="button"
                            id="cvsNextBtn"
                            @disabled(count($preparedItems) <= 1)
                            title="Next item"
                            aria-label="Next item"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition hover:opacity-90 disabled:cursor-not-allowed"
                            style="{{ count($preparedItems) <= 1 ? 'background:#e2e8f0;color:#94a3b8;' : 'background:#2563eb;color:#ffffff;' }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Attachments Strip --}}
            <div class="flex flex-wrap items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs shadow-sm">
                <span class="inline-flex items-center gap-1.5 font-bold text-slate-700">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                    </svg>
                    <span>Attachments:</span>
                </span>
                @forelse($preparedAttachments as $att)
                    <a
                        href="{{ $att['url'] }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 font-medium transition hover:bg-blue-100"
                        style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"
                    >
                        <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span class="max-w-[240px] truncate">{{ $att['name'] }}</span>
                        @if($att['sizeKb'])
                            <span class="text-[10px]" style="color:#3b82f6;">({{ $att['sizeKb'] }} KB)</span>
                        @endif
                    </a>
                @empty
                    <span class="text-slate-400">No files attached</span>
                @endforelse
            </div>
        @endif
    </div>

    @if(count($preparedItems) > 0)
        <script>
            (function () {
                var pills = Array.prototype.slice.call(document.querySelectorAll('[data-cvs-pill]'));
                var panels = Array.prototype.slice.call(document.querySelectorAll('[data-cvs-panel]'));
                var prevBtn = document.getElementById('cvsPrevBtn');
                var nextBtn = document.getElementById('cvsNextBtn');
                var pagerText = document.getElementById('cvsPagerText');
                var active = 0;

                function show(idx) {
                    if (idx < 0 || idx >= panels.length) return;
                    active = idx;
                    panels.forEach(function (panel, i) {
                        panel.style.display = i === active ? '' : 'none';
                    });
                    pills.forEach(function (pill, i) {
                        var isAct = i === active;
                        pill.style.border = isAct ? '1.5px solid #2563eb' : '1px solid #e2e8f0';
                        pill.style.color = isAct ? '#2563eb' : '#475569';
                        var qty = pill.querySelector('[data-cvs-pill-qty]');
                        if (qty) {
                            qty.style.background = isAct ? '#eff6ff' : '#f1f5f9';
                            qty.style.color = isAct ? '#2563eb' : '#475569';
                        }
                    });
                    if (pagerText) pagerText.textContent = 'Item ' + (active + 1) + ' of ' + panels.length;
                    if (prevBtn) prevBtn.disabled = active === 0;
                    if (nextBtn) {
                        var atEnd = active >= panels.length - 1;
                        nextBtn.disabled = atEnd;
                        nextBtn.style.background = atEnd ? '#e2e8f0' : '#2563eb';
                        nextBtn.style.color = atEnd ? '#94a3b8' : '#ffffff';
                    }
                }

                pills.forEach(function (pill, idx) {
                    pill.addEventListener('click', function () { show(idx); });
                });
                if (prevBtn) prevBtn.addEventListener('click', function () { show(active - 1); });
                if (nextBtn) nextBtn.addEventListener('click', function () { show(active + 1); });
            })();
        </script>
    @endif
</body>
</html>
