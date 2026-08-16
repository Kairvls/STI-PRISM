@extends('layouts.purchaser-layout')

@section('page-title', 'Receiving Reports')
@section('page-subtitle', 'Create receiving reports from approved Request for Check.')

@section('content')

<script type="application/json" id="rr-rfc-prefill">{!! json_encode($rfcPrefill ?? []) !!}</script>

<div
    x-data="{
        createOpen: {{ ($errors->any() && old('receiving_report_request_check_id')) || !empty($selectedRfcId) ? 'true' : 'false' }},
        viewOpen: false,
        editOpen: false,
        selectedRr: null,
        rfcPrefill: JSON.parse(document.getElementById('rr-rfc-prefill').textContent || '{}'),

        openView(id) { this.selectedRr = id; this.viewOpen = true; this.editOpen = false; },
        openEdit(id) { this.selectedRr = id; this.editOpen = true; this.viewOpen = false; },
        closeAll() { this.createOpen = false; this.viewOpen = false; this.editOpen = false; this.selectedRr = null; },

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
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Receiving Reports</h2>
            <p class="text-sm text-slate-600">Linked to approved Request for Check. Receiving Officer confirms Second Count.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('purchaser.rr.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700">Active</a>
            <a href="{{ route('purchaser.rr.index', ['view' => 'archive']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700">Archive</a>
            @unless($archiveView)
                <button type="button" @click="createOpen = true" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Create</button>
                <button type="button" @click="printRr('blank')" class="h-10 rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700">Print blank</button>
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
            <a href="{{ $card[2] }}" class="rounded-xl border border-gray-200 bg-white p-5 hover:shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $card[0] }}</p>
                <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $card[1] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 lg:grid-cols-5">
        @if($archiveView)<input type="hidden" name="view" value="archive">@endif
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search RR, RFC, supplier, invoice" class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2">
        <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All statuses</option>
            @foreach(['Draft','Submitted','Minor Revision','Completed','Returned'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ request('date') }}" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
        <div class="flex gap-2">
            <button class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Search</button>
            <a href="{{ $archiveView ? route('purchaser.rr.index', ['view' => 'archive']) : route('purchaser.rr.index') }}" class="inline-flex h-10 items-center rounded-lg border px-5 text-sm">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
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
                                @if($rr->receiving_report_status === 'Completed')
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Completed</span>
                                @elseif($rr->receiving_report_status === 'Returned')
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Returned</span>
                                @elseif($rr->receiving_report_status === 'Draft')
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">Draft</span>
                                @elseif($rr->receiving_report_status === 'Minor Revision')
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Minor Revision</span>
                                @else
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $rr->receiving_report_status }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="openView({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                    <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Print</button>
                                    @if($editable)
                                        <button type="button" @click="openEdit({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Edit</button>
                                        <form method="POST" action="{{ route('purchaser.rr.submit', $rr->receiving_report_id) }}" onsubmit="return confirm('Submit this Receiving Report?')">
                                            @csrf
                                            <button class="rounded-lg bg-gray-900 px-3 py-2 text-xs text-white">Submit</button>
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

    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/40" @click="createOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                <div class="flex justify-between border-b px-6 py-5">
                    <div>
                        <h3 class="text-xl font-semibold">Create Receiving Report</h3>
                        <p class="text-sm text-gray-500">Select an approved Request for Check.</p>
                    </div>
                    <button type="button" @click="createOpen = false">✕</button>
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
                            <button type="submit" onclick="this.form.save_action.value='submit'" class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white">Save & Submit</button>
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
        <div x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $rr->receiving_report_form_number }}</h3>
                            <p class="text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
                            @if($rr->receiving_report_revision_notes)<p class="mt-2 text-sm text-amber-700">Revision: {{ $rr->receiving_report_revision_notes }}</p>@endif
                            @if($rr->receiving_report_return_reason)<p class="mt-2 text-sm text-red-700">Returned: {{ $rr->receiving_report_return_reason }}</p>@endif
                        </div>
                        <button type="button" @click="viewOpen = false">✕</button>
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
                        <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="h-10 rounded-lg bg-gray-900 px-5 text-sm text-white">Print</button>
                        <button type="button" @click="viewOpen = false" class="h-10 rounded-lg border px-5 text-sm">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div x-show="editOpen && selectedRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="editOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                        <div class="flex justify-between border-b px-6 py-5">
                            <h3 class="text-xl font-semibold">Edit {{ $rr->receiving_report_form_number }}</h3>
                            <button type="button" @click="editOpen = false">✕</button>
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
                                <button type="submit" onclick="this.form.save_action.value='submit'" class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white">Save & Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <div class="hidden">
        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => null, 'rows' => collect(), 'printId' => 'rr-print-blank'])
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
