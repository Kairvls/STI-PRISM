@forelse ($pendingRis as $ris)
    @php
        $presidentSig = trim((string) ($ris->ris_approved_by_signature ?? ''));
        $rawStatus = (string) ($ris->ris_status ?? '');
        $awaitingPresident = $presidentSig === ''
            && (
                $rawStatus === 'Forwarded to President'
                || $rawStatus === 'Approved'
                || ($rawStatus === 'Pending' && !empty($ris->ris_approved_by_date))
            );
        $statusLabel = $awaitingPresident ? 'Pending' : 'Pending';
        $submitted = $ris->ris_created_at
            ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y')
            : '—';
    @endphp
    <article class="queue-item" data-ris-id="{{ $ris->ris_id }}" onclick="openRisReviewModal({{ $ris->ris_id }})">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h3 class="truncate text-sm font-semibold text-gray-900">{{ $ris->ris_form_number ?? 'RIS #' . $ris->ris_id }}</h3>
                <span class="status-pill status-pending">{{ $statusLabel }}</span>
            </div>
            <p class="mt-0.5 truncate text-sm text-gray-700">{{ $ris->ris_requested_by_signature ?: '—' }}</p>
            <p class="mt-0.5 line-clamp-1 text-xs text-gray-500">{{ $ris->ris_purpose_description ?: '—' }}</p>
            <div class="mt-1.5 flex items-center gap-3 text-xs text-gray-500">
                <span class="font-semibold text-gray-800">₱{{ number_format($ris->total_amount ?? 0, 2) }}</span>
                <span>{{ $submitted }}</span>
            </div>
        </div>
        <button type="button" class="review-btn" onclick="event.stopPropagation(); openRisReviewModal({{ $ris->ris_id }})">Review</button>
    </article>
@empty
    <div class="empty-queue">
        <p class="text-sm font-semibold text-gray-800">Nothing waiting for review</p>
        <p class="mt-1 text-xs text-gray-500">Forwarded RIS documents will appear here.</p>
    </div>
@endforelse
