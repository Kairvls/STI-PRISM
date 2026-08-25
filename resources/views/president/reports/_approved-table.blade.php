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

        $isApproved = \App\Support\RisWorkflow::isPresidentApproved($row);
        $isRejected = \App\Support\RisWorkflow::isPresidentRejected($row)
            || in_array((string) ($row->ris_status ?? ''), ['Rejected'], true);
        $rawStatus = (string) ($row->ris_status ?? '');
        $statusLower = strtolower($rawStatus);

        if ($isRejected) {
            $displayStatus = 'Rejected';
            $statusClass = 'status-rejected';
        } elseif ($isApproved) {
            $awaitingNotify = trim((string) ($row->ris_issued_by_signature ?? '')) === '';
            $displayStatus = $awaitingNotify ? 'Notify Admin' : 'Approved';
            $statusClass = $awaitingNotify ? 'status-notify' : 'status-approved';
        } elseif (
            \App\Support\RisWorkflow::isAwaitingPresident($row)
            || in_array($statusLower, ['pending', 'submitted', 'under review', 'resubmitted', 'forwarded to president'], true)
        ) {
            $displayStatus = 'Pending';
            $statusClass = 'status-pending';
        } else {
            $displayStatus = \App\Support\RisWorkflow::statusLabel($row);
            $labelLower = strtolower($displayStatus);
            if (str_contains($labelLower, 'reject') || $labelLower === 'amend') {
                $statusClass = 'status-rejected';
            } elseif (str_contains($labelLower, 'approv')) {
                $statusClass = 'status-approved';
            } else {
                $statusClass = 'status-pending';
            }
        }
    @endphp

    <tr class="border-b border-gray-100 outcome-row transition-all duration-200 hover:bg-slate-50">
        <td class="px-3 py-4 text-sm font-semibold text-gray-700">{{ $reference }}</td>
        <td class="px-3 py-4 text-sm">
            <span class="status-pill {{ $statusClass }}">{{ $displayStatus }}</span>
        </td>
        <td class="px-3 py-4 text-sm text-gray-700">
            {{ $decisionDate ? \Carbon\Carbon::parse($decisionDate)->format('F j, Y') : '—' }}
        </td>
        <td class="px-3 py-4 text-sm text-center font-semibold text-gray-800">
            ₱{{ number_format($row->total_amount ?? 0, 2) }}
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
