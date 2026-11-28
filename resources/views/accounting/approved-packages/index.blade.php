@extends('layouts.accounting-layout')

@section('title', 'Request for Check')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')
@include('accounting.partials.flash')

@php
    $allPkgCount = $records->count();
    $forReleaseCount = $records->filter(fn ($r) => empty($r->check_released))->count();
    $releasedCount = $records->filter(fn ($r) => ! empty($r->check_released))->count();
    $defaultPkgFilter = $forReleaseCount > 0 ? 'for_release' : 'all';

    $pkgGroupBuckets = [];
    foreach ($records as $rec) {
        $risId = (int) ($rec->ris_id ?? 0);
        $reqNo = trim((string) ($rec->request_no ?? ''));
        $gKey = $risId > 0
            ? 'ris:'.$risId
            : ($reqNo !== '' ? 'pr:'.$reqNo : 'solo:'.((int) ($rec->rfc_id ?? 0)));
        if (! isset($pkgGroupBuckets[$gKey])) {
            $pkgGroupBuckets[$gKey] = [];
        }
        $pkgGroupBuckets[$gKey][] = $rec;
    }

    $pkgEntries = [];
    foreach ($pkgGroupBuckets as $gKey => $members) {
        if (count($members) > 1) {
            $first = $members[0];
            $total = count($members);
            $unreleased = 0;
            $ready = 0;
            $released = 0;
            $hasUrgent = false;
            $payees = [];
            $requesters = [];
            $atpCount = 0;
            $pendingReadyMembers = [];
            $readyOnlyMembers = [];
            foreach ($members as $m) {
                if (! empty($m->check_released)) {
                    $released++;
                } else {
                    $unreleased++;
                    $mSummary = [
                        'id' => (int) ($m->rfc_id ?? 0),
                        'ref' => $m->ref ?? ('RFC #'.$m->rfc_id),
                        'atp' => $m->atp_no ?: '—',
                        'payee' => $m->payee ?? '—',
                        'fund' => $m->fund_label ?? '',
                    ];
                    if (! empty($m->check_ready)) {
                        $ready++;
                        $readyOnlyMembers[] = $mSummary;
                    } else {
                        $pendingReadyMembers[] = $mSummary;
                    }
                }
                if (! empty($m->urgent_row)) {
                    $hasUrgent = true;
                }
                $p = trim((string) ($m->payee ?? ''));
                if ($p !== '' && $p !== '—') {
                    $payees[$p] = true;
                }
                $rq = trim((string) ($m->requester ?? ''));
                if ($rq !== '' && $rq !== '—') {
                    $requesters[$rq] = true;
                }
                if (! empty($m->atp_no)) {
                    $atpCount++;
                }
            }
            $payeeKeys = array_keys($payees);
            $requesterKeys = array_keys($requesters);
            $pkgEntries[] = (object) [
                'is_group' => true,
                'key' => md5($gKey),
                'label' => trim((string) ($first->request_no ?? '')) !== ''
                    ? $first->request_no
                    : ($first->ris_no ?: 'Grouped RFCs'),
                'request_no' => $first->request_no ?? null,
                'date' => $first->date ?? null,
                'urgent' => $hasUrgent,
                'total' => $total,
                'atp_count' => $atpCount,
                'payee_summary' => count($payeeKeys) === 1 ? $payeeKeys[0] : (count($payeeKeys) > 1 ? count($payeeKeys).' payees' : '—'),
                'requester_summary' => count($requesterKeys) === 1 ? $requesterKeys[0] : (count($requesterKeys) > 1 ? count($requesterKeys).' requesters' : '—'),
                'unreleased_count' => $unreleased,
                'ready_count' => $ready,
                'pending_ready_count' => count($pendingReadyMembers),
                'pending_ready_members' => $pendingReadyMembers,
                'ready_only_members' => $readyOnlyMembers,
                'released_count' => $released,
                'members' => $members,
            ];
        } else {
            $pkgEntries[] = (object) [
                'is_group' => false,
                'item' => $members[0],
            ];
        }
    }
@endphp

<div class="acc-page space-y-6">
    <div class="pur-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-3.5">
            <div class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50/90 p-1" role="tablist" aria-label="Filter Request for Check">
                <button
                    type="button"
                    data-pkg-filter="all"
                    class="pkg-filter-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                >
                    <span>All</span>
                    <span class="rounded-full bg-gray-200/80 px-1.5 py-0.5 text-[10px] font-bold text-gray-700">{{ $allPkgCount }}</span>
                </button>
                <button
                    type="button"
                    data-pkg-filter="for_release"
                    class="pkg-filter-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                >
                    <span>For Release</span>
                    <span class="rounded-full bg-gray-200/80 px-1.5 py-0.5 text-[10px] font-bold text-gray-700">{{ $forReleaseCount }}</span>
                </button>
                <button
                    type="button"
                    data-pkg-filter="released"
                    class="pkg-filter-tab inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                >
                    <span>Released</span>
                    <span class="rounded-full bg-gray-200/80 px-1.5 py-0.5 text-[10px] font-bold text-gray-700">{{ $releasedCount }}</span>
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select
                    id="pkgUrgencySelect"
                    aria-label="Filter by urgency"
                    class="h-9 rounded-lg border border-gray-200 bg-white px-3 pr-7 text-xs font-medium text-gray-700 outline-none transition hover:border-gray-300 focus:border-[#0025cc] focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">All urgency</option>
                    <option value="urgent">Urgent</option>
                    <option value="non_urgent">Non-Urgent</option>
                </select>
                <div class="relative inline-flex items-center gap-1.5">
                    <button
                        type="button"
                        id="pkgDateBtn"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                        data-tip="Filter by date"
                        data-tip-place="above"
                        aria-label="Filter by date"
                    >
                        <i data-lucide="calendar" class="h-4 w-4"></i>
                    </button>
                    <input
                        type="date"
                        id="pkgDateInput"
                        class="pointer-events-none absolute left-0 top-0 h-9 w-9 opacity-0"
                        tabindex="-1"
                        aria-hidden="true"
                    >
                    <button
                        type="button"
                        id="pkgDateClear"
                        class="hidden inline-flex h-7 items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2.5 text-[11px] font-semibold text-[#0025cc] transition hover:bg-blue-100"
                        data-tip="Clear date filter"
                        data-tip-place="above"
                    >
                        <span id="pkgDateClearText"></span>
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="relative w-full sm:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </span>
                    <input
                        type="text"
                        id="pkgSearchInput"
                        placeholder="Search RFC, Request No., ATP, payee, requester..."
                        autocomplete="off"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-xs text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0025cc] focus:ring-2 focus:ring-blue-100"
                    >
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] table-fixed text-sm">
                <colgroup>
                    <col style="width:21%">
                    <col style="width:11%">
                    <col style="width:12%">
                    <col style="width:13%">
                    <col style="width:11%">
                    <col style="width:9%">
                    <col style="width:13%">
                    <col style="width:10%">
                </colgroup>
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC / Group</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Requested By</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($pkgEntries as $entry)
                        @if(! $entry->is_group)
                            @php
                                $item = $entry->item;
                                $rowIsoDate = !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '';
                                $rowSearchBlob = strtolower(trim(implode(' ', array_filter([
                                    $item->ref ?? '',
                                    $item->request_no ?? '',
                                    $item->atp_no ?? '',
                                    $item->payee ?? '',
                                    $item->requester ?? '',
                                    $item->fund_label ?? '',
                                    !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '',
                                ]))));
                            @endphp
                            <tr class="transition hover:bg-gray-50/70" data-pkg-row data-pkg-solo data-pkg-state="{{ !empty($item->check_released) ? 'released' : 'for_release' }}" data-pkg-urgency="{{ !empty($item->urgent_row) ? 'urgent' : 'non_urgent' }}" data-pkg-date="{{ $rowIsoDate }}" data-pkg-search="{{ $rowSearchBlob }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                            <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0 flex flex-wrap items-center gap-1.5">
                                            <p class="font-semibold text-gray-900">{{ $item->ref }}</p>
                                            @include('partials.ris-urgency-badge', [
                                                'urgent' => !empty($item->urgent_row),
                                                'size' => 'sm',
                                                'title' => 'Inherited from linked RIS',
                                            ])
                                            @if(!empty($item->fund_label) && $item->fund_label !== 'Not chosen')
                                                @include('partials.payment-method-badge', [
                                                    'path' => $item->fund_label,
                                                    'size' => 'sm',
                                                ])
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @include('partials.procurement-request-no', ['requestNo' => $item->request_no ?? null, 'inline' => true, 'showLabel' => false])
                                    @if(blank($item->request_no ?? null))
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $item->atp_no ?: '—' }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $item->payee }}</td>
                                <td class="px-5 py-4 font-medium text-gray-700">{{ $item->requester ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                    {{ !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if(!empty($item->check_released))
                                        <div class="inline-flex flex-col items-start">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#059669;"></span>
                                                <span>Released</span>
                                            </span>
                                            <div class="mt-1 text-[11px] font-medium leading-tight text-slate-500">
                                                {{ !empty($item->check_released_at) ? 'Released '.\Carbon\Carbon::parse($item->check_released_at)->format('M d, Y') : 'Check released' }}
                                            </div>
                                        </div>
                                    @elseif(!empty($item->check_ready))
                                        <div class="inline-flex flex-col items-start">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                style="background:#fffbeb;color:#b45309;border-color:#fde68a;"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#d97706;"></span>
                                                <span>Ready for Pickup</span>
                                            </span>
                                            <div class="mt-1 text-[11px] font-medium leading-tight" style="color:#b45309;">Mark as Released after pickup</div>
                                        </div>
                                    @else
                                        <div class="inline-flex flex-col items-start">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#2563eb;"></span>
                                                <span>For Check Release</span>
                                            </span>
                                            <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">Approved by School Administrator</div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center">
                                        <button
                                            type="button"
                                            class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition hover:opacity-85"
                                            style="{{ !empty($item->check_released) ? 'background:#ffffff;border:1px solid #e2e8f0;color:#475569;' : (empty($item->check_ready) ? 'background:#eff6ff;color:#0025cc;' : 'background:#fffbeb;color:#d97706;') }}"
                                            data-tip="{{ !empty($item->check_released) ? 'View RFC & ATP' : (empty($item->check_ready) ? 'Release Check' : 'Mark as Released (after pickup)') }}"
                                            data-tip-place="above"
                                            aria-label="{{ !empty($item->check_released) ? 'View RFC & ATP' : (empty($item->check_ready) ? 'Release Check' : 'Mark as Released (after pickup)') }}"
                                            data-pkg-view
                                            data-title="{{ $item->ref }}"
                                            data-fund="{{ $item->fund_label ?? '' }}"
                                            data-payee="{{ $item->payee ?? '' }}"
                                            data-requester="{{ $item->requester ?? '' }}"
                                            data-rfc-url="{{ $item->rfc_preview }}"
                                            data-atp-url="{{ $item->atp_preview }}"
                                            data-atp-label="{{ $item->atp_no ?: 'ATP' }}"
                                            data-ris-url="{{ $item->ris_preview ?? '' }}"
                                            data-ris-label="{{ $item->ris_no ?: 'Requisition Slip' }}"
                                            data-canvass-url="{{ $item->canvass_preview ?? '' }}"
                                            data-canvass-label="{{ $item->canvass_no ?: 'Canvass' }}"
                                            data-ready="{{ !empty($item->check_ready) ? '1' : '0' }}"
                                            data-released="{{ !empty($item->check_released) ? '1' : '0' }}"
                                            data-rfc-id="{{ $item->rfc_id }}"
                                        >
                                            @if(!empty($item->check_released))
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            @elseif(empty($item->check_ready))
                                                <i data-lucide="banknote" class="h-4 w-4"></i>
                                            @else
                                                <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                                <span class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" style="background:#f59e0b;"></span>
                                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full ring-2 ring-white" style="background:#d97706;"></span>
                                                </span>
                                            @endif
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr
                                class="cursor-pointer bg-slate-100/70 transition hover:bg-slate-100"
                                data-pkg-group-header="{{ $entry->key }}"
                                data-pkg-group-toggle="{{ $entry->key }}"
                                aria-expanded="false"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-blue-200/80 bg-blue-50 text-[#0025cc]">
                                            <i data-lucide="layers" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="font-semibold text-gray-900">{{ $entry->label }}</p>
                                                <span class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-[#0025cc]">
                                                    <span data-pkg-group-count="{{ $entry->key }}">{{ $entry->total }}</span>
                                                    <span>RFCs</span>
                                                </span>
                                                @include('partials.ris-urgency-badge', [
                                                    'urgent' => $entry->urgent,
                                                    'size' => 'sm',
                                                    'title' => 'Inherited from linked RIS',
                                                ])
                                            </div>
                                            <p class="mt-0.5 text-[11px] text-slate-500">Click to expand individual RFCs</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @include('partials.procurement-request-no', ['requestNo' => $entry->request_no, 'inline' => true, 'showLabel' => false])
                                    @if(blank($entry->request_no))
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ $entry->atp_count > 0 ? $entry->atp_count.' '.($entry->atp_count === 1 ? 'ATP' : 'ATPs') : '—' }}
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $entry->payee_summary }}</td>
                                <td class="px-5 py-4 font-medium text-gray-700">{{ $entry->requester_summary }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                    {{ !empty($entry->date) ? \Carbon\Carbon::parse($entry->date)->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($entry->unreleased_count === 0)
                                        <div class="inline-flex flex-col items-start">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#059669;"></span>
                                                <span>All Released</span>
                                            </span>
                                            <div class="mt-1 text-[11px] font-medium leading-tight text-slate-500">{{ $entry->total }} of {{ $entry->total }} released</div>
                                        </div>
                                    @elseif($entry->ready_count > 0 && $entry->ready_count === $entry->unreleased_count)
                                        <div class="inline-flex flex-col items-start">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                style="background:#fffbeb;color:#b45309;border-color:#fde68a;"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#d97706;"></span>
                                                <span>Ready for Pickup</span>
                                            </span>
                                            <div class="mt-1 text-[11px] font-medium leading-tight" style="color:#b45309;">{{ $entry->ready_count }} of {{ $entry->total }} ready for pickup</div>
                                        </div>
                                    @else
                                        <div class="inline-flex flex-col items-start">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;"
                                            >
                                                <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#2563eb;"></span>
                                                <span>For Check Release</span>
                                            </span>
                                            <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">
                                                {{ $entry->unreleased_count }} of {{ $entry->total }} for release{{ $entry->ready_count > 0 ? ' · '.$entry->ready_count.' ready' : '' }}
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($entry->pending_ready_count > 0)
                                            <button
                                                type="button"
                                                class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white shadow-xs transition hover:opacity-90"
                                                style="background:#0025cc;color:#ffffff;"
                                                data-tip="Release All Checks ({{ $entry->pending_ready_count }})"
                                                data-tip-place="above"
                                                aria-label="Release All Checks ({{ $entry->pending_ready_count }})"
                                                data-pkg-batch-btn
                                                data-batch-mode="ready"
                                                data-batch-label="{{ $entry->label }}"
                                                data-batch-action="{{ route('accounting.approved-packages.batch-check-release') }}"
                                                data-batch-items='@json($entry->pending_ready_members)'
                                            >
                                                <i data-lucide="banknote" class="h-4 w-4"></i>
                                                <span
                                                    class="pointer-events-none absolute -right-1.5 -top-1.5 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1 text-[10px] font-bold text-white ring-2 ring-white"
                                                    style="background:#f59e0b;"
                                                >{{ $entry->pending_ready_count }}</span>
                                            </button>
                                        @elseif($entry->ready_count > 0)
                                            <button
                                                type="button"
                                                class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white shadow-xs transition hover:opacity-90"
                                                style="background:#059669;color:#ffffff;"
                                                data-tip="Mark All as Released ({{ $entry->ready_count }})"
                                                data-tip-place="above"
                                                aria-label="Mark All as Released ({{ $entry->ready_count }})"
                                                data-pkg-batch-btn
                                                data-batch-mode="released"
                                                data-batch-label="{{ $entry->label }}"
                                                data-batch-action="{{ route('accounting.approved-packages.batch-check-released') }}"
                                                data-batch-items='@json($entry->ready_only_members)'
                                            >
                                                <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                                <span
                                                    class="pointer-events-none absolute -right-1.5 -top-1.5 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full px-1 text-[10px] font-bold text-white ring-2 ring-white"
                                                    style="background:#f59e0b;"
                                                >{{ $entry->ready_count }}</span>
                                            </button>
                                        @endif
                                        <button
                                            type="button"
                                            class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            data-tip="Expand individual RFCs"
                                            data-tip-place="above"
                                            aria-label="Toggle grouped RFCs"
                                        >
                                            <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-pkg-group-chevron="{{ $entry->key }}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hidden bg-white" data-pkg-group-body="{{ $entry->key }}">
                                <td colspan="8" class="border-t border-slate-100 bg-white p-0">
                                    <div class="max-h-[412px] overflow-y-auto">
                                        <table class="w-full table-fixed text-sm">
                                            <colgroup>
                                                <col style="width:21%">
                                                <col style="width:11%">
                                                <col style="width:12%">
                                                <col style="width:13%">
                                                <col style="width:11%">
                                                <col style="width:9%">
                                                <col style="width:13%">
                                                <col style="width:10%">
                                            </colgroup>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @foreach($entry->members as $item)
                                                    @php
                                                        $rowIsoDate = !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('Y-m-d') : '';
                                                        $rowSearchBlob = strtolower(trim(implode(' ', array_filter([
                                                            $item->ref ?? '',
                                                            $item->request_no ?? '',
                                                            $item->atp_no ?? '',
                                                            $item->payee ?? '',
                                                            $item->requester ?? '',
                                                            $item->fund_label ?? '',
                                                            !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '',
                                                        ]))));
                                                    @endphp
                                                    <tr class="bg-white transition hover:bg-slate-50/70" data-pkg-row data-pkg-child-row="{{ $entry->key }}" data-pkg-state="{{ !empty($item->check_released) ? 'released' : 'for_release' }}" data-pkg-urgency="{{ !empty($item->urgent_row) ? 'urgent' : 'non_urgent' }}" data-pkg-date="{{ $rowIsoDate }}" data-pkg-search="{{ $rowSearchBlob }}">
                                                        <td class="py-3.5 pl-10 pr-5">
                                                            <div class="min-w-0 flex flex-wrap items-center gap-1.5">
                                                                <p class="font-semibold text-gray-900">{{ $item->ref }}</p>
                                                                @include('partials.ris-urgency-badge', [
                                                                    'urgent' => !empty($item->urgent_row),
                                                                    'size' => 'sm',
                                                                    'title' => 'Inherited from linked RIS',
                                                                ])
                                                                @if(!empty($item->fund_label) && $item->fund_label !== 'Not chosen')
                                                                    @include('partials.payment-method-badge', [
                                                                        'path' => $item->fund_label,
                                                                        'size' => 'sm',
                                                                    ])
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-3.5">
                                                            @include('partials.procurement-request-no', ['requestNo' => $item->request_no ?? null, 'inline' => true, 'showLabel' => false])
                                                            @if(blank($item->request_no ?? null))
                                                                <span class="text-xs text-gray-400">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-5 py-3.5 text-gray-600">{{ $item->atp_no ?: '—' }}</td>
                                                        <td class="px-5 py-3.5 text-gray-600">{{ $item->payee }}</td>
                                                        <td class="px-5 py-3.5 font-medium text-gray-700">{{ $item->requester ?? '—' }}</td>
                                                        <td class="whitespace-nowrap px-5 py-3.5 text-gray-600">
                                                            {{ !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '—' }}
                                                        </td>
                                                        <td class="px-5 py-3.5">
                                                            @if(!empty($item->check_released))
                                                                <div class="inline-flex flex-col items-start">
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                                        style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;"
                                                                    >
                                                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#059669;"></span>
                                                                        <span>Released</span>
                                                                    </span>
                                                                    <div class="mt-1 text-[11px] font-medium leading-tight text-slate-500">
                                                                        {{ !empty($item->check_released_at) ? 'Released '.\Carbon\Carbon::parse($item->check_released_at)->format('M d, Y') : 'Check released' }}
                                                                    </div>
                                                                </div>
                                                            @elseif(!empty($item->check_ready))
                                                                <div class="inline-flex flex-col items-start">
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                                        style="background:#fffbeb;color:#b45309;border-color:#fde68a;"
                                                                    >
                                                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#d97706;"></span>
                                                                        <span>Ready for Pickup</span>
                                                                    </span>
                                                                    <div class="mt-1 text-[11px] font-medium leading-tight" style="color:#b45309;">Mark as Released after pickup</div>
                                                                </div>
                                                            @else
                                                                <div class="inline-flex flex-col items-start">
                                                                    <span
                                                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                                                        style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;"
                                                                    >
                                                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#2563eb;"></span>
                                                                        <span>For Check Release</span>
                                                                    </span>
                                                                    <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">Approved by School Administrator</div>
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-5 py-3.5">
                                                            <div class="flex items-center justify-center">
                                                                <button
                                                                    type="button"
                                                                    class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition hover:opacity-85"
                                                                    style="{{ !empty($item->check_released) ? 'background:#ffffff;border:1px solid #e2e8f0;color:#475569;' : (empty($item->check_ready) ? 'background:#eff6ff;color:#0025cc;' : 'background:#fffbeb;color:#d97706;') }}"
                                                                    data-tip="{{ !empty($item->check_released) ? 'View RFC & ATP' : (empty($item->check_ready) ? 'Release Check' : 'Mark as Released (after pickup)') }}"
                                                                    data-tip-place="above"
                                                                    aria-label="{{ !empty($item->check_released) ? 'View RFC & ATP' : (empty($item->check_ready) ? 'Release Check' : 'Mark as Released (after pickup)') }}"
                                                                    data-pkg-view
                                                                    data-title="{{ $item->ref }}"
                                                                    data-fund="{{ $item->fund_label ?? '' }}"
                                                                    data-payee="{{ $item->payee ?? '' }}"
                                                                    data-requester="{{ $item->requester ?? '' }}"
                                                                    data-rfc-url="{{ $item->rfc_preview }}"
                                                                    data-atp-url="{{ $item->atp_preview }}"
                                                                    data-atp-label="{{ $item->atp_no ?: 'ATP' }}"
                                                                    data-ris-url="{{ $item->ris_preview ?? '' }}"
                                                                    data-ris-label="{{ $item->ris_no ?: 'Requisition Slip' }}"
                                                                    data-canvass-url="{{ $item->canvass_preview ?? '' }}"
                                                                    data-canvass-label="{{ $item->canvass_no ?: 'Canvass' }}"
                                                                    data-ready="{{ !empty($item->check_ready) ? '1' : '0' }}"
                                                                    data-released="{{ !empty($item->check_released) ? '1' : '0' }}"
                                                                    data-rfc-id="{{ $item->rfc_id }}"
                                                                >
                                                                    @if(!empty($item->check_released))
                                                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                                                    @elseif(empty($item->check_ready))
                                                                        <i data-lucide="banknote" class="h-4 w-4"></i>
                                                                    @else
                                                                        <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                                                        <span class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" style="background:#f59e0b;"></span>
                                                                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full ring-2 ring-white" style="background:#d97706;"></span>
                                                                        </span>
                                                                    @endif
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center text-sm text-gray-500">No approved Request for Check is waiting.</td>
                        </tr>
                    @endforelse
                    <tr id="pkgNoMatchRow" class="hidden">
                        <td colspan="8" class="px-5 py-14 text-center text-sm text-gray-500">No matching Request for Check records found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="pkgViewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h3 id="pkgViewTitle" class="truncate text-base font-semibold text-gray-900"></h3>
                    <span id="pkgViewFund" class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-bold tracking-wide shadow-xs"></span>
                </div>
                <p id="pkgViewSlideLabel" class="mt-0.5 text-xs text-gray-500"></p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="pkgViewPrev" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50" aria-label="Previous document">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </button>
                <button type="button" id="pkgViewNext" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50" aria-label="Next document">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </button>
                <button type="button" id="pkgViewClose" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
        <div id="pkgReadyBanner" class="hidden flex items-center gap-2 border-b px-4 py-2 text-xs font-medium" style="background:#fffbeb;border-color:#fde68a;color:#92400e;">
            <span class="h-2 w-2 shrink-0 rounded-full" style="background:#d97706;"></span>
            <span>Check is ready for pickup — click <strong>Mark as Released</strong> once the Purchaser has collected the check.</span>
        </div>
        <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100">
            <div id="pkgViewTrack" class="flex h-[72vh] transition-transform duration-300"></div>
        </div>
        <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-4 py-3">
            <span class="w-[8.5rem]"></span>
            <div id="pkgViewDots" class="flex flex-wrap items-center justify-center gap-2"></div>
            <form id="pkgCheckForm" method="POST" class="flex min-w-[8.5rem] justify-end">
                @csrf
                <button id="pkgCheckButton" type="button" class="inline-flex h-9 items-center whitespace-nowrap rounded-lg bg-[#0025cc] px-3.5 text-xs font-semibold text-white hover:bg-[#001db3]" style="background:#0025cc;color:#ffffff;">Release Check</button>
            </form>
        </div>
    </div>
</div>

<div id="pkgCheckConfirmModal" class="fixed inset-0 hidden items-center justify-center bg-black/55 p-4 backdrop-blur-[1px]" style="z-index: 99999;">
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="pkgCheckConfirmTitle">
        <div class="flex items-start gap-3.5 px-6 pb-4 pt-6">
            <div id="pkgCheckConfirmIcon" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background:#eff6ff;color:#0025cc;">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 id="pkgCheckConfirmTitle" class="text-base font-bold text-slate-900">Confirm Check Release?</h3>
                <p id="pkgCheckConfirmDesc" class="mt-1 text-xs leading-relaxed text-slate-500">
                    The Purchaser will be notified that the check is ready for release.
                </p>
            </div>
        </div>
        <div class="space-y-2 px-6 pb-5 text-xs">
            <div class="space-y-1.5 rounded-xl border border-slate-200 bg-slate-50/80 p-3.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-slate-500">RFC No.</span>
                    <span id="pkgConfirmRfc" class="font-semibold text-slate-900">—</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-slate-500">ATP No.</span>
                    <span id="pkgConfirmAtp" class="font-semibold text-slate-900">—</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-slate-500">Payee</span>
                    <span id="pkgConfirmPayee" class="font-semibold text-slate-900">—</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-slate-500">Requested By</span>
                    <span id="pkgConfirmRequester" class="font-semibold text-slate-900">—</span>
                </div>
                <div id="pkgConfirmFundRow" class="flex items-center justify-between gap-2">
                    <span class="text-slate-500">Payment Method</span>
                    <span id="pkgConfirmFund" class="font-semibold" style="color:#0025cc;">—</span>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
            <button type="button" id="pkgCheckConfirmCancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
            <button type="button" id="pkgCheckConfirmProceed" class="rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-95" style="background:#0025cc;color:#ffffff;">Confirm Check Release</button>
        </div>
    </div>
</div>

<div id="pkgBatchConfirmModal" class="fixed inset-0 hidden items-center justify-center bg-black/55 p-4 backdrop-blur-[1px]" style="z-index: 99999;">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="pkgBatchConfirmTitle">
        <div class="flex items-start gap-3.5 px-6 pb-4 pt-6">
            <div id="pkgBatchConfirmIcon" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" style="background:#eff6ff;color:#0025cc;">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 id="pkgBatchConfirmTitle" class="text-base font-bold text-slate-900">Release All Checks?</h3>
                <p id="pkgBatchConfirmDesc" class="mt-1 text-xs leading-relaxed text-slate-500">
                    The Purchaser will be notified that these checks are ready for pickup.
                </p>
            </div>
        </div>
        <div class="px-6 pb-5">
            <div class="mb-2 flex items-center justify-between text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                <span id="pkgBatchGroupLabel">—</span>
                <span id="pkgBatchItemCount">0 checks</span>
            </div>
            <div id="pkgBatchItemsList" class="max-h-60 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/70"></div>
        </div>
        <form id="pkgBatchConfirmForm" method="POST" class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
            @csrf
            <div id="pkgBatchHiddenInputs"></div>
            <button type="button" id="pkgBatchConfirmCancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Cancel</button>
            <button type="submit" id="pkgBatchConfirmProceed" class="rounded-xl px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-95" style="background:#0025cc;color:#ffffff;">Confirm Release All</button>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('pkgViewModal');
        var track = document.getElementById('pkgViewTrack');
        var title = document.getElementById('pkgViewTitle');
        var fund = document.getElementById('pkgViewFund');
        var slideLabel = document.getElementById('pkgViewSlideLabel');
        var dotsWrap = document.getElementById('pkgViewDots');
        var checkForm = document.getElementById('pkgCheckForm');
        var checkButton = document.getElementById('pkgCheckButton');
        var readyBanner = document.getElementById('pkgReadyBanner');
        var confirmModal = document.getElementById('pkgCheckConfirmModal');
        var confirmIcon = document.getElementById('pkgCheckConfirmIcon');
        var confirmTitle = document.getElementById('pkgCheckConfirmTitle');
        var confirmDesc = document.getElementById('pkgCheckConfirmDesc');
        var confirmRfc = document.getElementById('pkgConfirmRfc');
        var confirmAtp = document.getElementById('pkgConfirmAtp');
        var confirmPayee = document.getElementById('pkgConfirmPayee');
        var confirmRequester = document.getElementById('pkgConfirmRequester');
        var confirmFundRow = document.getElementById('pkgConfirmFundRow');
        var confirmFund = document.getElementById('pkgConfirmFund');
        var confirmCancel = document.getElementById('pkgCheckConfirmCancel');
        var confirmProceed = document.getElementById('pkgCheckConfirmProceed');
        var index = 0;
        var slides = [];
        var currentPkg = { ref: '', atp: '', payee: '', fund: '', ready: false };

        if (confirmModal && confirmModal.parentElement !== document.body) {
            document.body.appendChild(confirmModal);
        }

        function paint() {
            var count = slides.length || 1;
            var pct = 100 / count;
            track.style.transform = 'translateX(' + (-index * pct) + '%)';
            var active = slides[index] || { label: 'Document' };
            slideLabel.textContent = active.label + ' · ' + (index + 1) + ' of ' + count;
            document.querySelectorAll('.pkg-dot').forEach(function (dot) {
                var on = Number(dot.getAttribute('data-pkg-dot')) === index;
                dot.className = 'pkg-dot rounded-full px-3 py-1 text-xs font-semibold transition ' + (on
                    ? 'bg-[#0025cc] text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
            });
        }

        function closeConfirmModal() {
            if (!confirmModal) return;
            confirmModal.classList.add('hidden');
            confirmModal.classList.remove('flex');
        }

        function openConfirmModal() {
            if (!confirmModal) return;
            if (confirmModal.parentElement !== document.body) {
                document.body.appendChild(confirmModal);
            }
            confirmTitle.textContent = currentPkg.ready ? 'Mark Check as Released?' : 'Release Check for Collection?';
            confirmDesc.innerHTML = currentPkg.ready
                ? 'Confirm that the Purchaser has physically received the check. This will unlock the Receiving Report for the Purchaser.'
                : 'Notify the Purchaser that the check is ready for pickup. After they collect it, return here and click <strong>Mark as Released</strong>.';
            if (confirmIcon) {
                confirmIcon.style.background = currentPkg.ready ? '#ecfdf5' : '#eff6ff';
                confirmIcon.style.color = currentPkg.ready ? '#059669' : '#0025cc';
            }
            confirmRfc.textContent = currentPkg.ref || '—';
            confirmAtp.textContent = currentPkg.atp || '—';
            confirmPayee.textContent = currentPkg.payee || '—';
            if (confirmRequester) confirmRequester.textContent = currentPkg.requester || '—';
            if (currentPkg.fund && currentPkg.fund !== 'Not chosen') {
                confirmFund.textContent = currentPkg.fund;
                confirmFundRow.classList.remove('hidden');
            } else {
                confirmFundRow.classList.add('hidden');
            }
            confirmProceed.disabled = false;
            confirmProceed.textContent = currentPkg.ready ? 'Confirm Released' : 'Confirm Release Check';
            confirmProceed.style.background = currentPkg.ready ? '#059669' : '#0025cc';
            confirmProceed.style.color = '#ffffff';
            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
        }

        function openModal(button) {
            index = 0;
            var ref = button.getAttribute('data-title') || 'Request for Check';
            var method = button.getAttribute('data-fund') || '';
            var payee = button.getAttribute('data-payee') || '';
            var requester = button.getAttribute('data-requester') || '';
            var atpLabel = button.getAttribute('data-atp-label') || 'Authority to Purchase';
            var ready = button.getAttribute('data-ready') === '1';
            var released = button.getAttribute('data-released') === '1';
            var rfcId = button.getAttribute('data-rfc-id');

            currentPkg = { ref: ref, atp: atpLabel, payee: payee, requester: requester, fund: method, ready: ready, released: released };
            title.textContent = ref;
            if (method && method !== 'Not chosen') {
                var mKey = method.toLowerCase();
                var pillStyle = mKey.indexOf('advance') !== -1
                    ? { bg: '#f3e8ff', fg: '#6b21a8', border: '#c084fc', dot: '#9333ea' }
                    : (mKey.indexOf('installment') !== -1
                        ? { bg: '#fffbeb', fg: '#b45309', border: '#fcd34d', dot: '#d97706' }
                        : { bg: '#eff6ff', fg: '#1d4ed8', border: '#93c5fd', dot: '#2563eb' });
                fund.innerHTML = '<span style="width:8px;height:8px;border-radius:9999px;background:' + pillStyle.dot + ';display:inline-block;flex-shrink:0;"></span><span>' + method + '</span>';
                fund.setAttribute('style', 'background:' + pillStyle.bg + ';color:' + pillStyle.fg + ';border:1.5px solid ' + pillStyle.border + ';');
                fund.hidden = false;
            } else {
                fund.textContent = '';
                fund.removeAttribute('style');
                fund.hidden = true;
            }
            if (readyBanner) {
                readyBanner.classList.toggle('hidden', !ready || released);
            }
            if (checkForm) {
                checkForm.classList.toggle('hidden', released);
            }
            checkForm.action = '/accounting/approved-packages/' + rfcId + (ready ? '/check-released' : '/check-release');
            checkButton.textContent = ready ? 'Mark as Released' : 'Release Check';
            checkButton.className = 'inline-flex h-9 items-center whitespace-nowrap rounded-lg px-3.5 text-xs font-semibold text-white transition hover:opacity-95';
            checkButton.style.background = ready ? '#059669' : '#0025cc';
            checkButton.style.color = '#ffffff';
            var rfcUrl = button.getAttribute('data-rfc-url') || '';
            var atpUrl = button.getAttribute('data-atp-url') || '';
            var risUrl = button.getAttribute('data-ris-url') || '';
            var risLabel = button.getAttribute('data-ris-label') || 'Requisition Slip';
            var canvassUrl = button.getAttribute('data-canvass-url') || '';
            var canvassLabel = button.getAttribute('data-canvass-label') || 'Canvass';

            slides = [];
            if (rfcUrl) slides.push({ pill: 'RFC', label: 'Funding request', url: rfcUrl });
            if (atpUrl) slides.push({ pill: 'ATP', label: atpLabel, url: atpUrl });
            if (risUrl) slides.push({ pill: 'RIS', label: risLabel, url: risUrl });
            if (canvassUrl) slides.push({ pill: 'Canvass', label: canvassLabel, url: canvassUrl });

            var count = slides.length || 1;
            track.style.width = (count * 100) + '%';
            track.innerHTML = '';
            if (dotsWrap) dotsWrap.innerHTML = '';
            slides.forEach(function (s, idx) {
                var iframe = document.createElement('iframe');
                iframe.title = s.label;
                iframe.src = s.url;
                iframe.className = 'h-full shrink-0 border-0 bg-white';
                iframe.style.width = (100 / count) + '%';
                track.appendChild(iframe);

                if (dotsWrap) {
                    var dot = document.createElement('button');
                    dot.type = 'button';
                    dot.setAttribute('data-pkg-dot', String(idx));
                    dot.className = 'pkg-dot rounded-full px-3 py-1 text-xs font-semibold';
                    dot.textContent = s.pill;
                    dotsWrap.appendChild(dot);
                }
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            paint();
            if (window.lucide) window.lucide.createIcons();
        }

        function closeModal() {
            closeConfirmModal();
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            track.innerHTML = '';
            if (dotsWrap) dotsWrap.innerHTML = '';
            slides = [];
        }

        if (checkButton) {
            checkButton.addEventListener('click', openConfirmModal);
        }
        if (confirmCancel) {
            confirmCancel.addEventListener('click', closeConfirmModal);
        }
        if (confirmModal) {
            confirmModal.addEventListener('click', function (event) {
                if (event.target === confirmModal) closeConfirmModal();
            });
        }
        if (confirmProceed) {
            confirmProceed.addEventListener('click', function () {
                confirmProceed.disabled = true;
                confirmProceed.textContent = 'Processing...';
                checkForm.submit();
            });
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-pkg-view]');
            if (button) {
                openModal(button);
                return;
            }
            var dot = event.target.closest('[data-pkg-dot]');
            if (dot && !modal.classList.contains('hidden')) {
                index = Number(dot.getAttribute('data-pkg-dot')) || 0;
                paint();
            }
        });
        document.getElementById('pkgViewPrev').addEventListener('click', function () {
            if (!slides.length) return;
            index = (index - 1 + slides.length) % slides.length;
            paint();
        });
        document.getElementById('pkgViewNext').addEventListener('click', function () {
            if (!slides.length) return;
            index = (index + 1) % slides.length;
            paint();
        });
        document.getElementById('pkgViewClose').addEventListener('click', closeModal);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            if (batchConfirmModal && !batchConfirmModal.classList.contains('hidden')) {
                closeBatchConfirmModal();
                return;
            }
            if (confirmModal && !confirmModal.classList.contains('hidden')) {
                closeConfirmModal();
                return;
            }
            if (!modal.classList.contains('hidden')) closeModal();
        });

        var batchConfirmModal = document.getElementById('pkgBatchConfirmModal');
        var batchConfirmIcon = document.getElementById('pkgBatchConfirmIcon');
        var batchConfirmTitle = document.getElementById('pkgBatchConfirmTitle');
        var batchConfirmDesc = document.getElementById('pkgBatchConfirmDesc');
        var batchGroupLabel = document.getElementById('pkgBatchGroupLabel');
        var batchItemCount = document.getElementById('pkgBatchItemCount');
        var batchItemsList = document.getElementById('pkgBatchItemsList');
        var batchConfirmForm = document.getElementById('pkgBatchConfirmForm');
        var batchHiddenInputs = document.getElementById('pkgBatchHiddenInputs');
        var batchConfirmCancel = document.getElementById('pkgBatchConfirmCancel');
        var batchConfirmProceed = document.getElementById('pkgBatchConfirmProceed');

        if (batchConfirmModal && batchConfirmModal.parentElement !== document.body) {
            document.body.appendChild(batchConfirmModal);
        }

        function closeBatchConfirmModal() {
            if (!batchConfirmModal) return;
            batchConfirmModal.classList.add('hidden');
            batchConfirmModal.classList.remove('flex');
        }

        function openBatchConfirmModal(btn) {
            if (!batchConfirmModal || !batchConfirmForm) return;
            if (batchConfirmModal.parentElement !== document.body) {
                document.body.appendChild(batchConfirmModal);
            }
            var mode = btn.getAttribute('data-batch-mode') || 'ready';
            var label = btn.getAttribute('data-batch-label') || 'Grouped RFCs';
            var action = btn.getAttribute('data-batch-action') || '';
            var items = [];
            try {
                items = JSON.parse(btn.getAttribute('data-batch-items') || '[]');
            } catch (e) {
                items = [];
            }
            if (!items.length || !action) return;

            var isReleasedMode = mode === 'released';
            batchConfirmTitle.textContent = isReleasedMode
                ? 'Mark All Checks as Released?'
                : 'Release All Checks for Collection?';
            batchConfirmDesc.innerHTML = isReleasedMode
                ? 'Confirm that the Purchaser has physically collected all <strong>' + items.length + '</strong> checks below. This will unlock their Receiving Reports.'
                : 'Notify the Purchaser that all <strong>' + items.length + '</strong> checks below are ready for pickup. Return here to mark them as released once collected.';
            if (batchConfirmIcon) {
                batchConfirmIcon.style.background = isReleasedMode ? '#ecfdf5' : '#eff6ff';
                batchConfirmIcon.style.color = isReleasedMode ? '#059669' : '#0025cc';
            }
            if (batchGroupLabel) batchGroupLabel.textContent = label;
            if (batchItemCount) batchItemCount.textContent = items.length + (items.length === 1 ? ' check' : ' checks');

            batchItemsList.innerHTML = '';
            batchHiddenInputs.innerHTML = '';

            items.forEach(function (it) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'rfc_ids[]';
                input.value = String(it.id || '');
                batchHiddenInputs.appendChild(input);

                var row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-3 bg-white px-3.5 py-2.5 text-xs';

                var left = document.createElement('div');
                left.className = 'min-w-0';
                var titleLine = document.createElement('div');
                titleLine.className = 'flex flex-wrap items-center gap-1.5';
                var refSpan = document.createElement('span');
                refSpan.className = 'font-semibold text-slate-900';
                refSpan.textContent = it.ref || '—';
                titleLine.appendChild(refSpan);

                if (it.fund && it.fund !== 'Not chosen') {
                    var fKey = String(it.fund).toLowerCase();
                    var badgeStyle = fKey.indexOf('advance') !== -1
                        ? 'background:#f3e8ff;color:#6b21a8;border-color:#d8b4fe;'
                        : (fKey.indexOf('installment') !== -1
                            ? 'background:#fffbeb;color:#b45309;border-color:#fde68a;'
                            : 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;');
                    var badge = document.createElement('span');
                    badge.className = 'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold';
                    badge.setAttribute('style', badgeStyle);
                    badge.textContent = it.fund;
                    titleLine.appendChild(badge);
                }

                var subLine = document.createElement('div');
                subLine.className = 'mt-0.5 truncate text-[11px] text-slate-500';
                subLine.textContent = (it.payee || '—') + (it.atp && it.atp !== '—' ? ' · ' + it.atp : '');
                left.appendChild(titleLine);
                left.appendChild(subLine);
                row.appendChild(left);
                batchItemsList.appendChild(row);
            });

            batchConfirmForm.action = action;
            batchConfirmProceed.disabled = false;
            batchConfirmProceed.textContent = isReleasedMode
                ? 'Confirm All Released (' + items.length + ')'
                : 'Confirm Release All (' + items.length + ')';
            batchConfirmProceed.style.background = isReleasedMode ? '#059669' : '#0025cc';
            batchConfirmProceed.style.color = '#ffffff';

            batchConfirmModal.classList.remove('hidden');
            batchConfirmModal.classList.add('flex');
        }

        document.addEventListener('click', function (event) {
            var batchBtn = event.target.closest('[data-pkg-batch-btn]');
            if (batchBtn) {
                event.stopPropagation();
                openBatchConfirmModal(batchBtn);
            }
        });
        if (batchConfirmCancel) {
            batchConfirmCancel.addEventListener('click', closeBatchConfirmModal);
        }
        if (batchConfirmModal) {
            batchConfirmModal.addEventListener('click', function (event) {
                if (event.target === batchConfirmModal) closeBatchConfirmModal();
            });
        }
        if (batchConfirmForm && batchConfirmProceed) {
            batchConfirmForm.addEventListener('submit', function () {
                batchConfirmProceed.disabled = true;
                batchConfirmProceed.textContent = 'Processing...';
            });
        }

        var searchInput = document.getElementById('pkgSearchInput');
        var urgencySelect = document.getElementById('pkgUrgencySelect');
        var dateBtn = document.getElementById('pkgDateBtn');
        var dateInput = document.getElementById('pkgDateInput');
        var dateClear = document.getElementById('pkgDateClear');
        var dateClearText = document.getElementById('pkgDateClearText');
        var noMatchRow = document.getElementById('pkgNoMatchRow');
        var soloRows = Array.prototype.slice.call(document.querySelectorAll('tr[data-pkg-solo]'));
        var groupHeaders = Array.prototype.slice.call(document.querySelectorAll('tr[data-pkg-group-header]'));
        var allPkgRows = Array.prototype.slice.call(document.querySelectorAll('tr[data-pkg-row]'));
        var filterTabs = Array.prototype.slice.call(document.querySelectorAll('[data-pkg-filter]'));
        var activeFilter = @json($defaultPkgFilter);
        var openGroups = {};

        function setGroupExpanded(key, expanded) {
            var header = document.querySelector('tr[data-pkg-group-header="' + key + '"]');
            var body = document.querySelector('tr[data-pkg-group-body="' + key + '"]');
            var chevron = document.querySelector('[data-pkg-group-chevron="' + key + '"]');
            if (header) header.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            if (body) body.classList.toggle('hidden', !expanded);
            if (chevron) chevron.style.transform = expanded ? 'rotate(90deg)' : '';
        }

        groupHeaders.forEach(function (header) {
            header.addEventListener('click', function (event) {
                if (event.target.closest('[data-pkg-batch-btn]')) return;
                var key = header.getAttribute('data-pkg-group-toggle');
                if (!key) return;
                openGroups[key] = !openGroups[key];
                setGroupExpanded(key, !!openGroups[key]);
            });
        });

        function paintFilterTabs() {
            filterTabs.forEach(function (tab) {
                var on = tab.getAttribute('data-pkg-filter') === activeFilter;
                tab.style.background = on ? '#ffffff' : 'transparent';
                tab.style.color = on ? '#0f172a' : '#64748b';
                tab.style.boxShadow = on ? '0 1px 2px rgba(15,23,42,0.08)' : 'none';
            });
        }

        function rowMatches(row, q, u, d) {
            var rowState = row.getAttribute('data-pkg-state') || 'for_release';
            var rowUrgency = row.getAttribute('data-pkg-urgency') || 'non_urgent';
            var rowSearch = row.getAttribute('data-pkg-search') || '';
            var rowDate = row.getAttribute('data-pkg-date') || '';
            var matchF = activeFilter === 'all' || rowState === activeFilter;
            var matchU = u === '' || rowUrgency === u;
            var matchQ = q === '' || rowSearch.indexOf(q) !== -1;
            var matchD = d === '' || rowDate === d;
            return matchF && matchU && matchQ && matchD;
        }

        function applyPkgFilters() {
            var q = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
            var u = urgencySelect ? String(urgencySelect.value || '').trim() : '';
            var d = dateInput ? String(dateInput.value || '').trim() : '';
            var visible = 0;

            soloRows.forEach(function (row) {
                var show = rowMatches(row, q, u, d);
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            groupHeaders.forEach(function (header) {
                var key = header.getAttribute('data-pkg-group-header');
                var body = document.querySelector('tr[data-pkg-group-body="' + key + '"]');
                var children = Array.prototype.slice.call(document.querySelectorAll('tr[data-pkg-child-row="' + key + '"]'));
                var countEl = document.querySelector('[data-pkg-group-count="' + key + '"]');
                var matchedChildren = 0;
                children.forEach(function (child) {
                    var showChild = rowMatches(child, q, u, d);
                    child.classList.toggle('hidden', !showChild);
                    if (showChild) matchedChildren++;
                });

                if (matchedChildren === 0) {
                    header.classList.add('hidden');
                    if (body) body.classList.add('hidden');
                } else {
                    header.classList.remove('hidden');
                    if (countEl) countEl.textContent = String(matchedChildren);
                    var shouldExpand = q !== '' || !!openGroups[key];
                    setGroupExpanded(key, shouldExpand);
                    visible += matchedChildren;
                }
            });

            if (noMatchRow) {
                noMatchRow.classList.toggle('hidden', allPkgRows.length === 0 || visible > 0);
            }
            if (dateClear && dateClearText) {
                if (d !== '') {
                    dateClearText.textContent = d;
                    dateClear.classList.remove('hidden');
                    if (dateBtn) {
                        dateBtn.style.background = '#eff6ff';
                        dateBtn.style.borderColor = '#bfdbfe';
                        dateBtn.style.color = '#0025cc';
                    }
                } else {
                    dateClearText.textContent = '';
                    dateClear.classList.add('hidden');
                    if (dateBtn) {
                        dateBtn.style.background = '';
                        dateBtn.style.borderColor = '';
                        dateBtn.style.color = '';
                    }
                }
            }
        }

        filterTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activeFilter = tab.getAttribute('data-pkg-filter') || 'all';
                paintFilterTabs();
                applyPkgFilters();
            });
        });

        if (urgencySelect) {
            urgencySelect.addEventListener('change', applyPkgFilters);
        }

        paintFilterTabs();
        applyPkgFilters();

        if (searchInput) {
            searchInput.addEventListener('input', applyPkgFilters);
        }
        if (dateBtn && dateInput) {
            dateBtn.addEventListener('click', function () {
                try {
                    if (typeof dateInput.showPicker === 'function') {
                        dateInput.showPicker();
                    } else {
                        dateInput.focus();
                        dateInput.click();
                    }
                } catch (e) {
                    dateInput.focus();
                    dateInput.click();
                }
            });
            dateInput.addEventListener('change', applyPkgFilters);
        }
        if (dateClear && dateInput) {
            dateClear.addEventListener('click', function () {
                dateInput.value = '';
                applyPkgFilters();
            });
        }
    })();
</script>
@endsection
