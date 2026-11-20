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
                        $requestNo = trim((string) ($ris->ris_request_no ?? ''));
                        $requester = \App\Support\RisWorkflow::requesterName($ris);
                        $purpose = trim((string) ($ris->ris_purpose_description ?? ''));
                        $formNumber = \App\Support\RisWorkflow::formNumber($ris);
                    @endphp
                    <tr
                        class="queue-row queue-row-approved"
                        data-ris-id="{{ $ris->ris_id }}"
                        data-queue-kind="awaiting_notify"
                        data-sort-date="{{ $sortDate }}"
                    >
                        <td class="col-ref">
                            <span class="ref-text" title="{{ $formNumber }}">{{ $formNumber }}</span>
                        </td>
                        <td class="col-reqno">
                            @if ($requestNo !== '')
                                @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'showLabel' => false])
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="col-req">
                            <span class="truncate-cell" title="{{ $requester }}">{{ $requester }}</span>
                        </td>
                        <td class="col-purpose">
                            <span class="truncate-cell" title="{{ $purpose !== '' ? $purpose : '—' }}">{{ $purpose !== '' ? $purpose : '—' }}</span>
                        </td>
                        <td class="col-date">{{ $submitted }}</td>
                        <td class="col-status">
                            @include('accounting.partials.status-badge', ['status' => \App\Support\ProcurementStatus::APPROVED_BY_PRESIDENT_NEED_ISSUED_BY])
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
                        $requestNo = trim((string) ($ris->ris_request_no ?? ''));
                        $requester = \App\Support\RisWorkflow::requesterName($ris);
                        $purpose = trim((string) ($ris->ris_purpose_description ?? ''));
                        $formNumber = \App\Support\RisWorkflow::formNumber($ris);
                    @endphp
                    <tr
                        class="queue-row queue-row-pending"
                        data-ris-id="{{ $ris->ris_id }}"
                        data-queue-kind="pending"
                        data-sort-date="{{ $ris->ris_created_at }}"
                    >
                        <td class="col-ref">
                            <span class="ref-text" title="{{ $formNumber }}">{{ $formNumber }}</span>
                        </td>
                        <td class="col-reqno">
                            @if ($requestNo !== '')
                                @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'showLabel' => false])
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="col-req">
                            <span class="truncate-cell" title="{{ $requester }}">{{ $requester }}</span>
                        </td>
                        <td class="col-purpose">
                            <span class="truncate-cell" title="{{ $purpose !== '' ? $purpose : '—' }}">{{ $purpose !== '' ? $purpose : '—' }}</span>
                        </td>
                        <td class="col-date">{{ $submitted }}</td>
                        <td class="col-status">
                            @include('accounting.partials.status-badge', ['status' => \App\Support\ProcurementStatus::SA_FORWARDED_TO_PRESIDENT])
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
                                    'icon' => 'x-circle',
                                    'label' => 'Reject',
                                    'class' => 'reject-btn',
                                    'onclick' => 'openDecisionModal(\'ris\', '.(int) $ris->ris_id.', \'Rejected\')',
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
