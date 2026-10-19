@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', $dv->dv_form_number ?: 'Disbursement Voucher')

@section('content')
@include('accounting.partials.flash')
@include('accounting.partials.print-form')

@php
    $rfc = $package['rfc'] ?? null;
@endphp

<div class="acc-page acc-page--review fade-in p-6">
    <div class="acc-review-head print-hidden">
        <div>
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
            <p class="mt-1 text-xs text-slate-400">
                @if(filled(optional($rfc)->request_check_form_number))
                    Request Check: {{ $rfc->request_check_form_number }}
                @endif
            </p>
        </div>
        <div class="acc-actions">
            @if($reviewable)
                <form method="POST" action="{{ route('school-admin.dv.approve', $dv->dv_id) }}" class="inline">
                    @csrf
                    <button type="submit" class="acc-action-btn is-approve">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        <span>Approve</span>
                    </button>
                </form>
            @endif
            <button type="button" onclick="window.accountingPrintForm && window.accountingPrintForm({ sheetId: 'dv-print-{{ $dv->dv_id }}', page: 'portrait' })" class="acc-action-btn" data-tip="Print DV">
                <i data-lucide="printer" class="h-4 w-4"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="acc-review-body">
        <div class="acc-review-grid">
            <div>
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

                @if($reviewable)
                    <div class="print-hidden mt-4 flex flex-wrap gap-3 rounded-xl border border-slate-200 bg-white p-4">
                        <form method="POST" action="{{ route('school-admin.dv.revise', $dv->dv_id) }}" class="flex flex-1 flex-wrap items-end gap-2">
                            @csrf
                            <div class="min-w-[200px] flex-1">
                                <label class="block text-xs font-medium text-slate-600">Return reason</label>
                                <input type="text" name="remarks" required maxlength="2000" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="What should Accounting fix?">
                            </div>
                            <button type="submit" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100">Send back</button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="acc-side-stack print-hidden">
                @include('accounting.partials.related-docs', [
                    'chain' => $chain ?? [],
                    'current' => 'dv',
                ])
            </div>
        </div>
    </div>
</div>
@endsection
