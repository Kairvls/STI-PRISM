@extends('layouts.purchaser-layout')

@section("page-title", "Requisition Issue Slip")

@section("page-subtitle", "Manage Requisition and Issue Slips")

@section("content")

@php
    $oldRisItems = old('ris_items', []);
    $createItemsInit = [];
    for ($i = 0; $i < 8; $i++) {
        $createItemsInit[] = [
            'name_description' => $oldRisItems[$i]['name_description'] ?? '',
            'brand_id' => (string) ($oldRisItems[$i]['brand_id'] ?? ''),
            'supplier_id' => (string) ($oldRisItems[$i]['supplier_id'] ?? ''),
            'uom_id' => (string) ($oldRisItems[$i]['uom_id'] ?? ''),
            'quantity_requested' => $oldRisItems[$i]['quantity_requested'] ?? '',
            'quantity_issued' => $oldRisItems[$i]['quantity_issued'] ?? '',
            'unit_cost' => $oldRisItems[$i]['unit_cost'] ?? '',
        ];
    }
    $viewRisId = request('view_ris') ?: request('ris_id');
    $risPageBoot = [
        'openModal' => $viewRisId ? 'ris-' . $viewRisId : null,
        'createRisModal' => ($errors->any() || request()->filled('replacement_request')),
        'createItems' => $createItemsInit,
        'purposeText' => (string) old('ris_purpose_description', ''),
        'selectedReplacement' => (string) old('ris_procurement_request_id', request('replacement_request', '')),
        'lockedReplacement' => request()->filled('replacement_request') || (string) old('source_locked') === '1',
        'supplierWarnings' => collect($activeSuppliers ?? [])->mapWithKeys(function ($supplier) {
            return [
                (string) $supplier->supplier_id => [
                    'flagged' => (bool) ($supplier->is_blacklisted ?? false),
                    'reason' => (string) ($supplier->supplier_blacklist_reason ?? ''),
                ],
            ];
        })->all(),
        'replacementRequests' => collect($availableReplacementRequests ?? [])->map(function ($request) {
            return [
                'id' => $request->procurement_request_id,
                'report_id' => $request->report_id ?? null,
                'equipment' => $request->equipment_name ?: ($request->report_unlisted_equipment_name ?: 'Unspecified equipment'),
                'asset_tag' => $request->equipment_asset_tag ?? null,
                'room' => $request->room_name ?? 'Unspecified room',
                'problem' => $request->report_problem_description ?? '',
                'reason' => $request->report_replacement_notes ?? '',
            ];
        })->values(),
    ];
@endphp

<script type="application/json" id="ris-page-boot">@json($risPageBoot)</script>

<div
    x-data="{
        ...JSON.parse(document.getElementById('ris-page-boot').textContent),
        editRisModal: null,
        submitRisConfirm: null,
        submitRisSending: false,
        closeEditRis(risId, returnToView = true) {
            this.editRisModal = null;
            if (returnToView) {
                this.openModal = 'ris-' + risId;
            }
        },
        openSubmitRis(id, number, action) {
            this.submitRisSending = false;
            this.submitRisConfirm = { id, number, action };
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        },
        closeSubmitRis() {
            if (this.submitRisSending) return;
            this.submitRisConfirm = null;
        },
        createRisFullscreen: false,
        createAttachmentName: '',
        onCreateAttachmentsChange(event) {
            const file = event.target.files && event.target.files[0];
            this.createAttachmentName = file ? file.name : '';
            this.$nextTick(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        },
        clearCreateAttachments() {
            this.createAttachmentName = '';
            if (this.$refs.createAttachmentsInput) {
                this.$refs.createAttachmentsInput.value = '';
            }
        },
        selectedReplacementData() {
            return this.replacementRequests.find(item => String(item.id) === String(this.selectedReplacement)) || null;
        },
        supplierWarning(supplierId) {
            const entry = (this.supplierWarnings || {})[String(supplierId || '')];
            return entry && entry.flagged ? entry : null;
        },
        replacementPurpose() {
            const item = this.selectedReplacementData();
            if (!item) return '';
            let purpose = 'Replacement of ' + item.equipment;
            if (item.room) purpose += ' in ' + item.room;
            if (item.reason) purpose += '. Reason: ' + item.reason;
            else if (item.problem) purpose += '. Reason: ' + item.problem;
            return purpose;
        },
        applyReplacementPrefill(overwrite = false) {
            const data = this.selectedReplacementData();
            if (!data) return;

            if (this.createItems[0] && (overwrite || !String(this.createItems[0].name_description || '').trim())) {
                this.createItems[0].name_description = data.equipment;
                if (!this.createItems[0].quantity_requested) {
                    this.createItems[0].quantity_requested = 1;
                }
            }

            if (overwrite || !String(this.purposeText || '').trim()) {
                this.purposeText = this.replacementPurpose();
            }
        },
        risItemKey(name) {
            return String(name || '').trim().toLowerCase();
        },
        risSplitInfo(items, index) {
            const key = this.risItemKey(items[index]?.name_description);
            if (!key) return null;

            const groupIndexes = items
                .map((item, i) => this.risItemKey(item.name_description) === key ? i : -1)
                .filter((i) => i >= 0);

            if (groupIndexes.length < 2) return null;

            const first = items[groupIndexes[0]];
            const asked = Number(first.quantity_requested) || 0;
            const allocated = groupIndexes.reduce((sum, i) => sum + (Number(items[i].quantity_issued) || 0), 0);

            return {
                asked,
                allocated,
                remaining: asked - allocated,
                isDuplicate: groupIndexes[0] !== index,
                overflow: asked > 0 && allocated > asked,
                label: String(first.name_description || '').trim()
            };
        },
        risHasOverflow(items) {
            return items.some((_, index) => this.risSplitInfo(items, index)?.overflow);
        },
        copySplitUom(items, index) {
            const info = this.risSplitInfo(items, index);
            if (!info || !info.isDuplicate) return;

            const key = this.risItemKey(items[index].name_description);
            const first = items.find((item) => this.risItemKey(item.name_description) === key);
            if (!first) return;

            if (!items[index].uom_id && first.uom_id) {
                items[index].uom_id = first.uom_id;
            }
            if (!items[index].brand_id && first.brand_id) {
                items[index].brand_id = first.brand_id;
            }
        },
        formatDateInput(event) {
            const el = event.target;
            const digits = String(el.value || '').replace(/\D/g, '').slice(0, 8);
            let formatted = digits;
            if (digits.length > 4) {
                formatted = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            } else if (digits.length > 2) {
                formatted = `${digits.slice(0, 2)}/${digits.slice(2)}`;
            }
            el.value = formatted;
        },
        init() {
            this.applyReplacementPrefill(false);
            this.$watch('selectedReplacement', () => {
                this.applyReplacementPrefill(true);
            });
        },
        recordsLoading: false,
        filterError: null,
        getRisFilterForm() {
            return this.$refs.risFilterForm || document.getElementById('ris-filter-form');
        },
        clearRisFilters() {
            const form = this.getRisFilterForm();
            if (form) {
                form.querySelectorAll('input[name], select[name]').forEach((el) => {
                    if (el.tagName === 'SELECT') {
                        el.selectedIndex = 0;
                    } else {
                        el.value = '';
                    }
                });
            }
            this.refreshRisRecords(@js(route('purchaser.ris.index')));
        },
        async refreshRisRecords(url = null) {
            const form = this.getRisFilterForm();
            if (!form && !url) return;

            let requestUrl = url || form.action;

            if (!url && form) {
                const params = new URLSearchParams(new FormData(form));
                params.delete('page');
                const query = params.toString();
                requestUrl = query ? `${form.action}?${query}` : form.action;
            }

            this.recordsLoading = true;
            this.filterError = null;
            this.openModal = null;
            this.editRisModal = null;

            try {
                const response = await fetch(requestUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh RIS records.');
                }

                const html = await response.text();
                const parsed = new DOMParser().parseFromString(html, 'text/html');

                const nextRecords = parsed.querySelector('#ris-records-section');
                const currentRecords = document.querySelector('#ris-records-section');
                const nextModals = parsed.querySelector('#ris-modals-section');
                const currentModals = document.querySelector('#ris-modals-section');

                if (!nextRecords || !currentRecords) {
                    throw new Error('RIS records section was not found.');
                }

                if (window.Alpine?.destroyTree) {
                    Alpine.destroyTree(currentRecords);
                    if (currentModals) {
                        Alpine.destroyTree(currentModals);
                    }
                }

                currentRecords.innerHTML = nextRecords.innerHTML;

                if (nextModals && currentModals) {
                    currentModals.innerHTML = nextModals.innerHTML;
                }

                if (window.Alpine) {
                    Alpine.initTree(currentRecords);
                    if (currentModals) {
                        Alpine.initTree(currentModals);
                    }
                }

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                const nextUrl = new URL(requestUrl, window.location.origin);
                window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
            } catch (error) {
                console.error(error);
                this.filterError = 'Could not refresh records. Please try again.';
            } finally {
                this.recordsLoading = false;
            }
        }
    }"
>

    <div x-show="filterError" x-cloak class="pur-alert-error" x-text="filterError"></div>

    @if(!empty($replacementSourceError))
        <div class="pur-alert-error">
            {{ $replacementSourceError }}
        </div>
    @endif

    <div class="mb-7 flex flex-wrap justify-end gap-2">
        <button
            type="button"
            x-on:click="openModal = 'empty-ris'"
            class="px-4 py-2.5 rounded-lg text-gray-700 text-[13px] bg-gray-200/80 font-medium hover:bg-gray-200"
        >
            Print Empty RIS
        </button>

        <button
            type="button"
            x-on:click="lockedReplacement = false; selectedReplacement = ''; createRisFullscreen = false; createRisModal = true"
            class="px-4 py-2.5 bg-[#0025cc] rounded-lg text-white text-[13px] font-medium hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
            Create RIS
        </button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">

        <a
            href="{{ route('purchaser.ris.index', ['status' => 'Draft']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Draft</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($risSummary['draft']) }}
                    </p>
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

        <a
            href="{{ route('purchaser.ris.index', ['status' => 'In Review']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">In Review</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($risSummary['submitted']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <i data-lucide="clock-3" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Submitted and under admin review</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.ris.index', ['status' => 'Approved']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($risSummary['approved']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Approved and ready for ATP</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.ris.index', ['status' => 'Rejected']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($risSummary['rejected']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <i data-lucide="circle-x" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Returned or declined RIS records</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

    </div>

    {{-- PRINT EMPTY RIS MODAL --}}

    <div
        x-cloak
        x-show="openModal === 'empty-ris'"
        x-on:keydown.escape.window="openModal = null"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(openModal === 'empty-ris', $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ris-empty-title"
    >
        <div
            x-on:click.self="openModal = null"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto w-full max-w-6xl rounded-xl bg-white shadow-2xl">

                {{-- MODAL HEADER --}}
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="ris-empty-title" class="text-lg font-semibold text-gray-900">
                            Print Empty RIS
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Original blank Requisition and Issue Slip format.
                        </p>
                    </div>

                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="overflow-x-auto bg-gray-100 p-5 md:p-8">

                    <div
                        id="print-empty-ris-content"
                        class="ris-original-form mx-auto bg-white text-black"
                    >

                        <div class="ris-document-header">

                            <div class="ris-school-name">
                                STI COLLEGE - ORMOC, INC.
                            </div>

                            <div class="ris-document-title">
                                REQUISITION AND ISSUE SLIP
                            </div>

                            {{-- RIS NUMBER --}}
                            <div class="ris-number-area">
                                <span class="ris-number-label">No.</span>
                                <span class="ris-number-line"></span>
                            </div>

                        </div>

                        <table class="ris-items-table">

                            <thead>

                                <tr>

                                    {{-- ITEM --}}
                                    <th
                                        rowspan="2"
                                        class="ris-item-column"
                                    >
                                        ITEM
                                    </th>

                                    {{-- BRAND --}}
                                    <th
                                        rowspan="2"
                                        class="ris-brand-column"
                                    >
                                        BRAND
                                    </th>

                                    {{-- UNIT --}}
                                    <th
                                        rowspan="2"
                                        class="ris-unit-column"
                                    >
                                        UNIT
                                    </th>

                                    {{-- SUPPLIER --}}
                                    <th
                                        rowspan="2"
                                        class="ris-supplier-column"
                                    >
                                        SUPPLIER
                                    </th>

                                    {{-- QUANTITY --}}
                                    <th
                                        colspan="2"
                                        class="ris-quantity-header"
                                    >
                                        QUANTITY
                                    </th>

                                    {{-- UNIT COST --}}
                                    <th
                                        rowspan="2"
                                        class="ris-unit-cost-column"
                                    >
                                        UNIT COST
                                    </th>

                                    {{-- AMOUNT --}}
                                    <th
                                        rowspan="2"
                                        class="ris-amount-column"
                                    >
                                        AMOUNT
                                    </th>

                                </tr>

                                <tr>

                                    <th class="ris-requested-column">
                                        REQUESTED
                                    </th>

                                    <th class="ris-issued-column">
                                        ISSUED
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                {{-- ORIGINAL FORM HAS 8 BLANK ROWS --}}
                                @for($row = 0; $row < 8; $row++)
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor

                            </tbody>

                        </table>

                        <div class="ris-purpose-area">

                            <div class="ris-purpose-label">
                                PURPOSE
                            </div>

                            <div class="ris-purpose-line-row">
                                <div class="ris-purpose-spacer"></div>
                                <div class="ris-purpose-line"></div>
                            </div>

                        </div>

                        <div class="ris-signatures">

                            {{-- REQUESTED BY --}}
                            <div class="ris-signature-column">

                                <div class="ris-signature-label">
                                    Requested by:
                                </div>

                                <div class="ris-signature-line"></div>

                                <div class="ris-date-label">
                                    Date:
                                </div>

                                <div class="ris-date-line"></div>

                            </div>

                            {{-- APPROVED BY --}}
                            <div class="ris-signature-column">

                                <div class="ris-signature-label">
                                    Approved by:
                                </div>

                                <div class="ris-signature-line"></div>

                                <div class="ris-date-label">
                                    Date:
                                </div>

                                <div class="ris-date-line"></div>

                            </div>

                            {{-- ISSUED BY --}}
                            <div class="ris-signature-column">

                                <div class="ris-signature-label">
                                    Issued by:
                                </div>

                                <div class="ris-signature-line"></div>

                                <div class="ris-date-label">
                                    Date:
                                </div>

                                <div class="ris-date-line"></div>

                            </div>

                            {{-- RECEIVED BY --}}
                            <div class="ris-signature-column">

                                <div class="ris-signature-label">
                                    Received by:
                                </div>

                                <div class="ris-signature-line"></div>

                                <div class="ris-date-label">
                                    Date:
                                </div>

                                <div class="ris-date-line"></div>

                            </div>

                        </div>

                    </div>
                </div>

                {{-- MODAL ACTIONS --}}
                <div class="print-hidden flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">

                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="px-2 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                    >
                        Cancel
                    </button>

                    <a
                        href="{{ route('purchaser.ris.export-blank-xlsx') }}"
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
                        href="{{ route('purchaser.ris.export-blank-docx') }}"
                        data-tooltip="Export to Word file"
                        aria-label="Export to Word file"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                            <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                            <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                            <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                            <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.32-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                        </svg>
                    </a>

                    <button
                        type="button"
                        onclick="printRis('print-empty-ris-content')"
                        class="px-4 py-2 bg-[#0025cc] rounded-lg text-white text-[13px]  flex items-center justify-center gap-2 font-medium hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty RIS
                    </button>

                </div>

            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }
        /* Main physical RIS sheet */
        .ris-original-form {
            width: 100%;
            max-width: 1095px;
            min-height: 845px;
            border: 2px solid #1f2937;
            padding: 26px 24px 24px 24px;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }

        .ris-document-header {
            position: relative;
            height: 120px;
            text-align: center;
        }

        .ris-school-name {
            font-size: 19px;
            line-height: 1.2;
            font-weight: 700;
        }

        .ris-document-title {
            margin-top: 9px;
            font-size: 15px;
            line-height: 1.2;
            font-weight: 700;
        }

        .ris-number-area {
            position: absolute;
            right: 0;
            bottom: 18px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .ris-number-label {
            font-size: 15px;
            font-weight: 600;
        }

        .ris-number-line {
            display: block;
            width: 160px;
            border-bottom: 1px solid #1f2937;
        }

        .ris-items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .ris-items-table th,
        .ris-items-table td {
            border: 1px solid #1f2937;
        }

        .ris-items-table th {
            padding: 9px 5px;
            vertical-align: middle;
            text-align: center;
            font-size: 12px;
            line-height: 1.2;
            font-weight: 700;
        }

        .ris-items-table tbody td {
            height: 45px;
            padding: 4px 6px;
            font-size: 12px;
        }

        /* Column widths matching the original (with brand + unit + supplier) */
        .ris-item-column {
            width: 20%;
        }

        .ris-brand-column {
            width: 10%;
        }

        .ris-unit-column {
            width: 7%;
        }

        .ris-supplier-column {
            width: 14%;
        }

        .ris-quantity-header {
            width: 20%;
        }

        .ris-requested-column {
            width: 10%;
            font-size: 11px !important;
        }

        .ris-issued-column {
            width: 10%;
            font-size: 11px !important;
        }

        .ris-unit-cost-column {
            width: 15%;
        }

        .ris-amount-column {
            width: 15%;
        }

        .ris-purpose-area {
            margin-top: 31px;
        }

        .ris-purpose-label {
            font-size: 13px;
            font-weight: 700;
        }

        .ris-purpose-line-row {
            display: flex;
            margin-top: 29px;
        }

        .ris-purpose-spacer {
            width: 80px;
            flex-shrink: 0;
        }

        .ris-purpose-line {
            flex: 1;
            border-bottom: 1px solid #1f2937;
        }

        .ris-signatures {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 32px;
            margin-top: 40px;
        }

        .ris-signature-column {
            min-width: 0;
        }

        .ris-signature-label {
            font-size: 12px;
            color: #374151;
        }

        .ris-signature-line {
            height: 49px;
            border-bottom: 1px solid #1f2937;
        }

        .ris-date-label {
            margin-top: 16px;
            font-size: 12px;
            color: #374151;
        }

        .ris-date-line {
            height: 31px;
            border-bottom: 1px solid #1f2937;
        }

        /* Smaller screen preview only */
        @media (max-width: 768px) {
            .ris-original-form {
                min-width: 900px;
            }
        }


        /* Values shown inside the physical RIS lines */
        .ris-value-line {
            display: flex;
            align-items: flex-end;
            min-height: 31px;
            padding: 0 6px 4px;
            font-size: 12px;
            line-height: 1.35;
        }

        .ris-number-line.ris-value-line {
            justify-content: center;
            min-height: 24px;
        }

        .ris-signature-line.ris-value-line,
        .ris-date-line.ris-value-line {
            justify-content: center;
            text-align: center;
        }

        /* Edit RIS inputs stay inside the exact physical form */
        .ris-number-input,
        .ris-signature-input,
        .ris-date-input,
        .ris-purpose-input {
            border: 0;
            border-bottom: 1px solid #1f2937;
            border-radius: 0;
            background: transparent;
            outline: none;
            box-shadow: none;
        }

        .ris-number-input {
            width: 160px;
            padding: 2px 6px;
            text-align: center;
            font-size: 12px;
        }

        .ris-cell-input {
            width: 100%;
            height: 36px;
            border: 0;
            background: transparent;
            padding: 4px 6px;
            font-size: 12px;
            outline: none;
            box-shadow: none;
        }

        .ris-edit-item-cell {
            display: block;
            min-width: 0;
        }

        .ris-edit-item-cell select.ris-cell-input {
            height: 28px;
            border-top: 1px solid #e5e7eb;
        }

        .ris-item-row {
            position: relative;
        }

        .ris-item-row > td {
            position: relative;
            overflow: visible;
        }

        .ris-row-delete-hit {
            position: absolute;
            inset: 0;
            z-index: 5;
            margin: 0;
            padding: 0;
            border: 0;
            background: rgba(15, 23, 42, 0.62);
            opacity: 0;
            pointer-events: none;
            cursor: pointer;
            transition: opacity 0.12s ease;
        }

        .ris-row-delete-x {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 6;
            width: 384.615%; /* ITEM col is 26% of row */
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.12s ease;
        }

        .ris-item-row:hover .ris-row-delete-hit:not(:disabled),
        .ris-item-row:hover .ris-row-delete-x {
            opacity: 0;
        }

        .ris-items-table.ris-delete-mode .ris-item-row:hover .ris-row-delete-hit:not(:disabled),
        .ris-items-table.ris-delete-mode .ris-item-row:hover .ris-row-delete-x {
            opacity: 1;
        }

        .ris-items-table.ris-delete-mode .ris-item-row:hover .ris-row-delete-hit:not(:disabled) {
            pointer-events: auto;
        }

        .ris-items-table.ris-delete-mode .ris-item-row:focus-within .ris-row-delete-hit,
        .ris-items-table.ris-delete-mode .ris-item-row:focus-within .ris-row-delete-x {
            opacity: 0;
            pointer-events: none;
        }

        .ris-row-delete-hit:disabled,
        .ris-item-row:has(.ris-row-delete-hit:disabled) .ris-row-delete-x {
            display: none;
        }

        .ris-row-delete-x svg {
            width: 100%;
            height: 100%;
            overflow: visible;
            display: block;
        }

        .ris-row-delete-x svg line {
            stroke: #ffffff;
            stroke-width: 0.9;
            vector-effect: non-scaling-stroke;
        }

        .ris-edit-add-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 10px;
        }

        .ris-edit-add-row button.ris-add-item-btn {
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #fff;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .ris-delete-mode-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            user-select: none;
        }

        .ris-delete-mode-toggle span {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        .ris-delete-mode-toggle span.is-active {
            color: #0f172a;
        }

        .ris-delete-mode-switch {
            position: relative;
            width: 36px;
            height: 20px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #e2e8f0;
            padding: 0;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .ris-delete-mode-switch.is-on {
            background: #0f172a;
            border-color: #0f172a;
        }

        .ris-delete-mode-switch::after {
            content: '';
            position: absolute;
            top: 1px;
            left: 1px;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.2);
            transition: transform 0.15s ease;
        }

        .ris-delete-mode-switch.is-on::after {
            transform: translateX(16px);
        }

        .ris-purpose-input {
            flex: 1;
            min-height: 42px;
            resize: none;
            padding: 4px 6px;
            font-size: 12px;
        }

        .ris-signature-input {
            width: 100%;
            height: 49px;
            padding: 22px 4px 4px;
            text-align: center;
            font-size: 12px;
            color: #020617;
        }

        .ris-signature-input::placeholder {
            color: #94a3b8;
        }

        .ris-date-input {
            width: 100%;
            height: 31px;
            padding: 4px;
            text-align: center;
            font-size: 11px;
            color: #020617;
        }

        .ris-date-input::placeholder {
            color: #020617;
            opacity: 1;
        }

        .ris-readonly-value {
            color: #94a3b8;
            cursor: not-allowed;
            user-select: none;
        }
        .ris-signature-line {
            position: relative;
            overflow: hidden;
        }

        .ris-signature-line img {
            max-width: 100%;
            max-height: 38px;
            object-fit: contain;
            display: block;
            margin: auto;
        }

        .ris-signature-line span {
            display: block;
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

    </style>
    <div
        x-cloak
        x-show="createRisModal"
        x-transition.opacity
        x-on:keydown.escape.window="createRisModal = false; createRisFullscreen = false"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50"
        x-bind:class="createRisFullscreen ? 'p-0' : 'p-4 md:p-8'"
        x-effect="window.purDialog && window.purDialog.sync(createRisModal, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ris-create-title"
    >
        <div
            x-on:click.self="createRisModal = false; createRisFullscreen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div
                class="w-full bg-white transition-[max-width,border-radius,margin] duration-200"
                x-bind:class="createRisFullscreen
                    ? 'my-0 min-h-full max-w-none rounded-none shadow-none'
                    : 'my-auto max-w-5xl rounded-xl shadow-2xl'"
            >
                <form method="POST" action="{{ route('purchaser.ris.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="save_action" value="draft">

                    {{-- HEADER --}}
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                        <div class="min-w-0">
                            <h3 id="ris-create-title" class="text-lg font-semibold tracking-tight text-gray-950">
                                Create Requisition and Issue Slip
                            </h3>
                            <p class="mt-0.5 text-sm text-gray-500">
                                <span x-show="lockedReplacement && selectedReplacement">Linked to an approved replacement request.</span>
                                <span x-show="!lockedReplacement">Fill out the RIS form below.</span>
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                type="button"
                                x-on:click="createRisFullscreen = !createRisFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border-0 bg-transparent text-gray-400 shadow-none outline-none ring-0 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-0"
                                x-bind:aria-label="createRisFullscreen ? 'Exit full screen' : 'Full screen'"
                                x-bind:data-tooltip="createRisFullscreen ? 'Exit full screen' : 'Full screen'"
                            >
                                <i x-show="!createRisFullscreen" data-lucide="maximize-2" class="h-4 w-4"></i>
                                <i x-show="createRisFullscreen" data-lucide="minimize-2" class="h-4 w-4" style="display: none;"></i>
                            </button>
                            <button
                                type="button"
                                x-on:click="createRisModal = false; createRisFullscreen = false"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border-0 bg-transparent text-gray-400 shadow-none outline-none ring-0 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-0"
                                aria-label="Close"
                            >
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- LOCKED SOURCE --}}
                    <div x-show="lockedReplacement && selectedReplacement" class="border-b border-gray-100 bg-slate-50 px-5 py-4 md:px-6">
                        <input type="hidden" name="source_locked" value="1" x-bind:disabled="!(lockedReplacement && selectedReplacement)">
                        <input type="hidden" name="ris_procurement_request_id" x-bind:value="selectedReplacement" x-bind:disabled="!(lockedReplacement && selectedReplacement)">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <i data-lucide="link-2" class="h-4 w-4"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Source Replacement Request</p>
                                    <p class="mt-0.5 truncate text-sm font-semibold text-slate-950">
                                        Request #<span x-text="selectedReplacement"></span>
                                        <span class="font-normal text-slate-500" x-show="selectedReplacementData()">
                                            · <span x-text="selectedReplacementData()?.equipment"></span>
                                            <span x-show="selectedReplacementData()?.room"> · <span x-text="selectedReplacementData()?.room"></span></span>
                                        </span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Approved
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                        <i data-lucide="lock" class="h-3 w-3"></i>
                                        Locked
                                    </span>
                                </div>
                            </div>
                            <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Equipment</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedReplacementData()?.equipment"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Asset Tag</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedReplacementData()?.asset_tag || 'Not specified'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Location</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedReplacementData()?.room"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Original Report</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900">#<span x-text="selectedReplacementData()?.report_id || 'N/A'"></span></p>
                                </div>
                            </div>
                            <div class="grid gap-3 border-t border-slate-100 px-4 py-3 lg:grid-cols-2" x-show="selectedReplacementData()?.problem || selectedReplacementData()?.reason">
                                <div x-show="selectedReplacementData()?.problem">
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Reported Problem</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600" x-text="selectedReplacementData()?.problem"></p>
                                </div>
                                <div x-show="selectedReplacementData()?.reason">
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Replacement Reason</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600" x-text="selectedReplacementData()?.reason"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SELECTABLE SOURCE --}}
                    <div x-show="!lockedReplacement" class="border-b border-gray-100 bg-slate-50 px-5 py-4 md:px-6">
                        <label class="mb-2 block text-sm font-medium text-gray-900">Source Replacement Request</label>
                        <select
                            x-bind:name="lockedReplacement ? null : 'ris_procurement_request_id'"
                            x-model="selectedReplacement"
                            x-bind:disabled="lockedReplacement"
                            class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:ring-2 focus:ring-slate-100"
                        >
                            <option value="">Manual RIS / No replacement request</option>
                            @foreach($availableReplacementRequests as $replacementRequest)
                                @php
                                    $replacementEquipment = $replacementRequest->equipment_name
                                        ?: $replacementRequest->report_unlisted_equipment_name
                                        ?: 'Unspecified equipment';
                                @endphp
                                <option
                                    value="{{ $replacementRequest->procurement_request_id }}"
                                    {{ (string) old('ris_procurement_request_id', request('replacement_request')) === (string) $replacementRequest->procurement_request_id ? 'selected' : '' }}
                                >
                                    Request #{{ $replacementRequest->procurement_request_id }}
                                    • {{ $replacementEquipment }}
                                    @if($replacementRequest->room_name)
                                        • {{ $replacementRequest->room_name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Select an approved replacement request, or leave this blank for a manual RIS.</p>

                        <div x-show="selectedReplacementData()" class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Replacement Request Details</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-950">Request #<span x-text="selectedReplacement"></span></p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Approved
                                </span>
                            </div>
                            <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Equipment</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedReplacementData()?.equipment"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Asset Tag</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedReplacementData()?.asset_tag || 'Not specified'"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Location</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900" x-text="selectedReplacementData()?.room"></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Original Report</p>
                                    <p class="mt-1 text-sm font-medium text-slate-900">#<span x-text="selectedReplacementData()?.report_id || 'N/A'"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FLUID RIS DOCUMENT --}}
                    <div class="bg-slate-100 p-3 md:p-5">
                        <div class="w-full border-2 border-gray-800 bg-white p-3 sm:p-5">
                            <div class="relative mb-4 text-center sm:mb-5">
                                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-900 sm:text-lg">STI COLLEGE - ORMOC, INC.</h2>
                                <h3 class="mt-1 text-xs font-bold uppercase text-gray-900 sm:text-base">REQUISITION AND ISSUE SLIP</h3>
                                <div class="mt-3 flex justify-end sm:mt-4">
                                    <div class="flex items-end gap-2">
                                        <label class="text-xs font-medium sm:text-sm">No.</label>
                                        <input
                                            type="text"
                                            name="ris_form_number"
                                            value="{{ old('ris_form_number') }}"
                                            inputmode="numeric"
                                            pattern="\d{8}"
                                            maxlength="8"
                                            title="Enter exactly 8 digits"
                                            x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 8)"
                                            class="w-28 border-0 border-b border-gray-800 bg-transparent px-1 py-1 text-xs outline-none focus:ring-0 sm:w-40 sm:text-sm"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="w-full">
                                <table class="w-full table-fixed border-collapse border border-gray-800">
                                    <colgroup>
                                        <col style="width:18%">
                                        <col style="width:10%">
                                        <col style="width:9%">
                                        <col style="width:15%">
                                        <col style="width:9%">
                                        <col style="width:9%">
                                        <col style="width:15%">
                                        <col style="width:15%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Item</th>
                                            <th rowspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Brand</th>
                                            <th rowspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Unit</th>
                                            <th rowspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Supplier</th>
                                            <th colspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Quantity</th>
                                            <th rowspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Unit Cost</th>
                                            <th rowspan="2" class="border border-gray-800 px-1 py-1.5 text-center text-[9px] font-bold uppercase sm:px-2 sm:text-xs">Amount</th>
                                        </tr>
                                        <tr>
                                            <th class="border border-gray-800 px-0.5 py-1 text-center text-[8px] font-bold uppercase sm:text-[11px]">Requested</th>
                                            <th class="border border-gray-800 px-0.5 py-1 text-center text-[8px] font-bold uppercase sm:text-[11px]">Issued</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in createItems" :key="index">
                                            <tr :class="risSplitInfo(createItems, index)?.overflow ? 'bg-red-50' : ''">
                                                <td class="min-w-0 border border-gray-800 p-0.5 align-top sm:p-1">
                                                    <input type="text" x-model="item.name_description" x-bind:name="`ris_items[${index}][name_description]`" x-on:input="copySplitUom(createItems, index)" class="w-full min-w-0 border-0 bg-transparent px-1 py-1.5 text-[11px] outline-none focus:ring-0 sm:px-2 sm:text-sm">
                                                    <p class="mt-1 px-1 text-[10px] leading-4 sm:px-2 sm:text-[11px]" x-show="risSplitInfo(createItems, index)" x-cloak :class="risSplitInfo(createItems, index)?.overflow ? 'text-red-700' : 'text-amber-700'" x-text="(() => { const info = risSplitInfo(createItems, index); if (!info) return ''; const prefix = info.isDuplicate ? ('Split of \"' + info.label + '\"') : ('Split across suppliers'); return prefix + ' — ' + info.allocated + ' of ' + info.asked + ' allocated, ' + info.remaining + ' remaining'; })()"></p>
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 align-top sm:p-1">
                                                    <select x-model="item.brand_id" x-bind:name="`ris_items[${index}][brand_id]`" class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-center text-[10px] outline-none focus:ring-0 sm:px-1 sm:text-xs">
                                                        <option value="">Brand</option>
                                                        @foreach(($brands ?? collect()) as $brand)
                                                            <option value="{{ $brand->brand_id }}">{{ $brand->brand_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 align-top sm:p-1">
                                                    <select x-model="item.uom_id" x-bind:name="`ris_items[${index}][uom_id]`" class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-center text-[10px] outline-none focus:ring-0 sm:px-1 sm:text-xs">
                                                        <option value="">Unit</option>
                                                        @foreach(($uoms ?? collect()) as $uom)
                                                            <option value="{{ $uom->uom_id }}">{{ $uom->uom_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 align-top sm:p-1">
                                                    <select x-model="item.supplier_id" x-bind:name="`ris_items[${index}][supplier_id]`" class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-[10px] outline-none focus:ring-0 sm:px-1 sm:text-xs">
                                                        <option value="">Select supplier</option>
                                                        @foreach(($activeSuppliers ?? collect()) as $supplier)
                                                            <option value="{{ $supplier->supplier_id }}">{{ $supplier->display_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <p class="mt-1 px-0.5 text-[9px] leading-snug text-amber-700 sm:px-1 sm:text-[10px]" x-show="supplierWarning(item.supplier_id)" x-text="'Warning: ' + (supplierWarning(item.supplier_id)?.reason || 'This supplier is marked as not recommended.')"></p>
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 sm:p-1">
                                                    <input type="number" min="1" x-model="item.quantity_requested" x-bind:name="`ris_items[${index}][quantity_requested]`" class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-center text-[11px] outline-none focus:ring-0 sm:text-sm">
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 sm:p-1">
                                                    <input type="number" min="0" x-model="item.quantity_issued" x-bind:name="`ris_items[${index}][quantity_issued]`" class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-center text-[11px] outline-none focus:ring-0 sm:text-sm">
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 sm:p-1">
                                                    <input type="number" min="0" step="0.01" x-model="item.unit_cost" x-bind:name="`ris_items[${index}][unit_cost]`" class="w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-right text-[11px] outline-none focus:ring-0 sm:px-2 sm:text-sm">
                                                </td>
                                                <td class="min-w-0 border border-gray-800 p-0.5 sm:p-1">
                                                    <input type="text" readonly tabindex="-1" x-bind:name="`ris_items[${index}][total_amount]`" x-bind:value="((Number(item.quantity_issued) || 0) * (Number(item.unit_cost) || 0)).toFixed(2)" class="w-full min-w-0 cursor-not-allowed border-0 bg-gray-50 px-0.5 py-1.5 text-right text-[11px] text-gray-500 outline-none focus:ring-0 sm:px-2 sm:text-sm">
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:gap-4">
                                    <label class="text-xs font-bold uppercase text-gray-900 sm:pt-2 sm:text-sm">Purpose</label>
                                    <textarea name="ris_purpose_description" rows="2" x-model="purposeText" class="w-full min-w-0 flex-1 resize-none border-0 border-b border-gray-800 bg-transparent px-1 py-2 text-xs outline-none focus:ring-0 sm:px-2 sm:text-sm"></textarea>
                                </div>
                            </div>

                            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-6 sm:mt-10 sm:gap-x-8 md:grid-cols-4">
                                <div>
                                    <label class="block text-[10px] text-gray-600 sm:text-xs">Requested by:</label>
                                    <input type="text" name="ris_requested_by" value="{{ old('ris_requested_by') }}" class="mt-3 w-full border-0 border-b border-gray-800 bg-transparent px-1 py-1 text-center text-xs text-gray-950 outline-none focus:ring-0 sm:mt-5 sm:text-sm">
                                    <label class="mt-3 block text-[10px] text-gray-600 sm:mt-4 sm:text-xs">Date:</label>
                                    <input type="text" name="ris_requested_by_date" value="{{ old('ris_requested_by_date') }}" placeholder="dd/mm/yyyy" inputmode="numeric" maxlength="10" autocomplete="off" x-on:input="formatDateInput($event)" class="mt-1 w-full border-0 border-b border-gray-800 bg-transparent px-1 py-1 text-center text-xs text-gray-950 placeholder:text-gray-950 outline-none focus:ring-0 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-600 sm:text-xs">Approved by:</label>
                                    <div class="mt-3 h-[24px] w-full cursor-not-allowed border-b border-gray-800 sm:mt-5 sm:h-[29px]"></div>
                                    <label class="mt-3 block text-[10px] text-gray-600 sm:mt-4 sm:text-xs">Date:</label>
                                    <div class="mt-1 flex h-[24px] w-full cursor-not-allowed items-center justify-center border-b border-gray-800 text-[10px] text-gray-400 sm:h-[29px] sm:text-sm">dd/mm/yyyy</div>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-600 sm:text-xs">Issued by:</label>
                                    <div class="mt-3 h-[24px] w-full cursor-not-allowed border-b border-gray-800 sm:mt-5 sm:h-[29px]"></div>
                                    <label class="mt-3 block text-[10px] text-gray-600 sm:mt-4 sm:text-xs">Date:</label>
                                    <div class="mt-1 flex h-[24px] w-full cursor-not-allowed items-center justify-center border-b border-gray-800 text-[10px] text-gray-400 sm:h-[29px] sm:text-sm">dd/mm/yyyy</div>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-600 sm:text-xs">Received by:</label>
                                    <div class="mt-3 h-[24px] w-full cursor-not-allowed border-b border-gray-800 sm:mt-5 sm:h-[29px]"></div>
                                    <label class="mt-3 block text-[10px] text-gray-600 sm:mt-4 sm:text-xs">Date:</label>
                                    <div class="mt-1 flex h-[24px] w-full cursor-not-allowed items-center justify-center border-b border-gray-800 text-[10px] text-gray-400 sm:h-[29px] sm:text-sm">dd/mm/yyyy</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-950">Supporting Documents</p>
                                    <p class="truncate text-[11px] text-slate-500">Optional · 1 file at a time · Word/Excel</p>
                                </div>
                                <button
                                    type="button"
                                    x-show="createAttachmentName"
                                    x-on:click="clearCreateAttachments()"
                                    class="shrink-0 text-xs font-medium text-slate-500 transition hover:text-slate-950"
                                >
                                    Clear
                                </button>
                            </div>

                            <div class="space-y-1.5 border-t border-slate-100 px-3.5 py-2.5">
                                <div x-show="createAttachmentName" class="flex items-center gap-2 rounded-lg bg-slate-50 px-1.5 py-1.5" style="display: none;">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                    </div>
                                    <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="createAttachmentName"></p>
                                </div>

                                <label
                                    x-show="!createAttachmentName"
                                    class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2 transition hover:border-slate-400 hover:bg-slate-50"
                                >
                                    <input
                                        type="file"
                                        name="ris_attachments[]"
                                        accept=".doc,.docx,.xls,.xlsx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                        class="sr-only"
                                        x-ref="createAttachmentsInput"
                                        x-on:change="onCreateAttachmentsChange($event)"
                                    >
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200 transition group-hover:text-slate-800">
                                        <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium text-slate-800">Add file</p>
                                        <p class="truncate text-[10px] text-slate-500">Choose 1 Word or Excel file</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4 md:px-6">
                        <button type="button" x-on:click="createRisModal = false; createRisFullscreen = false" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-950">Cancel</button>
                        <button type="submit" x-bind:disabled="risHasOverflow(createItems)" class="px-4 py-2 bg-[#0025cc] rounded-lg text-white text-sm font-medium hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">Save RIS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="ris-records-section" class="pur-card">

        {{-- TOOLBAR --}}
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">RIS Records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            {{ $risRecords->total() }}
                        </span>
                    </div>
                </div>

                <form
                    id="ris-filter-form"
                    method="GET"
                    action="{{ route('purchaser.ris.index') }}"
                    x-ref="risFilterForm"
                    x-on:submit.prevent="refreshRisRecords()"
                    class="flex flex-col gap-2 sm:flex-row sm:items-center"
                >
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search RIS..."
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    @php
                        $risStatusOptions = [
                            '' => 'All statuses',
                            'Draft' => 'Draft',
                            'In Review' => 'In Review',
                            'Submitted' => 'Submitted',
                            'Under Review' => 'Under Review',
                            'Minor Revision' => 'Minor Revision',
                            'Resubmitted' => 'Resubmitted',
                            'Approved' => 'Approved',
                            'Directly Approved' => 'Directly Approved',
                            'Forwarded to President' => 'Forwarded to President',
                            'Approved by the President' => 'Approved by the President',
                            'Rejected' => 'Rejected',
                            'Rejected by the President' => 'Rejected by the President',
                        ];
                        $selectedRisStatus = (string) request('status', '');
                    @endphp

                    <div
                        class="relative shrink-0"
                        x-data="{
                            open: false,
                            value: @js($selectedRisStatus),
                            labels: @js($risStatusOptions)
                        }"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                    >
                        <input type="hidden" name="status" x-bind:value="value">

                        <button
                            type="button"
                            x-on:click="open = !open"
                            class="box-border inline-flex h-9 min-w-[11.5rem] items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm leading-none text-gray-600 outline-none transition hover:border-gray-300 hover:bg-white focus:border-gray-300 focus:bg-white"
                            :aria-expanded="open.toString()"
                            aria-haspopup="listbox"
                        >
                            <span class="truncate" x-text="labels[value] || 'All statuses'"></span>
                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute left-0 top-[calc(100%+6px)] z-30 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_12px_40px_rgba(15,23,42,0.12)]"
                            role="listbox"
                        >
                            <div class="max-h-[11.25rem] overflow-y-auto overscroll-contain py-1 [scrollbar-width:thin] [scrollbar-color:#cbd5e1_transparent]">
                                <template x-for="(label, key) in labels" :key="String(key)">
                                    <button
                                        type="button"
                                        role="option"
                                        x-on:click="value = key; open = false"
                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition hover:bg-slate-50"
                                        :class="value === key ? 'bg-slate-50 font-medium text-slate-950' : 'text-slate-600'"
                                        :aria-selected="(value === key).toString()"
                                    >
                                        <span class="truncate" x-text="label"></span>
                                        <svg
                                            x-show="value === key"
                                            class="h-4 w-4 shrink-0 text-[#0025cc]"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m5 13 4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

                    <button
                        type="submit"
                        x-bind:disabled="recordsLoading"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium leading-none text-white hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"
                    >
                        <i data-lucide="filter" class="h-4 w-4 shrink-0"></i>
                        <span x-cloak x-show="!recordsLoading">Apply</span>
                        <span x-cloak x-show="recordsLoading">Loading...</span>
                    </button>

                    @if(request()->filled('search') || request()->filled('status') || request()->filled('date_from') || request()->filled('date_to'))
                        <button
                            type="button"
                            x-on:click="clearRisFilters()"
                            class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                        >
                            Clear
                        </button>
                    @endif
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS Number</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Requested By</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Documents</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($risRecords as $ris)
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M9 8h2m-4 13h10a2 2 0 0 0 2-2V5l-4-4H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $ris->ris_form_number ?: 'Draft RIS' }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $ris->ris_id }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                {{ $ris->ris_requested_by_signature ?: 'Not specified' }}
                            </td>

                            <td class="px-5 py-4">
                                @if($ris->risAttachments->count() === 1)
                                    @php $attachment = $ris->risAttachments->first(); @endphp
                                    <a
                                        href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}"
                                        class="inline-flex items-center gap-2 text-sm text-gray-600 transition hover:text-gray-900 hover:underline"
                                        title="Download {{ $attachment->ris_attachment_original_name }}"
                                    >
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.172 7-6.586 6.586a2 2 0 1 0 2.828 2.828L18 9.828a4 4 0 1 0-5.657-5.657L5.757 10.757a6 6 0 0 0 8.486 8.486L20 13.486" />
                                        </svg>
                                        <span>1 file</span>
                                    </a>
                                @elseif($ris->risAttachments->isNotEmpty())
                                    <div
                                        class="relative"
                                        x-data="{ openFiles: false }"
                                        @click.outside="openFiles = false"
                                    >
                                        <button
                                            type="button"
                                            @click="openFiles = !openFiles"
                                            class="inline-flex items-center gap-2 text-sm text-gray-600 transition hover:text-gray-900 hover:underline"
                                        >
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.172 7-6.586 6.586a2 2 0 1 0 2.828 2.828L18 9.828a4 4 0 1 0-5.657-5.657L5.757 10.757a6 6 0 0 0 8.486 8.486L20 13.486" />
                                            </svg>
                                            <span>{{ $ris->risAttachments->count() }} files</span>
                                        </button>

                                        <div
                                            x-show="openFiles"
                                            x-cloak
                                            x-transition
                                            class="absolute left-0 z-20 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg"
                                        >
                                            @foreach($ris->risAttachments as $attachment)
                                                <a
                                                    href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}"
                                                    class="block truncate px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                    title="{{ $attachment->ris_attachment_original_name }}"
                                                >
                                                    {{ $attachment->ris_attachment_original_name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">No files</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @include('admin.partials.ris-status-badge', ['ris' => $ris])
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                @if(!empty($ris->ris_submitted_at))
                                    <p class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($ris->ris_submitted_at)->format('M d, Y') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">{{ \Carbon\Carbon::parse($ris->ris_submitted_at)->format('h:i A') }}</p>
                                @else
                                    <span class="text-xs text-gray-400">Not submitted</span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        x-on:click="openModal = 'ris-{{ $ris->ris_id }}'"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                        title="View"
                                        aria-label="View"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>

                                    @if(in_array($ris->ris_status, ['Draft', 'Minor Revision'], true))
                                        <button
                                            type="button"
                                            x-on:click="editRisModal = 'edit-ris-{{ $ris->ris_id }}'"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]"
                                            title="Edit RIS"
                                            aria-label="Edit RIS"
                                        >
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </button>
                                    @endif

                                    @if($ris->ris_status === 'Draft')
                                        <button
                                            type="button"
                                            x-on:click="openSubmitRis({{ (int) $ris->ris_id }}, @js($ris->ris_form_number ?: 'Draft RIS'), @js(route('purchaser.ris.submit', $ris->ris_id)))"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]"
                                            title="Submit to Admin"
                                            aria-label="Submit to Admin"
                                        >
                                            <i data-lucide="send" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <p class="font-medium text-gray-700">No RIS records found</p>
                                <p class="mt-1 text-sm text-gray-400">Try changing your search or filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div
            class="border-t border-gray-100 px-6 py-4"
            x-on:click="
                const link = $event.target.closest('a');
                if (!link) return;
                $event.preventDefault();
                refreshRisRecords(link.href);
            "
        >
            {{ $risRecords->withQueryString()->links() }}
        </div>
    </div>

    <div id="ris-modals-section">
    @foreach($risRecords as $ris)

        {{-- VIEW RIS MODAL --}}
        <div
            x-cloak
            x-show="openModal === 'ris-{{ $ris->ris_id }}'"
            x-on:keydown.escape.window="openModal = null"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
            x-effect="window.purDialog && window.purDialog.sync(openModal === 'ris-{{ $ris->ris_id }}', $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        >
            <div
                x-on:click.self="openModal = null"
                class="flex min-h-full w-full justify-center"
            >
            <div
                class="my-auto w-full max-w-5xl rounded-xl bg-white shadow-2xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ris-view-title-{{ $ris->ris_id }}"
            >
                {{-- MODAL HEADER --}}
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 id="ris-view-title-{{ $ris->ris_id }}" class="text-xl font-semibold text-gray-900">
                                {{ $ris->ris_form_number ?: 'Draft RIS' }}
                            </h3>

                            @include('admin.partials.ris-status-badge', ['ris' => $ris])
                        </div>

                        <p class="mt-2 text-sm text-gray-500">Requisition and Issue Slip</p>
                        @if(!empty($ris->supplier_display_name))
                            <p class="mt-1 text-sm text-gray-500">Supplier: {{ $ris->supplier_display_name }}</p>
                        @endif
                        @php
                            $risLineage = \App\Support\DocumentLineage::forRis((int) $ris->ris_id);
                            $risHint = \App\Support\DocumentLineage::reviewHintForRis($ris);
                        @endphp
                        <div class="mt-3">
                            @include('partials.document-lineage', [
                                'lineage' => $risLineage,
                                'currentType' => 'RIS',
                                'statusHint' => $risHint,
                            ])
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                        <button
                            type="button"
                            onclick="printRis('view-ris-content-{{ $ris->ris_id }}')"
                            data-tooltip="Print RIS"
                            aria-label="Print RIS"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 "
                        >
                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                        </button>

                        <a
                            href="{{ route('purchaser.ris.export-xlsx', $ris->ris_id) }}"
                            data-tooltip="Export to Excel"
                            aria-label="Export to Excel"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200  transition hover:border-emerald-300 "
                        >
                            <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path fill="#185C37" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                <path fill="#21A366" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                <path fill="#107C41" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                <path fill="#FFF" d="M7.35 21.35 9.9 16.75l-2.4-4.5h1.85l1.5 3.15c.14.3.24.53.31.72h.04c.08-.22.19-.47.33-.76l1.55-3.11h1.7l-2.48 4.52 2.55 4.68h-1.82l-1.7-3.45c-.09-.18-.16-.35-.21-.52h-.04c-.05.18-.12.36-.22.55l-1.74 3.42H7.35z"/>
                            </svg>
                        </a>

                        <a
                            href="{{ route('purchaser.ris.export-docx', $ris->ris_id) }}"
                            data-tooltip="Export to Word file"
                            aria-label="Export to Word file"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 transition hover:border-blue-300 "
                        >
                            <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.32-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                            </svg>
                        </a>

                        <button
                            type="button"
                            x-on:click="openModal = null"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 transition hover:bg-gray-50"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                {{-- MODAL CONTENT --}}
                <div class="p-6">

                    {{-- CURRENT MINOR REVISION NOTICE --}}
                    @if($ris->ris_status === 'Minor Revision' && $ris->risRevisions->isNotEmpty())
                        @php
                            $latestRevision = $ris->risRevisions->first();
                        @endphp
                        <div class="mb-6 overflow-hidden rounded-lg border border-orange-200">
                            <div class="border-b border-orange-200 bg-orange-50 px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-orange-900">Minor Revision Required</p>
                                        <p class="mt-1 text-xs text-orange-700">This RIS was returned for correction.</p>
                                    </div>
                                    <span class="rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-medium text-orange-700">
                                        Action Required
                                    </span>
                                </div>
                            </div>
                            <div class="bg-white p-5">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Revision Note</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-800">
                                    {{ $latestRevision->ris_revision_note }}
                                </p>
                                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-gray-100 pt-4 text-xs text-gray-500">
                                    <span>Requested by: <strong class="font-medium text-gray-700">{{ $latestRevision->revision_requested_by_name ?? 'Administrator' }}</strong></span>
                                    <span>Type: <strong class="font-medium text-gray-700">{{ $latestRevision->ris_revision_type }}</strong></span>
                                    <span>Date: <strong class="font-medium text-gray-700">{{ $latestRevision->ris_revision_created_at ? \Carbon\Carbon::parse($latestRevision->ris_revision_created_at)->format('M d, Y h:i A') : 'Not available' }}</strong></span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- RIS DOCUMENT: SAME PHYSICAL DESIGN AS PRINT EMPTY RIS --}}
                    <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
                        <div
                            id="view-ris-content-{{ $ris->ris_id }}"
                            class="ris-original-form mx-auto bg-white text-black"
                        >
                            <div class="ris-document-header">
                                <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                                <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
                                <div class="ris-number-area">
                                    <span class="ris-number-label">No.</span>
                                    <span class="ris-number-line ris-value-line">{{ $ris->ris_form_number ?: ' ' }}</span>
                                </div>
                            </div>

                            <table class="ris-items-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="ris-item-column">ITEM</th>
                                        <th rowspan="2" class="ris-brand-column">BRAND</th>
                                        <th rowspan="2" class="ris-unit-column">UNIT</th>
                                        <th rowspan="2" class="ris-supplier-column">SUPPLIER</th>
                                        <th colspan="2" class="ris-quantity-header">QUANTITY</th>
                                        <th rowspan="2" class="ris-unit-cost-column">UNIT COST</th>
                                        <th rowspan="2" class="ris-amount-column">AMOUNT</th>
                                    </tr>
                                    <tr>
                                        <th class="ris-requested-column">REQUESTED</th>
                                        <th class="ris-issued-column">ISSUED</th>
                                    </tr>
                                </thead>
                            <tbody>
                                @for($row = 0; $row < 8; $row++)
                                    @php
                                        $item = $ris->risItems->get($row);
                                    @endphp
                                    <tr>
                                        <td>{{ $item?->ris_item_name_description ?: ' ' }}</td>
                                        <td class="text-center">{{ $item?->brand_name ?: ' ' }}</td>
                                        <td class="text-center">{{ $item?->uom_name ?: ' ' }}</td>
                                        <td>{{ $item?->supplier_display_name ?: ' ' }}</td>
                                        <td class="text-center">{{ $item?->ris_quantity_requested ?? ' ' }}</td>
                                        <td class="text-center">{{ $item?->ris_quantity_issued ?? ' ' }}</td>
                                        <td class="text-right">{{ $item && $item->ris_unit_cost !== null ? number_format((float) $item->ris_unit_cost, 2) : ' ' }}</td>
                                        <td class="text-right">{{ $item && $item->ris_total_amount !== null ? number_format((float) $item->ris_total_amount, 2) : ' ' }}</td>
                                    </tr>
                                @endfor
                            </tbody>
                            </table>

                            <div class="ris-purpose-area">
                                <div class="ris-purpose-label">PURPOSE</div>
                                <div class="ris-purpose-line-row">
                                    <div class="ris-purpose-spacer"></div>
                                    <div class="ris-purpose-line ris-value-line">{{ $ris->ris_purpose_description ?: ' ' }}</div>
                                </div>
                            </div>

                            <div class="ris-signatures">
                                @foreach([
                                    ['Requested by:', $ris->ris_requested_by_signature, $ris->ris_requested_by_date],
                                    ['Approved by:', ((int) ($ris->has_approved_by_signature ?? 0) === 1 ? 'Signed' : ''), $ris->ris_approved_by_date],
                                    ['Issued by:', ((int) ($ris->has_issued_by_signature ?? 0) === 1 ? 'Signed' : ''), $ris->ris_issued_by_date],
                                    ['Received by:', ((int) ($ris->has_received_by_signature ?? 0) === 1 ? 'Signed' : ''), $ris->ris_received_by_date],
                                ] as [$label, $signature, $date])
                                    <div class="ris-signature-column">
                                        <div class="ris-signature-label">{{ $label }}</div>
                                        <div class="ris-signature-line ris-value-line">

                                            @if(!empty($signature))

                                                @if(str_starts_with($signature, 'data:image'))

                                                    <img
                                                        src="{{ $signature }}"
                                                        alt="Signature"
                                                        class="max-h-10 max-w-full object-contain"
                                                    >

                                                @else

                                                    <span class="block w-full truncate text-center">
                                                        {{ $signature }}
                                                    </span>

                                                @endif

                                            @endif

                                        </div>
                                        <div class="ris-date-label">Date:</div>
                                        <div class="ris-date-line ris-value-line">
                                            {{ $date ? \Carbon\Carbon::parse($date)->format('M d, Y') : ' ' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- SUPPORTING DOCUMENTS --}}
                    <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-950/5">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-slate-950">Supporting Documents</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    @if($ris->risAttachments->isNotEmpty())
                                        {{ $ris->risAttachments->count() }} {{ $ris->risAttachments->count() === 1 ? 'file' : 'files' }} attached
                                    @else
                                        Word or Excel attachments for this RIS
                                    @endif
                                </p>
                            </div>
                            @if($ris->risAttachments->isNotEmpty())
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                    {{ $ris->risAttachments->count() }}
                                </span>
                            @endif
                        </div>

                        <div class="p-4">
                            @if($ris->risAttachments->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach($ris->risAttachments as $attachment)
                                        @php
                                            $ext = strtolower(pathinfo($attachment->ris_attachment_original_name, PATHINFO_EXTENSION));
                                            $isExcel = in_array($ext, ['xls', 'xlsx'], true);
                                            $isWord = in_array($ext, ['doc', 'docx'], true);
                                            $fileIconClass = $isExcel
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                : ($isWord
                                                    ? 'bg-blue-50 text-blue-700 ring-blue-200'
                                                    : 'bg-white text-slate-500 ring-slate-200');
                                            $fileIcon = $isExcel ? 'file-spreadsheet' : ($isWord ? 'file-text' : 'paperclip');
                                        @endphp
                                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ring-1 {{ $fileIconClass }}">
                                                <i data-lucide="{{ $fileIcon }}" class="h-4 w-4"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-slate-900">{{ $attachment->ris_attachment_original_name }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    @if($attachment->ris_attachment_size)
                                                        {{ number_format($attachment->ris_attachment_size / 1024, 1) }} KB
                                                    @else
                                                        Attached file
                                                    @endif
                                                </p>
                                            </div>
                                            <a
                                                href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}"
                                                data-tooltip="Download"
                                                aria-label="Download {{ $attachment->ris_attachment_original_name }}"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
                                            >
                                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-10 text-center">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                                        <i data-lucide="folder-open" class="h-5 w-5"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium text-slate-800">No supporting documents</p>
                                    <p class="mt-1 text-xs text-slate-500">Nothing has been attached to this RIS yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- REVISION HISTORY --}}
                    @if($ris->risRevisions->isNotEmpty())
                        <div class="mt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Revision History</h4>
                                    <p class="mt-1 text-xs text-gray-400">Previous correction requests for this RIS.</p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                    {{ $ris->risRevisions->count() }} {{ $ris->risRevisions->count() === 1 ? 'Revision' : 'Revisions' }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3">
                                @foreach($ris->risRevisions as $revision)
                                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">Revision #{{ $ris->risRevisions->count() - $loop->index }}</p>
                                                <p class="mt-1 text-xs text-gray-500">{{ $revision->ris_revision_type }}</p>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ $revision->ris_revision_created_at ? \Carbon\Carbon::parse($revision->ris_revision_created_at)->format('M d, Y h:i A') : '' }}</p>
                                        </div>
                                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $revision->ris_revision_note }}</p>
                                        <p class="mt-3 text-xs text-gray-500">
                                            Requested by: <span class="font-medium text-gray-700">{{ $revision->revision_requested_by_name ?? 'Administrator' }}</span>
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- RIS ACTION BAR --}}
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="px-2 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950"
                    >
                        Close
                    </button>

                    @if(in_array($ris->ris_status, ['Draft', 'Minor Revision'], true))
                        <button
                            type="button"
                            x-on:click="openModal = null; editRisModal = 'edit-ris-{{ $ris->ris_id }}';"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            Edit RIS
                        </button>
                    @endif

                    {{-- SUBMIT DRAFT --}}
                    @if($ris->ris_status === 'Draft')
                        <button
                            type="button"
                            x-on:click="openSubmitRis({{ (int) $ris->ris_id }}, @js($ris->ris_form_number ?: 'Draft RIS'), @js(route('purchaser.ris.submit', $ris->ris_id)))"
                            class="px-4 py-2 bg-[#0025cc] rounded-lg text-white text-[13px] font-medium hover:bg-blue-800"
                        >
                            Submit to Admin
                        </button>
                    @endif

                    {{-- CREATE ATP --}}
                    @if(!empty($ris->can_create_atp))
                        @if(!$ris->has_atp)
                            <a
                                href="{{ route('purchaser.atp.create', ['selected_ris' => $ris->ris_id]) }}"
                                class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                            >
                                Create ATP
                            </a>
                        @else
                            <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
                                ATP Created
                            </span>
                        @endif
                    @elseif(in_array($ris->ris_status, ['Submitted', 'Under Review', 'Resubmitted'], true))
                        <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700">
                            Waiting for Admin review
                        </span>
                    @elseif($ris->ris_status === 'Forwarded to President' || ($ris->ris_status === 'Approved' && trim((string) ($ris->ris_approved_by_signature ?? '')) === ''))
                        <span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700">
                            Waiting for President
                        </span>
                    @elseif(in_array($ris->ris_status, ['Approved by the President', 'Approved'], true) && trim((string) ($ris->ris_issued_by_signature ?? '')) === '')
                        <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700">
                            Waiting for Admin Issued by
                        </span>
                    @endif
                </div>
            </div>
            </div>
        </div>

        {{-- PRINT RIS MODAL --}}
        <div
            x-cloak
            x-show="openModal === 'print-ris-{{ $ris->ris_id }}'"
            x-on:keydown.escape.window="openModal = null"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
            x-effect="window.purDialog && window.purDialog.sync(openModal === 'print-ris-{{ $ris->ris_id }}', $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            role="dialog"
            aria-modal="true"
            aria-labelledby="ris-print-title-{{ $ris->ris_id }}"
        >
            <div x-on:click.self="openModal = null" class="flex min-h-full w-full justify-center">
                <div class="my-auto w-full max-w-5xl rounded-xl bg-white shadow-2xl">

                    {{-- PRINT PREVIEW HEADER --}}
                    <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h3 id="ris-print-title-{{ $ris->ris_id }}" class="text-lg font-semibold text-gray-900">Print RIS</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $ris->ris_form_number ?: 'No RIS Number' }}</p>
                        </div>
                        <button
                            type="button"
                            x-on:click="openModal = null"
                            class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- RIS DOCUMENT: SAME PHYSICAL DESIGN AS PRINT EMPTY RIS --}}
                    <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
                        <div
                            id="print-ris-content-{{ $ris->ris_id }}"
                            class="ris-original-form mx-auto bg-white text-black"
                        >
                            <div class="ris-document-header">
                                <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                                <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
                                <div class="ris-number-area">
                                    <span class="ris-number-label">No.</span>
                                    <span class="ris-number-line ris-value-line">{{ $ris->ris_form_number ?: ' ' }}</span>
                                </div>
                            </div>

                            <table class="ris-items-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="ris-item-column">ITEM</th>
                                        <th rowspan="2" class="ris-brand-column">BRAND</th>
                                        <th rowspan="2" class="ris-unit-column">UNIT</th>
                                        <th rowspan="2" class="ris-supplier-column">SUPPLIER</th>
                                        <th colspan="2" class="ris-quantity-header">QUANTITY</th>
                                        <th rowspan="2" class="ris-unit-cost-column">UNIT COST</th>
                                        <th rowspan="2" class="ris-amount-column">AMOUNT</th>
                                    </tr>
                                    <tr>
                                        <th class="ris-requested-column">REQUESTED</th>
                                        <th class="ris-issued-column">ISSUED</th>
                                    </tr>
                                </thead>
                            <tbody>
                                @for($row = 0; $row < 8; $row++)
                                    @php
                                        $item = $ris->risItems->get($row);
                                    @endphp
                                    <tr>
                                        <td>{{ $item?->ris_item_name_description ?: ' ' }}</td>
                                        <td class="text-center">{{ $item?->brand_name ?: ' ' }}</td>
                                        <td class="text-center">{{ $item?->uom_name ?: ' ' }}</td>
                                        <td>{{ $item?->supplier_display_name ?: ' ' }}</td>
                                        <td class="text-center">{{ $item?->ris_quantity_requested ?? ' ' }}</td>
                                        <td class="text-center">{{ $item?->ris_quantity_issued ?? ' ' }}</td>
                                        <td class="text-right">{{ $item && $item->ris_unit_cost !== null ? number_format((float) $item->ris_unit_cost, 2) : ' ' }}</td>
                                        <td class="text-right">{{ $item && $item->ris_total_amount !== null ? number_format((float) $item->ris_total_amount, 2) : ' ' }}</td>
                                    </tr>
                                @endfor
                            </tbody>
                            </table>

                            <div class="ris-purpose-area">
                                <div class="ris-purpose-label">PURPOSE</div>
                                <div class="ris-purpose-line-row">
                                    <div class="ris-purpose-spacer"></div>
                                    <div class="ris-purpose-line ris-value-line">{{ $ris->ris_purpose_description ?: ' ' }}</div>
                                </div>
                            </div>

                            <div class="ris-signatures">
                                @foreach([
                                    ['Requested by:', $ris->ris_requested_by_signature, $ris->ris_requested_by_date],
                                    ['Approved by:', ((int) ($ris->has_approved_by_signature ?? 0) === 1 ? 'Signed' : ''), $ris->ris_approved_by_date],
                                    ['Issued by:', ((int) ($ris->has_issued_by_signature ?? 0) === 1 ? 'Signed' : ''), $ris->ris_issued_by_date],
                                    ['Received by:', ((int) ($ris->has_received_by_signature ?? 0) === 1 ? 'Signed' : ''), $ris->ris_received_by_date],
                                ] as [$label, $signature, $date])
                                    <div class="ris-signature-column">
                                        <div class="ris-signature-label">{{ $label }}</div>
                                        <div class="ris-signature-line ris-value-line">{{ $signature ?: ' ' }}</div>
                                        <div class="ris-date-label">Date:</div>
                                        <div class="ris-date-line ris-value-line">
                                            {{ $date ? \Carbon\Carbon::parse($date)->format('M d, Y') : ' ' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- PRINT ACTION --}}
                    <div class="print-hidden flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            x-on:click="openModal = null"
                            class="px-2 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <a
                            href="{{ route('purchaser.ris.export-xlsx', $ris->ris_id) }}"
                            data-tooltip="Export to Excel"
                            aria-label="Export to Excel"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 transition hover:border-emerald-300 hover:bg-emerald-100"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path fill="#185C37" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                <path fill="#21A366" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                <path fill="#107C41" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                <path fill="#FFF" d="M7.35 21.35 9.9 16.75l-2.4-4.5h1.85l1.5 3.15c.14.3.24.53.31.72h.04c.08-.22.19-.47.33-.76l1.55-3.11h1.7l-2.48 4.52 2.55 4.68h-1.82l-1.7-3.45c-.09-.18-.16-.35-.21-.52h-.04c-.05.18-.12.36-.22.55l-1.74 3.42H7.35z"/>
                            </svg>
                        </a>
                        <a
                            href="{{ route('purchaser.ris.export-docx', $ris->ris_id) }}"
                            data-tooltip="Export to Word file"
                            aria-label="Export to Word file"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 transition hover:border-blue-300 hover:bg-blue-100"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                                <path fill="#185ABD" d="M18.5 3H8.8C7.25 3 6 4.25 6 5.8v20.4C6 27.75 7.25 29 8.8 29h14.4c1.55 0 2.8-1.25 2.8-2.8V10.5L18.5 3z"/>
                                <path fill="#4CA1FF" d="M18.5 3v6.2c0 1.21.99 2.2 2.2 2.2H29L18.5 3z"/>
                                <path fill="#2B7CD3" d="M14.2 9H4.9C3.85 9 3 9.85 3 10.9v12.2C3 24.15 3.85 25 4.9 25h9.3c1.05 0 1.9-.85 1.9-1.9V10.9C16.1 9.85 15.25 9 14.2 9z"/>
                                <path fill="#FFF" d="m6.55 21.2 1.45-6.55h1.55l.9 4.35c.08.4.14.74.18 1.02h.04c.05-.28.12-.62.22-1.02l1.05-4.35h1.45l1.1 4.35c.09.37.16.71.21 1.02h.04c.04-.28.11-.64.21-1.05l.95-4.32h1.48L15.4 21.2h-1.55l-1.05-4.2c-.08-.32-.14-.64-.18-.95h-.04c-.04.32-.11.64-.2.98l-1.1 4.17H9.7l-1.05-4.2c-.08-.33-.14-.64-.18-.95h-.03c-.04.3-.11.62-.2.95l-1.08 4.2H6.55z"/>
                            </svg>
                        </a>
                        <button
                            type="button"
                            onclick="printRis('print-ris-content-{{ $ris->ris_id }}')"
                            class="pur-btn-primary"
                        >
                            Print RIS
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(in_array($ris->ris_status, ['Draft', 'Minor Revision'], true))
            {{-- EDIT RIS MODAL: parent owns editRisModal so Close/Cancel/Escape work correctly --}}
            <div
                x-cloak
                x-show="editRisModal === 'edit-ris-{{ $ris->ris_id }}'"
                x-transition.opacity
                x-on:keydown.escape.window="closeEditRis({{ $ris->ris_id }})"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-black/50 p-4 md:p-8"
                x-effect="window.purDialog && window.purDialog.sync(editRisModal === 'edit-ris-{{ $ris->ris_id }}', $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            >
                <div
                    x-on:click.self="closeEditRis({{ $ris->ris_id }})"
                    class="flex h-full max-h-full w-full items-center justify-center"
                >
                <div
                    x-data="{
                    editItems: [
                        @forelse($ris->risItems as $item)
                            {
                                name_description: @js($item->ris_item_name_description ?? ''),
                                brand_id: @js((string) ($item->ris_item_brand_id ?? '')),
                                supplier_id: @js((string) ($item->ris_item_supplier_id ?? '')),
                                uom_id: @js((string) ($item->ris_item_uom_id ?? '')),
                                quantity_requested: @js($item->ris_quantity_requested ?? 1),
                                quantity_issued: @js($item->ris_quantity_issued ?? 0),
                                unit_cost: @js($item->ris_unit_cost ?? 0)
                            }{{ !$loop->last ? ',' : '' }}
                        @empty
                            { name_description: '', brand_id: '', supplier_id: '', uom_id: '', quantity_requested: 1, quantity_issued: 0, unit_cost: 0 }
                        @endforelse
                    ],
                    rowDeleteMode: false,
                    addEditItem() {
                        this.editItems.push({ name_description: '', brand_id: '', supplier_id: '', uom_id: '', quantity_requested: '', quantity_issued: '', unit_cost: '' });
                    },
                    removeEditItem(index) {
                        if (this.editItems.length > 1) { this.editItems.splice(index, 1); }
                    },
                    itemTotal(item) {
                        return (Number(item.quantity_issued) || 0) * (Number(item.unit_cost) || 0);
                    },
                    formatDateInput(event) {
                        const el = event.target;
                        const digits = String(el.value || '').replace(/\D/g, '').slice(0, 8);
                        let formatted = digits;
                        if (digits.length > 4) {
                            formatted = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
                        } else if (digits.length > 2) {
                            formatted = digits.slice(0, 2) + '/' + digits.slice(2);
                        }
                        el.value = formatted;
                    }
                }"
                    class="my-auto flex max-h-[min(96vh,calc(100vh-2rem))] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="ris-edit-title-{{ $ris->ris_id }}"
                >
                    {{-- EDIT MODAL HEADER --}}
                    <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <button
                                type="button"
                                x-on:click="closeEditRis({{ $ris->ris_id }})"
                                class="mt-0.5 inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                            >
                                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                
                            </button>
                            <div class="min-w-0">
                                <h3 id="ris-edit-title-{{ $ris->ris_id }}" class="text-lg font-semibold text-gray-900">Edit RIS</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $ris->ris_form_number ?: 'Draft RIS' }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            x-on:click="closeEditRis({{ $ris->ris_id }}, false)"
                            class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('purchaser.ris.update', $ris->ris_id) }}"
                        enctype="multipart/form-data"
                        novalidate
                        class="flex min-h-0 flex-1 flex-col"
                    >
                        @csrf
                        @method('PUT')

                        <input
                            type="hidden"
                            name="save_action"
                            value="save"
                        >
                        <div class="min-h-0 flex-1 overflow-y-auto p-6">

                            {{-- REVISION INSTRUCTIONS WHILE EDITING --}}
                            @if($ris->ris_status === 'Minor Revision' && $ris->risRevisions->isNotEmpty())
                                @php
                                    $latestRevision = $ris->risRevisions->first();
                                @endphp
                                <div class="mb-6 rounded-lg border border-orange-200 bg-orange-50 p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-orange-900">Changes Requested by Administrator</p>
                                            <p class="mt-1 text-xs text-orange-700">Correct the issues below before resubmitting this RIS.</p>
                                        </div>
                                        <span class="shrink-0 rounded-full border border-orange-200 bg-white px-3 py-1 text-xs font-medium text-orange-700">
                                            Minor Revision
                                        </span>
                                    </div>
                                    <div class="mt-4 rounded-lg border border-orange-100 bg-white p-4">
                                        <p class="whitespace-pre-line text-sm leading-6 text-gray-800">{{ $latestRevision->ris_revision_note }}</p>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-4 text-xs text-orange-700">
                                        <span>Requested by: <strong>{{ $latestRevision->revision_requested_by_name ?? 'Administrator' }}</strong></span>
                                        <span>{{ $latestRevision->ris_revision_created_at ? \Carbon\Carbon::parse($latestRevision->ris_revision_created_at)->format('M d, Y h:i A') : '' }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- EDIT RIS: EXACT SAME PHYSICAL RIS DESIGN --}}
                            <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
                                <div class="ris-original-form ris-edit-form mx-auto bg-white text-black">
                                    <div class="ris-document-header">
                                        <div class="ris-school-name">STI COLLEGE - ORMOC, INC.</div>
                                        <div class="ris-document-title">REQUISITION AND ISSUE SLIP</div>
                                        <div class="ris-number-area">
                                            <span class="ris-number-label">No.</span>
                                            <input
                                                type="text"
                                                name="ris_form_number"
                                                value="{{ $ris->ris_form_number }}"
                                                inputmode="numeric"
                                                pattern="\d{8}"
                                                maxlength="8"
                                                title="Enter exactly 8 digits"
                                                x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 8)"
                                                class="ris-number-input"
                                            >
                                        </div>
                                    </div>

                                    <table
                                        class="ris-items-table"
                                        :class="{ 'ris-delete-mode': rowDeleteMode }"
                                    >
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="ris-item-column">ITEM</th>
                                                <th rowspan="2" class="ris-brand-column">BRAND</th>
                                                <th rowspan="2" class="ris-unit-column">UNIT</th>
                                                <th rowspan="2" class="ris-supplier-column">SUPPLIER</th>
                                                <th colspan="2" class="ris-quantity-header">QUANTITY</th>
                                                <th rowspan="2" class="ris-unit-cost-column">UNIT COST</th>
                                                <th rowspan="2" class="ris-amount-column">AMOUNT</th>
                                            </tr>
                                            <tr>
                                                <th class="ris-requested-column">REQUESTED</th>
                                                <th class="ris-issued-column">ISSUED</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, index) in editItems" :key="index">
                                                <tr
                                                    class="ris-item-row"
                                                    :class="risSplitInfo(editItems, index)?.overflow ? 'bg-red-50' : ''"
                                                >
                                                    <td>
                                                        <div class="ris-edit-item-cell">
                                                            <input type="text" x-model="item.name_description" x-on:input="copySplitUom(editItems, index)" x-bind:name="`ris_items[${index}][name_description]`" class="ris-cell-input">
                                                            <p
                                                                class="mt-1 text-[11px] leading-4"
                                                                x-show="risSplitInfo(editItems, index)"
                                                                x-cloak
                                                                :class="risSplitInfo(editItems, index)?.overflow ? 'text-red-700' : 'text-amber-700'"
                                                                x-text="(() => {
                                                                    const info = risSplitInfo(editItems, index);
                                                                    if (!info) return '';
                                                                    const prefix = info.isDuplicate ? ('Split of \"' + info.label + '\"') : ('Split across suppliers');
                                                                    return prefix + ' — ' + info.allocated + ' of ' + info.asked + ' allocated, ' + info.remaining + ' remaining';
                                                                })()"
                                                            ></p>
                                                        </div>
                                                        <span class="ris-row-delete-x" aria-hidden="true">
                                                            <svg viewBox="0 0 100 40" preserveAspectRatio="none">
                                                                <line x1="0" y1="0" x2="100" y2="40" stroke="#ffffff" stroke-width="0.9" stroke-linecap="butt" vector-effect="non-scaling-stroke"></line>
                                                                <line x1="0" y1="40" x2="100" y2="0" stroke="#ffffff" stroke-width="0.9" stroke-linecap="butt" vector-effect="non-scaling-stroke"></line>
                                                            </svg>
                                                        </span>
                                                        <button
                                                            type="button"
                                                            class="ris-row-delete-hit"
                                                            x-on:click="removeEditItem(index)"
                                                            x-bind:disabled="editItems.length === 1"
                                                            aria-label="Remove item row"
                                                        ></button>
                                                    </td>
                                                    <td>
                                                        <select x-model="item.brand_id" x-bind:name="`ris_items[${index}][brand_id]`" class="ris-cell-input text-center text-xs">
                                                            <option value="">Brand</option>
                                                            @foreach(($brands ?? collect()) as $brand)
                                                                <option value="{{ $brand->brand_id }}">{{ $brand->brand_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button
                                                            type="button"
                                                            class="ris-row-delete-hit"
                                                            x-on:click="removeEditItem(index)"
                                                            x-bind:disabled="editItems.length === 1"
                                                            tabindex="-1"
                                                            aria-label="Remove item row"
                                                        ></button>
                                                    </td>
                                                    <td>
                                                        <select x-model="item.uom_id" x-bind:name="`ris_items[${index}][uom_id]`" class="ris-cell-input text-center text-xs">
                                                            <option value="">Unit</option>
                                                            @foreach(($uoms ?? collect()) as $uom)
                                                                <option value="{{ $uom->uom_id }}">{{ $uom->uom_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button
                                                            type="button"
                                                            class="ris-row-delete-hit"
                                                            x-on:click="removeEditItem(index)"
                                                            x-bind:disabled="editItems.length === 1"
                                                            tabindex="-1"
                                                            aria-label="Remove item row"
                                                        ></button>
                                                    </td>
                                                    <td>
                                                        <select x-model="item.supplier_id" x-bind:name="`ris_items[${index}][supplier_id]`" class="ris-cell-input text-xs">
                                                            <option value="">Select supplier</option>
                                                            @foreach(($activeSuppliers ?? collect()) as $supplier)
                                                                <option value="{{ $supplier->supplier_id }}">{{ $supplier->display_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <p
                                                            class="mt-1 text-[10px] leading-snug text-amber-700"
                                                            x-show="supplierWarning(item.supplier_id)"
                                                            x-text="'Warning: ' + (supplierWarning(item.supplier_id)?.reason || 'This supplier is marked as not recommended.')"
                                                        ></p>
                                                        <button
                                                            type="button"
                                                            class="ris-row-delete-hit"
                                                            x-on:click="removeEditItem(index)"
                                                            x-bind:disabled="editItems.length === 1"
                                                            tabindex="-1"
                                                            aria-label="Remove item row"
                                                        ></button>
                                                    </td>
                                                    <td>
                                                        <input type="number" min="0" x-bind:min="String(item.name_description || '').trim() ? 1 : 0" x-model="item.quantity_requested" x-bind:name="`ris_items[${index}][quantity_requested]`" class="ris-cell-input text-center">
                                                        <button type="button" class="ris-row-delete-hit" x-on:click="removeEditItem(index)" x-bind:disabled="editItems.length === 1" tabindex="-1" aria-label="Remove item row"></button>
                                                    </td>
                                                    <td>
                                                        <input type="number" min="0" x-model="item.quantity_issued" x-bind:name="`ris_items[${index}][quantity_issued]`" class="ris-cell-input text-center">
                                                        <button type="button" class="ris-row-delete-hit" x-on:click="removeEditItem(index)" x-bind:disabled="editItems.length === 1" tabindex="-1" aria-label="Remove item row"></button>
                                                    </td>
                                                    <td>
                                                        <input type="number" min="0" step="0.01" x-model="item.unit_cost" x-bind:name="`ris_items[${index}][unit_cost]`" class="ris-cell-input text-right">
                                                        <button type="button" class="ris-row-delete-hit" x-on:click="removeEditItem(index)" x-bind:disabled="editItems.length === 1" tabindex="-1" aria-label="Remove item row"></button>
                                                    </td>
                                                    <td>
                                                        <input type="text" readonly tabindex="-1" x-bind:name="`ris_items[${index}][total_amount]`" x-bind:value="itemTotal(item).toFixed(2)" class="ris-cell-input cursor-not-allowed bg-gray-50 text-right text-gray-500">
                                                        <button type="button" class="ris-row-delete-hit" x-on:click="removeEditItem(index)" x-bind:disabled="editItems.length === 1" tabindex="-1" aria-label="Remove item row"></button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>

                                    <div class="ris-edit-add-row">
                                        <div class="ris-delete-mode-toggle" title="Turn on to delete rows by hovering">
                                            <span :class="!rowDeleteMode ? 'is-active' : ''">Off</span>
                                            <button
                                                type="button"
                                                class="ris-delete-mode-switch"
                                                :class="{ 'is-on': rowDeleteMode }"
                                                x-on:click="rowDeleteMode = !rowDeleteMode"
                                                :aria-pressed="rowDeleteMode ? 'true' : 'false'"
                                                aria-label="Toggle delete row mode"
                                            ></button>
                                            <span :class="rowDeleteMode ? 'is-active' : ''">Delete</span>
                                        </div>
                                        <button type="button" class="ris-add-item-btn" x-on:click="addEditItem()">+ Add Item</button>
                                    </div>

                                    <div class="ris-purpose-area">
                                        <div class="ris-purpose-label">PURPOSE</div>
                                        <div class="ris-purpose-line-row">
                                            <div class="ris-purpose-spacer"></div>
                                            <textarea name="ris_purpose_description" rows="2" class="ris-purpose-input">{{ $ris->ris_purpose_description }}</textarea>
                                        </div>
                                    </div>

                                    <div class="ris-signatures">
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Requested by:</div>
                                            <input type="text" name="ris_requested_by" value="{{ $ris->ris_requested_by_signature }}" class="ris-signature-input">
                                            <div class="ris-date-label">Date:</div>
                                            <input
                                                type="text"
                                                name="ris_requested_by_date"
                                                value="{{ $ris->ris_requested_by_date ? \Carbon\Carbon::parse($ris->ris_requested_by_date)->format('d/m/Y') : '' }}"
                                                placeholder="dd/mm/yyyy"
                                                inputmode="numeric"
                                                maxlength="10"
                                                autocomplete="off"
                                                x-on:input="formatDateInput($event)"
                                                class="ris-date-input"
                                            >
                                        </div>
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Approved by:</div>
                                            <div class="ris-signature-line ris-value-line ris-readonly-value">{{ (int) ($ris->has_approved_by_signature ?? 0) === 1 ? 'Signed' : ' ' }}</div>
                                            <div class="ris-date-label">Date:</div>
                                            <div class="ris-date-line ris-value-line ris-readonly-value">
                                                {{ $ris->ris_approved_by_date ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                                            </div>
                                        </div>
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Issued by:</div>
                                            <div class="ris-signature-line ris-value-line ris-readonly-value">{{ (int) ($ris->has_issued_by_signature ?? 0) === 1 ? 'Signed' : ' ' }}</div>
                                            <div class="ris-date-label">Date:</div>
                                            <div class="ris-date-line ris-value-line ris-readonly-value">
                                                {{ $ris->ris_issued_by_date ? \Carbon\Carbon::parse($ris->ris_issued_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                                            </div>
                                        </div>
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Received by:</div>
                                            <div class="ris-signature-line ris-value-line ris-readonly-value">{{ (int) ($ris->has_received_by_signature ?? 0) === 1 ? 'Signed' : ' ' }}</div>
                                            <div class="ris-date-label">Date:</div>
                                            <div class="ris-date-line ris-value-line ris-readonly-value">
                                                {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- SUPPORTING DOCUMENTS --}}
                            <div
                                class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white"
                                x-data="{
                                    editAttachmentName: '',
                                    onEditAttachmentsChange(event) {
                                        const file = event.target.files && event.target.files[0];
                                        this.editAttachmentName = file ? file.name : '';
                                        this.$nextTick(() => {
                                            if (window.lucide) {
                                                window.lucide.createIcons();
                                            }
                                        });
                                    },
                                    clearEditAttachments() {
                                        this.editAttachmentName = '';
                                        if (this.$refs.editAttachmentsInput) {
                                            this.$refs.editAttachmentsInput.value = '';
                                        }
                                    }
                                }"
                            >
                                <div class="flex items-center justify-between gap-3 px-3.5 py-2.5">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-950">Supporting Documents</p>
                                        <p class="truncate text-[11px] text-slate-500">
                                            @if($ris->risAttachments->isNotEmpty())
                                                {{ $ris->risAttachments->count() }} existing · 1 file at a time · Word/Excel
                                            @else
                                                Optional · 1 file at a time · Word/Excel
                                            @endif
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        x-show="editAttachmentName"
                                        x-cloak
                                        x-on:click="clearEditAttachments()"
                                        class="shrink-0 text-xs font-medium text-slate-500 transition hover:text-slate-950"
                                    >
                                        Clear
                                    </button>
                                </div>

                                <div class="space-y-1.5 border-t border-slate-100 px-3.5 py-2.5">
                                    @forelse($ris->risAttachments as $attachment)
                                        @php
                                            $ext = strtolower(pathinfo($attachment->ris_attachment_original_name, PATHINFO_EXTENSION));
                                            $isExcel = in_array($ext, ['xls', 'xlsx'], true);
                                            $isWord = in_array($ext, ['doc', 'docx'], true);
                                            $fileIconClass = $isExcel
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : ($isWord ? 'bg-blue-50 text-blue-700' : 'bg-slate-50 text-slate-500');
                                            $fileIcon = $isExcel ? 'file-spreadsheet' : ($isWord ? 'file-text' : 'paperclip');
                                        @endphp
                                        <div class="flex items-center gap-2 rounded-lg px-1.5 py-1.5 hover:bg-slate-50">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $fileIconClass }}">
                                                <i data-lucide="{{ $fileIcon }}" class="h-3.5 w-3.5"></i>
                                            </div>
                                            <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800">{{ $attachment->ris_attachment_original_name }}</p>
                                            @if($attachment->ris_attachment_size)
                                                <span class="shrink-0 text-[10px] text-slate-400">{{ number_format($attachment->ris_attachment_size / 1024, 1) }} KB</span>
                                            @endif
                                            <a
                                                href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}"
                                                data-tooltip="Download"
                                                aria-label="Download {{ $attachment->ris_attachment_original_name }}"
                                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-500 transition hover:bg-white hover:text-slate-950"
                                            >
                                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                            </a>
                                        </div>
                                    @empty
                                    @endforelse

                                    <div x-show="editAttachmentName" x-cloak class="flex items-center gap-2 rounded-lg bg-emerald-50/60 px-1.5 py-1.5">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500">
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        </div>
                                        <p class="min-w-0 flex-1 truncate text-xs font-medium text-slate-800" x-text="editAttachmentName"></p>
                                        <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">New</span>
                                    </div>

                                    <label
                                        x-show="!editAttachmentName"
                                        x-cloak
                                        class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 px-2.5 py-2 transition hover:border-slate-400 hover:bg-slate-50"
                                    >
                                        <input
                                            type="file"
                                            name="ris_attachments[]"
                                            accept=".doc,.docx,.xls,.xlsx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                            class="sr-only"
                                            x-ref="editAttachmentsInput"
                                            x-on:change="onEditAttachmentsChange($event)"
                                        >
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white text-slate-500 ring-1 ring-slate-200 transition group-hover:text-slate-800">
                                            <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-slate-800">Add file</p>
                                            <p class="truncate text-[10px] text-slate-500">1 file only · existing files stay</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- EDIT ACTION BUTTONS --}}
                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <p
                                x-show="risHasOverflow(editItems)"
                                x-cloak
                                class="mr-auto text-sm text-red-700"
                            >
                                Issued quantity for a split item is higher than requested.
                            </p>
                            <button
                                type="button"
                                x-on:click="closeEditRis({{ $ris->ris_id }})"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-950"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            >
                                Save Changes
                            </button>
                            @if($ris->ris_status === 'Draft')
                                <button
                                    type="submit"
                                    x-bind:disabled="risHasOverflow(editItems)"
                                    onclick="this.form.querySelector('input[name=save_action]').value='submit'"
                                    class="px-4 py-2 bg-[#0025cc] rounded-lg text-white text-[13px] font-medium hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Save & Submit
                                </button>
                            @endif
                            @if($ris->ris_status === 'Minor Revision')
                                <button
                                    type="submit"
                                    x-bind:disabled="risHasOverflow(editItems)"
                                    onclick="this.form.querySelector('input[name=save_action]').value='resubmit'"
                                    class="pur-btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Save & Resubmit
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
                </div>
            </div>
        @endif

    @endforeach
    </div>

    <div
        x-cloak
        x-show="submitRisConfirm"
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(!!submitRisConfirm, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        @keydown.escape.window="closeSubmitRis()"
    >
        <div @click.self="closeSubmitRis()" class="flex min-h-full w-full justify-center">
            <div class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="ris-submit-title">
                <form
                    method="POST"
                    x-bind:action="submitRisConfirm?.action || '#'"
                    x-on:submit="submitRisSending = true"
                >
                    @csrf
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#0025cc]">
                                <i data-lucide="send" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 id="ris-submit-title" class="text-lg font-semibold tracking-tight text-gray-950">Submit to Admin</h3>
                                <p class="mt-0.5 text-sm text-gray-500">This will send the RIS for review.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            x-on:click="closeSubmitRis()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <div class="px-5 py-5 text-sm leading-6 text-gray-600">
                        Submit <span class="font-semibold text-gray-900" x-text="submitRisConfirm?.number"></span> to Admin?
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button
                            type="button"
                            x-on:click="closeSubmitRis()"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:text-gray-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            x-bind:disabled="submitRisSending"
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#001fa8] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Yes, submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>


<script>
    // =====================================================
    // CREATE RIS: CALCULATE AMOUNT AUTOMATICALLY
    // Amount = Quantity Issued x Unit Cost
    // The controller recalculates this again before saving.
    // =====================================================
    document.addEventListener('input', function (event) {
        const input = event.target;

        if (
            !input.matches('input[name^="ris_items"][name$="[quantity_issued]"]') &&
            !input.matches('input[name^="ris_items"][name$="[unit_cost]"]')
        ) {
            return;
        }

        const row = input.closest('tr');
        if (!row) return;

        const quantityIssued = row.querySelector('input[name$="[quantity_issued]"]');
        const unitCost = row.querySelector('input[name$="[unit_cost]"]');
        const amount = row.querySelector('input[name$="[total_amount]"]');

        if (!quantityIssued || !unitCost || !amount) return;

        const quantity = Number(quantityIssued.value) || 0;
        const cost = Number(unitCost.value) || 0;

        amount.value = (quantity * cost).toFixed(2);
    });
</script>

<script>
    // ============================================================
    // RIS PRINT
    // Replace your OLD printRis() function with this whole script.
    // ============================================================

    function printRis(elementId) {

        // ========================================================
        // FIND THE RIS DOCUMENT
        // ========================================================
        const printElement = document.getElementById(elementId);

        if (!printElement) {
            console.error('RIS print element not found:', elementId);
            alert('Unable to find the RIS document to print.');
            return;
        }


        // ========================================================
        // CREATE A CLEAN COPY
        // This prevents us from modifying the actual modal.
        // ========================================================
        const printableElement = printElement.cloneNode(true);


        // ========================================================
        // REMOVE THINGS THAT SHOULD NEVER APPEAR IN PRINT
        // ========================================================
        printableElement.querySelectorAll(
            '.no-print, button, script'
        ).forEach(function (element) {
            element.remove();
        });


        // ========================================================
        // CLEAN RIS VALUES
        //
        // This is important for your issue.
        // We only keep visible text inside the printed RIS.
        // ========================================================
        printableElement.querySelectorAll('.ris-value-line').forEach(function (element) {

            const cleanText = (element.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();

            element.textContent = cleanText;
        });


        // ========================================================
        // OPEN PRINT WINDOW
        // ========================================================
        const printWindow = window.open(
            '',
            '_blank',
            'width=1200,height=900'
        );

        if (!printWindow) {
            alert('Please allow popups to print the RIS.');
            return;
        }


        // ========================================================
        // BUILD PRINT DOCUMENT
        // ========================================================
        printWindow.document.open();

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>

                <meta charset="UTF-8">

                <meta
                    name="viewport"
                    content="width=device-width, initial-scale=1.0"
                >

                <title>Requisition and Issue Slip</title>

                <style>

                    /* ============================================
                       PAGE
                       ============================================ */

                    @page {
                        size: A4 landscape;
                        margin: 4mm;
                    }

                    * {
                        box-sizing: border-box;
                    }

                    html,
                    body {
                        margin: 0;
                        padding: 0;
                        width: 100%;
                        background: #ffffff;
                        color: #000000;

                        font-family:
                            Arial,
                            Helvetica,
                            sans-serif;
                    }

                    body {
                        padding: 0;
                    }


                    /* ============================================
                       MAIN RIS FORM
                       ============================================ */

                    .ris-original-form {
                        width: 100%;
                        max-width: 100%;

                        margin: 0;

                        border: 1.5px solid #1f2937;

                        padding:
                            7mm
                            6mm
                            6mm
                            6mm;

                        font-family:
                            Arial,
                            Helvetica,
                            sans-serif;

                        color: #000000;

                        background: #ffffff;

                        page-break-inside: avoid;
                        break-inside: avoid;

                        overflow: hidden;
                    }


                    /* ============================================
                       DOCUMENT HEADER
                       ============================================ */

                    .ris-document-header {
                        position: relative;

                        height: 29mm;

                        text-align: center;
                    }

                    .ris-school-name {
                        margin: 0;

                        font-size: 14pt;
                        line-height: 1.15;

                        font-weight: 700;
                    }

                    .ris-document-title {
                        margin-top: 3mm;

                        font-size: 10.5pt;
                        line-height: 1.15;

                        font-weight: 700;
                    }


                    /* ============================================
                       RIS NUMBER
                       ============================================ */

                    .ris-number-area {
                        position: absolute;

                        right: 0;
                        bottom: 3mm;

                        display: flex;

                        align-items: flex-end;

                        gap: 3mm;
                    }

                    .ris-number-label {
                        font-size: 10pt;

                        font-weight: 600;

                        white-space: nowrap;
                    }

                    .ris-number-line {
                        display: flex;

                        align-items: flex-end;
                        justify-content: center;

                        width: 42mm;
                        min-height: 6mm;

                        padding:
                            0
                            1.5mm
                            1mm;

                        border-bottom:
                            0.3mm solid #1f2937;

                        font-size: 8pt;

                        line-height: 1.25;

                        text-align: center;

                        white-space: normal;

                        overflow-wrap: anywhere;

                        word-break: normal;
                    }


                    /* ============================================
                       RIS ITEMS TABLE
                       ============================================ */

                    .ris-items-table {
                        width: 100%;

                        border-collapse: collapse;

                        table-layout: fixed;

                        margin: 0;
                    }

                    .ris-items-table th,
                    .ris-items-table td {
                        border:
                            0.3mm solid #1f2937;
                    }

                    .ris-items-table th {
                        padding:
                            2mm
                            1mm;

                        text-align: center;

                        vertical-align: middle;

                        font-size: 8pt;

                        line-height: 1.1;

                        font-weight: 700;
                    }

                    .ris-items-table tbody td {
                        height: 10.5mm;

                        padding:
                            1mm
                            2mm;

                        vertical-align: middle;

                        font-size: 8pt;

                        overflow-wrap: anywhere;
                    }


                    /* ============================================
                       TABLE COLUMN WIDTHS
                       ============================================ */

                    .ris-item-column {
                        width: 20%;
                    }

                    .ris-brand-column {
                        width: 10%;
                    }

                    .ris-unit-column {
                        width: 7%;
                    }

                    .ris-supplier-column {
                        width: 14%;
                    }

                    .ris-quantity-header {
                        width: 20%;
                    }

                    .ris-requested-column {
                        width: 10%;

                        font-size: 7pt !important;
                    }

                    .ris-issued-column {
                        width: 10%;

                        font-size: 7pt !important;
                    }

                    .ris-unit-cost-column {
                        width: 15%;
                    }

                    .ris-amount-column {
                        width: 15%;
                    }


                    /* ============================================
                       PURPOSE
                       ============================================ */

                    .ris-purpose-area {
                        margin-top: 7mm;
                    }

                    .ris-purpose-label {
                        font-size: 8.5pt;

                        font-weight: 700;
                    }

                    .ris-purpose-line-row {
                        display: flex;

                        align-items: flex-end;

                        margin-top: 6mm;

                        width: 100%;
                    }

                    .ris-purpose-spacer {
                        width: 21mm;

                        flex-shrink: 0;
                    }

                    .ris-purpose-line {
                        flex: 1;

                        min-width: 0;

                        min-height: 6mm;

                        padding:
                            0
                            1.5mm
                            1mm;

                        border-bottom:
                            0.3mm solid #1f2937;

                        font-size: 8pt;

                        line-height: 1.25;

                        overflow-wrap: anywhere;
                    }


                    /* ============================================
                       SIGNATURE SECTION
                       ============================================ */

                    .ris-signatures {
                        display: grid;

                        grid-template-columns:
                            repeat(4, minmax(0, 1fr));

                        column-gap: 8mm;

                        margin-top: 8mm;

                        page-break-inside: avoid;

                        break-inside: avoid;
                    }

                    .ris-signature-column {
                        min-width: 0;

                        overflow: hidden;
                    }

                    .ris-signature-label {
                        font-size: 7.5pt;
                    }

                    .ris-signature-line {
                        display: flex;

                        align-items: flex-end;

                        justify-content: center;

                        width: 100%;

                        height: 10mm;

                        min-width: 0;

                        padding:
                            0
                            1.5mm
                            1mm;

                        border-bottom:
                            0.3mm solid #1f2937;

                        font-size: 8pt;

                        line-height: 1.25;

                        text-align: center;

                        overflow: hidden;

                        overflow-wrap: anywhere;

                        word-break: normal;
                    }


                    /* ============================================
                       SIGNATURE DATE
                       ============================================ */

                    .ris-date-label {
                        margin-top: 3mm;

                        font-size: 7.5pt;
                    }

                    .ris-date-line {
                        display: flex;

                        align-items: flex-end;

                        justify-content: center;

                        width: 100%;

                        height: 6mm;

                        min-width: 0;

                        padding:
                            0
                            1.5mm
                            1mm;

                        border-bottom:
                            0.3mm solid #1f2937;

                        font-size: 8pt;

                        line-height: 1.25;

                        text-align: center;

                        overflow: hidden;

                        overflow-wrap: anywhere;
                    }


                    /* ============================================
                       VALUE LINES
                       ============================================ */

                    .ris-value-line {
                        min-width: 0;

                        max-width: 100%;

                        font-size: 8pt;

                        line-height: 1.25;
                    }


                    /* ============================================
                       BASIC HELPERS
                       ============================================ */

                    .text-center {
                        text-align: center;
                    }

                    .text-right {
                        text-align: right;
                    }

                    .font-bold,
                    .font-semibold {
                        font-weight: bold;
                    }

                    .uppercase {
                        text-transform: uppercase;
                    }


                    /* ============================================
                       NEVER PRINT THESE
                       ============================================ */

                    button,
                    .no-print {
                        display: none !important;
                    }


                    /* ============================================
                       PRINT
                       ============================================ */

                    @media print {

                        html,
                        body {
                            width: 100%;

                            margin: 0 !important;

                            padding: 0 !important;
                        }

                        .ris-original-form {
                            width: 100%;

                            margin: 0 !important;

                            page-break-inside: avoid;

                            break-inside: avoid;
                        }

                    }

                </style>

            </head>

            <body>

                ${printableElement.outerHTML}

            </body>

            </html>
        `);

        printWindow.document.close();


        // ========================================================
        // WAIT UNTIL THE NEW PRINT WINDOW IS READY
        // ========================================================
        printWindow.onload = function () {

            printWindow.focus();

            setTimeout(function () {

                printWindow.print();

            }, 300);

        };

    }
</script>

@endsection