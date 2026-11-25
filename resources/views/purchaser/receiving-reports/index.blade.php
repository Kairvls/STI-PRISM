@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Receiving Reports')
@section('page-subtitle', 'After check pickup. OR is for Cash Advance only.')

@section('content')

<script type="application/json" id="rr-rfc-prefill">{!! json_encode($rfcPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('receiving_report_funding_key')) || !empty($selectedFundingKey) || !empty($selectedRfcId) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewRrId) ? 'true' : 'false' }},
        editOpen: false,
        emptyOpen: false,
        modalFullscreen: false,
        selectedRr: {{ !empty($viewRrId) ? (int) $viewRrId : 'null' }},
        selectedFundingKey: @js((string) old('receiving_report_funding_key', $selectedFundingKey ?? '')),
        rfcPrefill: JSON.parse(document.getElementById('rr-rfc-prefill').textContent || '{}'),
        cashAdvancePath: 'cash_advance',

        isCashAdvance(fundingKey) {
            const data = this.rfcPrefill[String(fundingKey || '')];
            return !!(data && data.payment_path === this.cashAdvancePath);
        },

        referencesFor(fundingKey) {
            const data = this.rfcPrefill[String(fundingKey || '')];
            return (data && Array.isArray(data.references)) ? data.references : [];
        },

        fundingLabel(fundingKey) {
            const data = this.rfcPrefill[String(fundingKey || '')];
            if (!data) return '';
            if (data.payment_path === this.cashAdvancePath) return 'Cash Advance RR';
            if (data.payment_path === 'cash_installment') return 'Cash Installment RR';
            if (data.payment_path === 'direct_payment' || data.payment_path === 'request_for_check') return 'Direct Payment RR';
            return data.payment_label ? (data.payment_label + ' RR') : 'Receiving Report';
        },

        init() {
            this.$watch('viewOpen', (open) => {
                if (!open && window.purDialog) window.purDialog.clearDeepLink(['view_rr', 'edit_rr']);
            });
            this.$watch('editOpen', (open) => {
                if (!open && window.purDialog) window.purDialog.clearDeepLink(['view_rr', 'edit_rr']);
            });
            if (this.selectedFundingKey) {
                this.$nextTick(() => this.applyRfcPrefill(this.selectedFundingKey));
            }
        },
        openView(id) { this.selectedRr = id; this.viewOpen = true; this.editOpen = false; this.modalFullscreen = false; },
        openEdit(id) {
            this.selectedRr = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('rr-' + id, 'Received by signature');
        },
        openCreate() {
            this.createOpen = true;
            this.modalFullscreen = false;
            this.bindDocSig('rr-create', 'Received by signature');
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
        closeAll() { this.createOpen = false; this.viewOpen = false; this.editOpen = false; this.emptyOpen = false; this.modalFullscreen = false; this.selectedRr = null; },
        guardClose(kind) {
            const form = kind === 'create'
                ? this.$refs.createForm
                : document.querySelector('[data-pur-edit-form=rr][data-open=1]');
            const finish = () => {
                if (kind === 'create') this.createOpen = false;
                else this.editOpen = false;
                this.modalFullscreen = false;
            };
            if (window.purGuardFormClose) { window.purGuardFormClose(form, finish); return; }
            finish();
        },

        rrSubmitConfirmOpen: false,
        rrSubmitConfirmForm: null,
        rrSubmitConfirmSummary: { title: 'Submit for Second Count?', receivedFrom: '—', itemCount: '—', destination: 'Receiving Officer (Second Count)', okLabel: 'Confirm & Submit' },

        openRrSubmitConfirm(btn, isResubmit = false) {
            const form = btn ? btn.form : this.$refs.createForm;
            if (!form) return;
            const fromVal = (form.querySelector('[name=receiving_report_received_from]')?.value || '').trim();
            const articles = Array.from(form.querySelectorAll('input[name]'))
                .filter(el => String(el.getAttribute('name') || '').endsWith('[article]'))
                .map(el => String(el.value || '').trim())
                .filter(Boolean);
            this.rrSubmitConfirmForm = form;
            this.rrSubmitConfirmSummary = {
                title: isResubmit ? 'Resubmit for Second Count?' : 'Submit for Second Count?',
                receivedFrom: fromVal || '—',
                itemCount: articles.length ? (articles.length + (articles.length === 1 ? ' item' : ' items')) : '—',
                destination: 'Receiving Officer (Second Count)',
                okLabel: isResubmit ? 'Confirm & Resubmit' : 'Confirm & Submit',
            };
            this.rrSubmitConfirmOpen = true;
        },

        confirmRrSubmit() {
            const form = this.rrSubmitConfirmForm;
            this.rrSubmitConfirmOpen = false;
            if (!form) return;
            const saveInput = form.querySelector('input[name=save_action]');
            if (saveInput) saveInput.value = 'submit';
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        },

        applyRfcPrefill(fundingKey) {
            this.selectedFundingKey = String(fundingKey || '');
            const data = this.rfcPrefill[String(fundingKey)];
            if (data && data.check_amount != null) {
                window.dispatchEvent(new CustomEvent('rr-check-amount', { detail: { amount: Number(data.check_amount) } }));
            }
            const form = this.$refs.createForm;
            if (!form || !data) return;
            const from = form.querySelector('[name=receiving_report_received_from]');
            const address = form.querySelector('[name=receiving_report_supplier_address_override]');
            const checkAmt = form.querySelector('[name=receiving_report_check_amount]');
            if (from && data.received_from) from.value = data.received_from;
            if (address && data.address) address.value = data.address;
            if (checkAmt && data.check_amount != null) {
                checkAmt.value = data.check_amount;
                checkAmt.dispatchEvent(new Event('input', { bubbles: true }));
            }
            const rows = data.items || [];
            const rowsRoot = form.querySelector('[data-doc-rows]');
            const rowTotal = (rowsRoot && window.docRows)
                ? window.docRows.fit(rowsRoot, Math.max(1, rows.length)).length
                : Math.max(1, rows.length);
            for (let i = 0; i < rowTotal; i++) {
                const item = rows[i] || {};
                const qty = form.querySelector('[name=\'items[' + i + '][quantity]\']');
                const ordered = form.querySelector('[name=\'items[' + i + '][ordered_qty]\']');
                const damaged = form.querySelector('[name=\'items[' + i + '][damaged_qty]\']');
                const unit = form.querySelector('[name=\'items[' + i + '][unit]\']');
                const article = form.querySelector('[name=\'items[' + i + '][article]\']');
                const unitPrice = form.querySelector('[name=\'items[' + i + '][unit_price]\']');
                const supplier = form.querySelector('[data-rr-supplier-select][name=\'items[' + i + '][supplier_id]\']')
                    || form.querySelector('[name=\'items[' + i + '][supplier_id]\']');
                const supplierHidden = form.querySelector('[data-rr-supplier-hidden][name=\'items[' + i + '][supplier_id]\']');
                const supplierNameInput = form.querySelector('[name=\'items[' + i + '][supplier_name]\']');
                if (qty) qty.value = item.quantity ?? '';
                if (ordered) {
                    const hasOrdered = item.ordered_qty != null && item.ordered_qty !== '';
                    ordered.value = hasOrdered ? item.ordered_qty : '';
                    if (hasOrdered && window.rrLockOrderedCell) {
                        window.rrLockOrderedCell(ordered);
                    } else if (!hasOrdered && window.rrUnlockOrderedCell) {
                        window.rrUnlockOrderedCell(ordered);
                    }
                }
                if (damaged) damaged.value = '';
                if (unit) {
                    const hasUnit = item.unit != null && String(item.unit).trim() !== '';
                    unit.value = hasUnit ? item.unit : '';
                    unit.dispatchEvent(new Event('input', { bubbles: true }));
                    if (hasUnit && window.rrLockUnitCell) {
                        window.rrLockUnitCell(unit);
                    } else if (!hasUnit && window.rrUnlockUnitCell) {
                        window.rrUnlockUnitCell(unit);
                    }
                }
                if (article) article.value = item.article ?? '';
                const equipment = form.querySelector('[name=\'items[' + i + '][equipment_id]\']');
                if (equipment) equipment.value = item.equipment_id ? String(item.equipment_id) : '';
                if (unitPrice) unitPrice.value = item.unit_price ?? '';
                if (supplier && item.supplier_id) supplier.value = item.supplier_id;
                if (supplierHidden && item.supplier_id) supplierHidden.value = item.supplier_id;
                if (supplierNameInput && item.supplier_name) supplierNameInput.value = item.supplier_name;
            }
            if (window.rrSetSupplierEditable) {
                window.rrSetSupplierEditable(form, this.isCashAdvance(this.selectedFundingKey));
            }
            if (window.rrRefreshConditions) window.rrRefreshConditions(form);
        },

        printRr(id) {
            const sheetId = 'rr-print-' + id;
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'rr-print-active');
                return;
            }
            const sheet = document.getElementById(sheetId);
            if (sheet) {
                document.querySelectorAll('.rr-print-sheet').forEach(function (el) {
                    el.classList.remove('rr-print-active');
                });
                sheet.classList.add('rr-print-active');
            }
            window.print();
        }
    }"
    x-init="
        if (@js((int) ($editRrId ?? 0)) > 0) {
            $nextTick(() => openEdit(@js((int) ($editRrId ?? 0))));
        }
        if (createOpen) {
            $nextTick(() => {
                bindDocSig('rr-create', 'Received by signature');
                if (@js((string) ($selectedFundingKey ?? ''))) applyRfcPrefill(@js((string) ($selectedFundingKey ?? '')));
            });
        }
    "
    @keydown.escape.window="if (document.getElementById('purConfirmOverlay')) return; if (createOpen) { guardClose('create'); } else if (editOpen) { guardClose('edit'); } else { closeAll(); }"
    class="space-y-6"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="RR list view">
            <a
                href="{{ route(($pp ?? 'purchaser').'.rr.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route(($pp ?? 'purchaser').'.rr.index', ['view' => 'archive']) }}"
                class="pur-tab {{ $archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>

        <div class="flex flex-wrap items-center gap-2">
            @include('layouts.partials.export-pdf-button', [
                'exportUrl' => route(($pp ?? 'purchaser').'.rr.export-pdf', array_filter([
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
                    Print Empty RR
                </button>
                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create RR
                </button>
            @endunless
        </div>
    </div>


    @php
        $rrHasFilters = request()->filled('search')
            || request()->filled('status')
            || request()->filled('date')
            || request()->filled('completeness');
        $rrClearUrl = $archiveView
            ? route(($pp ?? 'purchaser').'.rr.index', ['view' => 'archive'])
            : route(($pp ?? 'purchaser').'.rr.index');
    @endphp

    <div class="pur-card">
        @if(!empty($attentionFocus))
            <div class="px-5 pt-5">
                @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $reports->total()])
            </div>
        @endif
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            {{ $archiveView ? 'Archived RR' : 'RR Records' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $reports->total() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $archiveView ? 'Stored receiving report records.' : 'Search and manage receiving reports from approved RFC.' }}
                    </p>
                </div>

                <form data-auto-filter
                    data-list-panel="#purchaser-rr-list-panel"
                    method="GET"
                    action="{{ route(($pp ?? 'purchaser').'.rr.index') }}"
                    role="search"
                    aria-label="Filter receiving reports"
                    class="flex flex-wrap items-center gap-2"
                >
                    @if(!empty($attentionFocus))
                        <input type="hidden" name="focus" value="{{ request('focus') }}">
                    @endif
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    <div class="relative min-w-[12rem] flex-1">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            id="rr-search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search RR, RFC, supplier, invoice"
                            aria-label="Search receiving reports"
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white"
                        >
                    </div>

                    <select id="rr-payment" name="payment" aria-label="Filter by payment method" class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All payments</option>
                        <option value="direct_payment" @selected(request('payment') === 'direct_payment')>Direct Payment</option>
                        <option value="cash_installment" @selected(request('payment') === 'cash_installment')>Cash Installment</option>
                        <option value="cash_advance" @selected(request('payment') === 'cash_advance')>Cash Advance</option>
                    </select>

                    <select id="rr-status" name="status" aria-label="Filter by status" class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(['Draft','Submitted','Minor Revision','Incomplete','Completed','Returned'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ \App\Support\ProcurementStatus::label($status, 'rr') }}</option>
                        @endforeach
                    </select>

                    <select id="rr-completeness" name="completeness" aria-label="Filter by delivery completeness" class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">Complete &amp; partial</option>
                        <option value="complete" @selected(($completeness ?? null) === 'complete')>Complete only</option>
                        <option value="incomplete" @selected(($completeness ?? null) === 'incomplete')>Partial receive only</option>
                    </select>

                    <input id="rr-date" type="date" name="date" value="{{ request('date') }}" aria-label="Filter by date" class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>

                    @if($rrHasFilters)
                        <a
                            href="{{ $rrClearUrl }}"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div id="purchaser-rr-list-panel" data-list-panel-root class="relative">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RR No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Received from</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($reports as $rr)
                        @php
                            $editable = in_array($rr->receiving_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
                            $rrFundLabel = $rr->funding_type_label ?? \App\Support\ProcurementPaymentPath::label(
                                $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                            );
                            $rrIsCashAdvance = !empty($rr->requires_liquidation)
                                || \App\Support\ProcurementPaymentPath::requiresLiquidation(
                                    $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                );
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="package-check" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-gray-900">{{ $rr->receiving_report_form_number ?: '—' }}</p>
                                            @include('partials.ris-urgency-badge', [
                                                'urgent' => \App\Support\DocumentUrgency::isUrgent('RR', $rr),
                                                'size' => 'sm',
                                                'title' => 'Inherited from linked RIS',
                                            ])
                                            @include('partials.payment-method-badge', [
                                                'path' => $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null),
                                                'size' => 'sm',
                                            ])
                                        </div>
                                        @if(($rr->open_back_orders ?? 0) > 0 || ($rr->replacement_rows ?? 0) > 0)
                                            <p class="mt-0.5 text-xs text-gray-400">
                                                @if(($rr->open_back_orders ?? 0) > 0)
                                                    <span class="rounded bg-amber-50 px-1.5 py-0.5 font-medium text-amber-700">{{ $rr->receiving_report_status === 'Incomplete' ? '' : 'Partial Receive · ' }}{{ $rr->open_back_orders }} back order{{ $rr->open_back_orders === 1 ? '' : 's' }}</span>
                                                @endif
                                                @if(($rr->replacement_rows ?? 0) > 0)
                                                    <span class="ml-1 rounded bg-sky-50 px-1.5 py-0.5 font-medium text-sky-700">{{ $rr->replacement_rows }} replacement row{{ $rr->replacement_rows === 1 ? '' : 's' }}</span>
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @include('partials.procurement-request-no', ['requestNo' => $rr->receiving_report_request_no ?? null, 'inline' => true, 'showLabel' => false])
                                @if(blank($rr->receiving_report_request_no ?? null))
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $rr->request_check_form_number ?? '—' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $rr->receiving_report_received_from ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $rr->receiving_report_date ? \Carbon\Carbon::parse($rr->receiving_report_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $rrRawStatus = trim((string) ($rr->receiving_report_status ?? ''));
                                    $rrRowItems = collect($itemsByRr[$rr->receiving_report_id] ?? []);
                                    $rrOrderedTotal = 0;
                                    $rrGoodReceivedTotal = 0;
                                    if ($rrRawStatus === 'Incomplete') {
                                        foreach ($rrRowItems as $ri) {
                                            $isRepl = !empty($ri->receiving_report_item_back_order_id);
                                            if (!$isRepl) {
                                                $rrOrderedTotal += (int) ($ri->receiving_report_item_ordered_qty ?? $ri->receiving_report_item_quantity ?? 0);
                                            }
                                            $rawQty = (int) ($ri->receiving_report_item_quantity ?? 0);
                                            $dmgQty = (int) ($ri->receiving_report_item_damaged_qty ?? 0);
                                            $isVerified = !property_exists($ri, 'receiving_report_item_verified') || !empty($ri->receiving_report_item_verified);
                                            if ($isVerified) {
                                                $rrGoodReceivedTotal += max(0, $rawQty - $dmgQty);
                                            }
                                        }
                                    }
                                    $rrBadge = match (true) {
                                        $rrRawStatus === 'Draft' => [
                                            'pill' => 'Draft',
                                            'sub' => 'In preparation',
                                            'bg' => '#f1f5f9', 'fg' => '#475569', 'border' => '#cbd5e1', 'dot' => '#64748b',
                                        ],
                                        in_array($rrRawStatus, ['Pending', 'Submitted', 'Resubmitted', 'Under Review', 'In Review'], true) => [
                                            'pill' => 'For Second Count',
                                            'sub' => 'With Receiving Officer',
                                            'bg' => '#f0f9ff', 'fg' => '#0369a1', 'border' => '#bae6fd', 'dot' => '#0284c7',
                                        ],
                                        $rrRawStatus === 'Incomplete' => [
                                            'pill' => 'Partial Receive',
                                            'sub' => $rrOrderedTotal > 0 ? ('Received '.min($rrGoodReceivedTotal, $rrOrderedTotal).' of '.$rrOrderedTotal) : 'Back order open',
                                            'bg' => '#fffbeb', 'fg' => '#b45309', 'border' => '#fde68a', 'dot' => '#d97706',
                                        ],
                                        in_array($rrRawStatus, ['Completed', 'Accepted', 'Delivered'], true) && $rrIsCashAdvance && empty($rr->has_liq) => [
                                            'pill' => 'Needs Liquidation',
                                            'sub' => 'Create Liquidation Report',
                                            'bg' => '#eef2ff', 'fg' => '#4338ca', 'border' => '#c7d2fe', 'dot' => '#4f46e5',
                                        ],
                                        in_array($rrRawStatus, ['Completed', 'Accepted', 'Delivered'], true) && $rrIsCashAdvance && ($rr->liq_status ?? null) !== 'Approved' => [
                                            'pill' => 'For Liquidation',
                                            'sub' => in_array($rr->liq_status ?? '', ['Draft', 'Minor Revision'], true) ? 'Liquidation in progress' : 'With Accounting',
                                            'bg' => '#f0f9ff', 'fg' => '#0369a1', 'border' => '#bae6fd', 'dot' => '#0284c7',
                                        ],
                                        in_array($rrRawStatus, ['Completed', 'Accepted', 'Delivered'], true) && $rrIsCashAdvance => [
                                            'pill' => 'Completed',
                                            'sub' => 'Liquidated & Verified',
                                            'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0', 'dot' => '#059669',
                                        ],
                                        in_array($rrRawStatus, ['Completed', 'Accepted', 'Delivered'], true) => [
                                            'pill' => 'Completed',
                                            'sub' => 'Verified by Receiving',
                                            'bg' => '#ecfdf5', 'fg' => '#047857', 'border' => '#a7f3d0', 'dot' => '#059669',
                                        ],
                                        in_array($rrRawStatus, ['Minor Revision', 'Returned', 'For revision'], true) => [
                                            'pill' => 'Returned',
                                            'sub' => 'For revision',
                                            'bg' => '#fff7ed', 'fg' => '#c2410c', 'border' => '#fed7aa', 'dot' => '#ea580c',
                                        ],
                                        default => [
                                            'pill' => \App\Support\ProcurementStatus::label($rrRawStatus, 'rr'),
                                            'sub' => '',
                                            'bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca', 'dot' => '#dc2626',
                                        ],
                                    };
                                @endphp
                                <div class="inline-flex flex-col items-start">
                                    <span
                                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                        style="background:{{ $rrBadge['bg'] }};color:{{ $rrBadge['fg'] }};border-color:{{ $rrBadge['border'] }};"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $rrBadge['dot'] }};"></span>
                                        <span>{{ $rrBadge['pill'] }}</span>
                                    </span>
                                    @if($rrBadge['sub'] !== '')
                                        <span class="mt-1 whitespace-nowrap text-[11px] font-medium leading-tight text-slate-500">
                                            {{ $rrBadge['sub'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $rrNeedsLiq = !$archiveView
                                        && $rr->receiving_report_status === 'Completed'
                                        && !empty($rr->requires_liquidation)
                                        && empty($rr->has_liq);
                                    $rrHasOpenBo = !$archiveView && ($rr->open_back_orders ?? 0) > 0;
                                @endphp
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    @if($rrNeedsLiq)
                                        <button
                                            type="button"
                                            @click="openView({{ $rr->receiving_report_id }})"
                                            class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                            title="Create Liquidation"
                                            aria-label="Create Liquidation"
                                        >
                                            <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                            <span class="pointer-events-none absolute -right-1 -top-1 flex h-2.5 w-2.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white"></span>
                                            </span>
                                        </button>
                                    @elseif($rrHasOpenBo)
                                        <button
                                            type="button"
                                            @click="openView({{ $rr->receiving_report_id }})"
                                            class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                                            title="Back orders ({{ $rr->open_back_orders }} open)"
                                            aria-label="Back orders"
                                        >
                                            <i data-lucide="package-x" class="h-4 w-4"></i>
                                            <span class="pointer-events-none absolute -right-1 -top-1 flex h-2.5 w-2.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white"></span>
                                            </span>
                                        </button>
                                    @else
                                        <button type="button" @click="openView({{ $rr->receiving_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="View" aria-label="View"><i data-lucide="eye" class="h-4 w-4"></i></button>
                                    @endif
                                    <button
                                        type="button"
                                        data-share-doc-type="rr"
                                        data-share-doc-id="{{ (int) $rr->receiving_report_id }}"
                                        data-share-doc-label="{{ \App\Support\RequestExplorer::shareUiLabel('rr', $rr->receiving_report_form_number ?? null) }}"
                                        class="js-share-document inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="Share to Messages"
                                        aria-label="Share to Messages"
                                    >
                                        <i data-lucide="share-2" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rr->receiving_report_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]" title="Edit" aria-label="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        @php
                                            $rrSubmitLabel = $rr->receiving_report_status === 'Minor Revision'
                                                ? 'Resubmit for Second Count'
                                                : 'Submit for Second Count';
                                        @endphp
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.rr.submit', $rr->receiving_report_id) }}"
                                            data-pur-confirm="{{ $rr->receiving_report_status === 'Minor Revision' ? 'Resubmit this Receiving Report for review?' : 'Submit this Receiving Report for review?' }}"
                                            data-pur-confirm-title="{{ $rr->receiving_report_status === 'Minor Revision' ? 'Resubmit RR' : 'Submit RR' }}"
                                            data-pur-confirm-ok="{{ $rr->receiving_report_status === 'Minor Revision' ? 'Resubmit' : 'Submit' }}"
                                            data-pur-confirm-reviewer-role="Receiving Officer"
                                            class="inline-flex"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800"
                                                title="{{ $rrSubmitLabel }}"
                                                aria-label="{{ $rrSubmitLabel }}"
                                            >
                                                <i data-lucide="send" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!$archiveView && $rr->receiving_report_status === 'Completed' && !empty($rr->requires_liquidation))
                                        @if($rr->has_liq)
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="Liquidation Created" aria-label="Liquidation Created"><i data-lucide="circle-check" class="h-4 w-4"></i></span>
                                        @endif
                                    @elseif(!$archiveView && $rr->receiving_report_status === 'Completed' && empty($rr->requires_liquidation))
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="Workflow complete" aria-label="Workflow complete"><i data-lucide="circle-check" class="h-4 w-4"></i></span>
                                    @endif
                                    @php
                                        // Hide archive while liquidation still needs to be created.
                                        $canArchiveRr = in_array($rr->receiving_report_status, ['Completed', 'Returned'], true)
                                            && ! $rrNeedsLiq
                                            && (
                                                $rr->receiving_report_status === 'Returned'
                                                || empty($rr->requires_liquidation)
                                                || ! empty($rr->has_liq)
                                            );
                                    @endphp
                                    @if($archiveView)
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rr.restore', $rr->receiving_report_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50" title="Restore" aria-label="Restore"><i data-lucide="archive-restore" class="h-4 w-4"></i></button>
                                        </form>
                                    @elseif($canArchiveRr)
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.rr.archive', $rr->receiving_report_id) }}"
                                            data-pur-confirm="Archive this Receiving Report?"
                                            data-pur-confirm-title="Archive RR"
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
                                    <i data-lucide="package-check" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No receiving reports found</p>
                                <p class="mt-1 text-sm text-gray-400">Create an RR or adjust the current filters.</p>
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

    {{-- CREATE RR MODAL (outer scroll, same shell as ATP/RIS) --}}
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
        aria-labelledby="rr-create-title"
    >
        <div
            x-on:click.self="guardClose('create')"
            class="flex min-h-full w-full justify-center"
        >
            @php $rrCreateEmpty = $eligibleRfcs->isEmpty(); @endphp
            <div
                @click.stop
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto {{ $rrCreateEmpty ? 'max-w-md' : 'max-w-5xl' }} rounded-xl shadow-2xl'"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="package-check" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="rr-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Receiving Report</h3>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @unless($rrCreateEmpty)
                            @include('purchaser.partials.modal-fullscreen-button')
                        @endunless
                        <button type="button" @click="guardClose('create')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                @if($rrCreateEmpty)
                    <div class="px-5 py-5 text-sm leading-relaxed text-gray-600 md:px-6">
                        No approved Request for Check with released funds is available. Create RR only after Accounting releases the check. Use <strong>Print Empty RR</strong> for a blank paper form.
                    </div>
                @else
                    <form
                        method="POST"
                        action="{{ route(($pp ?? 'purchaser').'.rr.store') }}"
                        enctype="multipart/form-data"
                        x-ref="createForm"
                        x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                    >
                        @csrf
                        <input type="hidden" name="save_action" value="draft">
                        <div class="bg-slate-100 p-3 md:p-5">
                            <div class="mx-auto mb-4 w-full max-w-[1095px]">
                                <label class="text-xs font-medium text-gray-500">Approved funding request · ATP <span class="text-red-500">*</span> <span class="font-normal text-gray-400">(required to create)</span></label>
                                @php $createFundingKey = (string) old('receiving_report_funding_key', $selectedFundingKey ?? ''); @endphp
                                <select name="receiving_report_funding_key" required x-on:change="applyRfcPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border px-3 text-sm">
                                    <option value="">Select funding request and ATP</option>
                                    @foreach($eligibleRfcs as $rfc)
                                        @php
                                            $rfcFundLabel = \App\Support\ProcurementPaymentPath::label(
                                                $rfc->authority_purchase_payment_path
                                                    ?? ($rfc->request_check_funding_type ?? null)
                                            );
                                        @endphp
                                        <option value="{{ $rfc->funding_key }}" {{ $createFundingKey === $rfc->funding_key ? 'selected' : '' }}>
                                            {{ $rfc->request_check_form_number }} @if($rfc->authority_purchase_form_number)· {{ $rfc->authority_purchase_form_number }}@endif
                                            @if($rfcFundLabel && $rfcFundLabel !== 'Not chosen') · {{ $rfcFundLabel }}@endif
                                            @if(!empty($rfc->is_multi_atp)) (one RR per ATP) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="mt-3" x-show="referencesFor(selectedFundingKey).length" x-cloak>
                                    <p class="text-xs font-medium text-slate-700">Approved reference</p>
                                    <!--<p class="mt-0.5 text-[11px] text-slate-500">Canvass, RIS, ATP, and RFC already approved. Open them while you create this Receiving Report.</p>-->
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="ref in referencesFor(selectedFundingKey)" :key="ref.url">
                                            <a :href="ref.url" target="_blank" rel="noopener" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-[#0025cc] hover:bg-slate-50" x-text="ref.label"></a>
                                        </template>
                                    </div>
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500" x-show="selectedFundingKey && isCashAdvance(selectedFundingKey)" x-cloak>
                                    Cash Advance RR — Official Receipt (OR) details are required.
                                </p>
                            </div>
                            @include('partials.receiving-report-paper', [
                                'editable' => true,
                                'rr' => null,
                                'rows' => collect(),
                                'allowMultiSupplier' => true,
                                'suppliers' => $suppliers ?? collect(),
                                'signKey' => 'rr-create',
                                'suggestedRrFormNumber' => $suggestedRrFormNumber ?? \App\Support\RrFormNumber::next(),
                                'showOrCard' => true,
                                'orCardAlpine' => 'isCashAdvance(selectedFundingKey)',
                                'fundingBadgeAlpine' => '!!selectedFundingKey',
                            ])
                            <div id="purSigSlot-rr-create" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                            <!--<p class="mr-auto max-w-sm text-[11px] text-slate-500">
                                Submit sends this Receiving Report to the Receiving Officer next.
                            </p>-->
                            <button type="button" @click="guardClose('create')" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                onclick="this.form.querySelector('input[name=save_action]').value='draft'"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            >
                                {{ \App\Support\ProcurementDraftLabel::SAVE }}
                            </button>
                            <button
                                type="button"
                                @click="openRrSubmitConfirm($el, false)"
                                class="rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-medium text-white hover:bg-blue-800"
                            >
                                Submit for Second Count
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    </template>

    {{-- SUBMIT FOR SECOND COUNT CONFIRMATION MODAL --}}
    <template x-teleport="body">
        <div
            x-show="rrSubmitConfirmOpen"
            x-cloak
            @keydown.escape.window="if (rrSubmitConfirmOpen) rrSubmitConfirmOpen = false"
            class="fixed inset-0 flex items-center justify-center bg-black/50 p-4"
            style="z-index: 99999;"
            @click.self="rrSubmitConfirmOpen = false"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                <h3 class="text-base font-bold text-slate-900" x-text="rrSubmitConfirmSummary.title"></h3>
                <p class="mt-2 text-sm text-slate-600">
                    This Receiving Report will be sent to the <strong class="text-slate-900" x-text="rrSubmitConfirmSummary.destination"></strong> for verification.
                </p>
                <div class="mt-4 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Received From</span>
                        <span class="truncate font-semibold text-slate-900" x-text="rrSubmitConfirmSummary.receivedFrom"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Items</span>
                        <span class="font-semibold text-slate-900" x-text="rrSubmitConfirmSummary.itemCount"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Submit To</span>
                        <span class="font-semibold text-[#0025cc]" x-text="rrSubmitConfirmSummary.destination"></span>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="rrSubmitConfirmOpen = false"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmRrSubmit()"
                        class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001db3]"
                        x-text="rrSubmitConfirmSummary.okLabel"
                    ></button>
                </div>
            </div>
        </div>
    </template>

    @foreach($reports as $rr)
        @php
            $rrItems = $items->get($rr->receiving_report_id, collect())->values();
            $canEdit = in_array($rr->receiving_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
        @endphp
        <template x-teleport="body">
        <div
            x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedRr === {{ $rr->receiving_report_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="rr-view-title-{{ $rr->receiving_report_id }}"
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
                                    <i data-lucide="package-check" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    @php
                                        $viewFundLabel = $rr->funding_type_label ?? \App\Support\ProcurementPaymentPath::label(
                                            $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                        );
                                        $viewIsCashAdvance = !empty($rr->requires_liquidation)
                                            || \App\Support\ProcurementPaymentPath::requiresLiquidation(
                                                $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                            );
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 id="rr-view-title-{{ $rr->receiving_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">{{ $rr->receiving_report_form_number }}</h3>
                                        @include('partials.ris-urgency-badge', [
                                            'urgent' => \App\Support\DocumentUrgency::isUrgent('RR', $rr),
                                            'title' => 'Inherited from linked RIS',
                                        ])
                                        @if($viewFundLabel && $viewFundLabel !== 'Not chosen')
                                            @php
                                                $viewFundPath = \App\Support\ProcurementPaymentPath::normalize($rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null));
                                                $viewFundStyle = match ($viewFundPath) {
                                                    \App\Support\ProcurementPaymentPath::CASH_ADVANCE => 'background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe;',
                                                    \App\Support\ProcurementPaymentPath::CASH_INSTALLMENT => 'background:#fffbeb;color:#b45309;border-color:#fde68a;',
                                                    default => 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold" style="{{ $viewFundStyle }}">{{ $viewFundLabel }} RR</span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
                                </div>
                            </div>
                            @if($rr->receiving_report_revision_notes)
                                <p class="mt-2 text-sm text-amber-700">Revision: {{ $rr->receiving_report_revision_notes }}</p>
                                @include('partials.ris-revision-images', [
                                    'revision' => \App\Support\DocumentRevisionNotes::latest('RR', $rr->receiving_report_id),
                                    'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                    'size' => 'sm',
                                ])
                            @endif
                            @if($rr->receiving_report_return_reason)
                                <p class="mt-2 text-sm text-red-700">Returned: {{ $rr->receiving_report_return_reason }}</p>
                                @include('partials.ris-revision-images', [
                                    'revision' => \App\Support\DocumentRevisionNotes::latest('RR', $rr->receiving_report_id, \App\Support\DocumentRevisionNotes::KIND_RETURN),
                                    'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                    'size' => 'sm',
                                ])
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="printRr({{ $rr->receiving_report_id }})"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                data-tooltip="Print RR"
                                aria-label="Print RR"
                            >
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            </button>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.rr.export-xlsx', $rr->receiving_report_id) }}"
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
                                href="{{ route(($pp ?? 'purchaser').'.rr.export-docx', $rr->receiving_report_id) }}"
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
                            <button type="button" @click="viewOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                    @php
                        $rrLineage = \App\Support\DocumentLineage::forRr((int) $rr->receiving_report_id);
                        $rrHint = \App\Support\DocumentLineage::reviewHint($rr->receiving_report_status ?? null, null, 'rr');
                    @endphp
                    @include('partials.document-lineage', [
                        'lineage' => $rrLineage,
                        'currentType' => 'RR',
                        'statusHint' => $rrHint,
                    ])
                    <div class="bg-slate-100 p-3 md:p-5">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="px-2 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
                        >
                            Close
                        </button>
                        @if(!$archiveView && ($rr->open_back_orders ?? 0) > 0)
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.bo.index', ['rr' => $rr->receiving_report_id]) }}"
                                data-pur-confirm="Open Back Orders for {{ $rr->receiving_report_form_number ?: 'this Receiving Report' }} to record a reason, refund, or replacement?"
                                data-pur-confirm-title="Open Back Orders"
                                data-pur-confirm-ok="Open Back Orders"
                                class="inline-flex h-10 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-800 transition hover:bg-amber-100"
                            >
                                <i data-lucide="package-x" class="h-4 w-4"></i>
                                {{ $rr->open_back_orders }} back order{{ $rr->open_back_orders === 1 ? '' : 's' }} to complete this RR
                            </a>
                        @elseif(!$archiveView && $rr->receiving_report_status === 'Completed' && !empty($rr->requires_liquidation))
                            @if(!$rr->has_liq)
                                <a
                                    href="{{ route(($pp ?? 'purchaser').'.liq.index', ['selected_rr' => $rr->receiving_report_id]) }}"
                                    data-pur-confirm="Proceed to create a Liquidation Report for {{ $rr->receiving_report_form_number ?: 'this Receiving Report' }}?"
                                    data-pur-confirm-title="Create Liquidation Report"
                                    data-pur-confirm-ok="Create Liquidation"
                                    class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-5 text-sm font-semibold text-white transition hover:bg-blue-800"
                                >Create Liquidation</a>
                            @else
                                <span class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700">Liquidation Created</span>
                            @endif
                        @elseif(!$archiveView && $rr->receiving_report_status === 'Completed' && empty($rr->requires_liquidation))
                            <span class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700">Workflow complete</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        </template>
        @if($canEdit)
            <template x-teleport="body">
            <div
                x-show="editOpen && selectedRr === {{ $rr->receiving_report_id }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedRr === {{ $rr->receiving_report_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                role="dialog"
                aria-modal="true"
                aria-labelledby="rr-edit-title-{{ $rr->receiving_report_id }}"
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
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                    <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 id="rr-edit-title-{{ $rr->receiving_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">Edit {{ $rr->receiving_report_form_number }}</h3>
                                        @php
                                            $editFundLabel = $rr->funding_type_label ?? \App\Support\ProcurementPaymentPath::label(
                                                $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                            );
                                            $editIsCashAdvance = !empty($rr->requires_liquidation)
                                                || \App\Support\ProcurementPaymentPath::requiresLiquidation(
                                                    $rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null)
                                                );
                                        @endphp
                                        @if($editFundLabel && $editFundLabel !== 'Not chosen')
                                            @php
                                                $editFundPath = \App\Support\ProcurementPaymentPath::normalize($rr->authority_purchase_payment_path ?? ($rr->request_check_funding_type ?? null));
                                                $editFundStyle = match ($editFundPath) {
                                                    \App\Support\ProcurementPaymentPath::CASH_ADVANCE => 'background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe;',
                                                    \App\Support\ProcurementPaymentPath::CASH_INSTALLMENT => 'background:#fffbeb;color:#b45309;border-color:#fde68a;',
                                                    default => 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold" style="{{ $editFundStyle }}">{{ $editFundLabel }} RR</span>
                                        @endif
                                    </div>
                                    @if($editIsCashAdvance)
                                        <p class="mt-0.5 text-xs text-slate-500">Cash Advance — Official Receipt section is required.</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                @include('purchaser.partials.modal-fullscreen-button')
                                <button type="button" @click="guardClose('edit')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        <form
                            method="POST"
                            action="{{ route(($pp ?? 'purchaser').'.rr.update', $rr->receiving_report_id) }}"
                            enctype="multipart/form-data"
                            data-pur-edit-form="rr"
                            x-bind:data-open="editOpen && selectedRr === {{ $rr->receiving_report_id }} ? '1' : '0'"
                            x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                        >
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="receiving_report_request_check_id" value="{{ $rr->receiving_report_request_check_id }}">
                            <div class="bg-slate-100 p-3 md:p-5">
                                @include('partials.receiving-report-paper', ['editable' => true, 'rr' => $rr, 'rows' => $rrItems, 'allowMultiSupplier' => true, 'suppliers' => $suppliers ?? collect(), 'signKey' => 'rr-'.$rr->receiving_report_id])
                                <div id="purSigSlot-rr-{{ $rr->receiving_report_id }}" class="mx-auto mt-4 w-full max-w-[1095px]"></div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                <!--<p class="mr-auto max-w-md text-xs text-gray-500">
                                    Submit sends this Receiving Report to the Receiving Officer next.
                                </p>-->
                                <button
                                    type="button"
                                    @click="guardClose('edit')"
                                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-950"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    onclick="this.form.querySelector('input[name=save_action]').value='draft'"
                                    class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Save Changes
                                </button>
                                <button
                                    type="button"
                                    @click="openRrSubmitConfirm($el, {{ $rr->receiving_report_status === 'Minor Revision' ? 'true' : 'false' }})"
                                    class="rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                >
                                    {{ $rr->receiving_report_status === 'Minor Revision' ? 'Resubmit for Second Count' : 'Submit for Second Count' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </template>
        @endif
    @endforeach

    {{-- PRINT EMPTY RR MODAL --}}
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
        aria-labelledby="rr-empty-title"
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
                            <h3 id="rr-empty-title" class="text-lg font-semibold text-gray-950">Print Empty RR</h3>
                            <p class="mt-0.5 text-xs text-gray-500">Blank paper only. System RRs must link a funds-released RFC.</p>
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
                    @include('partials.receiving-report-paper', [
                        'editable' => false,
                        'rr' => null,
                        'rows' => collect(),
                        'printId' => 'rr-print-blank',
                        'showOrCard' => false,
                        'showItemPhotos' => false,
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
                        href="{{ route(($pp ?? 'purchaser').'.rr.export-blank-xlsx') }}"
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
                        href="{{ route(($pp ?? 'purchaser').'.rr.export-blank-docx') }}"
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
                        @click="printRr('blank')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-medium text-white hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty RR
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <div id="purDocSignatureDock" class="hidden" aria-hidden="true">
        @include('purchaser.partials.document-signature-panel', ['savedSignatures' => $savedSignatures ?? collect()])
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        @page { size: A4 portrait; margin: 8mm; }
        .rr-print-active { background: #fff !important; }
    }
</style>
@include('partials.ris-revision-image-viewer')
@endsection
