@extends('layouts.purchaser-layout')

@section('page-title', 'Receiving Reports')
@section('page-subtitle', 'Create receiving reports from approved Request for Check.')

@section('content')

<script type="application/json" id="rr-rfc-prefill">{!! json_encode($rfcPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('receiving_report_request_check_id')) || !empty($selectedRfcId) ? 'true' : 'false' }},
        viewOpen: {{ !empty($viewRrId) ? 'true' : 'false' }},
        editOpen: false,
        emptyOpen: false,
        selectedRr: {{ !empty($viewRrId) ? (int) $viewRrId : 'null' }},
        rfcPrefill: JSON.parse(document.getElementById('rr-rfc-prefill').textContent || '{}'),

        openView(id) { this.selectedRr = id; this.viewOpen = true; this.editOpen = false; },
        openEdit(id) { this.selectedRr = id; this.editOpen = true; this.viewOpen = false; },
        closeAll() { this.createOpen = false; this.viewOpen = false; this.editOpen = false; this.emptyOpen = false; this.selectedRr = null; },

        applyRfcPrefill(rfcId) {
            const data = this.rfcPrefill[String(rfcId)];
            const form = this.$refs.createForm;
            if (!form || !data) return;
            const from = form.querySelector('[name=receiving_report_received_from]');
            const address = form.querySelector('[name=receiving_report_supplier_address_override]');
            if (from && data.received_from) from.value = data.received_from;
            if (address && data.address) address.value = data.address;
            const rows = data.items || [];
            for (let i = 0; i < 10; i++) {
                const item = rows[i] || {};
                const qty = form.querySelector('[name=\'items[' + i + '][quantity]\']');
                const unit = form.querySelector('[name=\'items[' + i + '][unit]\']');
                const article = form.querySelector('[name=\'items[' + i + '][article]\']');
                if (qty) qty.value = item.quantity ?? '';
                if (unit) unit.value = item.unit ?? '';
                if (article) article.value = item.article ?? '';
            }
        },

        printRr(id) {
            document.querySelectorAll('.rr-print-sheet').forEach(function (sheet) {
                sheet.classList.remove('rr-print-active');
            });
            const sheet = document.getElementById('rr-print-' + id);
            if (sheet) sheet.classList.add('rr-print-active');
            window.print();
        }
    }"
    x-init="
        if (createOpen && '{{ $selectedRfcId ?? '' }}') {
            $nextTick(() => applyRfcPrefill('{{ $selectedRfcId ?? '' }}'));
        }
    "
    @keydown.escape.window="closeAll()"
    class="space-y-6"
>
    @if(session('success'))
        <div class="pur-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pur-alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="pur-alert-error">
            <p class="mb-1 font-medium">Please fix the following:</p>
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="pur-tabs !mb-0" aria-label="RR list view">
            <a
                href="{{ route('purchaser.rr.index') }}"
                class="pur-tab {{ !$archiveView ? 'is-active' : '' }}"
            >
                <i data-lucide="file-stack" class="h-3.5 w-3.5"></i>
                Active
            </a>
            <a
                href="{{ route('purchaser.rr.index', ['view' => 'archive']) }}"
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
                    Print Empty RR
                </button>
                <button
                    type="button"
                    @click="createOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Create RR
                </button>
            </div>
        @endunless
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('purchaser.rr.index', ['status' => 'Draft']) }}" class="pur-stat-card group">
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

        <a href="{{ route('purchaser.rr.index', ['status' => 'Submitted']) }}" class="pur-stat-card group">
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

        <a href="{{ route('purchaser.rr.index', ['status' => 'Completed']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Completed</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['completed']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Approved and ready for liquidation</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a href="{{ route('purchaser.rr.index', ['status' => 'Returned']) }}" class="pur-stat-card group">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Returned</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($summary['returned']) }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <i data-lucide="undo-2" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Sent back for revision</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>
    </div>

    @php
        $rrHasFilters = request()->filled('search')
            || request()->filled('status')
            || request()->filled('date');
        $rrClearUrl = $archiveView
            ? route('purchaser.rr.index', ['view' => 'archive'])
            : route('purchaser.rr.index');
    @endphp

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
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

                <form
                    method="GET"
                    action="{{ route('purchaser.rr.index') }}"
                    role="search"
                    aria-label="Filter receiving reports"
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
                            id="rr-search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search RR, RFC, supplier, invoice"
                            aria-label="Search receiving reports"
                            class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    <select id="rr-status" name="status" aria-label="Filter by status" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">
                        <option value="">All statuses</option>
                        @foreach(['Draft','Submitted','Minor Revision','Completed','Returned'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status === 'Submitted' ? 'In Review' : $status }}</option>
                        @endforeach
                    </select>

                    <input id="rr-date" type="date" name="date" value="{{ request('date') }}" aria-label="Filter by date" class="box-border h-9 rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm leading-none text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white">

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

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RR No.</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RFC</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Received from</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($reports as $rr)
                        @php $editable = in_array($rr->receiving_report_status, ['Draft','Minor Revision'], true) && !$archiveView; @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                        <i data-lucide="package-check" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $rr->receiving_report_form_number }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">Record #{{ $rr->receiving_report_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ $rr->request_check_form_number ?? '—' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $rr->receiving_report_received_from ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $rr->receiving_report_date ? \Carbon\Carbon::parse($rr->receiving_report_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-5 py-4">
                                @include('accounting.partials.status-badge', ['status' => $rr->receiving_report_status])
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" @click="openView({{ $rr->receiving_report_id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">View</button>
                                    <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Print</button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rr->receiving_report_id }})" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Edit</button>
                                        <form method="POST" action="{{ route('purchaser.rr.submit', $rr->receiving_report_id) }}" onsubmit="return confirm('Submit this Receiving Report?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800">Submit</button>
                                        </form>
                                    @endif
                                    @if(!$archiveView && $rr->receiving_report_status === 'Completed')
                                        @if(!$rr->has_liq)
                                            <a href="{{ route('purchaser.liq.index', ['selected_rr' => $rr->receiving_report_id]) }}" class="inline-flex items-center rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800">Create Liquidation</a>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">Liquidation Created</span>
                                        @endif
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route('purchaser.rr.restore', $rr->receiving_report_id) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Restore</button>
                                        </form>
                                    @elseif(in_array($rr->receiving_report_status, ['Completed','Returned'], true))
                                        <form method="POST" action="{{ route('purchaser.rr.archive', $rr->receiving_report_id) }}" onsubmit="return confirm('Archive this Receiving Report?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-100">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
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
                aria-labelledby="rr-create-title"
            >
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                            <i data-lucide="package-check" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 id="rr-create-title" class="text-lg font-semibold tracking-tight text-slate-900">Create Receiving Report</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Select an approved Request for Check.</p>
                        </div>
                    </div>
                    <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                @if($eligibleRfcs->isEmpty())
                    <div class="p-6 text-sm text-gray-600">No approved Request for Check is available.</div>
                @else
                    <form method="POST" action="{{ route('purchaser.rr.store') }}" x-ref="createForm">
                        @csrf
                        <input type="hidden" name="save_action" value="draft">
                        <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                            <div class="mx-auto mb-4 w-[210mm] max-w-full">
                                <label class="text-xs font-medium text-gray-500">Approved Request for Check</label>
                                <select name="receiving_report_request_check_id" x-on:change="applyRfcPrefill($event.target.value)" class="mt-1 h-10 w-full rounded-lg border px-3 text-sm">
                                    <option value="">Select RFC</option>
                                    @foreach($eligibleRfcs as $rfc)
                                        <option value="{{ $rfc->request_check_id }}" {{ old('receiving_report_request_check_id', $selectedRfcId ?? '') == $rfc->request_check_id ? 'selected' : '' }}>
                                            {{ $rfc->request_check_form_number }} @if($rfc->authority_purchase_form_number)· {{ $rfc->authority_purchase_form_number }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @include('partials.receiving-report-paper', ['editable' => true, 'rr' => null, 'rows' => collect()])
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

    @foreach($reports as $rr)
        @php
            $rrItems = $items->get($rr->receiving_report_id, collect())->values();
            $canEdit = in_array($rr->receiving_report_status, ['Draft','Minor Revision'], true) && !$archiveView;
        @endphp
        <div
            x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            x-effect="window.purDialog && window.purDialog.sync(viewOpen && selectedRr === {{ $rr->receiving_report_id }}, $el)"
            @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        >
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl" role="dialog" aria-modal="true" aria-labelledby="rr-view-title-{{ $rr->receiving_report_id }}">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <i data-lucide="package-check" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 id="rr-view-title-{{ $rr->receiving_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">{{ $rr->receiving_report_form_number }}</h3>
                                    <p class="mt-0.5 text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
                                </div>
                            </div>
                            @php
                                $rrLineage = \App\Support\DocumentLineage::forRr((int) $rr->receiving_report_id);
                                $rrHint = \App\Support\DocumentLineage::reviewHint($rr->receiving_report_status ?? null, null, 'rr');
                            @endphp
                            <div class="mt-3">
                                @include('partials.document-lineage', [
                                    'lineage' => $rrLineage,
                                    'currentType' => 'RR',
                                    'statusHint' => $rrHint,
                                ])
                            </div>
                            @if($rr->receiving_report_revision_notes)<p class="mt-2 text-sm text-amber-700">Revision: {{ $rr->receiving_report_revision_notes }}</p>@endif
                            @if($rr->receiving_report_return_reason)<p class="mt-2 text-sm text-red-700">Returned: {{ $rr->receiving_report_return_reason }}</p>@endif
                        </div>
                        <button type="button" @click="viewOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 bg-gray-50 px-6 py-4">
                        @if(!$archiveView && $rr->receiving_report_status === 'Completed')
                            @if(!$rr->has_liq)
                                <a href="{{ route('purchaser.liq.index', ['selected_rr' => $rr->receiving_report_id]) }}" class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-5 text-sm font-semibold text-white transition hover:bg-blue-800">Create Liquidation</a>
                            @else
                                <span class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700">Liquidation Created</span>
                            @endif
                        @endif
                        <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="inline-flex h-10 items-center rounded-lg bg-[#0025cc] px-5 text-sm font-semibold text-white transition hover:bg-blue-800">Print</button>
                        <a href="{{ route('purchaser.rr.export-xlsx', $rr->receiving_report_id) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Excel</a>
                        <a href="{{ route('purchaser.rr.export-docx', $rr->receiving_report_id) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Word</a>
                        <button type="button" @click="viewOpen = false" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div
                x-show="editOpen && selectedRr === {{ $rr->receiving_report_id }}"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto"
                x-effect="window.purDialog && window.purDialog.sync(editOpen && selectedRr === {{ $rr->receiving_report_id }}, $el)"
                @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
            >
                <div class="fixed inset-0 bg-black/40" @click="editOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div
                        @click.stop
                        class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="rr-edit-title-{{ $rr->receiving_report_id }}"
                    >
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 md:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white">
                                    <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                                </div>
                                <h3 id="rr-edit-title-{{ $rr->receiving_report_id }}" class="text-lg font-semibold tracking-tight text-slate-900">Edit {{ $rr->receiving_report_form_number }}</h3>
                            </div>
                            <button type="button" @click="editOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('purchaser.rr.update', $rr->receiving_report_id) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="receiving_report_request_check_id" value="{{ $rr->receiving_report_request_check_id }}">
                            <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                                @include('partials.receiving-report-paper', ['editable' => true, 'rr' => $rr, 'rows' => $rrItems])
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

    {{-- PRINT EMPTY RR MODAL --}}
    <div
        x-cloak
        x-show="emptyOpen"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 md:p-8"
        x-effect="window.purDialog && window.purDialog.sync(emptyOpen, $el)"
        @keydown.tab="window.purDialog && window.purDialog.trap($event, $el)"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rr-empty-title"
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
                            <h3 id="rr-empty-title" class="text-lg font-semibold text-gray-950">Print Empty RR</h3>
                            <p class="mt-0.5 text-sm text-gray-500">Original blank Receiving Report format.</p>
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
                    @include('partials.receiving-report-paper', ['editable' => false, 'rr' => null, 'rows' => collect(), 'printId' => 'rr-print-blank'])
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
                        href="{{ route('purchaser.rr.export-blank-xlsx') }}"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Excel
                    </a>
                    <a
                        href="{{ route('purchaser.rr.export-blank-docx') }}"
                        class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Word
                    </a>
                    <button
                        type="button"
                        @click="printRr('blank')"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print Empty RR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        body * { visibility: hidden !important; }
        .rr-print-active, .rr-print-active * { visibility: visible !important; }
        .rr-print-active {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 210mm !important;
            min-height: 297mm !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>
@endsection
