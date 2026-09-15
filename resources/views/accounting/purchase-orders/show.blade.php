@extends('layouts.accounting-layout')

@section('title', 'Purchase Order')

@section('content')
@include('accounting.partials.flash')

@php
    $label = \App\Support\PurchaseOrderBasket::displayNumber($order);
    $linked = collect($order->linked_atps ?? []);
@endphp

<div class="acc-page acc-content-fill fade-in">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="/accounting/purchase-orders?status=incoming" class="text-sm font-medium text-blue-700 hover:underline">← Back to Purchase Orders</a>
            <h1 class="mt-2 text-xl font-semibold text-slate-900">{{ $label }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $order->purchase_order_status }} · {{ $linked->count() }} ATP(s) · ₱{{ number_format((float) ($order->po_total_amount ?? 0), 2) }}</p>
        </div>
    </div>

    @if(!empty($order->purchase_order_revision_reason) && ($order->purchase_order_status ?? '') === 'Draft')
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Previous revision note: {{ $order->purchase_order_revision_reason }}
        </div>
    @endif

    <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800">Linked ATPs</div>
        <div class="divide-y divide-slate-100">
            @forelse($linked as $atp)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="font-semibold text-slate-900">ATP {{ $atp->authority_purchase_form_number ?: ('#'.$atp->authority_purchase_id) }}</p>
                        <p class="text-xs text-slate-500">{{ $atp->supplier_display ?? 'Supplier' }} · RIS {{ $atp->ris_form_number ?: '—' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-800">₱{{ number_format((float) ($atp->po_total_amount ?? 0), 2) }}</span>
                        <a href="/accounting/authority-to-purchase/{{ $atp->authority_purchase_id }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">View ATP</a>
                    </div>
                </div>
            @empty
                <p class="px-4 py-6 text-sm text-slate-500">No ATPs linked.</p>
            @endforelse
        </div>
    </div>

    @if($reviewable)
        <div class="grid gap-4 lg:grid-cols-2">
            <form method="POST" action="/accounting/purchase-orders/{{ $order->purchase_order_id }}/approve" class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4">
                @csrf
                <h3 class="text-sm font-semibold text-emerald-900">Approve Purchase Order</h3>
                <p class="mt-1 text-xs text-emerald-800">Approves this PO and all linked ATPs.</p>
                <button type="submit" class="mt-3 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve PO</button>
            </form>

            <form method="POST" action="/accounting/purchase-orders/{{ $order->purchase_order_id }}/revise" class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4">
                @csrf
                <h3 class="text-sm font-semibold text-amber-900">Request revision</h3>
                <p class="mt-1 text-xs text-amber-800">Returns the PO and linked ATPs to the purchaser.</p>
                <textarea name="remarks" rows="3" required class="mt-3 w-full rounded-lg border border-amber-200 px-3 py-2 text-sm" placeholder="Describe what needs to be corrected..."></textarea>
                <button type="submit" class="mt-3 rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">Send back</button>
            </form>
        </div>
    @endif
</div>
@endsection
