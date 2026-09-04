@forelse ($records as $row)
    @php
        $reference = $row->ris_form_number ?? ('RIS #' . $row->ris_id);
        $risId = $row->ris_id;
        $adminName = $row->approved_by_name ?: 'Administrator';
        $reason = trim((string) ($row->ris_direct_approval_reason ?? ''));
        $decidedAt = $row->ris_direct_approval_at ?? $row->ris_approved_by_date ?? null;
        $dateLabel = $decidedAt
            ? \Carbon\Carbon::parse($decidedAt)->format('F j, Y')
            : '—';
        $hasProof = filled($row->ris_direct_approval_proof_path ?? null);
        $proofUrl = $hasProof
            ? route('president.direct-approvals.proof', $risId)
            : null;
        $proofName = $row->ris_direct_approval_proof_name ?: 'Download proof';
        $supportingDocs = $row->supportingDocuments ?? [];
        $detailPayload = [
            'risId' => (int) $risId,
            'reference' => $reference,
            'adminName' => $adminName,
            'dateLabel' => $dateLabel,
            'reason' => $reason !== '' ? $reason : 'No reason recorded for this direct approval.',
            'proofUrl' => $proofUrl,
            'proofName' => $proofName,
            'supportingDocuments' => $supportingDocs,
        ];
    @endphp

    <tr class="border-b border-gray-100 transition-all duration-200">
        <td class="px-2 py-4 text-sm font-semibold text-gray-600">RIS#{{ $risId }}</td>
        <td class="px-2 py-4 text-sm text-gray-700">
            <div>{{ $reference }}</div>
            @if (count($supportingDocs) > 0)
                <div class="mt-1.5 space-y-0.5">
                    @foreach ($supportingDocs as $file)
                        <a
                            href="{{ $file['url'] }}"
                            target="_blank"
                            rel="noopener"
                            class="block max-w-[180px] truncate text-xs text-sky-700 hover:underline"
                            title="{{ $file['name'] }}"
                        >
                            {{ $file['name'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </td>
        <td class="px-2 py-4 text-sm text-gray-700">{{ $adminName }}</td>
        <td class="px-2 py-4 text-sm text-gray-700 max-w-[220px]">
            <span class="line-clamp-2" title="{{ $reason !== '' ? $reason : 'No reason recorded' }}">
                {{ $reason !== '' ? $reason : '—' }}
            </span>
        </td>
        <td class="px-2 py-4 text-sm text-gray-700">{{ $dateLabel }}</td>
        <td class="px-2 py-4 text-sm text-center font-semibold text-gray-800">
            ₱{{ number_format($row->total_amount ?? 0, 2) }}
        </td>
        <td class="px-2 py-4 text-center">
            <div class="row-actions justify-center">
                <button
                    type="button"
                    class="icon-btn"
                    data-tip="View details"
                    aria-label="View details"
                    onclick='openDirectApprovalDetail(@json($detailPayload))'
                >
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                </button>
                <button
                    type="button"
                    class="icon-btn"
                    data-tip="View RIS"
                    aria-label="View RIS"
                    onclick="openRisViewModal({{ $risId }})"
                >
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </button>
                @if ($hasProof)
                    <a
                        href="{{ $proofUrl }}"
                        class="icon-btn"
                        data-tip="Download proof"
                        aria-label="Download proof"
                    >
                        <i data-lucide="paperclip" class="h-4 w-4"></i>
                    </a>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-2 py-12 text-center fade-in">
            <p class="text-sm font-semibold text-gray-800">No admin direct approvals found.</p>
            <p class="mt-1 text-xs text-gray-500">
                @if (request('search'))
                    No results match your search criteria.
                @else
                    When Admin uses Approve Directly, those RIS records will appear here for your review.
                @endif
            </p>
        </td>
    </tr>
@endforelse
