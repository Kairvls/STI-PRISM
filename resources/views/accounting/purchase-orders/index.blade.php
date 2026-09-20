@extends('layouts.accounting-layout')

@section('title', 'Purchase Orders')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
<style>
    .acc-po-page,
    .acc-po-page * {
        font-family: "Inter", sans-serif;
    }
    .acc-po-page .pur-card .acc-pagination {
        padding: 0.75rem 1.25rem 1rem;
        border-top: 1px solid #f1f5f9;
    }
</style>
@endpush

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
    $searchQuery = request('search') ? '&search='.urlencode(request('search')) : '';
    $statCards = [
        [
            'label' => 'Needs review',
            'hint' => 'Awaiting Accounting review',
            'value' => number_format($counts['incoming'] ?? 0),
            'href' => '/accounting/purchase-orders?status=incoming'.$searchQuery,
            'active' => $filter === 'incoming',
        ],
        [
            'label' => 'Revision',
            'hint' => 'Sent back to Purchaser',
            'value' => number_format($counts['revision'] ?? 0),
            'href' => '/accounting/purchase-orders?status=revision'.$searchQuery,
            'active' => $filter === 'revision',
        ],
        [
            'label' => 'Approved',
            'hint' => 'Cleared by Accounting',
            'value' => number_format($counts['approved'] ?? 0),
            'href' => '/accounting/purchase-orders?status=approved'.$searchQuery,
            'active' => $filter === 'approved',
        ],
    ];
    $filterLabels = [
        'all' => 'All Purchase Order records',
        'incoming' => 'POs awaiting your review',
        'revision' => 'POs sent back for revision',
        'approved' => 'POs cleared by Accounting',
        'cancelled' => 'Cancelled Purchase Orders',
    ];
@endphp

<div class="acc-page acc-po-page acc-content-fill space-y-6 fade-in">
    @include('layouts.partials.maintenance-stat-cards', ['cards' => $statCards])

    <div class="pur-card">
        @include('accounting.partials.status-filter-bar', [
            'filters' => $filters,
            'activeFilter' => $filter,
            'baseUrl' => '/accounting/purchase-orders',
            'searchPlaceholder' => 'Search PO...',
        ])

        <div class="overflow-x-auto overflow-y-visible">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">PO</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATPs</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($records as $order)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            {{ \App\Support\PurchaseOrderBasket::displayNumber($order) }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $order->purchase_order_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php $atpNumbers = collect($order->atp_numbers ?? [])->filter()->values(); @endphp
                                <div class="group relative z-0 inline-block max-w-full hover:z-50">
                                    <span class="cursor-default text-sm text-gray-700 {{ $atpNumbers->isNotEmpty() ? 'underline decoration-gray-300 decoration-dotted underline-offset-2' : '' }}">
                                        {{ $order->atp_display ?? '—' }}
                                    </span>
                                    @if($atpNumbers->isNotEmpty())
                                        <div
                                            class="pointer-events-none absolute left-0 bottom-full z-50 mb-2 hidden min-w-[11rem] max-w-xs rounded-xl border border-gray-200/80 bg-white px-3 py-2.5 shadow-[0_8px_24px_rgba(15,23,42,0.12)] group-hover:block"
                                            role="tooltip"
                                        >
                                            <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400">ATPs on this PO</p>
                                            <ul class="space-y-1">
                                                @foreach($atpNumbers as $atpNo)
                                                    <li class="font-mono text-[12px] leading-5 text-gray-700">{{ $atpNo }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-gray-400">{{ (int) ($order->atp_count ?? 0) }} ATP(s)</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
                                ₱{{ number_format((float) ($order->po_total_amount ?? 0), 2) }}
                            </td>
                            <td class="px-5 py-4">
                                @include('accounting.partials.status-badge', [
                                    'status' => $order->purchase_order_status,
                                    'submitted' => $order->purchase_order_submitted_at ?? null,
                                    'revision' => $order->purchase_order_revision_reason ?? null,
                                ])
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                {{ !empty($order->purchase_order_submitted_at) ? \Carbon\Carbon::parse($order->purchase_order_submitted_at)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <x-view-action-button
                                    :href="'/accounting/purchase-orders/'.$order->purchase_order_id"
                                    label="View"
                                    title="Open PO"
                                    aria-label="Open PO"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="pur-empty my-2">No Purchase Orders in this filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="acc-pagination">{{ $records->links('pagination.president') }}</div>
        @endif
    </div>
</div>
@endsection
