@extends('layouts.accounting-layout')

@section('title', 'ATP + RFC')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')
@include('accounting.partials.flash')

<div class="acc-page space-y-6">
    <div class="pur-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($records as $item)
                        <tr class="transition hover:bg-gray-50/70">
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
                                            <span class="inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-800 ring-1 ring-inset ring-violet-200">{{ $item->fund_label }}</span>
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
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                {{ !empty($item->date) ? \Carbon\Carbon::parse($item->date)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                @if(!empty($item->check_ready))
                                    @include('accounting.partials.status-badge', ['status' => \App\Support\ProcurementStatus::READY_FOR_COLLECTION])
                                @else
                                    @include('accounting.partials.status-badge', ['status' => \App\Support\ProcurementStatus::ACCOUNTING_NEED_REVISION])
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="View"
                                        aria-label="View"
                                        data-pkg-view
                                        data-title="{{ $item->ref }}"
                                        data-fund="{{ $item->fund_label ?? '' }}"
                                        data-rfc-url="{{ $item->rfc_preview }}"
                                        data-atp-url="{{ $item->atp_preview }}"
                                        data-atp-label="{{ $item->atp_no ?: 'ATP' }}"
                                        data-ready="{{ !empty($item->check_ready) ? '1' : '0' }}"
                                        data-rfc-id="{{ $item->rfc_id }}"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    @if(empty($item->check_ready))
                                        <form method="POST" action="{{ route('accounting.approved-packages.check-release', $item->rfc_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-[#001db3]">Check release</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('accounting.approved-packages.check-released', $item->rfc_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">Check released</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center text-sm text-gray-500">No approved ATP + RFC is waiting.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="pkgViewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 id="pkgViewTitle" class="truncate text-base font-semibold text-gray-900"></h3>
                    <span id="pkgViewFund" class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-semibold text-violet-800 ring-1 ring-inset ring-violet-200"></span>
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
        <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-100">
            <div id="pkgViewTrack" class="flex h-[72vh] w-[200%] transition-transform duration-300">
                <iframe id="pkgViewRfc" title="Funding request" class="h-full w-1/2 shrink-0 border-0 bg-white"></iframe>
                <iframe id="pkgViewAtp" title="Authority to Purchase" class="h-full w-1/2 shrink-0 border-0 bg-white"></iframe>
            </div>
        </div>
        <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-4 py-3">
            <span class="w-[8.5rem]"></span>
            <div class="flex items-center justify-center gap-2">
                <button type="button" data-pkg-dot="0" class="pkg-dot rounded-full px-3 py-1 text-xs font-semibold">RFC</button>
                <button type="button" data-pkg-dot="1" class="pkg-dot rounded-full px-3 py-1 text-xs font-semibold">ATP</button>
            </div>
            <form id="pkgCheckForm" method="POST" class="flex min-w-[8.5rem] justify-end">
                @csrf
                <button id="pkgCheckButton" type="submit" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-[#001db3]">Check release</button>
            </form>
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
        var rfcFrame = document.getElementById('pkgViewRfc');
        var atpFrame = document.getElementById('pkgViewAtp');
        var index = 0;
        var labels = ['Funding request', 'Authority to Purchase'];

        function paint() {
            track.style.transform = 'translateX(' + (-index * 50) + '%)';
            slideLabel.textContent = labels[index] + ' · ' + (index + 1) + ' of 2';
            document.querySelectorAll('.pkg-dot').forEach(function (dot) {
                var on = Number(dot.getAttribute('data-pkg-dot')) === index;
                dot.className = 'pkg-dot rounded-full px-3 py-1 text-xs font-semibold ' + (on
                    ? 'bg-[#0025cc] text-white'
                    : 'bg-gray-100 text-gray-600');
            });
        }

        function openModal(button) {
            index = 0;
            title.textContent = button.getAttribute('data-title') || 'ATP + RFC';
            var method = button.getAttribute('data-fund') || '';
            fund.textContent = method;
            fund.hidden = method === '' || method === 'Not chosen';
            var ready = button.getAttribute('data-ready') === '1';
            var rfcId = button.getAttribute('data-rfc-id');
            var form = document.getElementById('pkgCheckForm');
            var action = document.getElementById('pkgCheckButton');
            form.action = '/accounting/approved-packages/' + rfcId + (ready ? '/check-released' : '/check-release');
            action.textContent = ready ? 'Check released' : 'Check release';
            action.className = ready
                ? 'inline-flex h-9 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 hover:bg-emerald-100'
                : 'inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-[#001db3]';
            rfcFrame.src = button.getAttribute('data-rfc-url') || 'about:blank';
            atpFrame.src = button.getAttribute('data-atp-url') || 'about:blank';
            labels[1] = button.getAttribute('data-atp-label') || 'Authority to Purchase';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            paint();
            if (window.lucide) window.lucide.createIcons();
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            rfcFrame.src = 'about:blank';
            atpFrame.src = 'about:blank';
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
            index = index === 0 ? 1 : 0;
            paint();
        });
        document.getElementById('pkgViewNext').addEventListener('click', function () {
            index = index === 0 ? 1 : 0;
            paint();
        });
        document.getElementById('pkgViewClose').addEventListener('click', closeModal);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();
</script>
@endsection
