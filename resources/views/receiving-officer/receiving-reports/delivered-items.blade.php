@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Delivered Items</h1>
        <p class="admin-page-subtitle">Accepted deliveries that were added to inventory.</p>
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
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS / ATP</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Received</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Preview</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php $previewRisId = $row->ris_id ?? $row->authority_purchase_ris_id ?? null; @endphp
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->item_names ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ (int) ($row->total_qty ?? 0) }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="ro-preview-btn" @if($previewRisId) onclick="openReceivingRisPreview('{{ $previewRisId }}')" @else disabled @endif title="Preview RIS">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    @if(!empty($row->receiving_report_id))
                                        <a href="/receiving/reports/{{ $row->receiving_report_id }}/print" class="text-sm font-semibold text-[#0037c7]" title="Print receiving record">Print</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for an accepted delivery. Inspect an approved ATP first, then accept it into inventory.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
