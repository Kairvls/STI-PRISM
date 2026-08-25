@forelse ($recentRis as $ris)
    @php
        $isApproved = !empty($ris->is_president_approved);
        $isRejected = in_array((string) ($ris->ris_status ?? ''), ['Rejected', 'Rejected by President', 'Rejected by the President'], true);
        $displayStatus = $isApproved
            ? (!empty($ris->awaiting_notify) ? 'Notify Admin' : 'Approved')
            : ($isRejected ? 'Rejected' : 'Pending');
        $statusClass = $isApproved
            ? (!empty($ris->awaiting_notify) ? 'status-notify' : 'status-approved')
            : ($isRejected ? 'status-rejected' : 'status-pending');
    @endphp
    <div class="recent-row">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-gray-900">{{ $ris->ris_form_number ?? 'RIS #' . $ris->ris_id }}</p>
            <p class="truncate text-xs text-gray-500">{{ Str::limit($ris->ris_purpose_description ?? '—', 42) }}</p>
        </div>
        <span class="status-pill {{ $statusClass }}">{{ $displayStatus }}</span>
        <div class="row-actions">
            @if ($isApproved)
                <button type="button" class="icon-btn" data-tip="Open approved RIS" aria-label="Open approved RIS" onclick="openApprovedRisPreviewModal({{ $ris->ris_id }})">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </button>
            @endif
            <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="printRisDocument({{ $ris->ris_id }})">
                <i data-lucide="printer" class="h-4 w-4"></i>
            </button>
        </div>
    </div>
@empty
    <p class="empty-note">No recent decisions</p>
@endforelse
