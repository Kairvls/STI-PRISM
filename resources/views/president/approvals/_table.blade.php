@forelse ($pendingRis as $ris)
    <tr class="border-b border-gray-100 approval-row transition-all duration-200">
        <td class="px-2 py-4 text-sm font-semibold text-gray-600">RIS#{{ $ris->ris_id }}</td>
        <td class="px-2 py-4 text-sm font-medium text-gray-800">{{ $ris->ris_form_number ?? '—' }}</td>
        <td class="px-2 py-4 text-sm text-gray-700 max-w-[200px] truncate" title="{{ $ris->ris_purpose_description ?? '' }}">
            {{ $ris->ris_purpose_description ? Str::limit($ris->ris_purpose_description, 50) : '—' }}
        </td>
        <td class="px-2 py-4 text-sm text-gray-600">
            @if ($ris->ris_created_at)
                {{ \Carbon\Carbon::parse($ris->ris_created_at)->format('F j, Y') }}
            @else
                —
            @endif
        </td>
        <td class="px-2 py-4">
            @php
                $statusBadge = match ($ris->ris_status) {
                    'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'Rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                };
            @endphp
            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold border {{ $statusBadge }}">
                {{ $ris->ris_status }}
            </span>
        </td>
        <td class="px-2 py-4 text-sm text-center font-semibold text-gray-800">
            ₱{{ number_format($ris->total_amount ?? 0, 2) }}
        </td>
        <td class="px-2 py-4">
            <div class="flex items-center justify-center gap-2">
                <button
                    type="button"
                    class="action-btn inline-flex h-9 items-center justify-center rounded-lg bg-white px-3 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95"
                    title="View RIS form"
                    onclick="openRisFormModal('{{ $ris->ris_id }}')"
                >
                    <i data-lucide="eye" class="h-4 w-4"></i>
                    View
                </button>

                @if ($ris->ris_status === 'Pending')
                <button
                    type="button"
                    class="action-btn inline-flex h-9 items-center justify-center rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 border border-emerald-200 transition-all duration-200 hover:bg-emerald-100 active:scale-95"
                    onclick="openDecisionModal('ris', '{{ $ris->ris_id }}', 'Approved')"
                >
                    Approve
                </button>
                <button
                    type="button"
                    class="action-btn inline-flex h-9 items-center justify-center rounded-lg bg-rose-50 px-3 text-xs font-semibold text-rose-700 border border-rose-200 transition-all duration-200 hover:bg-rose-100 active:scale-95"
                    onclick="openDecisionModal('ris', '{{ $ris->ris_id }}', 'Rejected')"
                >
                    Reject
                </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-2 py-10 text-center fade-in">
            <p class="text-sm font-semibold text-gray-800">No RIS records found</p>
            <p class="mt-1 text-xs text-gray-500">
                @if (request('search') || request('status'))
                    No results match your search or filter criteria.
                @else
                    No forwarded RIS records available.
                @endif
            </p>
        </td>
    </tr>
@endforelse