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


    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="ATP list view">
            <a
                href="{{ route('purchaser.atp.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route('purchaser.atp.index', ['view' => 'archive']) }}"
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
                    Print Empty ATP
                </button>
                <button
                    type="button"
                    @click="createOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create ATP
                </button>
            </div>
        @endunless
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('purchaser.atp.index', ['status' => 'Draft']) }}" class="pur-stat-card group">
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

        <a href="{{ route('purchaser.atp.index', ['status' => 'Submitted']) }}" class="pur-stat-card group">
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

        <a href="{{ route('purchaser.atp.index', ['status' => 'Approved']) }}" class="pur-stat-card group">
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
                <span>Ready for purchasing workflow</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route('purchaser.atp.index', ['status' => 'Rejected']) }}" class="pur-stat-card group">
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
                <span>Returned or declined ATP</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
    </div>

    {{-- ATP RECORDS --}}
    <div class="pur-card">
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

                <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    @if($archiveView)
                        <input type="hidden" name="view" value="archive">
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
                            href="{{ $archiveView ? route('purchaser.atp.index', ['view' => 'archive']) : route('purchaser.atp.index') }}"
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
                                        <p class="font-semibold text-gray-900">
                                            {{ $atp->authority_purchase_form_number ?? 'ATP-' . $atp->authority_purchase_id }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $atp->authority_purchase_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-700">{{ $atp->ris_form_number ?? 'RIS-' . $atp->authority_purchase_ris_id }}</p>
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
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        @click="openView({{ $atp->authority_purchase_id }})"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                    >
                                        View
                                    </button>
                                    <button
                                        type="button"
                                        @click="printAtp({{ $atp->authority_purchase_id }})"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
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
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                        >
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('purchaser.atp.submit', $atp->authority_purchase_id) }}" onsubmit="return confirm('Submit this ATP for review?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800">
                                                Submit
                                            </button>
                                        </form>
                                    @endif

                                    @if(!$archiveView && $atp->authority_purchase_status === 'Approved')
                                        @if(!$atp->has_rfc)
                                            <a
                                                href="{{ route('purchaser.rfc.index', ['selected_atp' => $atp->authority_purchase_id]) }}"
                                                class="inline-flex items-center rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800"
                                            >
                                                Create RFC
                                            </a>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                                                RFC Created
                                            </span>
                                        @endif
                                    @endif

                                    @if($archiveView)
                                        <form method="POST" action="{{ route('purchaser.atp.restore', $atp->authority_purchase_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                Restore
                                            </button>
                                        </form>
                                    @elseif(!$atp->authority_purchase_is_archived && in_array($atp->authority_purchase_status, ['Approved', 'Rejected'], true))
                                        <form method="POST" action="{{ route('purchaser.atp.archive', $atp->authority_purchase_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100">
                                                Archive
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
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

                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                            <i data-lucide="file-check-2" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="atp-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Authority to Purchase</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Select an approved RIS to generate an ATP.</p>
                        </div>
                    </div>
                    <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
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

                        <button type="button" @click="viewOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
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
