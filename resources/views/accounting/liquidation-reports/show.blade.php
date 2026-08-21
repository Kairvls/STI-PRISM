@extends('layouts.accounting-layout')

@section('title', 'Review Liquidation')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/accounting/liquidation-reports" class="acc-back" data-tip="Back to liquidation queue" aria-label="Back to liquidation queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $liq->liquidation_report_form_number ?? ('LIQ-'.$liq->liquidation_report_id) }}</h1>
                @include('accounting.partials.status-badge', ['status' => $liq->liquidation_report_status])
            </div>
            <p class="acc-page-subtitle">Official liquidation form. Approval completes the transaction.</p>
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="document.getElementById('approve-box').classList.toggle('hidden'); if (window.initSignaturePad) window.initSignaturePad('liqSignatureCanvas');" class="icon-btn" data-tip="Approve liquidation" aria-label="Approve liquidation">
                    <i data-lucide="check" class="h-4 w-4"></i>
                </button>
                <button type="button" onclick="document.getElementById('revise-box').classList.toggle('hidden')" class="icon-btn" data-tip="Request revision" aria-label="Request revision">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                </button>
            @endif
            <button type="button" class="icon-btn" onclick="window.print()" data-tip="Print liquidation" aria-label="Print liquidation">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <form id="approve-box" method="POST" action="/accounting/liquidation-reports/{{ $liq->liquidation_report_id }}/approve" class="acc-modal hidden" onsubmit="return window.requireSignaturePad('liqSignatureCanvas', 'liqSignatureCanvasData', 'Please sign this liquidation before approving.')">
        @csrf
        <h3>Sign to approve</h3>
        @include('partials.signature-pad', [
            'canvasId' => 'liqSignatureCanvas',
            'label' => 'Accounting signature',
            'hint' => 'Sign to check and complete this liquidation.',
            'requiredMessage' => 'Please sign this liquidation before approving.',
        ])
        <div class="mt-2.5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('approve-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
            <button class="acc-btn acc-btn-approve">Approve</button>
        </div>
    </form>

    <form id="revise-box" method="POST" action="/accounting/liquidation-reports/{{ $liq->liquidation_report_id }}/revise" class="acc-modal hidden">
        @csrf
        <h3>Request revision</h3>
        <textarea name="remarks" required rows="3"></textarea>
        <div class="mt-2.5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('revise-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
            <button class="acc-btn acc-btn-revise">Send to Purchaser</button>
        </div>
    </form>

    <div class="acc-review-grid">
        <div>
            <div class="acc-viewer">
                <div class="acc-viewer-stage">
                    <div class="acc-viewer-fit">
                        @include('partials.liquidation-report-paper', ['editable' => false, 'liq' => $liq, 'rows' => $rows])
                    </div>
                </div>
            </div>
            @if ($attachments->isNotEmpty())
                <div class="acc-attachments">
                    <h3>Receipts and supporting documents</h3>
                    <ul>
                        @foreach ($attachments as $file)
                            <li>
                                <a href="/accounting/liquidation-reports/{{ $liq->liquidation_report_id }}/attachments/{{ $file->liquidation_attachment_id }}">
                                    {{ $file->liquidation_attachment_original_name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="acc-side-stack">
            @include('accounting.partials.related-docs', ['chain' => $chain])
            @include('accounting.partials.history', ['history' => $history])
            @if (!empty($liq->liquidation_report_revision_notes))
                <div class="acc-note acc-note-info">Last revision note: {{ $liq->liquidation_report_revision_notes }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
