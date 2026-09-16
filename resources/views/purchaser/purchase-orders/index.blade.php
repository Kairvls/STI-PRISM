@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Purchase Orders')
@section('page-subtitle', 'Group draft ATPs and submit to Accounting.')

@section('content')

@php
    $pp = $pp ?? 'purchaser';
@endphp

<div
    x-data="{
        openModal: @js($viewPoId ? ('po-'.$viewPoId) : ($editPoId ? ('edit-po-'.$editPoId) : null)),
        submitConfirm: null,
        submitSending: false,
        openSubmit(id, number, action) {
            this.submitSending = false;
            this.submitConfirm = { id, number, action };
        },
        closeSubmit() {
            if (this.submitSending) return;
            this.submitConfirm = null;
        }
    }"
    x-init="$nextTick(() => window.lucide && window.lucide.createIcons())"
    x-effect="if (openModal || submitConfirm) { $nextTick(() => window.lucide && window.lucide.createIcons()) }"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="Purchase order list view">
            <a href="{{ route($pp.'.purchase-orders.index') }}" class="pur-tab {{ !$archiveView ? 'is-active' : '' }}">
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a href="{{ route($pp.'.purchase-orders.index', ['view' => 'archive']) }}" class="pur-tab {{ $archiveView ? 'is-active' : '' }}">
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>
        <p class="text-sm text-gray-500">
            Up to {{ $maxAtps }} ATPs per PO
        </p>
    </div>

    <div class="pur-card overflow-visible">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold tracking-tight text-gray-950">
                            {{ $archiveView ? 'Archived orders' : 'Purchase orders' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $orders->total() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $archiveView ? 'Restore orders when you need them again.' : 'Draft ATPs pack here — review, then submit to Accounting.' }}
                    </p>
                </div>
                <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    <select
                        name="status"
                        class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
                    >
                        <option value="">All statuses</option>
                        @foreach(['Draft','Submitted','Approved','Rejected','Cancelled'] as $opt)
                            <option value="{{ $opt }}" @selected(($statusFilter ?? '') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>
                    @if(($statusFilter ?? '') !== '')
                        <a
                            href="{{ route($pp.'.purchase-orders.index', $archiveView ? ['view' => 'archive'] : []) }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">PO</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATPs</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Updated</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($orders as $order)
                        @php
                            $isDraft = ($order->purchase_order_status ?? '') === 'Draft';
                            $label = \App\Support\PurchaseOrderBasket::displayNumber($order);
                            $st = $order->purchase_order_status ?? 'Draft';
                            $atpNumbers = collect($order->atp_numbers ?? [])->filter()->values();
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                    </div>
                                    <p class="font-semibold tracking-tight text-gray-900">{{ $label }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="group relative z-0 inline-block max-w-full hover:z-50">
                                    <span class="cursor-default font-medium text-gray-800 {{ $atpNumbers->isNotEmpty() ? 'underline decoration-gray-300 decoration-dotted underline-offset-2' : '' }}">
                                        {{ $order->atp_display ?? 'No ATPs' }}
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
                                <p class="mt-0.5 text-xs text-gray-400">{{ (int) ($order->atp_count ?? 0) }} of {{ $maxAtps }}</p>
                            </td>
                            <td class="px-5 py-4 font-medium tabular-nums text-gray-800">
                                ₱{{ number_format((float) ($order->po_total_amount ?? 0), 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium
                                    {{ $st === 'Draft' ? 'bg-slate-100 text-slate-700' : '' }}
                                    {{ $st === 'Submitted' ? 'bg-blue-50 text-blue-700' : '' }}
                                    {{ $st === 'Approved' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                    {{ $st === 'Rejected' ? 'bg-rose-50 text-rose-700' : '' }}
                                    {{ $st === 'Cancelled' ? 'bg-stone-100 text-stone-700' : '' }}
                                ">{{ $st }}</span>
                                @if(!empty($order->purchase_order_revision_reason) && in_array($st, ['Draft', 'Cancelled'], true))
                                    <p class="mt-1 max-w-[14rem] text-xs leading-4 text-amber-700">{{ \Illuminate\Support\Str::limit($order->purchase_order_revision_reason, 72) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-500">
                                {{ !empty($order->purchase_order_updated_at) ? \Carbon\Carbon::parse($order->purchase_order_updated_at)->format('M d, Y') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="openModal = 'po-{{ $order->purchase_order_id }}'"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50"
                                        title="View"
                                        aria-label="View"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    @if($isDraft && !$archiveView)
                                        <button
                                            type="button"
                                            x-on:click="openModal = 'edit-po-{{ $order->purchase_order_id }}'"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                            title="Edit"
                                            aria-label="Edit"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="openSubmit({{ (int) $order->purchase_order_id }}, @js($label), @js(route($pp.'.purchase-orders.submit', $order->purchase_order_id)))"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                                            @disabled(($order->atp_count ?? 0) < 1)
                                            title="Submit"
                                        >
                                            <i data-lucide="send" class="h-3.5 w-3.5"></i>
                                            Submit
                                        </button>
                                    @endif
                                    @if(!$archiveView && in_array($st, ['Draft', 'Submitted'], true))
                                        <button
                                            type="button"
                                            x-on:click="openModal = 'cancel-po-{{ $order->purchase_order_id }}'"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-500 transition hover:bg-stone-50"
                                            title="Cancel"
                                            aria-label="Cancel"
                                        >
                                            <i data-lucide="ban" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                    @if(!$archiveView && in_array($st, ['Approved','Rejected','Cancelled'], true))
                                        <form method="POST" action="{{ route($pp.'.purchase-orders.archive', $order->purchase_order_id) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
                                                title="Archive"
                                                aria-label="Archive"
                                            >
                                                <i data-lucide="archive" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route($pp.'.purchase-orders.restore', $order->purchase_order_id) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                            >
                                                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                                Restore
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No purchase orders yet</p>
                                <p class="mt-1 text-sm text-gray-400">Create draft ATPs and they will pack into a draft PO automatically.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>

    @foreach($orders as $order)
        @php
            $isDraft = ($order->purchase_order_status ?? '') === 'Draft';
            $label = \App\Support\PurchaseOrderBasket::displayNumber($order);
            $linked = collect($order->linked_atps ?? []);
        @endphp

        {{-- VIEW --}}
        <template x-teleport="body">
        <div
            x-show="openModal === 'po-{{ $order->purchase_order_id }}'"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
            x-effect="window.purDialog && window.purDialog.sync(openModal === 'po-{{ $order->purchase_order_id }}', $el)"
            x-on:keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            x-on:keydown.escape.window="openModal = null"
            x-on:click.self="openModal = null"
        >
            <div class="my-auto w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="shopping-bag" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight text-gray-950">{{ $label }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">{{ $order->purchase_order_status }} · {{ $linked->count() }} ATP(s)</p>
                        </div>
                    </div>
                    <button type="button" x-on:click="openModal = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-3 px-5 py-5">
                    @if(!empty($order->purchase_order_revision_reason))
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-900">
                            <span class="font-medium">{{ ($order->purchase_order_status ?? '') === 'Cancelled' ? 'Cancel reason' : 'Note' }}:</span>
                            {{ $order->purchase_order_revision_reason }}
                        </div>
                    @endif
                    @forelse($linked as $atp)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $atp->authority_purchase_form_number ?: ('ATP #'.$atp->authority_purchase_id) }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $atp->supplier_display ?? 'Supplier' }} · RIS {{ $atp->ris_form_number ?: '—' }}</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-gray-800">₱{{ number_format((float) ($atp->po_total_amount ?? 0), 2) }}</p>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500">No ATPs linked.</p>
                    @endforelse
                </div>
                <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-base font-semibold tabular-nums text-gray-950">₱{{ number_format((float) ($order->po_total_amount ?? 0), 2) }}</p>
                </div>
            </div>
        </div>
        </template>

        {{-- EDIT DRAFT --}}
        @if($isDraft)
            <template x-teleport="body">
            <div
                x-show="openModal === 'edit-po-{{ $order->purchase_order_id }}'"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
                x-effect="window.purDialog && window.purDialog.sync(openModal === 'edit-po-{{ $order->purchase_order_id }}', $el)"
                x-on:keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                x-on:keydown.escape.window="openModal = null"
                x-on:click.self="openModal = null"
            >
                <div class="my-auto w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                <i data-lucide="pencil" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold tracking-tight text-gray-950">Edit {{ $label }}</h3>
                                <p class="mt-0.5 text-sm text-gray-500">{{ $linked->count() }} of {{ $maxAtps }} ATPs linked</p>
                            </div>
                        </div>
                        <button type="button" x-on:click="openModal = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <div class="space-y-5 px-5 py-5">
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Linked ATPs</p>
                            <div class="space-y-2">
                                @forelse($linked as $atp)
                                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $atp->authority_purchase_form_number ?: ('ATP #'.$atp->authority_purchase_id) }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $atp->supplier_display ?? 'Supplier' }}</p>
                                        </div>
                                        <form method="POST" action="{{ route($pp.'.purchase-orders.detach', $order->purchase_order_id) }}">
                                            @csrf
                                            <input type="hidden" name="authority_purchase_id" value="{{ $atp->authority_purchase_id }}">
                                            <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-2.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                <i data-lucide="minus" class="h-3.5 w-3.5"></i>
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500">No ATPs linked yet.</p>
                                @endforelse
                            </div>
                        </div>

                        @if(($order->open_slots ?? 0) > 0 && $availableAtps->isNotEmpty())
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Available draft ATPs</p>
                                <div class="space-y-2">
                                    @foreach($availableAtps as $atp)
                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-gray-200 px-4 py-3">
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $atp->authority_purchase_form_number ?: ('ATP #'.$atp->authority_purchase_id) }}</p>
                                                <p class="mt-0.5 text-xs text-gray-500">{{ $atp->supplier_display ?? 'Supplier' }}</p>
                                            </div>
                                            <form method="POST" action="{{ route($pp.'.purchase-orders.attach', $order->purchase_order_id) }}">
                                                @csrf
                                                <input type="hidden" name="authority_purchase_id" value="{{ $atp->authority_purchase_id }}">
                                                <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100">
                                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                                    Add
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <p class="text-xs text-gray-500">Supplier can’t meet quota? Cancel the PO — the record is kept.</p>
                        <div class="flex gap-2">
                            <button type="button" x-on:click="openModal = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Close</button>
                            <button
                                type="button"
                                x-on:click="openSubmit({{ (int) $order->purchase_order_id }}, @js($label), @js(route($pp.'.purchase-orders.submit', $order->purchase_order_id)))"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-40"
                                @disabled($linked->isEmpty())
                            >
                                <i data-lucide="send" class="h-4 w-4"></i>
                                Submit to Accounting
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            </template>
        @endif

        {{-- CANCEL --}}
        @if(in_array(($order->purchase_order_status ?? ''), ['Draft', 'Submitted'], true))
            <template x-teleport="body">
            <div
                x-show="openModal === 'cancel-po-{{ $order->purchase_order_id }}'"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
                x-effect="window.purDialog && window.purDialog.sync(openModal === 'cancel-po-{{ $order->purchase_order_id }}', $el)"
                x-on:keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                x-on:keydown.escape.window="openModal = null"
                x-on:click.self="openModal = null"
            >
                <div class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
                    <form method="POST" action="{{ route($pp.'.purchase-orders.cancel', $order->purchase_order_id) }}">
                        @csrf
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-stone-100 text-stone-700">
                                    <i data-lucide="ban" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold tracking-tight text-gray-950">Cancel Purchase Order</h3>
                                    <p class="mt-0.5 text-sm text-gray-500">Record is kept for history.</p>
                                </div>
                            </div>
                            <button type="button" x-on:click="openModal = null" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <div class="space-y-3 px-5 py-5">
                            <p class="text-sm text-gray-600">
                                Cancel <span class="font-semibold text-gray-900">{{ $label }}</span>? Linked ATPs return to draft so you can regroup them.
                            </p>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Reason <span class="text-red-500">*</span></label>
                                <textarea name="cancel_reason" rows="3" required class="box-border w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white" placeholder="e.g. Supplier cannot meet quota"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                            <button type="button" x-on:click="openModal = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Keep</button>
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-stone-800 px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-stone-900">Cancel PO</button>
                        </div>
                    </form>
                </div>
            </div>
            </template>
        @endif
    @endforeach

    <template x-teleport="body">
    <div
        x-show="submitConfirm"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[210] flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(!!submitConfirm, $el)"
        x-on:keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        x-on:keydown.escape.window="closeSubmit()"
        x-on:click.self="closeSubmit()"
    >
        <div class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
            <form x-bind:action="submitConfirm?.action || '#'" method="POST" x-on:submit="submitSending = true">
                @csrf
                <div class="flex items-start gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                        <i data-lucide="send" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight text-gray-950">Submit to Accounting</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Submit <span class="font-semibold text-gray-900" x-text="submitConfirm?.number"></span> with its linked ATPs?
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 bg-gray-50 px-5 py-4">
                    <button type="button" x-on:click="closeSubmit()" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Cancel</button>
                    <button type="submit" x-bind:disabled="submitSending" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-50">
                        Yes, submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>

@endsection
