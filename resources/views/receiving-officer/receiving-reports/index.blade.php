@extends('layouts.receiving-layout')

@section('content')

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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-amber-600">{{ $pending->count() }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ready to inspect</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">{{ $readyCount }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">For correction</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-rose-600">{{ $returnedCount }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="mb-3">
                <h2 class="text-sm font-semibold text-gray-900">Deliveries awaiting inspection</h2>
                <p class="mt-1 text-xs text-gray-500">Approved purchaser ATP records assigned to you for inspection.</p>
            </div>
            @include('layouts.partials.receiving-filters')
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS / ATP</th>
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
                        @php $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null; @endphp
                        <tr class="{{ $selected && (int) $selected->authority_purchase_id === (int) $row->authority_purchase_id ? 'bg-slate-50' : '' }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id) }}</td>
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
                                    <button type="button" class="ro-preview-btn" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" @else disabled @endif title="Preview RIS">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    <a href="/receiving/reports?atp={{ $row->authority_purchase_id }}" class="text-sm font-semibold text-[#0037c7]">Inspect</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for Purchaser ATP to be approved. Nothing is ready to inspect yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($selected)
        <form method="POST" action="/receiving/reports/{{ $selected->authority_purchase_id }}/accept" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">1. Purchaser document</p>
                    <h2 class="mt-1 text-sm font-semibold text-gray-900">ATP / RIS from Purchaser</h2>
                    <p class="mt-1 text-xs text-gray-500">Compare this list with what actually arrived.</p>
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
                    <button type="button" class="mt-4 w-full rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" @else disabled @endif>
                        Preview RIS
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

@endsection
