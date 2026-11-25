@php
    $awaitingNotifyRis = $awaitingNotifyRis ?? collect();
    $hasRows = $awaitingNotifyRis->isNotEmpty() || $pendingRis->count() > 0;
    $attentionFocus = $attentionFocus ?? null;
    $attentionFocusTotal = $attentionFocus['total'] ?? (($attentionFocus['key'] ?? null) === \App\Support\PresidentAttentionSummary::FOCUS_AWAITING_NOTIFY
        ? $awaitingNotifyRis->count()
        : $pendingRis->total());
    $presQueueRisIds = collect($awaitingNotifyRis)->pluck('ris_id')
        ->merge(collect($pendingRis->items() ?? [])->pluck('ris_id'))
        ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    $presAtpEntriesByRis = [];
    if (!empty($presQueueRisIds) && \Illuminate\Support\Facades\Schema::hasTable('authority_to_purchase_table')) {
        $atpRows = \Illuminate\Support\Facades\DB::table('authority_to_purchase_table as atp')
            ->leftJoin('suppliers_table as sup', 'atp.authority_purchase_supplier_id', '=', 'sup.supplier_id')
            ->leftJoin('physical_suppliers_table as psup', 'sup.supplier_id', '=', 'psup.supplier_id')
            ->leftJoin('online_suppliers_table as osup', 'sup.supplier_id', '=', 'osup.supplier_id')
            ->whereIn('atp.authority_purchase_ris_id', $presQueueRisIds)
            ->orderBy('atp.authority_purchase_id')
            ->get([
                'atp.authority_purchase_ris_id',
                'atp.authority_purchase_id',
                'atp.authority_purchase_form_number',
                'psup.company_name',
                'osup.shop_name',
            ]);
        foreach ($atpRows as $ar) {
            $rid = (int) $ar->authority_purchase_ris_id;
            $num = trim((string) ($ar->authority_purchase_form_number ?? ''));
            if ($num === '') {
                $num = 'ATP #'.$ar->authority_purchase_id;
            }
            $sup = trim((string) ($ar->company_name ?: ($ar->shop_name ?: '')));
            $presAtpEntriesByRis[$rid][] = ['number' => $num, 'supplier' => $sup];
        }
    }
@endphp

@include('partials.attention-focus-chip', ['focus' => $attentionFocus, 'total' => $attentionFocusTotal])

@if ($hasRows)
    <div class="pm-queue-table-wrap">
        <table class="pm-queue-table">
            <thead>
                <tr>
                    <th class="col-ref">RIS &amp; ATP Number</th>
                    <th class="col-reqno">Request No.</th>
                    <th class="col-req">Requester</th>
                    <th class="col-purpose">Purpose</th>
                    <th class="col-date">Date</th>
                    <th class="col-status" style="width:170px;min-width:160px;">Status</th>
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
                            @include('partials.atp-more-popover', ['atps' => $presAtpEntriesByRis[(int) $ris->ris_id] ?? []])
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
                            <div class="inline-flex flex-col items-start">
                                <span
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                    style="background:#ecfdf5;border-color:#a7f3d0;color:#059669;"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#10b981;"></span>
                                    <span>Approved</span>
                                </span>
                                <span class="mt-1 text-[11px] font-medium leading-tight text-slate-500">By President · Awaiting notify</span>
                            </div>
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
                            @include('partials.atp-more-popover', ['atps' => $presAtpEntriesByRis[(int) $ris->ris_id] ?? []])
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
                        <td class="col-status" style="overflow:visible;white-space:normal;">
                            <div class="inline-flex flex-col items-start">
                                <span
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                    style="background:#eef2ff;border-color:#c7d2fe;color:#4338ca;"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#4f46e5;"></span>
                                    <span>Need Approval</span>
                                </span>
                                <span class="mt-1 whitespace-nowrap text-[11px] font-medium leading-tight text-slate-500">Forwarded by SA</span>
                            </div>
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
                                    'icon' => 'check',
                                    'label' => 'Review & Approve RIS & ATP',
                                    'class' => 'approve-btn',
                                    'onclick' => 'openDecisionModal(\'ris\', '.(int) $ris->ris_id.', \'Approved\')',
                                ];
                                $rowActions[] = [
                                    'icon' => 'rotate-ccw',
                                    'label' => 'Return for Revision',
                                    'class' => 'revise-btn',
                                    'onclick' => 'openPresidentRevisionModal('.(int) $ris->ris_id.')',
                                ];
                                $costItems = ($costBreakdownByRis ?? [])[(int) $ris->ris_id] ?? [];
                                $costTotal = collect($costItems)->sum('amount');
                                if ($costTotal <= 0) {
                                    $costTotal = (float) ($ris->total_amount ?? 0);
                                }
                                $limitVal = (float) ($budgetLimit ?? \App\Support\RisApprovalBudget::limit());
                                $costSummaryPayload = json_encode([
                                    'ris_number' => $formNumber,
                                    'request_no' => $requestNo,
                                    'total' => $costTotal,
                                    'limit' => $limitVal,
                                    'requires_president' => $costTotal > $limitVal,
                                    'items' => $costItems,
                                ]);
                                $rowActions[] = [
                                    'icon' => 'calculator',
                                    'label' => 'Cost Summary',
                                    'class' => 'cost-summary-btn',
                                    'data_summary' => $costSummaryPayload,
                                    'onclick' => 'openPresidentCostSummaryModal(this)',
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
        <p class="mt-1 text-xs text-gray-500">Forwarded RIS &amp; ATP packages will appear here.</p>
    </div>
@endif
