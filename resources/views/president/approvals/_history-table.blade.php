@forelse ($approvalHistoryRecords as $row)
    @php
        $reference = $row->ris_form_number ?? ('RIS #' . $row->ris_id);
        $decision = $row->decision ?? $row->ris_status ?? 'Approved';
        $decisionLower = is_string($decision) ? strtolower($decision) : '';
        $decidedAt = $row->decided_at ?? $row->ris_approved_by_date ?? null;
        $risId = $row->ris_id ?? null;
    @endphp

    <tr class="border-b border-gray-100 history-row transition-all duration-200">
        <td class="px-2 py-4 text-sm font-semibold text-gray-600">RIS#{{ $risId }}</td>
        <td class="px-2 py-4 text-sm text-gray-700">{{ $reference }}</td>
        <td class="px-2 py-4 text-sm">
            @if ($decision === 'Approved' || $decisionLower === 'approved')
                <span class="status-pill status-approved">Approved</span>
            @elseif ($decision === 'Rejected' || $decisionLower === 'rejected')
                <span class="status-pill status-rejected">Rejected</span>
            @else
                <span class="status-pill status-pending">{{ $decision }}</span>
            @endif
        </td>
        <td class="px-2 py-4 text-sm text-gray-700">
            {{ $decidedAt
                ? \Carbon\Carbon::parse($decidedAt)->format('F j, Y')
                : '—'
            }}
        </td>
        <td class="px-2 py-4 text-sm text-center font-semibold text-gray-800">
            ₱{{ number_format($row->total_amount ?? 0, 2) }}
        </td>
        <td class="px-2 py-4 text-center">
            @if ($risId)
                <div class="row-actions justify-center">
                    <button type="button" class="icon-btn" data-tip="View RIS" aria-label="View RIS" onclick="openRisViewModal({{ $risId }})">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                    </button>
                    <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $risId }})">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                    </button>
                </div>
            @else
                <span class="text-xs text-gray-400">—</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-2 py-12 text-center fade-in">
            <p class="text-sm font-semibold text-gray-800">No approval records found.</p>
            <p class="mt-1 text-xs text-gray-500">
                @if (request('search'))
                    No results match your search criteria.
                @else
                    When decisions are made, the table will automatically populate here.
                @endif
            </p>
        </td>
    </tr>
@endforelse