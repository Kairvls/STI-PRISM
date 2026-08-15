@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Pending Receiving Reports</h1>
        <p class="admin-page-subtitle">Select a delivery, preview the RIS, complete physical validation, then accept into inventory or return with remarks.</p>
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
                <p class="mt-1 text-xs text-gray-500">Approved ATP records. Select a row, then accept or return below.</p>
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
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                <h2 class="text-sm font-semibold text-gray-900">Physical validation — {{ $selected->ris_form_number ?: $selected->authority_purchase_form_number }}</h2>
                <p class="mt-1 text-xs text-gray-500">Check every item against the purchase before accepting.</p>
                <div class="mt-4 space-y-2 text-sm text-gray-700">
                    @forelse($items as $item)
                        <div class="rounded-lg border border-gray-100 px-3 py-2">
                            <span class="font-medium">{{ $item->atp_description }}</span>
                            <span class="text-gray-400"> · {{ $item->atp_quantity }} {{ $item->atp_unit }} · ₱{{ number_format((float) $item->atp_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No line items on this ATP.</p>
                    @endforelse
                </div>
                <form method="POST" action="/receiving/reports/{{ $selected->authority_purchase_id }}/accept" class="mt-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($checklist as $item)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700">
                                <input type="checkbox" name="checklist[]" value="{{ $item }}" class="rounded border-gray-300 text-[#0037c7] focus:ring-[#0037c7]">
                                {{ $item }}
                            </label>
                        @endforeach
                    </div>
                    <label class="mt-4 block text-sm font-medium text-gray-700">Official receipt number</label>
                    <input type="text" name="official_receipt" required minlength="3" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="OR number" value="{{ old('official_receipt') }}">
                    <button type="submit" class="admin-btn-primary mt-4 w-full justify-center" onclick="return confirm('Accept this delivery and add the items to inventory? This cannot be undone from this screen.')">Yes — Accept and update inventory</button>
                </form>
            </div>

            <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-5">
                <h2 class="text-sm font-semibold text-gray-900">Items incorrect or incomplete?</h2>
                <p class="mt-1 text-xs text-gray-500">Return this delivery with remarks. Inventory will not be updated.</p>
                @if(!empty($selected->receiving_report_remarks))
                    <p class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700">Previous remarks: {{ $selected->receiving_report_remarks }}</p>
                @endif
                <form method="POST" action="/receiving/reports/{{ $selected->authority_purchase_id }}/return" class="mt-5">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">Receiving remarks</label>
                    <textarea name="remarks" rows="6" required class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="Describe missing items, damage, or mismatches..."></textarea>
                    <button type="submit" class="mt-3 w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100" onclick="return confirm('Return this delivery without updating inventory?')">
                        No — Return for correction
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

@endsection
