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
    <div class="pm-queue-stack-wrap">
        <div class="pm-queue-stack" id="queueTableBody">
            @foreach ($awaitingNotifyRis as $ris)
                @php
                    $submitted = $ris->ris_approved_by_date
                        ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('M d, Y')
                        : ($ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : '—');
                    $sortDate = $ris->ris_approved_by_date ?: $ris->ris_created_at;
                    $hasAdminForward = trim((string) ($ris->ris_forward_details ?? '')) !== ''
                        || trim((string) ($ris->ris_forward_attachment_path ?? '')) !== '';
                    $requestNo = trim((string) ($ris->ris_request_no ?? ''));
                    $requester = \App\Support\RisWorkflow::requesterName($ris);
                    $purpose = trim((string) ($ris->ris_purpose_description ?? ''));
                @endphp
                <div
                    class="queue-row queue-row-approved"
                    data-ris-id="{{ $ris->ris_id }}"
                    data-queue-kind="awaiting_notify"
                    data-sort-date="{{ $sortDate }}"
                >
                    <div class="queue-row-body min-w-0 flex-1">
                        <div class="queue-row-top">
                            <p class="ref-text">{{ \App\Support\RisWorkflow::formNumber($ris) }}</p>
                            <div class="queue-row-actions">
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
                            </div>
                        </div>
                        <div class="queue-row-status">
                            <span class="status-pill status-notify">Notify Administrator</span>
                        </div>
                        <p class="queue-row-meta">
                            <span>{{ $requester }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ $submitted }}</span>
                            <span aria-hidden="true">·</span>
                            <span>₱{{ number_format($ris->total_amount ?? 0, 2) }}</span>
                        </p>
                        @if($requestNo !== '')
                            <div class="queue-row-reqno">
                                @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'showLabel' => false])
                            </div>
                        @endif
                        <p class="queue-row-purpose">{{ $purpose !== '' ? $purpose : '—' }}</p>
                    </div>
                </div>
            @endforeach

            @foreach ($pendingRis as $ris)
                @php
                    $submitted = $ris->ris_created_at
                        ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y')
                        : '—';
                    $hasAdminForward = trim((string) ($ris->ris_forward_details ?? '')) !== ''
                        || trim((string) ($ris->ris_forward_attachment_path ?? '')) !== '';
                    $requestNo = trim((string) ($ris->ris_request_no ?? ''));
                    $requester = \App\Support\RisWorkflow::requesterName($ris);
                    $purpose = trim((string) ($ris->ris_purpose_description ?? ''));
                @endphp
                <div
                    class="queue-row queue-row-pending"
                    data-ris-id="{{ $ris->ris_id }}"
                    data-queue-kind="pending"
                    data-sort-date="{{ $ris->ris_created_at }}"
                >
                    <div class="queue-row-body min-w-0 flex-1">
                        <div class="queue-row-top">
                            <p class="ref-text">{{ \App\Support\RisWorkflow::formNumber($ris) }}</p>
                            <div class="queue-row-actions">
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
                                        'class' => 'review-btn',
                                        'onclick' => 'openRisReviewModal('.(int) $ris->ris_id.')',
                                    ];
                                    $rowActions[] = [
                                        'icon' => 'check',
                                        'label' => 'Approve',
                                        'class' => 'approve-btn',
                                        'onclick' => 'openDecisionModal(\'ris\', '.(int) $ris->ris_id.', \'Approved\')',
                                    ];
                                    $rowActions[] = [
                                        'icon' => 'rotate-ccw',
                                        'label' => 'Return for Revision',
                                        'class' => 'revise-btn',
                                        'onclick' => 'openPresidentRevisionModal('.(int) $ris->ris_id.')',
                                    ];
                                    $rowActions[] = [
                                        'icon' => 'printer',
                                        'label' => 'Print RIS',
                                        'onclick' => 'printRisDocument('.(int) $ris->ris_id.')',
                                    ];
                                @endphp
                                @include('president.approvals._row-actions', ['actions' => $rowActions])
                            </div>
                        </div>
                        <div class="queue-row-status">
                            <span class="status-pill status-pending">Pending</span>
                        </div>
                        <p class="queue-row-meta">
                            <span>{{ $requester }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ $submitted }}</span>
                            <span aria-hidden="true">·</span>
                            <span>₱{{ number_format($ris->total_amount ?? 0, 2) }}</span>
                        </p>
                        @if($requestNo !== '')
                            <div class="queue-row-reqno">
                                @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'showLabel' => false])
                            </div>
                        @endif
                        <p class="queue-row-purpose">{{ $purpose !== '' ? $purpose : '—' }}</p>
                    </div>
                </div>
            @endforeach        </div>

        {{-- Hidden table kept for Word export --}}
        <table class="pm-queue-table pm-queue-export-table" aria-hidden="true">
            <thead>
                <tr>
                    <th>RIS Number</th>
                    <th>Request No.</th>
                    <th>Requester</th>
                    <th>Purpose</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($awaitingNotifyRis as $ris)
                    <tr>
                        <td>{{ \App\Support\RisWorkflow::formNumber($ris) }}</td>
                        <td>{{ $ris->ris_request_no ?? '—' }}</td>
                        <td>{{ \App\Support\RisWorkflow::requesterName($ris) }}</td>
                        <td>{{ $ris->ris_purpose_description ?: '—' }}</td>
                        <td>₱{{ number_format($ris->total_amount ?? 0, 2) }}</td>
                        <td>{{ $ris->ris_approved_by_date ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('M d, Y') : ($ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : '—') }}</td>
                        <td>Approved</td>
                    </tr>
                @endforeach
                @foreach ($pendingRis as $ris)
                    <tr>
                        <td>{{ \App\Support\RisWorkflow::formNumber($ris) }}</td>
                        <td>{{ $ris->ris_request_no ?? '—' }}</td>
                        <td>{{ \App\Support\RisWorkflow::requesterName($ris) }}</td>
                        <td>{{ $ris->ris_purpose_description ?: '—' }}</td>
                        <td>₱{{ number_format($ris->total_amount ?? 0, 2) }}</td>
                        <td>{{ $ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : '—' }}</td>
                        <td>Pending</td>
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
