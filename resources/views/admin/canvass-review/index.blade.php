@extends('layouts.admin-layout')

@section('page-title', 'Canvass Review')
@section('page-subtitle', 'Review submitted supplier & brand quotations per item, allocate approved quantities, and authorize ATP & RIS.')

@section('content')
@php
    $portalPrefix = \App\Support\AdminPortal::prefix();
    $savedSigList = ($savedSignatures ?? collect())->map(fn ($s) => [
        'id' => (int) $s->user_signature_id,
        'label' => (string) ($s->user_signature_label ?: 'Saved signature'),
        'url' => (string) ($s->preview_url ?? ''),
    ])->values()->all();
@endphp

<div class="mx-auto max-w-6xl space-y-5 pb-12">
    {{-- Filter Tabs & Search --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex flex-wrap items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            @foreach ([
                'all' => ['label' => 'All Requests', 'count' => $counts['all']],
                'pending' => ['label' => 'Pending Review', 'count' => $counts['pending']],
                'approved' => ['label' => 'Approved', 'count' => $counts['approved']],
                'returned' => ['label' => 'Returned', 'count' => $counts['returned']],
            ] as $key => $meta)
                <a
                    href="{{ route($portalPrefix.'.canvass-review.index', ['filter' => $key]) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold transition {{ $filter === $key ? 'bg-[#0025cc] text-white' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                >
                    <span>{{ $meta['label'] }}</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route($portalPrefix.'.canvass-review.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search CAN no., purchaser..."
                class="w-56 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
            >
            <button type="submit" class="rounded-xl bg-[#0025cc] px-4 py-2 text-xs font-semibold text-white hover:bg-[#001db3]">Search</button>
        </form>
    </div>

    <h2 class="text-base font-semibold text-slate-800">Requests</h2>

    {{-- Expandable Request Cards (matches Images 1–4) --}}
    <div class="space-y-4">
        @forelse ($canvasses as $row)
            @php
                $cid = (int) $row->canvass_id;
                $ws = $workspaces[$cid] ?? null;
                $displayNo = \App\Support\CanvassWorkflow::displayNumber($row);
                $isInitiallyOpen = ($openCanvassId === $cid)
                    || ($openCanvassId === 0 && $loop->first && \App\Support\CanvassWorkflow::canReview($row));

                $wsBoot = [
                    'canvassId' => $cid,
                    'displayNo' => $displayNo,
                    'status' => (string) $row->canvass_status,
                    'submittedAt' => $row->canvass_submitted_at ? \Carbon\Carbon::parse($row->canvass_submitted_at)->format('Y-m-d H:i:s') : '—',
                    'purchaserSignature' => (string) ($row->canvass_purchaser_signature ?? ''),
                    'adminSignature' => (string) ($row->canvass_admin_signature ?? ($savedSigList[0]['url'] ?? '')),
                    'linkedRisNumber' => (string) ($row->linked_ris_number ?? ''),
                    'canAct' => (bool) $canAct,
                    'initiallyOpen' => (bool) $isInitiallyOpen,
                    'startReviewUrl' => $canAct ? route($portalPrefix.'.canvass-review.start-review', $cid) : '',
                    'approveItemBaseUrl' => $canAct ? url($portalPrefix.'/canvass-review/'.$cid.'/items') : '',
                    'printUrl' => route($portalPrefix.'.canvass-review.print', $cid),
                    'savedSignatures' => $savedSigList,
                    'attachments' => $ws ? $ws['attachments']->map(fn ($a) => [
                        'name' => (string) $a->canvass_attachment_original_name,
                        'sizeKb' => number_format(($a->canvass_attachment_size ?? 0) / 1024, 1),
                        'url' => route($portalPrefix.'.canvass-review.attachments.download', $a->canvass_attachment_id),
                    ])->values()->all() : [],
                    'items' => $ws ? $ws['items']->map(fn ($it) => [
                        'id' => (int) $it->canvass_item_id,
                        'name' => (string) ($it->canvass_item_name ?: $it->catalog_item_name),
                        'qty' => max(1, (int) $it->canvass_item_quantity),
                        'uom' => (string) ($it->uom_name ?: 'Pc'),
                        'status' => (string) $it->canvass_item_status,
                        'reviewNote' => (string) ($it->canvass_item_review_note ?? ''),
                        'offers' => $it->comparison_offers->map(fn ($o) => [
                            'id' => (int) $o->canvass_offer_id,
                            'brand' => (string) ($o->brand_name ?: '—'),
                            'unit' => (string) ($o->offer_uom_name ?: ($it->uom_name ?: 'Pc')),
                            'supplierId' => (int) $o->supplier_id,
                            'supplier' => (string) $o->supplier_display_name,
                            'blacklisted' => (bool) ($o->supplier_is_blacklisted ?? false),
                            'caution' => (string) ($o->supplier_caution_risk ?? ''),
                            'brandAvoid' => (bool) ($o->brand_avoid ?? false),
                            'pack' => (float) ($o->offer_units_per_pack ?? 1),
                            'unitCost' => (float) ($o->offer_unit_price ?? 0),
                            'checked' => (bool) $o->is_selected,
                            'approvedQty' => $o->is_selected ? (string) max(1, (int) ($o->approved_offer_units ?? $o->computed_offer_units)) : '',
                        ])->values()->all(),
                    ])->values()->all() : [],
                ];
            @endphp

            <div
                x-data="canvassReviewCard(@js($wsBoot))"
                x-init="init()"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                {{-- Card Header Bar (matches Image 1 & Image 4) --}}
                <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-[200px]">
                        <div class="text-sm font-bold text-slate-900" x-text="'Request ' + displayNo"></div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            Submitted <span x-text="submittedAt"></span> · <span x-text="items.length"></span> item(s)
                            <span x-show="linkedRisNumber" class="ml-1 font-semibold text-teal-700" x-text="'· RIS ' + linkedRisNumber"></span>
                        </div>
                    </div>

                    {{-- Center Status + Progress Bar --}}
                    <div class="flex min-w-[200px] flex-col items-center">
                        <span
                            class="inline-flex items-center rounded-full px-3 py-0.5 text-[11px] font-bold"
                            :style="statusBadgeStyle()"
                            x-text="status"
                        ></span>
                        <span class="mt-1 text-[11px] text-slate-500" x-text="approvedCount() + ' of ' + items.length + ' items approved'"></span>
                        <div class="mt-1 h-1.5 w-40 overflow-hidden rounded-full bg-slate-200">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :style="'width:' + progressPercent() + '%; background-color:' + (approvedCount() === items.length && items.length > 0 ? '#16a34a' : '#2563eb')"
                            ></div>
                        </div>
                    </div>

                    {{-- Right Controls --}}
                    <div class="flex items-center gap-2">
                        <span
                            x-show="status === 'Reviewing - Awaiting for approval'"
                            class="rounded-md bg-blue-600 px-2.5 py-1 text-[11px] font-semibold text-white"
                        >
                            Reviewing
                        </span>
                        <button
                            type="button"
                            @click="toggleOpen()"
                            class="rounded-lg border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            x-text="open ? 'Hide details' : 'View details'"
                        ></button>
                    </div>
                </div>

                {{-- Workspace Collapse Indicator --}}
                <div class="border-t border-slate-100 px-5 py-2">
                    <button
                        type="button"
                        @click="toggleOpen()"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:underline"
                    >
                        <span x-text="open ? '▾' : '▸'"></span>
                        <span>Request review workspace</span>
                    </button>
                </div>

                {{-- EXPANDED WORKSPACE --}}
                <div x-cloak x-show="open" class="space-y-5 border-t border-slate-100 bg-slate-50/40 p-5">
                    {{-- Feedback Banner --}}
                    <div x-show="feedbackMsg" class="rounded-xl border px-4 py-3 text-xs font-semibold" :style="feedbackOk ? 'border-color:#bbf7d0;background:#f0fdf4;color:#166534;' : 'border-color:#fecdd3;background:#fff1f2;color:#be123c;'" x-text="feedbackMsg"></div>

                    {{-- 1. PURCHASER ATTACHMENTS --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-xs font-semibold text-slate-700">Purchaser attachments</h4>
                        <template x-if="attachments.length === 0">
                            <p class="mt-1.5 text-xs text-slate-400">No files attached.</p>
                        </template>
                        <ul x-show="attachments.length > 0" class="mt-2 list-disc space-y-1 pl-5 text-xs">
                            <template x-for="(att, aIdx) in attachments" :key="aIdx">
                                <li>
                                    <a :href="att.url" class="font-medium text-blue-600 underline hover:text-blue-800" x-text="att.name"></a>
                                    <span class="text-slate-500" x-text="' (' + att.sizeKb + ' KB)'"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    {{-- 2. REVIEW AND APPROVE ITEM QUOTATIONS (matches Image 1) --}}
                    <template x-if="currentItem()">
                        <div class="rounded-xl border border-slate-200 bg-white p-5">
                            <div class="border-l-4 border-blue-600 pl-3">
                                <h4 class="text-sm font-bold text-slate-900">Review and approve item quotations</h4>
                                <p class="text-xs text-slate-500">Choose supplier offers and quantities for each item. The Authority to Purchase updates as you select offers.</p>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200 p-4">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-xs font-bold text-slate-900" x-text="'Supplier quotations · Item ' + (activeItemIdx + 1) + ': ' + currentItem().name"></div>
                                    <div class="text-xs text-slate-500">
                                        Requested: <span class="font-semibold text-slate-800" x-text="currentItem().qty + ' ' + currentItem().uom"></span>
                                        ·
                                        <span
                                            class="font-semibold"
                                            :style="currentItem().status === 'Approved' ? 'color:#15803d;' : (currentItem().status === 'Returned' ? 'color:#b45309;' : 'color:#64748b;')"
                                            x-text="currentItem().status"
                                        ></span>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-slate-50 text-left text-slate-800">
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Brand</th>
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Unit</th>
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Supplier</th>
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Pack size</th>
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Approved qty</th>
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Unit cost</th>
                                                <th class="border border-slate-200 px-3 py-2 font-bold">Amount</th>
                                                <th class="border border-slate-200 px-3 py-2 text-center font-bold">Approval</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="offer in currentItem().offers" :key="offer.id">
                                                <tr :class="offer.checked ? 'bg-blue-50/40' : ''">
                                                    <td class="border border-slate-200 px-3 py-2 font-medium text-slate-900">
                                                        <span x-text="offer.brand"></span>
                                                        <span x-show="offer.brandAvoid" class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-semibold" style="background:#ffe4e6;color:#be123c;">Avoid</span>
                                                    </td>
                                                    <td class="border border-slate-200 px-3 py-2 text-slate-700" x-text="offer.unit"></td>
                                                    <td class="border border-slate-200 px-3 py-2 text-slate-800">
                                                        <span x-text="offer.supplier"></span>
                                                        <span x-show="offer.blacklisted" class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-semibold" style="background:#ffe4e6;color:#be123c;">Blacklisted</span>
                                                        <span x-show="!offer.blacklisted && offer.caution" class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-semibold" style="background:#fef3c7;color:#92400e;">Caution</span>
                                                    </td>
                                                    <td class="border border-slate-200 px-3 py-2 text-slate-600" x-text="packLabel(offer, currentItem())"></td>
                                                    <td class="border border-slate-200 px-2.5 py-1.5">
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            x-model="offer.approvedQty"
                                                            :disabled="!canAct || !offer.checked || status === 'Approved' || status === 'Converted to RIS'"
                                                            placeholder="0"
                                                            class="w-28 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-900 outline-none focus:border-blue-500 disabled:bg-slate-100 disabled:text-slate-400"
                                                        >
                                                    </td>
                                                    <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="formatPeso(offer.unitCost)"></td>
                                                    <td class="border border-slate-200 px-3 py-2 font-semibold text-slate-900" x-text="formatPeso(offerAmount(offer))"></td>
                                                    <td class="border border-slate-200 px-3 py-2 text-center">
                                                        <input
                                                            type="checkbox"
                                                            x-model="offer.checked"
                                                            @change="onToggleOffer(currentItem(), offer)"
                                                            :disabled="!canAct || status === 'Approved' || status === 'Converted to RIS'"
                                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                        >
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                                    <span class="text-slate-500">Select offers and enter whole offer-unit quantities. Allocated quantities must cover the requested amount; pack sizes may cause an overage.</span>
                                    <span class="font-bold text-slate-900" x-text="'Allocated: ' + itemAllocatedUnits(currentItem()) + ' / ' + currentItem().qty + ' ' + currentItem().uom + ' · Estimated total: ' + formatPeso(itemEstimatedTotal(currentItem()))"></span>
                                </div>
                            </div>

                            {{-- Per-item Approve / Return Actions --}}
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <span class="text-xs text-slate-500" x-text="'Note: This request includes ' + items.length + ' item(s). Use Previous/Next to review each item; approvals are handled per item.'"></span>

                                <div x-show="canAct && status !== 'Approved' && status !== 'Converted to RIS'" class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="returnCurrentItem()"
                                        :disabled="busy"
                                        class="rounded-lg border border-amber-400 bg-white px-3.5 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-50 disabled:opacity-50"
                                    >
                                        Return item
                                    </button>
                                    <button
                                        type="button"
                                        @click="approveCurrentItem()"
                                        :disabled="busy"
                                        class="rounded-lg px-4 py-2 text-xs font-semibold text-white shadow-sm transition disabled:opacity-50"
                                        style="background-color: #198754;"
                                    >
                                        <span x-text="busy ? 'Saving...' : 'Approve'"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Previous / Next Item Pager --}}
                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                <button
                                    type="button"
                                    @click="if (activeItemIdx > 0) activeItemIdx--"
                                    :disabled="activeItemIdx === 0"
                                    class="rounded-lg border border-blue-500 bg-white px-3.5 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400"
                                >
                                    ← Previous item
                                </button>
                                <span class="text-xs font-medium text-slate-600" x-text="'Item ' + (activeItemIdx + 1) + ' of ' + items.length"></span>
                                <button
                                    type="button"
                                    @click="if (activeItemIdx < items.length - 1) activeItemIdx++"
                                    :disabled="activeItemIdx >= items.length - 1"
                                    class="rounded-lg bg-[#0d6efd] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                                >
                                    Next item →
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- 3. ADMIN AUTHORIZATION SIGNATURE (matches Image 2) --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h4 class="text-sm font-bold text-slate-900">Admin authorization signature</h4>
                        <p class="mt-0.5 text-xs text-slate-500">Draw your signature or attach a PNG/JPEG image. This signature is required to approve an item.</p>

                        <div x-show="canAct && savedSignatures.length > 0" class="mt-2.5 flex flex-wrap items-center gap-2">
                            <span class="text-[11px] font-semibold text-slate-500">Saved signatures:</span>
                            <template x-for="sig in savedSignatures" :key="sig.id">
                                <button
                                    type="button"
                                    @click="applySavedSig(sig.url)"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 hover:border-blue-400 hover:bg-blue-50"
                                >
                                    <img :src="sig.url" alt="" class="h-4 max-w-[50px] object-contain">
                                    <span x-text="sig.label"></span>
                                </button>
                            </template>
                        </div>

                        <div class="relative mt-3 overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-white">
                            <canvas
                                x-ref="adminSigCanvas"
                                width="860"
                                height="140"
                                @pointerdown="if (canAct) startSig($event)"
                                @pointermove="if (canAct) moveSig($event)"
                                @pointerup="endSig()"
                                @pointerleave="endSig()"
                                class="h-36 w-full cursor-crosshair touch-none"
                            ></canvas>
                            <div x-show="!adminSignature" class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-slate-400">
                                Draw your authorization signature here
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <div x-show="canAct" class="flex items-center gap-2">
                                <button type="button" @click="clearSig()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    Clear
                                </button>
                                <label class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    <span>Attach signature image</span>
                                    <input type="file" accept="image/png,image/jpeg,image/webp" @change="loadSigFile($event)" class="hidden">
                                </label>
                            </div>

                            <a
                                :href="printUrl"
                                target="_blank"
                                class="rounded-lg bg-[#0d6efd] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            >
                                Print Authority to Purchase and issue slip
                            </a>
                        </div>
                    </div>

                    {{-- 4. LIVE AUTHORITIES TO PURCHASE PREVIEW PER SUPPLIER (matches Image 3) --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="border-l-4 border-blue-600 pl-3">
                            <h4 class="text-sm font-bold text-slate-900">Authorities to Purchase</h4>
                            <p class="text-xs text-slate-500">Each supplier has a separate ATP with its selected brands and quantities.</p>
                        </div>

                        <template x-if="supplierAtpPages().length > 0">
                            <div class="mt-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <button
                                        type="button"
                                        @click="if (activeSupplierIdx > 0) activeSupplierIdx--"
                                        :disabled="activeSupplierIdx === 0"
                                        class="rounded-lg bg-[#0d6efd] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400"
                                    >
                                        ← Previous supplier
                                    </button>
                                    <span
                                        class="text-xs font-medium text-slate-600"
                                        x-text="'Supplier ' + (activeSupplierIdx + 1) + ' of ' + supplierAtpPages().length + ' · ' + activeSupplierPage().supplierName"
                                    ></span>
                                    <button
                                        type="button"
                                        @click="if (activeSupplierIdx < supplierAtpPages().length - 1) activeSupplierIdx++"
                                        :disabled="activeSupplierIdx >= supplierAtpPages().length - 1"
                                        class="rounded-lg bg-[#0d6efd] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400"
                                    >
                                        Next supplier →
                                    </button>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-white p-6">
                                    <div class="text-center">
                                        <div class="text-sm font-bold text-slate-900">STI COLLEGE - ORMOC, INC.</div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">AUTHORITY TO PURCHASE</div>
                                        <div class="mt-1 text-xs font-bold text-slate-800" x-text="'Supplier: ' + activeSupplierPage().supplierName"></div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap justify-between gap-2 text-xs">
                                        <div>
                                            <div><span class="font-bold">Request No.:</span> <span x-text="displayNo"></span></div>
                                            <div class="mt-0.5"><span class="font-bold">Date submitted:</span> <span x-text="submittedAt"></span></div>
                                        </div>
                                        <div><span class="font-bold">Status:</span> <span x-text="status"></span></div>
                                    </div>

                                    <div class="mt-3 overflow-x-auto">
                                        <table class="w-full border-collapse text-xs">
                                            <thead>
                                                <tr class="bg-slate-50 text-left text-slate-800">
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Item</th>
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Requested</th>
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Approved brand</th>
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Approved qty</th>
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Unit cost</th>
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Amount</th>
                                                    <th class="border border-slate-300 px-3 py-2 font-bold">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(line, lIdx) in activeSupplierPage().lines" :key="lIdx">
                                                    <tr>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-900" x-text="line.itemName"></td>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-700" x-text="line.requested"></td>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="line.brand"></td>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="line.approvedQtyLabel"></td>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="formatPeso(line.unitCost)"></td>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-900" x-text="line.amount > 0 ? formatPeso(line.amount) : '—'"></td>
                                                        <td class="border border-slate-200 px-3 py-2 text-slate-700" x-text="line.statusLabel"></td>
                                                    </tr>
                                                </template>
                                                <tr x-show="activeSupplierPage().lines.length === 0">
                                                    <td colspan="7" class="border border-slate-200 px-3 py-3 text-center text-slate-400">No offers selected for this supplier yet.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-6 grid grid-cols-1 gap-6 text-xs sm:grid-cols-3">
                                        <div>
                                            <div class="flex h-12 items-end justify-center border-b border-slate-500 pb-1">
                                                <img x-show="purchaserSignature" :src="purchaserSignature" alt="Requested by" class="max-h-11 object-contain">
                                            </div>
                                            <div class="mt-1 font-medium text-slate-700">Requested by</div>
                                            <div class="mt-1 text-slate-500">Date: ______________________</div>
                                        </div>
                                        <div>
                                            <div class="flex h-12 items-end justify-center border-b border-slate-500 pb-1">
                                                <img x-show="adminSignature" :src="adminSignature" alt="Authorized by" class="max-h-11 object-contain">
                                            </div>
                                            <div class="mt-1 font-medium text-slate-700">Authorized by</div>
                                            <div class="mt-1 text-slate-500">Date: ______________________</div>
                                        </div>
                                        <div>
                                            <div class="h-12 border-b border-slate-500"></div>
                                            <div class="mt-1 font-medium text-slate-700">Received by (Supplier)</div>
                                            <div class="mt-1 text-slate-500">Date: ______________________</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- 5. LIVE REQUISITION AND ISSUE SLIP PREVIEW (matches Image 4) --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="border-l-4 border-blue-600 pl-3">
                            <h4 class="text-sm font-bold text-slate-900">Requisition and Issue Slip</h4>
                            <p class="text-xs text-slate-500">The shared request slip includes every requested item and its signatories.</p>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-6">
                            <div class="text-center">
                                <div class="text-sm font-bold text-slate-900">STI COLLEGE - ORMOC, INC.</div>
                                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">REQUISITION AND ISSUE SLIP</div>
                            </div>

                            <div class="mt-4 flex flex-wrap justify-between gap-2 text-xs">
                                <div><span class="font-bold">Request No.:</span> <span x-text="displayNo"></span></div>
                                <div><span class="font-bold">Status:</span> <span x-text="status"></span></div>
                            </div>

                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-50 text-left text-slate-800">
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Requested item</th>
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Quantity</th>
                                            <th class="border border-slate-300 px-3 py-2 font-bold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="it in items" :key="it.id">
                                            <tr>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-900" x-text="it.name"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-700" x-text="it.qty + ' ' + it.uom"></td>
                                                <td class="border border-slate-200 px-3 py-2 text-slate-700" x-text="it.status"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-6 text-xs sm:grid-cols-3">
                                <div>
                                    <div class="flex h-12 items-end justify-center border-b border-slate-500 pb-1">
                                        <img x-show="purchaserSignature" :src="purchaserSignature" alt="Requested by" class="max-h-11 object-contain">
                                    </div>
                                    <div class="mt-1 font-medium text-slate-700">Requested by</div>
                                    <div class="mt-1 text-slate-500">Date: ______________________</div>
                                </div>
                                <div>
                                    <div class="flex h-12 items-end justify-center border-b border-slate-500 pb-1">
                                        <img x-show="adminSignature" :src="adminSignature" alt="Approved by" class="max-h-11 object-contain">
                                    </div>
                                    <div class="mt-1 font-medium text-slate-700">Approved by</div>
                                    <div class="mt-1 text-slate-500">Date: ______________________</div>
                                </div>
                                <div>
                                    <div class="flex h-12 items-end justify-center border-b border-slate-500 pb-1">
                                        <img x-show="adminSignature && approvedCount() === items.length && items.length > 0" :src="adminSignature" alt="Issued by" class="max-h-11 object-contain">
                                    </div>
                                    <div class="mt-1 font-medium text-slate-700">Issued by</div>
                                    <div class="mt-1 text-slate-500">Date: ______________________</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">
                No canvass requests found in this filter.
            </div>
        @endforelse
    </div>

    @if ($canvasses->hasPages())
        <div class="pt-2">
            {{ $canvasses->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function canvassReviewCard(boot) {
    return {
        canvassId: boot.canvassId,
        displayNo: boot.displayNo,
        status: boot.status,
        submittedAt: boot.submittedAt,
        purchaserSignature: boot.purchaserSignature || '',
        adminSignature: boot.adminSignature || '',
        linkedRisNumber: boot.linkedRisNumber || '',
        canAct: Boolean(boot.canAct),
        open: Boolean(boot.initiallyOpen),
        startReviewUrl: boot.startReviewUrl || '',
        approveItemBaseUrl: boot.approveItemBaseUrl || '',
        printUrl: boot.printUrl || '#',
        savedSignatures: boot.savedSignatures || [],
        attachments: boot.attachments || [],
        items: boot.items || [],
        activeItemIdx: 0,
        activeSupplierIdx: 0,
        busy: false,
        feedbackMsg: '',
        feedbackOk: true,
        drawing: false,

        init() {
            if (this.open) {
                this.ensureReviewStarted();
                this.$nextTick(() => {
                    if (this.adminSignature) this.drawSigImage(this.adminSignature);
                });
            }
        },

        toggleOpen() {
            this.open = !this.open;
            if (this.open) {
                this.ensureReviewStarted();
                this.$nextTick(() => {
                    if (this.adminSignature) this.drawSigImage(this.adminSignature);
                });
            }
        },

        async ensureReviewStarted() {
            if (!this.canAct || this.status !== 'Awaiting for review' || !this.startReviewUrl) return;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(this.startReviewUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    this.status = 'Reviewing - Awaiting for approval';
                }
            } catch (e) {}
        },

        currentItem() {
            return this.items[this.activeItemIdx] || null;
        },

        approvedCount() {
            return this.items.filter(it => it.status === 'Approved').length;
        },

        progressPercent() {
            if (!this.items.length) return 0;
            return Math.round((this.approvedCount() / this.items.length) * 100);
        },

        statusBadgeStyle() {
            if (this.status === 'Approved' || this.status === 'Converted to RIS') return 'background:#198754;color:#fff;';
            if (this.status === 'Reviewing - Awaiting for approval') return 'background:#0d6efd;color:#fff;';
            if (this.status === 'Returned') return 'background:#f59e0b;color:#fff;';
            return 'background:#fbbf24;color:#0f172a;';
        },

        formatPeso(val) {
            const n = Number(val || 0);
            return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        packLabel(offer, item) {
            const p = Number(offer.pack || 1);
            const pStr = Number.isInteger(p) ? String(p) : p.toFixed(2);
            return `${pStr} ${item.uom} per ${offer.unit}`;
        },

        onToggleOffer(item, offer) {
            if (offer.checked && (!offer.approvedQty || Number(offer.approvedQty) <= 0)) {
                const remaining = Math.max(1, item.qty - this.itemAllocatedUnits(item));
                const pack = Math.max(0.01, Number(offer.pack || 1));
                offer.approvedQty = String(Math.ceil(remaining / pack));
            }
            if (!offer.checked) {
                offer.approvedQty = '';
            }
        },

        offerAmount(offer) {
            if (!offer.checked) return 0;
            const qty = Math.max(0, parseInt(offer.approvedQty, 10) || 0);
            return qty * Number(offer.unitCost || 0);
        },

        itemAllocatedUnits(item) {
            return item.offers.reduce((sum, o) => {
                if (!o.checked) return sum;
                const qty = Math.max(0, parseInt(o.approvedQty, 10) || 0);
                return sum + (qty * Number(o.pack || 1));
            }, 0);
        },

        itemEstimatedTotal(item) {
            return item.offers.reduce((sum, o) => sum + this.offerAmount(o), 0);
        },

        supplierAtpPages() {
            const map = new Map();
            this.items.forEach(item => {
                item.offers.forEach(o => {
                    if (!map.has(o.supplierId)) {
                        map.set(o.supplierId, { supplierId: o.supplierId, supplierName: o.supplier, lines: [] });
                    }
                    if (o.checked) {
                        const qty = Math.max(0, parseInt(o.approvedQty, 10) || 0);
                        const amt = qty * Number(o.unitCost || 0);
                        map.get(o.supplierId).lines.push({
                            itemName: item.name,
                            requested: `${item.qty} ${item.uom}`,
                            brand: o.brand,
                            approvedQtyLabel: qty > 0 ? `${qty} ${o.unit}` : 'Quantity required',
                            unitCost: Number(o.unitCost || 0),
                            amount: amt,
                            statusLabel: item.status === 'Approved' ? 'Approved' : (qty > 0 ? 'Pending approval' : 'Quantity required'),
                        });
                    }
                });
            });
            const pages = Array.from(map.values());
            if (this.activeSupplierIdx >= pages.length) {
                this.activeSupplierIdx = Math.max(0, pages.length - 1);
            }
            return pages;
        },

        activeSupplierPage() {
            const pages = this.supplierAtpPages();
            return pages[this.activeSupplierIdx] || { supplierName: '—', lines: [] };
        },

        // Signature canvas
        sigPos(e) {
            const c = this.$refs.adminSigCanvas;
            const r = c.getBoundingClientRect();
            return { x: (e.clientX - r.left) * (c.width / r.width), y: (e.clientY - r.top) * (c.height / r.height) };
        },
        startSig(e) {
            const c = this.$refs.adminSigCanvas;
            if (!c) return;
            c.setPointerCapture(e.pointerId);
            const ctx = c.getContext('2d');
            const p = this.sigPos(e);
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a';
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            this.drawing = true;
        },
        moveSig(e) {
            if (!this.drawing) return;
            const ctx = this.$refs.adminSigCanvas.getContext('2d');
            const p = this.sigPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        },
        endSig() {
            if (!this.drawing) return;
            this.drawing = false;
            this.adminSignature = this.$refs.adminSigCanvas.toDataURL('image/png');
        },
        clearSig() {
            const c = this.$refs.adminSigCanvas;
            if (c) c.getContext('2d').clearRect(0, 0, c.width, c.height);
            this.adminSignature = '';
        },
        drawSigImage(url) {
            const c = this.$refs.adminSigCanvas;
            if (!c || !url) return;
            const ctx = c.getContext('2d');
            const img = new Image();
            img.onload = () => {
                ctx.clearRect(0, 0, c.width, c.height);
                const ratio = Math.min((c.width - 20) / img.width, (c.height - 20) / img.height, 1);
                const w = img.width * ratio;
                const h = img.height * ratio;
                ctx.drawImage(img, (c.width - w) / 2, (c.height - h) / 2, w, h);
            };
            img.src = url;
        },
        applySavedSig(url) {
            this.adminSignature = url;
            this.drawSigImage(url);
        },
        loadSigFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                if (typeof reader.result === 'string') {
                    this.adminSignature = reader.result;
                    this.drawSigImage(reader.result);
                }
            };
            reader.readAsDataURL(file);
        },

        async approveCurrentItem() {
            const item = this.currentItem();
            if (!item) return;
            this.feedbackMsg = '';

            if (!this.adminSignature) {
                this.feedbackOk = false;
                this.feedbackMsg = 'Please draw or attach your Admin authorization signature first.';
                return;
            }

            const allocations = item.offers
                .filter(o => o.checked && parseInt(o.approvedQty, 10) > 0)
                .map(o => ({ offer_id: o.id, qty: parseInt(o.approvedQty, 10) }));

            if (!allocations.length) {
                this.feedbackOk = false;
                this.feedbackMsg = 'Check at least one supplier offer and enter an approved quantity.';
                return;
            }

            this.busy = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`${this.approveItemBaseUrl}/${item.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        admin_signature: this.adminSignature,
                        allocations,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    this.feedbackOk = false;
                    this.feedbackMsg = data.message || 'Could not approve item.';
                    return;
                }
                item.status = 'Approved';
                if (data.all_approved) {
                    this.status = 'Approved';
                    if (data.ris_number) this.linkedRisNumber = data.ris_number;
                }
                this.feedbackOk = true;
                this.feedbackMsg = data.message || 'Item approved.';
            } catch (e) {
                this.feedbackOk = false;
                this.feedbackMsg = 'Network error while approving item.';
            } finally {
                this.busy = false;
            }
        },

        async returnCurrentItem() {
            const item = this.currentItem();
            if (!item) return;
            const note = prompt(`Enter reason for returning "${item.name}" to the Purchaser:`);
            if (!note || !note.trim()) return;

            this.busy = true;
            this.feedbackMsg = '';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`${this.approveItemBaseUrl}/${item.id}/return`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ note: note.trim() }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    this.feedbackOk = false;
                    this.feedbackMsg = data.message || 'Could not return item.';
                    return;
                }
                item.status = 'Returned';
                this.status = 'Returned';
                this.feedbackOk = true;
                this.feedbackMsg = data.message || 'Item returned to Purchaser.';
            } catch (e) {
                this.feedbackOk = false;
                this.feedbackMsg = 'Network error while returning item.';
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
@endsection
