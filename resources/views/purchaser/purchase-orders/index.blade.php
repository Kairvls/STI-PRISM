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
            {{ $minAtps }} to {{ $maxAtps }} ATPs per PO · a single ATP is submitted directly to Accounting
        </p>
    </div>

    @unless($archiveView)
        @include('partials.draft-handover', ['type' => 'po'])
    @endunless
    @php
        $handoverOutgoing = \App\Support\DraftHandover::outgoing('po');
        $handoverDeclined = \App\Support\DraftHandover::declinedForSender('po');
    @endphp

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
                            $rowAtps = collect($order->linked_atps ?? [])->values();
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                    </div>
                                    <p class="font-semibold tracking-tight text-gray-900">{{ $label }}</p>
                                    @if(\App\Support\DocumentUrgency::isUrgent('PO', $order))
                                        @include('partials.ris-urgency-badge', ['urgent' => true, 'size' => 'sm', 'title' => 'Includes an ATP from an urgent RIS'])
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($rowAtps->isNotEmpty())
                                    <div data-atp-pop>
                                        <button
                                            type="button"
                                            data-atp-pop-trigger
                                            aria-expanded="false"
                                            aria-haspopup="true"
                                            class="max-w-full text-left font-medium text-gray-800 underline decoration-gray-300 decoration-dotted underline-offset-2 transition hover:text-[#0025cc] hover:decoration-[#0025cc]"
                                        >
                                            {{ $order->atp_display }}
                                        </button>
                                        <div
                                            data-atp-pop-panel
                                            hidden
                                            role="menu"
                                            class="fixed z-[300] min-w-[15rem] max-w-xs rounded-xl border border-slate-200/80 bg-white px-2 py-2 shadow-[0_8px_24px_rgba(15,23,42,0.14)]"
                                        >
                                            <p class="mb-1 px-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Open an ATP form</p>
                                            <ul>
                                                @foreach($rowAtps as $rowAtp)
                                                    <li>
                                                        <a
                                                            href="{{ route('purchaser.atp.index', ['view_atp' => $rowAtp->authority_purchase_id]) }}"
                                                            role="menuitem"
                                                            class="flex items-center justify-between gap-3 rounded-lg px-1.5 py-1.5 text-[12px] leading-5 text-slate-700 transition hover:bg-slate-50 hover:text-[#0025cc]"
                                                        >
                                                            <span class="font-mono font-medium">{{ \App\Support\PurchaseOrderBasket::atpListLabel($rowAtp) }}</span>
                                                            <span class="truncate text-[11px] text-slate-400">{{ $rowAtp->supplier_display ?? '' }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @else
                                    <span class="font-medium text-gray-800">{{ $order->atp_display ?? 'No ATPs' }}</span>
                                @endif
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
                                @if($pendingHandover = $handoverOutgoing->get((int) $order->purchase_order_id))
                                    <p class="mt-1 flex items-center gap-1 text-[11px] font-medium text-amber-700">
                                        <i data-lucide="hourglass" class="h-3 w-3"></i>
                                        Waiting for {{ $pendingHandover->to_name ?: 'co-worker' }} to accept
                                    </p>
                                @elseif($declinedHandover = $handoverDeclined->get((int) $order->purchase_order_id))
                                    <p class="mt-1 flex items-center gap-1 text-[11px] font-medium text-rose-700" title="{{ $declinedHandover->handover_response_note ? 'Reason: '.$declinedHandover->handover_response_note : '' }}">
                                        <i data-lucide="user-x" class="h-3 w-3"></i>
                                        Declined by {{ $declinedHandover->to_name ?: 'co-worker' }}
                                    </p>
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
                                            @disabled(($order->atp_count ?? 0) < $minAtps)
                                            title="{{ ($order->atp_count ?? 0) < $minAtps ? 'A Purchase Order needs at least '.$minAtps.' ATPs' : 'Submit' }}"
                                        >
                                            <i data-lucide="send" class="h-3.5 w-3.5"></i>
                                            Submit
                                        </button>
                                        @if($poHandover = $handoverOutgoing->get((int) $order->purchase_order_id))
                                            <form method="POST" action="{{ route('purchaser.handovers.cancel', $poHandover->handover_id) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                                                    title="Take back from {{ $poHandover->to_name ?: 'co-worker' }}"
                                                    aria-label="Take back from {{ $poHandover->to_name ?: 'co-worker' }}"
                                                >
                                                    <i data-lucide="undo-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button
                                                type="button"
                                                x-on:click="window.dispatchEvent(new CustomEvent('open-draft-handover', { detail: { type: 'po', id: {{ (int) $order->purchase_order_id }}, label: @js($label.' · '.(int) ($order->atp_count ?? 0).' ATP(s)') } }))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50"
                                                title="Pass to co-worker"
                                                aria-label="Pass to co-worker"
                                            >
                                                <i data-lucide="user-round-plus" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                    @endif
                                    @if(!$archiveView && $st === 'Approved' && (empty($order->funding_path) || !empty($order->funding_open_groups)))
                                        <button
                                            type="button"
                                            x-on:click="openModal = 'po-{{ $order->purchase_order_id }}'"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001db3]"
                                            title="{{ empty($order->funding_path) ? 'Choose payment path' : 'Create funding request' }}"
                                        >
                                            <i data-lucide="banknote" class="h-3.5 w-3.5"></i>
                                            Fund
                                        </button>
                                    @endif
                                    @if(!$archiveView && $st === 'Submitted')
                                        @include('partials.purchaser-reassign-reviewer', [
                                            'type' => 'po',
                                            'id' => $order->purchase_order_id,
                                            'currentReviewerId' => $order->purchase_order_assigned_reviewer_id ?? null,
                                        ])
                                    @endif
                                    @if(!$archiveView && in_array($st, ['Approved','Rejected','Cancelled'], true))
                                        <form
                                            method="POST"
                                            action="{{ route($pp.'.purchase-orders.archive', $order->purchase_order_id) }}"
                                            data-pur-confirm="Archive this Purchase Order?"
                                            data-pur-confirm-title="Archive Purchase Order"
                                            data-pur-confirm-ok="Archive"
                                            data-pur-confirm-kind="archive"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50"
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
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-[#0025cc] transition hover:bg-slate-50"
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
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold tracking-tight text-gray-950">{{ $label }}</h3>
                                @if(\App\Support\DocumentUrgency::isUrgent('PO', $order))
                                    @include('partials.ris-urgency-badge', ['urgent' => true, 'title' => 'Includes an ATP from an urgent RIS'])
                                @endif
                            </div>
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
                            @if(($order->purchase_order_status ?? '') !== 'Cancelled')
                                @include('partials.ris-revision-images', [
                                    'revision' => \App\Support\DocumentRevisionNotes::latest('PO', $order->purchase_order_id),
                                    'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                    'size' => 'sm',
                                ])
                            @endif
                        </div>
                    @endif
                    @forelse($linked as $atp)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ \App\Support\PurchaseOrderBasket::atpListLabel($atp) }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $atp->supplier_display ?? 'Supplier' }}
                                    @if(filled($atp->ris_form_number))
                                        · RIS {{ $atp->ris_form_number }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <p class="text-sm font-semibold tabular-nums text-gray-800">₱{{ number_format((float) ($atp->po_total_amount ?? 0), 2) }}</p>
                                <a
                                    href="{{ route('purchaser.atp.index', ['view_atp' => $atp->authority_purchase_id]) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                >
                                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                    Open ATP
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500">No ATPs linked.</p>
                    @endforelse
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-base font-semibold tabular-nums text-gray-950">₱{{ number_format((float) ($order->po_total_amount ?? 0), 2) }}</p>
                </div>
                @if(($order->purchase_order_status ?? '') === 'Approved' && !$archiveView)
                    @php
                        $fundingPath = $order->funding_path ?? null;
                        $fundingLabel = \App\Support\ProcurementPaymentPath::label($fundingPath);
                        $openGroups = $order->funding_open_groups ?? [];
                        $fundingRequests = $order->funding_requests ?? [];
                    @endphp
                    <div class="space-y-3 border-t border-gray-100 px-5 py-5">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Funding</p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Cash Advance: one request for the whole Purchase Order. Request for Check: one request per supplier.
                            </p>
                        </div>

                        @if(!$fundingPath)
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route($pp.'.purchase-orders.payment-path', $order->purchase_order_id) }}">
                                    @csrf
                                    <input type="hidden" name="payment_path" value="request_for_check">
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800 transition hover:bg-blue-100">
                                        <i data-lucide="file-check-2" class="h-4 w-4"></i>
                                        Request for Check
                                    </button>
                                </form>
                                <form method="POST" action="{{ route($pp.'.purchase-orders.payment-path', $order->purchase_order_id) }}">
                                    @csrf
                                    <input type="hidden" name="payment_path" value="cash_advance">
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-800 transition hover:bg-sky-100">
                                        <i data-lucide="banknote" class="h-4 w-4"></i>
                                        Cash Advance
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-sm text-gray-700">Payment path: <span class="font-medium">{{ $fundingLabel }}</span></p>

                            @foreach($openGroups as $group)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-gray-300 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $fundingPath === 'cash_advance' ? 'Whole Purchase Order' : $group['supplier_name'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ implode(', ', $group['atp_labels']) }} · ₱{{ number_format((float) $group['amount'], 2) }}</p>
                                    </div>
                                    <a
                                        href="{{ route($pp.'.rfc.index', ['funding_type' => $fundingPath, 'selected_source' => $group['key']]) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#001db3]"
                                    >
                                        <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                        Create {{ $fundingPath === 'cash_advance' ? 'Cash Advance' : 'RFC' }}
                                    </a>
                                </div>
                            @endforeach

                            @foreach($fundingRequests as $fr)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $fr->form_number ?: 'Draft' }}
                                            <span class="font-normal text-gray-500">· {{ \App\Support\ProcurementPaymentPath::label($fr->funding_type) }}</span>
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $fr->payee ?: '—' }} · {{ implode(', ', $fr->atp_labels) }}
                                            @if($fr->amount !== null) · ₱{{ number_format((float) $fr->amount, 2) }} @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @include('accounting.partials.status-badge', ['status' => $fr->status])
                                        <a href="{{ route($pp.'.rfc.index', ['view_rfc' => $fr->id]) }}" class="text-xs font-medium text-[#0025cc] hover:underline">Open</a>
                                    </div>
                                </div>
                            @endforeach

                            @if($openGroups === [] && $fundingRequests === [])
                                <p class="text-sm text-gray-500">No fundable ATPs remain on this Purchase Order.</p>
                            @endif
                        @endif
                    </div>
                @endif
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
                                            <p class="font-medium text-gray-900">{{ \App\Support\PurchaseOrderBasket::atpListLabel($atp) }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $atp->supplier_display ?? 'Supplier' }}
                                                @if(filled($atp->ris_form_number))
                                                    · RIS {{ $atp->ris_form_number }}
                                                @endif
                                            </p>
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
                                                <p class="font-medium text-gray-900">{{ \App\Support\PurchaseOrderBasket::atpListLabel($atp) }}</p>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    {{ $atp->supplier_display ?? 'Supplier' }}
                                                    @if(filled($atp->ris_form_number))
                                                        · RIS {{ $atp->ris_form_number }}
                                                    @endif
                                                </p>
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
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <p class="mr-auto text-xs text-gray-500">Need to adjust? Edit or remove the linked ATPs. A PO needs at least {{ $minAtps }} ATPs.</p>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" x-on:click="openModal = null" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">Close</button>
                            <button
                                type="button"
                                x-on:click="openSubmit({{ (int) $order->purchase_order_id }}, @js($label), @js(route($pp.'.purchase-orders.submit', $order->purchase_order_id)))"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:opacity-40"
                                @disabled($linked->count() < $minAtps)
                                title="{{ $linked->count() < $minAtps ? 'A Purchase Order needs at least '.$minAtps.' ATPs' : '' }}"
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
                        <p class="mt-1 text-sm text-gray-600">Choose who should review this Purchase Order.</p>
                    </div>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <p class="text-sm text-gray-600">
                        Submit <span class="font-semibold text-gray-900" x-text="submitConfirm?.number"></span> with its linked ATPs?
                    </p>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-600">Assign to Accounting <span class="text-red-500">*</span></label>
                        <select
                            name="assigned_reviewer_id"
                            required
                            class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-gray-300"
                        >
                            <option value="">Select Accounting…</option>
                            @foreach(\App\Support\ReviewerAssignment::options(\App\Support\WorkflowNotifier::ROLE_ACCOUNTING) as $reviewer)
                                <option value="{{ $reviewer['id'] }}">{{ $reviewer['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-400">Only the selected Accounting user will receive this PO for review.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
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

@include('partials.ris-revision-image-viewer')
@endsection

@push('scripts')
    @include('partials.atp-popover-script')
@endpush
