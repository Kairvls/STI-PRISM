@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Funding Requests')
@section('page-subtitle', 'Prepare the funding request with ATP, then submit ATP + RFC together from here.')

@section('content')

<script type="application/json" id="rfc-atp-prefill">{!! json_encode($atpPrefill ?? []) !!}</script>
<script type="application/json" id="rfc-batch-items">{!! json_encode($batchRfcItems ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ (($errors->any() && old('request_check_funding_source') && !session('edit_rfc_id')) || !empty($selectedSource) || !empty($openCreate)) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewRfcId) ? 'true' : 'false' }},
        editOpen: {{ session('edit_rfc_id') ? 'true' : 'false' }},
        emptyOpen: false,
        modalFullscreen: false,
        selectedRfc: {{ session('edit_rfc_id') ? (int) session('edit_rfc_id') : (!empty($viewRfcId) ? (int) $viewRfcId : 'null') }},
        atpPrefill: JSON.parse(document.getElementById('rfc-atp-prefill').textContent || '{}'),
        today: '{{ now()->toDateString() }}',
        expandedRfcGroups: {},
        rfcCarouselOpen: false,
        rfcCarouselTitle: '',
        rfcCarouselIds: [],
        rfcCarouselIdx: 0,
        batchOpen: {{ !empty($batchRfcItems) ? 'true' : 'false' }},
        batchItems: JSON.parse(document.getElementById('rfc-batch-items').textContent || '[]'),
        batchIdx: 0,
        batchConfirmOpen: false,
        batchSubmitting: false,
        batchSigUrl: '',
        batchSignerName: @js(auth()->user()->user_full_name ?? 'Purchaser'),

        syncBatchSigPreview() {
            const hidden = document.getElementById('purSigImage-rfc-batch');
            const nameEl = document.getElementById('purSigName-rfc-batch');
            if (hidden) this.batchSigUrl = String(hidden.value || '');
            if (nameEl && nameEl.value) this.batchSignerName = String(nameEl.value);
        },

        toggleRfcGroup(key) {
            this.expandedRfcGroups[key] = !this.expandedRfcGroups[key];
            this.$nextTick(() => {
                if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            });
        },

        openRfcGroupCarousel(title, ids) {
            this.rfcCarouselTitle = title || 'Request Checks';
            this.rfcCarouselIds = Array.isArray(ids) ? ids : [];
            this.rfcCarouselIdx = 0;
            this.rfcCarouselOpen = true;
            this.$nextTick(() => this.renderRfcCarouselSlide());
        },

        renderRfcCarouselSlide() {
            const box = this.$refs.rfcCarouselBody;
            if (!box) return;
            const id = this.rfcCarouselIds[this.rfcCarouselIdx];
            const tpl = id ? document.getElementById('pur-rfc-carousel-sheet-' + id) : null;
            box.innerHTML = tpl ? tpl.innerHTML : '';
            if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
        },

        stepRfcCarousel(delta) {
            if (!this.rfcCarouselIds.length) return;
            const next = this.rfcCarouselIdx + delta;
            if (next < 0 || next >= this.rfcCarouselIds.length) return;
            this.rfcCarouselIdx = next;
            this.$nextTick(() => this.renderRfcCarouselSlide());
        },

        openBatchSubmitConfirm() {
            const form = this.$refs.batchRfcForm;
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            const sig = form ? (form.querySelector('#purSigImage-rfc-batch')?.value || '') : '';
            if (!sig || String(sig).indexOf('data:image/') !== 0) {
                alert('Please draw or select your Purchaser signature before submitting.');
                return;
            }
            this.batchConfirmOpen = true;
        },

        confirmBatchSubmit() {
            const form = this.$refs.batchRfcForm;
            if (!form || this.batchSubmitting) return;
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            this.batchSubmitting = true;
            this.batchConfirmOpen = false;
            form.submit();
        },

        init() {
            this.$watch('viewOpen', (open) => {
                if (!open && window.purDialog) window.purDialog.clearDeepLink(['view_rfc', 'edit_rfc']);
            });
            this.$watch('editOpen', (open) => {
                if (!open && window.purDialog) window.purDialog.clearDeepLink(['view_rfc', 'edit_rfc']);
            });
            if (this.batchOpen && this.batchItems.length > 0) {
                this.bindDocSig('rfc-batch', 'Requested by signature');
                const pollSig = () => {
                    this.syncBatchSigPreview();
                };
                setTimeout(pollSig, 120);
                setTimeout(pollSig, 400);
                document.addEventListener('change', (e) => {
                    if (e.target && (e.target.id === 'purSigImage-rfc-batch' || e.target.id === 'purSigName-rfc-batch')) pollSig();
                }, true);
                document.addEventListener('click', (e) => {
                    if (this.batchOpen && e.target && e.target.closest && e.target.closest('#purSigSlot-rfc-batch')) {
                        setTimeout(pollSig, 60);
                    }
                }, true);
                document.addEventListener('pointerup', (e) => {
                    if (this.batchOpen && e.target && e.target.closest && e.target.closest('#purSigSlot-rfc-batch')) {
                        setTimeout(pollSig, 60);
                    }
                }, true);
            } else if (this.editOpen && this.selectedRfc) {
                this.bindDocSig('rfc-' + this.selectedRfc, 'Requested by signature');
            } else if (this.createOpen) {
                this.bindDocSig('rfc-create', 'Requested by signature');
            }
        },

        openView(id) {
            this.selectedRfc = id;
            this.viewOpen = true;
            this.editOpen = false;
            this.modalFullscreen = false;
        },

        openEdit(id) {
            this.selectedRfc = id;
            this.editOpen = true;
            this.viewOpen = false;
            this.modalFullscreen = false;
            this.bindDocSig('rfc-' + id, 'Requested by signature');
        },

        openCreate() {
            @if(empty($loadFormDatasets))
            window.location.href = @js(route(($pp ?? 'purchaser').'.rfc.index', array_filter([
                'view' => !empty($archiveView) ? 'archive' : null,
                'fund' => $fundFilter ?? null,
                'create' => 1,
            ])));
            return;
            @endif
            this.createOpen = true;
            this.modalFullscreen = false;
            this.bindDocSig('rfc-create', 'Requested by signature');
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
            this.selectedRfc = null;
        },

        guardClose(kind) {
            const form = kind === 'create'
                ? this.$refs.createForm
                : document.querySelector('[data-pur-edit-form=rfc][data-open=1]');
            const finish = () => {
                if (kind === 'create') this.createOpen = false;
                else this.editOpen = false;
                this.modalFullscreen = false;
            };
            if (window.purGuardFormClose) {
                window.purGuardFormClose(form, finish);
                return;
            }
            finish();
        },

        createSubmitConfirmOpen: false,
        createSubmitSummary: { paymentMethod: '', payee: '', amount: '', destination: 'School Administrator' },

        prepareCreateSave(action) {
            const form = this.$refs.createForm || document.getElementById('rfcCreateForm');
            if (!form) return;
            const save = form.querySelector('[name=save_action]');
            if (save) save.value = action;
            form.removeAttribute('data-pur-confirm');
            form.removeAttribute('data-pur-confirm-title');
            form.removeAttribute('data-pur-confirm-ok');
            form.removeAttribute('data-pur-confirm-reviewer-role');
        },

        openCreateSubmitConfirm() {
            const form = this.$refs.createForm || document.getElementById('rfcCreateForm');
            if (!form) return;
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                return;
            }
            const payee = (form.querySelector('[name=request_check_payee]')?.value || '').trim();
            const rawAmount = (form.querySelector('[name=request_check_amount_figures]')?.value || '').trim();
            const numAmount = Number(rawAmount.replace(/[^0-9.\-]/g, ''));
            const amountLabel = rawAmount !== '' && !Number.isNaN(numAmount)
                ? '₱' + numAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : '—';
            const typeMap = {
                direct_payment: 'Direct Payment',
                cash_installment: 'Cash Installment',
                cash_advance: 'Cash Advance',
            };
            const fundingType = (form.querySelector('[name=request_check_funding_type]')?.value || '').trim();
            this.createSubmitSummary = {
                paymentMethod: typeMap[fundingType] || 'Request for Check',
                payee: payee || '—',
                amount: amountLabel,
                destination: 'School Administrator',
            };
            this.createSubmitConfirmOpen = true;
        },

        confirmCreateSubmit() {
            const form = this.$refs.createForm || document.getElementById('rfcCreateForm');
            if (!form) return;
            this.prepareCreateSave('submit');
            if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') {
                window.purchaserDocumentSignature.flush();
            }
            this.createSubmitConfirmOpen = false;
            form.submit();
        },

        applyAtpPrefill(atpId) {
            const data = this.atpPrefill[String(atpId)];
            const form = this.$refs.createForm;
            if (!form || !data) {
                return;
            }
            const payee = form.querySelector('[name=request_check_payee]');
            const amount = form.querySelector('[name=request_check_amount_figures]');
            const purpose = form.querySelector('[name=request_check_particulars_purpose]');
            const date = form.querySelector('[name=request_check_date]');
            if (payee && data.payee) payee.value = data.payee;
            if (amount && data.amount) amount.value = data.amount;
            if (purpose && data.purpose) purpose.value = data.purpose;
            if (date && !date.value) date.value = this.today;
        },

        printRfc(id) {
            const sheetId = 'rfc-print-' + id;
            if (window.purchaserPrintSheet) {
                window.purchaserPrintSheet(sheetId, 'rfc-print-active');
                return;
            }
            const sheet = document.getElementById(sheetId);
            if (sheet) {
                document.querySelectorAll('.rfc-print-sheet').forEach(function (el) {
                    el.classList.remove('rfc-print-active');
                });
                sheet.classList.add('rfc-print-active');
            }
            window.print();
        },
        recordsLoading: false,
        filterError: null,
        searchTimer: null,
        async refreshRfcRecords(url = null) {
            const form = this.$refs.rfcFilterForm;
            if (!form) return;

            const targetUrl = url || form.action;
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');

            let requestUrl = targetUrl;

            if (!url) {
                const query = params.toString();
                requestUrl = query ? `${form.action}?${query}` : form.action;
            }

            const absUrl = new URL(requestUrl, window.location.origin).href;
            const cachedHtml = window.PrismListCache?.get?.(absUrl) || null;

            this.recordsLoading = !cachedHtml;
            this.filterError = null;

            const applyRfcHtml = (html) => {
                const parsed = new DOMParser().parseFromString(html, 'text/html');

                const nextRecords = parsed.querySelector('#rfc-records-section');
                const currentRecords = document.querySelector('#rfc-records-section');

                if (!nextRecords || !currentRecords) {
                    throw new Error('RFC records section was not found.');
                }

                if (window.Alpine?.destroyTree) {
                    try { Alpine.destroyTree(currentRecords); } catch (e) {}
                }

                currentRecords.innerHTML = nextRecords.innerHTML;
                if (window.Alpine) {
                    Alpine.initTree(currentRecords);
                }

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                if (typeof window.bindPageCarousels === 'function') {
                    window.bindPageCarousels();
                }

                const nextUrl = new URL(absUrl, window.location.origin);
                window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
            };

            try {
                if (cachedHtml) {
                    applyRfcHtml(cachedHtml);
                    this.recordsLoading = false;
                    fetch(absUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                        credentials: 'same-origin',
                    })
                        .then((response) => {
                            if (!response.ok) throw new Error('Unable to refresh RFC records.');
                            return response.text();
                        })
                        .then((html) => {
                            window.PrismListCache?.set?.(absUrl, html);
                            if (html !== cachedHtml) {
                                applyRfcHtml(html);
                            }
                        })
                        .catch(() => {});
                    return;
                }

                const response = await fetch(absUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh RFC records.');
                }

                const html = await response.text();
                window.PrismListCache?.set?.(absUrl, html);
                applyRfcHtml(html);
            } catch (error) {
                console.error(error);
                this.filterError = 'Could not refresh records. Please try again.';
            } finally {
                this.recordsLoading = false;
            }
        }
    }"
    x-init="
        if (createOpen) {
            $nextTick(() => {
                bindDocSig('rfc-create', 'Requested by signature');
                if (@js((string) ($selectedSource ?? ''))) applyAtpPrefill(@js((string) ($selectedSource ?? '')));
            });
        }
    "
    @keydown.escape.window="if (document.getElementById('purConfirmOverlay')) return; if (createOpen) { guardClose('create'); } else if (editOpen) { guardClose('edit'); } else { closeAll(); }"
    class="flex flex-col gap-6"
>
    @php
        $fundQuery = !empty($fundFilter) ? ['fund' => $fundFilter] : [];
        $fundLabel = filled($fundFilter ?? null)
            ? \App\Support\ProcurementPaymentPath::label($fundFilter)
            : null;
    @endphp
    <div x-show="filterError" x-cloak class="pur-alert-error" x-text="filterError"></div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="RFC list view">
            <a
                href="{{ route(($pp ?? 'purchaser').'.rfc.index', $fundQuery) }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['view' => 'archive'] + $fundQuery) }}"
                class="pur-tab {{ $archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                Archive
            </a>
        </nav>

        <div class="flex flex-wrap items-center gap-2">
            @include('layouts.partials.export-pdf-button', [
                'exportUrl' => route(($pp ?? 'purchaser').'.rfc.export-pdf', array_filter([
                    'view' => !empty($archiveView) ? 'archive' : null,
                    'search' => request('search') ?: null,
                    'status' => request('status') ?: null,
                    'fund' => request('fund') ?: null,
                ])),
            ])
            @unless($archiveView)
                <button
                    type="button"
                    @click="emptyOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print Empty RFC
                </button>
                <button
                    type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create {{ \App\Support\ProcurementPaymentPath::label($selectedFundingType ?? \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT) }}
                </button>
            @endunless
        </div>
    </div>


    @php
        $rfcSubmittedDate = trim((string) request('date_from', request('date', '')));
        $rfcHasFilters = request()->filled('search')
            || request()->filled('status')
            || $rfcSubmittedDate !== ''
            || request()->filled('date_to');
        $rfcClearUrl = $archiveView
            ? route(($pp ?? 'purchaser').'.rfc.index', ['view' => 'archive'] + $fundQuery)
            : route(($pp ?? 'purchaser').'.rfc.index', $fundQuery);
    @endphp

    <div id="rfc-records-section" class="pur-card relative">
        <div x-cloak x-show="recordsLoading" class="absolute inset-0 z-20 overflow-hidden rounded-[inherit] bg-white">
            @include('partials.skeleton', ['skeletonType' => 'table', 'skeletonRows' => 6, 'skeletonLabel' => 'Loading RFC records'])
        </div>
        @if(!empty($attentionFocus))
            <div class="px-5 pt-5">
                @include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $rfcs->total()])
            </div>
        @endif
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0 shrink">
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            @if($fundLabel)
                                {{ $archiveView ? 'Archived '.$fundLabel : $fundLabel.' Records' }}
                            @else
                                {{ $archiveView ? 'Archived RFC' : 'RFC Records' }}
                            @endif
                        </h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $rfcs->total() }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($fundLabel)
                            {{ $archiveView ? 'Stored '.$fundLabel.' records.' : 'Manage '.$fundLabel.' records.' }}
                        @else
                            {{ $archiveView ? 'Stored RFC records.' : 'Manage RFC records.' }}
                        @endif
                    </p>
                </div>

                <form data-auto-filter
                    method="GET"
                    action="{{ route(($pp ?? 'purchaser').'.rfc.index') }}"
                    x-ref="rfcFilterForm"
                    x-on:submit.prevent="refreshRfcRecords()"
                    class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center xl:ml-auto xl:w-auto xl:flex-nowrap"
                >
                    @if(!empty($attentionFocus))
                        <input type="hidden" name="focus" value="{{ request('focus') }}">
                    @endif
                    @if(!empty($fundFilter))
                        <input type="hidden" name="fund" value="{{ $fundFilter }}">
                    @endif
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
                    @endif
                    <div class="relative shrink-0">
                        <button
                            type="button"
                            x-ref="rfcDateBtn"
                            x-on:click="
                                const input = $refs.rfcDateInput;
                                if (!input) return;
                                try {
                                    if (typeof input.showPicker === 'function') input.showPicker();
                                    else { input.focus(); input.click(); }
                                } catch (e) { input.focus(); }
                            "
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition {{ $rfcSubmittedDate !== '' ? 'border-[#0025cc] bg-blue-50 text-[#0025cc]' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                            title="{{ $rfcSubmittedDate !== '' ? 'Date '.$rfcSubmittedDate.' — click to change' : 'Filter by date' }}"
                            aria-label="Filter by date"
                        >
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                        </button>
                        <input
                            type="date"
                            name="date_from"
                            x-ref="rfcDateInput"
                            value="{{ $rfcSubmittedDate }}"
                            x-on:change="refreshRfcRecords()"
                            class="sr-only"
                            tabindex="-1"
                        >
                        <button
                            type="button"
                            x-on:click="
                                if ($refs.rfcDateInput) $refs.rfcDateInput.value = '';
                                refreshRfcRecords();
                            "
                            class="absolute -right-1.5 -top-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-700 text-[10px] font-bold leading-none text-white shadow {{ $rfcSubmittedDate !== '' ? '' : 'hidden' }}"
                            title="Clear date filter"
                            aria-label="Clear date filter"
                        >
                            ×
                        </button>
                    </div>

                    <div class="relative min-w-0 flex-1 sm:flex-none">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            x-on:input="
                                clearTimeout(searchTimer);
                                searchTimer = setTimeout(() => refreshRfcRecords(), 350);
                            "
                            placeholder="Search RFC, ATP, payee..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-52"
                        >
                    </div>

                    <select
                        name="status"
                        x-on:change="refreshRfcRecords()"
                        class="box-border h-9 shrink-0 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white sm:w-36"
                    >
                        <option value="">All statuses</option>
                        @foreach([
                            'Draft' => 'Draft',
                            'Submitted' => 'Pending',
                            'Approved' => 'Approved',
                            'Ready for Pickup' => 'Ready for Pickup',
                            'Released' => 'Released',
                            'Minor Revision' => 'For revision',
                            'Rejected' => 'Rejected',
                        ] as $statusVal => $statusLabel)
                            <option value="{{ $statusVal }}" {{ request('status') === $statusVal ? 'selected' : '' }}>{{ $statusLabel }}</option>
                        @endforeach
                    </select>

                    @if($rfcHasFilters)
                        <button
                            type="button"
                            x-on:click="
                                $refs.rfcFilterForm.reset();
                                if ($refs.rfcDateInput) $refs.rfcDateInput.value = '';
                                refreshRfcRecords('{{ $rfcClearUrl }}');
                            "
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </button>
                    @endif
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">ATP</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @php $rfcGroups = $rfcGroups ?? collect(); @endphp
                    @forelse($rfcGroups as $rGroup)
                        @php
                            $rgKey = (string) ($rGroup['key'] ?? '');
                            $rgRfcs = collect($rGroup['rfcs'] ?? []);
                            $rgCount = $rgRfcs->count();
                            $rgMulti = $rgCount > 1;
                            $rgReqNo = $rGroup['request_no'] ?? null;
                            $rgDate = $rGroup['date'] ?? null;
                            $rgIds = $rGroup['rfc_ids'] ?? [];
                        @endphp
                        @if($rgMulti)
                            <tr
                                class="cursor-pointer bg-slate-50/70 transition hover:bg-blue-50/40"
                                @click="toggleRfcGroup('{{ $rgKey }}')"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-blue-100 bg-blue-50/70 text-[#0025cc]">
                                            <i data-lucide="layers" class="h-4 w-4"></i>
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900">{{ $rgReqNo ?: 'Grouped Request Checks' }}</p>
                                                <span
                                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold"
                                                    style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"
                                                >{{ $rgCount }} RFCs</span>
                                                @include('partials.ris-urgency-badge', [
                                                    'urgent' => !empty($rGroup['urgent']),
                                                    'size' => 'sm',
                                                    'title' => 'Inherited from linked RIS',
                                                ])
                                            </div>
                                            <p class="mt-0.5 text-[11px] text-slate-500">Total: ₱{{ number_format((float) ($rGroup['total_amount'] ?? 0), 2) }} · Click to expand</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @include('partials.procurement-request-no', ['requestNo' => $rgReqNo, 'inline' => true, 'showLabel' => false])
                                </td>
                                <td class="px-5 py-4 text-xs font-medium text-gray-600">
                                    {{ $rgCount }} ATPs
                                </td>
                                <td class="px-5 py-4 text-xs font-medium text-gray-600">
                                    {{ $rGroup['payee_summary'] ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                    {{ $rgDate ? \Carbon\Carbon::parse($rgDate)->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $rgPending = $rgRfcs->filter(fn ($x) => in_array((string) $x->request_check_status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true))->count();
                                        $rgReleased = $rgRfcs->filter(fn ($x) => !empty($x->funds_released))->count();
                                        $rgApproved = $rgRfcs->filter(fn ($x) => (string) $x->request_check_status === 'Approved' && empty($x->funds_released))->count();
                                    @endphp
                                    <div class="inline-flex flex-col items-start">
                                        @if($rgReleased === $rgCount)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background:#2563eb;"></span>
                                                <span>Released</span>
                                            </span>
                                            <span class="mt-1 text-[11px] font-medium text-slate-500">All {{ $rgCount }} checks released</span>
                                        @elseif($rgPending > 0)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#fffbeb;color:#b45309;border-color:#fde68a;">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background:#d97706;"></span>
                                                <span>Pending</span>
                                            </span>
                                            <span class="mt-1 text-[11px] font-medium text-slate-500">{{ $rgPending }} awaiting School Admin</span>
                                        @elseif($rgApproved > 0)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background:#059669;"></span>
                                                <span>Approved</span>
                                            </span>
                                            <span class="mt-1 text-[11px] font-medium text-slate-500">By SA - Sent to Accounting</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-bold" style="background:#f1f5f9;color:#475569;border-color:#cbd5e1;">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background:#64748b;"></span>
                                                <span>Mixed</span>
                                            </span>
                                            <span class="mt-1 text-[11px] font-medium text-slate-500">Expand to view statuses</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5" @click.stop>
                                        <button
                                            type="button"
                                            @click='openRfcGroupCarousel(@json($rgReqNo ?: "Request Checks"), @json($rgIds))'
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white transition hover:opacity-90"
                                            style="background:#0025cc;color:#ffffff;"
                                            title="View all {{ $rgCount }} RFCs in carousel"
                                            aria-label="View all {{ $rgCount }} RFCs in carousel"
                                        >
                                            <i data-lucide="layers" class="h-4 w-4"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="toggleRfcGroup('{{ $rgKey }}')"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                            :title="expandedRfcGroups['{{ $rgKey }}'] ? 'Collapse RFCs' : 'Expand RFCs'"
                                            :aria-label="expandedRfcGroups['{{ $rgKey }}'] ? 'Collapse RFCs' : 'Expand RFCs'"
                                        >
                                            <svg class="h-4 w-4 transition-transform duration-150" :class="expandedRfcGroups['{{ $rgKey }}'] ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                        @foreach($rgRfcs as $rfc)
                        @php
                            $editable = in_array($rfc->request_check_status, ['Draft', 'Minor Revision'], true) && !$archiveView;
                        @endphp
                        <tr
                            class="{{ $rgMulti ? 'bg-white transition hover:bg-blue-50/20' : 'transition hover:bg-gray-50/70' }}"
                            @if($rgMulti) x-show="!!expandedRfcGroups['{{ $rgKey }}']" x-cloak @endif
                        >
                            <td class="relative px-5 py-4 {{ $rgMulti ? 'pl-14' : '' }}">
                                @if($rgMulti)
                                    <span class="pointer-events-none absolute left-[37px] top-0 w-px bg-slate-200 {{ $loop->last ? 'h-1/2' : 'h-full' }}" aria-hidden="true"></span>
                                    <span class="pointer-events-none absolute left-[37px] top-1/2 h-px w-4 bg-slate-200" aria-hidden="true"></span>
                                @endif
                                <div class="flex items-center gap-3">
                                    @unless($rgMulti)
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                            <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                        </div>
                                    @endunless
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-gray-900">{{ $rfc->request_check_form_number ?: '—' }}</p>
                                            @include('partials.ris-urgency-badge', [
                                                'urgent' => \App\Support\DocumentUrgency::isUrgent('RFC', $rfc),
                                                'size' => 'sm',
                                                'title' => 'Inherited from linked RIS',
                                            ])
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @include('partials.procurement-request-no', ['requestNo' => $rfc->request_check_request_no ?? null, 'inline' => true, 'showLabel' => false])
                                @if(blank($rfc->request_check_request_no ?? null))
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                @if(!empty($rfc->purchase_order_label))
                                    <p class="font-medium text-gray-800">{{ $rfc->purchase_order_label }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ implode(', ', $rfc->linked_atp_labels ?? []) }}</p>
                                @else
                                    {{ $rfc->authority_purchase_form_number ?? '—' }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $rfc->request_check_payee ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $rfc->request_check_date ? \Carbon\Carbon::parse($rfc->request_check_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $purRfcStatus = (string) ($rfc->request_check_status ?? '');
                                    $purRfcBadge = match (true) {
                                        $purRfcStatus === 'Approved' && !empty($rfc->funds_collected) => [
                                            'pill' => 'Collected',
                                            'sub' => 'Check collected',
                                            'bg' => '#f0fdfa',
                                            'fg' => '#0f766e',
                                            'border' => '#99f6e4',
                                            'dot' => '#0d9488',
                                        ],
                                        $purRfcStatus === 'Approved' && !empty($rfc->funds_released) => [
                                            'pill' => 'Released',
                                            'sub' => empty($rfc->has_rr) ? 'Create RR after delivery' : 'RR created',
                                            'bg' => '#eff6ff',
                                            'fg' => '#1d4ed8',
                                            'border' => '#bfdbfe',
                                            'dot' => '#2563eb',
                                        ],
                                        $purRfcStatus === 'Approved' && !empty($rfc->check_ready) => [
                                            'pill' => 'Ready for Pickup',
                                            'sub' => 'Collect check from Accounting',
                                            'bg' => '#fffbeb',
                                            'fg' => '#b45309',
                                            'border' => '#fde68a',
                                            'dot' => '#d97706',
                                        ],
                                        $purRfcStatus === 'Approved' => [
                                            'pill' => 'Approved',
                                            'sub' => 'By SA - Sent to Accounting for check release',
                                            'bg' => '#ecfdf5',
                                            'fg' => '#047857',
                                            'border' => '#a7f3d0',
                                            'dot' => '#059669',
                                        ],
                                        \App\Support\ProcurementDraftLabel::isDraftStatus($purRfcStatus) => [
                                            'pill' => 'Draft',
                                            'sub' => 'In preparation',
                                            'bg' => '#f1f5f9',
                                            'fg' => '#475569',
                                            'border' => '#cbd5e1',
                                            'dot' => '#64748b',
                                        ],
                                        in_array($purRfcStatus, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) => [
                                            'pill' => 'Pending',
                                            'sub' => 'Awaiting School Admin approval',
                                            'bg' => '#fffbeb',
                                            'fg' => '#b45309',
                                            'border' => '#fde68a',
                                            'dot' => '#d97706',
                                        ],
                                        in_array($purRfcStatus, ['Returned', 'For revision', 'Minor Revision', 'Revision', 'Revision required'], true) => [
                                            'pill' => 'Returned',
                                            'sub' => 'For revision',
                                            'bg' => '#fff7ed',
                                            'fg' => '#c2410c',
                                            'border' => '#fed7aa',
                                            'dot' => '#ea580c',
                                        ],
                                        $purRfcStatus === 'Rejected' => [
                                            'pill' => 'Rejected',
                                            'sub' => 'By School Administrator',
                                            'bg' => '#fef2f2',
                                            'fg' => '#dc2626',
                                            'border' => '#fecaca',
                                            'dot' => '#dc2626',
                                        ],
                                        default => null,
                                    };
                                @endphp
                                @if($purRfcBadge)
                                    <div class="inline-flex flex-col items-start">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                            style="background:{{ $purRfcBadge['bg'] }};color:{{ $purRfcBadge['fg'] }};border-color:{{ $purRfcBadge['border'] }};"
                                        >
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $purRfcBadge['dot'] }};"></span>
                                            <span>{{ $purRfcBadge['pill'] }}</span>
                                        </span>
                                        <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">{{ $purRfcBadge['sub'] }}</div>
                                    </div>
                                @else
                                    @include('accounting.partials.status-badge', [
                                        'status' => $rfc->request_check_status,
                                        'doc' => 'rfc',
                                    ])
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                    @php
                                        $rfcShowsCreateRr = !$archiveView
                                            && $rfc->request_check_status === 'Approved'
                                            && empty($rfc->can_send_to_accounting)
                                            && empty($rfc->has_rr)
                                            && !empty($rfc->funds_released);
                                    @endphp
                                    @if($rfcShowsCreateRr)
                                        <button
                                            type="button"
                                            @click="openView({{ $rfc->request_check_id }})"
                                            class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition hover:opacity-90"
                                            style="background:#0025cc;color:#ffffff;"
                                            data-tip="Create RR (after items are delivered)"
                                            data-tip-place="above"
                                            aria-label="Create RR (after items are delivered)"
                                        >
                                            <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                            <span class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" style="background:#f59e0b;"></span>
                                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full ring-2 ring-white" style="background:#d97706;"></span>
                                            </span>
                                        </button>
                                    @else
                                        <button type="button" @click="openView({{ $rfc->request_check_id }})" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="View" aria-label="View"><i data-lucide="eye" class="h-4 w-4"></i></button>
                                    @endif
                                    <button
                                        type="button"
                                        data-share-doc-type="rfc"
                                        data-share-doc-id="{{ (int) $rfc->request_check_id }}"
                                        data-share-doc-label="{{ \App\Support\RequestExplorer::shareUiLabel('rfc', $rfc->request_check_form_number ?? null) }}"
                                        class="js-share-document inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="Share to Messages"
                                        aria-label="Share to Messages"
                                    >
                                        <i data-lucide="share-2" class="h-4 w-4"></i>
                                    </button>
                                    <button type="button" @click="printRfc({{ $rfc->request_check_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rfc->request_check_id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]" title="Edit" aria-label="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        @php
                                            $rfcJointSubmit = ! empty($rfc->must_joint_submit) && ! empty($rfc->joint_submit_atp_id);
                                            $rfcSubmitConfirm = $rfcJointSubmit
                                                ? 'Submit this ATP and funding request together to the School Administrator?'
                                                : ($rfc->request_check_status === 'Minor Revision'
                                                    ? 'Resubmit this Request for Check to the School Administrator?'
                                                    : 'Submit this Request for Check to the School Administrator?');
                                            $rfcSubmitTitle = $rfcJointSubmit
                                                ? 'Submit ATP + RFC'
                                                : ($rfc->request_check_status === 'Minor Revision' ? 'Resubmit RFC' : 'Submit RFC');
                                            $rfcSubmitOk = $rfc->request_check_status === 'Minor Revision' ? 'Resubmit' : 'Submit';
                                            $rfcSubmitTip = $rfcJointSubmit
                                                ? 'Submit ATP + RFC together'
                                                : ($rfc->request_check_status === 'Minor Revision'
                                                    ? 'Resubmit to School Administrator'
                                                    : 'Submit to School Administrator');
                                        @endphp
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.rfc.submit', $rfc->request_check_id) }}"
                                            class="inline"
                                            data-pur-confirm="{{ $rfcSubmitConfirm }}"
                                            data-pur-confirm-title="{{ $rfcSubmitTitle }}"
                                            data-pur-confirm-ok="{{ $rfcSubmitOk }}"
                                            data-pur-confirm-reviewer-role="School Administrator"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border {{ $rfcJointSubmit ? 'border-[#0025cc] bg-white text-[#0025cc] hover:bg-blue-50' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900' }} transition"
                                                title="{{ $rfcSubmitTip }}"
                                                aria-label="{{ $rfcSubmitTip }}"
                                            >
                                                <i data-lucide="send" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!$archiveView && $rfc->request_check_status === 'Approved')
                                        @if(!empty($rfc->can_send_to_accounting))
                                            <form
                                                method="POST"
                                                action="{{ route(($pp ?? 'purchaser').'.rfc.send-to-accounting', $rfc->request_check_id) }}"
                                                class="inline"
                                                data-pur-confirm="Submit approved Canvass + RIS + ATP + RFC to Accounting?"
                                                data-pur-confirm-title="Submit to Accounting"
                                                data-pur-confirm-ok="Submit"
                                                data-pur-confirm-reviewer-role="Accounting"
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                                    title="Submit to Accounting"
                                                    aria-label="Submit to Accounting"
                                                ><i data-lucide="send" class="h-4 w-4"></i></button>
                                            </form>
                                        @elseif(!empty($rfc->sent_to_accounting) && !$rfc->funds_released)
                                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-800" title="{{ !empty($rfc->check_ready) ? 'Check is ready for collection' : 'Accounting already has this package' }}" aria-label="{{ !empty($rfc->check_ready) ? 'Check is ready for collection' : 'Accounting already has this package' }}"><i data-lucide="{{ !empty($rfc->check_ready) ? 'banknote' : 'building-2' }}" class="h-4 w-4"></i></span>
                                        @elseif(!$rfc->has_rr && $rfc->funds_released)
                                            {{-- Create RR button is rendered first in the action row --}}
                                        @elseif(!$rfc->has_rr)
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700" title="Waiting for funds" aria-label="Waiting for funds"><i data-lucide="hourglass" class="h-4 w-4"></i></span>
                                        @else
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="RR Created" aria-label="RR Created"><i data-lucide="circle-check" class="h-4 w-4"></i></span>
                                        @endif
                                    @endif
                                    @php
                                        // Hide archive while send / wait / create-RR actions are still open.
                                        $rfcNeedsHandoff = $rfc->request_check_status === 'Approved' && (
                                            ! empty($rfc->can_send_to_accounting)
                                            || (! empty($rfc->sent_to_accounting) && empty($rfc->funds_released))
                                            || empty($rfc->has_rr)
                                        );
                                        $canArchiveRfc = (
                                            $rfc->request_check_status === 'Rejected'
                                            || ($rfc->request_check_status === 'Approved' && ! empty($rfc->has_rr))
                                        ) && ! $rfcNeedsHandoff;
                                    @endphp
                                    @if($archiveView)
                                        <form method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.restore', $rfc->request_check_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50" title="Restore" aria-label="Restore"><i data-lucide="archive-restore" class="h-4 w-4"></i></button>
                                        </form>
                                    @elseif($canArchiveRfc)
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.rfc.archive', $rfc->request_check_id) }}"
                                            data-pur-confirm="Archive this Request for Check?"
                                            data-pur-confirm-title="Archive RFC"
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
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                    <i data-lucide="receipt-text" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-4 font-medium text-gray-700">No Request for Check records found</p>
                                <p class="mt-1 text-sm text-gray-400">Create an RFC or adjust the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div
            class="border-t border-gray-100 px-5 py-4"
            x-on:pointerenter.capture="
                const link = $event.target.closest('a[href]');
                if (link && window.PrismListCache?.prefetch) window.PrismListCache.prefetch(link.href);
            "
            x-on:click="
                const link = $event.target.closest('a');
                if (!link) return;
                $event.preventDefault();
                refreshRfcRecords(link.href);
            "
        >
            {{ $rfcs->links() }}
        </div>
    </div>

    {{-- Pre-rendered RFC paper templates for instant 0ms carousel switching --}}
    @foreach($rfcs as $rfcSheetRow)
        <template id="pur-rfc-carousel-sheet-{{ (int) $rfcSheetRow->request_check_id }}">
            @include('partials.request-check-paper', [
                'editable' => false,
                'rfc' => $rfcSheetRow,
            ])
        </template>
    @endforeach

    {{-- RFC GROUP VIEW CAROUSEL MODAL --}}
    <template x-teleport="body">
        <div
            x-cloak
            x-show="rfcCarouselOpen"
            @keydown.escape.window="if (rfcCarouselOpen) rfcCarouselOpen = false"
            @click.self="rfcCarouselOpen = false"
            class="fixed inset-0 flex items-center justify-center bg-black/60 p-4"
            style="z-index: 99990;"
        >
            <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.stop>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900" x-text="rfcCarouselTitle"></h3>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="'RFC ' + (rfcCarouselIdx + 1) + ' of ' + rfcCarouselIds.length"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="stepRfcCarousel(-1)"
                            :disabled="rfcCarouselIdx <= 0"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 disabled:opacity-40"
                            title="Previous RFC"
                        >‹</button>
                        <span class="min-w-[70px] text-center text-xs font-semibold tabular-nums text-slate-700" x-text="(rfcCarouselIdx + 1) + ' / ' + rfcCarouselIds.length"></span>
                        <button
                            type="button"
                            @click="stepRfcCarousel(1)"
                            :disabled="rfcCarouselIdx >= rfcCarouselIds.length - 1"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 disabled:opacity-40"
                            title="Next RFC"
                        >›</button>
                        <button type="button" @click="rfcCarouselOpen = false" class="ml-2 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto bg-slate-100 p-4 sm:p-6">
                    <div x-ref="rfcCarouselBody" class="mx-auto w-full max-w-[1000px]"></div>
                </div>
            </div>
        </div>
    </template>

    {{-- BATCH CREATE & SUBMIT RFC CAROUSEL MODAL (from Group Choose Payment on ATP) --}}
    @if(!empty($batchRfcItems))
        <template x-teleport="body">
            <div
                x-cloak
                x-show="batchOpen"
                @keydown.escape.window="if (batchConfirmOpen) { batchConfirmOpen = false; } else if (batchOpen) { batchOpen = false; }"
                @click.self="batchOpen = false"
                class="fixed inset-0 flex items-center justify-center bg-black/60 p-4"
                style="z-index: 99985;"
            >
                <form
                    x-ref="batchRfcForm"
                    method="POST"
                    action="{{ route(($pp ?? 'purchaser').'.rfc.batch-store') }}"
                    @click.stop
                    class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                >
                    @csrf
                    <input type="hidden" name="funding_type" value="{{ $batchFundingType ?? 'cash_advance' }}">
                    <input type="hidden" name="request_check_requested_by" id="purSigName-rfc-batch" value="{{ auth()->user()->user_full_name ?? 'Purchaser' }}">
                    <input type="hidden" name="request_check_requested_by_signature" id="purSigImage-rfc-batch" value="">

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                Create {{ \App\Support\ProcurementPaymentPath::label($batchFundingType ?? 'cash_advance') }} Requests
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500" x-text="'Reviewing ' + (batchIdx + 1) + ' of ' + batchItems.length + ' ATP(s) · Sign once below to submit all together'"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="if (batchIdx > 0) batchIdx--" :disabled="batchIdx <= 0" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40">‹</button>
                            <span class="min-w-[64px] text-center text-xs font-semibold tabular-nums text-slate-700" x-text="(batchIdx + 1) + ' / ' + batchItems.length"></span>
                            <button type="button" @click="if (batchIdx < batchItems.length - 1) batchIdx++" :disabled="batchIdx >= batchItems.length - 1" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40">›</button>
                            <button type="button" @click="batchOpen = false" class="ml-2 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
                        </div>
                    </div>

                    <div class="flex-1 space-y-4 overflow-y-auto bg-slate-100 p-4 sm:p-6">
                        <template x-for="(bItem, bIdx) in batchItems" :key="bItem.atp_id">
                            <div x-show="batchIdx === bIdx" class="mx-auto w-full max-w-[920px] rounded-2xl border border-slate-200 bg-white px-8 py-8 shadow-sm sm:px-12 sm:py-10" style="font-family: Arial, Helvetica, sans-serif;">
                                <input type="hidden" :name="'items[' + bIdx + '][atp_id]'" :value="bItem.atp_id">
                                <input type="hidden" :name="'items[' + bIdx + '][amount]'" :value="bItem.amount">

                                {{-- Top ATP reference pill --}}
                                <div class="mb-3 flex items-center justify-between text-[11px] text-slate-500">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 font-semibold text-[#0025cc]" x-text="bItem.atp_number"></span>
                                    <span x-show="bItem.request_no" class="font-medium text-slate-500" x-text="bItem.request_no"></span>
                                </div>

                                {{-- Formal RFC Paper Header --}}
                                <div class="text-center">
                                    <div class="text-xl font-bold tracking-wide text-black sm:text-2xl">STI COLLEGE- ORMOC, INC.</div>
                                    <div class="mt-1 text-lg font-bold italic tracking-wide text-black sm:text-xl">REQUEST FOR CHECK</div>
                                </div>

                                {{-- Date line (right-aligned) --}}
                                <div class="mt-7 flex items-end justify-end gap-2">
                                    <label class="text-sm font-bold text-black">Date:</label>
                                    <div class="w-48 border-b border-black pb-0.5">
                                        <input
                                            type="date"
                                            :name="'items[' + bIdx + '][date]'"
                                            x-model="bItem.date"
                                            required
                                            class="w-full border-0 bg-transparent px-1 text-sm text-black outline-none focus:bg-blue-50/40"
                                        >
                                    </div>
                                </div>

                                {{-- Payee / Amount / For lined rows --}}
                                <div class="mt-8 space-y-6">
                                    <div class="flex items-end gap-4">
                                        <label class="w-24 shrink-0 text-sm font-bold text-black">Payee:</label>
                                        <div class="flex-1 border-b border-black pb-0.5">
                                            <input
                                                type="text"
                                                :name="'items[' + bIdx + '][payee]'"
                                                x-model="bItem.payee"
                                                required
                                                class="w-full border-0 bg-transparent px-1 text-sm text-black outline-none focus:bg-blue-50/40"
                                            >
                                        </div>
                                    </div>

                                    <div class="flex items-end gap-4">
                                        <label class="w-24 shrink-0 text-sm font-bold text-black">Amount:</label>
                                        <div
                                            class="flex-1 border-b border-black px-1 pb-1 text-sm font-medium tabular-nums text-black"
                                            x-text="'₱' + Number(bItem.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"
                                        ></div>
                                    </div>

                                    <div class="flex items-end gap-4">
                                        <label class="w-24 shrink-0 text-sm font-bold text-black">For:</label>
                                        <div class="flex-1 border-b border-black pb-0.5">
                                            <input
                                                type="text"
                                                :name="'items[' + bIdx + '][purpose]'"
                                                x-model="bItem.purpose"
                                                required
                                                class="w-full border-0 bg-transparent px-1 text-sm text-black outline-none focus:bg-blue-50/40"
                                            >
                                        </div>
                                    </div>

                                    {{-- Full-width continuation line below For: --}}
                                    <div class="border-b border-black pt-3"></div>
                                </div>

                                {{-- Signatory footer (Requested by / Approved by) --}}
                                <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-20">
                                    <div>
                                        <div class="text-sm font-bold text-black">Requested by:</div>
                                        <div class="relative mt-9 flex min-h-[26px] items-end justify-center border-b border-black pb-1">
                                            <img
                                                x-show="batchSigUrl && batchSigUrl.indexOf('data:image/') === 0"
                                                :src="batchSigUrl"
                                                alt="Requested by signature"
                                                class="pointer-events-none absolute -top-5 left-1/2 max-h-14 -translate-x-1/2 object-contain"
                                            >
                                            <span class="relative z-10 text-sm text-black" x-text="batchSignerName"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-black">Approved by:</div>
                                        <div class="mt-9 min-h-[26px] border-b border-black pb-1"></div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Collapsible Purchaser Signature --}}
                        <div class="mx-auto w-full max-w-[920px] rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div id="purSigOverlay-rfc-batch" class="hidden"></div>
                            <div id="purSigSlot-rfc-batch"></div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-white px-6 py-4">
                        <span class="text-xs text-slate-500" x-text="'Total across ' + batchItems.length + ' RFC(s): ₱' + batchItems.reduce((s, x) => s + Number(x.amount || 0), 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="batchOpen = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button
                                type="button"
                                @click="openBatchSubmitConfirm()"
                                class="rounded-lg px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:opacity-95"
                                style="background:#0025cc;color:#ffffff;"
                                x-text="'Submit All for Approval (' + batchItems.length + ')'"
                            ></button>
                        </div>
                    </div>
                </form>
            </div>
        </template>

        {{-- BATCH SUBMIT CONFIRMATION MODAL --}}
        <template x-teleport="body">
            <div x-cloak x-show="batchConfirmOpen" @click.self="batchConfirmOpen = false" class="fixed inset-0 flex items-center justify-center bg-black/55 p-4" style="z-index: 99999;">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                    <h3 class="text-base font-bold text-slate-900" x-text="'Submit ' + batchItems.length + ' Request Check(s) for Approval?'"></h3>
                    <p class="mt-2 text-sm text-slate-600">All <strong x-text="batchItems.length"></strong> Request Checks will be created and sent to the <strong>School Administrator</strong> for approval.</p>
                    <div class="mt-4 max-h-40 space-y-1.5 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
                        <template x-for="bItem in batchItems" :key="bItem.atp_id">
                            <div class="flex justify-between gap-2">
                                <span class="truncate text-slate-600" x-text="bItem.atp_number + ' · ' + bItem.payee"></span>
                                <span class="shrink-0 font-semibold tabular-nums text-slate-900" x-text="'₱' + Number(bItem.amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                            </div>
                        </template>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-2">
                        <button type="button" @click="batchConfirmOpen = false" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" :disabled="batchSubmitting" @click="confirmBatchSubmit()" class="rounded-lg px-4 py-2 text-sm font-semibold text-white hover:opacity-95 disabled:opacity-60" style="background:#0025cc;color:#ffffff;" x-text="batchSubmitting ? 'Submitting...' : 'Confirm & Submit All'"></button>
                    </div>
                </div>
            </div>
        </template>
    @endif

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
        aria-labelledby="rfc-create-title"
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
                            <i data-lucide="receipt-text" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="rfc-create-title" class="text-lg font-semibold tracking-tight text-slate-900">
                                Create {{ \App\Support\ProcurementPaymentPath::label($selectedFundingType ?? \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT) }}
                            </h3>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @include('purchaser.partials.modal-fullscreen-button')
                        <button type="button" @click="guardClose('create')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <form id="rfcCreateForm" method="POST" action="{{ route(($pp ?? 'purchaser').'.rfc.store') }}" enctype="multipart/form-data" x-ref="createForm">
                    @csrf
                    <input type="hidden" name="save_action" value="draft">
                    <input type="hidden" name="request_check_funding_type" value="{{ \App\Support\ProcurementPaymentPath::normalize($selectedFundingType ?? null) ?? \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT }}">
                    <div class="bg-slate-100 p-3 md:p-5">
                        @php
                            $createSource = (string) old('request_check_funding_source', $selectedSource ?? '');
                            $fundingGroups = $fundingGroups ?? [];
                        @endphp
                        @if($eligibleAtps->isEmpty() && empty($fundingGroups))
                            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                No approved ATP is currently available. You can still save this {{ \App\Support\ProcurementPaymentPath::label($selectedFundingType ?? \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT) }} for later (in preparation), but you must link an approved ATP before submitting. Use <strong>Print Empty RFC</strong> for a blank paper form.
                            </div>
                        @else
                            <div class="mb-4">
                                <label class="text-xs font-medium text-gray-500">Fund <span class="text-red-500">*</span> <span class="font-normal text-gray-400">(optional while in preparation · required to submit)</span></label>
                                <select name="request_check_funding_source" x-on:change="applyAtpPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm">
                                    <option value="">Select an approved ATP or Purchase Order</option>
                                    @if(!empty($fundingGroups))
                                        <optgroup label="{{ \App\Support\ProcurementPaymentPath::requiresLiquidation($selectedFundingType ?? null) ? 'Purchase Orders (one Cash Advance per PO)' : 'Purchase Orders (one funding request per supplier)' }}">
                                            @foreach($fundingGroups as $group)
                                                <option value="{{ $group['key'] }}" {{ $createSource === $group['key'] ? 'selected' : '' }}>
                                                    {{ $group['label'] }} · {{ implode(', ', $group['atp_labels']) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($eligibleAtps->isNotEmpty())
                                        <optgroup label="Standalone ATPs">
                                            @foreach($eligibleAtps as $atp)
                                                <option value="atp:{{ $atp->authority_purchase_id }}" {{ $createSource === 'atp:'.$atp->authority_purchase_id ? 'selected' : '' }}>
                                                    {{ $atp->authority_purchase_form_number ?: '—' }}
                                                    @if($atp->ris_form_number) · {{ $atp->ris_form_number }} @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <p class="mt-1 text-[11px] text-gray-500">ATPs approved through a Purchase Order are funded together under their Purchase Order.</p>
                            </div>
                        @endif
                        @include('partials.request-check-paper', [
                            'editable' => true,
                            'rfc' => null,
                            'signKey' => 'rfc-create',
                            'cashAdvancePayee' => \App\Support\ProcurementPaymentPath::requiresLiquidation($selectedFundingType ?? null)
                                ? (auth()->user()->user_full_name ?? '')
                                : null,
                        ])
                        <div id="purSigSlot-rfc-create" class="mt-4 w-full"></div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" @click="guardClose('create')" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950">
                            Cancel
                        </button>
                        <button type="submit" @click="prepareCreateSave('draft')" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-[13px] font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            {{ \App\Support\ProcurementDraftLabel::SAVE }}
                        </button>
                        <button type="button" @click="openCreateSubmitConfirm()" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>

    {{-- SUBMIT RFC CONFIRMATION MODAL --}}
    <template x-teleport="body">
        <div
            x-show="createSubmitConfirmOpen"
            x-cloak
            @keydown.escape.window="if (createSubmitConfirmOpen) createSubmitConfirmOpen = false"
            class="fixed inset-0 flex items-center justify-center bg-black/50 p-4"
            style="z-index: 99999;"
            @click.self="createSubmitConfirmOpen = false"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.stop>
                <h3 class="text-base font-bold text-slate-900">Submit for Approval?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    This Request for Check will be submitted to the <strong class="text-slate-900" x-text="createSubmitSummary.destination"></strong> for approval.
                </p>
                <div class="mt-4 space-y-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Payment Method</span>
                        <span class="font-semibold text-slate-900" x-text="createSubmitSummary.paymentMethod"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Payee</span>
                        <span class="truncate font-semibold text-slate-900" x-text="createSubmitSummary.payee"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Amount</span>
                        <span class="font-semibold tabular-nums text-slate-900" x-text="createSubmitSummary.amount"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Submit To</span>
                        <span class="font-semibold text-[#0025cc]" x-text="createSubmitSummary.destination"></span>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="createSubmitConfirmOpen = false"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmCreateSubmit()"
                        class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white hover:bg-[#001db3]"
                        style="background:#0025cc;color:#ffffff;"
                    >
                        Confirm &amp; Submit
                    </button>
                </div>
            </div>
        </div>
    </template>

    @foreach($rfcs as $rfc)
        @php
            $canEdit = in_array($rfc->request_check_status, ['Draft', 'Minor Revision'], true) && !$archiveView;
        @endphp

        <template x-teleport="body">
        <div
            x-show="viewOpen && selectedRfc === {{ $rfc->request_check_id }}"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
            :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedRfc === {{ $rfc->request_check_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="rfc-view-title-{{ $rfc->request_check_id }}"
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
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 id="rfc-view-title-{{ $rfc->request_check_id }}" class="text-xl font-semibold text-slate-900">{{ $rfc->request_check_form_number }}</h3>
                                @include('partials.ris-urgency-badge', [
                                    'urgent' => \App\Support\DocumentUrgency::isUrgent('RFC', $rfc),
                                    'title' => 'Inherited from linked RIS',
                                ])
                            </div>
                            @if(!empty($rfc->request_check_request_no))
                                <div class="mt-1.5">
                                    @include('partials.procurement-request-no', ['requestNo' => $rfc->request_check_request_no, 'showHint' => true, 'size' => 'md'])
                                </div>
                            @endif
                            <p class="mt-1 text-sm text-gray-500">
                                @if(!empty($rfc->purchase_order_label))
                                    {{ $rfc->purchase_order_label }} · ATPs: {{ implode(', ', $rfc->linked_atp_labels ?? []) }}
                                @else
                                    ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}
                                @endif
                                @if(!empty($rfc->receiving_report_form_number))
                                    · RR: {{ $rfc->receiving_report_form_number }} ({{ $rfc->receiving_report_status }})
                                @endif
                            </p>
                            @if($rfc->request_check_revision_notes)
                                <p class="mt-2 text-sm text-amber-700">Revision notes: {{ $rfc->request_check_revision_notes }}</p>
                                @include('partials.ris-revision-images', [
                                    'revision' => \App\Support\DocumentRevisionNotes::latest('RFC', $rfc->request_check_id),
                                    'routeName' => ($pp ?? 'purchaser').'.document-revision-image',
                                    'size' => 'sm',
                                ])
                            @endif
                            @if($rfc->request_check_rejection_reason)
                                <p class="mt-2 text-sm text-red-700">Rejection: {{ $rfc->request_check_rejection_reason }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                type="button"
                                @click="printRfc({{ $rfc->request_check_id }})"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-gray-50"
                                data-tooltip="Print RFC"
                                aria-label="Print RFC"
                            >
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            </button>
                            <a
                                href="{{ route(($pp ?? 'purchaser').'.rfc.export-xlsx', $rfc->request_check_id) }}"
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
                                href="{{ route(($pp ?? 'purchaser').'.rfc.export-docx', $rfc->request_check_id) }}"
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
                        $rfcIdKey = (int) $rfc->request_check_id;
                        $rfcLineage = ($rfcLineages[$rfcIdKey] ?? null)
                            ?: \App\Support\DocumentLineage::forRfc($rfcIdKey);
                        $rfcHint = \App\Support\DocumentLineage::reviewHint($rfc->request_check_status ?? null, $rfc->request_check_review_stage ?? null, 'rfc');
                    @endphp
                    @include('partials.document-lineage', [
                        'lineage' => $rfcLineage,
                        'currentType' => 'RFC',
                        'statusHint' => $rfcHint,
                    ])
                    <div class="bg-slate-100 p-3 md:p-5">
                        @include('partials.request-check-paper', ['editable' => false, 'rfc' => $rfc, 'printId' => 'rfc-print-'.$rfc->request_check_id])
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            @click="viewOpen = false"
                            class="px-2 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
                        >
                            Close
                        </button>
                        @if(!$archiveView && $rfc->request_check_status === 'Approved')
                            @if(!empty($rfc->can_send_to_accounting))
                                <form
                                    method="POST"
                                    action="{{ route(($pp ?? 'purchaser').'.rfc.send-to-accounting', $rfc->request_check_id) }}"
                                    data-pur-confirm="Submit approved Canvass + RIS + ATP + RFC to Accounting?"
                                    data-pur-confirm-title="Submit to Accounting"
                                    data-pur-confirm-ok="Submit"
                                    data-pur-confirm-reviewer-role="Accounting"
                                >
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                    >Submit to Accounting</button>
                                </form>
                            @elseif($rfc->has_rr)
                                <span class="inline-flex h-10 items-center rounded-lg border border-green-200 bg-green-50 px-4 text-sm font-medium text-green-700">RR Created</span>
                            @elseif(!empty($rfc->sent_to_accounting) && !$rfc->funds_released)
                                <span class="inline-flex h-10 items-center rounded-lg border border-sky-200 bg-sky-50 px-4 text-sm font-medium text-sky-800">Accounting already has this package</span>
                            @elseif(!$rfc->funds_released)
                                <span class="inline-flex h-10 items-center rounded-lg border border-amber-200 bg-amber-50 px-4 text-sm font-medium text-amber-700">{{ \App\Support\ProcurementStatus::ACCOUNTING_NEED_REVISION }}</span>
                            @else
                                <a
                                    href="{{ route(($pp ?? 'purchaser').'.rr.index', ['selected_rfc' => $rfc->request_check_id]) }}"
                                    data-pur-confirm="Create a Receiving Report for {{ $rfc->request_check_form_number ?? ('RFC-'.$rfc->request_check_id) }}? Make sure the items have already been purchased and delivered by the supplier."
                                    data-pur-confirm-title="Create Receiving Report"
                                    data-pur-confirm-ok="Create RR"
                                    class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
                                    style="background:#0025cc;color:#ffffff;"
                                >
                                    <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                    <span>Create RR</span>
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        </template>

        @if($canEdit)
            <template x-teleport="body">
            <div
                x-show="editOpen && selectedRfc === {{ $rfc->request_check_id }}"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
                :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedRfc === {{ $rfc->request_check_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
                role="dialog"
                aria-modal="true"
                aria-labelledby="rfc-edit-title-{{ $rfc->request_check_id }}"
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
                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <h3 id="rfc-edit-title-{{ $rfc->request_check_id }}" class="text-xl font-semibold text-slate-900">Edit {{ $rfc->request_check_form_number }}</h3>
                            <div class="flex shrink-0 items-center gap-1">
                                @include('purchaser.partials.modal-fullscreen-button')
                                <button type="button" @click="guardClose('edit')" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        <form
                            method="POST"
                            action="{{ route(($pp ?? 'purchaser').'.rfc.update', $rfc->request_check_id) }}"
                            enctype="multipart/form-data"
                            data-pur-edit-form="rfc"
                            x-bind:data-open="editOpen && selectedRfc === {{ $rfc->request_check_id }} ? '1' : '0'"
                            x-on:submit="if (window.purchaserDocumentSignature && typeof window.purchaserDocumentSignature.flush === 'function') { window.purchaserDocumentSignature.flush(); }"
                        >
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="request_check_funding_type" value="{{ \App\Support\ProcurementPaymentPath::normalize($rfc->request_check_funding_type ?? ($selectedFundingType ?? null)) ?? \App\Support\ProcurementPaymentPath::DIRECT_PAYMENT }}">
                            <div class="bg-slate-100 p-3 md:p-5">
                                @if($rfc->request_check_authority_purchase_id)
                                    <input type="hidden" name="request_check_authority_purchase_id" value="{{ $rfc->request_check_authority_purchase_id }}">
                                    @if(!empty($rfc->purchase_order_label))
                                        <p class="mb-3 text-sm text-gray-600">{{ $rfc->purchase_order_label }} · ATPs: {{ implode(', ', $rfc->linked_atp_labels ?? []) }}</p>
                                    @else
                                        <p class="mb-3 text-sm text-gray-600">ATP: {{ $rfc->authority_purchase_form_number ?? '—' }}</p>
                                    @endif
                                @elseif(($eligibleAtps ?? collect())->isNotEmpty() || !empty($fundingGroups))
                                    <div class="mb-4">
                                        <label class="text-xs font-medium text-gray-500">Fund <span class="text-red-500">*</span> <span class="font-normal text-gray-400">(required before submit)</span></label>
                                        <select
                                            name="request_check_funding_source"
                                            class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm"
                                        >
                                            <option value="">Select an approved ATP or Purchase Order</option>
                                            @foreach(($fundingGroups ?? []) as $group)
                                                <option value="{{ $group['key'] }}" {{ (string) old('request_check_funding_source') === $group['key'] ? 'selected' : '' }}>
                                                    {{ $group['label'] }} · {{ implode(', ', $group['atp_labels']) }}
                                                </option>
                                            @endforeach
                                            @foreach($eligibleAtps as $atp)
                                                <option value="atp:{{ $atp->authority_purchase_id }}" {{ (string) old('request_check_funding_source') === 'atp:'.$atp->authority_purchase_id ? 'selected' : '' }}>
                                                    {{ $atp->authority_purchase_form_number ?: '—' }}
                                                    @if($atp->ris_form_number) · {{ $atp->ris_form_number }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <input type="hidden" name="request_check_authority_purchase_id" value="">
                                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                        No approved ATP with a matching payment path is available. Link one before submitting to School Administrator.
                                    </div>
                                @endif
                                @include('partials.request-check-paper', [
                                    'editable' => true,
                                    'rfc' => $rfc,
                                    'signKey' => 'rfc-'.$rfc->request_check_id,
                                    'cashAdvancePayee' => \App\Support\ProcurementPaymentPath::requiresLiquidation($rfc->request_check_funding_type ?? null)
                                        ? (auth()->user()->user_full_name ?? '')
                                        : null,
                                ])
                                <div id="purSigSlot-rfc-{{ $rfc->request_check_id }}" class="mt-4 w-full"></div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                <p class="mr-auto max-w-md text-xs text-gray-500">
                                    @if(!empty($rfc->must_joint_submit) && !empty($rfc->joint_submit_atp_id))
                                        Save your funding request here (including signature), then use <strong>Submit ATP + RFC together</strong> (send) in the list to send both to the School Administrator.
                                    @elseif($rfc->request_check_status === 'Minor Revision')
                                        Save your corrections here, then use <strong>Resubmit to School Administrator</strong> in the list.
                                    @else
                                        Save your changes here (including signature), then use the <strong>Submit</strong> (send) button in the list.
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
                                    onclick="this.form.querySelector('input[name=save_action]').value='draft'"
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

    {{-- PRINT EMPTY RFC MODAL --}}
    <template x-teleport="body">
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-[1000] flex items-start justify-center overflow-y-auto bg-black/50"
        :class="modalFullscreen ? 'p-0' : 'p-3 sm:p-4 md:p-6'"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rfc-empty-title"
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
                        <h3 id="rfc-empty-title" class="text-lg font-semibold text-gray-900">Print Empty RFC</h3>
                        <p class="mt-0.5 text-xs text-gray-500">Blank paper only. System RFCs need a linked ATP before submit (in-preparation records may omit it).</p>
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
                    @include('partials.request-check-paper', ['editable' => false, 'rfc' => null, 'printId' => 'rfc-print-blank'])
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
                        href="{{ route(($pp ?? 'purchaser').'.rfc.export-blank-xlsx') }}"
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
                        href="{{ route(($pp ?? 'purchaser').'.rfc.export-blank-docx') }}"
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
                        @click="printRfc('blank')"
                        class="flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2 text-[13px] font-medium text-white hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty RFC
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
        .rfc-print-active { background: #fff !important; }
    }
</style>
@include('partials.ris-revision-image-viewer')
@endsection
