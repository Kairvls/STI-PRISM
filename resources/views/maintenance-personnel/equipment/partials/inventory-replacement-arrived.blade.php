@php
    $replacementLines = $pendingReplacementLines ?? collect();
@endphp
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex flex-col gap-1 border-b border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <i data-lucide="package-check" class="h-4 w-4 text-violet-600"></i>
                Replacement arrived
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Delivered replacement stock waiting to be stocked as Individual units and linked to the equipment it replaces.
            </p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50/80">
                <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3">Article</th>
                    <th class="px-4 py-3">Receiving report</th>
                    <th class="px-4 py-3 text-right">Qty left</th>
                    <th class="px-4 py-3">Replaces</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($replacementLines as $line)
                    @php
                        $lineId = (int) ($line->receiving_report_item_id ?? 0);
                        $qtyLeft = (int) ($line->remaining_qty ?? $line->quantity ?? 0);
                        $replacesId = (int) ($line->replaces_equipment_id ?? 0);
                        $replacesLabel = trim((string) ($line->replaces_label ?? ''));
                        if ($replacesLabel === '') {
                            $replacesLabel = ! empty($line->is_back_order_replacement) ? 'Back order replacement' : 'Replacement delivery';
                        }
                    @endphp
                    <tr class="align-top hover:bg-slate-50/60">
                        <td class="px-5 py-3.5">
                            <p class="font-semibold text-slate-900">{{ $line->article ?? 'Item' }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                <span class="rounded-full bg-violet-100 px-2 py-0.5 font-semibold text-violet-800">Replacement</span>
                                @if (! empty($line->supplier_name))
                                    <span class="ml-1">{{ $line->supplier_name }}</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="font-mono text-[13px] font-semibold text-slate-800">{{ $line->rr_number ?? 'RR' }}</p>
                            @if (! empty($line->po_number))
                                <p class="mt-0.5 text-[11px] text-slate-400">PO {{ $line->po_number }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            <span class="font-semibold tabular-nums text-amber-700">{{ $qtyLeft }}</span>
                            <span class="block text-[11px] text-slate-400">Individual units</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-sm text-slate-800">{{ $replacesLabel }}</p>
                            @if ($replacesId > 0)
                                <p class="mt-0.5 font-mono text-[11px] text-slate-400">#{{ $replacesId }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @if ($canManage ?? false)
                                <button
                                    type="button"
                                    onclick="openStockAndLinkModal({{ $lineId }}, {{ $replacesId > 0 ? $replacesId : 'null' }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-2 text-[13px] font-semibold text-white transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    @if (!($defaultStorageRoomId ?? null) || $qtyLeft < 1) disabled @endif
                                >
                                    <i data-lucide="link-2" class="h-3.5 w-3.5"></i>
                                    Stock &amp; Link
                                </button>
                            @else
                                <span class="text-xs text-slate-400">Administrator stocks</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-2">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="package-check" class="h-5 w-5"></i>
                                </div>
                                <p class="text-sm font-medium text-slate-700">No replacement deliveries waiting</p>
                                <p class="text-xs text-slate-400">When a replacement RR is verified, it will appear here to stock as Individual units and link to the replaced equipment.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
