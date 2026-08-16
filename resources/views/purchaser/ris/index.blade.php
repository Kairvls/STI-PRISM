@extends('layouts.purchaser-layout')

@section("page-title", "Requisition Issue Slip")

@section("page-subtitle", "Manage Requisition and Issue Slips")

@section("content")

<div
    x-data="{
        openModal: @js(request('ris_id') ? 'ris-' . request('ris_id') : null),
        editRisModal: null,
        createRisModal: {{ ($errors->any() || request()->filled('replacement_request')) ? 'true' : 'false' }},
        selectedReplacement: @js((string) old('ris_procurement_request_id', request('replacement_request', ''))),
        replacementRequests: @js(
            collect($availableReplacementRequests ?? [])->map(function ($request) {
                return [
                    'id' => $request->procurement_request_id,
                    'report_id' => $request->report_id ?? null,
                    'equipment' => $request->equipment_name ?: ($request->report_unlisted_equipment_name ?: 'Unspecified equipment'),
                    'asset_tag' => $request->equipment_asset_tag ?? null,
                    'room' => $request->room_name ?? 'Unspecified room',
                    'problem' => $request->report_problem_description ?? '',
                    'reason' => $request->report_replacement_notes ?? '',
                ];
            })->values()
        ),
        selectedReplacementData() {
            return this.replacementRequests.find(item => String(item.id) === String(this.selectedReplacement)) || null;
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
        recordsLoading: false,
        searchTimer: null,
        async refreshRisRecords(url = null) {
            const form = this.$refs.risFilterForm;
            if (!form) return;

            const targetUrl = url || form.action;
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');

            let requestUrl = targetUrl;

            if (!url) {
                const query = params.toString();
                requestUrl = query ? `${form.action}?${query}` : form.action;
            }

            this.recordsLoading = true;

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

                if (!nextRecords || !currentRecords) {
                    throw new Error('RIS records section was not found.');
                }

                currentRecords.innerHTML = nextRecords.innerHTML;

                const nextUrl = new URL(requestUrl, window.location.origin);
                window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);
            } catch (error) {
                console.error(error);
            } finally {
                this.recordsLoading = false;
            }
        }
    }"
    x-cloak
>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Please fix the following RIS form errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-gray-900"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Procurement</span>
                </div>

                <h1 class="text-3xl font-semibold tracking-tight text-gray-950">Requisition &amp; Issue Slips</h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                    Manage RIS drafts, submissions, revisions, approvals, and procurement records.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    x-on:click="openModal = 'empty-ris'"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50"
                >
                    Print Empty RIS
                </button>

                <button
                    type="button"
                    x-on:click="createRisModal = true"
                    class="rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800"
                >
                    + New RIS
                </button>
            </div>
        </div>
    </div>

    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid grid-cols-2 divide-gray-100 sm:grid-cols-3 lg:grid-cols-5 lg:divide-x">
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $risSummary['total'] }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Total RIS</p>
            </div>
            <div class="px-5 py-5">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $risSummary['draft'] }}</p>
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Draft</p>
            </div>
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $risSummary['submitted'] }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">In Review</p>
            </div>
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $risSummary['approved'] }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Approved</p>
            </div>
            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $risSummary['rejected'] }}</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Rejected</p>
            </div>
        </div>
    </div>

    {{-- PRINT EMPTY RIS MODAL --}}

    <div
        x-cloak
        x-show="openModal === 'empty-ris'"
        x-on:keydown.escape.window="openModal = null"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
    >
        <div
            x-on:click.self="openModal = null"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto w-full max-w-6xl rounded-xl bg-white shadow-2xl">

                {{-- MODAL HEADER --}}
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Print Empty RIS
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Original blank Requisition and Issue Slip format.
                        </p>
                    </div>

                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                    >
                        Close
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
                <div class="print-hidden flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">

                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        onclick="printRis('print-empty-ris-content')"
                        class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Print Empty RIS
                    </button>

                </div>

            </div>
        </div>
    </div>

    <style>

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

        /* Column widths matching the original */
        .ris-item-column {
            width: 40%;
        }

        .ris-quantity-header {
            width: 23%;
        }

        .ris-requested-column {
            width: 11%;
            font-size: 11px !important;
        }

        .ris-issued-column {
            width: 12%;
            font-size: 11px !important;
        }

        .ris-unit-cost-column {
            width: 17%;
        }

        .ris-amount-column {
            width: 20%;
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
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .ris-remove-item {
            flex: 0 0 auto;
            width: 22px;
            height: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            font-size: 15px;
            line-height: 1;
            color: #6b7280;
            background: #fff;
        }

        .ris-remove-item:disabled {
            opacity: .35;
            cursor: not-allowed;
        }

        .ris-edit-add-row {
            display: flex;
            justify-content: flex-end;
            padding-top: 10px;
        }

        .ris-edit-add-row button {
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #fff;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 600;
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
        }

        .ris-date-input {
            width: 100%;
            height: 31px;
            padding: 4px;
            text-align: center;
            font-size: 11px;
        }

    </style>
    <div
        x-show="createRisModal"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        {{-- MODAL CONTAINER --}}
        <div
            x-on:click.outside="createRisModal = false"
            class="max-h-[95vh] w-full max-w-6xl overflow-y-auto rounded-xl bg-white shadow-2xl"
        >
            <form method="POST" action="{{ route('purchaser.ris.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- CREATE RIS: Save button creates a Draft --}}
        <input type="hidden" name="save_action" value="draft">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <label class="mb-2 block text-sm font-medium text-gray-900">
                        Source Replacement Request
                    </label>

                    <select
                        name="ris_procurement_request_id"
                        x-model="selectedReplacement"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-gray-500"
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

                    <p class="mt-2 text-xs text-gray-500">
                        Select an approved replacement request, or leave this blank for a manual RIS.
                    </p>
                </div>

                <div
                    x-show="selectedReplacementData()"
                    class="border-b border-gray-200 bg-white px-6 py-5"
                >
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">
                                    Replacement Request Details
                                </p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">
                                    Request #<span x-text="selectedReplacement"></span>
                                </p>
                            </div>

                            <span class="rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                Approved
                            </span>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <p class="text-xs text-gray-400">Equipment</p>
                                <p class="mt-1 text-sm font-medium text-gray-800" x-text="selectedReplacementData()?.equipment"></p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Asset Tag</p>
                                <p class="mt-1 text-sm font-medium text-gray-800" x-text="selectedReplacementData()?.asset_tag || 'Not specified'"></p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Location</p>
                                <p class="mt-1 text-sm font-medium text-gray-800" x-text="selectedReplacementData()?.room"></p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-400">Original Report</p>
                                <p class="mt-1 text-sm font-medium text-gray-800">
                                    #<span x-text="selectedReplacementData()?.report_id || 'N/A'"></span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div x-show="selectedReplacementData()?.problem">
                                <p class="text-xs text-gray-400">Reported Problem</p>
                                <p class="mt-1 text-sm leading-6 text-gray-700" x-text="selectedReplacementData()?.problem"></p>
                            </div>

                            <div x-show="selectedReplacementData()?.reason">
                                <p class="text-xs text-gray-400">Replacement Reason</p>
                                <p class="mt-1 text-sm leading-6 text-gray-700" x-text="selectedReplacementData()?.reason"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL TOP BAR --}}
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Create Requisition and Issue Slip</h3>
                        <p class="text-sm text-gray-500">Fill out the RIS form below.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="createRisModal = false"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-xl text-gray-600 hover:bg-gray-200"
                    >
                        ×
                    </button>
                </div>

                {{-- PHYSICAL RIS DOCUMENT --}}
                <div class="p-6">
                    <div class="mx-auto border-2 border-gray-800 bg-white p-6">
                        {{-- RIS HEADER --}}
                        <div class="relative mb-5 text-center">
                            <h2 class="text-lg font-bold uppercase tracking-wide text-gray-900">STI COLLEGE - ORMOC, INC.</h2>
                            <h3 class="mt-1 text-base font-bold uppercase text-gray-900">REQUISITION AND ISSUE SLIP</h3>
                            <div class="mt-4 flex justify-end">
                                <div class="flex items-end gap-2">
                                    <label class="text-sm font-medium">No.</label>
                                    <input
                                        type="text"
                                        name="ris_form_number"
                                        value="{{ old('ris_form_number') }}"
                                        class="w-40 border-0 border-b border-gray-800 bg-transparent px-1 py-1 text-sm outline-none focus:ring-0"
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- RIS ITEMS TABLE --}}
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border border-gray-800">
                                <thead>
                                    <tr>
                                        <th
                                            rowspan="2"
                                            class="w-[40%] border border-gray-800 px-3 py-2 text-center text-xs font-bold uppercase"
                                        >
                                            Item
                                        </th>

                                        <th
                                            colspan="2"
                                            class="border border-gray-800 px-3 py-2 text-center text-xs font-bold uppercase"
                                        >
                                            Quantity
                                        </th>

                                        <th
                                            rowspan="2"
                                            class="w-[17%] border border-gray-800 px-3 py-2 text-center text-xs font-bold uppercase"
                                        >
                                            Unit Cost
                                        </th>

                                        <th
                                            rowspan="2"
                                            class="w-[20%] border border-gray-800 px-3 py-2 text-center text-xs font-bold uppercase"
                                        >
                                            Amount
                                        </th>
                                    </tr>

                                    <tr>
                                        <th class="w-[11%] border border-gray-800 px-2 py-2 text-center text-[11px] font-bold uppercase">
                                            Requested
                                        </th>

                                        <th class="w-[12%] border border-gray-800 px-2 py-2 text-center text-[11px] font-bold uppercase">
                                            Issued
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @for($i = 0; $i < 8; $i++)
                                        <tr>
                                            {{-- ITEM --}}
                                            <td class="border border-gray-800 p-1">
                                                @if($i === 0)
                                                    <input
                                                        type="text"
                                                        name="ris_items[{{ $i }}][name_description]"
                                                        x-bind:value="selectedReplacementData() ? selectedReplacementData().equipment : @js(old('ris_items.0.name_description', ''))"
                                                        class="w-full border-0 bg-transparent px-2 py-2 text-sm outline-none focus:ring-0"
                                                    >
                                                @else
                                                    <input
                                                        type="text"
                                                        name="ris_items[{{ $i }}][name_description]"
                                                        value="{{ old('ris_items.' . $i . '.name_description') }}"
                                                        class="w-full border-0 bg-transparent px-2 py-2 text-sm outline-none focus:ring-0"
                                                    >
                                                @endif
                                            </td>

                                            {{-- QUANTITY REQUESTED --}}
                                            <td class="border border-gray-800 p-1">
                                                <input
                                                    type="number"
                                                    name="ris_items[{{ $i }}][quantity_requested]"
                                                    value="{{ old('ris_items.' . $i . '.quantity_requested') }}"
                                                    min="1"
                                                    class="w-full border-0 bg-transparent px-1 py-2 text-center text-sm outline-none focus:ring-0"
                                                >
                                            </td>

                                            {{-- QUANTITY ISSUED --}}
                                            <td class="border border-gray-800 p-1">
                                                <input
                                                    type="number"
                                                    name="ris_items[{{ $i }}][quantity_issued]"
                                                    value="{{ old('ris_items.' . $i . '.quantity_issued') }}"
                                                    min="0"
                                                    class="w-full border-0 bg-transparent px-1 py-2 text-center text-sm outline-none focus:ring-0"
                                                >
                                            </td>

                                            {{-- UNIT COST --}}
                                            <td class="border border-gray-800 p-1">
                                                <input
                                                    type="number"
                                                    name="ris_items[{{ $i }}][unit_cost]"
                                                    value="{{ old('ris_items.' . $i . '.unit_cost') }}"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-full border-0 bg-transparent px-2 py-2 text-right text-sm outline-none focus:ring-0"
                                                >
                                            </td>

                                            {{-- AMOUNT --}}
                                            <td class="border border-gray-800 p-1">
                                                <input
                                                    type="text"
                                                    name="ris_items[{{ $i }}][total_amount]"
                                                    value="{{ old('ris_items.' . $i . '.total_amount', '0.00') }}"
                                                    readonly
                                                    tabindex="-1"
                                                    class="w-full cursor-not-allowed border-0 bg-gray-50 px-2 py-2 text-right text-sm text-gray-500 outline-none focus:ring-0"
                                                >
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        {{-- PURPOSE --}}
                        <div class="mt-5">
                            <div class="flex items-start gap-4">
                                <label class="pt-2 text-sm font-bold uppercase text-gray-900">Purpose</label>
                                <textarea
                                    name="ris_purpose_description"
                                    rows="2"
                                    x-bind:value="selectedReplacementData() ? replacementPurpose() : @js(old('ris_purpose_description', ''))"
                                    class="flex-1 resize-none border-0 border-b border-gray-800 bg-transparent px-2 py-2 text-sm outline-none focus:ring-0"
                                ></textarea>
                            </div>
                        </div>

                        {{-- SIGNATURE / PERSONNEL AREA --}}
                        <div class="mt-10 grid grid-cols-2 gap-x-8 gap-y-8 md:grid-cols-4">
                            {{-- REQUESTED BY --}}
                            <div>
                                <label class="block text-xs text-gray-600">Requested by:</label>
                                <input
                                    type="text"
                                    name="ris_requested_by"
                                    value="{{ old('ris_requested_by') }}"
                                    class="mt-5 w-full border-0 border-b border-gray-800 bg-transparent px-1 py-1 text-sm outline-none focus:ring-0"
                                >
                                <label class="mt-4 block text-xs text-gray-600">Date:</label>
                                <input
                                    type="text"
                                    name="ris_requested_by_date"
                                    value="{{ old('ris_requested_by_date') }}"
                                    placeholder="dd/mm/yyyy"
                                    inputmode="numeric"
                                    maxlength="10"
                                    autocomplete="off"
                                    class="mt-1 w-full border-0 border-b border-gray-800 bg-transparent px-1 py-1 text-center text-sm outline-none focus:ring-0"
                                >
                            </div>
                            {{-- APPROVED BY --}}
                            <div>
                                <label class="block text-xs text-gray-600">Approved by:</label>
                                <div class="mt-5 h-[29px] w-full border-b border-gray-800"></div>
                                <label class="mt-4 block text-xs text-gray-600">Date:</label>
                                <div class="mt-1 flex h-[29px] w-full items-center justify-center border-b border-gray-800 text-sm text-gray-400">
                                    dd/mm/yyyy
                                </div>
                            </div>
                            {{-- ISSUED BY --}}
                            <div>
                                <label class="block text-xs text-gray-600">Issued by:</label>
                                <div class="mt-5 h-[29px] w-full border-b border-gray-800"></div>
                                <label class="mt-4 block text-xs text-gray-600">Date:</label>
                                <div class="mt-1 flex h-[29px] w-full items-center justify-center border-b border-gray-800 text-sm text-gray-400">
                                    dd/mm/yyyy
                                </div>
                            </div>
                            {{-- RECEIVED BY --}}
                            <div>
                                <label class="block text-xs text-gray-600">Received by:</label>
                                <div class="mt-5 h-[29px] w-full border-b border-gray-800"></div>
                                <label class="mt-4 block text-xs text-gray-600">Date:</label>
                                <div class="mt-1 flex h-[29px] w-full items-center justify-center border-b border-gray-800 text-sm text-gray-400">
                                    dd/mm/yyyy
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SUPPORTING DOCUMENTS (system-only, kept outside physical RIS) --}}
                    <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <label class="block text-sm font-medium text-gray-900">Supporting Documents</label>
                        <input type="file" name="ris_attachments[]" multiple class="mt-3 block w-full text-sm text-gray-500">
                        <p class="mt-2 text-xs text-gray-500">Word or Excel files only: .doc, .docx, .xls, .xlsx</p>
                    </div>
                </div>

                {{-- MODAL ACTIONS --}}
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="createRisModal = false"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        Save RIS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="ris-records-section" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

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
                    <p class="mt-1 text-xs text-gray-400">View and manage your requisition records.</p>
                </div>

                <form
                    method="GET"
                    action="{{ route('purchaser.ris.index') }}"
                    x-ref="risFilterForm"
                    x-on:submit.prevent="refreshRisRecords()"
                    class="flex flex-col gap-2 sm:flex-row"
                >
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            x-on:input="
                                clearTimeout(searchTimer);
                                searchTimer = setTimeout(() => refreshRisRecords(), 350);
                            "
                            placeholder="Search RIS..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select name="status" x-on:change="refreshRisRecords()" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(['Draft', 'Submitted', 'Under Review', 'Minor Revision', 'Resubmitted', 'Approved', 'Rejected'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="date_from" value="{{ request('date_from') }}" x-on:change="refreshRisRecords()" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" x-on:change="refreshRisRecords()" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

                    <button
                        type="submit"
                        x-bind:disabled="recordsLoading"
                        class="rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-wait disabled:opacity-60"
                    >
                        <span x-show="!recordsLoading">Apply</span>
                        <span x-show="recordsLoading">Loading...</span>
                    </button>

                    @if(request()->filled('search') || request()->filled('status') || request()->filled('date_from') || request()->filled('date_to'))
                        <button
                            type="button"
                            x-on:click="
                                $refs.risFilterForm.reset();
                                refreshRisRecords('{{ route('purchaser.ris.index') }}');
                            "
                            class="rounded-xl border border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-gray-600 transition hover:bg-gray-50"
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
                                @if($ris->risAttachments->isNotEmpty())
                                    <div class="inline-flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.172 7-6.586 6.586a2 2 0 1 0 2.828 2.828L18 9.828a4 4 0 1 0-5.657-5.657L5.757 10.757a6 6 0 0 0 8.486 8.486L20 13.486" />
                                        </svg>
                                        <span>{{ $ris->risAttachments->count() }} {{ $ris->risAttachments->count() === 1 ? 'file' : 'files' }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">No files</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @php
                                    $issuedBy = trim((string) ($ris->ris_issued_by_signature ?? ''));
                                    $presidentSig = trim((string) ($ris->ris_approved_by_signature ?? ''));
                                    $awaitingAdmin = in_array($ris->ris_status, ['Approved', 'Approved by the President'], true)
                                        && $presidentSig !== ''
                                        && $issuedBy === '';

                                    $statusClass = $awaitingAdmin
                                        ? 'bg-amber-50 text-amber-700'
                                        : match($ris->ris_status) {
                                            'Draft' => 'bg-gray-100 text-gray-700',
                                            'Submitted' => 'bg-blue-50 text-blue-700',
                                            'Under Review' => 'bg-amber-50 text-amber-700',
                                            'Minor Revision' => 'bg-yellow-50 text-amber-600',
                                            'Resubmitted' => 'bg-indigo-50 text-indigo-700',
                                            'Approved' => 'bg-green-50 text-green-700',
                                            'Forwarded to President' => 'bg-blue-50 text-blue-700',
                                            'Approved by the President' => 'bg-green-50 text-green-700',
                                            'Directly Approved' => 'bg-sky-50 text-sky-700',
                                            'Rejected' => 'bg-red-50 text-red-700',
                                            'Rejected by President' => 'bg-red-50 text-red-700',
                                            'Rejected by the President' => 'bg-red-50 text-red-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        };

                                    if ($ris->ris_status === 'Directly Approved') {
                                        $statusLabel = 'Admin Approved';
                                    } elseif (in_array($ris->ris_status, ['Rejected by President', 'Rejected by the President'], true)) {
                                        $statusLabel = 'Rejected by the President';
                                    } elseif ($ris->ris_status === 'Forwarded to President' || ($ris->ris_status === 'Approved' && $presidentSig === '')) {
                                        $statusLabel = 'Forwarded to President';
                                    } elseif ($awaitingAdmin) {
                                        $statusLabel = 'Awaiting Admin';
                                    } elseif (in_array($ris->ris_status, ['Approved', 'Approved by the President'], true) && $presidentSig !== '' && $issuedBy !== '') {
                                        $statusLabel = 'Approved by the President';
                                    } elseif ($ris->ris_status === 'Approved by the President') {
                                        $statusLabel = 'Approved by the President';
                                    } else {
                                        $statusLabel = $ris->ris_status;
                                    }
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                @if($ris->ris_submitted_at)
                                    <p class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($ris->ris_submitted_at)->format('M d, Y') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">{{ \Carbon\Carbon::parse($ris->ris_submitted_at)->format('h:i A') }}</p>
                                @else
                                    <span class="text-xs text-gray-400">Not submitted</span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    x-on:click="openModal = 'ris-{{ $ris->ris_id }}'"
                                    class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-950"
                                >
                                    View
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                                    </svg>
                                </button>
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

    @foreach($risRecords as $ris)

        {{-- VIEW RIS MODAL --}}
        <div
            x-cloak
            x-show="openModal === 'ris-{{ $ris->ris_id }}'"
            x-on:keydown.escape.window="openModal = null"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div
                x-on:click.self="openModal = null"
                class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
            >
                {{-- MODAL HEADER --}}
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-xl font-semibold text-gray-900">
                                {{ $ris->ris_form_number ?: 'Draft RIS' }}
                            </h3>

                            @php
                                $issuedBy = trim((string) ($ris->ris_issued_by_signature ?? ''));
                                $presidentSig = trim((string) ($ris->ris_approved_by_signature ?? ''));
                                $awaitingAdmin = in_array($ris->ris_status, ['Approved', 'Approved by the President'], true)
                                    && $presidentSig !== ''
                                    && $issuedBy === '';

                                $statusClasses = $awaitingAdmin
                                    ? 'border-amber-200 bg-amber-50 text-amber-700'
                                    : match($ris->ris_status) {
                                        'Draft' => 'border-gray-200 bg-gray-100 text-gray-700',
                                        'Submitted' => 'border-blue-200 bg-blue-50 text-blue-700',
                                        'Under Review' => 'border-amber-200 bg-amber-50 text-amber-700',
                                        'Minor Revision' => 'border-yellow-300 bg-yellow-50 text-amber-600',
                                        'Resubmitted' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
                                        'Approved' => 'border-green-200 bg-green-50 text-green-700',
                                        'Forwarded to President' => 'border-blue-200 bg-blue-50 text-blue-700',
                                        'Approved by the President' => 'border-green-200 bg-green-50 text-green-700',
                                        'Directly Approved' => 'border-sky-200 bg-sky-50 text-sky-700',
                                        'Rejected' => 'border-red-200 bg-red-50 text-red-700',
                                        'Rejected by President' => 'border-red-200 bg-red-50 text-red-700',
                                        'Rejected by the President' => 'border-red-200 bg-red-50 text-red-700',
                                        default => 'border-gray-200 bg-gray-100 text-gray-700',
                                    };

                                if ($ris->ris_status === 'Directly Approved') {
                                    $statusLabel = 'Admin Approved';
                                } elseif (in_array($ris->ris_status, ['Rejected by President', 'Rejected by the President'], true)) {
                                    $statusLabel = 'Rejected by the President';
                                } elseif ($ris->ris_status === 'Forwarded to President' || ($ris->ris_status === 'Approved' && $presidentSig === '')) {
                                    $statusLabel = 'Forwarded to President';
                                } elseif ($awaitingAdmin) {
                                    $statusLabel = 'Awaiting Admin';
                                } elseif (in_array($ris->ris_status, ['Approved', 'Approved by the President'], true) && $presidentSig !== '' && $issuedBy !== '') {
                                    $statusLabel = 'Approved by the President';
                                } elseif ($ris->ris_status === 'Approved by the President') {
                                    $statusLabel = 'Approved by the President';
                                } else {
                                    $statusLabel = $ris->ris_status;
                                }
                            @endphp

                            <span class="rounded-full border px-3 py-1 text-xs font-medium {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-gray-500">Requisition and Issue Slip</p>
                    </div>

                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>

                {{-- SCROLLABLE MODAL CONTENT --}}
                <div class="min-h-0 flex-1 overflow-y-auto p-6">

                    {{-- CURRENT MINOR REVISION NOTICE --}}
                    @if($ris->ris_status === 'Minor Revision' && $ris->risRevisions->isNotEmpty())
                        @php($latestRevision = $ris->risRevisions->first())
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

                    {{{-- RIS DOCUMENT: SAME PHYSICAL DESIGN AS PRINT EMPTY RIS --}}}
                    <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
                        <div class="ris-original-form mx-auto bg-white text-black">
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
                                    @php($item = $ris->risItems->get($row))
                                    <tr>
                                        <td>{{ $item?->ris_item_name_description ?: ' ' }}</td>
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
                                    ['Approved by:', $ris->ris_approved_by_signature, $ris->ris_approved_by_date],
                                    ['Issued by:', $ris->ris_issued_by_signature, $ris->ris_issued_by_date],
                                    ['Received by:', $ris->ris_received_by_signature, $ris->ris_received_by_date],
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

                    {{-- SUPPORTING DOCUMENTS --}}
                    <div class="mt-8">
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Supporting Documents</h4>
                        @if($ris->risAttachments->isNotEmpty())
                            <div class="mt-3 divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200">
                                @foreach($ris->risAttachments as $attachment)
                                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-gray-900">{{ $attachment->ris_attachment_original_name }}</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                @if($attachment->ris_attachment_size)
                                                    {{ number_format($attachment->ris_attachment_size / 1024, 1) }} KB
                                                @else
                                                    File attachment
                                                @endif
                                            </p>
                                        </div>
                                        <a
                                            href="{{ route('purchaser.ris.attachments.download', $attachment->ris_attachment_id) }}"
                                            class="shrink-0 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            Download
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-3 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center">
                                <p class="text-sm text-gray-500">No supporting documents attached.</p>
                            </div>
                        @endif
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
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="openModal = null"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Close
                    </button>

                    <div class="flex flex-wrap justify-end gap-2">

                        <button
                            type="button"
                            x-on:click="openModal = 'print-ris-{{ $ris->ris_id }}'"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            Print RIS
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
                            <form method="POST" action="{{ route('purchaser.ris.submit', $ris->ris_id) }}">
                                @csrf
                                <button
                                    type="submit"
                                    onclick="return confirm('Submit this RIS to Admin?')"
                                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                >
                                    Submit to Admin
                                </button>
                            </form>
                        @endif

                        {{-- CREATE ATP --}}
                        @if(!empty($ris->can_create_atp))
                            @if(!$ris->has_atp)
                                <a
                                    href="{{ route('purchaser.atp.create', ['selected_ris' => $ris->ris_id]) }}"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                >
                                    Create ATP
                                </a>
                            @else
                                <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700">
                                    ATP Created
                                </span>
                            @endif
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
        >
            <div x-on:click.self="openModal = null" class="flex min-h-full w-full justify-center">
                <div class="my-auto w-full max-w-5xl rounded-xl bg-white shadow-2xl">

                    {{-- PRINT PREVIEW HEADER --}}
                    <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Print RIS</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $ris->ris_form_number ?: 'No RIS Number' }}</p>
                        </div>
                        <button
                            type="button"
                            x-on:click="openModal = null"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Close
                        </button>
                    </div>

                    {{-- RIS DOCUMENT: SAME PHYSICAL DESIGN AS PRINT EMPTY RIS --}}
                    <div class="overflow-x-auto bg-gray-100 p-4 md:p-6">
                        <div class="ris-original-form mx-auto bg-white text-black">
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
                                    @php($item = $ris->risItems->get($row))
                                    <tr>
                                        <td>{{ $item?->ris_item_name_description ?: ' ' }}</td>
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
                                    ['Approved by:', $ris->ris_approved_by_signature, $ris->ris_approved_by_date],
                                    ['Issued by:', $ris->ris_issued_by_signature, $ris->ris_issued_by_date],
                                    ['Received by:', $ris->ris_received_by_signature, $ris->ris_received_by_date],
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
                    <div class="print-hidden flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <button
                            type="button"
                            x-on:click="openModal = null"
                            class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            onclick="printRis('print-ris-content-{{ $ris->ris_id }}')"
                            class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            Print RIS
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(in_array($ris->ris_status, ['Draft', 'Minor Revision'], true))
            <div
                x-cloak
                x-show="editRisModal === 'edit-ris-{{ $ris->ris_id }}'"
                x-data="{
                    editItems: [
                        @forelse($ris->risItems as $item)
                            {
                                name_description: @js($item->ris_item_name_description ?? ''),
                                quantity_requested: @js($item->ris_quantity_requested ?? 1),
                                quantity_issued: @js($item->ris_quantity_issued ?? 0),
                                unit_cost: @js($item->ris_unit_cost ?? 0)
                            }{{ !$loop->last ? ',' : '' }}
                        @empty
                            { name_description: '', quantity_requested: 1, quantity_issued: 0, unit_cost: 0 }
                        @endforelse
                    ],
                    addEditItem() {
                        this.editItems.push({ name_description: '', quantity_requested: 1, quantity_issued: 0, unit_cost: 0 });
                    },
                    removeEditItem(index) {
                        if (this.editItems.length > 1) { this.editItems.splice(index, 1); }
                    },
                    itemTotal(item) {
                        return (Number(item.quantity_issued) || 0) * (Number(item.unit_cost) || 0);
                    }
                }"
                x-on:keydown.escape.window="editRisModal = null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div
                    x-on:click.self="editRisModal = null"
                    class="flex max-h-[95vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
                >
                    {{-- EDIT MODAL HEADER --}}
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Edit RIS</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $ris->ris_form_number ?: 'Draft RIS' }}</p>
                        </div>
                        <button
                            type="button"
                            x-on:click="editRisModal = null"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Close
                        </button>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('purchaser.ris.update', $ris->ris_id) }}"
                        enctype="multipart/form-data"
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
                                @php($latestRevision = $ris->risRevisions->first())
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
                                                class="ris-number-input"
                                            >
                                        </div>
                                    </div>

                                    <table class="ris-items-table">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" class="ris-item-column">ITEM</th>
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
                                                <tr>
                                                    <td>
                                                        <div class="ris-edit-item-cell">
                                                            <input type="text" x-model="item.name_description" x-bind:name="`ris_items[${index}][name_description]`" class="ris-cell-input">
                                                            <button type="button" x-on:click="removeEditItem(index)" x-bind:disabled="editItems.length === 1" class="ris-remove-item" title="Remove item">×</button>
                                                        </div>
                                                    </td>
                                                    <td><input type="number" min="1" x-model="item.quantity_requested" x-bind:name="`ris_items[${index}][quantity_requested]`" class="ris-cell-input text-center"></td>
                                                    <td><input type="number" min="0" x-model="item.quantity_issued" x-bind:name="`ris_items[${index}][quantity_issued]`" class="ris-cell-input text-center"></td>
                                                    <td><input type="number" min="0" step="0.01" x-model="item.unit_cost" x-bind:name="`ris_items[${index}][unit_cost]`" class="ris-cell-input text-right"></td>
                                                    <td><input type="text" readonly tabindex="-1" x-bind:name="`ris_items[${index}][total_amount]`" x-bind:value="itemTotal(item).toFixed(2)" class="ris-cell-input cursor-not-allowed bg-gray-50 text-right text-gray-500"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>

                                    <div class="ris-edit-add-row">
                                        <button type="button" x-on:click="addEditItem()">+ Add Item</button>
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
                                                class="ris-date-input"
                                            >
                                        </div>
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Approved by:</div>
                                            <div class="ris-signature-line ris-value-line">{{ $ris->ris_approved_by_signature ?: ' ' }}</div>
                                            <div class="ris-date-label">Date:</div>
                                            <div class="ris-date-line ris-value-line">
                                                {{ $ris->ris_approved_by_date ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                                            </div>
                                        </div>
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Issued by:</div>
                                            <div class="ris-signature-line ris-value-line">{{ $ris->ris_issued_by_signature ?: ' ' }}</div>
                                            <div class="ris-date-label">Date:</div>
                                            <div class="ris-date-line ris-value-line">
                                                {{ $ris->ris_issued_by_date ? \Carbon\Carbon::parse($ris->ris_issued_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                                            </div>
                                        </div>
                                        <div class="ris-signature-column">
                                            <div class="ris-signature-label">Received by:</div>
                                            <div class="ris-signature-line ris-value-line">{{ $ris->ris_received_by_signature ?: ' ' }}</div>
                                            <div class="ris-date-label">Date:</div>
                                            <div class="ris-date-line ris-value-line">
                                                {{ $ris->ris_received_by_date ? \Carbon\Carbon::parse($ris->ris_received_by_date)->format('d/m/Y') : 'dd/mm/yyyy' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- EXISTING ATTACHMENTS --}}
                            <div class="mt-8">
                                <h4 class="font-semibold text-gray-900">Supporting Documents</h4>
                                @if($ris->risAttachments->isNotEmpty())
                                    <div class="mt-3 space-y-2">
                                        @foreach($ris->risAttachments as $attachment)
                                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                                <span class="text-sm text-gray-700">{{ $attachment->ris_attachment_original_name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-gray-500">No supporting documents attached.</p>
                                @endif

                                <div class="mt-4">
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Add Supporting Documents</label>
                                    <input
                                        type="file"
                                        name="ris_attachments[]"
                                        multiple
                                        accept=".doc,.docx,.xls,.xlsx"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">Existing attachments will remain. New files will be added.</p>
                                </div>
                            </div>
                        </div>

                        {{-- EDIT ACTION BUTTONS --}}
                        <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <button
                                type="button"
                                x-on:click="editRisModal = null"
                                class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
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
                                    onclick="this.form.querySelector('input[name=save_action]').value='submit'"
                                    class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                >
                                    Save & Submit
                                </button>
                            @endif
                            @if($ris->ris_status === 'Minor Revision')
                                <button
                                    type="submit"
                                    onclick="this.form.querySelector('input[name=save_action]').value='resubmit'"
                                    class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                >
                                    Save & Resubmit
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        @endif

    @endforeach

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
    // RIS PRINT
    function printRis(elementId) {

        const printElement = document.getElementById(elementId);

        if (!printElement) {
            return;
        }

        const printWindow = window.open(
            '',
            '_blank',
            'width=1200,height=900'
        );

        if (!printWindow) {
            alert('Please allow popups to print the RIS.');
            return;
        }

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>

                <meta charset="UTF-8">

                <title>Requisition and Issue Slip</title>

                <style>

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
                        background: white;
                        font-family: Arial, Helvetica, sans-serif;
                        color: black;
                    }

                    body {
                        padding: 0;
                    }

                    .ris-original-form {
                        width: 100%;

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

                        color: #000;

                        page-break-inside: avoid;
                        break-inside: avoid;
                    }

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
                    }

                    .ris-number-line {
                        display: block;

                        width: 42mm;

                        border-bottom:
                            0.3mm solid #1f2937;
                    }

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

                    .ris-item-column {
                        width: 40%;
                    }

                    .ris-quantity-header {
                        width: 23%;
                    }

                    .ris-requested-column {
                        width: 11%;

                        font-size: 7pt !important;
                    }

                    .ris-issued-column {
                        width: 12%;

                        font-size: 7pt !important;
                    }

                    .ris-unit-cost-column {
                        width: 17%;
                    }

                    .ris-amount-column {
                        width: 20%;
                    }

                    .ris-items-table tbody td {
                        height: 10.5mm;

                        padding:
                            1mm
                            2mm;

                        font-size: 8pt;
                    }

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
                    }

                    .ris-purpose-spacer {
                        width: 21mm;

                        flex-shrink: 0;
                    }

                    .ris-purpose-line {
                        flex: 1;

                        border-bottom:
                            0.3mm solid #1f2937;
                    }

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
                    }

                    .ris-signature-label {
                        font-size: 7.5pt;
                    }

                    .ris-signature-line {
                        height: 10mm;

                        border-bottom:
                            0.3mm solid #1f2937;
                    }

                    .ris-date-label {
                        margin-top: 3mm;

                        font-size: 7.5pt;
                    }

                    .ris-date-line {
                        height: 6mm;

                        border-bottom:
                            0.3mm solid #1f2937;
                    }

                    table {
                        width: 100%;

                        border-collapse: collapse;
                    }

                    th,
                    td {
                        border: 1px solid #000;

                        padding: 5px;

                        font-size: 10px;
                    }

                    h1 {
                        margin: 0;

                        font-size: 17px;

                        text-align: center;

                        text-transform: uppercase;
                    }

                    p {
                        font-size: 10px;
                    }

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

                    .mb-6 {
                        margin-bottom: 18px;
                    }

                    .mt-1 {
                        margin-top: 4px;
                    }

                    .mt-8 {
                        margin-top: 24px;
                    }

                    .h-9 {
                        height: 32px;
                    }

                    .h-20 {
                        height: 65px;
                    }

                    .align-bottom {
                        vertical-align: bottom;
                    }

                    .ris-value-line {
                        display: flex;
                        align-items: flex-end;
                        min-height: 6mm;
                        padding: 0 1.5mm 1mm;
                        font-size: 8pt;
                        line-height: 1.25;
                    }

                    .ris-number-line.ris-value-line,
                    .ris-signature-line.ris-value-line,
                    .ris-date-line.ris-value-line {
                        justify-content: center;
                        text-align: center;
                    }

                    #print-empty-ris-content {
                        page-break-after: avoid;
                    }

                </style>

            </head>

            <body>

                ${printElement.outerHTML}

            </body>

            </html>
        `);

        printWindow.document.close();

        printWindow.onload = function () {

            printWindow.focus();

            setTimeout(function () {

                printWindow.print();

            }, 300);

        };

    }
</script>

@endsection
