@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', \App\Support\CanvassWorkflow::displayNumber($canvass))
@section('page-subtitle', 'Canvass quotation comparison & approval summary.')

@section('content')
@php
    $routePrefix = $pp ?? 'purchaser';
    $badge = \App\Support\CanvassWorkflow::statusBadge((string) $canvass->canvass_status, $canvass->linked_ris_status ?? null);
    $editable = \App\Support\CanvassWorkflow::canEdit($canvass);
    $canCreateRis = \App\Support\CanvassWorkflow::canConvertToRis($canvass);
    $hasLinkedRis = ! empty($linkedRis);

    $risPreviewRows = [];
    if (! $hasLinkedRis) {
        foreach ($items as $it) {
            $itemName = (string) ($it->canvass_item_name ?: ($it->catalog_item_name ?? 'Untitled item'));
            $reqQty = max(1, (int) ($it->canvass_item_quantity ?? 1));
            $itemUom = (string) ($it->uom_name ?? 'pcs');
            $requestedText = (string) $reqQty;

            $rawOffers = [];
            foreach (($it->suppliers ?? []) as $sup) {
                $supLabel = trim((string) ($sup->supplier_display_name ?? ''));
                foreach (($sup->offers ?? []) as $o) {
                    $priceNum = (float) ($o->offer_unit_price ?? 0);
                    $lineTotal = (float) ($o->computed_total ?? 0);
                    $rawOffers[] = [
                        'brand' => trim((string) ($o->brand_name ?? '')) ?: '—',
                        'unit' => (string) ($o->offer_uom_name ?: $itemUom),
                        'supplier' => $supLabel !== '' ? $supLabel : '—',
                        'supplierKey' => mb_strtolower($supLabel),
                        'unitCost' => $priceNum > 0 ? ('₱'.number_format($priceNum, 2)) : '—',
                        'amount' => $lineTotal > 0 ? ('₱'.number_format($lineTotal, 2)) : '—',
                    ];
                }
            }

            if ($rawOffers === []) {
                $risPreviewRows[] = [
                    'itemName' => $itemName,
                    'brand' => '—',
                    'unit' => $itemUom,
                    'supplier' => '—',
                    'requested' => $requestedText,
                    'unitCost' => '—',
                    'amount' => '—',
                    'showItem' => true,
                    'itemSpan' => 1,
                    'showSupplier' => true,
                    'supplierSpan' => 1,
                ];
                continue;
            }

            $offerCount = count($rawOffers);
            foreach ($rawOffers as $oIdx => $of) {
                $prev = $oIdx > 0 ? $rawOffers[$oIdx - 1] : null;
                $sameSup = $prev && $of['supplierKey'] !== '' && $of['supplierKey'] === $prev['supplierKey'];
                $supplierSpan = 1;
                if (! $sameSup && $of['supplierKey'] !== '') {
                    for ($j = $oIdx + 1; $j < $offerCount; $j++) {
                        if ($rawOffers[$j]['supplierKey'] !== $of['supplierKey']) {
                            break;
                        }
                        $supplierSpan++;
                    }
                }
                $risPreviewRows[] = [
                    'itemName' => $itemName,
                    'brand' => $of['brand'],
                    'unit' => $of['unit'],
                    'supplier' => $of['supplier'],
                    'requested' => $requestedText,
                    'unitCost' => $of['unitCost'],
                    'amount' => $of['amount'],
                    'showItem' => $oIdx === 0,
                    'itemSpan' => $offerCount,
                    'showSupplier' => ! $sameSup,
                    'supplierSpan' => $supplierSpan,
                ];
            }
        }
    }
    $risPreviewDate = $canvass->canvass_submitted_at
        ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('m/d/Y')
        : now()->format('m/d/Y');
@endphp

@include('partials.ris-purchaser-sheet-styles')

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }
    #purchaser-print-mount {
        width: 100% !important;
        max-width: none !important;
    }
    #purchaser-print-mount .canvass-print-quotations-bundle {
        width: 100% !important;
        padding: 4mm 6mm !important;
        background: #ffffff !important;
    }
    #purchaser-print-mount .canvass-print-card {
        box-shadow: none !important;
        break-inside: avoid;
        margin-bottom: 12px !important;
    }
    #purchaser-print-mount .canvass-ris-print-target,
    #purchaser-print-mount .canvass-ris-print-target .ris-original-form,
    #purchaser-print-mount .canvass-ris-print-target .ris-document {
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        zoom: 1 !important;
        transform: none !important;
        margin: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    #purchaser-print-mount .ris-items-table tfoot {
        display: table-row-group !important;
    }
    #purchaser-print-mount .no-print {
        display: none !important;
    }
}
</style>

<div
    id="canvassPrintRoot"
    class="mx-auto max-w-5xl space-y-5 pb-10"
    x-data="{
        activeTab: 'quotations',
        printActiveTab() {
            const existing = document.getElementById('purchaser-print-mount');
            if (existing) existing.remove();

            const mount = document.createElement('div');
            mount.id = 'purchaser-print-mount';
            mount.setAttribute('aria-hidden', 'true');
            mount.className = 'purchaser-print-mount';

            if (this.activeTab === 'ris') {
                const wrap = document.getElementById('canvass-ris-print-sheet');
                if (!wrap) { window.print(); return; }
                const sheetEl = wrap.querySelector('.ris-original-form, .ris-document') || wrap;
                const holder = document.createElement('div');
                holder.className = 'purchaser-print-sheet canvass-ris-print-target';
                const rClone = sheetEl.cloneNode(true);
                rClone.removeAttribute('id');
                holder.appendChild(rClone);
                mount.appendChild(holder);
            } else {
                const summary = document.getElementById('canvass-summary-card');
                const quotes = document.getElementById('canvass-quotations-print-sheet');
                if (!quotes) { window.print(); return; }
                const bundle = document.createElement('div');
                bundle.className = 'purchaser-print-sheet canvass-print-quotations-bundle space-y-4';
                if (summary) {
                    const sClone = summary.cloneNode(true);
                    sClone.removeAttribute('id');
                    bundle.appendChild(sClone);
                }
                const qClone = quotes.cloneNode(true);
                qClone.removeAttribute('id');
                qClone.style.display = 'block';
                bundle.appendChild(qClone);
                mount.appendChild(bundle);
            }

            document.body.appendChild(mount);

            let cleaned = false;
            const cleanup = () => {
                if (cleaned) return;
                cleaned = true;
                if (mount.parentNode) mount.parentNode.removeChild(mount);
                window.removeEventListener('afterprint', cleanup);
            };
            window.addEventListener('afterprint', cleanup);
            window.print();
            window.setTimeout(cleanup, 1500);
        }
    }"
>
    {{-- Header Actions --}}
    <div class="no-print flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route($routePrefix.'.canvass.index') }}"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
            <span>Back to Canvasses</span>
        </a>

        <div class="flex flex-wrap items-center gap-2">
            @if ($editable)
                <a
                    href="{{ route($routePrefix.'.canvass.edit', $canvass->canvass_id) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-blue-300 bg-white px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                >
                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                    <span>Edit Canvass</span>
                </a>
            @endif

            @if ($canCreateRis)
                <a
                    href="{{ route($routePrefix.'.ris.index', ['from_canvass' => $canvass->canvass_id]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
                    style="background:#10b981;color:#ffffff;"
                >
                    <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                    <span>Create RIS from Canvass</span>
                </a>
            @elseif (! empty($canvass->canvass_ris_id))
                <a
                    href="{{ route($routePrefix.'.ris.index', ['view_ris' => $canvass->canvass_ris_id]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-90"
                    style="background:#0025cc;color:#ffffff;"
                >
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                    <span>Open Linked RIS {{ $canvass->linked_ris_number ?: '#'.$canvass->canvass_ris_id }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Summary Card --}}
    <div id="canvass-summary-card" class="canvass-summary-card canvass-print-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-lg font-bold text-slate-900">{{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}</h2>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                        style="{{ $badge['style'] }}"
                    >
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $badge['dot'] }};"></span>
                        <span>{{ $badge['label'] }}</span>
                    </span>
                    @if ($canvass->canvass_urgency === 'Urgent')
                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Urgent</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    @if (! empty($canvass->canvass_request_no))
                        Request No.: <span class="font-semibold text-blue-700">{{ $canvass->canvass_request_no }}</span> ·
                    @endif
                    Submitted: {{ $canvass->canvass_submitted_at ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('M d, Y h:i A') : 'Draft' }}
                    @if (! empty($canvass->reviewer_name))
                        · Reviewed by {{ $canvass->reviewer_name }}
                    @endif
                </p>
                @if (! empty($canvass->canvass_purpose))
                    <p class="mt-2 text-sm text-slate-700"><span class="font-semibold">Purpose:</span> {{ $canvass->canvass_purpose }}</p>
                @endif
            </div>

            <div class="flex gap-4 text-right">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Lowest Est. Total</p>
                    <p class="text-lg font-bold text-slate-900">₱{{ number_format((float) $lowestTotalSum, 2) }}</p>
                </div>
                @if ($approvedTotalSum > 0)
                    <div class="border-l border-slate-200 pl-4">
                        <p class="text-[11px] font-semibold uppercase text-emerald-600">Approved Total</p>
                        <p class="text-lg font-bold text-emerald-700">₱{{ number_format((float) $approvedTotalSum, 2) }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if (! empty($canvass->canvass_review_note))
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                <span class="font-semibold">School Administrator review note:</span> {{ $canvass->canvass_review_note }}
            </div>
        @endif
    </div>

    {{-- View Switcher Tabs: Supplier Quotations | Requisition Slip --}}
    <div class="no-print flex flex-wrap items-center justify-between gap-3 border-b border-slate-200">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="activeTab = 'quotations'"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-bold transition"
                :style="activeTab === 'quotations'
                    ? 'border-color:#0025cc;color:#0025cc;'
                    : 'border-color:transparent;color:#64748b;'"
            >
                <span>Supplier Quotations</span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:#f1f5f9;color:#475569;">{{ $items->count() }}</span>
            </button>
            <button
                type="button"
                @click="activeTab = 'ris'"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-xs font-bold transition"
                :style="activeTab === 'ris'
                    ? 'border-color:#0025cc;color:#0025cc;'
                    : 'border-color:transparent;color:#64748b;'"
            >
                <span>Requisition Slip</span>
                @if ($hasLinkedRis)
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:#ecfdf5;color:#059669;">
                        {{ $canvass->linked_ris_number ?: ('RIS #'.$canvass->canvass_ris_id) }}
                    </span>
                @else
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:#eff6ff;color:#1d4ed8;">Preview</span>
                @endif
            </button>
        </div>

        <button
            type="button"
            @click="printActiveTab()"
            class="mb-1.5 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
        >
            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
            <span x-text="activeTab === 'ris' ? 'Print Requisition Slip' : 'Print Quotations'"></span>
        </button>
    </div>

    {{-- TAB 1: Per-Item Quotation Comparison + Attachments --}}
    <div id="canvass-quotations-print-sheet" x-show="activeTab === 'quotations'" class="canvass-quotations-tab space-y-5">
    @foreach ($items as $idx => $item)
        <div class="canvass-print-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold uppercase text-blue-600">Item {{ $idx + 1 }}</span>
                        @if (! empty($item->canvass_item_is_sole_source))
                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" style="background:#fffbeb;border-color:#fde68a;color:#b45309;">
                                Sole Source
                            </span>
                        @endif
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ $item->canvass_item_name ?: $item->catalog_item_name }}
                        <span class="font-normal text-slate-500">· {{ $item->canvass_item_quantity }} {{ $item->uom_name ?: 'unit(s)' }}</span>
                    </h3>
                </div>
                <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    style="{{ $item->canvass_item_status === 'Approved' ? 'background:#dcfce7;color:#166534;' : ($item->canvass_item_status === 'Rejected' ? 'background:#fee2e2;color:#991b1b;' : ($item->canvass_item_status === 'Returned' ? 'background:#fef3c7;color:#92400e;' : 'background:#e2e8f0;color:#334155;')) }}"
                >
                    {{ $item->canvass_item_status }}
                </span>
            </div>

            @if (! empty($item->canvass_item_is_sole_source) && ! empty($item->canvass_item_sole_source_reason))
                <div class="border-b px-5 py-2.5 text-xs" style="border-color:#fde68a;background:#fffbeb;color:#92400e;">
                    <span class="font-bold">Sole Source justification:</span> {{ $item->canvass_item_sole_source_reason }}
                </div>
            @endif

            @if (! empty($item->canvass_item_review_note))
                <div class="border-b border-amber-100 bg-amber-50/70 px-5 py-2.5 text-xs text-amber-900">
                    <span class="font-semibold">Review note:</span> {{ $item->canvass_item_review_note }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-white text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5">Supplier</th>
                            <th class="px-4 py-2.5">Brand</th>
                            <th class="px-4 py-2.5">Sold As</th>
                            <th class="px-4 py-2.5 text-right">Offer Price</th>
                            <th class="px-4 py-2.5 text-center">Offer Units</th>
                            <th class="px-5 py-2.5 text-right">Estimated Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item->comparison_offers as $offer)
                            <tr class="{{ $offer->is_selected ? 'bg-emerald-50/80' : ($offer->is_lowest ? 'bg-blue-50/40' : '') }}">
                                <td class="px-5 py-3 font-medium text-slate-900">
                                    {{ $offer->supplier_display_name }}
                                    @if ($offer->supplier_is_blacklisted)
                                        <span class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700">Blacklisted</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-800">{{ $offer->brand_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $offer->offer_uom_name ?: 'Unit' }}
                                    @if ((float) $offer->offer_units_per_pack != 1.0)
                                        <span class="text-slate-400">({{ rtrim(rtrim(number_format((float) $offer->offer_units_per_pack, 2), '0'), '.') }}/{{ $offer->offer_uom_name }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-800">₱{{ number_format((float) $offer->offer_unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">{{ $offer->computed_offer_units }}</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">
                                    ₱{{ number_format((float) $offer->computed_total, 2) }}
                                    @if ($offer->is_selected)
                                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:#10b981;color:#ffffff;">Approved</span>
                                    @elseif ($offer->is_lowest)
                                        <span class="ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:#eff6ff;color:#1d4ed8;">Lowest</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- Attachments & Purchaser Signature --}}
    <div class="no-print grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Supporting Attachments</h3>
            @if ($attachments->isEmpty())
                <p class="mt-2 text-xs text-slate-400">No files attached.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($attachments as $att)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                            <a href="{{ route($routePrefix.'.canvass.attachments.download', $att->canvass_attachment_id) }}" class="truncate font-semibold text-blue-700 hover:underline">
                                {{ $att->canvass_attachment_original_name }}
                            </a>
                            <span class="ml-2 shrink-0 text-slate-400">{{ number_format(($att->canvass_attachment_size ?? 0) / 1024, 1) }} KB</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Purchaser Signature</h3>
            @if (! empty($canvass->canvass_purchaser_signature))
                <div class="mt-3 flex flex-col items-center rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Purchaser signature" class="max-h-20 object-contain">
                    <p class="mt-2 text-xs font-semibold text-slate-700">{{ $canvass->owner_name ?: 'Purchaser' }}</p>
                </div>
            @else
                <p class="mt-2 text-xs text-slate-400">No signature attached yet.</p>
            @endif
        </div>
    </div>
    </div>

    {{-- TAB 2: Requisition Slip (Official Linked RIS or Formal Preview) --}}
    <div x-show="activeTab === 'ris'" x-cloak class="canvass-ris-tab space-y-4">
        @if ($hasLinkedRis)
            <div class="no-print flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3 text-xs" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;">
                <span class="font-medium">
                    Official Requisition Slip generated from School Administrator approval ({{ $canvass->linked_ris_number ?: ('RIS #'.$canvass->canvass_ris_id) }}).
                </span>
                <a
                    href="{{ route($routePrefix.'.ris.index', ['view_ris' => $canvass->canvass_ris_id]) }}"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 font-semibold text-white transition hover:opacity-90"
                    style="background:#0025cc;color:#ffffff;"
                >
                    <span>Open in RIS Module</span>
                </a>
            </div>
            <div id="canvass-ris-print-sheet" class="canvass-ris-sheet-wrap ris-print-sheet overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-6 shadow-sm">
                @include('partials.ris-document-paper-styles', [
                    'ris' => $linkedRis,
                    'risItems' => $linkedRisItems,
                    'presidentName' => $canvass->reviewer_name ?: 'School Administrator',
                    'isScreenPreview' => true,
                ])
            </div>
        @else
            <div class="no-print rounded-xl border px-4 py-3 text-xs font-medium" style="border-color:#bfdbfe;background:#eff6ff;color:#1e40af;">
                <strong>Requisition Slip Preview:</strong> The official RIS will be generated once the School Administrator selects the winning supplier(s) &amp; brand(s) and approves this canvass.
            </div>
            <div id="canvass-ris-print-sheet" class="canvass-ris-sheet-wrap ris-print-sheet overflow-x-auto rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-6 shadow-sm">
                <div class="ris-original-form mx-auto bg-white shadow-sm">
                    <div class="ris-document-header">
                        <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                        <div class="ris-document-title">REQUISITION SLIP</div>
                        <div class="ris-number-area">
                            <span class="ris-number-label">No.</span>
                            <span class="ris-number-line ris-value-line">&nbsp;</span>
                        </div>
                    </div>

                    <table class="ris-items-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="ris-item-column">ITEM</th>
                                <th rowspan="2" class="ris-brand-column">BRAND</th>
                                <th rowspan="2" class="ris-unit-column">UNIT</th>
                                <th rowspan="2" class="ris-supplier-column">SUPPLIER</th>
                                <th colspan="2" class="ris-quantity-header">QUANTITY</th>
                                <th rowspan="2" class="ris-unit-cost-column">UNIT COST</th>
                                <th rowspan="2" class="ris-amount-column">AMOUNT</th>
                            </tr>
                            <tr>
                                <th class="ris-requested-column">REQUESTED</th>
                                <th class="ris-issued-column">ISSUED</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($risPreviewRows as $row)
                                <tr>
                                    @if ($row['showItem'])
                                        <td rowspan="{{ $row['itemSpan'] }}">{{ $row['itemName'] }}</td>
                                    @endif
                                    <td class="text-center">{{ $row['brand'] }}</td>
                                    <td class="text-center">{{ $row['unit'] }}</td>
                                    @if ($row['showSupplier'])
                                        <td rowspan="{{ $row['supplierSpan'] }}">{{ $row['supplier'] }}</td>
                                    @endif
                                    @if ($row['showItem'])
                                        <td rowspan="{{ $row['itemSpan'] }}" class="text-center">{{ $row['requested'] }}</td>
                                        <td rowspan="{{ $row['itemSpan'] }}" class="text-center">&nbsp;</td>
                                    @endif
                                    <td class="text-right">{{ $row['unitCost'] }}</td>
                                    <td class="text-right">{{ $row['amount'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="ris-total-label">TOTAL AMOUNT</td>
                                <td class="ris-total-value">₱{{ number_format((float) $lowestTotalSum, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="ris-purpose-area">
                        <div class="ris-purpose-lined-wrap">
                            <div class="ris-purpose-label">PURPOSE</div>
                            <div class="ris-purpose-input">{{ trim((string) ($canvass->canvass_purpose ?? '')) ?: "\u{00A0}" }}</div>
                        </div>
                    </div>

                    <div class="ris-signatures">
                        <div class="ris-signature-column">
                            <div class="ris-signature-label">Requested by:</div>
                            <div class="ris-signature-line ris-value-line relative flex items-end justify-center">
                                @if (! empty($canvass->canvass_purchaser_signature))
                                    <img
                                        src="{{ $canvass->canvass_purchaser_signature }}"
                                        alt="Requested by signature"
                                        class="pointer-events-none absolute -top-4 left-1/2 max-h-12 -translate-x-1/2 object-contain"
                                    >
                                @endif
                                <span class="relative z-10 text-xs font-medium text-slate-900">{{ $canvass->owner_name ?: 'Purchaser' }}</span>
                            </div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line ris-value-line">{{ $risPreviewDate }}</div>
                        </div>
                        <div class="ris-signature-column">
                            <div class="ris-signature-label">Approved by:</div>
                            <div class="ris-signature-line ris-value-line">&nbsp;</div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line ris-value-line">&nbsp;</div>
                        </div>
                        <div class="ris-signature-column">
                            <div class="ris-signature-label">Issued by:</div>
                            <div class="ris-signature-line ris-value-line">&nbsp;</div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line ris-value-line">&nbsp;</div>
                        </div>
                        <div class="ris-signature-column">
                            <div class="ris-signature-label">Received by:</div>
                            <div class="ris-signature-line ris-value-line">&nbsp;</div>
                            <div class="ris-date-label">Date:</div>
                            <div class="ris-date-line ris-value-line">&nbsp;</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
