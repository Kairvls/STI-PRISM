@extends('layouts.admin-layout')

@section('page-title', 'Canvass Review')
@section('page-subtitle', 'Compare supplier & brand quotations, select winning offers per item, and authorize ATP & RIS.')

@section('content')
@php
    $portalPrefix = \App\Support\AdminPortal::prefix();
    $savedSigList = ($savedSignatures ?? collect())->map(fn ($s) => [
        'id' => (int) $s->user_signature_id,
        'label' => (string) ($s->user_signature_label ?: 'Saved signature'),
        'url' => (string) ($s->preview_url ?? ''),
    ])->values()->all();

    $filterMeta = [
        'all' => ['label' => 'All Requests', 'count' => $counts['all']],
        'pending' => ['label' => 'Pending Review', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved', 'count' => $counts['approved']],
        'returned' => ['label' => 'Returned', 'count' => $counts['returned']],
        'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected'] ?? 0],
    ];
    $activeTitle = $filterMeta[$filter]['label'] ?? 'All Requests';
@endphp

<div class="mx-auto max-w-6xl space-y-5 pb-12">
    {{-- Top Filter Tabs (matches RIS Approvals) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex flex-wrap items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            @foreach ($filterMeta as $key => $meta)
                <a
                    href="{{ route($portalPrefix.'.canvass-review.index', array_filter(['filter' => $key, 'urgency' => $urgency, 'search' => $search])) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold transition {{ $filter === $key ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                >
                    <span>{{ $meta['label'] }}</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Main Unified Table Card (matches RIS Approvals) --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- Card Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-2.5">
                <h2 class="text-sm font-bold text-slate-900">{{ $activeTitle }}</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $canvasses->total() }} total
                </span>
            </div>

            <form method="GET" action="{{ route($portalPrefix.'.canvass-review.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <select
                    name="urgency"
                    onchange="this.form.submit()"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 outline-none focus:border-blue-500"
                >
                    <option value="">All urgency</option>
                    <option value="Urgent" @selected($urgency === 'Urgent')>Urgent</option>
                    <option value="Non-Urgent" @selected($urgency === 'Non-Urgent')>Non-Urgent</option>
                </select>
                <div class="relative">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search CAN no., Request no., purchaser..."
                        class="w-64 rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-3 text-xs text-slate-900 outline-none focus:border-blue-500"
                    >
                </div>
                <button type="submit" class="rounded-xl bg-[#0025cc] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#001db3]">
                    Search
                </button>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50/80 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Canvass No.</th>
                        <th class="px-4 py-3.5">Request No.</th>
                        <th class="px-4 py-3.5">Items &amp; Attachments</th>
                        <th class="px-4 py-3.5">Requested By</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>

                @forelse ($canvasses as $row)
                    @php
                        $cid = (int) $row->canvass_id;
                        $ws = $workspaces[$cid] ?? null;
                        $displayNo = \App\Support\CanvassWorkflow::displayNumber($row);
                        $isInitiallyOpen = ($openCanvassId === $cid);
                        $rowUrgency = \App\Support\RisWorkflow::normalizeUrgency($row->canvass_urgency ?? null);
                        $firstItemName = $ws && $ws['items']->isNotEmpty()
                            ? (string) ($ws['items']->first()->canvass_item_name ?: $ws['items']->first()->catalog_item_name)
                            : '—';
                        $extraItemCount = $ws ? max(0, $ws['items']->count() - 1) : 0;
                        $rowAttachments = $ws ? $ws['attachments'] : collect();

                        $wsBoot = [
                            'canvassId' => $cid,
                            'displayNo' => $displayNo,
                            'requestNo' => (string) ($row->canvass_request_no ?? ''),
                            'urgency' => $rowUrgency,
                            'ownerName' => (string) ($row->owner_name ?: 'Purchaser'),
                            'status' => (string) $row->canvass_status,
                            'submittedAt' => $row->canvass_submitted_at ? \Carbon\Carbon::parse($row->canvass_submitted_at)->format('M d, Y h:i A') : '—',
                            'purchaserSignature' => (string) ($row->canvass_purchaser_signature ?? ''),
                            'adminSignature' => (string) ($row->canvass_admin_signature ?? ($savedSigList[0]['url'] ?? '')),
                            'linkedRisNumber' => (string) ($row->linked_ris_number ?? ''),
                            'canAct' => (bool) $canAct,
                            'initiallyOpen' => (bool) $isInitiallyOpen,
                            'startReviewUrl' => $canAct ? route($portalPrefix.'.canvass-review.start-review', $cid) : '',
                            'approveItemBaseUrl' => $canAct ? url($portalPrefix.'/canvass-review/'.$cid.'/items') : '',
                            'bulkApproveUrl' => $canAct ? route($portalPrefix.'.canvass-review.bulk-approve', $cid) : '',
                            'bulkReturnUrl' => $canAct ? route($portalPrefix.'.canvass-review.bulk-return', $cid) : '',
                            'bulkRejectUrl' => $canAct ? route($portalPrefix.'.canvass-review.bulk-reject', $cid) : '',
                            'printUrl' => route($portalPrefix.'.canvass-review.print', $cid),
                            'savedSignatures' => $savedSigList,
                            'attachments' => $rowAttachments->map(fn ($a) => [
                                'name' => (string) $a->canvass_attachment_original_name,
                                'sizeKb' => number_format(($a->canvass_attachment_size ?? 0) / 1024, 1),
                                'url' => route($portalPrefix.'.canvass-review.attachments.preview', $a->canvass_attachment_id),
                            ])->values()->all(),
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

                    <tbody
                        x-data="canvassReviewCard(@js($wsBoot))"
                        x-init="init()"
                        class="divide-y divide-slate-100 bg-white"
                    >
                        <tr class="transition hover:bg-slate-50/70">
                            {{-- 1. CANVASS NO. + URGENCY --}}
                            <td class="px-5 py-4 align-top">
                                <div class="font-bold text-slate-900" x-text="displayNo"></div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold"
                                        style="{{ $rowUrgency === 'Urgent' ? 'background:#dc2626;color:#ffffff;' : 'background:#dcfce7;color:#15803d;' }}"
                                    >
                                        {{ $rowUrgency }}
                                    </span>
                                    <span
                                        x-show="linkedRisNumber"
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-semibold"
                                        style="background:#ccfbf1;color:#0f766e;"
                                        x-text="linkedRisNumber"
                                    ></span>
                                </div>
                            </td>

                            {{-- 2. REQUEST NO. --}}
                            <td class="px-4 py-4 align-top">
                                @if (! empty($row->canvass_request_no))
                                    <span class="inline-flex rounded-lg border border-blue-200 bg-blue-50/70 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                                        {{ $row->canvass_request_no }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- 3. ITEMS & ATTACHMENTS --}}
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-semibold text-slate-900">{{ $firstItemName }}</span>
                                    @if ($extraItemCount > 0)
                                        <span class="rounded-md bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700">
                                            +{{ $extraItemCount }} more
                                        </span>
                                    @endif
                                </div>
                                @if (! empty($row->canvass_purpose))
                                    <p class="mt-0.5 max-w-xs truncate text-[11px] text-slate-500">{{ $row->canvass_purpose }}</p>
                                @endif
                                @if ($rowAttachments->isNotEmpty())
                                    <div class="mt-1.5 flex flex-col gap-0.5">
                                        @foreach ($rowAttachments->take(2) as $att)
                                            <a
                                                href="{{ route($portalPrefix.'.canvass-review.attachments.preview', $att->canvass_attachment_id) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="max-w-[230px] truncate text-[11px] font-semibold text-blue-700 hover:underline"
                                                title="{{ $att->canvass_attachment_original_name }}"
                                            >
                                                {{ $att->canvass_attachment_original_name }}
                                            </a>
                                        @endforeach
                                        @if ($rowAttachments->count() > 2)
                                            <span class="text-[10px] text-slate-400">+{{ $rowAttachments->count() - 2 }} more file(s)</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- 4. REQUESTED BY --}}
                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-slate-900">{{ $row->owner_name ?: 'Purchaser' }}</div>
                                <div class="mt-0.5 text-[11px] text-slate-500">
                                    Submitted {{ $row->canvass_submitted_at ? \Carbon\Carbon::parse($row->canvass_submitted_at)->format('M d, Y') : '—' }}
                                </div>
                            </td>

                            {{-- 5. STATUS --}}
                            <td class="px-4 py-4 align-top">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    :style="statusBadgeStyle()"
                                    x-text="shortStatus()"
                                ></span>
                                <div class="mt-1.5 text-[11px] text-slate-500">
                                    <span x-text="approvedCount() + ' of ' + items.length + ' approved'"></span>
                                    <span x-show="rejectedCount() > 0" style="color:#dc2626;" x-text="' · ' + rejectedCount() + ' rejected'"></span>
                                </div>
                            </td>

                            {{-- 6. ACTIONS (matches RIS Approvals icon + primary action pattern) --}}
                            <td class="px-5 py-4 text-right align-top">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openWorkspace()"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                        :style="canAct && isReviewable()
                                            ? 'background:#0025cc;color:#ffffff;'
                                            : 'background:#ffffff;border:1px solid #cbd5e1;color:#334155;'"
                                        x-text="canAct && isReviewable() ? 'Review & Approve' : 'View'"
                                    ></button>

                                    <button
                                        type="button"
                                        x-show="canAct && isReviewable()"
                                        @click="openReasonModal('return', 'all')"
                                        :disabled="busy"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition hover:opacity-85 disabled:opacity-45"
                                        style="background:#fffbeb;border-color:#fcd34d;color:#b45309;"
                                        title="Return for revision"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        x-show="canAct && isReviewable()"
                                        @click="openReasonModal('reject', 'all')"
                                        :disabled="busy"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition hover:opacity-85 disabled:opacity-45"
                                        style="background:#fef2f2;border-color:#fecaca;color:#dc2626;"
                                        title="Reject request"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>

                                    <a
                                        :href="printUrl"
                                        target="_blank"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                        title="Print ATP & RIS"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                                        </svg>
                                    </a>
                                </div>

                                {{-- TELEPORTED XL WORKSPACE MODAL --}}
                                <template x-teleport="body">
                                    <div
                                        x-cloak
                                        x-show="open"
                                        @keydown.escape.window="if (open && !reasonModal.open && !approveModal.open) open = false"
                                        class="fixed inset-0 z-[250] flex items-center justify-center bg-slate-900/45 p-4"
                                        @click.self="open = false"
                                    >
                                        <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            {{-- Sticky Modal Header --}}
                                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-6 py-4">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h3 class="text-base font-bold text-slate-900" x-text="'Request ' + displayNo"></h3>
                                                        <span
                                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                                            :style="statusBadgeStyle()"
                                                            x-text="status"
                                                        ></span>
                                                        <span
                                                            x-show="linkedRisNumber"
                                                            class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                                            style="background:#ccfbf1;color:#0f766e;"
                                                            x-text="'RIS ' + linkedRisNumber"
                                                        ></span>
                                                    </div>
                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        Requested by <span class="font-semibold text-slate-700" x-text="ownerName"></span>
                                                        · Submitted <span x-text="submittedAt"></span>
                                                        · <span x-text="items.length + ' item(s)'"></span>
                                                        · <span class="font-semibold text-slate-700" x-text="'Total qty requested: ' + totalRequestedQty()"></span>
                                                    </p>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        x-show="canAct && isReviewable()"
                                                        @click="openReasonModal('return', 'all')"
                                                        :disabled="busy"
                                                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition hover:opacity-90 disabled:opacity-45"
                                                        style="background:#fffbeb;border-color:#fcd34d;color:#b45309;"
                                                    >
                                                        Return
                                                    </button>
                                                    <button
                                                        type="button"
                                                        x-show="canAct && isReviewable()"
                                                        @click="openReasonModal('reject', 'all')"
                                                        :disabled="busy"
                                                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition hover:opacity-90 disabled:opacity-45"
                                                        style="background:#fef2f2;border-color:#fecaca;color:#dc2626;"
                                                    >
                                                        Reject
                                                    </button>
                                                    <a
                                                        :href="printUrl"
                                                        target="_blank"
                                                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                    >
                                                        Print ATP &amp; RIS
                                                    </a>
                                                    <button
                                                        type="button"
                                                        @click="open = false"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                        title="Close"
                                                    >
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Scrollable Modal Body (3-Tab Compact Workspace) --}}
                                            <div class="flex-1 space-y-4 overflow-y-auto bg-slate-50/50 p-5">
                                                {{-- 3-Tab Switcher --}}
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="inline-flex flex-wrap items-center gap-1 rounded-xl border border-slate-200 bg-white p-1">
                                                        <button
                                                            type="button"
                                                            @click="activeTab = 'review'"
                                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                                            :style="activeTab === 'review' ? 'background:#0025cc;color:#ffffff;' : 'background:transparent;color:#475569;'"
                                                        >
                                                            <span>Review &amp; Approve</span>
                                                            <span
                                                                class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                                                :style="activeTab === 'review' ? 'background:rgba(255,255,255,0.22);color:#ffffff;' : 'background:#f1f5f9;color:#475569;'"
                                                                x-text="pendingItems().length + ' pending'"
                                                            ></span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="activeTab = 'atp'"
                                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                                            :style="activeTab === 'atp' ? 'background:#0025cc;color:#ffffff;' : 'background:transparent;color:#475569;'"
                                                        >
                                                            <span>Authorities to Purchase</span>
                                                            <span
                                                                class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                                                :style="activeTab === 'atp' ? 'background:rgba(255,255,255,0.22);color:#ffffff;' : 'background:#f1f5f9;color:#475569;'"
                                                                x-text="supplierAtpPages().length"
                                                            ></span>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="activeTab = 'ris'"
                                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                                            :style="activeTab === 'ris' ? 'background:#0025cc;color:#ffffff;' : 'background:transparent;color:#475569;'"
                                                        >
                                                            <span>Requisition &amp; Issue Slip</span>
                                                            <span
                                                                class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                                                :style="activeTab === 'ris' ? 'background:rgba(255,255,255,0.22);color:#ffffff;' : 'background:#f1f5f9;color:#475569;'"
                                                                x-text="items.length"
                                                            ></span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Feedback Banner --}}
                                                <div x-show="feedbackMsg" class="rounded-xl border px-4 py-2.5 text-xs font-semibold" :style="feedbackOk ? 'border-color:#bbf7d0;background:#f0fdf4;color:#166534;' : 'border-color:#fecdd3;background:#fff1f2;color:#be123c;'" x-text="feedbackMsg"></div>

                                                {{-- TAB 1: REVIEW & APPROVE --}}
                                                <div x-show="activeTab === 'review'" class="space-y-3.5">
                                                    {{-- Item Quotations Card --}}
                                                    <template x-if="currentItem()">
                                                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                                                            <div class="border-l-4 border-blue-600 pl-3">
                                                                <h4 class="text-sm font-bold text-slate-900">Select supplier offers to approve</h4>
                                                                <!--<p class="text-xs text-slate-500">Check the winning supplier offer(s) and quantity for each item you want to approve. Items left unselected will be marked Rejected when you confirm approval.</p>-->
                                                            </div>

                                                            {{-- Clean Item Switcher Pills --}}
                                                            <div class="mt-3.5 flex flex-wrap items-center gap-2">
                                                                <template x-for="(it, idx) in items" :key="'pill-' + it.id">
                                                                    <button
                                                                        type="button"
                                                                        @click="activeItemIdx = idx"
                                                                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                                                                        :style="activeItemIdx === idx
                                                                            ? 'border-color:#0025cc;background:#eff6ff;color:#0025cc;'
                                                                            : 'border-color:#e2e8f0;background:#ffffff;color:#475569;'"
                                                                    >
                                                                        <span
                                                                            class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                                                            :style="(it.status === 'Approved' || (isReviewable() && itemAllocationReady(it)))
                                                                                ? 'background:#16a34a;color:#ffffff;'
                                                                                : (it.status === 'Rejected'
                                                                                    ? 'background:#dc2626;color:#ffffff;'
                                                                                    : (it.status === 'Returned'
                                                                                        ? 'background:#d97706;color:#ffffff;'
                                                                                        : 'background:#e2e8f0;color:#64748b;'))"
                                                                            x-text="(it.status === 'Approved' || (isReviewable() && itemAllocationReady(it))) ? '✓' : (it.status === 'Rejected' ? '✕' : (it.status === 'Returned' ? '↺' : (idx + 1)))"
                                                                        ></span>
                                                                        <span class="max-w-[170px] truncate" x-text="'Item ' + (idx + 1) + ': ' + it.name"></span>
                                                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600" x-text="it.qty + ' ' + it.uom"></span>
                                                                    </button>
                                                                </template>
                                                            </div>

                                                            <div class="mt-3.5 rounded-xl border border-slate-200 p-4">
                                                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                                                    <div class="text-xs font-bold text-slate-900" x-text="'Supplier quotations · Item ' + (activeItemIdx + 1) + ': ' + currentItem().name"></div>
                                                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                                                        <span
                                                                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-bold"
                                                                            style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"
                                                                        >
                                                                            <span>Total Qty Requested:</span>
                                                                            <span x-text="currentItem().qty + ' ' + currentItem().uom"></span>
                                                                        </span>
                                                                        <span
                                                                            class="rounded-lg px-2 py-1 text-[11px] font-semibold"
                                                                            :style="currentItem().status === 'Approved' ? 'background:#dcfce7;color:#15803d;' : (currentItem().status === 'Rejected' ? 'background:#fee2e2;color:#dc2626;' : (currentItem().status === 'Returned' ? 'background:#fef3c7;color:#b45309;' : 'background:#f1f5f9;color:#475569;'))"
                                                                            x-text="currentItem().status"
                                                                        ></span>
                                                                        <button
                                                                            type="button"
                                                                            x-show="canAct && isReviewable() && currentItem().status !== 'Approved' && currentItem().status !== 'Rejected'"
                                                                            @click="openReasonModal('reject', 'single')"
                                                                            :disabled="busy"
                                                                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[11px] font-semibold transition hover:opacity-85 disabled:opacity-45"
                                                                            style="background:#fef2f2;border-color:#fecaca;color:#dc2626;"
                                                                            title="Reject only this item"
                                                                        >
                                                                            <span>Reject item</span>
                                                                        </button>
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
                                                                                            :max="maxOfferUnits(currentItem(), offer)"
                                                                                            step="1"
                                                                                            x-model="offer.approvedQty"
                                                                                            @input="onQtyInput(currentItem(), offer)"
                                                                                            :disabled="!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected'"
                                                                                            placeholder="0"
                                                                                            class="w-24 rounded-md border px-2.5 py-1 text-xs outline-none focus:border-blue-500"
                                                                                            :style="(!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected')
                                                                                                ? 'background:#f1f5f9;border-color:#e2e8f0;color:#94a3b8;cursor:not-allowed;'
                                                                                                : 'background:#ffffff;border-color:#94a3b8;color:#0f172a;'"
                                                                                        >
                                                                                    </td>
                                                                                    <td class="border border-slate-200 px-3 py-2 text-slate-800" x-text="formatPeso(offer.unitCost)"></td>
                                                                                    <td class="border border-slate-200 px-3 py-2 font-semibold text-slate-900" x-text="formatPeso(offerAmount(offer))"></td>
                                                                                    <td class="border border-slate-200 px-3 py-2 text-center">
                                                                                        <input
                                                                                            type="checkbox"
                                                                                            :checked="offer.checked"
                                                                                            @change="onToggleOffer(currentItem(), offer, $event.target.checked)"
                                                                                            :disabled="isOfferCheckboxDisabled(currentItem(), offer)"
                                                                                            :title="(!offer.checked && itemAllocationReady(currentItem())) ? 'Requested quantity already reached. Lower an allocated qty or uncheck an offer to select this row.' : 'Select offer for approval'"
                                                                                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-40"
                                                                                        >
                                                                                    </td>
                                                                                </tr>
                                                                            </template>
                                                                        </tbody>
                                                                    </table>
                                                                </div>

                                                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 font-semibold"
                                                                        :style="allocationBadgeStyle(currentItem())"
                                                                        x-text="allocationBadgeText(currentItem())"
                                                                    ></span>
                                                                    <span class="text-xs font-bold text-slate-900" x-text="'Estimated total: ' + formatPeso(itemEstimatedTotal(currentItem()))"></span>
                                                                </div>

                                                                <template x-if="currentItem().reviewNote">
                                                                    <div class="mt-2.5 rounded-lg border px-3 py-2 text-xs" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
                                                                        <span class="font-semibold">Admin note:</span> <span x-text="currentItem().reviewNote"></span>
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            {{-- Pager + Single Primary Approve CTA --}}
                                                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                                                <div class="flex items-center gap-2">
                                                                    <button
                                                                        type="button"
                                                                        @click="if (activeItemIdx > 0) activeItemIdx--"
                                                                        :disabled="activeItemIdx === 0"
                                                                        title="Previous item"
                                                                        aria-label="Previous item"
                                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-500 bg-white text-sm font-bold text-blue-600 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400"
                                                                    >
                                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                                                                        </svg>
                                                                    </button>
                                                                    <span class="text-xs font-medium text-slate-600" x-text="'Item ' + (activeItemIdx + 1) + ' of ' + items.length"></span>
                                                                    <button
                                                                        type="button"
                                                                        @click="if (activeItemIdx < items.length - 1) activeItemIdx++"
                                                                        :disabled="activeItemIdx >= items.length - 1"
                                                                        title="Next item"
                                                                        aria-label="Next item"
                                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#0d6efd] text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                                                                    >
                                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                                                        </svg>
                                                                    </button>
                                                                </div>

                                                                <div x-show="canAct && isReviewable()" class="flex flex-wrap items-center gap-3">
                                                                    <div class="text-right text-xs">
                                                                        <span class="font-semibold text-slate-800" x-text="readyPendingItems().length + ' of ' + pendingItems().length + ' selected for approval'"></span>
                                                                        <span
                                                                            x-show="readyPendingItems().length > 0 && readyPendingItems().length < pendingItems().length"
                                                                            class="ml-1 font-medium"
                                                                            style="color: #dc2626;"
                                                                            x-text="'(' + (pendingItems().length - readyPendingItems().length) + ' will be rejected)'"
                                                                        ></span>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        @click="openFinalizeApproveModal()"
                                                                        :disabled="busy || readyPendingItems().length === 0"
                                                                        class="rounded-lg px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-45"
                                                                        style="background-color: #198754;"
                                                                        x-text="readyPendingItems().length === pendingItems().length && pendingItems().length > 0
                                                                            ? ('Approve all (' + pendingItems().length + ' of ' + pendingItems().length + ')')
                                                                            : ('Approve selected (' + readyPendingItems().length + ' of ' + pendingItems().length + ')')"
                                                                    ></button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Inline Attachments Strip --}}
                                                    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs">
                                                        <span class="font-semibold text-slate-600">Attachments:</span>
                                                        <template x-if="attachments.length === 0">
                                                            <span class="text-slate-400">No files attached</span>
                                                        </template>
                                                        <template x-for="(att, aIdx) in attachments" :key="aIdx">
                                                            <a
                                                                :href="att.url"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50/70 px-2.5 py-1 font-medium text-blue-700 transition hover:bg-blue-100"
                                                            >
                                                                <span class="max-w-[200px] truncate" x-text="att.name"></span>
                                                                <span class="text-[10px] text-blue-500" x-text="'(' + att.sizeKb + ' KB)'"></span>
                                                            </a>
                                                        </template>
                                                    </div>

                                                    {{-- Compact 1-Row Admin Signature Bar --}}
                                                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                                            <div class="flex flex-wrap items-center gap-3">
                                                                <span class="text-xs font-bold text-slate-800">Admin signature:</span>
                                                                <template x-if="adminSignature">
                                                                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1">
                                                                        <img :src="adminSignature" alt="Admin signature" class="h-6 max-w-[90px] object-contain">
                                                                        <span class="text-[11px] font-semibold" style="color: #16a34a;">✓ Ready</span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!adminSignature">
                                                                    <span class="text-xs font-semibold" style="color: #dc2626;">Required to approve</span>
                                                                </template>
                                                            </div>

                                                            <div x-show="canAct" class="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    @click="toggleSigPanel()"
                                                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                                    x-text="sigPanelOpen ? 'Hide signature pad' : (adminSignature ? 'Change / Draw signature' : 'Draw or attach signature')"
                                                                ></button>
                                                            </div>
                                                        </div>

                                                        <div x-show="sigPanelOpen" class="mt-3 border-t border-slate-100 pt-3">
                                                            <div x-show="canAct && savedSignatures.length > 0" class="mb-2 flex flex-wrap items-center gap-2">
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

                                                            <div class="relative overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-white">
                                                                <canvas
                                                                    x-ref="adminSigCanvas"
                                                                    width="860"
                                                                    height="130"
                                                                    @pointerdown="if (canAct) startSig($event)"
                                                                    @pointermove="if (canAct) moveSig($event)"
                                                                    @pointerup="endSig()"
                                                                    @pointerleave="endSig()"
                                                                    class="h-28 w-full cursor-crosshair touch-none"
                                                                ></canvas>
                                                                <div x-show="!adminSignature" class="pointer-events-none absolute inset-0 flex items-center justify-center text-xs text-slate-400">
                                                                    Draw your authorization signature here
                                                                </div>
                                                            </div>

                                                            <div x-show="canAct" class="mt-2.5 flex flex-wrap items-center gap-2">
                                                                <button type="button" @click="clearSig()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                                    Clear
                                                                </button>
                                                                <label class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                                    <span>Attach signature image</span>
                                                                    <input type="file" accept="image/png,image/jpeg,image/webp" @change="loadSigFile($event)" class="hidden">
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- TAB 2: AUTHORITIES TO PURCHASE PREVIEW --}}
                                                <div x-show="activeTab === 'atp'" class="rounded-xl border border-slate-200 bg-white p-5">
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

                                                {{-- TAB 3: REQUISITION AND ISSUE SLIP PREVIEW --}}
                                                <div x-show="activeTab === 'ris'" class="rounded-xl border border-slate-200 bg-white p-5">
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
                                                                    <img x-show="adminSignature && approvedCount() > 0 && pendingItems().length === 0" :src="adminSignature" alt="Issued by" class="max-h-11 object-contain">
                                                                </div>
                                                                <div class="mt-1 font-medium text-slate-700">Issued by</div>
                                                                <div class="mt-1 text-slate-500">Date: ______________________</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- TELEPORTED REASON MODAL (Return for revision / Reject) --}}
                                <template x-teleport="body">
                                    <div
                                        x-cloak
                                        x-show="reasonModal.open"
                                        @keydown.escape.window="if (reasonModal.open && !busy) reasonModal.open = false"
                                        class="fixed inset-0 z-[270] flex items-center justify-center bg-slate-900/45 p-4"
                                        @click.self="if (!busy) reasonModal.open = false"
                                    >
                                        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="border-b border-slate-100 px-6 py-4">
                                                <h3
                                                    class="text-base font-bold"
                                                    :style="reasonModal.type === 'reject' ? 'color:#991b1b;' : 'color:#92400e;'"
                                                    x-text="reasonModal.title"
                                                ></h3>
                                                <p class="mt-1 text-xs text-slate-500" x-text="reasonModal.subtitle"></p>
                                            </div>
                                            <div class="space-y-3 px-6 py-4">
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">
                                                        <span x-text="reasonModal.type === 'reject' ? 'Rejection reason' : 'Revision note for Purchaser'"></span>
                                                        <span style="color:#dc2626;">*</span>
                                                    </label>
                                                    <textarea
                                                        x-model="reasonModal.note"
                                                        rows="3"
                                                        maxlength="1000"
                                                        placeholder="Enter clear instructions or reason..."
                                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                    ></textarea>
                                                    <p x-show="reasonModal.error" x-text="reasonModal.error" class="mt-1 text-xs font-semibold" style="color:#dc2626;"></p>
                                                </div>
                                                <p x-show="reasonModal.type === 'reject'" class="rounded-lg border px-3 py-2 text-xs" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
                                                    Rejected items will not be included in any generated RIS or Authority to Purchase.
                                                </p>
                                            </div>
                                            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                                                <button
                                                    type="button"
                                                    :disabled="busy"
                                                    @click="reasonModal.open = false"
                                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    type="button"
                                                    :disabled="busy"
                                                    @click="submitReasonModal()"
                                                    class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                                                    :style="reasonModal.type === 'reject' ? 'background:#dc2626;' : 'background:#d97706;'"
                                                    x-text="busy ? 'Processing...' : reasonModal.confirmLabel"
                                                ></button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- TELEPORTED FINALIZE APPROVE CONFIRMATION MODAL --}}
                                <template x-teleport="body">
                                    <div
                                        x-cloak
                                        x-show="approveModal.open"
                                        @keydown.escape.window="if (approveModal.open && !busy) approveModal.open = false"
                                        class="fixed inset-0 z-[270] flex items-center justify-center bg-slate-900/45 p-4"
                                        @click.self="if (!busy) approveModal.open = false"
                                    >
                                        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="border-b border-slate-100 px-6 py-4">
                                                <h3 class="text-base font-bold text-slate-900" x-text="approveModal.title"></h3>
                                                <p class="mt-0.5 text-xs text-slate-500">Review the items and supplier totals to be approved with your authorization signature.</p>
                                            </div>
                                            <div class="max-h-80 space-y-3 overflow-y-auto px-6 py-4">
                                                <div>
                                                    <p class="mb-1.5 text-[11px] font-bold uppercase tracking-wider" style="color: #166534;" x-text="'Will be Approved (' + approveModal.summary.length + ')'"></p>
                                                    <div class="space-y-2">
                                                        <template x-for="row in approveModal.summary" :key="row.itemId">
                                                            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs">
                                                                <div class="min-w-0">
                                                                    <div class="truncate font-bold text-slate-900" x-text="row.name"></div>
                                                                    <div class="mt-0.5 text-slate-500" x-text="row.detail"></div>
                                                                </div>
                                                                <span class="shrink-0 font-bold text-slate-900" x-text="formatPeso(row.total)"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-between border-t border-slate-200 pt-2.5 text-xs font-bold text-slate-900">
                                                    <span>Total estimated approval amount</span>
                                                    <span x-text="formatPeso(approveModal.grandTotal)"></span>
                                                </div>

                                                <template x-if="approveModal.unselected.length > 0">
                                                    <div class="rounded-xl border p-3.5 text-xs" style="background:#fef2f2;border-color:#fecaca;">
                                                        <p class="font-bold" style="color:#991b1b;" x-text="'Not selected — will be marked Rejected (' + approveModal.unselected.length + ')'"></p>
                                                        <ul class="mt-1.5 list-disc space-y-0.5 pl-4" style="color:#dc2626;">
                                                            <template x-for="u in approveModal.unselected" :key="u.itemId">
                                                                <li x-text="u.name"></li>
                                                            </template>
                                                        </ul>
                                                        <div class="mt-2.5">
                                                            <label class="mb-1 block text-[11px] font-semibold" style="color:#991b1b;">Optional note for unselected items</label>
                                                            <input
                                                                type="text"
                                                                x-model="approveModal.unselectedNote"
                                                                maxlength="500"
                                                                placeholder="e.g. Budget deferred to next quarter"
                                                                class="w-full rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 outline-none focus:border-red-400"
                                                            >
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                                                <button
                                                    type="button"
                                                    :disabled="busy"
                                                    @click="approveModal.open = false"
                                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    type="button"
                                                    :disabled="busy"
                                                    @click="confirmBulkApprove()"
                                                    class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                                                    style="background:#198754;"
                                                    x-text="busy ? 'Approving...' : approveModal.confirmLabel"
                                                ></button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                No canvass requests found in this filter.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- Table Footer --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5 text-xs text-slate-500">
            <div>
                @if ($canvasses->total() > 0)
                    Showing <span class="font-semibold text-slate-800">{{ $canvasses->firstItem() }}</span> to <span class="font-semibold text-slate-800">{{ $canvasses->lastItem() }}</span> of <span class="font-semibold text-slate-800">{{ $canvasses->total() }}</span> records
                @else
                    Showing 0 records
                @endif
            </div>
            @if ($canvasses->hasPages())
                <div>{{ $canvasses->links() }}</div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function canvassReviewCard(boot) {
    return {
        canvassId: boot.canvassId,
        displayNo: boot.displayNo,
        ownerName: boot.ownerName || 'Purchaser',
        status: boot.status,
        submittedAt: boot.submittedAt,
        purchaserSignature: boot.purchaserSignature || '',
        adminSignature: boot.adminSignature || '',
        linkedRisNumber: boot.linkedRisNumber || '',
        canAct: Boolean(boot.canAct),
        open: Boolean(boot.initiallyOpen),
        activeTab: 'review',
        sigPanelOpen: !boot.adminSignature,
        startReviewUrl: boot.startReviewUrl || '',
        approveItemBaseUrl: boot.approveItemBaseUrl || '',
        bulkApproveUrl: boot.bulkApproveUrl || '',
        bulkReturnUrl: boot.bulkReturnUrl || '',
        bulkRejectUrl: boot.bulkRejectUrl || '',
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
        reasonModal: { open: false, type: 'return', scope: 'all', title: '', subtitle: '', confirmLabel: '', note: '', error: '' },
        approveModal: { open: false, all: false, title: '', confirmLabel: '', summary: [], unselected: [], unselectedNote: '', grandTotal: 0, payloadItems: [] },

        init() {
            if (this.open) {
                this.ensureReviewStarted();
                this.$nextTick(() => {
                    if (this.sigPanelOpen && this.adminSignature) this.drawSigImage(this.adminSignature);
                });
            }
        },

        openWorkspace() {
            this.open = true;
            this.ensureReviewStarted();
            this.$nextTick(() => {
                if (this.sigPanelOpen && this.adminSignature) this.drawSigImage(this.adminSignature);
            });
        },

        toggleSigPanel() {
            this.sigPanelOpen = !this.sigPanelOpen;
            if (this.sigPanelOpen && this.adminSignature) {
                this.$nextTick(() => this.drawSigImage(this.adminSignature));
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

        isReviewable() {
            return this.status === 'Awaiting for review' || this.status === 'Reviewing - Awaiting for approval';
        },

        pendingItems() {
            return this.items.filter(it => it.status !== 'Approved' && it.status !== 'Rejected');
        },

        readyPendingItems() {
            return this.pendingItems().filter(it => this.itemAllocationReady(it));
        },

        approvedCount() {
            return this.items.filter(it => it.status === 'Approved').length;
        },

        rejectedCount() {
            return this.items.filter(it => it.status === 'Rejected').length;
        },

        shortStatus() {
            if (this.status === 'Awaiting for review') return 'To review';
            if (this.status === 'Reviewing - Awaiting for approval') return 'Reviewing';
            return this.status;
        },

        statusBadgeStyle() {
            if (this.status === 'Approved' || this.status === 'Converted to RIS') return 'background:#dcfce7;color:#15803d;';
            if (this.status === 'Rejected') return 'background:#fee2e2;color:#b91c1c;';
            if (this.status === 'Reviewing - Awaiting for approval') return 'background:#dbeafe;color:#1d4ed8;';
            if (this.status === 'Returned') return 'background:#fef3c7;color:#b45309;';
            return 'background:#dcfce7;color:#15803d;';
        },

        itemAllocationReady(item) {
            if (!item) return false;
            return (this.itemAllocatedUnits(item) + 0.0001) >= Number(item.qty || 1);
        },

        totalRequestedQty() {
            return (this.items || []).reduce((sum, it) => sum + Math.max(0, parseInt(it.qty, 10) || 0), 0);
        },

        allocationBadgeText(item) {
            if (!item) return '';
            const alloc = this.itemAllocatedUnits(item);
            const req = Number(item.qty || 1);
            const uom = item.uom || 'unit';
            if (alloc <= 0) {
                return `Allocated: 0 / ${req} ${uom} (Select offer(s) to cover ${req} ${uom})`;
            }
            if (alloc + 0.0001 < req) {
                const diff = Math.round((req - alloc) * 100) / 100;
                return `Allocated: ${alloc} / ${req} ${uom} (${diff} ${uom} remaining)`;
            }
            if (alloc > req + 0.0001) {
                const over = Math.round((alloc - req) * 100) / 100;
                return `⚠ Allocated: ${alloc} / ${req} ${uom} (+${over} ${uom} over requested)`;
            }
            return `✓ Allocated: ${alloc} / ${req} ${uom} (Exact match)`;
        },

        allocationBadgeStyle(item) {
            if (!item) return 'background:#f8fafc;border-color:#e2e8f0;color:#64748b;';
            const alloc = this.itemAllocatedUnits(item);
            const req = Number(item.qty || 1);
            if (alloc <= 0) return 'background:#f8fafc;border-color:#e2e8f0;color:#64748b;';
            if (alloc + 0.0001 < req) return 'background:#fffbeb;border-color:#fcd34d;color:#b45309;';
            if (alloc > req + 0.0001) return 'background:#fff7ed;border-color:#fdba74;color:#c2410c;';
            return 'background:#f0fdf4;border-color:#bbf7d0;color:#15803d;';
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

        isOfferCheckboxDisabled(item, offer) {
            if (!this.canAct || !this.isReviewable() || !item) return true;
            if (item.status === 'Approved' || item.status === 'Rejected') return true;
            if (!offer.checked && this.itemAllocationReady(item)) return true;
            return false;
        },

        maxOfferUnits(item, offer) {
            if (!item || !offer) return 1;
            const otherAllocated = item.offers.reduce((sum, o) => {
                if (o.id === offer.id || !o.checked) return sum;
                const q = Math.max(0, parseInt(o.approvedQty, 10) || 0);
                return sum + (q * Number(o.pack || 1));
            }, 0);
            const remaining = Math.max(0, Number(item.qty || 1) - otherAllocated);
            const pack = Math.max(0.01, Number(offer.pack || 1));
            return Math.max(1, Math.ceil(remaining / pack));
        },

        onQtyInput(item, offer) {
            if (!offer.checked) return;
            if (offer.approvedQty === '' || offer.approvedQty === null) return;
            const raw = parseInt(offer.approvedQty, 10);
            if (isNaN(raw)) return;
            const maxUnits = this.maxOfferUnits(item, offer);
            if (raw > maxUnits) {
                offer.approvedQty = String(maxUnits);
            } else if (raw < 1) {
                offer.approvedQty = '1';
            }
            if (this.itemAllocationReady(item)) {
                item.offers.forEach(o => {
                    if (o.id !== offer.id && o.checked && (parseInt(o.approvedQty, 10) || 0) <= 0) {
                        o.checked = false;
                        o.approvedQty = '';
                    }
                });
            }
        },

        onToggleOffer(item, offer, isChecked) {
            if (isChecked && this.itemAllocationReady(item) && !offer.checked) {
                return;
            }
            offer.checked = Boolean(isChecked);
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

        openFinalizeApproveModal() {
            this.feedbackMsg = '';
            if (!this.adminSignature) {
                this.sigPanelOpen = true;
                this.feedbackOk = false;
                this.feedbackMsg = 'Please draw or attach your Admin authorization signature first.';
                return;
            }

            const pending = this.pendingItems();
            const ready = this.readyPendingItems();
            if (!ready.length) {
                this.feedbackOk = false;
                this.feedbackMsg = 'Select winning supplier offer(s) covering the requested quantity on at least one item before approving.';
                return;
            }

            for (const it of pending) {
                const hasAnyChecked = it.offers.some(o => o.checked);
                if (hasAnyChecked && !this.itemAllocationReady(it)) {
                    const idx = this.items.findIndex(x => x.id === it.id);
                    if (idx >= 0) this.activeItemIdx = idx;
                    this.feedbackOk = false;
                    this.feedbackMsg = `Item ${idx + 1} (${it.name}) has checked offer(s) totaling ${this.itemAllocatedUnits(it)} ${it.uom}, which is less than the requested ${it.qty} ${it.uom}. Finish allocating it or uncheck its offers.`;
                    return;
                }
            }

            const summary = [];
            const unselected = [];
            const payloadItems = [];
            let grandTotal = 0;

            for (const it of pending) {
                const idx = this.items.findIndex(x => x.id === it.id);
                if (this.itemAllocationReady(it)) {
                    const allocs = it.offers
                        .filter(o => o.checked && parseInt(o.approvedQty, 10) > 0)
                        .map(o => ({ offer_id: o.id, qty: parseInt(o.approvedQty, 10) }));
                    const total = this.itemEstimatedTotal(it);
                    grandTotal += total;
                    const supNames = [...new Set(it.offers.filter(o => o.checked && parseInt(o.approvedQty, 10) > 0).map(o => o.supplier))];
                    summary.push({
                        itemId: it.id,
                        name: `Item ${idx + 1}: ${it.name}`,
                        detail: `${this.itemAllocatedUnits(it)} ${it.uom} · ${supNames.join(', ')}`,
                        total,
                    });
                    payloadItems.push({ item_id: it.id, allocations: allocs });
                } else {
                    unselected.push({
                        itemId: it.id,
                        name: `Item ${idx + 1}: ${it.name} (${it.qty} ${it.uom})`,
                    });
                }
            }

            const allSelected = unselected.length === 0;
            this.approveModal = {
                open: true,
                all: allSelected,
                title: allSelected
                    ? `Approve all ${summary.length} item(s)?`
                    : `Approve ${summary.length} of ${pending.length} item(s)?`,
                confirmLabel: 'Confirm & Finalize Approval',
                summary,
                unselected,
                unselectedNote: '',
                grandTotal,
                payloadItems,
            };
        },

        async confirmBulkApprove() {
            if (this.busy || !this.bulkApproveUrl) return;
            this.busy = true;
            this.feedbackMsg = '';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(this.bulkApproveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        admin_signature: this.adminSignature,
                        items: this.approveModal.payloadItems,
                        reject_unselected: true,
                        unselected_note: String(this.approveModal.unselectedNote || '').trim(),
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    this.approveModal.open = false;
                    this.feedbackOk = false;
                    this.feedbackMsg = data.message || 'Could not approve items.';
                    return;
                }
                const approvedSet = new Set((data.approved_item_ids || []).map(Number));
                const rejectedSet = new Set((data.rejected_item_ids || []).map(Number));
                const rejNote = String(this.approveModal.unselectedNote || '').trim() || 'Not selected during School Administrator approval.';
                this.items.forEach(it => {
                    if (approvedSet.has(Number(it.id))) it.status = 'Approved';
                    if (rejectedSet.has(Number(it.id))) {
                        it.status = 'Rejected';
                        it.reviewNote = rejNote;
                    }
                });
                if (data.canvass_status) this.status = data.canvass_status;
                if (data.all_approved) {
                    this.status = 'Approved';
                    if (data.ris_number) this.linkedRisNumber = data.ris_number;
                }
                this.approveModal.open = false;
                this.feedbackOk = true;
                this.feedbackMsg = data.message || 'Approval finalized.';
            } catch (e) {
                this.approveModal.open = false;
                this.feedbackOk = false;
                this.feedbackMsg = 'Network error while approving items.';
            } finally {
                this.busy = false;
            }
        },

        openReasonModal(type, scope) {
            this.feedbackMsg = '';
            const isReject = type === 'reject';
            if (scope === 'single') {
                const item = this.currentItem();
                if (!item) return;
                this.reasonModal = {
                    open: true,
                    type,
                    scope: 'single',
                    title: `Reject "${item.name}"?`,
                    subtitle: 'Only this item will be marked Rejected and excluded from any generated RIS or ATP.',
                    confirmLabel: 'Confirm Reject Item',
                    note: '',
                    error: '',
                };
                return;
            }
            this.reasonModal = {
                open: true,
                type,
                scope,
                title: isReject ? `Reject ${this.displayNo}?` : `Return ${this.displayNo} for revision?`,
                subtitle: isReject
                    ? 'All items in this canvass will be rejected and no RIS or ATP will be generated.'
                    : 'This canvass will be returned to the Purchaser so they can edit and resubmit.',
                confirmLabel: isReject ? 'Confirm Reject All' : 'Confirm Return',
                note: '',
                error: '',
            };
        },

        async submitReasonModal() {
            const note = String(this.reasonModal.note || '').trim();
            if (!note) {
                this.reasonModal.error = 'Please enter a reason before continuing.';
                return;
            }
            this.reasonModal.error = '';
            this.busy = true;
            this.feedbackMsg = '';

            const { type, scope } = this.reasonModal;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            let url = type === 'reject' ? this.bulkRejectUrl : this.bulkReturnUrl;
            let bodyPayload = { note, item_ids: [], all: true };

            if (scope === 'single') {
                const item = this.currentItem();
                if (!item) { this.busy = false; return; }
                url = `${this.approveItemBaseUrl}/${item.id}/reject`;
                bodyPayload = { note };
            }

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(bodyPayload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    this.reasonModal.error = data.message || 'Action failed.';
                    return;
                }

                if (scope === 'single') {
                    const item = this.currentItem();
                    if (item) {
                        item.status = 'Rejected';
                        item.reviewNote = note;
                        item.offers.forEach(o => { o.checked = false; o.approvedQty = ''; });
                    }
                    if (data.canvass_status) this.status = data.canvass_status;
                    if (data.all_approved && data.ris_number) this.linkedRisNumber = data.ris_number;
                } else if (type === 'return') {
                    this.items.forEach(it => {
                        it.status = 'Returned';
                        it.reviewNote = note;
                    });
                    this.status = 'Returned';
                } else {
                    this.items.forEach(it => {
                        it.status = 'Rejected';
                        it.reviewNote = note;
                        it.offers.forEach(o => { o.checked = false; o.approvedQty = ''; });
                    });
                    this.status = 'Rejected';
                }

                this.reasonModal.open = false;
                this.feedbackOk = true;
                this.feedbackMsg = data.message || 'Action completed.';
            } catch (e) {
                this.reasonModal.error = 'Network error. Please try again.';
            } finally {
                this.busy = false;
            }
        },
    };
}
</script>
@endpush
@endsection
