@extends('layouts.accounting-layout')

@section('title', 'Review Request Check')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/accounting/request-check" class="acc-back" data-tip="Back to Request Check queue" aria-label="Back to Request Check queue">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
            </a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="acc-page-title">{{ $rfc->request_check_form_number ?? ('RFC-'.$rfc->request_check_id) }}</h1>
                @include('accounting.partials.status-badge', ['status' => !empty($rfc->request_check_funds_released_at) ? 'Released' : $rfc->request_check_status])
            </div>
            <p class="acc-page-subtitle">Official Request for Check. Funds are collected in person.</p>
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="document.getElementById('approve-box').classList.toggle('hidden'); if (window.initSignaturePad) window.initSignaturePad('rfcSignatureCanvas');" class="icon-btn" data-tip="Approve request check" aria-label="Approve request check">
                    <i data-lucide="check" class="h-4 w-4"></i>
                </button>
                <button type="button" onclick="document.getElementById('revise-box').classList.toggle('hidden')" class="icon-btn" data-tip="Request revision" aria-label="Request revision">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                </button>
            @endif
            @if ($releasable)
                <form method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/release-funds" onsubmit="return confirm('Mark funds as ready for personal collection?');">
                    @csrf
                    <button type="submit" class="icon-btn" data-tip="Release funds" aria-label="Release funds">
                        <i data-lucide="banknote" class="h-4 w-4"></i>
                    </button>
                </form>
            @endif
            <button type="button" class="icon-btn" onclick="window.print()" data-tip="Print request check" aria-label="Print request check">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <form id="approve-box" method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/approve" class="acc-modal hidden" onsubmit="return window.requireSignaturePad('rfcSignatureCanvas', 'rfcSignatureCanvasData', 'Please sign this Request Check before approving.')">
        @csrf
        <h3>Sign to approve</h3>
        @include('partials.signature-pad', [
            'canvasId' => 'rfcSignatureCanvas',
            'label' => 'Accounting signature',
            'hint' => 'Sign to approve this Request for Check.',
            'requiredMessage' => 'Please sign this Request Check before approving.',
        ])
        <div class="mt-2.5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('approve-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
            <button class="acc-btn acc-btn-approve">Approve</button>
        </div>
    </form>

    <form id="revise-box" method="POST" action="/accounting/request-check/{{ $rfc->request_check_id }}/revise" class="acc-modal hidden">
        @csrf
        <h3>Request revision</h3>
        <textarea name="remarks" required rows="3"></textarea>
        <div class="mt-2.5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('revise-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
            <button class="acc-btn acc-btn-revise">Send to Purchaser</button>
        </div>
    </form>

    @if (!empty($rfc->request_check_funds_released_at))
        <div class="acc-note acc-note-ok mb-3">
            Funds ready for personal collection since {{ \Carbon\Carbon::parse($rfc->request_check_funds_released_at)->format('M d, Y g:i A') }}.
        </div>
    @endif

    <div class="acc-review-grid">
        <div>
            <div class="acc-viewer">
                <div class="acc-viewer-stage">
                    <div class="acc-viewer-fit">
                        @include('partials.request-check-paper', ['editable' => false, 'rfc' => $rfc])
                    </div>
                </div>
            </div>
            @if ($attachments->isNotEmpty())
                <div class="acc-attachments">
                    <h3>Supporting documents</h3>
                    <ul>
                        @foreach ($attachments as $file)
                            <li>
                                <a href="/accounting/request-check/{{ $rfc->request_check_id }}/attachments/{{ $file->request_check_attachment_id }}">
                                    {{ $file->request_check_attachment_original_name }}
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
            @if (!empty($rfc->request_check_revision_notes))
                <div class="acc-note acc-note-info">Last revision note: {{ $rfc->request_check_revision_notes }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
