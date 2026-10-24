@php
    $awaitingNotifyRis = $awaitingNotifyRis ?? collect();
    $hasRows = $awaitingNotifyRis->isNotEmpty() || $pendingRis->count() > 0;
    $attentionFocus = $attentionFocus ?? null;
    $attentionFocusTotal = $attentionFocus['total'] ?? (($attentionFocus['key'] ?? null) === \App\Support\PresidentAttentionSummary::FOCUS_AWAITING_NOTIFY
        ? $awaitingNotifyRis->count()
        : $pendingRis->total());
@endphp

@include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $attentionFocusTotal])

@if ($hasRows)
    <div class="pm-queue-table-wrap">
        <table class="pm-queue-table">
            <thead>
                <tr>
                    <th class="col-ref">RIS Number</th>
                    <th class="col-reqno">Request No.</th>
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
                            <span class="ref-text">{{ \App\Support\RisWorkflow::formNumber($ris) }}</span>
                        </td>
                        <td class="col-reqno">
                            @include('partials.procurement-request-no', ['requestNo' => $ris->ris_request_no ?? null, 'inline' => true, 'showLabel' => false])
                            @if(blank($ris->ris_request_no ?? null))
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="col-req">
                            <span class="truncate-cell">{{ \App\Support\RisWorkflow::requesterName($ris) }}</span>
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
                            @php
                                $rowActions = [[
                                    'icon' => 'pin',
                                    'label' => 'Pin to top',
                                    'class' => 'pin-btn',
                                    'onclick' => 'toggleRisPin('.(int) $ris->ris_id.')',
                                ]];
                                if ($hasAdminForward) {
                                    $rowActions[] = [
                                        'icon' => 'message-square',
                                        'label' => 'Administrator supporting details',
                                        'onclick' => 'openAdminForwardDetails('.(int) $ris->ris_id.')',
                                    ];
                                }
                                $rowActions[] = [
                                    'icon' => 'eye',
                                    'label' => 'Review approved RIS',
                                    'onclick' => 'openApprovedRisPreviewModal('.(int) $ris->ris_id.')',
                                ];
                                $rowActions[] = [
                                    'icon' => 'printer',
                                    'label' => 'Print RIS',
                                    'onclick' => 'printRisDocument('.(int) $ris->ris_id.')',
                                ];
                            @endphp
                            @include('president.approvals._row-actions', ['actions' => $rowActions])
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
                            <span class="ref-text">{{ \App\Support\RisWorkflow::formNumber($ris) }}</span>
                        </td>
                        <td class="col-reqno">
                            @include('partials.procurement-request-no', ['requestNo' => $ris->ris_request_no ?? null, 'inline' => true, 'showLabel' => false])
                            @if(blank($ris->ris_request_no ?? null))
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="col-req">
                            <span class="truncate-cell">{{ \App\Support\RisWorkflow::requesterName($ris) }}</span>
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
                            @php
                                $rowActions = [];
                                if ($hasAdminForward) {
                                    $rowActions[] = [
                                        'icon' => 'message-square',
                                        'label' => 'Administrator supporting details',
                                        'onclick' => 'openAdminForwardDetails('.(int) $ris->ris_id.')',
                                    ];
                                }
                                $rowActions[] = [
                                    'icon' => 'eye',
                                    'label' => 'Review RIS',
                                    'onclick' => 'openRisReviewModal('.(int) $ris->ris_id.')',
                                ];
                                $rowActions[] = [
                                    'icon' => 'printer',
                                    'label' => 'Print RIS',
                                    'onclick' => 'printRisDocument('.(int) $ris->ris_id.')',
                                ];
                            @endphp
                            @include('president.approvals._row-actions', ['actions' => $rowActions])
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
