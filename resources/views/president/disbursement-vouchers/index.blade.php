@extends('layouts.president-layout')

@section('title', 'Disbursement Vouchers')

@section('content')
@include('accounting.partials.flash')
@include('accounting.partials.print-form')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Awaiting signature',
        'approved' => 'Signed / released',
        'revision' => 'Returned to Accounting',
    ];
    $viewDvId = (int) ($viewDvId ?? 0);
    $modalDvs = $modalDvs ?? collect();
    $modalPackages = $modalPackages ?? [];
    $savedSignatures = $savedSignatures ?? \App\Support\UserSignatureLibrary::forUser((int) auth()->id());
    $presidentPrintedDefault = \App\Support\AccountingSigner::currentUserName() ?: 'President';
@endphp

<div
    class="space-y-6"
    x-data="{
        selectedDv: {{ $viewDvId ?: 'null' }},
        viewOpen: {{ $viewDvId ? 'true' : 'false' }},
        signOpen: false,
        modalFullscreen: false,
        openView(id) {
            this.selectedDv = id;
            this.viewOpen = true;
            this.signOpen = false;
            this.modalFullscreen = false;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        closeView() {
            this.viewOpen = false;
            this.signOpen = false;
            this.selectedDv = null;
            this.modalFullscreen = false;
            document.body.classList.remove('overflow-hidden');
        },
        openSign() {
            this.signOpen = true;
            this.$nextTick(() => {
                const modal = document.getElementById('dvPresidentApproveModal');
                if (modal && modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                if (window.accountingSignaturePanel && typeof window.accountingSignaturePanel.clear === 'function') {
                    window.accountingSignaturePanel.clear();
                }
                const paperName = document.getElementById('accPaperSigPrintedName-' + this.selectedDv)
                    || document.querySelector('[data-president-paper-name]')
                    || document.getElementById('accPaperSigPrintedName');
                const modalName = document.getElementById('accSigPrintedName');
                if (paperName && modalName) {
                    const next = String(paperName.value || '').trim();
                    if (next !== '') {
                        modalName.value = next;
                        modalName.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
                const form = document.getElementById('dvPresidentApproveForm');
                if (form && this.selectedDv) {
                    form.action = @js(url('/president/disbursement-vouchers')) + '/' + this.selectedDv + '/approve';
                }
                document.body.classList.add('overflow-hidden');
                if (window.lucide) window.lucide.createIcons();
            });
        },
        closeSign() {
            this.signOpen = false;
            if (window.accountingSignaturePanel && typeof window.accountingSignaturePanel.clear === 'function') {
                window.accountingSignaturePanel.clear();
            }
            if (!this.viewOpen) {
                document.body.classList.remove('overflow-hidden');
            }
        },
        printDv(id) {
            if (window.accountingPrintForm) {
                window.accountingPrintForm({ sheetId: 'dv-print-' + id, page: 'portrait' });
                return;
            }
            window.print();
        }
    }"
    x-effect="document.body.classList.toggle('overflow-hidden', viewOpen || signOpen)"
>
    <nav
        role="tablist"
        aria-label="Disbursement voucher filters"
        class="flex min-w-0 flex-wrap items-center gap-1"
    >
        @foreach($filters as $key => $label)
            <a
                href="{{ route('president.dv.index', ['status' => $key]) }}"
                role="tab"
                aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                class="shrink-0 rounded-lg px-3 py-2 text-sm transition {{ $filter === $key
                    ? 'bg-gray-100/80 font-medium text-black'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}"
            >
                {{ $label }}
                <span class="ml-1 tabular-nums">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-gray-50/70">
                <tr class="border-b border-gray-100">
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">DV</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee / RFC</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/70">
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $row->dv_form_number ?: 'Disbursement Voucher' }}</td>
                        <td class="px-5 py-4 text-gray-600">
                            @include('partials.procurement-request-no', [
                                'requestNo' => $row->dv_request_no ?: ($row->rfc_request_no ?: null),
                                'inline' => true,
                                'showLabel' => false,
                            ])
                            @if(blank($row->dv_request_no ?: ($row->rfc_request_no ?? null)))
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            <div>{{ $row->dv_payee ?: ($row->request_check_payee ?: '—') }}</div>
                            <div class="text-xs text-gray-400">{{ $row->request_check_form_number ?: ('RFC #'.$row->dv_request_check_id) }}</div>
                        </td>
                        <td class="px-5 py-4 tabular-nums text-gray-700">
                            {{ $row->dv_amount !== null ? '₱'.number_format((float) $row->dv_amount, 2) : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $row->dv_status }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                @include('partials.share-document-button', [
                                    'type' => 'dv',
                                    'id' => $row->dv_id,
                                    'label' => \App\Support\RequestExplorer::shareUiLabel('dv', $row->dv_form_number ?? null),
                                    'variant' => 'button',
                                ])
                                <button
                                    type="button"
                                    @click="openView({{ (int) $row->dv_id }})"
                                    class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-[#001db3]"
                                >Open</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-500">No Disbursement Vouchers in this view.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>

    @foreach($modalDvs as $dv)
        @php
            $dvId = (int) $dv->dv_id;
            $pkg = $modalPackages[$dvId] ?? [];
            $rfc = $pkg['rfc'] ?? null;
            $reviewable = ($dv->dv_status ?? '') === 'With President';
            $requestNo = $dv->dv_request_no ?? ($rfc->request_check_request_no ?? null);
        @endphp
        <template x-teleport="body">
            <div
                x-show="viewOpen && selectedDv === {{ $dvId }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                @keydown.escape.window="if (signOpen) { closeSign(); } else if (viewOpen && selectedDv === {{ $dvId }}) { closeView(); }"
                role="dialog"
                aria-modal="true"
                aria-labelledby="president-dv-title-{{ $dvId }}"
            >
                <div x-on:click.self="closeView()" class="flex w-full justify-center" :class="modalFullscreen ? 'min-h-[100dvh] h-auto items-stretch' : 'min-h-full'">
                    <div
                        @click.stop
                        class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                        :class="modalFullscreen ? 'my-0 min-h-[100dvh] h-auto max-w-none rounded-none shadow-none' : 'my-auto max-w-[900px] rounded-xl shadow-2xl'"
                    >
                        <div class="print-hidden flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4">
                            <div class="min-w-0">
                                <h3 id="president-dv-title-{{ $dvId }}" class="text-lg font-semibold text-slate-900">{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Status: {{ $dv->dv_status }}
                                    @if($requestNo)
                                        · @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true])
                                    @endif
                                </p>
                                @if(filled(optional($rfc)->request_check_form_number))
                                    <p class="mt-1 text-xs text-slate-400">{{ $rfc->request_check_form_number }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" @click="modalFullscreen = !modalFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100" :aria-label="modalFullscreen ? 'Exit full screen' : 'Full screen'">
                                    <i :data-lucide="modalFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                                </button>
                                <button type="button" @click="printDv({{ $dvId }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100" aria-label="Print">
                                    <i data-lucide="printer" class="h-4 w-4"></i>
                                </button>
                                <button type="button" @click="closeView()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-100" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>

                        <div class="px-4 py-4 sm:px-6">
                            @include('partials.disbursement-voucher-paper', [
                                'dv' => $dv,
                                'editable' => false,
                                'printId' => 'dv-print-'.$dvId,
                                'presidentNameEditable' => $reviewable,
                                'presidentNamePreview' => $reviewable ? $presidentPrintedDefault : '',
                                'presidentPaperNameId' => 'accPaperSigPrintedName-'.$dvId,
                            ])
                        </div>

                        <div class="print-hidden flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-5 py-4">
                            <button type="button" @click="closeView()" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-900">Close</button>
                            @if($reviewable)
                                <button
                                    type="button"
                                    @click="openSign()"
                                    class="rounded-lg bg-[#0025cc] px-3 py-2 text-sm font-semibold text-white hover:bg-[#001db3]"
                                >Approve</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endforeach

    <div
        id="dvPresidentApproveModal"
        class="confirm-modal print-hidden"
        x-show="signOpen"
        x-cloak
        style="position:fixed; inset:0; z-index:12050;"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dvPresidentApproveTitle"
    >
        <div class="confirm-backdrop" @click="closeSign()" style="position:fixed; inset:0;"></div>
        <div class="confirm-card wide" @click.stop style="position:relative; z-index:1; max-height: min(92vh, 820px); overflow: auto;">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 id="dvPresidentApproveTitle">Sign to approve</h3>
                    <p>Signature overlays your printed name under <strong>Approved for Payment</strong>.</p>
                </div>
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" @click="closeSign()" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <form id="dvPresidentApproveForm" method="POST" action="#" class="mt-4">
                @csrf
                <input type="hidden" name="signature_data" id="signatureData" value="">
                <input type="hidden" name="signature_used" id="signatureUsed" value="0">
                @include('accounting.partials.decision-signature-panel', [
                    'savedSignatures' => $savedSignatures,
                    'printedNameDefault' => $presidentPrintedDefault,
                    'signTitle' => 'President signature',
                    'signHint' => 'Pick a saved signature, draw one, or upload. Printed name under Approved for Payment can still be edited.',
                    'padTitle' => 'Sign the DV',
                    'padHint' => 'Sign clearly. This overlays your printed name on Approved for Payment.',
                ])
                <div class="confirm-actions mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100" @click="closeSign()">Cancel</button>
                    <button type="button" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-[#001db3]" onclick="submitDvPresidentApprove()">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let dvPresidentApproveInFlight = false;

    function submitDvPresidentApprove() {
        if (dvPresidentApproveInFlight) return;
        const form = document.getElementById('dvPresidentApproveForm');
        if (!form || !form.action || form.action.endsWith('#')) {
            if (typeof window.showMpToast === 'function') {
                showMpToast('Open a Disbursement Voucher first.', { title: 'Unable to approve', type: 'warning' });
            }
            return;
        }

        const root = document.querySelector('[x-data]');
        const selectedId = root && window.Alpine ? (Alpine.$data(root).selectedDv || '') : '';
        const paperName = (selectedId ? document.getElementById('accPaperSigPrintedName-' + selectedId) : null)
            || document.querySelector('[data-president-paper-name]')
            || document.getElementById('accPaperSigPrintedName');
        const modalName = document.getElementById('accSigPrintedName');
        if (paperName && modalName) {
            const next = String(paperName.value || '').trim();
            if (next !== '') {
                modalName.value = next;
            }
        }

        const hasSig = window.accountingSignaturePanel
            ? window.accountingSignaturePanel.hasSignature()
            : String(document.getElementById('signatureData')?.value || '').indexOf('data:image/') === 0;
        if (!hasSig) {
            if (typeof window.showMpToast === 'function') {
                showMpToast('Please add a signature before approving.', { title: 'Signature required', type: 'warning', timer: 3600 });
            } else {
                alert('Please add a signature before approving.');
            }
            return;
        }

        const used = document.getElementById('signatureUsed');
        if (used) used.value = '1';
        dvPresidentApproveInFlight = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to save decision.');
            if (typeof window.showMpToast === 'function') {
                showMpToast(data.message || 'Disbursement Voucher approved successfully.', { title: 'Approved', type: 'success' });
            }
            setTimeout(() => window.location.href = @json(route('president.dv.index', ['status' => 'approved'])), 900);
        })
        .catch(error => {
            if (typeof window.showMpToast === 'function') {
                showMpToast(error.message || 'Unable to save decision.', { title: 'Unable to complete', type: 'error', timer: 4200 });
            } else {
                alert(error.message);
            }
        })
        .finally(() => { dvPresidentApproveInFlight = false; });
    }
</script>
@endsection
