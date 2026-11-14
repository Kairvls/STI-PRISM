@extends('layouts.president-layout')

@section('title', $dv->dv_form_number ?: 'Disbursement Voucher')

@section('content')
@include('accounting.partials.flash')
@include('accounting.partials.print-form')

@php
    $rfc = $package['rfc'] ?? null;
    $requestNo = $dv->dv_request_no ?? ($rfc->request_check_request_no ?? null);
@endphp

<div class="mx-auto max-w-[900px] space-y-4 p-6">
    <div class="print-hidden flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('president.dv.index') }}" class="text-sm text-slate-500 hover:text-[#0025cc]">← Disbursement Vouchers</a>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Status: <span class="font-medium text-slate-800">{{ $dv->dv_status }}</span>
                @if($requestNo)
                    · @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true])
                @endif
            </p>
            <p class="mt-1 text-xs text-slate-400">
                @if(filled(optional($rfc)->request_check_form_number) || !empty($package['atps']))
                    Request:
                    {{ optional($rfc)->request_check_form_number }}
                    @foreach($package['atps'] ?? [] as $atp)
                        @if(filled($atp->authority_purchase_form_number ?? null))
                            · {{ $atp->authority_purchase_form_number }}
                        @endif
                    @endforeach
                @endif
            </p>
        </div>
        <button
            type="button"
            onclick="window.accountingPrintForm && window.accountingPrintForm({ sheetId: 'dv-print-{{ $dv->dv_id }}', page: 'portrait' })"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >Print</button>
    </div>

    @include('partials.disbursement-voucher-paper', [
        'dv' => $dv,
        'editable' => false,
        'printId' => 'dv-print-'.$dv->dv_id,
        'presidentNameEditable' => (bool) $reviewable,
        'presidentNamePreview' => $reviewable
            ? (\App\Support\AccountingSigner::currentUserName() ?: 'President')
            : '',
    ])

    @if($reviewable)
        <div class="print-hidden flex flex-wrap items-center justify-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
            <button
                type="button"
                onclick="window.history.back()"
                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-900"
            >
                Close
            </button>
            <button
                type="button"
                onclick="openDvPresidentApproveModal()"
                class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-[#001db3]"
            >
                Approve
            </button>
        </div>

        <div id="dvPresidentApproveModal" class="confirm-modal hidden print-hidden" style="position:fixed; inset:0; z-index:12050;">
            <div class="confirm-backdrop" onclick="closeDvPresidentApproveModal()" style="position:fixed; inset:0;"></div>
            <div class="confirm-card wide" onclick="event.stopPropagation()" style="position:relative; z-index:1; max-height: min(92vh, 820px); overflow: auto;">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3>Sign to approve</h3>
                        <p>{{ $dv->dv_form_number ?: 'Disbursement Voucher' }} — signature overlays your printed name under <strong>Approved for Payment</strong>.</p>
                    </div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" onclick="closeDvPresidentApproveModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <form id="dvPresidentApproveForm" method="POST" action="{{ route('president.dv.approve', $dv->dv_id) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="signature_data" id="signatureData" value="">
                    <input type="hidden" name="signature_used" id="signatureUsed" value="0">
                    @include('accounting.partials.decision-signature-panel', [
                        'savedSignatures' => \App\Support\UserSignatureLibrary::forUser((int) auth()->id()),
                        'printedNameDefault' => \App\Support\AccountingSigner::currentUserName()
                            ?: (\App\Support\AccountingSigner::fromUserId((int) ($dv->dv_assigned_president_id ?? 0)) ?: 'President'),
                        'signTitle' => 'President signature',
                        'signHint' => 'Pick a saved signature, draw one, or upload. Printed name under Approved for Payment can still be edited.',
                        'padTitle' => 'Sign the DV',
                        'padHint' => 'Sign clearly. This overlays your printed name on Approved for Payment.',
                    ])
                    <div class="confirm-actions mt-5 flex items-center justify-end gap-2">
                        <button type="button" class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100" onclick="closeDvPresidentApproveModal()">Cancel</button>
                        <button type="button" class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-[#001db3]" onclick="submitDvPresidentApprove()">Approve</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            .btn-ghost, .btn-reject, .btn-approve {
                border: 0;
                border-radius: 0.5rem;
                padding: 8px 16px;
                font-size: 0.875rem;
                line-height: 1.25rem;
                font-weight: 500;
                cursor: pointer;
            }
            .btn-ghost { background: #f8fafc; color: #475569; }
            .btn-ghost:hover { background: #f1f5f9; color: #0f172a; }
            .btn-approve { background: #059669; color: #fff; }
            .btn-approve:hover { background: #047857; }
        </style>

        <script>
            let dvPresidentApproveInFlight = false;

            function syncPresidentPrintedNameFromPaper() {
                const paperName = document.getElementById('accPaperSigPrintedName');
                const modalName = document.getElementById('accSigPrintedName');
                if (!paperName || !modalName) return;
                const next = String(paperName.value || '').trim();
                if (next === '') return;
                modalName.value = next;
                modalName.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function openDvPresidentApproveModal() {
                const modal = document.getElementById('dvPresidentApproveModal');
                if (!modal) return;
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                if (window.accountingSignaturePanel && typeof window.accountingSignaturePanel.clear === 'function') {
                    window.accountingSignaturePanel.clear();
                } else if (window.accountingSignaturePanel) {
                    window.accountingSignaturePanel.reset();
                }
                syncPresidentPrintedNameFromPaper();
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                if (window.lucide) lucide.createIcons();
            }

            function closeDvPresidentApproveModal() {
                const modal = document.getElementById('dvPresidentApproveModal');
                if (modal) modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                if (window.accountingSignaturePanel && typeof window.accountingSignaturePanel.clear === 'function') {
                    window.accountingSignaturePanel.clear();
                } else if (window.accountingSignaturePanel) {
                    window.accountingSignaturePanel.reset();
                }
            }

            function submitDvPresidentApprove() {
                if (dvPresidentApproveInFlight) return;
                const form = document.getElementById('dvPresidentApproveForm');
                if (!form) return;
                syncPresidentPrintedNameFromPaper();
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
                    closeDvPresidentApproveModal();
                    if (typeof window.showMpToast === 'function') {
                        showMpToast(data.message || 'Disbursement Voucher approved successfully.', { title: 'Approved', type: 'success' });
                    }
                    setTimeout(() => window.location.reload(), 900);
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

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') return;
                const modal = document.getElementById('dvPresidentApproveModal');
                if (modal && !modal.classList.contains('hidden')) closeDvPresidentApproveModal();
            });
        </script>
    @endif
</div>
@endsection
