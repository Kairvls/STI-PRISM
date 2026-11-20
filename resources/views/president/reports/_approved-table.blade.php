@php
    $records = $approvedOutcomeRecords ?? $outcomeRecords ?? collect();
    $type = $type ?? 'approved';
@endphp
@forelse ($records as $row)
    @php
        $reference = \App\Support\RisWorkflow::formNumber($row);
        $decisionDate = $row->decided_at ?? $row->ris_created_at ?? null;
        $remarks = $row->remarks ?? null;
        $risId = $row->ris_id ?? null;
        $displayStatus = \App\Support\RisWorkflow::statusLabel($row);
    @endphp

    <tr class="border-b border-gray-100 outcome-row transition-all duration-200 hover:bg-slate-50">
        <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $reference }}</td>
        <td class="px-3 py-4 text-sm">
            @include('accounting.partials.status-badge', ['status' => $displayStatus])
        </td>
        <td class="px-3 py-4 text-sm text-gray-700">
            {{ $decisionDate ? \Carbon\Carbon::parse($decisionDate)->format('F j, Y') : '—' }}
        </td>
        <td class="px-3 py-4 text-center">
            @if ($remarks)
                <button type="button" class="icon-btn" data-tip="View remarks" aria-label="View remarks" onclick="openRemarksModal('{{ addslashes($remarks) }}')">
                    <i data-lucide="message-square-text" class="h-4 w-4"></i>
                </button>
            @else
                <span class="text-xs text-gray-400">—</span>
            @endif
        </td>
        <td class="px-3 py-4 text-center">
            <div class="row-actions justify-center">
                <button type="button" class="icon-btn" data-tip="View RIS" aria-label="View RIS" onclick="openRisViewModal({{ $risId }})">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </button>
                <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $risId }})">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-2 py-12 text-center">
            <p class="text-sm font-semibold text-gray-800">{{ $type === 'rejected' ? 'No rejected outcomes found.' : ($type === 'pending' ? 'No pending outcomes found.' : ($type === 'all' ? 'No RIS records found.' : 'No approved outcomes found.')) }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $type === 'rejected' ? 'Rejected RIS records will appear here.' : ($type === 'pending' ? 'Pending RIS records will appear here.' : ($type === 'all' ? 'RIS records will appear here.' : 'Approved RIS records will appear here.')) }}</p>
        </td>
    </tr>
@endforelse
