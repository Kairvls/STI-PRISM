@extends($layout)

@section('title', 'Back Orders')
@section('page-title', 'Back Orders')
@section('page-subtitle', $canManage
    ? 'Missing and damaged items from your Receiving Reports. Record the reason, a refund (Cash Advance), or the replacement delivery to complete the RR.'
    : 'Monitor missing and damaged items and how the Purchaser is completing each Receiving Report.')

@section('content')
@use('App\Support\BackOrders')
@php
    $statusStyles = [
        BackOrders::STATUS_OPEN => 'border-amber-200 bg-amber-50 text-amber-800',
        BackOrders::STATUS_WAITING_RESTOCK => 'border-orange-200 bg-orange-50 text-orange-800',
        BackOrders::STATUS_REFUNDED => 'border-rose-200 bg-rose-50 text-rose-800',
        BackOrders::STATUS_RECEIVING => 'border-sky-200 bg-sky-50 text-sky-800',
        BackOrders::STATUS_FULFILLED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        BackOrders::STATUS_FULFILLED_REPLACEMENT => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    ];
    $fileAccept = '.'.str_replace(',', ',.', BackOrders::FILE_MIMES);

    $boData = [];
    foreach ($backOrders as $bo) {
        $files = fn (array $list, string $kind) => collect($list)->map(fn ($file, $i) => [
            'url' => route($routePrefix.'.file', ['id' => $bo->back_order_id, 'kind' => $kind, 'index' => $i]),
            'name' => $file['name'] ?? basename($file['path']),
            'image' => BackOrders::isImage($file),
        ])->values();
        $boData[$bo->back_order_id] = [
            'id' => (int) $bo->back_order_id,
            'number' => \App\Support\BackOrderNumber::label($bo),
            'article' => $bo->back_order_article,
            'quantity' => (int) $bo->back_order_quantity,
            'unit' => $bo->back_order_unit,
            'unit_price' => (float) $bo->back_order_unit_price,
            'type' => $bo->back_order_type,
            'type_label' => BackOrders::TYPES[$bo->back_order_type] ?? $bo->back_order_type,
            'supplier' => $bo->back_order_supplier_name,
            'supplier_id' => $bo->back_order_supplier_id ? (int) $bo->back_order_supplier_id : null,
            'reason' => $bo->back_order_reason,
            'reason_label' => BackOrders::reasonLabel($bo->back_order_reason),
            'remarks' => $bo->back_order_remarks,
            'status' => $bo->back_order_status,
            'status_label' => BackOrders::statusLabel($bo->back_order_status),
            'is_cash_advance' => $bo->is_cash_advance,
            'rr' => $bo->root_rr_number ?: ('RR #'.$bo->back_order_root_receiving_report_id),
            'rr_status' => $bo->root_rr_status,
            'refund_amount' => $bo->back_order_refund_amount !== null ? (float) $bo->back_order_refund_amount : null,
            'refund_reference' => $bo->back_order_refund_reference,
            'max_refund' => round((float) $bo->back_order_unit_price * (int) $bo->back_order_quantity, 2),
            'replacement' => $bo->back_order_replacement_item_id ? [
                'quantity' => (int) $bo->back_order_replacement_quantity,
                'unit_price' => (float) $bo->back_order_replacement_unit_price,
                'supplier' => $bo->is_new_supplier ? $bo->back_order_replacement_supplier_name : $bo->back_order_supplier_name,
                'is_new' => $bo->is_new_supplier,
                'reference' => $bo->back_order_replacement_reference,
            ] : null,
            'cash_note' => $bo->back_order_cash_note,
            'files' => $files($bo->files, 'proof'),
            'refund_files' => $files($bo->refund_files, 'refund'),
            'replacement_files' => $files($bo->replacement_files, 'replacement'),
            'update_url' => $canManage ? route($routePrefix.'.update', $bo->back_order_id) : null,
            'refund_url' => $canManage ? route($routePrefix.'.refund', $bo->back_order_id) : null,
            'replace_url' => $canManage ? route($routePrefix.'.replace', $bo->back_order_id) : null,
        ];
    }

    $query = array_filter([
        'status' => $filter,
        'type' => $typeFilter,
        'payment' => $paymentFilter,
        'supplier' => $supplierFilter,
        'rr' => $rootRrId,
        'search' => request('search'),
    ]);
    $filterUrl = fn (array $changes) => route($routePrefix.'.index', array_filter(array_merge($query, $changes)));
    $pill = fn (bool $active) => $active
        ? 'border-[#0025cc] bg-[#0025cc] text-white'
        : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50';
@endphp

<script type="application/json" id="bo-data">{!! json_encode($boData) !!}</script>

<div
    x-data="{
        items: JSON.parse(document.getElementById('bo-data').textContent || '{}'),
        selected: null,
        detailOpen: false,
        editOpen: false,
        refundOpen: false,
        replaceOpen: false,
        rep: { quantity: 0, damaged: 0, price: 0, supplierId: '', supplierName: '' },
        get current() { return this.selected ? this.items[this.selected] : null; },
        money(v) { return '₱' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        openDetail(id) { this.selected = id; this.detailOpen = true; this.refreshIcons(); },
        openEdit(id) { this.selected = id; this.editOpen = true; this.refreshIcons(); },
        openRefund(id) { this.selected = id; this.refundOpen = true; this.refreshIcons(); },
        openReplace(id) {
            const bo = this.items[id];
            this.selected = id;
            this.rep = {
                quantity: bo.quantity,
                damaged: 0,
                price: bo.unit_price,
                supplierId: bo.supplier_id ? String(bo.supplier_id) : '',
                supplierName: bo.supplier_id ? '' : (bo.supplier || ''),
            };
            this.replaceOpen = true;
            this.refreshIcons();
        },
        cashPreview() {
            const bo = this.current;
            if (!bo || !bo.is_cash_advance) return '';
            const cost = (Number(this.rep.quantity) || 0) * (Number(this.rep.price) || 0);
            let available = 0, source = 'no refund recorded';
            if (bo.refund_amount !== null) { available = bo.refund_amount; source = 'refund of ' + this.money(available); }
            else if (bo.type === 'short') { available = bo.quantity * bo.unit_price; source = 'unspent ' + this.money(available) + ' for the missing items'; }
            const diff = Math.round((available - cost) * 100) / 100;
            if (diff > 0) return 'Cost ' + this.money(cost) + ' vs ' + source + ': ' + this.money(diff) + ' remaining – return to cashier.';
            if (diff < 0) return 'Cost ' + this.money(cost) + ' vs ' + source + ': additional ' + this.money(-diff) + ' needed from cashier.';
            return 'Cost ' + this.money(cost) + ' matches the ' + source + '.';
        },
        closeAll() { this.detailOpen = false; this.editOpen = false; this.refundOpen = false; this.replaceOpen = false; },
        refreshIcons() { this.$nextTick(() => { if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); }); }
    }"
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>
    @unless($supported)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Back orders are not set up yet. Run the database migrations to enable this page.
        </div>
    @endunless

    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            [
                'label' => 'Needs action',
                'hint' => 'Missing or damaged items not yet delivered',
                'value' => number_format($summary['unresolved'] ?? 0),
                'href' => $filterUrl(['status' => 'unresolved']),
                'active' => $filter === 'unresolved',
            ],
            [
                'label' => 'For second count',
                'hint' => 'Replacement delivered, waiting for Receiving',
                'value' => number_format($summary['receiving'] ?? 0),
                'href' => $filterUrl(['status' => 'receiving']),
                'active' => $filter === 'receiving',
            ],
            [
                'label' => 'Delivered',
                'hint' => 'Completed through replacement or delivery',
                'value' => number_format($summary['resolved'] ?? 0),
                'href' => $filterUrl(['status' => 'resolved']),
                'active' => $filter === 'resolved',
            ],
        ],
    ])

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="space-y-3 border-b border-gray-100 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav class="flex flex-wrap gap-1.5" aria-label="Back order status">
                    @foreach($filters as $key => $label)
                        <a href="{{ $filterUrl(['status' => $key]) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $filter === $key ? 'bg-[#0025cc] text-white' : 'text-gray-600 hover:bg-gray-100' }}">{{ $label }}</a>
                    @endforeach
                </nav>
                <form method="GET" action="{{ route($routePrefix.'.index') }}" class="flex items-center gap-2">
                    @foreach(array_diff_key($query, ['search' => true]) as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="BO no., article, supplier, RR no." class="h-9 w-64 rounded-lg border border-gray-300 pl-9 pr-3 text-sm">
                    </div>
                </form>
            </div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="font-medium text-gray-500">Type</span>
                    <a href="{{ $filterUrl(['type' => null]) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill(!$typeFilter) }}">All</a>
                    @foreach(BackOrders::TYPES as $value => $label)
                        <a href="{{ $filterUrl(['type' => $value]) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill($typeFilter === $value) }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-medium text-gray-500">Supplier</span>
                    <a href="{{ $filterUrl(['supplier' => null]) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill(!$supplierFilter) }}">All</a>
                    <a href="{{ $filterUrl(['supplier' => 'original']) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill($supplierFilter === 'original') }}">Original supplier</a>
                    <a href="{{ $filterUrl(['supplier' => 'new']) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill($supplierFilter === 'new') }}">New supplier</a>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-medium text-gray-500">Payment</span>
                    <a href="{{ $filterUrl(['payment' => null]) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill(!$paymentFilter) }}">All</a>
                    <a href="{{ $filterUrl(['payment' => 'rfc']) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill($paymentFilter === 'rfc') }}">Request for Check</a>
                    <a href="{{ $filterUrl(['payment' => 'ca']) }}" class="rounded-full border px-2.5 py-1 font-medium {{ $pill($paymentFilter === 'ca') }}">Cash Advance</a>
                </div>
            </div>
        </div>

        @if($rootRrId)
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-sky-50/60 px-5 py-2 text-sm text-sky-800">
                <span>Showing back orders from one Receiving Report only.</span>
                <a href="{{ $filterUrl(['rr' => null]) }}" class="font-medium underline">Show all</a>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3">Receiving Report</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Reason</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($backOrders as $bo)
                        <tr class="align-top transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $bo->back_order_article }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    <span class="font-semibold {{ $bo->back_order_type === 'damaged' ? 'text-red-700' : 'text-amber-700' }}">{{ (int) $bo->back_order_quantity }} {{ $bo->back_order_unit ?: 'pcs' }} {{ strtolower(BackOrders::TYPES[$bo->back_order_type] ?? $bo->back_order_type) }}</span>
                                    · ₱{{ number_format((float) $bo->back_order_unit_price, 2) }} each
                                </p>
                                <p class="mt-0.5 text-[11px] text-gray-400">
                                    <span class="font-mono font-medium text-gray-500">{{ \App\Support\BackOrderNumber::label($bo) }}</span>
                                    @if($bo->is_replacement_line)
                                        · from a replacement row
                                    @endif
                                </p>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                @if($canManage)
                                    <a href="{{ \App\Support\ProcurementPortal::route('rr.index', ['view_rr' => $bo->back_order_root_receiving_report_id]) }}" class="font-medium text-[#0025cc] hover:underline">{{ $bo->root_rr_number ?: 'RR #'.$bo->back_order_root_receiving_report_id }}</a>
                                @else
                                    <p class="font-medium text-gray-800">{{ $bo->root_rr_number ?: 'RR #'.$bo->back_order_root_receiving_report_id }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $bo->is_cash_advance ? 'Cash Advance' : 'Request for Check' }}
                                    @if($bo->authority_purchase_form_number)
                                        · {{ $bo->authority_purchase_form_number }}
                                    @endif
                                </p>
                                @if($bo->root_rr_status)
                                    <p class="mt-1">@include('accounting.partials.status-badge', ['status' => $bo->root_rr_status])</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                <p>{{ $bo->back_order_supplier_name ?: '—' }}</p>
                                @if($bo->back_order_replacement_item_id)
                                    <p class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $bo->is_new_supplier ? 'bg-violet-50 text-violet-700' : 'bg-sky-50 text-sky-700' }}">
                                        {{ $bo->is_new_supplier ? 'New supplier: '.$bo->back_order_replacement_supplier_name : 'Same supplier' }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500">Replacement {{ (int) $bo->back_order_replacement_quantity }} × ₱{{ number_format((float) $bo->back_order_replacement_unit_price, 2) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($bo->back_order_reason)
                                    <p class="text-gray-800">{{ BackOrders::reasonLabel($bo->back_order_reason) }}</p>
                                @else
                                    <p class="text-xs font-medium text-amber-700">Not recorded yet</p>
                                @endif
                                @if($bo->back_order_remarks)
                                    <p class="mt-0.5 max-w-[220px] truncate text-xs text-gray-500" title="{{ $bo->back_order_remarks }}">{{ $bo->back_order_remarks }}</p>
                                @endif
                                @if(count($bo->files))
                                    <p class="mt-0.5 inline-flex items-center gap-1 text-xs text-gray-500"><i data-lucide="paperclip" class="h-3 w-3"></i>{{ count($bo->files) }} attachment{{ count($bo->files) === 1 ? '' : 's' }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusStyles[$bo->back_order_status] ?? 'border-gray-200 bg-gray-50 text-gray-700' }}">
                                    {{ BackOrders::statusLabel($bo->back_order_status) }}
                                </span>
                                @if($bo->back_order_refund_amount !== null)
                                    <p class="mt-1 text-xs text-gray-500">Refunded ₱{{ number_format((float) $bo->back_order_refund_amount, 2) }}</p>
                                @endif
                                @if($bo->back_order_cash_note)
                                    <p class="mt-1 max-w-[240px] text-[11px] leading-snug {{ (float) $bo->back_order_cash_difference < 0 ? 'text-rose-700' : 'text-gray-500' }}">{{ $bo->back_order_cash_note }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" @click="openDetail({{ $bo->back_order_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900" title="View details" aria-label="View details"><i data-lucide="eye" class="h-4 w-4"></i></button>
                                    @if($canManage && $bo->is_unresolved)
                                        <button type="button" @click="openEdit({{ $bo->back_order_id }})" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition hover:bg-gray-50" title="Reason, remarks and attachments">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Reason
                                        </button>
                                    @endif
                                    @if($canManage && $bo->can_refund)
                                        <button type="button" @click="openRefund({{ $bo->back_order_id }})" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition hover:bg-gray-50" title="Supplier returned the money">
                                            <i data-lucide="banknote" class="h-3.5 w-3.5"></i> Refund
                                        </button>
                                    @endif
                                    @if($canManage && in_array($bo->back_order_status, BackOrders::REPLACEABLE, true))
                                        <button
                                            type="button"
                                            @click="openReplace({{ $bo->back_order_id }})"
                                            @disabled(!$bo->can_replace)
                                            title="{{ $bo->can_replace ? 'Record the replacement delivery as a new row on the RR' : $bo->replace_blocked_reason }}"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001db3] disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            <i data-lucide="package-plus" class="h-3.5 w-3.5"></i> Replacement
                                        </button>
                                    @endif
                                    @if($canManage && $bo->can_undo)
                                        <form method="POST" action="{{ route($routePrefix.'.undo-replacement', $bo->back_order_id) }}" data-pur-confirm="Remove the replacement row from the RR? The back order will be open again." data-pur-confirm-title="Undo replacement" data-pur-confirm-ok="Undo">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition hover:bg-gray-50" title="Take back the replacement row before Receiving counts it">
                                                <i data-lucide="undo-2" class="h-3.5 w-3.5"></i> Undo
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @if($canManage && !$bo->can_replace && $bo->replace_blocked_reason)
                                    <p class="mt-1 text-right text-[11px] text-gray-400">{{ $bo->replace_blocked_reason }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <i data-lucide="package-check" class="h-6 w-6"></i>
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-700">No back orders here</p>
                                <p class="mt-1 text-xs text-gray-500">Back orders appear when an RR is submitted with missing or damaged items, or when Receiving finds them at second count.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($backOrders, 'links') && $backOrders->hasPages())
            <div class="border-t border-gray-100 px-5 py-3">{{ $backOrders->links() }}</div>
        @endif
    </div>

    {{-- Details --}}
    <template x-teleport="body">
        <div x-show="detailOpen && current" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="detailOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-xl" @click.stop>
                    <template x-if="current">
                        <div>
                            <div class="flex items-start justify-between gap-3 border-b px-6 py-5">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900" x-text="current.article"></h3>
                                    <p class="text-sm text-gray-500" x-text="current.number + ' · ' + current.type_label + ' · ' + current.rr"></p>
                                </div>
                                <button type="button" @click="detailOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
                            </div>
                            <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-6 py-5 text-sm">
                                <div><p class="text-xs text-gray-500">Quantity</p><p class="font-medium text-gray-900" x-text="current.quantity + ' ' + (current.unit || 'pcs') + ' ' + current.type_label.toLowerCase()"></p></div>
                                <div><p class="text-xs text-gray-500">Unit price</p><p class="font-medium text-gray-900" x-text="money(current.unit_price)"></p></div>
                                <div><p class="text-xs text-gray-500">Original supplier</p><p class="font-medium text-gray-900" x-text="current.supplier || '—'"></p></div>
                                <div><p class="text-xs text-gray-500">Payment</p><p class="font-medium text-gray-900" x-text="current.is_cash_advance ? 'Cash Advance' : 'Request for Check'"></p></div>
                                <div><p class="text-xs text-gray-500">Status</p><p class="font-medium text-gray-900" x-text="current.status_label"></p></div>
                                <div><p class="text-xs text-gray-500">Reason</p><p class="font-medium text-gray-900" x-text="current.reason_label"></p></div>
                                <template x-if="current.replacement">
                                    <div class="col-span-2 rounded-lg border px-4 py-3" :class="current.replacement.is_new ? 'border-violet-100 bg-violet-50/60' : 'border-sky-100 bg-sky-50/60'">
                                        <p class="text-xs font-semibold" :class="current.replacement.is_new ? 'text-violet-700' : 'text-sky-700'" x-text="'Replacement row · ' + (current.replacement.is_new ? 'New supplier' : 'Same supplier')"></p>
                                        <p class="mt-1 text-gray-800" x-text="current.replacement.quantity + ' ' + (current.unit || 'pcs') + ' × ' + money(current.replacement.unit_price) + ' from ' + (current.replacement.supplier || '—') + (current.replacement.reference ? ' · Ref ' + current.replacement.reference : '')"></p>
                                    </div>
                                </template>
                                <div x-show="current.refund_amount !== null"><p class="text-xs text-gray-500">Refund</p><p class="font-medium text-gray-900" x-text="money(current.refund_amount) + (current.refund_reference ? ' · ' + current.refund_reference : '')"></p></div>
                                <div class="col-span-2" x-show="current.cash_note"><p class="text-xs text-gray-500">Cash note</p><p class="text-gray-800" x-text="current.cash_note"></p></div>
                                <div class="col-span-2" x-show="current.remarks"><p class="text-xs text-gray-500">Remarks</p><p class="whitespace-pre-line text-gray-800" x-text="current.remarks"></p></div>
                                @foreach(['files' => 'Attachments', 'refund_files' => 'Refund proof', 'replacement_files' => 'Replacement receipt'] as $key => $label)
                                    <div class="col-span-2" x-show="current.{{ $key }}.length">
                                        <p class="mb-1.5 text-xs text-gray-500">{{ $label }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="file in current.{{ $key }}" :key="file.url">
                                                <a :href="file.url" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-gray-200" :class="file.image ? 'h-20 w-20' : 'flex h-20 w-32 flex-col items-center justify-center gap-1 bg-gray-50 px-2 text-center'">
                                                    <template x-if="file.image"><img :src="file.url" :alt="file.name" class="h-full w-full object-cover"></template>
                                                    <template x-if="!file.image"><span class="line-clamp-2 break-all text-[11px] text-gray-600" x-text="file.name"></span></template>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-end border-t bg-gray-50 px-6 py-4">
                                <button type="button" @click="detailOpen = false" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Close</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    @if($canManage)
        {{-- Reason, remarks, attachments --}}
        <template x-teleport="body">
            <div x-show="editOpen && current" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="editOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <form method="POST" :action="current ? current.update_url : '#'" enctype="multipart/form-data" class="relative w-full max-w-xl rounded-2xl bg-white shadow-xl" @click.stop>
                        @csrf
                        <template x-if="current">
                            <div>
                                <div class="flex items-start justify-between gap-3 border-b px-6 py-5">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Reason &amp; attachments</h3>
                                        <p class="text-sm text-gray-500" x-text="current.quantity + ' ' + (current.unit || 'pcs') + ' ' + current.type_label.toLowerCase() + ' · ' + current.article"></p>
                                    </div>
                                    <button type="button" @click="editOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
                                </div>
                                <div class="space-y-4 px-6 py-5">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Reason</label>
                                        <select name="back_order_reason" required class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                            <option value="">Select a reason</option>
                                            @foreach(BackOrders::REASONS as $value => $label)
                                                <option value="{{ $value }}" :selected="current.reason === '{{ $value }}'">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div x-show="['open', 'waiting_restock'].includes(current.status)">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Supplier status</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-200 p-3 text-sm">
                                                <input type="radio" name="back_order_status" value="open" :checked="current.status !== 'waiting_restock'" class="mt-0.5">
                                                <span><span class="block font-medium text-gray-800">Ready to deliver</span><span class="text-xs text-gray-500">Replacement can be delivered now</span></span>
                                            </label>
                                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-gray-200 p-3 text-sm">
                                                <input type="radio" name="back_order_status" value="waiting_restock" :checked="current.status === 'waiting_restock'" class="mt-0.5">
                                                <span><span class="block font-medium text-gray-800">Waiting for restock</span><span class="text-xs text-gray-500">Supplier is out of stock for now</span></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Remarks</label>
                                        <textarea name="back_order_remarks" rows="3" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="What did the supplier say? Expected restock date, etc." x-text="current.remarks || ''"></textarea>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Attachments <span class="font-normal text-gray-400">(images, PDF, Word, Excel · up to {{ BackOrders::FILE_MAX }})</span></label>
                                        <div class="mb-2 space-y-1" x-show="current.files.length">
                                            <template x-for="(file, index) in current.files" :key="file.url">
                                                <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-1.5 text-xs">
                                                    <a :href="file.url" target="_blank" rel="noopener" class="truncate text-[#0025cc] hover:underline" x-text="file.name"></a>
                                                    <span class="flex shrink-0 items-center gap-1 text-gray-500"><input type="checkbox" name="remove_files[]" :value="index"> Remove</span>
                                                </label>
                                            </template>
                                        </div>
                                        <input type="file" name="attachments[]" accept="{{ $fileAccept }}" multiple class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                                        <p class="mt-1 text-xs text-gray-500">Delivery receipt, supplier message, photos of the damaged items. Max 10 MB each.</p>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 border-t bg-gray-50 px-6 py-4">
                                    <button type="button" @click="editOpen = false" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</button>
                                    <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001db3]">Save</button>
                                </div>
                            </div>
                        </template>
                    </form>
                </div>
            </div>
        </template>

        {{-- Replacement delivery: new row on the same RR --}}
        <template x-teleport="body">
            <div x-show="replaceOpen && current" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="replaceOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <form method="POST" :action="current ? current.replace_url : '#'" enctype="multipart/form-data" class="relative w-full max-w-xl rounded-2xl bg-white shadow-xl" @click.stop>
                        @csrf
                        <template x-if="current">
                            <div>
                                <div class="flex items-start justify-between gap-3 border-b px-6 py-5">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Record replacement delivery</h3>
                                        <p class="text-sm text-gray-500" x-text="current.quantity + ' ' + (current.unit || 'pcs') + ' ' + current.type_label.toLowerCase() + ' · ' + current.article + ' · ' + current.rr"></p>
                                    </div>
                                    <button type="button" @click="replaceOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
                                </div>
                                <div class="space-y-4 px-6 py-5">
                                    <p class="text-sm text-gray-600">The replacement is added as a new row under the original line on the same RR (the original row is kept), then the RR goes back to Receiving for second count. The RR is completed once every back order is delivered.</p>

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Supplier <span class="text-red-500" x-show="current.is_cash_advance">*</span></label>
                                        <template x-if="current.is_cash_advance">
                                            <div class="space-y-2">
                                                <select name="supplier_id" x-model="rep.supplierId" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                                    <option value="">Other supplier (type the name)</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->supplier_id }}">{{ $supplier->supplier_name }}</option>
                                                    @endforeach
                                                </select>
                                                <input x-show="!rep.supplierId" type="text" name="supplier_name" x-model="rep.supplierName" maxlength="255" placeholder="Store / supplier name" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                                <p class="text-xs text-gray-500">Cash Advance can buy the replacement from any supplier. Picking a different one marks the row as <span class="font-semibold text-violet-700">New supplier</span>.</p>
                                            </div>
                                        </template>
                                        <template x-if="!current.is_cash_advance">
                                            <div>
                                                <p class="flex h-10 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700" x-text="current.supplier || 'Original supplier'"></p>
                                                <p class="mt-1 text-xs text-gray-500">Request for Check stays with the original supplier and price; the check was issued to them.</p>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Received</label>
                                            <input type="number" name="quantity" min="1" :max="current.quantity" x-model.number="rep.quantity" required class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                            <p class="mt-1 text-xs text-gray-500" x-text="'Of ' + current.quantity + ' on back order'"></p>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Damaged</label>
                                            <input type="number" name="damaged_qty" min="0" :max="rep.quantity" x-model.number="rep.damaged" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Unit price <span class="text-red-500" x-show="current.is_cash_advance">*</span></label>
                                            <input type="number" name="unit_price" step="0.01" min="0" x-model.number="rep.price" :readonly="!current.is_cash_advance" :class="current.is_cash_advance ? '' : 'bg-gray-50 text-gray-500'" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                        </div>
                                    </div>
                                    <p x-show="rep.quantity < current.quantity || rep.damaged > 0" class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-800">Units still missing or damaged after this delivery stay on back order.</p>

                                    <div x-show="current.is_cash_advance" class="rounded-lg border px-3 py-2 text-xs" :class="cashPreview().includes('additional') ? 'border-rose-100 bg-rose-50 text-rose-800' : 'border-sky-100 bg-sky-50 text-sky-800'" x-text="cashPreview()"></div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Invoice / receipt no.</label>
                                            <input type="text" name="reference" maxlength="255" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="Optional">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Remarks</label>
                                            <input type="text" name="remarks" maxlength="500" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="Optional">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Receipt / proof
                                            <span class="font-normal text-gray-400" x-text="current.is_cash_advance ? '(required for Cash Advance)' : '(optional)'"></span>
                                        </label>
                                        <input type="file" name="replacement_files[]" accept="{{ $fileAccept }}" multiple :required="current.is_cash_advance" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                                        <p class="mt-1 text-xs text-gray-500">Images, PDF, Word or Excel · up to {{ BackOrders::FILE_MAX }} files, 10 MB each.</p>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 border-t bg-gray-50 px-6 py-4">
                                    <button type="button" @click="replaceOpen = false" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</button>
                                    <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001db3]">Add to RR &amp; send for second count</button>
                                </div>
                            </div>
                        </template>
                    </form>
                </div>
            </div>
        </template>

        {{-- Refund (Cash Advance) --}}
        <template x-teleport="body">
            <div x-show="refundOpen && current" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="refundOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <form method="POST" :action="current ? current.refund_url : '#'" enctype="multipart/form-data" class="relative w-full max-w-xl rounded-2xl bg-white shadow-xl" @click.stop>
                        @csrf
                        <template x-if="current">
                            <div>
                                <div class="flex items-start justify-between gap-3 border-b px-6 py-5">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Record supplier refund</h3>
                                        <p class="text-sm text-gray-500" x-text="current.quantity + ' ' + (current.unit || 'pcs') + ' · ' + current.article"></p>
                                    </div>
                                    <button type="button" @click="refundOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" aria-label="Close"><i data-lucide="x" class="h-4 w-4"></i></button>
                                </div>
                                <div class="space-y-4 px-6 py-5">
                                    <p class="text-sm text-gray-600">The supplier returned the money. The back order stays open and the RR stays Incomplete until you buy the replacement and record it here.</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Refund amount</label>
                                            <input type="number" name="back_order_refund_amount" step="0.01" min="0.01" :max="current.max_refund > 0 ? current.max_refund : null" :value="current.max_refund > 0 ? current.max_refund.toFixed(2) : ''" required class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm">
                                            <p class="mt-1 text-xs text-gray-500" x-show="current.max_refund > 0" x-text="'Up to ' + money(current.max_refund)"></p>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Receipt / reference no.</label>
                                            <input type="text" name="back_order_refund_reference" maxlength="255" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" placeholder="Optional">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Refund proof</label>
                                        <input type="file" name="refund_files[]" accept="{{ $fileAccept }}" multiple required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
                                        <p class="mt-1 text-xs text-gray-500">Refund receipt or signed acknowledgment (image, PDF, Word or Excel).</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Remarks</label>
                                        <textarea name="back_order_remarks" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Optional" x-text="current.remarks || ''"></textarea>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 border-t bg-gray-50 px-6 py-4">
                                    <button type="button" @click="refundOpen = false" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Cancel</button>
                                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Record refund</button>
                                </div>
                            </div>
                        </template>
                    </form>
                </div>
            </div>
        </template>
    @endif
</div>
@endsection
