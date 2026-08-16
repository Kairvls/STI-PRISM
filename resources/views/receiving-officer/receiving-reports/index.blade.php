@extends('layouts.receiving-layout')

@section('content')

<<<<<<< Updated upstream
<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Pending Receiving Reports</h1>
        <p class="admin-page-subtitle">Receive the purchaser ATP, compare it with the actual receipt and the delivered equipment, then accept only if they match.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    @php
        $filter = $filter ?? 'pending';
        $receivingCards = [
            ['filter' => 'pending', 'label' => 'Pending', 'count' => $pendingCount ?? 0, 'color' => 'text-amber-600', 'title' => 'Show reports waiting for inspection'],
            ['filter' => 'returned', 'label' => 'For correction', 'count' => $returnedCount ?? 0, 'color' => 'text-rose-600', 'title' => 'Show returned reports'],
            ['filter' => 'all', 'label' => 'All', 'count' => $allCount ?? $pending->count(), 'color' => 'text-slate-900', 'title' => 'Show all pending and returned reports'],
        ];
        $sliderOptions = [
            ['filter' => 'pending', 'label' => 'Pending', 'title' => 'Show reports waiting for inspection'],
            ['filter' => 'returned', 'label' => 'For correction', 'title' => 'Show returned reports'],
            ['filter' => 'all', 'label' => 'All', 'title' => 'Show all pending and returned reports'],
        ];
    @endphp

    <div data-ro-table data-ro-default-filter="pending" class="space-y-6">
    @include('layouts.partials.receiving-stat-cards', ['cards' => $receivingCards, 'current' => $filter])

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Receiving Reports</h2>
                        <p class="mt-1 text-xs text-gray-500">Inspect, accept, or return. Filter matches the chips above.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'reports'])
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $pendingCount ?? $pending->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    @include('layouts.partials.receiving-filter-slider', [
                        'sliderId' => 'receivingReportFilterSlider',
                        'current' => $filter,
                        'ariaLabel' => 'Receiving report filters',
                        'options' => $sliderOptions,
                    ])
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingReportSearch', 'placeholder' => 'Search RR, RIS, ATP, supplier...'])
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Receiving Report</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">PO / Value</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pending as $row)
                        @php
                            $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null;
                            $rowStatus = ($row->receiving_report_status ?? null) === 'Returned' ? 'returned' : 'pending';
                            $rowSearch = trim(implode(' ', [
                                $row->receiving_report_form_number ?? '',
                                $row->ris_form_number ?? '',
                                $row->authority_purchase_form_number ?? '',
                                $row->item_names ?? '',
                                $row->supplier_name ?? '',
                                $row->authority_purchase_reference_po_no ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}" class="{{ $selected && (int) $selected->authority_purchase_id === (int) $row->authority_purchase_id ? 'bg-slate-50' : '' }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                {{ $row->receiving_report_form_number ?: ($row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id)) }}
                                <p class="mt-1 text-xs font-normal text-gray-500">{{ $row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id }}{{ $row->ris_form_number ? ' · '.$row->ris_form_number : '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->item_names ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ (int) ($row->total_qty ?? 0) }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->authority_purchase_reference_po_no ?: '—' }} · ₱{{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
                            <td class="px-5 py-4">
                                @if(($row->receiving_report_status ?? null) === 'Returned')
                                    <span class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Returned</span>
                                @else
                                    <span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    @include('layouts.partials.receiving-ris-eye', ['risId' => $previewRisId])
                                    <a href="/receiving/reports?atp={{ $row->authority_purchase_id }}" class="text-sm font-semibold text-[#0037c7]">Inspect</a>
                                    @if(!empty($row->receiving_report_id))
                                        <a href="/receiving/reports/{{ $row->receiving_report_id }}/print" class="text-sm font-semibold text-gray-600">Print RR</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                        <tr class="receiving-empty-row" @if($pending->count()) style="display:none" @endif>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">No pending receiving reports. They open here when a purchaser ATP is approved.</td>
                        </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
    </div>

    @if($selected)
        <form method="POST" action="/receiving/reports/{{ $selected->authority_purchase_id }}/accept" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">1. Receiving report</p>
                    <h2 class="mt-1 text-sm font-semibold text-gray-900">{{ $selected->receiving_report_form_number ?: 'Pending receiving report' }}</h2>
                    <p class="mt-1 text-xs text-gray-500">Opened from the approved purchaser ATP. Compare this list with the actual receipt and delivered goods.</p>
                    <dl class="mt-4 space-y-2 text-sm text-gray-700">
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">ATP</dt><dd class="font-medium">{{ $selected->authority_purchase_form_number ?: 'ATP-'.$selected->authority_purchase_id }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">RIS</dt><dd class="font-medium">{{ $selected->ris_form_number ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Supplier</dt><dd class="font-medium text-right">{{ $selected->supplier_name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">PO / Ref</dt><dd class="font-medium">{{ $selected->authority_purchase_reference_po_no ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">ATP total</dt><dd class="font-medium">₱{{ number_format((float) ($selected->total_amount ?? 0), 2) }}</dd></div>
                    </dl>
                    <div class="mt-4 space-y-2">
                        @forelse($items as $item)
                            <div class="rounded-lg border border-gray-100 px-3 py-2 text-sm">
                                <p class="font-medium text-gray-900">{{ $item->atp_description }}</p>
                                <p class="text-xs text-gray-500">Ordered: {{ $item->atp_quantity }} {{ $item->atp_unit }} · ₱{{ number_format((float) $item->atp_amount, 2) }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No line items on this ATP.</p>
                        @endforelse
                    </div>
                    @php $previewRisId = $selected->ris_id ?? $selected->authority_purchase_ris_id ?? null; @endphp
                    <button type="button" class="mt-4 w-full rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" title="Preview RIS" @else disabled title="No RIS is attached to this ATP. Preview is available only when an RIS exists." @endif>
                        {{ $previewRisId ? 'Preview RIS' : 'Preview RIS (none attached)' }}
                    </button>
                </div>

                <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">2. Actual receipt</p>
                    <h2 class="mt-1 text-sm font-semibold text-gray-900">Official receipt</h2>
                    <p class="mt-1 text-xs text-gray-500">Enter the OR from the physical receipt you are holding.</p>
                    <label class="mt-4 block text-sm font-medium text-gray-700">Official receipt number</label>
                    <input type="text" name="official_receipt" required minlength="3" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="OR number" value="{{ old('official_receipt', $selected->official_receipt ?? '') }}">
                    <p class="mt-4 text-xs text-gray-500">The OR must belong to this delivery. If it does not match the purchaser ATP, return the delivery.</p>
                </div>

                <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">3. Delivered equipment</p>
                    <h2 class="mt-1 text-sm font-semibold text-gray-900">Physical count</h2>
                    <p class="mt-1 text-xs text-gray-500">Count each delivered item. Quantity must match the purchaser ATP to accept.</p>
                    <div class="mt-4 space-y-3">
                        @forelse($items as $item)
                            <label class="block rounded-lg border border-gray-100 px-3 py-2 text-sm">
                                <span class="font-medium text-gray-900">{{ $item->atp_description }}</span>
                                <span class="mt-2 flex items-center gap-2">
                                    <input
                                        type="number"
                                        name="received_qty[{{ $item->atp_item_id }}]"
                                        min="0"
                                        required
                                        class="w-24 rounded-lg border border-gray-200 px-2 py-1.5 text-sm"
                                        value="{{ old('received_qty.'.$item->atp_item_id, $item->atp_quantity) }}"
                                    >
                                    <span class="text-xs text-gray-500">{{ $item->atp_unit }} received (ATP: {{ $item->atp_quantity }})</span>
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-gray-400">No equipment listed on this ATP.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                    <h2 class="text-sm font-semibold text-gray-900">Physical validation checklist</h2>
                    <p class="mt-1 text-xs text-gray-500">Check every point against the purchaser ATP, the actual receipt, and the goods.</p>
                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($checklist as $item)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="checklist[]" value="{{ $item }}" class="rounded border-gray-300 text-[#0037c7] focus:ring-[#0037c7]" {{ is_array(old('checklist')) && in_array($item, old('checklist'), true) ? 'checked' : '' }}>
                                {{ $item }}
                            </label>
                        @endforeach
                    </div>
                    <label class="mt-4 flex items-start gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-3 text-sm text-emerald-800">
                        <input type="checkbox" name="goods_match" value="1" required class="mt-0.5 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-600">
                        <span>Goods, purchaser ATP, and actual receipt match. Accept this delivery so Purchaser can proceed to liquidation.</span>
                    </label>
                    <button type="submit" class="admin-btn-primary mt-4 w-full justify-center" onclick="return confirm('Accept this delivery and add the items to inventory? This cannot be undone from this screen.')">Yes — Accept and update inventory</button>
                </div>
            </div>
        </form>

        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
            <h2 class="text-sm font-semibold text-gray-900">Items incorrect or incomplete?</h2>
            <p class="mt-1 text-xs text-gray-500">If the goods, receipt, or ATP do not match, return this delivery. Inventory will not be updated.</p>
            @if(!empty($selected->receiving_report_remarks))
                <p class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700">Previous remarks: {{ $selected->receiving_report_remarks }}</p>
            @endif
            <form method="POST" action="/receiving/reports/{{ $selected->authority_purchase_id }}/return" class="mt-5">
                @csrf
                <label class="block text-sm font-medium text-gray-700">Receiving remarks</label>
                <textarea name="remarks" rows="4" required class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="Describe missing items, damage, or mismatches..."></textarea>
                <button type="submit" class="mt-3 w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100" onclick="return confirm('Return this delivery without updating inventory?')">
                    No — Return for correction
                </button>
            </form>
        </div>
    @endif
</div>

=======
<div
    x-data="{
        viewOpen: false,
        selectedRr: null,
        reviseOpen: false,
        returnOpen: false,
        remarksRr: null,
        openView(id) { this.selectedRr = id; this.viewOpen = true; },
        openRevise(id) { this.remarksRr = id; this.reviseOpen = true; this.returnOpen = false; },
        openReturn(id) { this.remarksRr = id; this.returnOpen = true; this.reviseOpen = false; },
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
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Receiving Reports</h2>
        <p class="text-sm text-slate-600">Confirm Second Count when delivered items match the report.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('receiving.rr.index', ['status' => 'queue']) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'queue' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">For Second Count</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['queue'] }}</p>
        </a>
        <a href="{{ route('receiving.rr.index', ['status' => 'completed']) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'completed' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Completed</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['completed'] }}</p>
        </a>
        <a href="{{ route('receiving.rr.index', ['status' => 'returned']) }}" class="rounded-xl border bg-white p-5 {{ $filter === 'returned' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Returned</p>
            <p class="mt-3 text-3xl font-semibold">{{ $counts['returned'] }}</p>
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white">
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
                    @php $reviewable = in_array($rr->receiving_report_status, ['Submitted','Resubmitted','Under Review'], true); @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $rr->receiving_report_form_number }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rr->request_check_form_number ?? '—' }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $rr->receiving_report_received_from ?: '—' }}</td>
                        <td class="px-4 py-4">{{ $rr->receiving_report_status }}</td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openView({{ $rr->receiving_report_id }}); fetch('{{ route('receiving.rr.start-review', $rr->receiving_report_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})" class="rounded-lg border px-3 py-2 text-xs">View</button>
                                <button type="button" @click="printRr({{ $rr->receiving_report_id }})" class="rounded-lg border px-3 py-2 text-xs">Print</button>
                                @if($reviewable)
                                    <form method="POST" action="{{ route('receiving.rr.second-count', $rr->receiving_report_id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Confirm Second Count? Items arrived correctly?')" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">Second Count</button>
                                    </form>
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
    <div>{{ $reports->links() }}</div>

    @foreach($reports as $rr)
        @php $rrItems = $items->get($rr->receiving_report_id, collect())->values(); @endphp
        <div x-show="viewOpen && selectedRr === {{ $rr->receiving_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold">{{ $rr->receiving_report_form_number }}</h3>
                            <p class="text-sm text-gray-500">RFC: {{ $rr->request_check_form_number ?? '—' }}</p>
                        </div>
                        <button type="button" @click="viewOpen = false">✕</button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">
                        @include('partials.receiving-report-paper', ['editable' => false, 'rr' => $rr, 'rows' => $rrItems, 'printId' => 'rr-print-'.$rr->receiving_report_id])
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/receiving/reports/' + remarksRr + '/revise'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Request revision</h3>
            <textarea name="remarks" required rows="4" class="mt-4 w-full rounded-lg border p-3 text-sm"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="reviseOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white">Send back</button>
            </div>
        </form>
    </div>

    <div x-show="returnOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/receiving/reports/' + remarksRr + '/return'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            @csrf
            <h3 class="text-lg font-semibold">Return delivery</h3>
            <p class="mt-1 text-sm text-gray-500">Items did not arrive correctly.</p>
            <textarea name="remarks" required rows="4" class="mt-4 w-full rounded-lg border p-3 text-sm"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="returnOpen = false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Return</button>
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
>>>>>>> Stashed changes
@endsection
