@extends('layouts.receiving-layout')

@section('content')
<div
    x-data="{
        viewOpen: false,
        selectedRr: null,
        reviseOpen: false,
        returnOpen: false,
        remarksRr: null,
        signOpen: false,
        signRr: null,
        openView(id) { this.selectedRr = id; this.viewOpen = true; },
        openRevise(id) {
            this.remarksRr = id;
            this.reviseOpen = true;
            this.returnOpen = false;
            this.signOpen = false;
            this.$nextTick(() => {
                const el = document.getElementById('reviseRemarks');
                if (el) { el.value = ''; el.focus(); }
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            });
        },
        openReturn(id) {
            this.remarksRr = id;
            this.returnOpen = true;
            this.reviseOpen = false;
            this.signOpen = false;
            this.$nextTick(() => {
                const el = document.getElementById('returnRemarks');
                if (el) { el.value = ''; el.focus(); }
                if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
            });
        },
        openSign(id) {
            this.signRr = id;
            this.signOpen = true;
            this.viewOpen = false;
            this.reviseOpen = false;
            this.returnOpen = false;
            this.$nextTick(() => {
                if (typeof window.initSecondCountSign === 'function') {
                    window.initSecondCountSign(id);
                }
            });
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
    class="space-y-6 p-6"
>
    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Receiving Reports</h2>
        <p class="text-sm text-slate-600">Confirm Second Count when delivered items match the report.</p>
        @if(!empty($dateFilter))
            <p class="mt-2 inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-800">
                Showing reports for {{ \Carbon\Carbon::parse($dateFilter)->format('M d, Y') }}
                <a href="{{ route('receiving.rr.index', ['status' => $filter]) }}" class="underline">Clear date</a>
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('receiving.rr.index', array_filter(['status' => 'queue', 'date' => $dateFilter ?? null])) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'queue' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">For Second Count</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['queue'] }}</p>
        </a>
        <a href="{{ route('receiving.rr.index', array_filter(['status' => 'completed', 'date' => $dateFilter ?? null])) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'completed' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Delivered</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['completed'] }}</p>
        </a>
        <a href="{{ route('receiving.rr.index', array_filter(['status' => 'returned', 'date' => $dateFilter ?? null])) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'returned' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Returned</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['returned'] }}</p>
        </a>
    </div>

    <form method="GET" action="{{ route('receiving.rr.index') }}" class="overflow-hidden rounded-xl border bg-white">
        <input type="hidden" name="status" value="{{ $filter }}">
        @if(!empty($dateFilter))
            <input type="hidden" name="date" value="{{ $dateFilter }}">
        @endif
        <div class="border-b border-gray-100 px-4 py-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Receiving reports</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Search and review the current queue.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @include('admin.partials.view-mode-switcher', [
                        'switcherId' => 'roRrViewSwitcher',
                        'btnClass' => 'ro-rr-view-btn',
                    ])
                    <div class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                        {{ $reports->total() }} total
                    </div>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search RR, RFC, or received from"
                    class="h-10 w-full max-w-md rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                >
                <button type="submit" class="h-10 rounded-lg bg-slate-900 px-4 text-sm font-medium text-white">Search</button>
                <a href="{{ route('receiving.rr.index', ['status' => $filter]) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm text-gray-700">Reset</a>
            </div>
        </div>

        <div id="roRrTable" class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="border-b bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">RR No.</th>
                        <th class="px-4 py-3">RFC</th>
                        <th class="px-4 py-3">Received from</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($reports as $rr)
                        @php
                            $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true);
                            $displayStatus = in_array($rr->receiving_report_status, ['Accepted', 'Completed'], true) ? 'Delivered' : $rr->receiving_report_status;
                        @endphp
                        <tr>
                            <td class="px-4 py-4 font-medium">{{ $rr->receiving_report_form_number }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rr->request_check_form_number ?? '—' }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $rr->receiving_report_received_from ?? '—' }}</td>
                            <td class="px-4 py-4">@include('accounting.partials.status-badge', ['status' => $displayStatus])</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="openView({{ $rr->receiving_report_id }}); fetch('{{ route('receiving.rr.start-review', $rr->receiving_report_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                    <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                                    @if($reviewable)
                                        <button type="button" @click="openSign({{ $rr->receiving_report_id }})" class="rounded-lg bg-sky-50 px-3 py-2 text-xs font-medium text-sky-700">Second Count</button>
                                        <button type="button" @click="openRevise({{ $rr->receiving_report_id }})" class="rounded-lg border border-amber-300 px-3 py-2 text-xs text-amber-700">Revise</button>
                                        <button type="button" @click="openReturn({{ $rr->receiving_report_id }})" class="rounded-lg border border-red-300 px-3 py-2 text-xs text-red-700">Return</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">No receiving reports in this queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="roRrCards" class="hidden space-y-3 px-4 py-4">
            @forelse($reports as $rr)
                @php
                    $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true);
                    $displayStatus = in_array($rr->receiving_report_status, ['Accepted', 'Completed'], true) ? 'Delivered' : $rr->receiving_report_status;
                    $actionsHtml = '<button type="button" @click="openView('.$rr->receiving_report_id.'); fetch(\''.route('receiving.rr.start-review', $rr->receiving_report_id).'\', {method:\'POST\', headers:{\'X-CSRF-TOKEN\':\''.csrf_token().'\',\'Accept\':\'application/json\'}})" class="rounded-lg border px-3 py-1.5 text-xs">View</button>'
                        .'<button type="button" @click="printRr('.$rr->receiving_report_id.')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>';
                    if ($reviewable) {
                        $actionsHtml .= '<button type="button" @click="openSign('.$rr->receiving_report_id.')" class="rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">Second Count</button>'
                            .'<button type="button" @click="openRevise('.$rr->receiving_report_id.')" class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs text-amber-700">Revise</button>'
                            .'<button type="button" @click="openReturn('.$rr->receiving_report_id.')" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-700">Return</button>';
                    }
                @endphp
                @include('receiving-officer.partials.list-info-card', [
                    'title' => $rr->receiving_report_form_number,
                    'subtitle' => 'RFC: '.($rr->request_check_form_number ?? '—'),
                    'status' => $displayStatus,
                    'statusClass' => 'border-slate-200 bg-slate-50 text-slate-700',
                    'fields' => [
                        ['label' => 'From', 'value' => $rr->receiving_report_received_from ?? '—'],
                        ['label' => 'Status', 'value' => $displayStatus],
                    ],
                    'actionsHtml' => $actionsHtml,
                ])
            @empty
                <div class="px-2 py-10 text-center text-sm text-gray-400">No receiving reports in this queue.</div>
            @endforelse
        </div>
    </form>
    <div>{{ $reports->links() }}</div>

    @foreach($reports as $rr)
        @php $rrItems = $items->get($rr->receiving_report_id, collect())->values(); @endphp
        <div x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                    <div class="flex items-center justify-between gap-3 border-b px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $rr->receiving_report_form_number }}</h3>
                            <p class="text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white hover:bg-slate-800" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></button>
                            <button type="button" @click="viewOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" title="Close" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @foreach($reports as $rr)
        @php
            $rrItems = $items->get($rr->receiving_report_id, collect())->values();
            $reviewable = in_array($rr->receiving_report_status, ['Pending','Submitted','Resubmitted','Under Review'], true);
        @endphp
        @if($reviewable)
            <div x-show="signOpen && signRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-black/40" @click="signOpen = false"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <form
                        method="POST"
                        action="{{ route('receiving.rr.second-count', $rr->receiving_report_id) }}"
                        enctype="multipart/form-data"
                        @click.stop
                        class="relative flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
                        data-second-count-form="{{ $rr->receiving_report_id }}"
                    >
                        @csrf
                        <input type="hidden" name="signature_image" id="scSigImage-{{ $rr->receiving_report_id }}" value="">

                        <div class="flex items-center justify-between gap-3 border-b px-6 py-5">
                            <div>
                                <h3 class="text-xl font-semibold">Confirm Second Count</h3>
                                <p class="text-sm text-gray-500">{{ $rr->receiving_report_form_number }} · Sign Second Count on the document below</p>
                            </div>
                            <button type="button" @click="signOpen = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-600" title="Close" aria-label="Close">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900">Second Count signature</h4>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Your name is filled in on <strong>Second Count</strong>. Optionally upload a handwritten signature image.
                                        </p>
                                        <label class="mt-3 block text-xs text-slate-600">
                                            Handwritten signature image (optional)
                                            <input
                                                type="file"
                                                id="scSigUpload-{{ $rr->receiving_report_id }}"
                                                name="signature_file"
                                                accept="image/*"
                                                class="mt-1 block w-full text-xs"
                                            >
                                        </label>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Product verification photos</label>
                                        <input
                                            type="file"
                                            name="verification_photos[]"
                                            accept="image/*"
                                            multiple
                                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700"
                                        >
                                        <p class="mt-1 text-xs text-gray-500">Optional. Upload photos of the verified products.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="max-h-[65vh] overflow-y-auto bg-gray-100 p-6">
                                @include('partials.receiving-report-paper', [
                                    'editable' => false,
                                    'signSecondCount' => true,
                                    'rr' => $rr,
                                    'rows' => $rrItems,
                                    'officerName' => auth()->user()->user_full_name ?? 'Receiving Officer',
                                    'signSuffix' => (string) $rr->receiving_report_id,
                                    'printId' => null,
                                    'printClass' => '',
                                ])
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-white px-6 py-4">
                            <button type="button" @click="signOpen = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">Confirm Second Count</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    <div
        x-show="reviseOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @keydown.escape.window="reviseOpen = false"
    >
        <div class="absolute inset-0" @click="reviseOpen = false"></div>
        <form
            method="POST"
            :action="'/receiving/reports/' + remarksRr + '/revise'"
            @click.stop
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-700">
                        <i data-lucide="file-pen-line" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-slate-900">Request revision</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Send this Receiving Report back to the Purchaser with clear notes on what must be corrected.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="reviseOpen = false"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100"
                        title="Close"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="reviseRemarks" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Revision remarks <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="reviseRemarks"
                        name="remarks"
                        required
                        rows="5"
                        maxlength="5000"
                        placeholder="Describe what needs to be fixed before this report can be counted again."
                        class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    ></textarea>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs leading-relaxed text-slate-600">
                        The Purchaser will see these remarks, update the report, and resubmit it for second count. Inventory is not updated until second count is confirmed.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-6 py-4">
                <button
                    type="button"
                    @click="reviseOpen = false"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    Send back to Purchaser
                </button>
            </div>
        </form>
    </div>

    <div
        x-show="returnOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        @keydown.escape.window="returnOpen = false"
    >
        <div class="absolute inset-0" @click="returnOpen = false"></div>
        <form
            method="POST"
            :action="'/receiving/reports/' + remarksRr + '/return'"
            @click.stop
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700">
                        <i data-lucide="undo-2" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-semibold text-slate-900">Return delivery</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Mark this delivery as returned when items did not arrive correctly.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="returnOpen = false"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:bg-slate-100"
                        title="Close"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="returnRemarks" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Return reason <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="returnRemarks"
                        name="remarks"
                        required
                        rows="5"
                        maxlength="5000"
                        placeholder="Explain why the delivery is being returned to the Purchaser."
                        class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                    ></textarea>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50/70 px-4 py-3">
                    <p class="text-xs leading-relaxed text-rose-800">
                        Items will not be accepted into inventory. The Purchaser is notified with your reason.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-6 py-4">
                <button
                    type="button"
                    @click="returnOpen = false"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-800"
                >
                    Return to Purchaser
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @media print {
        body * { visibility: hidden !important; }
        .rr-print-active, .rr-print-active * { visibility: visible !important; }
        .rr-print-active { position: absolute !important; left: 0 !important; top: 0 !important; width: 210mm !important; box-shadow: none !important; }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>

@include('admin.partials.view-mode-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.bindAdminViewMode === 'function') {
        window.bindAdminViewMode({
            tableId: 'roRrTable',
            cardsId: 'roRrCards',
            buttonSelector: '.ro-rr-view-btn',
            storageKey: 'ro_rr_view',
        });
    }
    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
});

(function () {
    function readFileAsDataUrl(file) {
        return new Promise(function (resolve, reject) {
            if (!file) return resolve(null);
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function syncPreview(id) {
        var nameInput = document.getElementById('scName-' + id);
        var hidden = document.getElementById('scSigImage-' + id);
        var preview = document.getElementById('scSigPreview-' + id);
        if (!preview) return;

        var name = nameInput ? String(nameInput.value || '').trim() : '';
        var dataUrl = hidden ? String(hidden.value || '').trim() : '';
        if (dataUrl.indexOf('data:image/') === 0) {
            var html = '<img src="' + dataUrl + '" alt="Second Count signature" style="max-height:48px;width:auto;">';
            if (name !== '') {
                html += '<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">'
                    + name.replace(/</g, '&lt;') + '</span>';
                html += '<span style="font-size:10px;color:#4b5563;">Receiving Officer</span>';
            }
            preview.innerHTML = html;
            preview.style.display = 'flex';
            if (nameInput) nameInput.style.display = 'none';
            return;
        }

        preview.innerHTML = '';
        preview.style.display = 'none';
        if (nameInput) nameInput.style.display = '';
    }

    window.initSecondCountSign = function (id) {
        var nameInput = document.getElementById('scName-' + id);
        var fileInput = document.getElementById('scSigUpload-' + id);
        var hidden = document.getElementById('scSigImage-' + id);
        var form = document.querySelector('[data-second-count-form="' + id + '"]');

        if (hidden) hidden.value = '';
        if (fileInput) fileInput.value = '';
        syncPreview(id);

        if (nameInput && !nameInput.dataset.scBound) {
            nameInput.dataset.scBound = '1';
            nameInput.addEventListener('input', function () { syncPreview(id); });
        }

        if (fileInput && !fileInput.dataset.scBound) {
            fileInput.dataset.scBound = '1';
            fileInput.addEventListener('change', function () {
                readFileAsDataUrl(fileInput.files && fileInput.files[0]).then(function (url) {
                    if (hidden) hidden.value = url || '';
                    syncPreview(id);
                }).catch(function () {});
            });
        }

        if (form && !form.dataset.scBound) {
            form.dataset.scBound = '1';
            form.addEventListener('submit', function (event) {
                if (!fileInput || !fileInput.files || !fileInput.files[0] || !hidden) return;
                if (String(hidden.value || '').indexOf('data:image/') === 0) return;
                event.preventDefault();
                readFileAsDataUrl(fileInput.files[0]).then(function (url) {
                    if (hidden) hidden.value = url || '';
                    form.submit();
                }).catch(function () {
                    form.submit();
                });
            });
        }
    };
})();
</script>
@endsection
