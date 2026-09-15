@extends('layouts.accounting-layout')

@section('title', 'Purchase Orders')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'all' => 'All',
        'incoming' => 'Needs review',
        'revision' => 'Revision',
        'approved' => 'Approved',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="acc-page acc-content-fill fade-in">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Purchase Orders</h1>
            <p class="mt-1 text-sm text-slate-500">Review bundled ATPs submitted by Purchaser.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search PO..." class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
            <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Search</button>
        </form>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($filters as $key => $label)
            <a
                href="/accounting/purchase-orders?status={{ $key }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === $key ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700' }}"
            >
                {{ $label }}
                <span class="ml-1 opacity-80">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">PO</th>
                    <th class="px-4 py-3">ATPs</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($records as $order)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ \App\Support\PurchaseOrderBasket::displayNumber($order) }}</td>
                        <td class="px-4 py-3">
                            @php $atpNumbers = collect($order->atp_numbers ?? [])->filter()->values(); @endphp
                            <div class="group relative z-0 inline-block max-w-full hover:z-50">
                                <span class="cursor-default {{ $atpNumbers->isNotEmpty() ? 'underline decoration-slate-300 decoration-dotted underline-offset-2' : '' }}">
                                    {{ $order->atp_display ?? '—' }}
                                </span>
                                @if($atpNumbers->isNotEmpty())
                                    <div
                                        class="pointer-events-none absolute left-0 bottom-full z-50 mb-2 hidden min-w-[11rem] max-w-xs rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.12)] group-hover:block"
                                        role="tooltip"
                                    >
                                        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">ATPs on this PO</p>
                                        <ul class="space-y-1">
                                            @foreach($atpNumbers as $atpNo)
                                                <li class="font-mono text-[12px] leading-5 text-slate-700">{{ $atpNo }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400">{{ (int) ($order->atp_count ?? 0) }} ATP(s)</p>
                        </td>
                        <td class="px-4 py-3">₱{{ number_format((float) ($order->po_total_amount ?? 0), 2) }}</td>
                        <td class="px-4 py-3">{{ $order->purchase_order_status }}</td>
                        <td class="px-4 py-3 text-slate-500">
                            {{ !empty($order->purchase_order_submitted_at) ? \Carbon\Carbon::parse($order->purchase_order_submitted_at)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="/accounting/purchase-orders/{{ $order->purchase_order_id }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No Purchase Orders in this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($records->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </div>
</div>
@endsection
