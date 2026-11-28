@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Liquidation Reports')
@section('page-subtitle', 'Liquidate cash advances after receiving.')

@section('content')
@php
    $accountingReviewers = \App\Support\ReviewerAssignment::options(\App\Support\WorkflowNotifier::ROLE_ACCOUNTING);
    $preferredAccountingId = \App\Support\ReviewerAssignment::preferredId($accountingReviewers);
@endphp
<script type="application/json" id="liq-rr-prefill">{!! json_encode($rrPrefill ?? []) !!}</script>
<script type="application/json" id="liq-accounting-reviewers">{!! json_encode($accountingReviewers) !!}</script>
<div
    x-data="{
        createOpen: {{ ($errors->any() && old('liquidation_report_receiving_report_id')) || !empty($selectedRrId) ? 'true' : 'false' }},
        createSubmitConfirmOpen: false,
        createSubmitReviewerId: @js($preferredAccountingId ? (string) $preferredAccountingId : ''),
        accountingReviewers: JSON.parse(document.getElementById('liq-accounting-reviewers').textContent || '[]'),
        createSubmitSummary: { employee: '', purpose: '', amount: '', dateSubmitted: '', rrLabel: '' },
        selectedRrOrSummary: null,
        fmtPeso(v) {
            if (v === null || v === undefined || v === '' || Number.isNaN(Number(v))) return '—';
            return '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        viewOpen: {{ !empty($viewLiqId) ? 'true' : 'false' }},
        editOpen: false,
        emptyOpen: false,
        modalFullscreen: false,
        selectedLiq: {{ !empty($viewLiqId) ? (int) $viewLiqId : 'null' }},
        rrPrefill: JSON.parse(document.getElementById('liq-rr-prefill').textContent || '{}'),
        selectedAccountingName() {
            const match = (this.accountingReviewers || []).find(r => String(r.id) === String(this.createSubmitReviewerId));
            if (match) return match.name;
            return (this.accountingReviewers && this.accountingReviewers[0]) ? this.accountingReviewers[0].name : 'Accounting Officer';
        },
        openCreateSubmitConfirm() {
            const form = this.$refs.createForm || document.getElementById('liqCreateForm');
            if (!form) return;
            const save = form.querySelector('[name=save_action]');
            if (save) save.value = 'submit';
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }
            const rrSel = form.querySelector('[name=liquidation_report_receiving_report_id]');
            const rrLabel = rrSel && rrSel.selectedIndex > 0 ? rrSel.options[rrSel.selectedIndex].text.trim() : '—';
            const employee = (form.querySelector('[name=liquidation_report_employee_name]')?.value || '').trim() || '—';
            const purpose = (form.querySelector('[name=liquidation_report_purpose]')?.value || '').trim() || '—';
            const rawAmt = (form.querySelector('[name=liquidation_report_amount_advance]')?.value || '').replace(/,/g, '').trim();
            const numAmt = Number(rawAmt);
            const amount = rawAmt !== '' && !Number.isNaN(numAmt)
                ? '₱' + numAmt.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';
            const dateSubmitted = (form.querySelector('[name=liquidation_report_date_submitted]')?.value || '').trim() || 'Today';
            this.createSubmitSummary = { employee, purpose, amount, dateSubmitted, rrLabel };
            this.createSubmitConfirmOpen = true;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
        confirmCreateSubmit() {
            const form = this.$refs.createForm || document.getElementById('liqCreateForm');
            if (!form) return;
            const save = form.querySelector('[name=save_action]');
            if (save) save.value = 'submit';
            const rev = form.querySelector('[name=assigned_reviewer_id]');
            if (rev && this.createSubmitReviewerId) rev.value = this.createSubmitReviewerId;
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            this.createSubmitConfirmOpen = false;
            form.submit();
        },
        openView(id) { this.selectedLiq = id; this.viewOpen = true; this.editOpen = false; this.modalFullscreen = false; },
        openEdit(id) {
            this.selectedLiq = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('liq-' + id, 'Submitted by signature');
        },
        closeAll() { this.createOpen = false; this.viewOpen = false; this.editOpen = false; this.emptyOpen = false; this.modalFullscreen = false; this.selectedLiq = null; },
        guardClose(kind) {
            const form = kind === 'create'
                ? this.$refs.createForm
                : document.querySelector('[data-pur-edit-form=liq][data-open=1]');
            const finish = () => {
                if (kind === 'create') this.createOpen = false;
                else this.editOpen = false;
                this.modalFullscreen = false;
            };
            if (window.purGuardFormClose) { window.purGuardFormClose(form, finish); return; }
            finish();
        },
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
            const form = this.$refs.createForm || document.getElementById('liqCreateForm');
            if (!form || !data) return;
            const purpose = form.querySelector('[name=liquidation_report_purpose]');
            const amount = form.querySelector('[name=liquidation_report_amount_advance]');
            if (purpose && data.purpose) purpose.value = data.purpose;
            if (amount && data.amount != null) {
                amount.value = window.PrismInputLimits && window.PrismInputLimits.formatMoney
                    ? window.PrismInputLimits.formatMoney(data.amount)
                    : data.amount;
            }
            const rows = data.items || [];
            const rowsRoot = form.querySelector('[data-doc-rows]');
            const rowTotal = (rowsRoot && window.docRows)
                ? window.docRows.fit(rowsRoot, Math.max(1, rows.length)).length
                : Math.max(1, rows.length);
            const fmtVal = (v) => (v != null && v !== '' && window.PrismInputLimits && window.PrismInputLimits.formatMoney)
                ? window.PrismInputLimits.formatMoney(v)
                : (v ?? '');
            for (let i = 0; i < rowTotal; i++) {
                const item = rows[i] || {};
                const p = form.querySelector('[name=\'items[' + i + '][particulars]\']');
                const a = form.querySelector('[name=\'items[' + i + '][amount]\']');
                const aa = form.querySelector('[name=\'items[' + i + '][actual_amount]\']');
                const at = form.querySelector('[name=\'items[' + i + '][actual_total]\']');
                const ref = form.querySelector('[name=\'items[' + i + '][ref_no]\']');
                if (p) p.value = item.particulars ?? '';
                if (a) a.value = fmtVal(item.amount);
                if (aa) aa.value = fmtVal(item.actual_amount);
                if (at) at.value = fmtVal(item.actual_total);
                if (ref) ref.value = item.ref_no ?? '';
            }
            this.selectedRrOrSummary = data.or_summary || null;
            if (window.PrismInputLimits && typeof window.PrismInputLimits.scan === 'function') {
                window.PrismInputLimits.scan(form);
            }
            if (window.liqRecalcTotals) {
                window.liqRecalcTotals(form);
            }
            const hintEl = form.querySelector('[data-cash-returned-hint]');
            const cashInput = form.querySelector('[data-cash-returned-input]');
            const hint = data.cash_returned_hint || '';
            const unusedCash = Number(data.shortfall_amount || (data.or_summary && data.or_summary.change_amount) || 0);
            if (hintEl) {
                hintEl.textContent = hint;
                hintEl.classList.toggle('hidden', !hint);
            }
            if (cashInput) {
                cashInput.value = unusedCash > 0 ? fmtVal(unusedCash) : '';
                if (hint) {
                    cashInput.placeholder = hint;
                    cashInput.classList.add('ring-1', 'ring-amber-400');
                } else {
                    cashInput.placeholder = '0.00';
                    cashInput.classList.remove('ring-1', 'ring-amber-400');
                }
            }
            if (window.liqRecalcTotals) {
                window.liqRecalcTotals(form);
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
    @keydown.escape.window="if (document.getElementById('purConfirmOverlay')) return; if (createOpen) { guardClose('create'); } else if (editOpen) { guardClose('edit'); } else { closeAll(); }"
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

        <div class="flex flex-wrap items-center gap-2">
            @include('layouts.partials.export-pdf-button', [
                'exportUrl' => route(($pp ?? 'purchaser').'.liq.export-pdf', array_filter([
                    'view' => !empty($archiveView) ? 'archive' : null,
                    'search' => request('search') ?: null,
                    'status' => request('status') ?: null,
                ])),
            ])
            @unless($archiveView)
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
            @endunless
        </div>
    </div>

    @php
        $overdueLiquidations = $overdueLiquidations ?? [];
        $overduePendingRrs = $overduePendingRrs ?? [];
        $overdueIds = $overdueIds ?? [];
        $overdueTotal = count($overdueLiquidations) + count($overduePendingRrs);
    @endphp

    @if(! $archiveView && $overdueTotal > 0)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950 shadow-sm">
            <div class="flex flex-wrap items-start gap-3">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                    <i data-lucide="alarm-clock" class="h-4 w-4"></i>
                </div>
                <div class="min-w-0 flex-1 space-y-1.5">
                    <p class="font-semibold text-rose-900">
                        {{ $overdueTotal }} liquidation{{ $overdueTotal === 1 ? '' : 's' }} overdue
                        <span class="font-normal text-rose-800/90">— create an LR within {{ \App\Support\LiquidationDeadline::DAYS }} days of RR completion (in preparation drafts are not overdue yet).</span>
                    </p>
                    <ul class="space-y-1 text-[13px] text-rose-900/90">
                        @foreach($overdueLiquidations as $od)
                            @php
                                $odLabel = filled($od->liquidation_report_form_number ?? null)
                                    ? (string) $od->liquidation_report_form_number
                                    : '-';
                                $odDays = (int) ($od->liquidation_report_days_lapse ?? 0);
                            @endphp
                            <li class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $odLabel }}</span>
                                <span class="text-rose-800/80">{{ $odDays }} day{{ $odDays === 1 ? '' : 's' }} late</span>
                                <button type="button" class="font-semibold text-[#0025cc] underline-offset-2 hover:underline" @click="openEdit({{ (int) $od->liquidation_report_id }})">Open</button>
                            </li>
                        @endforeach
                        @foreach($overduePendingRrs as $odRr)
                            @php
                                $odRrLabel = $odRr->receiving_report_form_number ?: ('RR #'.$odRr->receiving_report_id);
                                $odRrDays = (int) ($odRr->days_lapsed ?? 0);
                            @endphp
                            <li class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $odRrLabel }}</span>
                                <span class="text-rose-800/80">needs LR · {{ $odRrDays }} day{{ $odRrDays === 1 ? '' : 's' }} late</span>
                                <a href="{{ route(($pp ?? 'purchaser').'.liq.index', ['selected_rr' => $odRr->receiving_report_id]) }}" class="font-semibold text-[#0025cc] underline-offset-2 hover:underline">Create Liquidation</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

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

                <form data-auto-filter
                    data-list-panel="#purchaser-liq-list-panel"
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
                        @foreach(['Draft','Overdue','Submitted','Minor Revision','Approved','Rejected'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ \App\Support\ProcurementStatus::label($s, 'liq') }}</option>
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

        <div id="purchaser-liq-list-panel" data-list-panel-root class="relative">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">LR No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RR</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Employee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($reports as $liq)
                        @php
                            $editable = in_array($liq->liquidation_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
                            $liqIsOverdue = in_array((int) $liq->liquidation_report_id, $overdueIds, true)
                                || \App\Support\LiquidationDeadline::isCreationOverdue($liq);
                        @endphp
                        <tr class="transition hover:bg-gray-50/70 {{ $liqIsOverdue ? 'bg-rose-50/40' : '' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="receipt" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-gray-900">{{ $liq->liquidation_report_form_number ?: '-' }}</p>
                                            @include('partials.ris-urgency-badge', [
                                                'urgent' => \App\Support\DocumentUrgency::isUrgent('LIQ', $liq),
                                                'size' => 'sm',
                                                'title' => 'Inherited from linked RIS',
                                            ])
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @include('partials.procurement-request-no', ['requestNo' => $liq->liquidation_report_request_no ?? null, 'inline' => true, 'showLabel' => false])
                                @if(blank($liq->liquidation_report_request_no ?? null))
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $liq->receiving_report_form_number ?? '-' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $liq->liquidation_report_employee_name ?: '-' }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $liqRawStatus = trim((string) ($liq->liquidation_report_status ?? ''));
                                    $liqBadge = match (true) {
                                        $liqIsOverdue => [
                                            'pill' => 'Overdue',
                                            'sub' => 'Past 15-day deadline',
                                            'bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca', 'dot' => '#dc2626',
                                        ],
                                        $liqRawStatus === 'Draft' => [
                                            'pill' => 'Draft',
                                            'sub' => 'In preparation',
                                            'bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1', 'dot' => '#64748b',
                                        ],
                                        in_array($liqRawStatus, ['Submitted', 'Under Review', 'Resubmitted'], true) => [
                                            'pill' => 'With Accounting',
                                            'sub' => 'Awaiting verification',
                                            'bg' => '#f0f9ff', 'fg' => '#0369a1', 'border' => '#bae6fd', 'dot' => '#0284c7',
                                        ],
                                        $liqRawStatus === 'Pending Admin Approval' => [
                                            'pill' => 'With School Admin',
                                            'sub' => 'Awaiting approval',
                                            'bg' => '#eef2ff', 'fg' => '#4338ca', 'border' => '#c7d2fe', 'dot' => '#4f46e5',
                                        ],
                                        in_array($liqRawStatus, ['Minor Revision', 'Returned', 'For revision'], true) => [
                                            'pill' => 'For Revision',
                                            'sub' => 'Returned by Accounting',
                                            'bg' => '#fff7ed', 'fg' => '#c2410c', 'border' => '#fed7aa', 'dot' => '#ea580c',
                                        ],
                                        $liqRawStatus === 'Approved' => [
                                            'pill' => 'Completed',
                                            'sub' => 'Liquidation approved',
                                            'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0', 'dot' => '#059669',
                                        ],
                                        $liqRawStatus === 'Rejected' => [
                                            'pill' => 'Rejected',
                                            'sub' => 'Not approved',
                                            'bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca', 'dot' => '#dc2626',
                                        ],
                                        default => [
                                            'pill' => \App\Support\ProcurementStatus::label($liqRawStatus, 'liq'),
                                            'sub' => '',
                                            'bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1', 'dot' => '#64748b',
                                        ],
                                    };
                                @endphp
                                <div class="inline-flex flex-col items-start">
                                    <span
                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                        style="background:{{ $liqBadge['bg'] }};color:{{ $liqBadge['fg'] }};border-color:{{ $liqBadge['border'] }};"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $liqBadge['dot'] }};"></span>
                                        <span>{{ $liqBadge['pill'] }}</span>
                                    </span>
                                    @if($liqBadge['sub'] !== '')
                                        <span class="mt-1 whitespace-nowrap text-[11px] font-medium leading-tight text-slate-500">
                                            {{ $liqBadge['sub'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" @click="openView({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="View" aria-label="View"><i data-lucide="eye" class="h-4 w-4"></i></button>
                                    <button
                                        type="button"
                                        data-share-doc-type="liq"
                                        data-share-doc-id="{{ (int) $liq->liquidation_report_id }}"
                                        data-share-doc-label="{{ \App\Support\RequestExplorer::shareUiLabel('liq', $liq->liquidation_report_form_number ?? null) }}"
                                        class="js-share-document inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="Share to Messages"
                                        aria-label="Share to Messages"
                                    >
                                        <i data-lucide="share-2" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button" @click="printLiq({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $liq->liquidation_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]" title="Edit" aria-label="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.liq.submit', $liq->liquidation_report_id) }}"
                                            class="inline"
                                            data-pur-confirm="{{ $liq->liquidation_report_status === 'Minor Revision' ? 'Resubmit this Liquidation Report to Accounting?' : 'Submit this Liquidation Report to Accounting?' }}"
                                            data-pur-confirm-title="{{ $liq->liquidation_report_status === 'Minor Revision' ? 'Resubmit Liquidation' : 'Submit Liquidation' }}"
                                            data-pur-confirm-ok="{{ $liq->liquidation_report_status === 'Minor Revision' ? 'Resubmit' : 'Submit' }}"
                                            data-pur-confirm-reviewer-role="Accounting"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg shadow-sm transition"
                                                style="background:#059669;color:#ffffff;"
                                                onmouseenter="this.style.background='#047857'"
                                                onmouseleave="this.style.background='#059669'"
                                                title="{{ $liq->liquidation_report_status === 'Minor Revision' ? 'Resubmit to Accounting' : 'Submit to Accounting' }}"
                                                aria-label="{{ $liq->liquidation_report_status === 'Minor Revision' ? 'Resubmit to Accounting' : 'Submit to Accounting' }}"
                                            >
                                                <i data-lucide="send" class="h-4 w-4"></i>
                                            </button>
                                        </form>
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
                            <td colspan="7" class="px-6 py-16 text-center">
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
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="printer" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="liq-empty-title" class="text-lg font-semibold text-gray-950">Print Empty LR</h3>
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
            x-on:click.self="guardClose('create')"
            class="flex min-h-full w-full justify-center"
        >
            <div
                @click.stop
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="liq-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Liquidation Report</h3>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button type="button" @click="guardClose('create')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <form id="liqCreateForm" method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.store') }}" enctype="multipart/form-data" x-ref="createForm">
                    @csrf
                    <input type="hidden" name="save_action" value="draft">
                    <input type="hidden" name="assigned_reviewer_id" value="{{ $preferredAccountingId ?? '' }}">
                    <div class="bg-slate-100 p-3 md:p-5">
                        @if($eligibleRrs->isEmpty())
                            <div class="mx-auto mb-4 w-full max-w-[1095px] rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                No completed Receiving Report is available. You can still fill out and save this Liquidation for later (in preparation), then link a completed RR later before submitting.
                            </div>
                        @else
                            <div class="mx-auto mb-4 w-full max-w-[1095px]">
                                <label class="text-xs text-gray-500">Completed Receiving Report <span class="text-red-500">*</span> <span class="font-normal text-gray-400">(optional while in preparation)</span></label>
                                <select name="liquidation_report_receiving_report_id" x-on:change="applyRrPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border px-3 text-sm">
                                    <option value="">Select RR</option>
                                    @foreach($eligibleRrs as $rr)
                                        <option value="{{ $rr->receiving_report_id }}" {{ old('liquidation_report_receiving_report_id', $selectedRrId ?? '') == $rr->receiving_report_id ? 'selected' : '' }}>
                                            {{ $rr->receiving_report_form_number }} @if($rr->request_check_form_number)· {{ $rr->request_check_form_number }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div
                                    x-show="selectedRrOrSummary"
                                    x-cloak
                                    class="mt-2.5 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-indigo-200/80 bg-indigo-50/70 px-3.5 py-2.5 text-xs text-slate-700"
                                >
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="font-semibold text-indigo-900">RR Official Receipt:</span>
                                        <span x-show="selectedRrOrSummary && selectedRrOrSummary.or_number">
                                            OR #<strong class="text-slate-900" x-text="selectedRrOrSummary ? selectedRrOrSummary.or_number : ''"></strong>
                                        </span>
                                        <span x-show="selectedRrOrSummary && selectedRrOrSummary.check_amount !== null">
                                            Funds: <strong class="tabular-nums text-slate-900" x-text="selectedRrOrSummary ? fmtPeso(selectedRrOrSummary.check_amount) : ''"></strong>
                                        </span>
                                        <span x-show="selectedRrOrSummary && selectedRrOrSummary.or_amount !== null">
                                            OR Spend: <strong class="tabular-nums text-slate-900" x-text="selectedRrOrSummary ? fmtPeso(selectedRrOrSummary.or_amount) : ''"></strong>
                                        </span>
                                        <span x-show="selectedRrOrSummary && selectedRrOrSummary.change_amount !== null">
                                            Change: <strong class="tabular-nums text-amber-800" x-text="selectedRrOrSummary ? fmtPeso(selectedRrOrSummary.change_amount) : ''"></strong>
                                        </span>
                                    </div>
                                    <template x-if="selectedRrOrSummary && selectedRrOrSummary.attachment_url">
                                        <a
                                            :href="selectedRrOrSummary.attachment_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-[#0025cc] transition hover:bg-indigo-50"
                                        >
                                            <i data-lucide="file-image" class="h-3.5 w-3.5"></i>
                                            View OR
                                        </a>
                                    </template>
                                </div>
                            </div>
                        @endif
                        @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => null, 'rows' => collect(), 'signKey' => 'liq-create'])
                        <div id="purSigSlot-liq-create" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                        @include('partials.single-file-upload', [
                            'name' => 'attachments[]',
                            'refKey' => 'createLiqAttachment',
                            'class' => 'mx-auto mt-4 w-full max-w-[1095px] overflow-hidden rounded-xl border border-slate-200 bg-white',
                            'title' => 'Supporting Document',
                            'hint' => 'Optional · 1 file · PDF, JPG, PNG · max 5MB',
                            'uploadLabel' => 'Upload file',
                            'helpLine' => 'One file only · PDF, JPG, or PNG',
                        ])
                    </div>
                    <div class="flex flex-wrap justify-end gap-2.5 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="guardClose('create')" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            onclick="this.form.save_action.value='draft'"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-[13px] font-semibold text-gray-700 transition hover:bg-gray-100"
                        >
                            <i data-lucide="bookmark" class="h-4 w-4"></i>
                            {{ \App\Support\ProcurementDraftLabel::SAVE }}
                        </button>
                        <button
                            type="button"
                            @click="openCreateSubmitConfirm()"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                        >
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Submit to Accounting
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>

    {{-- SUBMIT TO ACCOUNTING CONFIRMATION MODAL --}}
    <template x-teleport="body">
        <div
            x-show="createSubmitConfirmOpen"
            x-cloak
            class="fixed inset-0 flex items-center justify-center p-4"
            style="z-index: 99999;"
            role="dialog"
            aria-modal="true"
            aria-labelledby="liqSubmitConfirmTitle"
        >
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-[1px]" @click="createSubmitConfirmOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" @click.stop>
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" style="background:#eff6ff;border-color:#bfdbfe;color:#0025cc;">
                        <i data-lucide="send" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="liqSubmitConfirmTitle" class="text-base font-bold text-slate-900">Submit to Accounting?</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            This Liquidation Report will be sent to <strong class="text-slate-900">Accounting</strong> (<strong class="text-slate-900" x-text="selectedAccountingName()"></strong>) for verification.
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Destination</span>
                        <span class="font-semibold text-slate-900">Accounting Office</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Recipient</span>
                        <template x-if="accountingReviewers.length > 1">
                            <select
                                x-model="createSubmitReviewerId"
                                class="h-7 max-w-[200px] rounded-md border border-slate-300 bg-white px-2 text-xs font-semibold text-slate-900 outline-none"
                            >
                                <template x-for="rev in accountingReviewers" :key="rev.id">
                                    <option :value="String(rev.id)" x-text="rev.name"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="accountingReviewers.length <= 1">
                            <span class="font-semibold text-slate-900" x-text="selectedAccountingName()"></span>
                        </template>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200/80 pt-2">
                        <span class="text-slate-500">Receiving Report</span>
                        <span class="max-w-[220px] truncate font-medium text-slate-800" x-text="createSubmitSummary.rrLabel"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Employee</span>
                        <span class="max-w-[220px] truncate font-medium text-slate-800" x-text="createSubmitSummary.employee"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Amount Advanced</span>
                        <span class="font-semibold tabular-nums text-slate-900" x-text="createSubmitSummary.amount"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Date Submitted</span>
                        <span class="font-medium text-slate-800" x-text="createSubmitSummary.dateSubmitted"></span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="createSubmitConfirmOpen = false"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmCreateSubmit()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="send" class="h-3.5 w-3.5"></i>
                        Confirm &amp; Submit
                    </button>
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
                    :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                    <i data-lucide="receipt" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 id="liq-view-title-{{ $liq->liquidation_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">{{ $liq->liquidation_report_form_number }}</h3>
                                        @include('partials.ris-urgency-badge', [
                                            'urgent' => \App\Support\DocumentUrgency::isUrgent('LIQ', $liq),
                                            'title' => 'Inherited from linked RIS',
                                        ])
                                    </div>
                                    <p class="mt-0.5 text-sm text-gray-500">RR: {{ $liq->receiving_report_form_number ?? '-' }}</p>
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
                    @if(!empty($liq->liquidation_report_revision_notes) && in_array($liq->liquidation_report_status, ['Minor Revision', 'Rejected'], true))
                        <div class="mx-5 mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 md:mx-6">
                            <p><span class="font-medium">Revision requested by Accounting:</span> {{ $liq->liquidation_report_revision_notes }}</p>
                            @include('partials.ris-revision-images', [
                                'revision' => \App\Support\DocumentRevisionNotes::latest('LIQ', $liq->liquidation_report_id),
                                'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                'size' => 'sm',
                            ])
                        </div>
                    @endif
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
                    x-on:click.self="guardClose('edit')"
                    class="flex min-h-full w-full justify-center"
                >
                    <div
                        @click.stop
                        class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                        :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                    >
                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.liq.update', $liq->liquidation_report_id) }}" enctype="multipart/form-data"
                            data-pur-edit-form="liq"
                            x-bind:data-open="editOpen && selectedLiq === {{ $liq->liquidation_report_id }} ? '1' : '0'"
                            x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                        >
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
                                    <button type="button" @click="guardClose('edit')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="bg-slate-100 p-3 md:p-5">
                                @if(!empty($liq->liquidation_report_revision_notes) && $liq->liquidation_report_status === 'Minor Revision')
                                    <div class="mx-auto mb-4 w-full max-w-[1095px] rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                        <p><span class="font-medium">Changes requested:</span> {{ $liq->liquidation_report_revision_notes }}</p>
                                        @include('partials.ris-revision-images', [
                                            'revision' => \App\Support\DocumentRevisionNotes::latest('LIQ', $liq->liquidation_report_id),
                                            'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                            'size' => 'sm',
                                        ])
                                    </div>
                                @endif
                                @include('partials.liquidation-report-paper', ['editable' => true, 'liq' => $liq, 'rows' => $liqItems, 'signKey' => 'liq-'.$liq->liquidation_report_id])
                                <div id="purSigSlot-liq-{{ $liq->liquidation_report_id }}" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                                @include('partials.single-file-upload', [
                                    'name' => 'attachments[]',
                                    'refKey' => 'editLiqAttachment'.$liq->liquidation_report_id,
                                    'class' => 'mx-auto mt-4 w-full max-w-[1095px] overflow-hidden rounded-xl border border-slate-200 bg-white',
                                    'title' => 'Supporting Document',
                                    'hint' => 'Optional · 1 file · PDF, JPG, PNG · max 5MB',
                                    'uploadLabel' => 'Upload file',
                                    'helpLine' => 'One file only · remove the current file first to replace',
                                    'existing' => $liqFiles->map(fn ($file) => [
                                        'id' => $file->liquidation_attachment_id,
                                        'name' => $file->liquidation_attachment_original_name,
                                    ]),
                                ])
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                <p class="mr-auto max-w-md text-xs text-gray-500">
                                    @if($liq->liquidation_report_status === 'Minor Revision')
                                        Save your corrections here, then use <strong>? ? Resubmit to Accounting</strong> in the list.
                                    @else
                                        Save your changes here (including signature), then use <strong>? ? Submit to Accounting</strong> in the list.
                                    @endif
                                </p>
                                <button
                                    type="button"
                                    @click="guardClose('edit')"
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
    @page { size: A4 portrait; margin: 8mm; }
    .liq-print-active { background: #fff !important; }
}
</style>
<script>
(function () {
    var WINDOW_DAYS = {{ (int) \App\Support\LiquidationDeadline::DAYS }};

    function parseYmd(value) {
        if (!value) return null;
        var parts = String(value).split('-');
        if (parts.length !== 3) return null;
        var y = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10) - 1;
        var d = parseInt(parts[2], 10);
        if (!y || m < 0 || !d) return null;
        return new Date(y, m, d);
    }

    function formatYmd(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function daysBetween(from, to) {
        var ms = to.getTime() - from.getTime();
        return Math.max(0, Math.floor(ms / 86400000));
    }

    function parseMoney(val) {
        var s = String(val == null ? '' : val).replace(/,/g, '').replace(/[^\d.-]/g, '').trim();
        if (s === '' || s === '-' || s === '.') return null;
        var n = Number(s);
        return isFinite(n) ? n : null;
    }

    function fmtPeso(n) {
        if (n === null || !isFinite(n)) return '';
        return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.liqRecalcTotals = function (root) {
        if (!root || !root.querySelectorAll) return;
        var rows = root.querySelectorAll('tbody[data-doc-rows-body] tr[data-doc-row]');
        var sumBudget = 0;
        var sumActual = 0;
        var hasBudget = false;
        var hasActual = false;

        rows.forEach(function (tr) {
            var amtEl = tr.querySelector('input[name$="[amount]"]');
            var actAmtEl = tr.querySelector('input[name$="[actual_amount]"]');
            var actTotEl = tr.querySelector('input[name$="[actual_total]"]');
            var varEl = tr.querySelector('[data-liq-row-variance]');

            var budget = amtEl ? parseMoney(amtEl.value) : null;
            var actual = actTotEl ? parseMoney(actTotEl.value) : null;
            if (actual === null && actAmtEl) actual = parseMoney(actAmtEl.value);

            if (budget !== null) { sumBudget += budget; hasBudget = true; }
            if (actual !== null) { sumActual += actual; hasActual = true; }

            if (varEl) {
                if (budget !== null || actual !== null) {
                    var diff = Math.round(((budget || 0) - (actual || 0)) * 100) / 100;
                    varEl.textContent = fmtPeso(diff);
                    varEl.style.color = diff > 0 ? '#b45309' : (diff < 0 ? '#dc2626' : '#334155');
                    varEl.style.fontWeight = diff !== 0 ? '600' : '400';
                } else {
                    varEl.textContent = '';
                    varEl.style.color = '';
                    varEl.style.fontWeight = '';
                }
            }
        });

        var headerAdvEl = root.querySelector('input[name="liquidation_report_amount_advance"]');
        var headerAdv = headerAdvEl ? parseMoney(headerAdvEl.value) : null;
        var effectiveAdv = headerAdv !== null ? headerAdv : (hasBudget ? sumBudget : null);
        var effectiveAct = hasActual ? Math.round(sumActual * 100) / 100 : null;
        var balance = (effectiveAdv !== null || effectiveAct !== null)
            ? Math.round(((effectiveAdv || 0) - (effectiveAct || 0)) * 100) / 100
            : null;

        var totAdv = root.querySelector('[data-liq-total-advance]');
        var totAct = root.querySelector('[data-liq-total-actual]');
        var sumAdv = root.querySelector('[data-liq-summary-advance]');
        var sumAct = root.querySelector('[data-liq-summary-actual]');
        var sumBal = root.querySelector('[data-liq-summary-balance]');

        if (totAdv) totAdv.textContent = hasBudget ? fmtPeso(Math.round(sumBudget * 100) / 100) : (effectiveAdv !== null ? fmtPeso(effectiveAdv) : '');
        if (totAct) totAct.textContent = effectiveAct !== null ? fmtPeso(effectiveAct) : '';
        if (sumAdv) sumAdv.textContent = effectiveAdv !== null ? fmtPeso(effectiveAdv) : '';
        if (sumAct) sumAct.textContent = effectiveAct !== null ? fmtPeso(effectiveAct) : '';
        if (sumBal) {
            sumBal.textContent = balance !== null ? fmtPeso(balance) : '';
            sumBal.style.color = balance !== null && balance > 0 ? '#b45309' : (balance !== null && balance < 0 ? '#dc2626' : '#047857');
        }

        var cashInput = root.querySelector('[data-cash-returned-input]');
        var cashErr = root.querySelector('[data-cash-returned-error]');
        if (cashInput) {
            var entered = parseMoney(cashInput.value);
            var maxUnused = balance !== null ? Math.max(0, balance) : 0;
            var isOver = entered !== null && entered > (maxUnused + 0.001);
            if (isOver) {
                cashInput.style.borderColor = '#dc2626';
                cashInput.style.backgroundColor = '#fef2f2';
                cashInput.style.color = '#b91c1c';
                cashInput.style.boxShadow = '0 0 0 1px #dc2626';
                if (cashErr) {
                    var excess = Math.round((entered - maxUnused) * 100) / 100;
                    cashErr.textContent = 'Exceeds unused cash balance by ' + fmtPeso(excess);
                    cashErr.classList.remove('hidden');
                }
            } else {
                cashInput.style.borderColor = '';
                cashInput.style.backgroundColor = '';
                cashInput.style.color = '';
                cashInput.style.boxShadow = '';
                if (cashErr) {
                    cashErr.textContent = '';
                    cashErr.classList.add('hidden');
                }
            }
        }
    };

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!el || !el.name) return;
        if (
            el.name === 'liquidation_report_amount_advance'
            || el.name === 'liquidation_report_cash_returned_or_no'
            || /^items\[\d+\]\[(amount|actual_amount|actual_total)\]$/.test(el.name)
        ) {
            var form = el.closest('form');
            if (form) window.liqRecalcTotals(form);
        }
    });

    function syncForm(root) {
        if (!root) return;
        var activity = root.querySelector('[data-liq-activity-end]');
        var released = root.querySelector('[data-liq-date-released]');
        var deadline = root.querySelector('[data-liq-deadline]');
        var daysOut = root.querySelector('[data-liq-days-lapsed]');
        if (!deadline) return;

        var anchor = parseYmd(activity && activity.value) || parseYmd(released && released.value);
        if (!anchor) {
            if (deadline.tagName === 'INPUT') deadline.value = '';
            if (daysOut) {
                if (daysOut.tagName === 'INPUT') daysOut.value = '';
                else daysOut.textContent = '';
            }
            return;
        }

        var due = new Date(anchor.getFullYear(), anchor.getMonth(), anchor.getDate());
        due.setDate(due.getDate() + WINDOW_DAYS);
        if (deadline.tagName === 'INPUT') {
            deadline.value = formatYmd(due);
        } else {
            deadline.textContent = (due.getMonth() + 1) + '/' + due.getDate() + '/' + due.getFullYear();
        }

        if (daysOut) {
            var today = new Date();
            today = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            var lapsedText = today > due ? String(daysBetween(due, today)) : '0';
            if (daysOut.tagName === 'INPUT') daysOut.value = lapsedText;
            else daysOut.textContent = lapsedText;
        }
    }

    function bindRoot(root) {
        if (!root || root.dataset.liqDeadlineBound === '1') return;
        root.dataset.liqDeadlineBound = '1';
        root.addEventListener('change', function (event) {
            var t = event.target;
            if (!t || !t.matches) return;
            if (t.matches('[data-liq-activity-end], [data-liq-date-released]')) {
                syncForm(root);
            }
        });
        syncForm(root);
    }

    function scan() {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.querySelector('[data-liq-deadline]')) bindRoot(form);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }
    document.addEventListener('alpine:initialized', scan);
    window.addEventListener('liq-deadline-resync', scan);
})();
</script>
@include('partials.ris-revision-image-viewer')
@endsection
