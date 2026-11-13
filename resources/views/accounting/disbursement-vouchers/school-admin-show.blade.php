@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', $dv->dv_form_number ?: 'Disbursement Voucher')

@section('content')
@include('accounting.partials.flash')
@include('accounting.partials.print-form')

@php
    $rfc = $package['rfc'] ?? null;
@endphp

<div class="acc-page acc-page--review fade-in">
    <div class="acc-review-head print-hidden">
        <div class="min-w-0">
            <a href="{{ route('school-admin.dv.index') }}" class="acc-back" data-tip="Back to DV queue" aria-label="Back to DV queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</h1>
                @include('accounting.partials.status-badge', ['status' => $dv->dv_status])
            </div>
            @php
                $dvRequestNo = trim((string) ($dv->dv_request_no ?? ($rfc->request_check_request_no ?? '')));
            @endphp
            @if($dvRequestNo !== '')
                <div class="mt-2">
                    @include('partials.procurement-request-no', ['requestNo' => $dvRequestNo, 'inline' => true, 'size' => 'md'])
                </div>
            @endif
        </div>
        <div class="acc-actions shrink-0">
            @if($reviewable)
                <button type="button" class="acc-action-btn is-approve" data-tip="Approve disbursement voucher" onclick="openDvApproveModal()">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    <span>Approve</span>
                </button>
                <button type="button" class="acc-action-btn is-revise" data-tip="Send back to Accounting for revision" onclick="openDvReviseModal()">
                    <i data-lucide="undo-2" class="h-4 w-4"></i>
                    <span>Return for Revision</span>
                </button>
            @endif
            <button type="button" onclick="window.accountingPrintForm && window.accountingPrintForm({ sheetId: 'dv-print-{{ $dv->dv_id }}', page: 'portrait' })" class="acc-action-btn" data-tip="Print DV">
                <i data-lucide="printer" class="h-4 w-4"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    @if($reviewable)
        <div id="dvApproveModal" class="confirm-modal hidden print-hidden" style="z-index: 12050;">
            <div class="confirm-backdrop" onclick="closeDvApproveModal()"></div>
            <div class="confirm-card wide" onclick="event.stopPropagation()">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3>Sign to approve</h3>
                        <p>{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</p>
                    </div>
                    <button type="button" class="icon-close" onclick="closeDvApproveModal()" aria-label="Close"><i data-lucide="x"></i></button>
                </div>
                <form id="dvApproveForm" method="POST" action="{{ route('school-admin.dv.approve', $dv->dv_id) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="signature_data" id="signatureData" value="">
                    <input type="hidden" name="signature_used" id="signatureUsed" value="0">
                    @include('accounting.partials.decision-signature-panel', [
                        'savedSignatures' => \App\Support\UserSignatureLibrary::forUser((int) auth()->id()),
                        'signTitle' => 'School Administrator signature',
                        'signHint' => 'Pick a saved signature, draw one, or upload. It authorizes this Disbursement Voucher.',
                        'padTitle' => 'Sign the DV',
                        'padHint' => 'Sign clearly. This overlays your printed name on the approval.',
                    ])
                    <div class="mt-4">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3" maxlength="2000" placeholder="Optional for approve."></textarea>
                    </div>
                    <div class="confirm-actions mt-5">
                        <button type="button" class="btn-ghost" onclick="closeDvApproveModal()">Cancel</button>
                        <button type="button" class="btn-approve" onclick="submitDvApprove()">Approve</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="dvReviseModal" class="confirm-modal hidden print-hidden" style="z-index: 12050;">
            <div class="confirm-backdrop" onclick="closeDvReviseModal()"></div>
            <div class="confirm-card wide" onclick="event.stopPropagation()">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3>Return for revision</h3>
                        <p>{{ $dv->dv_form_number ?: 'Disbursement Voucher' }}</p>
                    </div>
                    <button type="button" class="icon-close" onclick="closeDvReviseModal()" aria-label="Close"><i data-lucide="x"></i></button>
                </div>
                <form method="POST" action="{{ route('school-admin.dv.revise', $dv->dv_id) }}" class="mt-4">
                    @csrf
                    <label>Remarks <span class="text-red-500">*</span></label>
                    <textarea name="remarks" rows="3" required maxlength="2000" placeholder="What should Accounting fix?"></textarea>
                    <div class="confirm-actions mt-5">
                        <button type="button" class="btn-ghost" onclick="closeDvReviseModal()">Cancel</button>
                        <button type="submit" class="btn-reject">Send back</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div class="min-w-0">
                <div class="acc-viewer">
                    <div class="acc-viewer-stage">
                        <div class="acc-viewer-fit">
                            @include('partials.disbursement-voucher-paper', [
                                'dv' => $dv,
                                'editable' => false,
                                'printId' => 'dv-print-'.$dv->dv_id,
                            ])
                        </div>
                    </div>
                </div>
            </div>
            <div class="acc-side-stack print-hidden">
                @include('accounting.partials.related-docs', [
                    'chain' => $chain ?? [],
                    'current' => 'dv',
                ])
                @include('accounting.partials.history', ['history' => $history ?? collect()])
                @if(! empty($dv->dv_revision_reason))
                    <div class="acc-note acc-note-info">
                        Last revision note: {{ $dv->dv_revision_reason }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($reviewable)
<script>
    let dvApproveInFlight = false;

    function openDvApproveModal() {
        const modal = document.getElementById('dvApproveModal');
        if (!modal) return;
        const form = document.getElementById('dvApproveForm');
        const remarks = form?.querySelector('textarea[name="remarks"]');
        if (remarks) remarks.value = '';
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
        modal.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeDvApproveModal() {
        document.getElementById('dvApproveModal')?.classList.add('hidden');
        if (window.accountingSignaturePanel) window.accountingSignaturePanel.reset();
    }

    function openDvReviseModal() {
        document.getElementById('dvReviseModal')?.classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeDvReviseModal() {
        document.getElementById('dvReviseModal')?.classList.add('hidden');
    }

    function submitDvApprove() {
        if (dvApproveInFlight) return;
        const form = document.getElementById('dvApproveForm');
        if (!form) return;
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
        dvApproveInFlight = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: new FormData(form)
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) throw new Error(data.message || 'Unable to save decision.');
            closeDvApproveModal();
            if (typeof window.showMpToast === 'function') {
                showMpToast('Disbursement Voucher approved successfully.', { title: 'Approved', type: 'success' });
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
        .finally(() => { dvApproveInFlight = false; });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        const approve = document.getElementById('dvApproveModal');
        const revise = document.getElementById('dvReviseModal');
        if (approve && !approve.classList.contains('hidden')) closeDvApproveModal();
        else if (revise && !revise.classList.contains('hidden')) closeDvReviseModal();
    });
</script>
@endif
@endsection
