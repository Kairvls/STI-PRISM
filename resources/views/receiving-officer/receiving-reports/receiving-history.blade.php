@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Delivery History</h1>
        <p class="admin-page-subtitle">Accepted and returned receiving reports.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            @include('layouts.partials.receiving-filters')
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS / ATP</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">OR / PO</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Result</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null; @endphp
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->item_names ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->official_receipt ?: '—' }} / {{ $row->authority_purchase_reference_po_no ?: '—' }}</td>
                            <td class="px-5 py-4">
                                @if(in_array($row->receiving_report_status, ['Accepted', 'Completed'], true))
                                    <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Accepted</span>
                                @else
                                    <span class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Returned</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->officer_name ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="ro-preview-btn" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" @else disabled @endif title="Preview RIS">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    @if(!empty($row->receiving_report_id))
                                        <a href="/receiving/reports/{{ $row->receiving_report_id }}/print" class="text-sm font-semibold text-[#0037c7]">Print</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for Purchaser ATP to be approved and inspected. History appears after you accept or return a delivery.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
