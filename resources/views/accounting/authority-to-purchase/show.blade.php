@extends('layouts.accounting-layout')

@section('title', 'Review ATP')

@section('content')
@php
    $supplier = ($atp->supplier_store_type ?? '') === 'Physical Store' ? ($atp->company_name ?? '—') : ($atp->shop_name ?? '—');
    $total = $items->sum(fn ($i) => (float) ($i->atp_amount ?? 0));
@endphp
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-review-head">
        <div>
            <a href="/accounting/authority-to-purchase" class="acc-back">← ATP queue</a>
            <h1 class="acc-page-title mt-1">{{ $atp->authority_purchase_form_number }}</h1>
            <p class="acc-page-subtitle">Purchaser ATP form. Actions stay outside the document.</p>
        </div>
        <div class="acc-actions">
            @if ($reviewable)
                <button type="button" onclick="document.getElementById('approve-box').classList.toggle('hidden'); if (window.initSignaturePad) window.initSignaturePad('atpSignatureCanvas');" class="acc-btn acc-btn-approve">Approve</button>
                <button type="button" onclick="document.getElementById('revise-box').classList.toggle('hidden')" class="acc-btn acc-btn-revise">Request revision</button>
            @endif
            @include('accounting.partials.status-badge', ['status' => \App\Support\RisWorkflow::atpStatusLabel($atp), 'submitted' => $atp->authority_purchase_submitted_at, 'revision' => $atp->authority_purchase_rejection_reason])
            <button type="button" class="acc-btn acc-btn-ghost" onclick="window.print()">Print</button>
        </div>
    </div>

    <form id="approve-box" method="POST" action="/accounting/authority-to-purchase/{{ $atp->authority_purchase_id }}/approve" class="acc-modal hidden" onsubmit="return window.requireSignaturePad('atpSignatureCanvas', 'atpSignatureCanvasData', 'Please sign this ATP before approving.')">
        @csrf
        <h3>Sign to approve</h3>
        @include('partials.signature-pad', [
            'canvasId' => 'atpSignatureCanvas',
            'label' => 'Accounting signature',
            'hint' => 'Sign to authorize this Authority to Purchase.',
            'requiredMessage' => 'Please sign this ATP before approving.',
        ])
        <div class="mt-2.5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('approve-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
            <button class="acc-btn acc-btn-approve">Approve</button>
        </div>
    </form>

    <form id="revise-box" method="POST" action="/accounting/authority-to-purchase/{{ $atp->authority_purchase_id }}/revise" class="acc-modal hidden">
        @csrf
        <h3>Request revision</h3>
        <textarea name="remarks" required rows="3" placeholder="Tell Purchaser what to correct"></textarea>
        <div class="mt-2.5 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('revise-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
            <button class="acc-btn acc-btn-revise">Send to Purchaser</button>
        </div>
    </form>

    <div class="acc-review-grid">
        <div class="acc-viewer">
            <div class="acc-viewer-stage">
                <div class="acc-viewer-fit">
                    <div class="acc-paper">
                        <div class="acc-paper-title">
                            <p class="org">STI COLLEGE- ORMOC, INC.</p>
                            <p class="doc">AUTHORITY TO PURCHASE</p>
                        </div>
                        <dl>
                            <div><dt>ATP No.</dt><dd>{{ $atp->authority_purchase_form_number }}</dd></div>
                            <div><dt>Date</dt><dd>{{ $atp->authority_purchase_date ? \Carbon\Carbon::parse($atp->authority_purchase_date)->format('F d, Y') : '—' }}</dd></div>
                            <div><dt>RIS</dt><dd>{{ $atp->ris_form_number ?? '—' }}</dd></div>
                            <div><dt>Supplier</dt><dd>{{ $supplier }}</dd></div>
                            <div class="sm:col-span-2"><dt>Purpose</dt><dd>{{ $atp->ris_purpose_description ?? '—' }}</dd></div>
                        </dl>
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="!text-right">Qty</th>
                                    <th class="!text-right">Unit</th>
                                    <th class="!text-right">Unit price</th>
                                    <th class="!text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $item->atp_description }}</td>
                                        <td class="text-right">{{ $item->atp_quantity }}</td>
                                        <td class="text-right">{{ $item->atp_unit }}</td>
                                        <td class="text-right">{{ $item->atp_unit_price !== null ? number_format($item->atp_unit_price, 2) : '—' }}</td>
                                        <td class="text-right">{{ $item->atp_amount !== null ? number_format($item->atp_amount, 2) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-semibold">
                                    <td colspan="4" class="text-right px-2 py-1.5">Total</td>
                                    <td class="text-right px-2 py-1.5">₱{{ number_format($total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="mt-6 grid grid-cols-2 gap-6 text-center text-xs">
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Received by</p>
                                <p class="mt-6 border-b border-slate-800 pb-1">{{ $atp->authority_purchase_received_by_name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Authorized by Accounting</p>
                                <p class="mt-6 border-b border-slate-800 pb-1 min-h-[3rem]">
                                    @include('partials.drawn-signature', ['value' => $atp->authority_purchase_authorized_by_signature ?? ''])
                                </p>
                            </div>
                        </div>
                        @if ($atp->authority_purchase_rejection_reason)
                            <p class="mt-4 rounded-lg bg-sky-50 p-2.5 text-xs text-sky-900">Revision remarks: {{ $atp->authority_purchase_rejection_reason }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="acc-side-stack">
            @include('accounting.partials.related-docs', ['chain' => $chain])
            @include('accounting.partials.history', ['history' => $history])
        </div>
    </div>
</div>
@endsection
