@extends('layouts.purchaser-layout')

@section('page-title', 'Authority to Purchase')
@section('page-subtitle', 'Create, manage, print, and archive Authority to Purchase records.')

@section('content')

<script type="application/json" id="atp-ris-prefill">{!! json_encode($risPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('authority_purchase_ris_id')) || !empty($selectedRisId) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewAtpId) ? 'true' : 'false' }},
        editOpen: {{ !empty($editAtpId) ? 'true' : 'false' }},
        emptyOpen: false,
        selectedAtp: {{ !empty($editAtpId) ? (int) $editAtpId : (!empty($viewAtpId) ? (int) $viewAtpId : 'null') }},
        risPrefill: JSON.parse(document.getElementById('atp-ris-prefill').textContent || '{}'),

        openView(id) {
            this.selectedAtp = id;
            this.viewOpen = true;
            this.editOpen = false;
        },

        openEdit(id) {
            this.selectedAtp = id;
            this.editOpen = true;
            this.viewOpen = false;
        },

        closeAll() {
            this.createOpen = false;
            this.viewOpen = false;
            this.editOpen = false;
            this.emptyOpen = false;
            this.selectedAtp = null;
        },

        applyRisPrefill(risId) {
            const data = this.risPrefill[String(risId)];
            const form = this.$refs.createForm;
            if (!form || !data) {
                return;
            }

            if (data.supplier_id) {
                const supplierSelect = form.querySelector('[name=authority_purchase_supplier_id]');
                if (supplierSelect) {
                    supplierSelect.value = data.supplier_id;
                }
            }

            for (let i = 0; i < 8; i++) {
                const item = (data.items && data.items[i]) ? data.items[i] : {};
                const qty = form.querySelector('[name=\'items[' + i + '][quantity]\']');
                const unit = form.querySelector('[name=\'items[' + i + '][unit]\']');
                const desc = form.querySelector('[name=\'items[' + i + '][description]\']');
                const price = form.querySelector('[name=\'items[' + i + '][unit_price]\']');
                const amount = form.querySelector('[name=\'items[' + i + '][amount_display]\']');

                if (qty) qty.value = item.quantity ?? '';
                if (unit) unit.value = item.unit ?? '';
                if (desc) desc.value = item.description ?? '';
                if (price) price.value = item.unit_price ?? '';
                if (amount) {
                    const quantity = parseFloat(item.quantity || 0);
                    const unitPrice = parseFloat(item.unit_price || 0);
                    amount.value = quantity && unitPrice ? (quantity * unitPrice).toFixed(2) : '';
                }
            }
        },

        printAtp(id) {
            document.querySelectorAll('.atp-print-sheet').forEach(function (sheet) {
                sheet.classList.remove('atp-print-active');
            });
            const sheet = document.getElementById(id === 'blank' ? 'atp-print-blank' : ('atp-print-' + id));
            if (sheet) {
                sheet.classList.add('atp-print-active');
            }
            window.print();
        }
    }"
    x-init="
        if (createOpen && '{{ $selectedRisId ?? '' }}') {
            $nextTick(() => applyRisPrefill('{{ $selectedRisId ?? '' }}'));
        }
    "
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>

    {{-- ========================================================= --}}
    {{-- FLASH MESSAGES --}}
    {{-- ========================================================= --}}

    @if(session('success'))
        <div class="pur-alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="pur-alert-error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="pur-alert-error">
            <p class="font-medium">Please check the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="pur-page-kicker">Purchasing Workflow</p>
            <h2 class="pur-page-title">
                Authority to Purchase
            </h2>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('purchaser.atp.index') }}"
                class="{{ !$archiveView ? 'pur-btn-primary' : 'pur-btn-secondary' }}"
            >
                Active
            </a>
            <a
                href="{{ route('purchaser.atp.index', ['view' => 'archive']) }}"
                class="{{ $archiveView ? 'pur-btn-primary' : 'pur-btn-secondary' }}"
            >
                Archive
            </a>

            @unless($archiveView)
                <button
                    type="button"
                    @click="emptyOpen = true"
                    class="pur-btn-secondary"
                >
                    Print Empty ATP
                </button>
                <button
                    type="button"
                    @click="createOpen = true"
                    class="pur-btn-primary"
                >
                    Create ATP
                </button>
            @endunless
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- SUMMARY CARDS (dashboard style) --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">

        <a
            href="{{ route('purchaser.atp.index') }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total ATP</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpSummary['total']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="files" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>All active Authority to Purchase</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.atp.index', ['status' => 'Draft']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Draft</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpSummary['draft']) }}
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
            href="{{ route('purchaser.atp.index', ['status' => 'Submitted']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Submitted</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpSummary['submitted']) }}
                    </p>
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

        <a
            href="{{ route('purchaser.atp.index', ['status' => 'Approved']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpSummary['approved']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Approved for purchasing workflow</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.atp.index', ['status' => 'Rejected']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpSummary['rejected']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <i data-lucide="circle-x" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Returned or declined ATP records</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.atp.index', ['view' => 'archive']) }}"
            class="pur-stat-card group"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Archived</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpSummary['archived']) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <i data-lucide="archive" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Stored away from the active list</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- SEARCH / FILTERS --}}
    {{-- ========================================================= --}}

    <form
        method="GET"
        class="pur-card grid gap-3 p-4 lg:grid-cols-5"
    >

        @if($archiveView)
            <input type="hidden" name="view" value="archive">
        @endif

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search ATP, RIS, supplier, or equipment"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2"
        >

        <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All statuses</option>
            <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
            <option value="Submitted" {{ request('status') === 'Submitted' ? 'selected' : '' }}>Submitted</option>
            <option value="Minor Revision" {{ request('status') === 'Minor Revision' ? 'selected' : '' }}>Minor Revision</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        @if(\Illuminate\Support\Facades\Schema::hasColumn('requisition_issue_slip_table', 'ris_request_type'))
        <select name="request_type" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All RIS types</option>
            <option value="New Procurement" {{ request('request_type') === 'New Procurement' ? 'selected' : '' }}>New Procurement</option>
            <option value="Replacement Procurement" {{ request('request_type') === 'Replacement Procurement' ? 'selected' : '' }}>Replacement Procurement</option>
        </select>
        @endif

        <div class="flex gap-2">

            <button type="submit" class="pur-btn-primary">
                Search
            </button>

            <a
                href="{{ $archiveView ? route('purchaser.atp.index', ['view' => 'archive']) : route('purchaser.atp.index') }}"
                class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
            >
                Reset
            </a>

        </div>

    </form>


    {{-- ========================================================= --}}
    {{-- ATP TABLE --}}
    {{-- ========================================================= --}}

    <div class="pur-card">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px] text-sm">

                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">ATP No.</th>
                        <th class="px-4 py-3">RIS</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">

                    @forelse($atps as $atp)

                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-4 font-medium text-slate-900">
                                {{ $atp->authority_purchase_form_number ?? 'ATP-' . $atp->authority_purchase_id }}
                            </td>

                            <td class="px-4 py-4 text-gray-600">
                                {{ $atp->ris_form_number ?? 'RIS-' . $atp->authority_purchase_ris_id }}
                                <br>
                                <span class="text-xs text-gray-400">
                                    {{ $atp->equipment_name ?? $atp->report_unlisted_equipment_name ?? 'No equipment' }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-gray-600">
                                @if($atp->supplier_store_type === 'Physical Store')
                                    {{ $atp->company_name ?? 'Physical supplier' }}
                                @else
                                    {{ $atp->shop_name ?? 'Online supplier' }}
                                @endif
                            </td>

                            <td class="px-4 py-4 text-gray-600">
                                @if($atp->authority_purchase_date)
                                    {{ \Carbon\Carbon::parse($atp->authority_purchase_date)->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-4">
                                @include('accounting.partials.status-badge', [
                                    'status' => \App\Support\RisWorkflow::atpStatusLabel($atp),
                                    'submitted' => $atp->authority_purchase_submitted_at,
                                    'revision' => $atp->authority_purchase_rejection_reason,
                                ])
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">

                                    <button
                                        type="button"
                                        @click="openView({{ $atp->authority_purchase_id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                    >
                                        View
                                    </button>

                                    <button
                                        type="button"
                                        @click="printAtp({{ $atp->authority_purchase_id }})"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                    >
                                        Print
                                    </button>

                                    @if(
                                        !$atp->authority_purchase_submitted_at
                                        && $atp->authority_purchase_status === 'Pending'
                                        && !$archiveView
                                    )

                                        <button
                                            type="button"
                                            @click="openEdit({{ $atp->authority_purchase_id }})"
                                            class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                        >
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('purchaser.atp.submit', $atp->authority_purchase_id) }}" onsubmit="return confirm('Submit this ATP for review?')">
                                            @csrf
                                            <button type="submit" class="pur-btn-primary !px-3 !py-2 !text-xs">
                                                Submit
                                            </button>
                                        </form>

                                    @endif

                                    @if(!$archiveView && $atp->authority_purchase_status === 'Approved')
                                        @if(!$atp->has_rfc)
                                            <a
                                                href="{{ route('purchaser.rfc.index', ['selected_atp' => $atp->authority_purchase_id]) }}"
                                                class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700"
                                            >
                                                Create RFC
                                            </a>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">
                                                RFC Created
                                            </span>
                                        @endif
                                    @endif

                                    @if($archiveView)

                                        <form method="POST" action="{{ route('purchaser.atp.restore', $atp->authority_purchase_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">
                                                Restore
                                            </button>
                                        </form>

                                    @elseif(!$atp->authority_purchase_is_archived && in_array($atp->authority_purchase_status, ['Approved', 'Rejected'], true))

                                        <form method="POST" action="{{ route('purchaser.atp.archive', $atp->authority_purchase_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700">
                                                Archive
                                            </button>
                                        </form>

                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                                No ATP records found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    <div>
        {{ $atps->links() }}
    </div>



    {{-- ========================================================= --}}
    {{-- CREATE ATP MODAL (paper layout, same direction as RIS) --}}
    {{-- ========================================================= --}}

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
                class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="atp-create-title"
            >

                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h3 id="atp-create-title" class="text-xl font-semibold text-slate-900">Create Authority to Purchase</h3>
                        <p class="mt-1 text-sm text-gray-500">Select an approved RIS to generate an Authority to Purchase.</p>
                    </div>

                    <button type="button" @click="createOpen = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        ✕
                    </button>
                </div>

                @if($eligibleRis->isEmpty())

                    <div class="p-6">
                        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
                            No approved RIS is currently available for Authority to Purchase creation.
                        </div>
                    </div>

                @else

                    <form method="POST" action="{{ route('purchaser.atp.store') }}" x-ref="createForm">

                        @csrf
                        <input type="hidden" name="save_action" value="draft">

                        <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">

                            {{-- RIS selector sits above the paper preview --}}
                            <div class="mx-auto mb-4 w-[210mm] max-w-full">

                                <label class="text-xs font-medium text-gray-500">Approved RIS</label>

                                <select
                                    name="authority_purchase_ris_id"
                                    required
                                    x-on:change="applyRisPrefill($event.target.value)"
                                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm"
                                >
                                    <option value="">Select approved RIS</option>

                                    @foreach($eligibleRis as $ris)
                                        <option
                                            value="{{ $ris->ris_id }}"
                                            {{ old('authority_purchase_ris_id', $selectedRisId ?? '') == $ris->ris_id ? 'selected' : '' }}
                                        >
                                            {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}
                                            @if($ris->equipment_name || $ris->report_unlisted_equipment_name)
                                                · {{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name }}
                                            @elseif(!empty($ris->ris_purpose_description))
                                                · {{ \Illuminate\Support\Str::limit($ris->ris_purpose_description, 40) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                            </div>

                            @include('partials.authority-to-purchase-paper', [
                                'editable' => true,
                                'atp' => null,
                                'items' => collect(),
                                'suppliers' => $suppliers,
                            ])

                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                            <button type="button" @click="createOpen = false" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">
                                Cancel
                            </button>
                            <button type="submit" class="pur-btn-primary">
                                Save Draft
                            </button>
                        </div>

                    </form>

                @endif

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- VIEW ATP MODALS (paper preview, same direction as RIS) --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $items = $atpItems->get($atp->authority_purchase_id, collect());
        @endphp

        <div
            x-show="viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        >

            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>

            <div class="relative flex min-h-full items-center justify-center p-4">

                <div
                    @click.stop
                    class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="atp-view-title-{{ $atp->authority_purchase_id }}"
                >

                    {{-- Header --}}
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">

                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 id="atp-view-title-{{ $atp->authority_purchase_id }}" class="text-xl font-semibold text-slate-900">
                                    {{ $atp->authority_purchase_form_number ?? 'ATP #' . $atp->authority_purchase_id }}
                                </h3>

                                @include('accounting.partials.status-badge', [
                                    'status' => \App\Support\RisWorkflow::atpStatusLabel($atp),
                                    'submitted' => $atp->authority_purchase_submitted_at,
                                    'revision' => $atp->authority_purchase_rejection_reason,
                                ])
                            </div>

                            <p class="mt-1 text-sm text-gray-500">
                                RIS: {{ $atp->ris_form_number ?? 'RIS-' . $atp->authority_purchase_ris_id }}
                            </p>
                            @php
                                $atpLineage = \App\Support\DocumentLineage::forAtp((int) $atp->authority_purchase_id);
                                $atpHint = \App\Support\DocumentLineage::reviewHint(
                                    \App\Support\RisWorkflow::atpStatusLabel($atp),
                                    null,
                                    'atp'
                                );
                            @endphp
                            <div class="mt-3">
                                @include('partials.document-lineage', [
                                    'lineage' => $atpLineage,
                                    'currentType' => 'ATP',
                                    'statusHint' => $atpHint,
                                ])
                            </div>
                            @if($atp->authority_purchase_rejection_reason)
                                <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                    {{ $atp->authority_purchase_status === 'Rejected' ? 'Rejection reason:' : 'Revision requested:' }}
                                    {{ $atp->authority_purchase_rejection_reason }}
                                </p>
                            @endif
                        </div>

                        <button type="button" @click="viewOpen = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            ✕
                        </button>

                    </div>

                    {{-- ATP PAPER PREVIEW --}}
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-8">
                        @include('partials.authority-to-purchase-paper', [
                            'editable' => false,
                            'atp' => $atp,
                            'items' => $items,
                            'printId' => 'atp-print-'.$atp->authority_purchase_id,
                        ])
                    </div>

                    {{-- Footer Actions --}}
                    <div class="flex justify-between border-t border-gray-200 px-6 py-4">

                        <div class="flex gap-2">
                            @if(
                                !$archiveView
                                && !$atp->authority_purchase_submitted_at
                                && $atp->authority_purchase_status === 'Pending'
                            )
                                <button
                                    type="button"
                                    @click="
                                        viewOpen = false;
                                        openEdit({{ $atp->authority_purchase_id }});
                                    "
                                    class="h-10 rounded-lg border border-gray-300 px-5 text-sm"
                                >
                                    Edit ATP
                                </button>

                                <form method="POST" action="{{ route('purchaser.atp.submit', $atp->authority_purchase_id) }}" onsubmit="return confirm('Submit this ATP for review?')">
                                    @csrf
                                    <button type="submit" class="pur-btn-primary">
                                        Submit to Review
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            @if(!$archiveView && $atp->authority_purchase_status === 'Approved')
                                @if(!$atp->has_rfc)
                                    <a
                                        href="{{ route('purchaser.rfc.index', ['selected_atp' => $atp->authority_purchase_id]) }}"
                                        class="h-10 inline-flex items-center rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700"
                                    >
                                        Create RFC
                                    </a>
                                @else
                                    <span class="inline-flex h-10 items-center rounded-lg border border-green-200 bg-green-50 px-4 text-sm font-medium text-green-700">
                                        RFC Created
                                    </span>
                                @endif
                            @endif
                            <button type="button" @click="printAtp({{ $atp->authority_purchase_id }})" class="pur-btn-primary">
                                Print
                            </button>
                            <a href="{{ route('purchaser.atp.export-xlsx', $atp->authority_purchase_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Excel
                            </a>
                            <a href="{{ route('purchaser.atp.export-docx', $atp->authority_purchase_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Word
                            </a>
                            <button type="button" @click="viewOpen = false" class="h-10 rounded-lg border border-gray-300 px-5 text-sm">
                                Close
                            </button>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endforeach

    {{-- PRINT EMPTY ATP MODAL --}}
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="atp-empty-title"
    >
        <div
            x-on:click.self="emptyOpen = false"
            class="flex min-h-full w-full justify-center"
        >
            <div class="my-auto w-full max-w-6xl rounded-xl bg-white shadow-2xl">

                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="atp-empty-title" class="text-lg font-semibold text-gray-900">
                            Print Empty ATP
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Original blank Authority to Purchase format.
                        </p>
                    </div>
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        aria-label="Close"
                    >
                        Close
                    </button>
                </div>

                <div class="overflow-x-auto bg-gray-100 p-5 md:p-8">
                    @include('partials.authority-to-purchase-paper', [
                        'editable' => false,
                        'atp' => null,
                        'items' => collect(),
                        'printId' => 'atp-print-blank',
                    ])
                </div>

                <div class="print-hidden flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button
                        type="button"
                        x-on:click="emptyOpen = false"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <a
                        href="{{ route('purchaser.atp.export-blank-xlsx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Excel
                    </a>
                    <a
                        href="{{ route('purchaser.atp.export-blank-docx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Word
                    </a>
                    <button
                        type="button"
                        @click="printAtp('blank')"
                        class="pur-btn-primary"
                    >
                        Print Empty ATP
                    </button>
                </div>

            </div>
        </div>
    </div>



    {{-- ========================================================= --}}
    {{-- EDIT ATP MODALS (editable paper layout) --}}
    {{-- ========================================================= --}}

    @foreach($atps as $atp)

        @php
            $editItems = $atpItems->get($atp->authority_purchase_id, collect());
        @endphp

        @if(!$atp->authority_purchase_submitted_at && $atp->authority_purchase_status === 'Pending')

            <div
                x-show="editOpen && selectedAtp === {{ $atp->authority_purchase_id }}"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedAtp === {{ $atp->authority_purchase_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            >

                <div class="fixed inset-0 bg-black/40" @click="editOpen = false"></div>

                <div class="relative flex min-h-full items-center justify-center p-4">

                    <div
                        @click.stop
                        class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="atp-edit-title-{{ $atp->authority_purchase_id }}"
                    >

                        <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                            <div>
                                <h3 id="atp-edit-title-{{ $atp->authority_purchase_id }}" class="text-xl font-semibold text-slate-900">Edit ATP Draft</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $atp->authority_purchase_form_number ?? 'ATP-' . $atp->authority_purchase_id }}
                                </p>
                            </div>

                            <button type="button" @click="editOpen = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                ✕
                            </button>
                        </div>

                        <form method="POST" action="{{ route('purchaser.atp.update', $atp->authority_purchase_id) }}">

                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="save">

                            <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-8">
                                @include('partials.authority-to-purchase-paper', [
                                    'editable' => true,
                                    'atp' => $atp,
                                    'items' => $editItems,
                                    'suppliers' => $suppliers,
                                ])
                            </div>

                            <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                                <button type="button" @click="editOpen = false" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    onclick="this.form.querySelector('input[name=save_action]').value='save'"
                                    class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700"
                                >
                                    Update Draft
                                </button>
                                <button
                                    type="submit"
                                    onclick="this.form.querySelector('input[name=save_action]').value='submit'"
                                    class="pur-btn-primary"
                                >
                                    Save & Submit
                                </button>
                            </div>

                        </form>

                    </div>

                </div>

            </div>

        @endif

    @endforeach

</div>


<style>
    [x-cloak] {
        display: none !important;
    }

    @media print {
        body * {
            visibility: hidden !important;
        }

        .atp-print-active,
        .atp-print-active * {
            visibility: visible !important;
        }

        .atp-print-active {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 210mm !important;
            min-height: 297mm !important;
            margin: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
        }
    }
</style>

@endsection
