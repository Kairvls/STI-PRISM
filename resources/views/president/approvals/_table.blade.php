@php
    $awaitingNotifyRis = $awaitingNotifyRis ?? collect();
    $hasRows = $awaitingNotifyRis->isNotEmpty() || $pendingRis->count() > 0;
@endphp

@if ($hasRows)
    <div class="pm-queue-table-wrap">
        <table class="pm-queue-table">
            <thead>
                <tr>
                    <th class="col-ref">RIS</th>
                    <th class="col-req">Requester</th>
                    <th class="col-purpose">Purpose</th>
                    <th class="col-amount">Amount</th>
                    <th class="col-date">Date</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="queueTableBody">
                @foreach ($awaitingNotifyRis as $ris)
                    @php
                        $submitted = $ris->ris_approved_by_date
                            ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('M d, Y')
                            : ($ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : '—');
                        $sortDate = $ris->ris_approved_by_date ?: $ris->ris_created_at;
                        $hasAdminForward = trim((string) ($ris->ris_forward_details ?? '')) !== ''
                            || trim((string) ($ris->ris_forward_attachment_path ?? '')) !== '';
                    @endphp
                    <tr
                        class="queue-row queue-row-approved"
                        data-ris-id="{{ $ris->ris_id }}"
                        data-queue-kind="awaiting_notify"
                        data-sort-date="{{ $sortDate }}"
                    >
                        <td class="col-ref">
                            <span class="ref-text">{{ $ris->ris_form_number ?? 'RIS #' . $ris->ris_id }}</span>
                        </td>
                        <td class="col-req">
                            <span class="truncate-cell">{{ $ris->ris_requested_by_signature ?: '—' }}</span>
                        </td>
                        <td class="col-purpose">
                            <span class="truncate-cell">{{ $ris->ris_purpose_description ?: '—' }}</span>
                        </td>
                        <td class="col-amount">₱{{ number_format($ris->total_amount ?? 0, 2) }}</td>
                        <td class="col-date">{{ $submitted }}</td>
                        <td class="col-status">
                            <span class="status-pill status-notify">Approved</span>
                        </td>
                        <td class="col-actions">
                            <div class="row-actions">
                                <button type="button" class="icon-btn pin-btn" data-tip="Pin to top" aria-label="Pin to top" onclick="event.stopPropagation(); toggleRisPin({{ $ris->ris_id }})">
                                    <i data-lucide="pin" class="h-4 w-4"></i>
                                </button>
                                @if ($hasAdminForward)
                                    <button type="button" class="icon-btn" data-tip="Admin supporting details" aria-label="Admin supporting details" onclick="event.stopPropagation(); openAdminForwardDetails({{ $ris->ris_id }})">
                                        <i data-lucide="message-square" class="h-4 w-4"></i>
                                    </button>
                                @endif
                                <button type="button" class="icon-btn" data-tip="Review approved RIS" aria-label="Review approved RIS" onclick="event.stopPropagation(); openApprovedRisPreviewModal({{ $ris->ris_id }})">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="event.stopPropagation(); printRisDocument({{ $ris->ris_id }})">
                                    <i data-lucide="printer" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @foreach ($pendingRis as $ris)
                    @php
                        $submitted = $ris->ris_created_at
                            ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y')
                            : '—';
                        $hasAdminForward = trim((string) ($ris->ris_forward_details ?? '')) !== ''
                            || trim((string) ($ris->ris_forward_attachment_path ?? '')) !== '';
                    @endphp
                    <tr
                        class="queue-row queue-row-pending"
                        data-ris-id="{{ $ris->ris_id }}"
                        data-queue-kind="pending"
                        data-sort-date="{{ $ris->ris_created_at }}"
                    >
                        <td class="col-ref">
                            <span class="ref-text">{{ $ris->ris_form_number ?? 'RIS #' . $ris->ris_id }}</span>
                        </td>
                        <td class="col-req">
                            <span class="truncate-cell">{{ $ris->ris_requested_by_signature ?: '—' }}</span>
                        </td>
                        <td class="col-purpose">
                            <span class="truncate-cell">{{ $ris->ris_purpose_description ?: '—' }}</span>
                        </td>
                        <td class="col-amount">₱{{ number_format($ris->total_amount ?? 0, 2) }}</td>
                        <td class="col-date">{{ $submitted }}</td>
                        <td class="col-status">
                            <span class="status-pill status-pending">Pending</span>
                        </td>
                        <td class="col-actions">
                            <div class="row-actions">
                                @if ($hasAdminForward)
                                    <button type="button" class="icon-btn" data-tip="Admin supporting details" aria-label="Admin supporting details" onclick="event.stopPropagation(); openAdminForwardDetails({{ $ris->ris_id }})">
                                        <i data-lucide="message-square" class="h-4 w-4"></i>
                                    </button>
                                @endif
                                <button type="button" class="icon-btn" data-tip="Review RIS" aria-label="Review RIS" onclick="event.stopPropagation(); openRisReviewModal({{ $ris->ris_id }})">
                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </button>
                                <button type="button" class="icon-btn" data-tip="Print RIS" aria-label="Print RIS" onclick="event.stopPropagation(); printRisDocument({{ $ris->ris_id }})">
                                    <i data-lucide="printer" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="empty-queue">
        <p class="text-sm font-semibold text-gray-800">Nothing waiting for review</p>
        <p class="mt-1 text-xs text-gray-500">Forwarded RIS documents will appear here.</p>
    </div>
@endif
