@extends('layouts.accounting-layout')

@section('title', 'Review ATP')

@section('content')
@php
    $supplier = ($atp->supplier_store_type ?? '') === 'Physical Store' ? ($atp->company_name ?? '—') : ($atp->shop_name ?? '—');
    $total = $items->sum(fn ($i) => (float) ($i->atp_amount ?? 0));
@endphp
@include('accounting.partials.flash')

<div class="flex flex-wrap items-start justify-between gap-4 fade-in">
    <div>
        <a href="/accounting/authority-to-purchase" class="text-xs font-semibold text-gray-500 hover:text-gray-900">← ATP queue</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">{{ $atp->authority_purchase_form_number }}</h1>
        <p class="text-sm text-gray-500">Purchaser ATP form. Actions stay outside the document.</p>
    </div>
    <div class="acc-actions">
        @if ($reviewable)
            <form method="POST" action="/accounting/authority-to-purchase/{{ $atp->authority_purchase_id }}/approve" onsubmit="return confirm('Approve this ATP?');">
                @csrf
                <button class="acc-btn acc-btn-approve">Approve</button>
            </form>
            <button type="button" onclick="document.getElementById('revise-box').classList.toggle('hidden')" class="acc-btn acc-btn-revise">Request revision</button>
        @endif
        @include('accounting.partials.status-badge', ['status' => $atp->authority_purchase_status, 'submitted' => $atp->authority_purchase_submitted_at])
    </div>
</div>

<form id="revise-box" method="POST" action="/accounting/authority-to-purchase/{{ $atp->authority_purchase_id }}/revise" class="acc-modal mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-4">
    @csrf
    <h3 class="text-sm font-bold text-amber-900">Request revision</h3>
    <textarea name="remarks" required rows="3" placeholder="Tell Purchaser what to correct" class="mt-2 w-full rounded-xl border border-amber-200 bg-white p-3 text-sm"></textarea>
    <div class="mt-3 flex justify-end gap-2">
        <button type="button" onclick="document.getElementById('revise-box').classList.add('hidden')" class="acc-btn acc-btn-ghost">Cancel</button>
        <button class="acc-btn acc-btn-revise">Send to Purchaser</button>
    </div>
</form>

<div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1fr)_300px]">
    <div class="acc-viewer rounded-xl border border-gray-200 p-4">
        <div class="mx-auto max-w-3xl rounded-xl bg-white p-8 shadow-sm">
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900">STI COLLEGE- ORMOC, INC.</p>
                <p class="text-sm font-semibold tracking-wide text-gray-700">AUTHORITY TO PURCHASE</p>
            </div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">ATP No.</dt><dd class="font-medium">{{ $atp->authority_purchase_form_number }}</dd></div>
                <div><dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Date</dt><dd>{{ $atp->authority_purchase_date ? \Carbon\Carbon::parse($atp->authority_purchase_date)->format('F d, Y') : '—' }}</dd></div>
                <div><dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">RIS</dt><dd>{{ $atp->ris_form_number ?? '—' }}</dd></div>
                <div><dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Supplier</dt><dd>{{ $supplier }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Purpose</dt><dd>{{ $atp->ris_purpose_description ?? '—' }}</dd></div>
            </dl>
            <table class="mt-6 w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Unit</th>
                        <th class="px-3 py-2 text-right">Unit price</th>
                        <th class="px-3 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($items as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->atp_description }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_quantity }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_unit }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_unit_price !== null ? number_format($item->atp_unit_price, 2) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_amount !== null ? number_format($item->atp_amount, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold">
                        <td colspan="4" class="px-3 py-2 text-right">Total</td>
                        <td class="px-3 py-2 text-right">₱{{ number_format($total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="mt-8 grid grid-cols-2 gap-8 text-center text-sm">
                <div>
                    <p class="text-[11px] uppercase text-gray-400">Received by</p>
                    <p class="mt-8 border-b border-gray-800 pb-1">{{ $atp->authority_purchase_received_by_name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase text-gray-400">Authorized by Accounting</p>
                    <p class="mt-8 border-b border-gray-800 pb-1">{{ $atp->authority_purchase_authorized_by_signature ?? '' }}</p>
                </div>
            </div>
            @if ($atp->authority_purchase_rejection_reason)
                <p class="mt-6 rounded-lg bg-sky-50 p-3 text-sm text-sky-900">Revision remarks: {{ $atp->authority_purchase_rejection_reason }}</p>
            @endif
        </div>
    </div>
    <div class="space-y-4">
        @include('accounting.partials.related-docs', ['chain' => $chain])
        @include('accounting.partials.history', ['history' => $history])
    </div>
</div>
@endsection
