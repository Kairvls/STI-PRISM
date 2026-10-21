@extends($procurementLayout ?? 'layouts.purchaser-layout')

@php
    $atpIsSupplyPortal = \App\Support\ProcurementPortal::isSupplyOfficerContext();
@endphp
@section('page-title', $atpIsSupplyPortal ? 'Authority to Purchase' : 'Released ATPs')
@section('page-subtitle', $atpIsSupplyPortal
    ? 'Create ATP from Incoming RIS, submit to School Administrator, then release approved ATP back to the Purchaser.'
    : 'ATP released by the Supply Officer — choose Direct Payment, Cash Installment, or Cash Advance to continue.')

@section('content')
@include('partials.floating-menu-script')

<script type="application/json" id="atp-ris-prefill">{!! json_encode($risPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('authority_purchase_ris_id')) || !empty($selectedRisId) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewAtpId) ? 'true' : 'false' }},
        canGoBack: {{ !empty($viewAtpId) ? 'true' : 'false' }} && (() => {
            try {
                const ref = new URL(document.referrer);
                return window.history.length > 1 && ref.origin === window.location.origin && ref.pathname !== window.location.pathname;
            } catch (e) {
                return false;
            }
        })(),
        editOpen: {{ !empty($editAtpId) ? 'true' : 'false' }},
        emptyOpen: false,
        modalFullscreen: false,
        selectedAtp: {{ !empty($editAtpId) ? (int) $editAtpId : (!empty($viewAtpId) ? (int) $viewAtpId : 'null') }},
        risPrefill: JSON.parse(document.getElementById('atp-ris-prefill').textContent || '{}'),
        risSplit: false,
        risChoiceTick: 0,

        openView(id) {
            this.selectedAtp = id;
            this.viewOpen = true;
            this.editOpen = false;
            this.modalFullscreen = false;
        },

        openEdit(id) {
            this.selectedAtp = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('atp-' + id, 'Received by signature');
        },

        openCreate() {
            this.createOpen = true;
            this.modalFullscreen = false;
            this.bindDocSig('atp-create', 'Received by signature');
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

        closeAll() {
            this.createOpen = false;
            this.viewOpen = false;
            this.editOpen = false;
            this.emptyOpen = false;
            this.modalFullscreen = false;
            this.selectedAtp = null;
        },

        syncRisSplit() {
            const form = this.$refs.createForm;
            const option = this.$refs.risSelect ? this.$refs.risSelect.selectedOptions[0] : null;
            this.risSplit = !!(option && option.dataset.split === '1');
            const supplierSelect = form ? form.querySelector('[data-atp-supplier]') : null;
            if (supplierSelect) {
                if (supplierSelect.dataset.baseTitle === undefined) {
                    supplierSelect.dataset.baseTitle = supplierSelect.title || '';
                }
                supplierSelect.style.pointerEvents = this.risSplit ? 'none' : '';
                supplierSelect.tabIndex = this.risSplit ? -1 : 0;
                supplierSelect.title = this.risSplit ? 'Set by the selected RIS supplier' : supplierSelect.dataset.baseTitle;
            }
        },

        risChoices() {
            const tick = this.risChoiceTick;
            const select = this.$refs.risSelect;
            if (!select || tick === undefined) return [];
            const current = select.selectedOptions[0];
            const risId = current ? (current.dataset.risId || '') : '';
            if (!risId) return [];
            return Array.from(select.options).filter(function (opt) {
                return opt.dataset.risId === risId && opt.value;
            }).map(function (opt) {
                return {
                    key: opt.dataset.key,
                    label: opt.dataset.chip || opt.textContent.trim(),
                    active: opt.selected
                };
            });
        },

        chooseRisOption(key) {
            const select = this.$refs.risSelect;
            if (!select) return;
            select.value = key;
            this.applyRisPrefill(key);
        },

        lockSupplierField(form, locked, supplierId) {
            const select = form.querySelector('[data-atp-supplier]');
            if (!select) return;
            select.disabled = !!locked;
            select.classList.toggle('cursor-not-allowed', !!locked);
            select.classList.toggle('!bg-gray-50', !!locked);
            select.title = locked ? 'Taken from the RIS' : (select.dataset.baseTitle || '');
            let hidden = form.querySelector('input[data-locked-supplier]');
            if (locked) {
                select.removeAttribute('name');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'authority_purchase_supplier_id';
                    hidden.setAttribute('data-locked-supplier', '');
                    select.insertAdjacentElement('afterend', hidden);
                }
                hidden.value = supplierId || select.value || '';
            } else {
                if (!select.getAttribute('name')) {
                    select.setAttribute('name', 'authority_purchase_supplier_id');
                }
                if (hidden) hidden.remove();
            }
        },

        lockFetchedRis(data) {
            const form = this.$refs.createForm;
            if (!form) return;
            const items = data && Array.isArray(data.items) ? data.items : [];
            this.lockSupplierField(form, !!(data && data.supplier_id), data ? data.supplier_id : '');
            const rows = form.querySelectorAll('tr[data-doc-row]');
            rows.forEach(function (row, index) {
                const locked = index < items.length;
                if (locked) row.setAttribute('data-doc-row-locked', '');
                else row.removeAttribute('data-doc-row-locked');
                row.querySelectorAll('input').forEach(function (input) {
                    const name = input.getAttribute('name') || '';
                    const isLine = name.indexOf('[quantity]') !== -1 || name.indexOf('[description]') !== -1 || name.indexOf('[unit_price]') !== -1;
                    if (!isLine) return;
                    input.readOnly = locked;
                    input.classList.toggle('cursor-not-allowed', locked);
                    input.classList.toggle('!bg-gray-50', locked);
                    input.title = locked ? 'Taken from the RIS' : '';
                });
                const unitButton = row.querySelector('button');
                if (unitButton) {
                    unitButton.disabled = locked;
                    unitButton.classList.toggle('cursor-not-allowed', locked);
                    unitButton.title = locked ? 'Taken from the RIS' : '';
                }
            });
        },

        applyRisPrefill(key) {
            const option = this.$refs.risSelect ? this.$refs.risSelect.selectedOptions[0] : null;
            const risIdInput = this.$refs.risIdInput;
            if (risIdInput) {
                risIdInput.value = option && option.dataset.risId ? option.dataset.risId : '';
            }
            this.risChoiceTick = (this.risChoiceTick || 0) + 1;
            this.syncRisSplit();
            const data = this.risPrefill[String(key)];
            const form = this.$refs.createForm;
            if (!form || !data) {
                if (form) this.lockFetchedRis(null);
                return;
            }

            if (data.supplier_id) {
                const supplierSelect = form.querySelector('[data-atp-supplier]');
                if (supplierSelect) {
                    supplierSelect.disabled = false;
                    supplierSelect.value = data.supplier_id;
                    supplierSelect.dispatchEvent(new Event('change'));
                }
            }

            const prefillItems = Array.isArray(data.items) ? data.items : [];
            const rowsRoot = form.querySelector('[data-doc-rows]');
            const rowTotal = (rowsRoot && window.docRows)
                ? window.docRows.fit(rowsRoot, prefillItems.length).length
                : 8;
            for (let i = 0; i < rowTotal; i++) {
                const item = prefillItems[i] || {};
                const qty = form.querySelector('[name=\'items[' + i + '][quantity]\']');
                const unit = form.querySelector('[name=\'items[' + i + '][unit]\']');
                const desc = form.querySelector('[name=\'items[' + i + '][description]\']');
                const price = form.querySelector('[name=\'items[' + i + '][unit_price]\']');
                const amount = form.querySelector('[name=\'items[' + i + '][amount_display]\']');

                if (qty) qty.value = item.quantity ?? '';
                if (unit) {
                    unit.value = item.unit ?? '';
                    unit.dispatchEvent(new Event('input'));
                }
                if (desc) desc.value = item.description ?? '';
                if (price) price.value = item.unit_price ?? '';
                if (amount) {
                    const quantity = parseFloat(item.quantity || 0);
                    const unitPrice = parseFloat(item.unit_price || 0);
                    amount.value = quantity && unitPrice ? (quantity * unitPrice).toFixed(2) : '';
                }
            }
            this.lockFetchedRis(data);
        },

        printAtp(id) {
            const sheetId = id === 'blank' ? 'atp-print-blank' : ('atp-print-' + id);
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'atp-print-active');
                return;
            }
            const sheet = document.getElementById(sheetId);
            if (sheet) {
                document.querySelectorAll('.atp-print-sheet').forEach(function (el) {
                    el.classList.remove('atp-print-active');
                });
                sheet.classList.add('atp-print-active');
            }
            window.print();
        }
    }"
    x-init="
        if (createOpen) {
            $nextTick(() => {
                bindDocSig('atp-create', 'Received by signature');
                const risOption = $refs.risSelect ? $refs.risSelect.selectedOptions[0] : null;
                if ({{ old('authority_purchase_ris_id') !== null ? 'true' : 'false' }}) {
                    syncRisSplit();
                    if (risOption && risOption.dataset.key && risPrefill[risOption.dataset.key]) {
                        lockFetchedRis(risPrefill[risOption.dataset.key]);
                    }
                } else if ('{{ $selectedRisId ?? '' }}' && risOption && risOption.dataset.key) {
                    applyRisPrefill(risOption.dataset.key);
                }
            });
        }
        if (editOpen && selectedAtp) {
            $nextTick(() => bindDocSig('atp-' + selectedAtp, 'Received by signature'));
        }
    "
    @keydown.escape.window="if (window.atpSubmitOpen) return; closeAll()"
    class="space-y-6"
>

    {{-- ========================================================= --}}
    {{-- FLASH MESSAGES --}}
    {{-- ========================================================= --}}

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="ATP list view">
            <a
                href="{{ route(($pp ?? 'purchaser').'.atp.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route(($pp ?? 'purchaser').'.atp.index', ['view' => 'archive']) }}"
                class="pur-tab {{ $archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>

        @unless($archiveView)
            @if(\App\Support\ProcurementPortal::isSupplyOfficerContext())
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="emptyOpen = true; modalFullscreen = false; $nextTick(() => window.lucide && window.lucide.createIcons())"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty ATP
                    </button>
                    <button
                        type="button"
                        @click="openCreate()"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                    >
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Create ATP
                    </button>
                </div>
            @endif
        @endunless
    </div>

    @unless($archiveView)
        @if(\App\Support\ProcurementPortal::isSupplyOfficerContext())
            @include('partials.draft-handover', ['type' => 'atp'])
        @endif
    @endunless
    @php
        $handoverOutgoing = \App\Support\DraftHandover::outgoing('atp');
        $handoverDeclined = \App\Support\DraftHandover::declinedForSender('atp');
        $atpFormLabel = fn ($atp) => $atp->authority_purchase_form_number ?: ('ATP #'.$atp->authority_purchase_id);
        $directPaymentConfirm = fn ($atp) => 'Pay '.$atpFormLabel($atp).' through Direct Payment? Accounting will process payment to the supplier. Pipeline ends at Receiving Report — no liquidation. This can\'t be changed once the funding request is created.';
        $cashInstallmentConfirm = fn ($atp) => 'Pay '.$atpFormLabel($atp).' through Cash Installment? Accounting will process installment payments to the supplier. Pipeline ends at Receiving Report — no liquidation. This can\'t be changed once the funding request is created.';
        $cashAdvanceConfirm = fn ($atp) => 'Pay '.$atpFormLabel($atp).' through a Cash Advance? Accounting will release cash to you, and you must liquidate it with receipts after receiving. Pipeline ends at Liquidation Report. This can\'t be changed once the cash advance request is created.';
    @endphp

    {{-- SUMMARY CARDS --}}

    {{-- ATP RECORDS --}}
    <div class="pur-card">
        @if(!empty($attentionFocus))
            <div class="px-5 pt-5">
                @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $atps->total()])
            </div>
        @endif
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            {{ $archiveView ? 'Archived ATP' : 'ATP Records' }}
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $atps->total() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $archiveView ? 'Stored Authority to Purchase records.' : 'Search and manage Authority to Purchase records.' }}
                    </p>
                </div>

                <form data-auto-filter method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    @if(!empty($attentionFocus))
                        <input type="hidden" name="focus" value="{{ request('focus') }}">
                    @endif

                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search ATP, RIS, supplier..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select name="status" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Submitted" {{ request('status') === 'Submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="Minor Revision" {{ request('status') === 'Minor Revision' ? 'selected' : '' }}>Minor Revision</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    @if(\Illuminate\Support\Facades\Schema::hasColumn('requisition_issue_slip_table', 'ris_request_type'))
                        <select name="request_type" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                            <option value="">All RIS types</option>
                            <option value="New Procurement" {{ request('request_type') === 'New Procurement' ? 'selected' : '' }}>New Procurement</option>
                            <option value="Replacement Procurement" {{ request('request_type') === 'Replacement Procurement' ? 'selected' : '' }}>Replacement Procurement</option>
                        </select>
                    @endif

                    <button
                        type="submit"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        Apply
                    </button>

                    @if(request()->filled('search') || request()->filled('status') || request()->filled('request_type'))
                        <a
                            href="{{ $archiveView ? route(($pp ?? 'purchaser').'.atp.index', ['view' => 'archive']) : route(($pp ?? 'purchaser').'.atp.index') }}"
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
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($atps as $atp)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="file-check-2" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-gray-900">
                                                {{ $atp->authority_purchase_form_number ?: '—' }}
                                            </p>
                                            @if(\App\Support\DocumentUrgency::isUrgent('ATP', $atp))
                                                @include('partials.ris-urgency-badge', ['urgent' => true, 'size' => 'sm', 'title' => 'Urgent RIS'])
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $atp->authority_purchase_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @include('partials.procurement-request-no', ['requestNo' => $atp->authority_purchase_request_no ?? null, 'inline' => true, 'showLabel' => false])
                                @if(blank($atp->authority_purchase_request_no ?? null))
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-700">{{ \App\Support\RisWorkflow::formNumber($atp, (int) ($atp->authority_purchase_ris_id ?? 0)) }}</p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ $atp->equipment_name ?? $atp->report_unlisted_equipment_name ?? 'No equipment' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                @if($atp->supplier_store_type === 'Physical Store')
                                    {{ $atp->company_name ?? 'Physical supplier' }}
                                @else
                                    {{ $atp->shop_name ?? 'Online supplier' }}
                                @endif
                                @if(!empty($atp->ris_supplier_position))
                                    <p class="mt-1">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600"
                                            title="This RIS buys from {{ $atp->ris_supplier_total }} suppliers; each supplier has its own ATP."
                                        >
                                            <i data-lucide="split" class="h-3 w-3"></i>
                                            Supplier {{ $atp->ris_supplier_position }} of {{ $atp->ris_supplier_total }}
                                        </span>
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                @if($atp->authority_purchase_date)
                                    {{ \Carbon\Carbon::parse($atp->authority_purchase_date)->format('M d, Y') }}
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @include('accounting.partials.status-badge', [
                                    'status' => \App\Support\RisWorkflow::atpStatusLabel($atp),
                                    'submitted' => $atp->authority_purchase_submitted_at,
                                    'revision' => $atp->authority_purchase_rejection_reason,
                                ])
                                @if(!empty($atp->purchase_order_id) && !empty($atp->purchase_order_label))
                                    @php
                                        $poIndexRoute = ($pp ?? 'purchaser').'.purchase-orders.index';
                                    @endphp
                                    @if(\Illuminate\Support\Facades\Route::has($poIndexRoute))
                                        <a
                                            href="{{ route($poIndexRoute, ['edit_po' => $atp->purchase_order_id]) }}"
                                            class="mt-1 inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100"
                                        >In {{ $atp->purchase_order_label }}</a>
                                    @else
                                        <span class="mt-1 inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">In {{ $atp->purchase_order_label }}</span>
                                    @endif
                                @endif
                                @if($pendingHandover = $handoverOutgoing->get((int) $atp->authority_purchase_id))
                                    <p class="mt-1 flex items-center gap-1 text-[11px] font-medium text-amber-700">
                                        <i data-lucide="hourglass" class="h-3 w-3"></i>
                                        Waiting for {{ $pendingHandover->to_name ?: 'co-worker' }} to accept
                                    </p>
                                @elseif($declinedHandover = $handoverDeclined->get((int) $atp->authority_purchase_id))
                                    <p class="mt-1 flex items-center gap-1 text-[11px] font-medium text-rose-700" title="{{ $declinedHandover->handover_response_note ? 'Reason: '.$declinedHandover->handover_response_note : '' }}">
                                        <i data-lucide="user-x" class="h-3 w-3"></i>
                                        Declined by {{ $declinedHandover->to_name ?: 'co-worker' }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openView({{ $atp->authority_purchase_id }})"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="View"
                                        aria-label="View"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="printAtp({{ $atp->authority_purchase_id }})"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="Print"
                                        aria-label="Print"
                                    >
                                        <i data-lucide="printer" class="h-4 w-4"></i>
                                    </button>

                                    @if(
                                        !$atp->authority_purchase_submitted_at
                                        && $atp->authority_purchase_status === 'Pending'
                                        && !$archiveView
                                    )
                                        <button
                                            type="button"
                                            @click="openEdit({{ $atp->authority_purchase_id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                            title="Edit"
                                            aria-label="Edit"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </button>
                                        <div
                                            class="relative"
                                            x-data="{ openActions: false, actionsTrigger: null }"
                                            @keydown.escape.window="openActions = false"
                                            x-on:scroll.window.capture="openActions = false"
                                            x-on:resize.window="openActions = false"
                                        >
                                            <button
                                                type="button"
                                                x-on:click="actionsTrigger = $el; openActions = !openActions"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                                title="More actions"
                                                aria-label="More actions"
                                                :aria-expanded="openActions.toString()"
                                            >
                                                <i data-lucide="ellipsis" class="h-4 w-4"></i>
                                            </button>
                                            <template x-teleport="body">
                                            <div
                                                x-show="openActions"
                                                x-cloak
                                                x-transition.opacity
                                                x-effect="openActions && $nextTick(() => { window.lucide && window.lucide.createIcons(); window.purPlaceMenu(actionsTrigger, $el); })"
                                                @click.outside="if (!actionsTrigger || !actionsTrigger.contains($event.target)) openActions = false"
                                                class="fixed z-[900] w-56 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg"
                                            >
                                                @if(!empty($atp->submits_via_po) && \Illuminate\Support\Facades\Route::has(($pp ?? 'purchaser').'.purchase-orders.index'))
                                                    <a
                                                        href="{{ route(($pp ?? 'purchaser').'.purchase-orders.index', ['edit_po' => $atp->purchase_order_id]) }}"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50"
                                                    >
                                                        <i data-lucide="file-text" class="h-3.5 w-3.5 text-gray-400"></i>
                                                        Submit via Purchase Order
                                                    </a>
                                                @else
                                                    <button
                                                        type="button"
                                                        data-atp-submit
                                                        data-atp-action="{{ route(($pp ?? 'purchaser').'.atp.submit', $atp->authority_purchase_id) }}"
                                                        data-atp-number="{{ $atp->authority_purchase_form_number ?: ('Draft ATP #'.$atp->authority_purchase_id) }}"
                                                        data-atp-signature="{{ \App\Support\RisWorkflow::isDrawnSignature((string) ($atp->authority_purchase_received_by_signature ?? '')) ? $atp->authority_purchase_received_by_signature : '' }}"
                                                        x-on:click="openActions = false"
                                                        class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50"
                                                    >
                                                        <i data-lucide="send" class="h-3.5 w-3.5 text-gray-400"></i>
                                                        Submit to School Administrator
                                                    </button>
                                                @endif
                                                @if(empty($atp->purchase_order_id))
                                                    @if($atpHandover = $handoverOutgoing->get((int) $atp->authority_purchase_id))
                                                        <form method="POST" action="{{ route('purchaser.handovers.cancel', $atpHandover->handover_id) }}">
                                                            @csrf
                                                            <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50">
                                                                <i data-lucide="undo-2" class="h-3.5 w-3.5 text-gray-400"></i>
                                                                Take back from {{ \Illuminate\Support\Str::before($atpHandover->to_name ?: 'co-worker', ' ') }}
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button
                                                            type="button"
                                                            x-on:click="openActions = false; window.dispatchEvent(new CustomEvent('open-draft-handover', { detail: { type: 'atp', id: {{ (int) $atp->authority_purchase_id }}, label: @js(($atp->authority_purchase_form_number ?: 'Draft ATP').' · Record #'.$atp->authority_purchase_id) } }))"
                                                            class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50"
                                                        >
                                                            <i data-lucide="user-round-plus" class="h-3.5 w-3.5 text-gray-400"></i>
                                                            Pass to 
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                            </template>
                                        </div>
                                    @endif

                                    @if(
                                        !$archiveView
                                        && $atp->authority_purchase_status === 'Pending'
                                        && $atp->authority_purchase_submitted_at
                                        && empty($atp->purchase_order_id)
                                    )
                                        @include('partials.purchaser-reassign-reviewer', [
                                            'type' => 'atp',
                                            'id' => $atp->authority_purchase_id,
                                            'currentReviewerId' => $atp->authority_purchase_assigned_reviewer_id ?? null,
                                        ])
                                    @endif

                                    @if(!$archiveView && !empty($atp->can_release_to_purchaser))
                                        <form method="POST" action="{{ route('supply-officer.atp.release-purchaser', $atp->authority_purchase_id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                data-pur-confirm="Release {{ $atp->authority_purchase_form_number ?: 'ATP #'.$atp->authority_purchase_id }} (with approved Canvas + RIS) back to the Purchaser for Request for Check?"
                                                data-pur-confirm-title="Release to Purchaser"
                                                data-pur-confirm-ok="Release"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700"
                                                title="Release to Purchaser"
                                                aria-label="Release to Purchaser"
                                            >
                                                <i data-lucide="send" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @elseif(!$archiveView && $atp->authority_purchase_status === 'Approved' && !empty($atp->released_to_purchaser) && empty($atp->has_rfc) && !\App\Support\ProcurementPortal::isSupplyOfficerContext())
                                        @if(false && !empty($atp->funds_via_po) && !$atp->has_rfc && \Illuminate\Support\Facades\Route::has(($pp ?? 'purchaser').'.purchase-orders.index'))
                                            <a
                                                href="{{ route(($pp ?? 'purchaser').'.purchase-orders.index', ['view_po' => $atp->purchase_order_id]) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                                title="Fund via {{ $atp->purchase_order_label ?? 'Purchase Order' }}"
                                                aria-label="Fund via Purchase Order"
                                            >
                                                <i data-lucide="shopping-bag" class="h-4 w-4"></i>
                                            </a>
                                        @elseif(!$atp->has_rfc)
                                            @if(empty($atp->authority_purchase_payment_path))
                                                <div class="flex flex-wrap gap-1.5">
                                                    <form
                                                        method="POST"
                                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                                        class="inline"
                                                        data-pur-confirm="{{ $directPaymentConfirm($atp) }}"
                                                        data-pur-confirm-title="Use Direct Payment?"
                                                        data-pur-confirm-ok="Use Direct Payment"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT }}">
                                                        <button
                                                            type="submit"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-800 transition hover:bg-blue-100"
                                                            aria-label="Direct Payment"
                                                            data-pur-tip-title="Direct Payment"
                                                            data-pur-tip="Accounting pays the supplier directly. Opens the funding request form for this ATP."
                                                            data-pur-tip-note="Ends at Receiving Report — no liquidation."
                                                            data-pur-tip-accent="#60a5fa"
                                                        >
                                                            <i data-lucide="badge-check" class="h-4 w-4"></i>
                                                        </button>
                                                    </form>
                                                    <form
                                                        method="POST"
                                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                                        class="inline"
                                                        data-pur-confirm="{{ $cashInstallmentConfirm($atp) }}"
                                                        data-pur-confirm-title="Use Cash Installment?"
                                                        data-pur-confirm-ok="Use Cash Installment"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::CASH_INSTALLMENT }}">
                                                        <button
                                                            type="submit"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-violet-200 bg-violet-50 text-violet-800 transition hover:bg-violet-100"
                                                            aria-label="Cash Installment"
                                                            data-pur-tip-title="Cash Installment"
                                                            data-pur-tip="Accounting pays the supplier in installments. Opens the funding request form for this ATP."
                                                            data-pur-tip-note="Ends at Receiving Report — no liquidation."
                                                            data-pur-tip-accent="#a78bfa"
                                                        >
                                                            <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                                                        </button>
                                                    </form>
                                                    <form
                                                        method="POST"
                                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                                        class="inline"
                                                        data-pur-confirm="{{ $cashAdvanceConfirm($atp) }}"
                                                        data-pur-confirm-title="Use Cash Advance?"
                                                        data-pur-confirm-ok="Use Cash Advance"
                                                    >
                                                        @csrf
                                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::CASH_ADVANCE }}">
                                                        <button
                                                            type="submit"
                                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-800 transition hover:bg-sky-100"
                                                            aria-label="Cash Advance"
                                                            data-pur-tip-title="Cash Advance"
                                                            data-pur-tip="Accounting releases cash to you to buy the items. Opens the cash advance form for this ATP."
                                                            data-pur-tip-note="Ends at Liquidation Report — liquidate with receipts."
                                                            data-pur-tip-accent="#38bdf8"
                                                        >
                                                            <i data-lucide="banknote" class="h-4 w-4"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                @php
                                                    $pathLabel = \App\Support\ProcurementPaymentPath::label($atp->authority_purchase_payment_path);
                                                    $pathKey = \App\Support\ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path)
                                                        ?? $atp->authority_purchase_payment_path;
                                                @endphp
                                                <a
                                                    href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['selected_atp' => $atp->authority_purchase_id, 'funding_type' => $pathKey, 'fund' => $pathKey]) }}"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                                    title="Create {{ $pathLabel }}"
                                                    aria-label="Create {{ $pathLabel }}"
                                                >
                                                    <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                                </a>
                                            @endif
                                        @else
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="RFC Created" aria-label="RFC Created">
                                                <i data-lucide="circle-check" class="h-4 w-4"></i>
                                            </span>
                                        @endif
                                    @endif

                                    @php
                                        // Hide archive while Supply must release, or Purchaser must create funding.
                                        $atpNeedsHandoff = ! empty($atp->can_release_to_purchaser)
                                            || (
                                                $atp->authority_purchase_status === 'Approved'
                                                && ! empty($atp->released_to_purchaser)
                                                && empty($atp->has_rfc)
                                            );
                                        $canArchiveAtp = ! $atp->authority_purchase_is_archived
                                            && ! $atpNeedsHandoff
                                            && (
                                                $atp->authority_purchase_status === 'Rejected'
                                                || ($atp->authority_purchase_status === 'Approved' && ! empty($atp->has_rfc))
                                            );
                                    @endphp
                                    @if($archiveView)
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.atp.restore', $atp->authority_purchase_id) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50"
                                                title="Restore"
                                                aria-label="Restore"
                                            >
                                                <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @elseif($canArchiveAtp)
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.atp.archive', $atp->authority_purchase_id) }}"
                                            data-pur-confirm="Archive this Authority to Purchase?"
                                            data-pur-confirm-title="Archive ATP"
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
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="file-check-2" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No ATP records found</p>
                                <p class="mt-1 text-sm text-gray-400">Create an ATP or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($atps->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $atps->links() }}
            </div>
        @endif
    </div>



    {{-- ========================================================= --}}
    {{-- CREATE ATP MODAL (teleport to body so overlay covers topbar) --}}
    {{-- ========================================================= --}}

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
        aria-labelledby="atp-create-title"
    >
        <div
            x-on:click.self="createOpen = false; modalFullscreen = false"
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
                            <i data-lucide="file-check-2" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="atp-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Authority to Purchase</h3>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button type="button" @click="createOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                @if(empty($risOptions))
                    <div class="space-y-2 p-6 text-sm text-gray-600">
                        @if(!empty($selectedRisId))
                            <p>
                                This Canvas + RIS already has an Authority to Purchase (or is no longer available for a new ATP).
                                Open it from the ATP list, or go back to <strong>Incoming RIS</strong> for RIS that still need an ATP.
                            </p>
                        @else
                            <p>
                                No approved Canvas + RIS is waiting for ATP.
                                Use <strong>Incoming RIS</strong> after the Purchaser sends an approved request, or use <strong>Print Empty ATP</strong> for a blank paper form.
                            </p>
                        @endif
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="createOpen = false; modalFullscreen = false" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                            Close
                        </button>
                    </div>
                @else
                <form method="POST" action="{{ route(($pp ?? 'purchaser').'.atp.store') }}" x-ref="createForm">
                    @csrf
                    <input type="hidden" name="save_action" value="draft">

                    <div class="bg-slate-100 p-3 md:p-5">
                            @php
                                $oldRisId = old('authority_purchase_ris_id');
                                $oldSupplierId = old('authority_purchase_supplier_id');
                                $selectedRisKey = null;
                                foreach ($risOptions as $opt) {
                                    if ($oldRisId !== null) {
                                        if ((string) $oldRisId === (string) $opt['ris_id']
                                            && (!$opt['split'] || (string) $oldSupplierId === (string) $opt['supplier_id'])) {
                                            $selectedRisKey = $opt['key'];
                                            break;
                                        }
                                    } elseif (!empty($selectedRisId) && (string) $selectedRisId === (string) $opt['ris_id']) {
                                        $selectedRisKey ??= $opt['key'];
                                        if (empty($selectedSupplierId) || (string) $selectedSupplierId === (string) $opt['supplier_id']) {
                                            $selectedRisKey = $opt['key'];
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <div class="mb-4">
                                @php
                                    $selectedRisNumeric = '';
                                    foreach ($risOptions as $opt) {
                                        if ($selectedRisKey === $opt['key']) {
                                            $selectedRisNumeric = $opt['ris_id'];
                                            break;
                                        }
                                    }
                                @endphp
                                <label class="text-xs font-medium text-gray-500">
                                    Approved RIS <span class="text-red-500">*</span>
                                    <span class="font-normal text-gray-400">(required to create)</span>
                                </label>
                                <input type="hidden" name="authority_purchase_ris_id" x-ref="risIdInput" value="{{ old('authority_purchase_ris_id', $selectedRisNumeric) }}">
                                <select
                                    x-ref="risSelect"
                                    required
                                    x-on:change="applyRisPrefill($event.target.selectedOptions[0]?.dataset.key || '')"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm"
                                >
                                    <option value="">Select approved RIS</option>
                                    @foreach($risOptions as $opt)
                                        <option
                                            value="{{ $opt['key'] }}"
                                            data-key="{{ $opt['key'] }}"
                                            data-ris-id="{{ $opt['ris_id'] }}"
                                            data-chip="{{ $opt['chip'] }}"
                                            data-split="{{ $opt['split'] ? '1' : '0' }}"
                                            {{ $selectedRisKey === $opt['key'] ? 'selected' : '' }}
                                        >{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                <div x-show="risSplit && risChoices().length > 1" x-cloak class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="choice in risChoices()" :key="choice.key">
                                        <button
                                            type="button"
                                            x-on:click="chooseRisOption(choice.key)"
                                            x-text="choice.label"
                                            x-bind:class="choice.active ? 'border-[#0025cc] bg-blue-50 text-[#0025cc]' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                            class="rounded-lg border px-3 py-1.5 text-left text-xs font-semibold"
                                        ></button>
                                    </template>
                                </div>
                                <p x-show="risSplit" x-cloak class="mt-1.5 text-xs text-slate-500">
                                    This RIS buys from more than one supplier, so each supplier gets its own ATP. Choose the other item above to create its ATP.
                                </p>
                            </div>

                        @include('partials.authority-to-purchase-paper', [
                            'editable' => true,
                            'atp' => null,
                            'items' => collect(),
                            'suppliers' => $suppliers,
                            'signKey' => 'atp-create',
                            'suggestedAtpFormNumber' => $suggestedAtpFormNumber ?? '0001',
                        ])

                        <div id="purSigSlot-atp-create" class="mt-4 w-full"></div>
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
                @endif
            </div>
        </div>
    </div>
    </template>



    {{-- ========================================================= --}}
    {{-- VIEW ATP MODALS (paper preview, same direction as RIS) --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $items = $atpItems->get($atp->authority_purchase_id, collect());
        @endphp

        <template x-teleport="body">
        <div
            x-show="viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="atp-view-title-{{ $atp->authority_purchase_id }}"
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
                    {{-- Header --}}
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">

                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    x-show="canGoBack"
                                    x-cloak
                                    @click="window.history.back()"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                    data-tooltip="Back"
                                    aria-label="Back to previous page"
                                >
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                </button>
                                <h3 id="atp-view-title-{{ $atp->authority_purchase_id }}" class="text-xl font-semibold text-slate-900">
                                    {{ $atp->authority_purchase_form_number ?: '—' }}
                                </h3>

                                @if(\App\Support\DocumentUrgency::isUrgent('ATP', $atp))
                                    @include('partials.ris-urgency-badge', ['urgent' => true, 'title' => 'Urgent RIS'])
                                @endif
                                @include('accounting.partials.status-badge', [
                                    'status' => \App\Support\RisWorkflow::atpStatusLabel($atp),
                                    'submitted' => $atp->authority_purchase_submitted_at,
                                    'revision' => $atp->authority_purchase_rejection_reason,
                                ])
                            </div>

                            <p class="mt-1 text-sm text-gray-500">
                                RIS: {{ \App\Support\RisWorkflow::formNumber($atp, (int) ($atp->authority_purchase_ris_id ?? 0)) }}
                            </p>
                            @if($atp->authority_purchase_rejection_reason)
                                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    <p>
                                        {{ $atp->authority_purchase_status === 'Rejected' ? 'Rejection reason:' : 'Revision requested:' }}
                                        {{ $atp->authority_purchase_rejection_reason }}
                                    </p>
                                    @include('partials.ris-revision-images', [
                                        'revision' => \App\Support\DocumentRevisionNotes::latest('ATP', $atp->authority_purchase_id),
                                        'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                        'size' => 'sm',
                                    ])
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="printAtp({{ $atp->authority_purchase_id }})"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                data-tooltip="Print ATP"
                                aria-label="Print ATP"
                            >
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            </button>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.atp.export-xlsx', $atp->authority_purchase_id) }}"
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
                                href="{{ route(($pp ?? 'purchaser').'.atp.export-docx', $atp->authority_purchase_id) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300 hover:bg-blue-50"
                                data-tooltip="Export to Word file"
                                aria-label="Export to Word file"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                    <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                    <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                    <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                    <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.32-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                                </svg>
                            </a>
                            @include('purchaser.partials.modal-fullscreen-button')
                            <button type="button" @click="viewOpen = false; modalFullscreen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>

                    </div>

                    @php
                        $atpLineage = \App\Support\DocumentLineage::forAtp((int) $atp->authority_purchase_id);
                        $atpHint = \App\Support\DocumentLineage::reviewHint(
                            \App\Support\RisWorkflow::atpStatusLabel($atp),
                            null,
                            'atp'
                        );
                    @endphp
                    @include('partials.document-lineage', [
                        'lineage' => $atpLineage,
                        'currentType' => 'ATP',
                        'statusHint' => $atpHint,
                    ])

                    {{-- ATP PAPER PREVIEW --}}
                    <div class="bg-slate-100 p-3 md:p-5">
                        @include('partials.authority-to-purchase-paper', [
                            'editable' => false,
                            'atp' => $atp,
                            'items' => $items,
                            'printId' => 'atp-print-'.$atp->authority_purchase_id,
                        ])
                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="px-2 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
                        >
                            Close
                        </button>

                        @if(
                            !$archiveView
                            && !$atp->authority_purchase_submitted_at
                            && $atp->authority_purchase_status === 'Pending'
                            && empty($atp->submits_via_po)
                        )
                            <button
                                type="button"
                                data-atp-submit
                                data-atp-action="{{ route(($pp ?? 'purchaser').'.atp.submit', $atp->authority_purchase_id) }}"
                                data-atp-number="{{ $atp->authority_purchase_form_number ?: ('Draft ATP #'.$atp->authority_purchase_id) }}"
                                data-atp-signature="{{ \App\Support\RisWorkflow::isDrawnSignature((string) ($atp->authority_purchase_received_by_signature ?? '')) ? $atp->authority_purchase_received_by_signature : '' }}"
                                class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                            >
                                Submit to Review
                            </button>
                        @endif

                        @if(!$archiveView && !empty($atp->can_release_to_purchaser))
                            <form method="POST" action="{{ route('supply-officer.atp.release-purchaser', $atp->authority_purchase_id) }}">
                                @csrf
                                <button
                                    type="submit"
                                    data-pur-confirm="Release {{ $atp->authority_purchase_form_number ?: 'ATP #'.$atp->authority_purchase_id }} (with approved Canvas + RIS) back to the Purchaser for Request for Check?"
                                    data-pur-confirm-title="Release to Purchaser"
                                    data-pur-confirm-ok="Release"
                                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                                >
                                    Release to Purchaser
                                </button>
                            </form>
                        @elseif(!$archiveView && $atp->authority_purchase_status === 'Approved' && !empty($atp->released_to_purchaser) && !$atp->has_rfc && !\App\Support\ProcurementPortal::isSupplyOfficerContext())
                            @if(false)
                                <a href="#" class="hidden">PO</a>
                            @elseif(!$atp->has_rfc)
                                @if(empty($atp->authority_purchase_payment_path))
                                    <form
                                        method="POST"
                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                        data-pur-confirm="{{ $directPaymentConfirm($atp) }}"
                                        data-pur-confirm-title="Use Direct Payment?"
                                        data-pur-confirm-ok="Use Direct Payment"
                                    >
                                        @csrf
                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-800 transition hover:bg-blue-100"
                                            data-pur-tip-title="Direct Payment"
                                            data-pur-tip="Accounting pays the supplier directly. Opens the funding request form for this ATP."
                                            data-pur-tip-note="Ends at Receiving Report — no liquidation."
                                            data-pur-tip-accent="#60a5fa"
                                        >Direct Payment</button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                        data-pur-confirm="{{ $cashInstallmentConfirm($atp) }}"
                                        data-pur-confirm-title="Use Cash Installment?"
                                        data-pur-confirm-ok="Use Cash Installment"
                                    >
                                        @csrf
                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::CASH_INSTALLMENT }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-medium text-violet-800 transition hover:bg-violet-100"
                                            data-pur-tip-title="Cash Installment"
                                            data-pur-tip="Accounting pays the supplier in installments. Opens the funding request form for this ATP."
                                            data-pur-tip-note="Ends at Receiving Report — no liquidation."
                                            data-pur-tip-accent="#a78bfa"
                                        >Cash Installment</button>
                                    </form>
                                    <form
                                        method="POST"
                                        action="{{ route(($pp ?? 'purchaser').'.atp.payment-path', $atp->authority_purchase_id) }}"
                                        data-pur-confirm="{{ $cashAdvanceConfirm($atp) }}"
                                        data-pur-confirm-title="Use Cash Advance?"
                                        data-pur-confirm-ok="Use Cash Advance"
                                    >
                                        @csrf
                                        <input type="hidden" name="authority_purchase_payment_path" value="{{ \App\Support\ProcurementPaymentPath::CASH_ADVANCE }}">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-800 transition hover:bg-sky-100"
                                            data-pur-tip-title="Cash Advance"
                                            data-pur-tip="Accounting releases cash to you to buy the items. Opens the cash advance form for this ATP."
                                            data-pur-tip-note="Ends at Liquidation Report — liquidate with receipts."
                                            data-pur-tip-accent="#38bdf8"
                                        >Cash Advance</button>
                                    </form>
                                @else
                                    @php
                                        $pathLabel = \App\Support\ProcurementPaymentPath::label($atp->authority_purchase_payment_path);
                                        $pathKey = \App\Support\ProcurementPaymentPath::normalize($atp->authority_purchase_payment_path)
                                            ?? $atp->authority_purchase_payment_path;
                                    @endphp
                                    <a
                                        href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['selected_atp' => $atp->authority_purchase_id, 'funding_type' => $pathKey, 'fund' => $pathKey]) }}"
                                        class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                    >
                                        Create {{ $pathLabel }}
                                    </a>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
                                    RFC Created
                                </span>
                            @endif
                        @endif
                    </div>

                </div>
            </div>
        </div>
        </template>

    @endforeach

    {{-- PRINT EMPTY ATP MODAL --}}
    <template x-teleport="body">
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-4 md:p-8'"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="atp-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false; modalFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >

                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="atp-empty-title" class="text-lg font-semibold text-gray-900">
                            Print Empty ATP
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">Blank paper only. System ATPs must link an approved RIS.</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button
                            type="button"
                            x-on:click="emptyOpen = false; modalFullscreen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-100 p-3 md:p-5">
                    @include('partials.authority-to-purchase-paper', [
                        'editable' => false,
                        'atp' => null,
                        'items' => collect(),
                        'printId' => 'atp-print-blank',
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
                        href="{{ route(($pp ?? 'purchaser').'.atp.export-blank-xlsx') }}"
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
                        href="{{ route(($pp ?? 'purchaser').'.atp.export-blank-docx') }}"
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
                        @click="printAtp('blank')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-medium text-white hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty ATP
                    </button>
                </div>

            </div>
        </div>
    </div>
    </template>



    {{-- ========================================================= --}}
    {{-- EDIT ATP MODALS (editable paper layout) --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $editItems = $atpItems->get($atp->authority_purchase_id, collect());
        @endphp

        @if(!$atp->authority_purchase_submitted_at && $atp->authority_purchase_status === 'Pending')

            <template x-teleport="body">
            <div
                x-show="editOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedAtp === {{ $atp->authority_purchase_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                role="dialog"
                aria-modal="true"
                aria-labelledby="atp-edit-title-{{ $atp->authority_purchase_id }}"
            >
                <div
                    x-on:click.self="editOpen = false; modalFullscreen = false"
                    class="flex min-h-full w-full justify-center"
                >
                    <div
                        @click.stop
                        class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                        :class="modalFullscreen ? 'pur-modal-is-fullscreen my-0 min-h-[100dvh] max-w-none rounded-none shadow-none' : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
                    >
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <div>
                                <h3 id="atp-edit-title-{{ $atp->authority_purchase_id }}" class="text-xl font-semibold text-slate-900">Edit ATP Draft</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $atp->authority_purchase_form_number ?: '—' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                @include('purchaser.partials.modal-fullscreen-button')
                                <button type="button" @click="editOpen = false; modalFullscreen = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(($pp ?? 'purchaser').'.atp.update', $atp->authority_purchase_id) }}"
                            x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                        >

                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="save">

                            <div class="bg-slate-100 p-3 md:p-5">
                                @include('partials.authority-to-purchase-paper', [
                                    'editable' => true,
                                    'atp' => $atp,
                                    'items' => $editItems,
                                    'suppliers' => $suppliers,
                                    'signKey' => 'atp-'.$atp->authority_purchase_id,
                                ])
                                <div id="purSigSlot-atp-{{ $atp->authority_purchase_id }}" class="mt-4 w-full"></div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                <p class="mr-auto max-w-md text-xs text-gray-500">
                                    Save your changes here (including signature), then use <strong>⋮ → Submit to School Administrator</strong> in the list.
                                </p>
                                <button
                                    type="button"
                                    @click="editOpen = false"
                                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-950"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    onclick="this.form.querySelector('input[name=save_action]').value='save'"
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

@include('purchaser.authority-to-purchase._submit-modal', ['savedSignatures' => $savedSignatures ?? collect()])


<style>
    [x-cloak] {
        display: none !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        .atp-print-active {
            background: #fff !important;
        }
    }
</style>

@include('partials.ris-revision-image-viewer')
@endsection
