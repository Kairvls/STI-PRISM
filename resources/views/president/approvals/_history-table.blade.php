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
                <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">Approved</span>
            @elseif ($decision === 'Rejected' || $decisionLower === 'rejected')
                <span class="inline-flex items-center rounded-lg bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200">Rejected</span>
            @else
                <span class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 border border-gray-200">{{ $decision }}</span>
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
                <button type="button" class="action-btn inline-flex h-8 items-center justify-center rounded-lg bg-white px-2.5 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View RIS form" onclick="openRisViewModal({{ $risId }})">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </button>
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