@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Liquidation Reports')
@section('page-subtitle', 'Liquidate cash advances from completed receiving reports. Request for Check workflows end at Receiving Report.')

@section('content')
<script type="application/json" id="liq-rr-prefill">{!! json_encode($rrPrefill ?? []) !!}</script>
<div
    x-data="{
        createOpen: {{ ($errors->any() && old('liquidation_report_receiving_report_id')) || !empty($selectedRrId) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewLiqId) ? 'true' : 'false' }},
        editOpen: false,
        emptyOpen: false,
        modalFullscreen: false,
        selectedLiq: {{ !empty($viewLiqId) ? (int) $viewLiqId : 'null' }},
        rrPrefill: JSON.parse(document.getElementById('liq-rr-prefill').textContent || '{}'),
        openView(id) { this.selectedLiq = id; this.viewOpen = true; this.editOpen = false; this.modalFullscreen = false; },
        openEdit(id) {
            this.selectedLiq = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('liq-' + id, 'Submitted by signature');
        },
        closeAll() { this.createOpen = false; this.viewOpen = false; this.editOpen = false; this.emptyOpen = false; this.modalFullscreen = false; this.selectedLiq = null; },
        bindDocSig(key, title) {
            this.$nextTick(() => {
                if (window.purchaserDocumentSignature) {
                    window.purchaserDocumentSignature.bind({
                        key: key,
                        hiddenId: 'purSigImage-' + key,
                        nameId: 'purSigName-' + key,
                        previewId: 'purSigOverlay-' + key,
                        slotId: 'purSigSlot-' + key,
                        title: title || 'Purchaser signature',
                        hint: 'Pick a saved signature, draw one, or upload. It overlays your printed name on the form above.'
                    });
                }
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },
        applyRrPrefill(rrId) {
            const data = this.rrPrefill[String(rrId)];
            const form = this.$refs.createForm;
            if (!form || !data) return;
            const purpose = form.querySelector('[name=liquidation_report_purpose]');
            const amount = form.querySelector('[name=liquidation_report_amount_advance]');
            if (purpose && data.purpose) purpose.value = data.purpose;
            if (amount && data.amount) amount.value = data.amount;
            const rows = data.items || [];
            for (let i = 0; i < 8; i++) {
                const item = rows[i] || {};
                const p = form.querySelector('[name=\'items[' + i + '][particulars]\']');
                const a = form.querySelector('[name=\'items[' + i + '][amount]\']');
                const aa = form.querySelector('[name=\'items[' + i + '][actual_amount]\']');
                const at = form.querySelector('[name=\'items[' + i + '][actual_total]\']');
                if (p) p.value = item.particulars ?? '';
                if (a) a.value = item.amount ?? '';
                if (aa) aa.value = item.actual_amount ?? '';
                if (at) at.value = item.actual_total ?? '';
            }
            const hintEl = form.querySelector('[data-cash-returned-hint]');
            const cashInput = form.querySelector('[data-cash-returned-input]');
            const hint = data.cash_returned_hint || '';
            if (hintEl) {
                hintEl.textContent = hint;
                hintEl.classList.toggle('hidden', !hint);
            }
            if (cashInput && hint) {
                cashInput.placeholder = hint;
                cashInput.classList.add('ring-1', 'ring-amber-400');
            } else if (cashInput) {
                cashInput.placeholder = 'Required when unused cash remains';
                cashInput.classList.remove('ring-1', 'ring-amber-400');
            }
        },
        printLiq(id) {
            const sheetId = id === 'blank' ? 'liq-print-blank' : ('liq-print-' + id);
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'liq-print-active');
                return;
            }
            const sheet = document.getElementById(sheetId);
            if (sheet) {
                document.querySelectorAll('.liq-print-sheet').forEach(function (s) {
                    s.classList.remove('liq-print-active');
                });
                sheet.classList.add('liq-print-active');
            }
            window.print();
        }
    }"
    x-init="
        if (createOpen) {
            $nextTick(() => {
                bindDocSig('liq-create', 'Submitted by signature');
                if ('{{ $selectedRrId ?? '' }}') applyRrPrefill('{{ $selectedRrId ?? '' }}');
            });
        }
    "
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="LR list view">
            <a
                href="{{ route(($pp ?? 'purchaser').'.liq.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route(($pp ?? 'purchaser').'.liq.index', ['view' => 'archive']) }}"
                class="pur-tab {{ $archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>

        @unless($archiveView)
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="emptyOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print Empty LR
                </button>
                <button
                    type="button"
                    @click="createOpen = true; modalFullscreen = false; bindDocSig('liq-create', 'Submitted by signature'); $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create Liquidation
                </button>
            </div>
        @endunless
    </div>

    <div class="mb-6">
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                [
                    'label' => 'Draft',
                    'hint' => 'Incomplete drafts awaiting submit',
                    'value' => number_format($summary['draft']),
                    'href' => route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Draft']),
                    'active' => request('status') === 'Draft',
                ],
                [
                    'label' => 'In Review',
                    'hint' => 'Waiting for accounting review',
                    'value' => number_format($summary['submitted']),
                    'href' => route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Submitted']),
                    'active' => request('status') === 'Submitted',
                ],
                [
                    'label' => 'Approved',
                    'hint' => 'Approved liquidation reports',
                    'value' => number_format($summary['approved']),
                    'href' => route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Approved']),
                    'active' => request('status') === 'Approved',
                ],
            ],
        ])
    </div>

    @php
        $liqHasFilters = request()->filled('search')
            || request()->filled('status')
            || request()->filled('date');
        $liqClearUrl = $archiveView
            ? route(($pp ?? 'purchaser').'.liq.index', ['view' => 'archive'])
            : route(($pp ?? 'purchaser').'.liq.index');
    @endphp

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            {{ $archiveView ? 'Archived LR' : 'LR Records' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $reports->total() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $archiveView ? 'Stored liquidation report records.' : 'Search and manage liquidation reports from completed RR.' }}
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route(($pp ?? 'purchaser').'.liq.index') }}"
                    role="search"
                    aria-label="Filter liquidation reports"
                    class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                >
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            id="liq-search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search liquidation, RR, employee"
                            aria-label="Search liquidation reports"
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select id="liq-status" name="status" aria-label="Filter by status" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(['Draft','Submitted','Minor Revision','Approved','Rejected'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s === 'Submitted' ? 'In Review' : $s }}</option>
                        @endforeach
                    </select>

                    <input id="liq-date" type="date" name="date" value="{{ request('date') }}" aria-label="Filter by date" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>

                    @if($liqHasFilters)
                        <a
                            href="{{ $liqClearUrl }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RR</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Employee</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($reports as $liq)
                        @php $editable = in_array($liq->liquidation_report_status, ['Draft','Minor Revision'], true) && !$archiveView; @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="receipt" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $liq->liquidation_report_form_number }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $liq->liquidation_report_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $liq->receiving_report_form_number ?? '—' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $liq->liquidation_report_employee_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-right tabular-nums font-medium text-gray-700">{{ $liq->liquidation_report_amount_advance !== null ? '₱'.number_format((float) $liq->liquidation_report_amount_advance, 2) : '—' }}</td>
                            <td class="px-5 py-4">@include('accounting.partials.status-badge', ['status' => $liq->liquidation_report_status])</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" @click="openView({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="View" aria-label="View"><i data-lucide="eye" class="h-4 w-4"></i></button>
                                    <button type="button" @click="printLiq({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]" title="Edit" aria-label="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.liq.submit', $liq->liquidation_report_id) }}"
                                            data-pur-confirm="Submit this Liquidation Report to Accounting?"
                                            data-pur-confirm-title="Submit Liquidation"
                                            data-pur-confirm-ok="Submit"
                                            data-pur-confirm-reviewer-role="Accounting"
                                        >
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]" title="Submit" aria-label="Submit"><i data-lucide="send" class="h-4 w-4"></i></button>
                                        </form>
                                    @endif
                                    @if(
                                        !$archiveView
                                        && in_array($liq->liquidation_report_status, ['Submitted', 'Under Review', 'Resubmitted', 'Pending Admin Approval'], true)
                                    )
                                        @include('partials.purchaser-reassign-reviewer', [
                                            'type' => 'liq',
                                            'id' => $liq->liquidation_report_id,
                                            'currentReviewerId' => $liq->liquidation_report_assigned_reviewer_id ?? null,
                                        ])
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.restore', $liq->liquidation_report_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50" title="Restore" aria-label="Restore"><i data-lucide="archive-restore" class="h-4 w-4"></i></button>
                                        </form>
                                    @elseif(in_array($liq->liquidation_report_status, ['Approved','Rejected'], true))
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.liq.archive', $liq->liquidation_report_id) }}"
                                            data-pur-confirm="Archive this liquidation?"
                                            data-pur-confirm-title="Archive Liquidation"
                                            data-pur-confirm-ok="Archive"
                                            data-pur-confirm-kind="archive"
                                        >
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50" title="Archive" aria-label="Archive"><i data-lucide="archive" class="h-4 w-4"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="receipt" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No liquidation reports found</p>
                                <p class="mt-1 text-sm text-gray-400">Create a liquidation or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-5 py-4">
            {{ $reports->links() }}
        </div>
    </div>

    {{-- PRINT EMPTY LR MODAL --}}
    <template x-teleport="body">
    <div
        x-cloak
        x-show="emptyOpen"
        x-transition.opacity
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="liq-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false; modalFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="printer" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="liq-empty-title" class="text-lg font-semibold text-gray-950">Print Empty LR</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Original blank Liquidation Report format.</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button
                            type="button"
                            x-on:click="emptyOpen = false; modalFullscreen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-100 p-3 md:p-5">
                    @include('partials.liquidation-report-paper', [
                        'editable' => false,
                        'liq' => null,
                        'rows' => collect(),
                        'printId' => 'liq-print-blank',
                    ])
                </div>

                <div class="print-hidden flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="px-2 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                    >
                        Cancel
                    </button>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.liq.export-blank-xlsx') }}"
                        data-tooltip="Export to Excel"
                        aria-label="Export to Excel"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 transition hover:border-emerald-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <path fill="#185C37" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                            <path fill="#21A366" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                            <path fill="#107C41" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                            <path fill="#FFF" d="M7.35 21.35 9.9 16.75l-2.4-4.5h1.85l1.5 3.15c.14.3.24.53.31.72h.04c.08-.22.19-.47.33-.76l1.55-3.11h1.7l-2.48 4.52 2.55 4.68h-1.82l-1.7-3.45c-.09-.18-.16-.35-.21-.52h-.04c-.05.18-.12.36-.22.55l-1.74 3.42H7.35z"/>
                        </svg>
                    </a>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.liq.export-blank-docx') }}"
                        data-tooltip="Export to Word file"
                        aria-label="Export to Word file"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                            <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                            <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                            <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                        </svg>
                    </a>
                    <button
                        type="button"
                        @click="printLiq('blank')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-medium text-white hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty LR
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    {{-- CREATE LIQ MODAL (outer scroll, same shell as RFC) --}}
    <template x-teleport="body">
    <div
        x-show="createOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
        x-effect="window.purDialog && window.purDialog.sync(createOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="liq-create-title"
    >
        <div
            x-on:click.self="createOpen = false; modalFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                @click.stop
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="liq-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Liquidation Report</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Select a completed Receiving Report.</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button type="button" @click="createOpen=false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.store') }}" enctype="multipart/form-data" x-ref="createForm">
                    @csrf
                    <input type="hidden" name="save_action" value="draft">
                    <div class="bg-slate-100 p-3 md:p-5">
                        @if($eligibleRrs->isEmpty())
                            <div class="mx-auto mb-4 w-full max-w-[1095px] rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                No completed Receiving Report is available. You can still fill out and save this Liquidation as a draft, then link a completed RR later before submitting.
                            </div>
                        @else
                            <div class="mx-auto mb-4 w-full max-w-[1095px]">
                                <label class="text-xs text-gray-500">Completed Receiving Report <span class="font-normal text-gray-400">(optional for draft)</span></label>
                                <select name="liquidation_report_receiving_report_id" x-on:change="applyRrPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border px-3 text-sm">
                                    <option value="">Select RR</option>
                                    @foreach($eligibleRrs as $rr)
                                        <option value="{{ $rr->receiving_report_id }}" {{ old('liquidation_report_receiving_report_id', $selectedRrId ?? '') == $rr->receiving_report_id ? 'selected' : '' }}>
                                            {{ $rr->receiving_report_form_number }} @if($rr->request_check_form_number)· {{ $rr->request_check_form_number }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => null, 'rows' => collect(), 'signKey' => 'liq-create'])
                        <div id="purSigSlot-liq-create" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                        <div
                            class="mx-auto mt-4 w-full max-w-[1095px] overflow-hidden rounded-xl border border-slate-200 bg-white"
                            x-data="{ attachmentNames: [] }"
                        >
                            <div class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-950">Supporting Documents</p>
                                    <p class="truncate text-[11px] text-slate-500">Optional · PDF, JPG, PNG · max 5MB each</p>
                                </div>
                                <button
                                    type="button"
                                    x-show="attachmentNames.length"
                                    x-cloak
                                    x-on:click="attachmentNames = []; $refs.createLiqAttachments.value = ''"
                                    class="shrink-0 text-xs font-medium text-slate-500 transition hover:text-slate-950"
                                >
                                    Clear
                                </button>
                            </div>
                            <div class="space-y-1.5 border-t border-slate-100 px-3.5 py-2.5">
                                <template x-for="(name, index) in attachmentNames" :key="index">
                                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        </div>
                                        <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="name"></p>
                                    </div>
                                </template>
                                <label class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2 transition hover:border-slate-400 hover:bg-slate-50">
                                    <input
                                        type="file"
                                        name="attachments[]"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only"
                                        x-ref="createLiqAttachments"
                                        x-on:change="attachmentNames = Array.from($event.target.files || []).map((f) => f.name); $nextTick(() => window.lucide && window.lucide.createIcons())"
                                    >
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200 transition group-hover:text-slate-800">
                                        <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-slate-800">Add files</p>
                                        <p class="truncate text-[10px] text-slate-500">Choose PDF, JPG, or PNG</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="createOpen = false" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            Save Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>

    @foreach($reports as $liq)
        @php
            $liqItems = $items->get($liq->liquidation_report_id, collect())->values();
            $liqFiles = $attachments->get($liq->liquidation_report_id, collect());
            $canEdit = in_array($liq->liquidation_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
        @endphp
        <template x-teleport="body">
        <div
            x-show="viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="liq-view-title-{{ $liq->liquidation_report_id }}"
        >
            <div
                x-on:click.self="viewOpen = false; modalFullscreen = false"
                class="flex min-h-full w-full justify-center"
            >
                <div
                    @click.stop
                    class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                    :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                    <i data-lucide="receipt" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 id="liq-view-title-{{ $liq->liquidation_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">{{ $liq->liquidation_report_form_number }}</h3>
                                    <p class="mt-0.5 text-sm text-gray-500">RR: {{ $liq->receiving_report_form_number ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="printLiq({{ $liq->liquidation_report_id }})"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                data-tooltip="Print LR"
                                aria-label="Print LR"
                            >
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            </button>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.liq.export-xlsx', $liq->liquidation_report_id) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 transition hover:border-emerald-300 hover:bg-emerald-50"
                                data-tooltip="Export to Excel"
                                aria-label="Export to Excel"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                    <path fill="#185C37" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                    <path fill="#21A366" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                    <path fill="#107C41" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                    <path fill="#FFF" d="M7.35 21.35 9.9 16.75l-2.4-4.5h1.85l1.5 3.15c.14.3.24.53.31.72h.04c.08-.22.19-.47.33-.76l1.55-3.11h1.7l-2.48 4.52 2.55 4.68h-1.82l-1.7-3.45c-.09-.18-.16-.35-.21-.52h-.04c-.05.18-.12.36-.22.55l-1.74 3.42H7.35z"/>
                                </svg>
                            </a>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.liq.export-docx', $liq->liquidation_report_id) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300 hover:bg-blue-50"
                                data-tooltip="Export to Word file"
                                aria-label="Export to Word file"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                    <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                    <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                    <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                    <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                                </svg>
                            </a>
                            @include('purchaser.partials.modal-fullscreen-button')
                            <button type="button" @click="viewOpen=false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                    @php
                        $liqLineage = \App\Support\DocumentLineage::forLiq((int) $liq->liquidation_report_id);
                        $liqHint = \App\Support\DocumentLineage::reviewHint($liq->liquidation_report_status ?? null, $liq->liquidation_report_review_stage ?? null, 'liq');
                    @endphp
                    @include('partials.document-lineage', [
                        'lineage' => $liqLineage,
                        'currentType' => 'LIQ',
                        'statusHint' => $liqHint,
                    ])
                    <div class="bg-slate-100 p-3 md:p-5">
                        @include('partials.liquidation-report-paper', ['editable' => false, 'liq' => $liq, 'rows' => $liqItems, 'printId' => 'liq-print-'.$liq->liquidation_report_id])
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="px-2 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </template>
        @if($canEdit)
            <template x-teleport="body">
            <div
                x-show="editOpen && selectedLiq === {{ $liq->liquidation_report_id }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedLiq === {{ $liq->liquidation_report_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                role="dialog"
                aria-modal="true"
                aria-labelledby="liq-edit-title-{{ $liq->liquidation_report_id }}"
            >
                <div
                    x-on:click.self="editOpen = false; modalFullscreen = false"
                    class="flex min-h-full w-full justify-center"
                >
                    <div
                        @click.stop
                        class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                        :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-full max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                    >
                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.update', $liq->liquidation_report_id) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="liquidation_report_receiving_report_id" value="{{ $liq->liquidation_report_receiving_report_id }}">
                            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                        <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                                    </div>
                                    <h3 id="liq-edit-title-{{ $liq->liquidation_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">Edit {{ $liq->liquidation_report_form_number }}</h3>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    @include('purchaser.partials.modal-fullscreen-button')
                                    <button type="button" @click="editOpen=false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="bg-slate-100 p-3 md:p-5">
                                @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => $liq, 'rows' => $liqItems, 'signKey' => 'liq-'.$liq->liquidation_report_id])
                                <div id="purSigSlot-liq-{{ $liq->liquidation_report_id }}" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                                <div
                                    class="mx-auto mt-4 w-full max-w-[1095px] overflow-hidden rounded-xl border border-slate-200 bg-white"
                                    x-data="{ attachmentNames: [] }"
                                >
                                    <div class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-950">Supporting Documents</p>
                                            <p class="truncate text-[11px] text-slate-500">Optional · PDF, JPG, PNG · max 5MB each</p>
                                        </div>
                                        <button
                                            type="button"
                                            x-show="attachmentNames.length"
                                            x-cloak
                                            x-on:click="attachmentNames = []; $refs.editLiqAttachments{{ $liq->liquidation_report_id }}.value = ''"
                                            class="shrink-0 text-xs font-medium text-slate-500 transition hover:text-slate-950"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                    <div class="space-y-1.5 border-t border-slate-100 px-3.5 py-2.5">
                                        @foreach($liqFiles as $file)
                                            <label class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5 text-xs text-slate-700">
                                                <input type="checkbox" name="delete_attachments[]" value="{{ $file->liquidation_attachment_id }}" class="rounded border-slate-300">
                                                <span class="min-w-0 flex-1 truncate">Remove {{ $file->liquidation_attachment_original_name }}</span>
                                            </label>
                                        @endforeach
                                        <template x-for="(name, index) in attachmentNames" :key="index">
                                            <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5">
                                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
                                                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                </div>
                                                <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="name"></p>
                                            </div>
                                        </template>
                                        <label class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2 transition hover:border-slate-400 hover:bg-slate-50">
                                            <input
                                                type="file"
                                                name="attachments[]"
                                                multiple
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                class="sr-only"
                                                x-ref="editLiqAttachments{{ $liq->liquidation_report_id }}"
                                                x-on:change="attachmentNames = Array.from($event.target.files || []).map((f) => f.name); $nextTick(() => window.lucide && window.lucide.createIcons())"
                                            >
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200 transition group-hover:text-slate-800">
                                                <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-medium text-slate-800">Add files</p>
                                                <p class="truncate text-[10px] text-slate-500">Choose PDF, JPG, or PNG</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                <button
                                    type="button"
                                    @click="editOpen = false"
                                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-950"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    onclick="this.form.save_action.value='draft'"
                                    class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Save Changes
                                </button>
                                <button
                                    type="submit"
                                    onclick="
                                        this.form.save_action.value='submit';
                                        if (window.purchaserDocumentSignature && !window.purchaserDocumentSignature.hasSignature()) {
                                            event.preventDefault();
                                            if (typeof window.showMpToast === 'function') showMpToast('Draw or upload your signature before submitting.', { title: 'Signature required', type: 'warning' });
                                            else alert('Draw or upload your signature before submitting.');
                                        }
                                    "
                                    class="rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-medium text-white hover:bg-blue-800"
                                >
                                    Save & Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </template>
        @endif
    @endforeach

    <div id="purDocSignatureDock" class="hidden" aria-hidden="true">
        @include('purchaser.partials.document-signature-panel', ['savedSignatures' => $savedSignatures ?? collect()])
    </div>
</div>
<style>
[x-cloak]{display:none!important}
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    .liq-print-active { background: #fff !important; }
}
</style>
@endsection
