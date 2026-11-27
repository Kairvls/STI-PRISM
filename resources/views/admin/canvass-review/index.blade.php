@extends('layouts.admin-layout')

@section('page-title', 'Purchase Approvals')
@section('page-subtitle', 'Compare supplier quotations, finalize offers, then Direct Approve or forward to the President.')

@section('content')
@include('partials.ris-purchaser-sheet-styles')
@php
    $portalPrefix = \App\Support\AdminPortal::prefix();
    $savedSigList = ($savedSignatures ?? collect())->map(fn ($s) => [
        'id' => (int) $s->user_signature_id,
        'label' => (string) ($s->user_signature_label ?: 'Saved signature'),
        'url' => (string) ($s->preview_url ?? ''),
    ])->values()->all();

    $filterMeta = [
        'all' => ['label' => 'All', 'count' => $counts['all']],
        'pending' => ['label' => 'Pending Review', 'count' => $counts['pending']],
        'approved' => ['label' => 'Approved', 'count' => $counts['approved']],
        'returned' => ['label' => 'Returned', 'count' => $counts['returned']],
        'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected'] ?? 0],
    ];
    $activeTitle = $filterMeta[$filter]['label'] ?? 'All';
@endphp

<div class="mx-auto max-w-6xl pb-12">
    {{-- Shared 3-Phase Switcher: Pending review | Reviewed RIS | Awaiting Issued by --}}
    @include('admin.partials.ris-phase-tabs', [
        'phase' => 'accept',
        'pendingAcceptCount' => $pendingAcceptCount ?? $counts['pending'],
        'readyToSignCount' => $readyToSignCount ?? 0,
        'awaitingIssuedCount' => $awaitingIssuedCount ?? 0,
    ])

    {{-- Main Unified Table Card (matches Reviewed RIS) --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- Card Header --}}
        <div class="px-5 pt-4 pb-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-base font-semibold text-slate-900">Canvass Requests</h2>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                            {{ $canvasses->total() }} total
                        </span>
                    </div>
                </div>

                <a
                    href="{{ route($portalPrefix.'.canvass-review.export-pdf', array_filter(['filter' => $filter, 'urgency' => $urgency, 'submitted' => $submitted ?? '', 'search' => $search])) }}"
                    title="Export the current Canvass Requests table to PDF"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export PDF
                </a>
            </div>

            {{-- Sub-filters + Search Toolbar --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3.5">
                <div class="inline-flex flex-wrap items-center gap-1 rounded-xl border border-slate-200 bg-slate-50/70 p-1">
                    @foreach ($filterMeta as $key => $meta)
                        <a
                            href="{{ route($portalPrefix.'.canvass-review.index', array_filter(['filter' => $key, 'urgency' => $urgency, 'submitted' => $submitted ?? '', 'search' => $search])) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $filter === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <span>{{ $meta['label'] }}</span>
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $filter === $key ? 'bg-slate-100 text-slate-800' : 'text-slate-500' }}">{{ $meta['count'] }}</span>
                        </a>
                    @endforeach
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
                    <div class="relative inline-flex items-center">
                        <input
                            type="date"
                            name="submitted"
                            id="canvassReviewDateInput"
                            value="{{ $submitted ?? '' }}"
                            onchange="this.form.submit()"
                            class="pointer-events-none absolute inset-0 h-0 w-0 opacity-0"
                            tabindex="-1"
                            aria-hidden="true"
                        >
                        <button
                            type="button"
                            onclick="var inp = document.getElementById('canvassReviewDateInput'); if (inp) { if (typeof inp.showPicker === 'function') { inp.showPicker(); } else { inp.focus(); inp.click(); } }"
                            title="{{ !empty($submitted) ? 'Submitted: '.\Carbon\Carbon::parse($submitted)->format('M d, Y').' — click to change' : 'Filter by submitted date' }}"
                            aria-label="Filter by submitted date"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl border px-2.5 text-xs font-medium transition {{ !empty($submitted) ? 'border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            @if (!empty($submitted))
                                <span class="tabular-nums">{{ \Carbon\Carbon::parse($submitted)->format('M j, Y') }}</span>
                            @endif
                        </button>
                        @if (!empty($submitted))
                            <a
                                href="{{ route($portalPrefix.'.canvass-review.index', array_filter(['filter' => $filter, 'urgency' => $urgency, 'search' => $search])) }}"
                                title="Clear date filter"
                                aria-label="Clear date filter"
                                class="ml-1 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                            >×</a>
                        @endif
                    </div>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search CVS no., Request no., purchaser..."
                            class="w-64 rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-3 text-xs text-slate-900 outline-none focus:border-blue-500"
                        >
                    </div>
                </form>
            </div>
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
                        <th class="px-5 py-3.5 text-center">Actions</th>
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
                            'adminName' => (string) (auth()->user()->user_full_name ?? \App\Support\AccountingSigner::SIGNATURE_TITLE),
                            'status' => \App\Support\CanvassWorkflow::resolveDisplayStatus((string) $row->canvass_status, $row->linked_ris_status ?? null),
                            'submittedAt' => $row->canvass_submitted_at ? \Carbon\Carbon::parse($row->canvass_submitted_at)->format('M d, Y h:i A') : '—',
                            'atpDate' => $row->canvass_submitted_at ? \Carbon\Carbon::parse($row->canvass_submitted_at)->format('m/d/Y') : now()->format('m/d/Y'),
                            'purpose' => (string) ($row->canvass_purpose ?? ''),
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
                                'isSoleSource' => !empty($it->canvass_item_is_sole_source),
                                'soleSourceReason' => trim((string) ($it->canvass_item_sole_source_reason ?? '')),
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
                                        @include('partials.closable-more-popover', [
                                            'moreCount' => $extraItemCount,
                                            'heading' => 'Canvass items',
                                            'popoverKey' => 'canvass-review-'.$cid,
                                            'rows' => ($ws ? $ws['items'] : collect())->map(fn ($it) => [
                                                'title' => (string) ($it->canvass_item_name ?: $it->catalog_item_name),
                                                'meta' => 'Qty '.max(1, (int) ($it->canvass_item_quantity ?? 1)).' '.($it->uom_name ?: 'pcs'),
                                            ])->values()->all(),
                                        ])
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
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                    :style="statusBadgeStyle()"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" :style="'background:' + statusDotColor() + ';'"></span>
                                    <span x-text="shortStatus()"></span>
                                </span>
                                <div x-show="statusSubtext()" class="mt-1 text-[11px] font-medium leading-tight text-slate-600" x-text="statusSubtext()"></div>
                                <div class="mt-1 text-[11px] text-slate-500">
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
                                        :data-tip="canAct && isReviewable() ? 'Review Canvass' : 'View Canvass'"
                                        data-tip-place="above"
                                        :aria-label="canAct && isReviewable() ? 'Review Canvass' : 'View Canvass'"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition hover:opacity-85"
                                        :style="canAct && isReviewable()
                                            ? 'background:#eff6ff;color:#0025cc;'
                                            : 'background:#ffffff;border:1px solid #cbd5e1;color:#334155;'"
                                    >
                                        <template x-if="canAct && isReviewable()">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                        </template>
                                        <template x-if="!(canAct && isReviewable())">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </template>
                                    </button>

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

                                    {{-- Direct Print button when only 1 action is available (not reviewable) --}}
                                    <div x-show="!(canAct && isReviewable())" class="group relative inline-flex">
                                        <a
                                            :href="printUrl"
                                            target="_blank"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            aria-label="Print ATP &amp; RIS"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                                            </svg>
                                        </a>
                                        <span
                                            class="pointer-events-none absolute bottom-full right-0 z-30 mb-2 translate-y-1 whitespace-nowrap rounded-lg px-2.5 py-1 text-[11px] font-medium opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                            style="background:#0f172a;color:#ffffff;"
                                        >
                                            Print ATP &amp; RIS
                                            <span class="absolute right-3 top-full -mt-1 h-2 w-2 rotate-45" style="background:#0f172a;"></span>
                                        </span>
                                    </div>

                                    {{-- 3-Dots More Actions Menu (Print + Reject, only when reviewable) --}}
                                    <div
                                        x-show="canAct && isReviewable()"
                                        class="relative inline-flex"
                                        x-data="{
                                            menuOpen: false,
                                            top: 0,
                                            right: 0,
                                            toggleMenu(el) {
                                                if (this.menuOpen) { this.menuOpen = false; return; }
                                                const r = el.getBoundingClientRect();
                                                this.top = r.bottom + 6;
                                                this.right = Math.max(12, window.innerWidth - r.right);
                                                this.menuOpen = true;
                                            }
                                        }"
                                        @keydown.escape.window="menuOpen = false"
                                        @scroll.window="menuOpen = false"
                                    >
                                        <button
                                            type="button"
                                            @click.stop="toggleMenu($el)"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            title="More actions"
                                            aria-label="More actions"
                                        >
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M6 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm5.5 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm5.5 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                            </svg>
                                        </button>

                                        <template x-teleport="body">
                                            <div
                                                x-cloak
                                                x-show="menuOpen"
                                                @click.outside="menuOpen = false"
                                                :style="`position:fixed;top:${top}px;right:${right}px;z-index:240;`"
                                                class="w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-xl"
                                            >
                                                <a
                                                    :href="printUrl"
                                                    target="_blank"
                                                    @click="menuOpen = false"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                                                >
                                                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                                                    </svg>
                                                    <span>Print ATP &amp; RIS</span>
                                                </a>
                                                <button
                                                    type="button"
                                                    x-show="canAct && isReviewable()"
                                                    :disabled="busy"
                                                    @click="menuOpen = false; openReasonModal('reject', 'all')"
                                                    class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs font-semibold transition hover:bg-red-50 disabled:opacity-45"
                                                    style="color:#dc2626;"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <span>Reject</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- TELEPORTED XL WORKSPACE MODAL --}}
                                <template x-teleport="body">
                                    <div
                                        x-cloak
                                        x-show="open"
                                        @keydown.escape.window="if (open && !reasonModal.open && !approveModal.open && !closeConfirmOpen) requestCloseWorkspace()"
                                        class="fixed inset-0 z-[250] flex items-center justify-center bg-slate-900/45 p-4"
                                        @click.self="requestCloseWorkspace()"
                                    >
                                        <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            {{-- Unified Sticky White Header + Tabs --}}
                                            <div class="border-b border-slate-200 bg-white px-6 pt-5 pb-4">
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2.5">
                                                            <h3 class="text-lg font-bold tracking-tight text-slate-900" x-text="'Request ' + displayNo"></h3>
                                                            <span
                                                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                                                :style="statusBadgeStyle()"
                                                            >
                                                                <span
                                                                    class="h-1.5 w-1.5 rounded-full"
                                                                    :style="'background:' + statusDotColor() + ';'"
                                                                ></span>
                                                                <span x-text="status"></span>
                                                            </span>
                                                            <span
                                                                x-show="linkedRisNumber"
                                                                class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                                                style="background:#ccfbf1;color:#0f766e;"
                                                                x-text="'RIS ' + linkedRisNumber"
                                                            ></span>
                                                        </div>
                                                        <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                                                            <span>Requested by <span class="font-semibold text-slate-800" x-text="ownerName"></span></span>
                                                            <span>·</span>
                                                            <span>Submitted <span x-text="submittedAt"></span></span>
                                                            <span>·</span>
                                                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700" x-text="items.length + ' item(s)'"></span>
                                                            <span>·</span>
                                                            <span>Total qty requested: <span class="font-bold text-slate-900" x-text="totalRequestedQty()"></span></span>
                                                        </p>
                                                    </div>

                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            @click="requestCloseWorkspace()"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                            title="Close"
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>
                                                </div>

                                                <hr style="border-top:1px solid #e2e8f0;margin:1rem 0;">

                                                {{-- Integrated 3-Tab Switcher (Underlined tabs) --}}
                                                <div class="mt-4 flex flex-wrap items-center gap-6">
                                                    <button
                                                        type="button"
                                                        @click="activeTab = 'review'"
                                                        class="inline-flex items-center gap-2 px-1 pb-1 text-xs font-semibold transition"
                                                        :style="activeTab === 'review'
                                                            ? 'border-bottom:2px solid #2563eb;color:#2563eb;'
                                                            : 'border-bottom:2px solid #cbd5e1;color:#64748b;'"
                                                    >
                                                        <span>Review &amp; Accept</span>
                                                        <span
                                                            class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                                            :style="activeTab === 'review' ? 'background:#eff6ff;color:#2563eb;' : 'background:#f1f5f9;color:#64748b;'"
                                                            x-text="pendingItems().length + ' pending'"
                                                        ></span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="activeTab = 'ris'"
                                                        class="inline-flex items-center gap-2 px-1 pb-1 text-xs font-semibold transition"
                                                        :style="activeTab === 'ris'
                                                            ? 'border-bottom:2px solid #2563eb;color:#2563eb;'
                                                            : 'border-bottom:2px solid #cbd5e1;color:#64748b;'"
                                                    >
                                                        <span>Requisition Slip</span>
                                                        <span
                                                            class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                                            :style="activeTab === 'ris' ? 'background:#eff6ff;color:#2563eb;' : 'background:#f1f5f9;color:#64748b;'"
                                                            x-text="items.length"
                                                        ></span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="activeTab = 'atp'"
                                                        class="inline-flex items-center gap-2 px-1 pb-1 text-xs font-semibold transition"
                                                        :style="activeTab === 'atp'
                                                            ? 'border-bottom:2px solid #2563eb;color:#2563eb;'
                                                            : 'border-bottom:2px solid #cbd5e1;color:#64748b;'"
                                                    >
                                                        <span>Authorities to Purchase</span>
                                                        <span
                                                            class="rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                                                            :style="activeTab === 'atp' ? 'background:#eff6ff;color:#2563eb;' : 'background:#f1f5f9;color:#64748b;'"
                                                            x-text="supplierAtpPages().length"
                                                        ></span>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Scrollable Modal Body --}}
                                            <div class="flex-1 space-y-4 overflow-y-auto p-5" style="background:#f8fafc;">
                                                {{-- Feedback Banner --}}
                                                <div x-show="feedbackMsg" class="rounded-xl border px-4 py-2.5 text-xs font-semibold" :style="feedbackOk ? 'border-color:#bbf7d0;background:#f0fdf4;color:#166534;' : 'border-color:#fecdd3;background:#fff1f2;color:#be123c;'" x-text="feedbackMsg"></div>

                                                {{-- TAB 1: REVIEW & APPROVE --}}
                                                <div x-show="activeTab === 'review'" class="space-y-4">
                                                    {{-- Item Quotations Section (Direct on #f8fafc canvas — no double-nested card) --}}
                                                    <template x-if="currentItem()">
                                                        <div>
                                                            {{-- Supplier Quotations White Card (Title + Item Switcher Pills + Header + Table + Footer) --}}
                                                            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                                                                {{-- Top Section Title inside Card --}}
                                                                <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6 pb-4">
                                                                    <div class="flex items-center gap-2.5">
                                                                        <span class="h-5 w-1 shrink-0 rounded-full" style="background:#2563eb;"></span>
                                                                        <div>
                                                                            <h4 class="text-lg font-bold leading-tight text-slate-900">Supplier Quotations</h4>
                                                                            <!--<p class="mt-0.5 text-[11px] text-slate-500">Review competitive bids, adjust allocation quantities, and finalize order authorization.</p>-->
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Clean Item Switcher Pills --}}
                                                                <div class="flex flex-wrap items-center gap-2.5 px-6 pb-6">
                                                                    <template x-for="(it, idx) in items" :key="'pill-' + it.id">
                                                                        <button
                                                                            type="button"
                                                                            @click="activeItemIdx = idx"
                                                                            class="inline-flex items-center gap-2 rounded-md px-3.5 py-2 text-xs font-semibold transition"
                                                                            :style="activeItemIdx === idx
                                                                                ? 'border:1.5px solid #2563eb;background:#ffffff;color:#2563eb;'
                                                                                : 'border:1px solid #e2e8f0;background:#ffffff;color:#475569;'"
                                                                        >
                                                                            <span
                                                                                class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                                                                                :style="(it.status === 'Approved' || (isReviewable() && itemAllocationReady(it)))
                                                                                    ? 'background:#16a34a;color:#ffffff;'
                                                                                    : (it.status === 'Rejected'
                                                                                        ? 'background:#dc2626;color:#ffffff;'
                                                                                        : (it.status === 'Returned'
                                                                                            ? 'background:#d97706;color:#ffffff;'
                                                                                            : (activeItemIdx === idx ? 'background:#dbeafe;color:#2563eb;' : 'background:#f1f5f9;color:#475569;')))"
                                                                                x-text="(it.status === 'Approved' || (isReviewable() && itemAllocationReady(it))) ? '✓' : (it.status === 'Rejected' ? '✕' : (it.status === 'Returned' ? '↺' : (idx + 1)))"
                                                                            ></span>
                                                                            <span class="max-w-[180px] truncate" x-text="'Item ' + (idx + 1) + ': ' + it.name"></span>
                                                                            <span
                                                                                class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                                                                :style="activeItemIdx === idx
                                                                                    ? 'background:#eff6ff;color:#2563eb;'
                                                                                    : 'background:#f1f5f9;color:#475569;'"
                                                                                x-text="it.qty + ' ' + it.uom"
                                                                            ></span>
                                                                        </button>
                                                                    </template>
                                                                </div>

                                                                {{-- Item Header Row --}}
                                                                <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-1 pb-4">
                                                                    <div>
                                                                        <div class="flex flex-wrap items-center gap-2 text-[15px] font-bold text-slate-900">
                                                                            <span x-text="'Item ' + (activeItemIdx + 1) + ': ' + currentItem().name"></span>
                                                                            <span
                                                                                x-show="currentItem().isSoleSource"
                                                                                class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                                                                style="background:#fffbeb;border-color:#fde68a;color:#b45309;"
                                                                            >Sole Source</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex flex-wrap items-center gap-3 text-xs">
                                                                        <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                                                            <span>Total Requested:</span>
                                                                            <span class="font-semibold text-slate-900" x-text="currentItem().qty + ' Qty'"></span>
                                                                        </span>
                                                                        <template x-if="['Approved', 'Rejected', 'Returned'].includes(currentItem().status)">
                                                                            <span
                                                                                class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-semibold"
                                                                                :style="currentItem().status === 'Approved'
                                                                                    ? 'background:#ecfdf5;border-color:#a7f3d0;color:#059669;'
                                                                                    : (currentItem().status === 'Rejected'
                                                                                        ? 'background:#fef2f2;border-color:#fecaca;color:#dc2626;'
                                                                                        : 'background:#fff7ed;border-color:#fed7aa;color:#c2410c;')"
                                                                            >
                                                                                <span
                                                                                    class="h-1.5 w-1.5 rounded-full"
                                                                                    :style="currentItem().status === 'Approved' ? 'background:#10b981;' : (currentItem().status === 'Rejected' ? 'background:#dc2626;' : 'background:#ea580c;')"
                                                                                ></span>
                                                                                <span x-text="currentItem().status"></span>
                                                                            </span>
                                                                        </template>
                                                                        <div
                                                                            x-show="canAct && isReviewable() && currentItem().status !== 'Approved' && currentItem().status !== 'Rejected' && currentItem().status !== 'Returned'"
                                                                            class="group relative inline-flex"
                                                                        >
                                                                            <button
                                                                                type="button"
                                                                                @click="openReasonModal('return', 'single')"
                                                                                :disabled="busy"
                                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition disabled:opacity-45"
                                                                                style="background-color:#ffffff;border-color:#fed7aa;color:#c2410c;"
                                                                                onmouseenter="this.style.backgroundColor='#fff7ed';this.style.borderColor='#fdba74';"
                                                                                onmouseleave="this.style.backgroundColor='#ffffff';this.style.borderColor='#fed7aa';"
                                                                                aria-label="Return item for revision"
                                                                            >
                                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                                                            </button>
                                                                            <span
                                                                                class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-2 -translate-x-1/2 translate-y-1 whitespace-nowrap rounded-lg px-2.5 py-1 text-[11px] font-medium opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                                                                style="background:#0f172a;color:#ffffff;"
                                                                            >
                                                                                Return item for revision
                                                                                <span class="absolute left-1/2 top-full -mt-1 h-2 w-2 -translate-x-1/2 rotate-45" style="background:#0f172a;"></span>
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            x-show="canAct && isReviewable() && currentItem().status !== 'Approved' && currentItem().status !== 'Rejected'"
                                                                            class="group relative inline-flex"
                                                                        >
                                                                            <button
                                                                                type="button"
                                                                                @click="openReasonModal('reject', 'single')"
                                                                                :disabled="busy"
                                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition disabled:opacity-45"
                                                                                style="background-color:#ffffff;border-color:#ffe4e6;color:#e11d48;"
                                                                                onmouseenter="this.style.backgroundColor='#fff1f2';this.style.borderColor='#fecdd3';"
                                                                                onmouseleave="this.style.backgroundColor='#ffffff';this.style.borderColor='#ffe4e6';"
                                                                                aria-label="Reject item"
                                                                            >
                                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                            </button>
                                                                            <span
                                                                                class="pointer-events-none absolute bottom-full right-0 z-30 mb-2 translate-y-1 whitespace-nowrap rounded-lg px-2.5 py-1 text-[11px] font-medium opacity-0 shadow-lg transition-all duration-150 group-hover:translate-y-0 group-hover:opacity-100"
                                                                                style="background:#0f172a;color:#ffffff;"
                                                                            >
                                                                                Reject item
                                                                                <span class="absolute right-3 top-full -mt-1 h-2 w-2 rotate-45" style="background:#0f172a;"></span>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Sole Source Justification Banner --}}
                                                                <template x-if="currentItem().isSoleSource && currentItem().soleSourceReason">
                                                                    <div class="mx-6 mb-4 rounded-xl border px-4 py-2.5 text-xs" style="border-color:#fde68a;background:#fffbeb;color:#92400e;">
                                                                        <span class="font-bold">Sole Source justification:</span>
                                                                        <span x-text="currentItem().soleSourceReason"></span>
                                                                    </div>
                                                                </template>

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
                                                                            <template x-for="offer in currentItem().offers" :key="offer.id">
                                                                                <tr class="transition hover:bg-slate-50/40" :class="offer.checked ? 'bg-blue-50/20' : ''">
                                                                                    <td class="py-4 pl-6 pr-3 align-middle">
                                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                                            <span class="text-[13px] font-bold text-slate-900" x-text="offer.supplier"></span>
                                                                                            <span
                                                                                                x-show="isLowestOffer(currentItem(), offer) && !offer.blacklisted"
                                                                                                class="rounded-md border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                                                                                style="background:#ecfdf5;border-color:#a7f3d0;color:#059669;"
                                                                                            >
                                                                                                Lowest Price
                                                                                            </span>
                                                                                            <span x-show="offer.blacklisted" class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" style="background:#ffe4e6;color:#be123c;">Blacklisted</span>
                                                                                            <span x-show="!offer.blacklisted && offer.caution" class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" style="background:#fef3c7;color:#92400e;">Caution</span>
                                                                                        </div>
                                                                                    </td>
                                                                                    <td class="px-3 py-4 align-middle text-[13px] font-medium text-slate-800">
                                                                                        <span x-text="offer.brand"></span>
                                                                                        <span x-show="offer.brandAvoid" class="ml-1 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" style="background:#ffe4e6;color:#be123c;">Avoid</span>
                                                                                    </td>
                                                                                    <td class="px-3 py-4 align-middle">
                                                                                        <span
                                                                                            class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600"
                                                                                            x-text="offer.unit"
                                                                                        ></span>
                                                                                    </td>
                                                                                    <td class="px-3 py-4 text-center align-middle">
                                                                                        <div class="inline-flex items-center gap-1.5">
                                                                                            <button
                                                                                                type="button"
                                                                                                @click="stepOfferQty(currentItem(), offer, -1)"
                                                                                                :disabled="!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected' || (parseInt(offer.approvedQty, 10) || 0) <= 1"
                                                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition disabled:cursor-not-allowed"
                                                                                                :style="(!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected' || (parseInt(offer.approvedQty, 10) || 0) <= 1)
                                                                                                    ? 'background:#f8fafc;border-color:#e2e8f0;color:#cbd5e1;'
                                                                                                    : 'background:#eff6ff;border-color:#93c5fd;color:#0025cc;'"
                                                                                                aria-label="Decrease quantity"
                                                                                            >
                                                                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                                                                            </button>
                                                                                            <input
                                                                                                type="number"
                                                                                                min="1"
                                                                                                :max="maxOfferUnits(currentItem(), offer)"
                                                                                                step="1"
                                                                                                x-model="offer.approvedQty"
                                                                                                @input="onQtyInput(currentItem(), offer)"
                                                                                                :disabled="!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected'"
                                                                                                placeholder="0"
                                                                                                class="h-8 w-12 rounded-lg border text-center text-xs font-semibold tabular-nums outline-none focus:border-blue-500 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                                                                :style="(!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected')
                                                                                                    ? 'background:#ffffff;border-color:#e2e8f0;color:#64748b;cursor:not-allowed;'
                                                                                                    : 'background:#ffffff;border-color:#93c5fd;color:#0f172a;'"
                                                                                            >
                                                                                            <button
                                                                                                type="button"
                                                                                                @click="stepOfferQty(currentItem(), offer, 1)"
                                                                                                :disabled="!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected' || (parseInt(offer.approvedQty, 10) || 0) >= maxOfferUnits(currentItem(), offer)"
                                                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition disabled:cursor-not-allowed"
                                                                                                :style="(!canAct || !offer.checked || !isReviewable() || currentItem().status === 'Approved' || currentItem().status === 'Rejected' || (parseInt(offer.approvedQty, 10) || 0) >= maxOfferUnits(currentItem(), offer))
                                                                                                    ? 'background:#f8fafc;border-color:#e2e8f0;color:#cbd5e1;'
                                                                                                    : 'background:#eff6ff;border-color:#93c5fd;color:#0025cc;'"
                                                                                                aria-label="Increase quantity"
                                                                                            >
                                                                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                                                                                            </button>
                                                                                        </div>
                                                                                    </td>
                                                                                    <td class="px-3 py-4 text-right align-middle text-[13px] font-semibold tabular-nums text-slate-900" x-text="formatPeso(offer.unitCost)"></td>
                                                                                    <td
                                                                                        class="px-3 py-4 text-right align-middle text-[13px] font-semibold tabular-nums"
                                                                                        :class="offerAmount(offer) > 0 ? 'text-slate-900' : 'text-slate-400'"
                                                                                        x-text="formatPeso(offerAmount(offer))"
                                                                                    ></td>
                                                                                    <td class="py-4 pl-3 pr-6 text-center align-middle">
                                                                                        <input
                                                                                            type="checkbox"
                                                                                            :checked="offer.checked"
                                                                                            @change="onToggleOffer(currentItem(), offer, $event.target.checked)"
                                                                                            :disabled="isOfferCheckboxDisabled(currentItem(), offer)"
                                                                                            :title="(!offer.checked && itemAllocationFull(currentItem())) ? 'Requested quantity already reached. Lower an allocated qty or uncheck an offer to select this row.' : 'Select offer for approval'"
                                                                                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-40"
                                                                                        >
                                                                                    </td>
                                                                                </tr>
                                                                            </template>
                                                                        </tbody>
                                                                    </table>
                                                                </div>

                                                                {{-- Allocation Pill + Estimated Total --}}
                                                                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-[#f8fafc] px-6 py-3.5 text-xs">
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium"
                                                                        :style="allocationBadgeStyle(currentItem())"
                                                                    >
                                                                        <template x-if="itemAllocationReady(currentItem())">
                                                                            <svg
                                                                                class="h-3.5 w-3.5 shrink-0"
                                                                                fill="none"
                                                                                viewBox="0 0 24 24"
                                                                                stroke="currentColor"
                                                                                stroke-width="2.2"
                                                                            >
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                            </svg>
                                                                        </template>
                                                                        <template x-if="!itemAllocationReady(currentItem())">
                                                                            <span
                                                                                class="h-2 w-2 shrink-0 rounded-full"
                                                                                :style="itemAllocatedUnits(currentItem()) <= 0
                                                                                    ? 'background:#94a3b8;'
                                                                                    : (itemAllocatedUnits(currentItem()) > Number(currentItem().qty || 1) + 0.0001
                                                                                        ? 'background:#ea580c;'
                                                                                        : 'background:#d97706;')"
                                                                            ></span>
                                                                        </template>
                                                                        <span>Allocated:</span>
                                                                        <span class="font-bold" :style="itemAllocatedUnits(currentItem()) <= 0 ? 'color:#0f172a;' : ''" x-text="itemAllocatedUnits(currentItem())"></span>
                                                                        <span x-text="'/ ' + currentItem().qty + ' ' + (currentItem().uom || 'unit')"></span>
                                                                        <span class="ml-0.5" :style="itemAllocatedUnits(currentItem()) <= 0 ? 'color:#94a3b8;' : 'opacity:0.85;'" x-text="allocationStatusNote(currentItem())"></span>
                                                                    </span>
                                                                    <div class="flex items-baseline gap-1.5">
                                                                        <span class="text-xs font-medium text-slate-600">Estimated total:</span>
                                                                        <span class="text-sm font-bold tabular-nums text-slate-900" x-text="formatPeso(itemEstimatedTotal(currentItem()))"></span>
                                                                    </div>
                                                                </div>

                                                                <template x-if="currentItem().reviewNote">
                                                                    <div class="border-t border-red-100 px-6 py-2.5 text-xs" style="background:#fef2f2;color:#991b1b;">
                                                                        <span class="font-semibold">Admin note:</span> <span x-text="currentItem().reviewNote"></span>
                                                                    </div>
                                                                </template>

                                                                {{-- Pager + Single Primary Approve CTA (inside Supplier Quotations card) --}}
                                                                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-white px-6 py-4">
                                                                    <div class="flex items-center gap-3">
                                                                        <button
                                                                            type="button"
                                                                            @click="if (activeItemIdx > 0) activeItemIdx--"
                                                                            :disabled="activeItemIdx === 0"
                                                                            title="Previous item"
                                                                            aria-label="Previous item"
                                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-45"
                                                                        >
                                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                                                                            </svg>
                                                                        </button>
                                                                        <span class="text-xs font-semibold text-slate-700" x-text="'Item ' + (activeItemIdx + 1) + ' of ' + items.length"></span>
                                                                        <button
                                                                            type="button"
                                                                            @click="if (activeItemIdx < items.length - 1) activeItemIdx++"
                                                                            :disabled="activeItemIdx >= items.length - 1"
                                                                            title="Next item"
                                                                            aria-label="Next item"
                                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white transition hover:opacity-90 disabled:cursor-not-allowed"
                                                                            :style="activeItemIdx >= items.length - 1 ? 'background:#e2e8f0;color:#94a3b8;' : 'background:#2563eb;color:#ffffff;'"
                                                                        >
                                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                                                            </svg>
                                                                        </button>
                                                                    </div>

                                                                    <div x-show="canAct && isReviewable()" class="flex flex-wrap items-center gap-3.5">
                                                                        <div class="text-right text-xs">
                                                                            <span class="font-semibold text-slate-700" x-text="readyPendingItems().length + ' of ' + pendingItems().length + ' item' + (pendingItems().length === 1 ? '' : 's') + ' ready'"></span>
                                                                            <span
                                                                                x-show="readyPendingItems().length > 0 && readyPendingItems().length < pendingItems().length"
                                                                                class="ml-1 font-medium"
                                                                                style="color: #dc2626;"
                                                                                x-text="'(' + (pendingItems().length - readyPendingItems().length) + ' unselected item' + ((pendingItems().length - readyPendingItems().length) === 1 ? '' : 's') + ' will be rejected)'"
                                                                            ></span>
                                                                        </div>
                                                                        <button
                                                                            type="button"
                                                                            @click="openFinalizeApproveModal()"
                                                                            :disabled="busy || readyPendingItems().length === 0"
                                                                            class="rounded-md px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-65"
                                                                            style="background-color: #10b981;"
                                                                            x-text="readyPendingItems().length === pendingItems().length && pendingItems().length > 0
                                                                                ? ('Accept all (' + pendingItems().length + ' of ' + pendingItems().length + ')')
                                                                                : ('Accept selected (' + readyPendingItems().length + ' of ' + pendingItems().length + ')')"
                                                                        ></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- Inline Attachments Strip (with paperclip & file icons, matches Image 2) --}}
                                                    <div class="flex flex-wrap items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs shadow-sm">
                                                        <span class="inline-flex items-center gap-1.5 font-bold text-slate-700">
                                                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                                                            </svg>
                                                            <span>Attachments:</span>
                                                        </span>
                                                        <template x-if="attachments.length === 0">
                                                            <span class="text-slate-400">No files attached</span>
                                                        </template>
                                                        <template x-for="(att, aIdx) in attachments" :key="aIdx">
                                                            <a
                                                                :href="att.url"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 font-medium transition hover:bg-blue-100"
                                                                style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"
                                                            >
                                                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                                                </svg>
                                                                <span class="max-w-[200px] truncate" x-text="att.name"></span>
                                                                <span class="text-[10px] text-blue-500" x-text="'(' + att.sizeKb + ' KB)'"></span>
                                                            </a>
                                                        </template>
                                                    </div>

                                                    {{-- Compact 1-Row Admin Signature Bar --}}
                                                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                                            <div class="flex flex-wrap items-center gap-3">
                                                                <span class="text-xs font-bold text-slate-800">Admin signature:</span>
                                                                <template x-if="adminSignature">
                                                                    <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1">
                                                                        <img :src="adminSignature" alt="Admin signature" class="h-6 max-w-[90px] object-contain">
                                                                        <span class="rounded px-1.5 py-0.5 text-[11px] font-semibold" style="background:#ecfdf5;color:#059669;" x-text="isReviewable() ? '✓ Ready' : '✓ Signed'"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!adminSignature">
                                                                    <span class="text-xs font-semibold" style="color: #dc2626;" x-text="isReviewable() ? 'Required to accept' : 'Not signed'"></span>
                                                                </template>
                                                            </div>

                                                            <div x-show="canAct && isReviewable()" class="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    @click="toggleSigPanel()"
                                                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                                    x-text="sigPanelOpen ? 'Hide signature pad' : (adminSignature ? 'Change / Draw signature' : 'Draw or attach signature')"
                                                                ></button>
                                                            </div>
                                                        </div>

                                                        <div x-show="sigPanelOpen && canAct && isReviewable()" class="mt-3 border-t border-slate-100 pt-3">
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
                                                                    title="Previous supplier"
                                                                    aria-label="Previous supplier"
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-45"
                                                                >
                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                                                                    </svg>
                                                                </button>
                                                                <span
                                                                    class="text-xs font-semibold text-slate-700"
                                                                    x-text="'Supplier ' + (activeSupplierIdx + 1) + ' of ' + supplierAtpPages().length + ' · ' + activeSupplierPage().supplierName"
                                                                ></span>
                                                                <button
                                                                    type="button"
                                                                    @click="if (activeSupplierIdx < supplierAtpPages().length - 1) activeSupplierIdx++"
                                                                    :disabled="activeSupplierIdx >= supplierAtpPages().length - 1"
                                                                    title="Next supplier"
                                                                    aria-label="Next supplier"
                                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white transition hover:opacity-90 disabled:cursor-not-allowed"
                                                                    :style="activeSupplierIdx >= supplierAtpPages().length - 1 ? 'background:#e2e8f0;color:#94a3b8;' : 'background:#2563eb;color:#ffffff;'"
                                                                >
                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                                                    </svg>
                                                                </button>
                                                            </div>

                                                            <div class="mx-auto w-full rounded-xl border border-slate-200 bg-white px-8 py-8 text-[13px] leading-tight text-black">
                                                                {{-- HEADER --}}
                                                                <div class="relative text-center">
                                                                    <div class="text-lg font-bold">STI COLLEGE ORMOC, INC.</div>
                                                                    <div class="text-xs">Centrum Mall, Aviles Street, Ormoc City</div>
                                                                    <div class="mt-2 text-xl font-bold tracking-wide">AUTHORITY TO PURCHASE</div>

                                                                    <div class="absolute right-0 top-0 text-left text-sm">
                                                                        <div class="flex items-end gap-2" style="color:#dc2626;">
                                                                            <strong class="font-semibold">No.</strong>
                                                                            <span class="inline-block min-w-[9rem] border-b border-red-200 px-1 text-center font-semibold" x-text="activeSupplierPage().atpNumber || ''"></span>
                                                                        </div>
                                                                        <div class="mt-2 flex items-end gap-2">
                                                                            <strong>Date</strong>
                                                                            <span class="inline-block min-w-[7rem] border-b border-black px-1 text-center" x-text="atpDate"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- SUPPLIER --}}
                                                                <div class="mt-10">
                                                                    <strong>To:</strong>
                                                                    <span class="ml-1" x-text="activeSupplierPage().supplierName"></span>
                                                                </div>

                                                                <p class="mt-6">
                                                                    Please deliver to bearer the following items chargeable to our account.
                                                                </p>

                                                                {{-- ITEMS TABLE --}}
                                                                <table class="mt-5 w-full table-fixed border-collapse border border-black text-sm">
                                                                    <colgroup>
                                                                        <col style="width: 10%">
                                                                        <col style="width: 10%">
                                                                        <col style="width: 52%">
                                                                        <col style="width: 14%">
                                                                        <col style="width: 14%">
                                                                    </colgroup>
                                                                    <thead>
                                                                        <tr>
                                                                            <th class="border border-black p-2 text-center font-bold">Quantity</th>
                                                                            <th class="border border-black p-2 text-center font-bold">Unit</th>
                                                                            <th class="border border-black p-2 text-left font-bold">Description</th>
                                                                            <th class="border border-black p-2 text-center font-bold">Unit Price</th>
                                                                            <th class="border border-black p-2 text-center font-bold">Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <template x-for="(line, lIdx) in activeSupplierPage().lines" :key="lIdx">
                                                                            <tr>
                                                                                <td class="h-8 border border-black text-center" x-text="line.qty > 0 ? line.qty : ''"></td>
                                                                                <td class="border border-black text-center" x-text="line.unit"></td>
                                                                                <td class="border border-black px-2 text-left" x-text="line.description"></td>
                                                                                <td class="border border-black px-2 text-right tabular-nums" x-text="formatPeso(line.unitCost)"></td>
                                                                                <td class="border border-black px-2 text-right tabular-nums" x-text="line.amount > 0 ? formatPeso(line.amount) : ''"></td>
                                                                            </tr>
                                                                        </template>
                                                                        <tr x-show="activeSupplierPage().lines.length === 0">
                                                                            <td colspan="5" class="h-9 border border-black px-3 text-center text-xs text-slate-400">No offers selected for this supplier yet.</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="3" class="h-8 border border-black">&nbsp;</td>
                                                                            <td class="border border-black px-2 text-right font-bold">TOTAL</td>
                                                                            <td class="border border-black px-2 text-right font-bold tabular-nums" x-text="activeSupplierPage().lines.length > 0 ? formatPeso(activeSupplierPage().totalAmount || 0) : ''"></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>

                                                                {{-- BOTTOM SIGNATURES --}}
                                                                <div class="mt-10 grid grid-cols-2 items-start gap-10">
                                                                    <div class="w-full max-w-sm">
                                                                        <div class="font-semibold leading-6">RECEIVED BY:</div>
                                                                        <div class="relative mt-6 flex min-h-[1.75rem] w-full items-center justify-center border-b border-black pb-1 text-center">
                                                                            <span class="signature-name-stack relative inline-flex max-w-full items-center justify-center">
                                                                                <img
                                                                                    x-show="purchaserSignature"
                                                                                    :src="purchaserSignature"
                                                                                    alt="Received by"
                                                                                    class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-10 max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                                                                                >
                                                                                <span class="signature-name relative z-[1] text-xs font-normal text-slate-900" x-text="ownerName"></span>
                                                                            </span>
                                                                        </div>
                                                                        <div class="mt-6 flex items-end gap-2">
                                                                            <span class="shrink-0 whitespace-nowrap pb-1 text-xs">Reference P.O. No.</span>
                                                                            <div class="min-w-0 flex-1 border-b border-black pb-1 text-sm">&nbsp;</div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="w-full max-w-xs justify-self-end text-left">
                                                                        <div class="leading-6">Authorized by</div>
                                                                        <div class="relative mt-6 flex min-h-[1.75rem] w-full items-center justify-center border-b border-black pb-1 text-center">
                                                                            <span class="signature-name-stack relative inline-flex max-w-full items-center justify-center">
                                                                                <img
                                                                                    x-show="adminSignature"
                                                                                    :src="adminSignature"
                                                                                    alt="Authorized by"
                                                                                    class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-10 max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain object-center"
                                                                                >
                                                                                <span class="signature-name relative z-[1] text-xs font-normal text-slate-900" x-text="adminName"></span>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                                {{-- TAB 3: REQUISITION SLIP PREVIEW --}}
                                                <div x-show="activeTab === 'ris'" class="rounded-xl border border-slate-200 bg-white p-5">
                                                    <div class="border-l-4 border-blue-600 pl-3">
                                                        <h4 class="text-sm font-bold text-slate-900">Requisition Slip</h4>
                                                        <p class="text-xs text-slate-500">The shared request slip includes every requested item and its signatories.</p>
                                                    </div>

                                                    <div class="mt-4 overflow-x-auto">
                                                        <div class="ris-original-form" style="min-height: 0; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px 32px;">
                                                            <div class="ris-document-header">
                                                                <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                                                                <div class="ris-document-title">REQUISITION SLIP</div>
                                                                <div class="ris-number-area">
                                                                    <span class="ris-number-label">No.</span>
                                                                    <span class="ris-number-line ris-value-line" x-text="linkedRisNumber || ''"></span>
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
                                                                    <template x-for="(r, rIdx) in risPreviewRows()" :key="rIdx">
                                                                        <tr>
                                                                            <template x-if="!r.sameItem">
                                                                                <td :rowspan="r.itemSpan > 1 ? r.itemSpan : null" x-text="r.itemName"></td>
                                                                            </template>
                                                                            <td class="text-center" x-text="r.brandLabel"></td>
                                                                            <td class="text-center" x-text="r.unit"></td>
                                                                            <template x-if="!r.sameSupplier">
                                                                                <td :rowspan="r.supplierSpan > 1 ? r.supplierSpan : null" x-text="r.supplierDisplay"></td>
                                                                            </template>
                                                                            <template x-if="!r.sameItem">
                                                                                <td :rowspan="r.itemSpan > 1 ? r.itemSpan : null" class="text-center" x-text="r.requestedDisplay"></td>
                                                                            </template>
                                                                            <template x-if="!r.sameItem">
                                                                                <td :rowspan="r.itemSpan > 1 ? r.itemSpan : null" class="text-center" x-text="r.issuedDisplay"></td>
                                                                            </template>
                                                                            <td class="text-right tabular-nums" x-text="r.unitCostDisplay"></td>
                                                                            <td class="text-right tabular-nums" x-text="r.amountDisplay"></td>
                                                                        </tr>
                                                                    </template>
                                                                    <tr x-show="risPreviewRows().length === 0">
                                                                        <td colspan="8" class="text-center text-slate-400">No items to display.</td>
                                                                    </tr>
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr>
                                                                        <td colspan="7" class="ris-total-label">TOTAL AMOUNT</td>
                                                                        <td class="ris-total-value" x-text="risPreviewGrandTotal() > 0 ? formatPeso(risPreviewGrandTotal()) : ' '"></td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>

                                                            <div class="ris-purpose-area">
                                                                <div class="ris-purpose-lined-wrap">
                                                                    <div class="ris-purpose-label">PURPOSE</div>
                                                                    <div class="ris-purpose-input" x-text="purpose || ' '"></div>
                                                                </div>
                                                            </div>

                                                            <div class="ris-signatures">
                                                                <div class="ris-signature-column">
                                                                    <div class="ris-signature-label">Requested by:</div>
                                                                    <div class="ris-signature-line ris-value-line relative flex items-center justify-center">
                                                                        <span class="signature-name-stack relative inline-flex max-w-full items-center justify-center">
                                                                            <img
                                                                                x-show="purchaserSignature"
                                                                                :src="purchaserSignature"
                                                                                alt="Requested by"
                                                                                class="signature-image pointer-events-none absolute left-1/2 top-1/2 z-10 max-h-[38px] w-auto max-w-[92%] -translate-x-1/2 -translate-y-1/2 object-contain"
                                                                            >
                                                                            <span class="signature-name relative z-[1] text-xs font-normal text-slate-900" x-text="ownerName"></span>
                                                                        </span>
                                                                    </div>
                                                                    <div class="ris-date-label">Date:</div>
                                                                    <div class="ris-date-line ris-value-line" x-text="atpDate"></div>
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
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- TELEPORTED REASON / VALIDATION MODAL (Return for revision / Reject) --}}
                                <template x-teleport="body">
                                    <div
                                        x-cloak
                                        x-show="reasonModal.open"
                                        @keydown.escape.window="if (reasonModal.open && !busy) reasonModal.open = false"
                                        class="fixed inset-0 z-[270] flex items-center justify-center bg-slate-900/45 p-4"
                                        @click.self="if (!busy) reasonModal.open = false"
                                    >
                                        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="flex items-start gap-3.5 border-b border-slate-100 px-6 py-4">
                                                <div
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                                    :style="reasonModal.type === 'reject' ? 'background:#fef2f2;color:#dc2626;' : 'background:#fffbeb;color:#d97706;'"
                                                >
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h3
                                                        class="text-base font-bold"
                                                        :style="reasonModal.type === 'reject' ? 'color:#991b1b;' : 'color:#92400e;'"
                                                        x-text="reasonModal.title"
                                                    ></h3>
                                                    <p class="mt-1 text-xs leading-relaxed text-slate-500" x-text="reasonModal.subtitle"></p>
                                                </div>
                                            </div>
                                            <div class="space-y-3 px-6 py-4">
                                                <template x-if="reasonModal.itemLabel">
                                                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs">
                                                        <span class="truncate font-bold text-slate-900" x-text="reasonModal.itemLabel"></span>
                                                        <span class="shrink-0 font-semibold text-slate-500" x-text="reasonModal.itemQtyLabel"></span>
                                                    </div>
                                                </template>
                                                <template x-if="reasonModal.warning">
                                                    <div class="rounded-xl border px-3.5 py-2.5 text-xs font-medium" style="background:#fffbeb;border-color:#fde68a;color:#92400e;" x-text="reasonModal.warning"></div>
                                                </template>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">
                                                        <span x-text="reasonModal.type === 'reject' ? 'Rejection reason' : 'Revision note for Purchaser'"></span>
                                                        <span style="color:#dc2626;">*</span>
                                                    </label>
                                                    <textarea
                                                        x-model="reasonModal.note"
                                                        @input="if (reasonModal.error && String(reasonModal.note || '').trim()) reasonModal.error = ''"
                                                        rows="3"
                                                        maxlength="1000"
                                                        placeholder="Enter clear instructions or reason..."
                                                        class="w-full rounded-xl border bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                                                        :style="reasonModal.error ? 'border-color:#dc2626;' : 'border-color:#cbd5e1;'"
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
                                                <p class="mt-0.5 text-xs text-slate-500">Review the selected items and supplier totals to accept this canvass and generate the RIS &amp; ATP(s) for approval.</p>
                                            </div>
                                            <div class="max-h-80 space-y-3 overflow-y-auto px-6 py-4">
                                                <div>
                                                    <p class="mb-1.5 text-[11px] font-bold uppercase tracking-wider" style="color: #166534;" x-text="'Will be Accepted (' + approveModal.summary.length + ')'"></p>
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
                                                    <span>Total estimated amount</span>
                                                    <span class="text-base font-extrabold tabular-nums text-slate-900" x-text="formatPeso(approveModal.grandTotal)"></span>
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
                                                    x-text="busy ? 'Accepting...' : approveModal.confirmLabel"
                                                ></button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- TELEPORTED CONFIRM CLOSE MODAL (when unsaved changes exist) --}}
                                <template x-teleport="body">
                                    <div
                                        x-cloak
                                        x-show="closeConfirmOpen"
                                        @keydown.escape.window="if (closeConfirmOpen) closeConfirmOpen = false"
                                        class="fixed inset-0 z-[275] flex items-center justify-center bg-slate-900/45 p-4"
                                        @click.self="closeConfirmOpen = false"
                                    >
                                        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                            <div class="flex items-start gap-3.5 px-6 pt-6 pb-4">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background:#fef2f2;color:#dc2626;">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h3 class="text-base font-bold text-slate-900">Close review workspace?</h3>
                                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                                                        You have unsaved offer selections, quantities, or signature changes in this review. Closing now will leave them unsubmitted.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                                                <button
                                                    type="button"
                                                     @click="closeConfirmOpen = false"
                                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                                                >
                                                    Continue reviewing
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="confirmCloseWorkspace()"
                                                    class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90"
                                                    style="background:#dc2626;"
                                                >
                                                    Close anyway
                                                </button>
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
        adminName: boot.adminName || '',
        status: boot.status,
        submittedAt: boot.submittedAt,
        atpDate: boot.atpDate || '',
        purpose: boot.purpose || '',
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
        reasonModal: { open: false, type: 'return', scope: 'all', title: '', subtitle: '', itemLabel: '', itemQtyLabel: '', warning: '', confirmLabel: '', note: '', error: '' },
        approveModal: { open: false, all: false, title: '', confirmLabel: '', summary: [], unselected: [], unselectedNote: '', grandTotal: 0, payloadItems: [] },
        closeConfirmOpen: false,
        initialSnapshot: '',

        currentStateSnapshot() {
            return JSON.stringify({
                sig: this.adminSignature || '',
                items: (this.items || []).map(it => ({
                    id: it.id,
                    status: it.status,
                    offers: (it.offers || []).map(o => ({
                        id: o.id,
                        checked: Boolean(o.checked),
                        qty: String(o.approvedQty ?? ''),
                    })),
                })),
            });
        },

        hasUnsavedChanges() {
            if (!this.isReviewable() || !this.canAct) return false;
            return this.initialSnapshot !== '' && this.currentStateSnapshot() !== this.initialSnapshot;
        },

        requestCloseWorkspace() {
            if (this.hasUnsavedChanges()) {
                this.closeConfirmOpen = true;
                return;
            }
            this.open = false;
        },

        restoreFromSnapshot() {
            if (!this.initialSnapshot) return;
            try {
                const snap = JSON.parse(this.initialSnapshot);
                this.adminSignature = snap.sig || '';
                const byItemId = {};
                (snap.items || []).forEach(si => { byItemId[si.id] = si; });
                (this.items || []).forEach(it => {
                    const si = byItemId[it.id];
                    if (!si) return;
                    it.status = si.status;
                    const byOfferId = {};
                    (si.offers || []).forEach(so => { byOfferId[so.id] = so; });
                    (it.offers || []).forEach(o => {
                        const so = byOfferId[o.id];
                        if (!so) return;
                        o.checked = Boolean(so.checked);
                        o.approvedQty = String(so.qty ?? '');
                    });
                });
                this.activeTab = 'review';
                this.activeItemIdx = 0;
                this.feedbackMsg = '';
            } catch (e) {}
        },

        confirmCloseWorkspace() {
            this.restoreFromSnapshot();
            this.closeConfirmOpen = false;
            this.open = false;
        },

        init() {
            this.initialSnapshot = this.currentStateSnapshot();
            if (this.open) {
                this.ensureReviewStarted();
                this.$nextTick(() => {
                    if (this.sigPanelOpen && this.adminSignature) this.drawSigImage(this.adminSignature);
                });
            }
        },

        openWorkspace() {
            this.initialSnapshot = this.currentStateSnapshot();
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
            const parts = String(this.status || '').split(' - ');
            return parts[0] || this.status;
        },

        statusSubtext() {
            const parts = String(this.status || '').split(' - ');
            return parts.length > 1 ? parts.slice(1).join(' - ') : '';
        },

        statusBadgeStyle() {
            if (this.status === 'Approved' || this.status === 'Approved - by School Administrator' || this.status === 'Approved - by the President') return 'background:#ecfdf5;border-color:#a7f3d0;color:#047857;';
            if (this.status === 'Converted to RIS') return 'background:#f0fdfa;border-color:#99f6e4;color:#0f766e;';
            if (this.status === 'Reviewed - Awaiting for Approval') return 'background:#f3e8ff;border-color:#d8b4fe;color:#7e22ce;';
            if (this.status === 'Forwarded to President') return 'background:#eef2ff;border-color:#c7d2fe;color:#4338ca;';
            if (this.status === 'Rejected') return 'background:#fef2f2;border-color:#fecaca;color:#dc2626;';
            if (this.status === 'Reviewing - Awaiting for approval') return 'background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;';
            if (this.status === 'Awaiting for review') return 'background:#fffbeb;border-color:#fde68a;color:#b45309;';
            if (this.status === 'Returned') return 'background:#fff7ed;border-color:#fed7aa;color:#c2410c;';
            return 'background:#f1f5f9;border-color:#cbd5e1;color:#475569;';
        },

        statusDotColor() {
            if (this.status === 'Approved' || this.status === 'Approved - by School Administrator' || this.status === 'Approved - by the President') return '#10b981';
            if (this.status === 'Converted to RIS') return '#0d9488';
            if (this.status === 'Reviewed - Awaiting for Approval') return '#9333ea';
            if (this.status === 'Forwarded to President') return '#4f46e5';
            if (this.status === 'Rejected') return '#dc2626';
            if (this.status === 'Reviewing - Awaiting for approval') return '#2563eb';
            if (this.status === 'Awaiting for review') return '#d97706';
            if (this.status === 'Returned') return '#ea580c';
            return '#64748b';
        },

        itemAllocationFull(item) {
            if (!item) return false;
            return (this.itemAllocatedUnits(item) + 0.0001) >= Number(item.qty || 1);
        },

        itemHasEmptyCheckedOffer(item) {
            if (!item || !item.offers) return false;
            return item.offers.some(o => o.checked && (parseInt(o.approvedQty, 10) || 0) <= 0);
        },

        itemAllocationReady(item) {
            if (!item) return false;
            const alloc = this.itemAllocatedUnits(item);
            const req = Number(item.qty || 1);
            if (alloc <= 0 || alloc > req + 0.0001) return false;
            if (this.itemHasEmptyCheckedOffer(item)) return false;
            return true;
        },

        totalRequestedQty() {
            return (this.items || []).reduce((sum, it) => sum + Math.max(0, parseInt(it.qty, 10) || 0), 0);
        },

        allocationStatusNote(item) {
            if (!item) return '';
            const alloc = this.itemAllocatedUnits(item);
            const req = Number(item.qty || 1);
            const uom = item.uom || 'unit';
            if (alloc <= 0) {
                return `(Select offer(s) to approve up to ${req} ${uom})`;
            }
            if (this.itemHasEmptyCheckedOffer(item)) {
                return '(Enter qty ≥ 1 for all checked rows)';
            }
            if (alloc + 0.0001 < req) {
                const diff = Math.round((req - alloc) * 100) / 100;
                return `(Partial approval · ${diff} ${uom} below requested)`;
            }
            if (alloc > req + 0.0001) {
                const over = Math.round((alloc - req) * 100) / 100;
                return `(+${over} ${uom} over requested)`;
            }
            return '(Full requested quantity)';
        },

        allocationBadgeText(item) {
            if (!item) return '';
            const alloc = this.itemAllocatedUnits(item);
            const req = Number(item.qty || 1);
            const uom = item.uom || 'unit';
            return `Allocated: ${alloc} / ${req} ${uom} ${this.allocationStatusNote(item)}`;
        },

        isLowestOffer(item, offer) {
            if (!item || !offer || !item.offers || !item.offers.length) return false;
            const validOffers = item.offers.filter(o => !o.blacklisted && Number(o.unitCost || 0) > 0);
            if (!validOffers.length) return false;
            const effCost = o => Number(o.unitCost || 0) / Math.max(0.01, Number(o.pack || 1));
            const minEff = Math.min(...validOffers.map(effCost));
            return Math.abs(effCost(offer) - minEff) < 0.0001;
        },

        stepOfferQty(item, offer, delta) {
            if (!offer.checked) return;
            const cur = parseInt(offer.approvedQty, 10) || 0;
            const maxUnits = this.maxOfferUnits(item, offer);
            const next = Math.min(maxUnits, Math.max(1, cur + delta));
            offer.approvedQty = String(next);
            this.onQtyInput(item, offer);
        },

        allocationBadgeStyle(item) {
            if (!item) return 'background:#ffffff;border-color:#e2e8f0;color:#475569;';
            const alloc = this.itemAllocatedUnits(item);
            const req = Number(item.qty || 1);
            if (alloc <= 0) return 'background:#ffffff;border-color:#e2e8f0;color:#475569;';
            if (this.itemHasEmptyCheckedOffer(item)) return 'background:#fffbeb;border-color:#fcd34d;color:#b45309;';
            if (alloc > req + 0.0001) return 'background:#fff7ed;border-color:#fdba74;color:#c2410c;';
            if (alloc + 0.0001 < req) return 'background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;';
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
            if (!offer.checked && this.itemAllocationFull(item)) return true;
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
            if (this.itemAllocationFull(item)) {
                item.offers.forEach(o => {
                    if (o.id !== offer.id && o.checked && (parseInt(o.approvedQty, 10) || 0) <= 0) {
                        o.checked = false;
                        o.approvedQty = '';
                    }
                });
            }
        },

        onToggleOffer(item, offer, isChecked) {
            if (isChecked && this.itemAllocationFull(item) && !offer.checked) {
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
            let hasAnySelected = false;
            this.items.forEach(item => {
                if (item.status === 'Rejected') return;
                item.offers.forEach(o => {
                    const supName = String(o.supplier || '').trim();
                    const key = supName ? supName.toLowerCase() : ('id:' + o.supplierId);
                    if (!map.has(key)) {
                        map.set(key, {
                            supplierKey: key,
                            supplierId: o.supplierId,
                            supplierName: supName || 'Supplier',
                            lines: [],
                            totalAmount: 0,
                        });
                    }
                    if (o.checked) {
                        hasAnySelected = true;
                        const qty = Math.max(0, parseInt(o.approvedQty, 10) || 0);
                        const amt = qty * Number(o.unitCost || 0);
                        const group = map.get(key);
                        const brandText = String(o.brand || '').trim();
                        const descText = brandText && brandText !== '—' ? `${item.name} (${brandText})` : item.name;
                        group.lines.push({
                            qty: qty,
                            unit: o.unit || item.uom || 'pcs',
                            description: descText,
                            itemName: item.name,
                            requested: `${item.qty} ${item.uom}`,
                            brand: o.brand,
                            approvedQtyLabel: qty > 0 ? `${qty} ${o.unit}` : 'Quantity required',
                            unitCost: Number(o.unitCost || 0),
                            amount: amt,
                            statusLabel: item.status === 'Approved' ? 'Approved' : (qty > 0 ? 'Pending approval' : 'Quantity required'),
                        });
                        group.totalAmount += amt;
                    }
                });
            });
            let pages = Array.from(map.values());
            if (hasAnySelected) {
                pages = pages.filter(p => p.lines.length > 0);
            }
            if (this.activeSupplierIdx >= pages.length) {
                this.activeSupplierIdx = Math.max(0, pages.length - 1);
            }
            return pages;
        },

        activeSupplierPage() {
            const pages = this.supplierAtpPages();
            return pages[this.activeSupplierIdx] || { supplierName: '—', lines: [], totalAmount: 0 };
        },

        risPreviewRows() {
            const rawRows = [];
            (this.items || []).forEach(item => {
                if (item.status === 'Rejected') return;
                const itemName = String(item.name || '').trim();
                const nameKey = itemName.toLowerCase();
                const reqQty = Number(item.qty || 0);
                const selectedOffers = (item.offers || []).filter(o => o.checked);
                if (selectedOffers.length === 0) {
                    rawRows.push({
                        itemName,
                        nameKey,
                        brandLabel: '',
                        unit: item.uom || 'pcs',
                        supplierRaw: '',
                        supplierBase: '',
                        supplierKey: '',
                        requestedQty: reqQty,
                        issuedQty: 0,
                        unitCost: null,
                        amount: null,
                    });
                    return;
                }
                const totalIssued = selectedOffers.reduce((sum, o) => {
                    const q = Math.max(0, parseInt(o.approvedQty, 10) || 0);
                    return sum + (q * Number(o.pack || 1));
                }, 0);
                selectedOffers.forEach(o => {
                    const q = Math.max(0, parseInt(o.approvedQty, 10) || 0);
                    const brandBase = String(o.brand || '').trim();
                    const brandLabel = (brandBase && brandBase !== '—')
                        ? (q > 1 ? `${brandBase} ×${q}` : brandBase)
                        : (q > 1 ? `×${q}` : '');
                    const supRaw = String(o.supplier || '').trim();
                    const supBase = supRaw.split('·')[0].trim();
                    const supKey = (supBase || supRaw).toLowerCase();
                    const unitCost = Number(o.unitCost || 0);
                    const amount = q * unitCost;
                    rawRows.push({
                        itemName,
                        nameKey,
                        brandLabel,
                        unit: o.unit || item.uom || 'pcs',
                        supplierRaw: supRaw,
                        supplierBase: supBase,
                        supplierKey: supKey,
                        requestedQty: reqQty,
                        issuedQty: totalIssued,
                        unitCost,
                        amount,
                    });
                });
            });

            const fmtNum = (val) => {
                const n = Number(val || 0);
                return Math.abs(n - Math.round(n)) < 0.0001
                    ? String(Math.round(n))
                    : n.toFixed(2).replace(/\.?0+$/, '');
            };

            return rawRows.map((row, idx) => {
                const prev = idx > 0 ? rawRows[idx - 1] : null;
                const sameItem = row.nameKey !== '' && prev && prev.nameKey === row.nameKey;
                const sameSupplier = sameItem && row.supplierKey !== '' && prev && prev.supplierKey === row.supplierKey;

                let itemSpan = 1;
                if (row.nameKey !== '' && !sameItem) {
                    for (let i = idx + 1; i < rawRows.length; i++) {
                        if (rawRows[i].nameKey !== row.nameKey) break;
                        itemSpan++;
                    }
                }

                let supplierSpan = 1;
                if (row.supplierKey !== '' && !sameSupplier) {
                    for (let i = idx + 1; i < rawRows.length; i++) {
                        if (rawRows[i].nameKey !== row.nameKey || rawRows[i].supplierKey !== row.supplierKey) break;
                        supplierSpan++;
                    }
                }

                const supplierDisplay = supplierSpan > 1 && row.supplierBase ? row.supplierBase : row.supplierRaw;

                return {
                    itemName: row.itemName,
                    brandLabel: row.brandLabel,
                    unit: row.unit,
                    supplierDisplay,
                    requestedDisplay: fmtNum(row.requestedQty),
                    issuedDisplay: fmtNum(row.issuedQty),
                    unitCostDisplay: row.unitCost !== null ? this.formatPeso(row.unitCost) : '',
                    amount: row.amount,
                    amountDisplay: row.amount !== null ? this.formatPeso(row.amount) : '',
                    sameItem: Boolean(sameItem),
                    sameSupplier: Boolean(sameSupplier),
                    itemSpan,
                    supplierSpan,
                };
            });
        },

        risPreviewGrandTotal() {
            return (this.risPreviewRows() || []).reduce((sum, r) => sum + (Number(r.amount) || 0), 0);
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
                this.feedbackMsg = 'Select at least one supplier offer and enter an approved quantity (>= 1) on at least one item before approving.';
                return;
            }

            for (const it of pending) {
                if (this.itemHasEmptyCheckedOffer(it)) {
                    const idx = this.items.findIndex(x => x.id === it.id);
                    if (idx >= 0) this.activeItemIdx = idx;
                    this.feedbackOk = false;
                    this.feedbackMsg = `Item ${idx + 1} (${it.name}) has a checked supplier offer with 0/empty quantity. Enter an approved quantity (>= 1) or uncheck it before finalizing.`;
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
                    const allocUnits = this.itemAllocatedUnits(it);
                    const qtySummary = allocUnits + 0.0001 < Number(it.qty || 1)
                        ? `${allocUnits} of ${it.qty} ${it.uom} (partial)`
                        : `${allocUnits} ${it.uom}`;
                    const supNames = [...new Set(it.offers.filter(o => o.checked && parseInt(o.approvedQty, 10) > 0).map(o => o.supplier))];
                    summary.push({
                        itemId: it.id,
                        name: `Item ${idx + 1}: ${it.name}`,
                        detail: `${qtySummary} · ${supNames.join(', ')}`,
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
                    ? `Accept all ${summary.length} item(s)?`
                    : `Accept ${summary.length} of ${pending.length} item(s)?`,
                confirmLabel: 'Confirm & Accept Canvass',
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
                this.initialSnapshot = this.currentStateSnapshot();
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
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
                const checkedCount = (item.offers || []).filter(o => o.checked).length;
                const alloc = this.itemAllocatedUnits(item);
                const warning = checkedCount > 0
                    ? `This item currently has ${checkedCount} selected offer(s) (${alloc} / ${item.qty} ${item.uom} allocated). ${isReject ? 'Rejecting' : 'Returning'} it will discard those selections.`
                    : '';
                this.reasonModal = {
                    open: true,
                    type,
                    scope: 'single',
                    title: isReject ? `Reject "${item.name}"?` : `Return "${item.name}" for revision?`,
                    subtitle: isReject
                        ? 'Please confirm and provide a reason before rejecting this item.'
                        : 'Returning this item will send the canvass back to the Purchaser with this item flagged for revision.',
                    itemLabel: `Item ${this.activeItemIdx + 1}: ${item.name}`,
                    itemQtyLabel: `Total Requested: ${item.qty} Qty`,
                    warning,
                    confirmLabel: isReject ? 'Confirm Reject Item' : 'Confirm Return Item',
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
                itemLabel: '',
                itemQtyLabel: '',
                warning: '',
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
                url = type === 'return'
                    ? `${this.approveItemBaseUrl}/${item.id}/return`
                    : `${this.approveItemBaseUrl}/${item.id}/reject`;
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
                        item.status = type === 'return' ? 'Returned' : 'Rejected';
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
                this.initialSnapshot = this.currentStateSnapshot();
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
