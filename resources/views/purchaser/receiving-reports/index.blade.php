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

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="pur-page-kicker">Purchasing Workflow</p>
            <h2 class="pur-page-title">Receiving Reports</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @include('purchaser.partials.archive-tabs', ['archiveView' => $archiveView, 'activeRoute' => 'purchaser.rr.index', 'activeLabel' => 'Active'])
            @unless($archiveView)
                <button
                    type="button"
                    @click="emptyOpen = true"
                    class="pur-btn-secondary"
                >
                    Print Empty RR
                </button>
                <button type="button" @click="createOpen = true" class="pur-btn-primary">Create RR</button>
            @endunless
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        @foreach([
            ['Total', $summary['total'], route('purchaser.rr.index')],
            ['Draft', $summary['draft'], route('purchaser.rr.index', ['status' => 'Draft'])],
            ['In Review', $summary['submitted'], route('purchaser.rr.index', ['status' => 'Submitted'])],
            ['Completed', $summary['completed'], route('purchaser.rr.index', ['status' => 'Completed'])],
            ['Returned', $summary['returned'], route('purchaser.rr.index', ['status' => 'Returned'])],
            ['Archived', $summary['archived'], route('purchaser.rr.index', ['view' => 'archive'])],
        ] as $card)
            <a href="{{ $card[2] }}" class="pur-stat-card">
                <p class="text-sm font-medium text-gray-500">{{ $card[0] }}</p>
                <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $card[1] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="pur-card grid gap-3 p-4 lg:grid-cols-5" role="search" aria-label="Filter receiving reports">
        @if($archiveView)<input type="hidden" name="view" value="archive">@endif
        <div class="lg:col-span-2">
            <label for="rr-search" class="sr-only">Search</label>
            <input id="rr-search" type="text" name="search" value="{{ request('search') }}" placeholder="Search RR, RFC, supplier, invoice" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" aria-label="Search receiving reports">
        </div>
        <div>
            <label for="rr-status" class="sr-only">Status</label>
            <select id="rr-status" name="status" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach(['Draft','Submitted','Minor Revision','Completed','Returned'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="rr-date" class="sr-only">Date</label>
            <input id="rr-date" type="date" name="date" value="{{ request('date') }}" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm" aria-label="Filter by date">
        </div>
        <div class="flex gap-2">
            <button class="pur-btn-primary">Search</button>
            <a href="{{ $archiveView ? route('purchaser.rr.index', ['view' => 'archive']) : route('purchaser.rr.index') }}" class="inline-flex h-10 items-center rounded-lg border px-5 text-sm">Reset</a>
        </div>
    </form>

    <div class="pur-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="border-b bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">RR No.</th>
                        <th class="px-4 py-3">RFC</th>
                        <th class="px-4 py-3">Received from</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($reports as $rr)
                        @php $editable = in_array($rr->receiving_report_status, ['Draft','Minor Revision'], true) && !$archiveView; @endphp
                        <tr>
                            <td class="px-4 py-4 font-medium">{{ $rr->receiving_report_form_number }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rr->request_check_form_number ?? '—' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rr->receiving_report_received_from ?: '—' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rr->receiving_report_date ? \Carbon\Carbon::parse($rr->receiving_report_date)->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-4">
                                @include('accounting.partials.status-badge', ['status' => $rr->receiving_report_status])
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="openView({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                    <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Print</button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Edit</button>
                                        <form method="POST" action="{{ route('purchaser.rr.submit', $rr->receiving_report_id) }}" onsubmit="return confirm('Submit this Receiving Report?')">
                                            @csrf
                                            <button class="pur-btn-primary !px-3 !py-2 !text-xs">Submit</button>
                                        </form>
                                    @endif
                                    @if(!$archiveView && $rr->receiving_report_status === 'Completed')
                                        @if(!$rr->has_liq)
                                            <a href="{{ route('purchaser.liq.index', ['selected_rr' => $rr->receiving_report_id]) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">Create Liquidation</a>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">Liquidation Created</span>
                                        @endif
                                    @endif
                                    @if($archiveView)
                                        <form method="POST" action="{{ route('purchaser.rr.restore', $rr->receiving_report_id) }}">@csrf<button class="rounded-lg border px-3 py-2 text-xs">Restore</button></form>
                                    @elseif(in_array($rr->receiving_report_status, ['Completed','Returned'], true))
                                        <form method="POST" action="{{ route('purchaser.rr.archive', $rr->receiving_report_id) }}" onsubmit="return confirm('Archive this Receiving Report?')">@csrf<button class="rounded-lg bg-gray-100 px-3 py-2 text-xs">Archive</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No receiving reports found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div>{{ $reports->links() }}</div>

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
                <div class="flex justify-between border-b px-6 py-5">
                    <div>
                        <h3 id="rr-create-title" class="text-xl font-semibold">Create Receiving Report</h3>
                        <p class="text-sm text-gray-500">Select an approved Request for Check.</p>
                    </div>
                    <button type="button" @click="createOpen = false" aria-label="Close">✕</button>
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
                        <div class="flex justify-end gap-2 border-t px-6 py-4">
                            <button type="submit" class="rounded-lg border px-4 py-2 text-sm">Save Draft</button>
                            <button type="submit" onclick="this.form.save_action.value='submit'" class="pur-btn-primary">Save & Submit</button>
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
                    <div class="flex justify-between border-b px-6 py-5">
                        <div>
                            <h3 id="rr-view-title-{{ $rr->receiving_report_id }}" class="text-xl font-semibold">{{ $rr->receiving_report_form_number }}</h3>
                            <p class="text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
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
                        <button type="button" @click="viewOpen = false" aria-label="Close">✕</button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                    <div class="flex justify-end gap-2 border-t px-6 py-4">
                        @if(!$archiveView && $rr->receiving_report_status === 'Completed')
                            @if(!$rr->has_liq)
                                <a href="{{ route('purchaser.liq.index', ['selected_rr' => $rr->receiving_report_id]) }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Create Liquidation</a>
                            @else
                                <span class="inline-flex h-10 items-center rounded-lg border border-green-200 bg-green-50 px-4 text-sm font-medium text-green-700">Liquidation Created</span>
                            @endif
                        @endif
                        <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="pur-btn-primary">Print</button>
                        <a href="{{ route('purchaser.rr.export-xlsx', $rr->receiving_report_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm">Excel</a>
                        <a href="{{ route('purchaser.rr.export-docx', $rr->receiving_report_id) }}" class="h-10 inline-flex items-center rounded-lg border border-gray-300 px-5 text-sm">Word</a>
                        <button type="button" @click="viewOpen = false" class="h-10 rounded-lg border px-5 text-sm">Close</button>
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
                        <div class="flex justify-between border-b px-6 py-5">
                            <h3 id="rr-edit-title-{{ $rr->receiving_report_id }}" class="text-xl font-semibold">Edit {{ $rr->receiving_report_form_number }}</h3>
                            <button type="button" @click="editOpen = false" aria-label="Close">✕</button>
                        </div>
                        <form method="POST" action="{{ route('purchaser.rr.update', $rr->receiving_report_id) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="save_action" value="draft">
                            <input type="hidden" name="receiving_report_request_check_id" value="{{ $rr->receiving_report_request_check_id }}">
                            <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                                @include('partials.receiving-report-paper', ['editable' => true, 'rr' => $rr, 'rows' => $rrItems])
                            </div>
                            <div class="flex justify-end gap-2 border-t px-6 py-4">
                                <button type="submit" class="rounded-lg border px-4 py-2 text-sm">Update Draft</button>
                                <button type="submit" onclick="this.form.save_action.value='submit'" class="pur-btn-primary">Save & Submit</button>
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
                <div class="print-hidden flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 id="rr-empty-title" class="text-lg font-semibold text-gray-900">Print Empty RR</h3>
                        <p class="mt-1 text-sm text-gray-500">Original blank Receiving Report format.</p>
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
                    @include('partials.receiving-report-paper', ['editable' => false, 'rr' => null, 'rows' => collect(), 'printId' => 'rr-print-blank'])
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
                        href="{{ route('purchaser.rr.export-blank-xlsx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Excel
                    </a>
                    <a
                        href="{{ route('purchaser.rr.export-blank-docx') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Word
                    </a>
                    <button
                        type="button"
                        @click="printRr('blank')"
                        class="pur-btn-primary"
                    >
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
