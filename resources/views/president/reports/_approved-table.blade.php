@php
    $records = $approvedOutcomeRecords ?? $outcomeRecords ?? collect();
    $type = $type ?? 'approved';
@endphp
@forelse ($records as $row)
    @php
        $reference = $row->ris_form_number ?? ('RIS #' . $row->ris_id);
        $decisionDate = $row->decided_at ?? $row->ris_created_at ?? null;
        $remarks = $row->remarks ?? null;
        $risId = $row->ris_id ?? null;
        $statusLower = strtolower($row->ris_status ?? '');
    @endphp

    <tr class="border-b border-gray-100 outcome-row transition-all duration-200 hover:bg-yellow-50/40">
        <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $reference }}</td>
        <td class="px-3 py-4 text-sm text-gray-700">
            {{ $decisionDate ? \Carbon\Carbon::parse($decisionDate)->format('F j, Y') : '—' }}
        </td>
        <td class="px-3 py-4 text-sm text-center font-semibold text-gray-800">
            ₱{{ number_format($row->total_amount ?? 0, 2) }}
        </td>
        <td class="px-3 py-4 text-center">
            @if ($remarks)
                <button type="button" class="action-btn inline-flex h-8 items-center justify-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View remarks" onclick="openRemarksModal('{{ addslashes($remarks) }}')">
                    <i data-lucide="message-square-text" class="h-4 w-4"></i>
                    <span class="ml-1.5">Remarks</span>
                </button>
            @else
                <span class="text-xs text-gray-400">—</span>
            @endif
        </td>
        <td class="px-3 py-4 text-center">
            <button type="button" class="action-btn inline-flex h-8 items-center justify-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View RIS form" onclick="openRisViewModal({{ $risId }})">
                <i data-lucide="eye" class="h-4 w-4"></i>
                <span class="ml-1.5">View</span>
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-2 py-12 text-center">
            <p class="text-sm font-semibold text-gray-800">{{ $type === 'rejected' ? 'No rejected outcomes found.' : ($type === 'pending' ? 'No pending outcomes found.' : ($type === 'all' ? 'No RIS records found.' : 'No approved outcomes found.')) }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $type === 'rejected' ? 'Rejected RIS records will appear here.' : ($type === 'pending' ? 'Pending RIS records will appear here.' : ($type === 'all' ? 'RIS records will appear here.' : 'Approved RIS records will appear here.')) }}</p>
        </td>
    </tr>
@endforelse
