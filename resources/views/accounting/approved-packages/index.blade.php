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
            <table class="w-full min-w-[1050px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC No.</th>
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
                    @forelse($records as $item)
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
                        <tr class="transition hover:bg-gray-50/70" data-pkg-row data-pkg-state="{{ !empty($item->check_released) ? 'released' : 'for_release' }}" data-pkg-urgency="{{ !empty($item->urgent_row) ? 'urgent' : 'non_urgent' }}" data-pkg-date="{{ $rowIsoDate }}" data-pkg-search="{{ $rowSearchBlob }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
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
                                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl transition hover:opacity-85"
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
            if (confirmModal && !confirmModal.classList.contains('hidden')) {
                closeConfirmModal();
                return;
            }
            if (!modal.classList.contains('hidden')) closeModal();
        });

        var searchInput = document.getElementById('pkgSearchInput');
        var urgencySelect = document.getElementById('pkgUrgencySelect');
        var dateBtn = document.getElementById('pkgDateBtn');
        var dateInput = document.getElementById('pkgDateInput');
        var dateClear = document.getElementById('pkgDateClear');
        var dateClearText = document.getElementById('pkgDateClearText');
        var noMatchRow = document.getElementById('pkgNoMatchRow');
        var pkgRows = Array.prototype.slice.call(document.querySelectorAll('tr[data-pkg-row]'));
        var filterTabs = Array.prototype.slice.call(document.querySelectorAll('[data-pkg-filter]'));
        var activeFilter = @json($defaultPkgFilter);

        function paintFilterTabs() {
            filterTabs.forEach(function (tab) {
                var on = tab.getAttribute('data-pkg-filter') === activeFilter;
                tab.style.background = on ? '#ffffff' : 'transparent';
                tab.style.color = on ? '#0f172a' : '#64748b';
                tab.style.boxShadow = on ? '0 1px 2px rgba(15,23,42,0.08)' : 'none';
            });
        }

        function applyPkgFilters() {
            var q = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
            var u = urgencySelect ? String(urgencySelect.value || '').trim() : '';
            var d = dateInput ? String(dateInput.value || '').trim() : '';
            var visible = 0;
            pkgRows.forEach(function (row) {
                var rowState = row.getAttribute('data-pkg-state') || 'for_release';
                var rowUrgency = row.getAttribute('data-pkg-urgency') || 'non_urgent';
                var rowSearch = row.getAttribute('data-pkg-search') || '';
                var rowDate = row.getAttribute('data-pkg-date') || '';
                var matchF = activeFilter === 'all' || rowState === activeFilter;
                var matchU = u === '' || rowUrgency === u;
                var matchQ = q === '' || rowSearch.indexOf(q) !== -1;
                var matchD = d === '' || rowDate === d;
                var show = matchF && matchU && matchQ && matchD;
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            if (noMatchRow) {
                noMatchRow.classList.toggle('hidden', pkgRows.length === 0 || visible > 0);
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
