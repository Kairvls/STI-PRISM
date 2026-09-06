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
        selectedLiq: {{ !empty($viewLiqId) ? (int) $viewLiqId : 'null' }},
        rrPrefill: JSON.parse(document.getElementById('liq-rr-prefill').textContent || '{}'),
        openView(id) { this.selectedLiq = id; this.viewOpen = true; this.editOpen = false; },
        openEdit(id) { this.selectedLiq = id; this.editOpen = true; this.viewOpen = false; },
        closeAll() { this.createOpen = false; this.viewOpen = false; this.editOpen = false; this.emptyOpen = false; this.selectedLiq = null; },
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
        if (createOpen && '{{ $selectedRrId ?? '' }}') {
            $nextTick(() => applyRrPrefill('{{ $selectedRrId ?? '' }}'));
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
                    @click="createOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create Liquidation
                </button>
            </div>
        @endunless
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Draft']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Draft</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['draft']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Incomplete drafts awaiting submit</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Submitted']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">In Review</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['submitted']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <i data-lucide="send" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Waiting for accounting review</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Approved']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['approved']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Approved liquidation reports</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route(($pp ?? 'purchaser').'.liq.index', ['status' => 'Rejected']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['rejected']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <i data-lucide="circle-x" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Returned or declined liquidation</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
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
                                        <button type="button" @click="openEdit({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]" title="Edit" aria-label="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.submit', $liq->liquidation_report_id) }}" onsubmit="return confirm('Submit this Liquidation Report?')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]" title="Submit" aria-label="Submit"><i data-lucide="send" class="h-4 w-4"></i></button>
                                        </form>
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.restore', $liq->liquidation_report_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Restore" aria-label="Restore"><i data-lucide="archive-restore" class="h-4 w-4"></i></button>
                                        </form>
                                    @elseif(in_array($liq->liquidation_report_status, ['Approved','Rejected'], true))
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.archive', $liq->liquidation_report_id) }}" onsubmit="return confirm('Archive this liquidation?')">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600 transition hover:bg-gray-100" title="Archive" aria-label="Archive"><i data-lucide="archive" class="h-4 w-4"></i></button>
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
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="liq-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto w-full max-w-6xl rounded-xl bg-white shadow-2xl">
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                            <i data-lucide="printer" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="liq-empty-title" class="text-lg font-semibold text-gray-950">Print Empty LR</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Original blank Liquidation Report format.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="overflow-x-auto bg-gray-100 p-5 md:p-8">
                    @include('partials.liquidation-report-paper', [
                        'editable' => false,
                        'liq' => null,
                        'rows' => collect(),
                        'printId' => 'liq-print-blank',
                    ])
                </div>

                <div class="print-hidden flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.liq.export-blank-xlsx') }}"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Excel
                    </a>
                    <a
                        href="{{ route(($pp ?? 'purchaser').'.liq.export-blank-docx') }}"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Word
                    </a>
                    <button
                        type="button"
                        @click="printLiq('blank')"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty LR
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="createOpen"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        x-effect="window.purDialog && window.purDialog.sync(createOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
    >
        <div class="fixed inset-0 bg-black/40" @click="createOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div
                @click.stop
                class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="liq-create-title"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="liq-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Liquidation Report</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Select a completed Receiving Report.</p>
                        </div>
                    </div>
                    <button type="button" @click="createOpen=false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                @if($eligibleRrs->isEmpty())
                    <div class="p-6 text-sm text-gray-600">No completed Receiving Report is available.</div>
                @else
                    <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.store') }}" enctype="multipart/form-data" x-ref="createForm">
                        @csrf
                        <input type="hidden" name="save_action" value="draft">
                        <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                            <div class="mx-auto mb-4 w-[297mm] max-w-full">
                                <label class="text-xs text-gray-500">Completed Receiving Report</label>
                                <select name="liquidation_report_receiving_report_id" x-on:change="applyRrPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border px-3 text-sm">
                                    <option value="">Select RR</option>
                                    @foreach($eligibleRrs as $rr)
                                        <option value="{{ $rr->receiving_report_id }}" {{ old('liquidation_report_receiving_report_id', $selectedRrId ?? '') == $rr->receiving_report_id ? 'selected' : '' }}>
                                            {{ $rr->receiving_report_form_number }} @if($rr->request_check_form_number)· {{ $rr->request_check_form_number }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => null, 'rows' => collect()])
                            <div class="mx-auto mt-3 w-[297mm] max-w-full rounded bg-white p-3 text-sm">
                                <label>Supporting documents (PDF, JPG, PNG · 5MB)</label>
                                <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4">
                            <button type="submit" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Save Draft</button>
                            <button type="submit" onclick="this.form.save_action.value='submit'" class="inline-flex items-center rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Save & Submit</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @foreach($reports as $liq)
        @php
            $liqItems = $items->get($liq->liquidation_report_id, collect())->values();
            $liqFiles = $attachments->get($liq->liquidation_report_id, collect());
            $canEdit = in_array($liq->liquidation_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
        @endphp
        <div
            x-show="viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        >
            <div class="fixed inset-0 bg-black/40" @click="viewOpen=false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="liq-view-title-{{ $liq->liquidation_report_id }}">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <i data-lucide="receipt" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 id="liq-view-title-{{ $liq->liquidation_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">{{ $liq->liquidation_report_form_number }}</h3>
                                    <p class="mt-0.5 text-sm text-gray-500">RR: {{ $liq->receiving_report_form_number ?? '—' }}</p>
                                </div>
                            </div>
                            @php
                                $liqLineage = \App\Support\DocumentLineage::forLiq((int) $liq->liquidation_report_id);
                                $liqHint = \App\Support\DocumentLineage::reviewHint($liq->liquidation_report_status ?? null, $liq->liquidation_report_review_stage ?? null, 'liq');
                            @endphp
                            <div class="mt-3">
                                @include('partials.document-lineage', [
                                    'lineage' => $liqLineage,
                                    'currentType' => 'LIQ',
                                    'statusHint' => $liqHint,
                                ])
                            </div>
                        </div>
                        <button type="button" @click="viewOpen=false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.liquidation-report-paper', ['editable' => false, 'liq' => $liq, 'rows' => $liqItems, 'printId' => 'liq-print-'.$liq->liquidation_report_id])
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4">
                        <button type="button" @click="printLiq({{ $liq->liquidation_report_id }})" class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-5 text-sm font-semibold text-white transition hover:bg-blue-800">Print</button>
                        <a href="{{ route(($pp ?? 'purchaser').'.liq.export-xlsx', $liq->liquidation_report_id) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Excel</a>
                        <a href="{{ route(($pp ?? 'purchaser').'.liq.export-docx', $liq->liquidation_report_id) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Word</a>
                        <button type="button" @click="viewOpen = false" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div
                x-show="editOpen && selectedLiq === {{ $liq->liquidation_report_id }}"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedLiq === {{ $liq->liquidation_report_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            >
                <div class="fixed inset-0 bg-black/40" @click="editOpen=false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div
                        @click.stop
                        class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="liq-edit-title-{{ $liq->liquidation_report_id }}"
                    >
                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.update', $liq->liquidation_report_id) }}" enctype="multipart/form-data">
                            @csrf @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="liquidation_report_receiving_report_id" value="{{ $liq->liquidation_report_receiving_report_id }}">
                            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                        <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                                    </div>
                                    <h3 id="liq-edit-title-{{ $liq->liquidation_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">Edit {{ $liq->liquidation_report_form_number }}</h3>
                                </div>
                                <button type="button" @click="editOpen=false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                            <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                                @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => $liq, 'rows' => $liqItems])
                                @foreach($liqFiles as $file)
                                    <label class="mx-auto mt-1 flex w-[297mm] max-w-full items-center gap-2 text-sm"><input type="checkbox" name="delete_attachments[]" value="{{ $file->liquidation_attachment_id }}"> Remove {{ $file->liquidation_attachment_original_name }}</label>
                                @endforeach
                                <input type="file" name="attachments[]" multiple class="mx-auto mt-2 block w-[297mm] max-w-full text-sm">
                            </div>
                            <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4">
                                <button type="submit" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Update Draft</button>
                                <button type="submit" onclick="this.form.save_action.value='submit'" class="inline-flex items-center rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Save & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
<style>
[x-cloak]{display:none!important}
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    .liq-print-active { background: #fff !important; }
}
</style>
@endsection
