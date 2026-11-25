@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Purchase Canvass')
@section('page-subtitle', 'Submit one request with multiple items. Each item has its own supplier and brand quotations, and School Admin approves each item independently.')

@section('content')
@php
    $routePrefix = $pp ?? 'purchaser';
    $statuses = \App\Support\CanvassWorkflow::statuses();
@endphp

<div
    class="space-y-5"
    x-data="{
        risModal: { open: false, title: '', printUrl: '', inlineHtml: '' },
        openRisModal(risId, title, printUrl) {
            const tpl = document.getElementById('canvass-ris-sheet-' + risId);
            this.risModal = {
                open: true,
                title,
                printUrl,
                inlineHtml: tpl ? tpl.innerHTML : '',
            };
        },
        closeRisModal() {
            this.risModal.open = false;
            this.risModal.inlineHtml = '';
            this.risModal.printUrl = '';
        },
        printRisSheet() {
            if (this.risModal.printUrl) {
                const sep = this.risModal.printUrl.includes('?') ? '&' : '?';
                window.open(this.risModal.printUrl + sep + 'auto=1', '_blank', 'noopener');
            }
        }
    }"
>
    {{-- Top bar: Active / Archive tabs + New Canvass CTA --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <a
                href="{{ route($routePrefix.'.canvass.index') }}"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ ! $archiveView ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            >
                <i data-lucide="scale" class="h-4 w-4"></i>
                <span>Active</span>
                <span class="rounded-full px-1.5 py-0.5 text-[11px] font-bold {{ ! $archiveView ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $activeCount }}</span>
            </a>
            <a
                href="{{ route($routePrefix.'.canvass.index', ['view' => 'archive']) }}"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ $archiveView ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            >
                <i data-lucide="archive" class="h-4 w-4"></i>
                <span>Archive</span>
                <span class="rounded-full px-1.5 py-0.5 text-[11px] font-bold {{ $archiveView ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $archiveCount }}</span>
            </a>
        </div>

        <a
            href="{{ route($routePrefix.'.canvass.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001db3]"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            <span>New Canvass</span>
        </a>
    </div>

    {{-- Filters + Table Card --}}
    <div class="pur-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
            method="GET"
            action="{{ route($routePrefix.'.canvass.index') }}"
            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"
        >
            @if ($archiveView)
                <input type="hidden" name="view" value="archive">
            @endif
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold text-slate-900">
                    {{ $archiveView ? 'Archived Canvasses' : 'Submitted & Draft Canvass Requests' }}
                </h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $canvasses->total() }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search CVS no., item, PR..."
                        class="w-56 rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:bg-white"
                    >
                </div>

                @unless ($archiveView)
                    <select
                        name="status"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-500 focus:bg-white"
                    >
                        <option value="">All statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                @endunless

                <button
                    type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#001db3]"
                >
                    <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                    <span>Apply</span>
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Request No.</th>
                        <th class="px-4 py-3.5">Date Submitted</th>
                        <th class="px-4 py-3.5">Items</th>
                        <th class="px-4 py-3.5">Suppliers</th>
                        <th class="px-4 py-3.5 text-right">Lowest Est. Total</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Request Details</th>
                        <th class="px-5 py-3.5 text-right">Edit / Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($canvasses as $row)
                        @php
                            $badge = \App\Support\CanvassWorkflow::statusBadge((string) $row->canvass_status, $row->linked_ris_status ?? null);
                            $editable = \App\Support\CanvassWorkflow::canEdit($row);
                            $canCreateRis = \App\Support\CanvassWorkflow::canConvertToRis($row);
                            $displayNo = \App\Support\CanvassWorkflow::displayNumber($row);
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 align-middle">
                                <div class="font-semibold text-slate-900">{{ $displayNo }}</div>
                                @if (! empty($row->canvass_request_no))
                                    <div class="mt-0.5 text-xs font-medium text-blue-700">{{ $row->canvass_request_no }}</div>
                                @endif
                                @if (! empty($row->linked_ris_number))
                                    <div class="mt-0.5 text-[11px] text-teal-700">RIS: {{ $row->linked_ris_number }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 align-middle text-xs text-slate-600">
                                @if ($row->canvass_submitted_at)
                                    {{ \Carbon\Carbon::parse($row->canvass_submitted_at)->format('Y-m-d H:i:s') }}
                                @else
                                    <span class="text-slate-400">Not submitted</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <span class="font-semibold text-slate-900">{{ $row->item_count }}</span>
                                @if (! empty($row->first_item_name))
                                    <div class="mt-0.5 flex max-w-[240px] items-center gap-1.5 text-xs text-slate-500">
                                        <span class="truncate">{{ $row->first_item_name }}</span>
                                        @if ($row->item_count > 1)
                                            @include('partials.closable-more-popover', [
                                                'moreCount' => $row->item_count - 1,
                                                'heading' => 'Canvass items',
                                                'popoverKey' => 'pur-canvass-'.$row->canvass_id,
                                                'rows' => collect($row->canvass_items_preview ?? [])->map(fn ($ci) => [
                                                    'title' => (string) ($ci->canvass_item_name ?: ($ci->catalog_item_name ?: 'Item')),
                                                    'meta' => 'Qty '.max(1, (int) ($ci->canvass_item_quantity ?? 1)).' '.($ci->uom_name ?: 'pcs'),
                                                ])->values()->all(),
                                            ])
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle text-slate-700">
                                {{ $row->supplier_count }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right align-middle font-semibold text-slate-900">
                                ₱{{ number_format((float) $row->lowest_total_sum, 2) }}
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <span
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    style="{{ $badge['style'] }}"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $badge['dot'] }};"></span>
                                    <span>{{ $badge['primary'] }}</span>
                                </span>
                                @if ($badge['secondary'] !== '')
                                    <div class="mt-1 text-[11px] font-medium leading-tight text-slate-500">
                                        {{ $badge['secondary'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <a
                                    href="{{ route($routePrefix.'.canvass.show', $row->canvass_id) }}"
                                    class="text-xs font-semibold text-blue-600 underline-offset-2 hover:underline"
                                >
                                    View items
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right align-middle">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    @if (! empty($row->canvass_ris_id))
                                        <button
                                            type="button"
                                            @click="openRisModal({{ (int) $row->canvass_ris_id }}, @js($row->linked_ris_number ?: 'RIS #'.$row->canvass_ris_id), @js(route($routePrefix.'.ris.print', $row->canvass_ris_id)))"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-xl transition hover:opacity-85"
                                            style="background:#eff6ff;color:#0025cc;"
                                            data-tip="View RIS &amp; ATP"
                                            data-tip-place="above"
                                            aria-label="View RIS &amp; ATP"
                                        >
                                            <i data-lucide="file-text" class="h-4 w-4"></i>
                                        </button>
                                    @endif

                                    @if (! $archiveView)
                                        @if ($editable)
                                            @php $isReturnedRow = (string) ($row->canvass_status ?? '') === \App\Support\CanvassWorkflow::STATUS_RETURNED; @endphp
                                            <a
                                                href="{{ route($routePrefix.'.canvass.edit', $row->canvass_id) }}"
                                                class="inline-flex items-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition hover:opacity-90"
                                                style="{{ $isReturnedRow ? 'background:#fff7ed;border-color:#fdba74;color:#c2410c;' : 'background:#ffffff;border-color:#93c5fd;color:#1d4ed8;' }}"
                                            >
                                                {{ $isReturnedRow ? 'Edit & Resubmit' : 'Edit' }}
                                            </a>
                                        @else
                                            <span
                                                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400"
                                                data-tip="Locked — review has started or is already approved"
                                                data-tip-place="above"
                                                aria-label="Locked"
                                            >
                                                <i data-lucide="lock" class="h-3.5 w-3.5"></i>
                                            </span>
                                        @endif

                                        <form method="POST" action="{{ route($routePrefix.'.canvass.archive', $row->canvass_id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                title="Archive canvass"
                                                onclick="return confirm('Archive this canvass?')"
                                            >
                                                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route($routePrefix.'.canvass.restore', $row->canvass_id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Restore
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <i data-lucide="scale" class="h-6 w-6"></i>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-900">
                                    {{ $archiveView ? 'No archived canvasses' : 'No purchase canvasses yet' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Create a canvass to compare 2–3 suppliers and up to 10 brand offers per item before generating an RIS.
                                </p>
                                @unless ($archiveView)
                                    <a
                                        href="{{ route($routePrefix.'.canvass.create') }}"
                                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-[#0025cc] px-4 py-2 text-xs font-semibold text-white hover:bg-[#001db3]"
                                    >
                                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                        <span>Create first canvass</span>
                                    </a>
                                @endunless
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canvasses->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $canvasses->links() }}
            </div>
        @endif
    </div>

    {{-- Pre-rendered RIS + linked ATP sheets for 0ms instant modal display --}}
    @foreach (($linkedRisSheets ?? []) as $rid => $sheet)
        @php $sheetAtps = collect($sheet['atps'] ?? [])->values(); @endphp
        <template id="canvass-ris-sheet-{{ $rid }}">
            @if ($sheetAtps->isNotEmpty())
                <div data-canvass-doc-root>
                    <div class="mb-4 flex items-center gap-5 rounded-xl border border-slate-200 bg-white px-4 pt-2">
                        <button
                            type="button"
                            data-canvass-doc-tab="ris"
                            class="border-b-2 pb-2.5 text-xs font-semibold transition"
                            style="border-color:#2563eb;color:#1d4ed8;"
                        >
                            Requisition Slip
                        </button>
                        <button
                            type="button"
                            data-canvass-doc-tab="atp"
                            class="inline-flex items-center gap-1.5 border-b-2 pb-2.5 text-xs font-semibold transition"
                            style="border-color:transparent;color:#64748b;"
                        >
                            <span>Authorities to Purchase</span>
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold" style="background:#eff6ff;color:#1d4ed8;">{{ $sheetAtps->count() }}</span>
                        </button>
                    </div>

                    <div data-canvass-doc-pane="ris">
                        @include('partials.ris-purchaser-sheet', [
                            'ris' => $sheet['ris'],
                            'risItems' => $sheet['items'],
                        ])
                    </div>

                    <div data-canvass-doc-pane="atp" class="hidden space-y-3">
                        @if ($sheetAtps->count() > 1)
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-2.5">
                                <button
                                    type="button"
                                    data-canvass-atp-prev
                                    disabled
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
                                    title="Previous ATP"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                </button>
                                <span class="text-xs font-semibold text-slate-700" data-canvass-atp-label>
                                    ATP 1 of {{ $sheetAtps->count() }} · {{ $sheetAtps->first()->authority_purchase_form_number ?: ('ATP #'.$sheetAtps->first()->authority_purchase_id) }} · {{ $sheetAtps->first()->company_name ?: ($sheetAtps->first()->shop_name ?: 'Supplier') }}
                                </span>
                                <button
                                    type="button"
                                    data-canvass-atp-next
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:opacity-90 disabled:opacity-40"
                                    style="background:#2563eb;color:#ffffff;"
                                    title="Next ATP"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </button>
                            </div>
                        @endif

                        @foreach ($sheetAtps as $atpIdx => $linkedAtp)
                            <div
                                class="{{ $atpIdx === 0 ? '' : 'hidden' }}"
                                data-canvass-atp-sheet="{{ $atpIdx }}"
                                data-canvass-atp-title="{{ $linkedAtp->authority_purchase_form_number ?: ('ATP #'.$linkedAtp->authority_purchase_id) }} · {{ $linkedAtp->company_name ?: ($linkedAtp->shop_name ?: 'Supplier') }}"
                            >
                                @include('partials.authority-to-purchase-paper', [
                                    'editable' => false,
                                    'atp' => $linkedAtp,
                                    'items' => $linkedAtp->atpItems ?? collect(),
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                @include('partials.ris-purchaser-sheet', [
                    'ris' => $sheet['ris'],
                    'risItems' => $sheet['items'],
                ])
            @endif
        </template>
    @endforeach

    <script>
        (function () {
            if (window.__canvassDocModalTabsInit) return;
            window.__canvassDocModalTabsInit = true;
            document.addEventListener('click', function (event) {
                var tabBtn = event.target.closest('[data-canvass-doc-tab]');
                if (tabBtn) {
                    var root = tabBtn.closest('[data-canvass-doc-root]');
                    if (!root) return;
                    var target = tabBtn.getAttribute('data-canvass-doc-tab');
                    root.querySelectorAll('[data-canvass-doc-pane]').forEach(function (pane) {
                        pane.classList.toggle('hidden', pane.getAttribute('data-canvass-doc-pane') !== target);
                    });
                    root.querySelectorAll('[data-canvass-doc-tab]').forEach(function (btn) {
                        var active = btn.getAttribute('data-canvass-doc-tab') === target;
                        btn.style.borderColor = active ? '#2563eb' : 'transparent';
                        btn.style.color = active ? '#1d4ed8' : '#64748b';
                    });
                    return;
                }

                var stepBtn = event.target.closest('[data-canvass-atp-prev], [data-canvass-atp-next]');
                if (stepBtn) {
                    var root = stepBtn.closest('[data-canvass-doc-root]');
                    if (!root) return;
                    var sheets = Array.from(root.querySelectorAll('[data-canvass-atp-sheet]'));
                    if (!sheets.length) return;
                    var cur = sheets.findIndex(function (el) { return !el.classList.contains('hidden'); });
                    if (cur < 0) cur = 0;
                    var next = stepBtn.hasAttribute('data-canvass-atp-next') ? cur + 1 : cur - 1;
                    next = Math.max(0, Math.min(sheets.length - 1, next));
                    sheets.forEach(function (el, idx) { el.classList.toggle('hidden', idx !== next); });
                    var labelEl = root.querySelector('[data-canvass-atp-label]');
                    if (labelEl && sheets[next]) {
                        labelEl.textContent = 'ATP ' + (next + 1) + ' of ' + sheets.length + ' · ' + (sheets[next].getAttribute('data-canvass-atp-title') || '');
                    }
                    var prevBtn = root.querySelector('[data-canvass-atp-prev]');
                    var nextBtn = root.querySelector('[data-canvass-atp-next]');
                    if (prevBtn) prevBtn.disabled = next === 0;
                    if (nextBtn) nextBtn.disabled = next >= sheets.length - 1;
                }
            });
        })();
    </script>

    {{-- Teleported Instant RIS & ATP Preview Modal --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="risModal.open"
            @keydown.escape.window="if (risModal.open) closeRisModal()"
            @click.self="closeRisModal()"
            class="fixed inset-0 z-[260] flex items-center justify-center bg-slate-900/50 p-4"
        >
            <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-5 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <h3 class="text-sm font-bold text-slate-900">RIS &amp; ATP</h3>
                        <span
                            class="rounded-md px-2 py-0.5 text-xs font-semibold"
                            style="background:#eff6ff;color:#1d4ed8;"
                            x-text="risModal.title"
                        ></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="printRisSheet()"
                            title="Print RIS"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold text-white transition hover:opacity-90"
                            style="background:#0025cc;color:#ffffff;"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                            </svg>
                            <span>Print</span>
                        </button>
                        <button
                            type="button"
                            @click="closeRisModal()"
                            title="Close"
                            aria-label="Close"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            ✕
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-6">
                    <div x-html="risModal.inlineHtml"></div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
