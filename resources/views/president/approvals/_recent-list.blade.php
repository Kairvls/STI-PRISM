@if ($recentRis->count() > 0)
    <div class="pm-queue-table-wrap">
        <table class="pm-queue-table">
            <thead>
                <tr>
                    <th class="col-ref">RIS Number</th>
                    <th class="col-reqno">Request No.</th>
                    <th class="col-purpose">Purpose</th>
                    <th class="col-date">Date</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recentRis as $ris)
                    @php
                        $isApproved = !empty($ris->is_president_approved);
                        $isRejected = in_array((string) ($ris->ris_status ?? ''), ['Rejected', 'Rejected by President', 'Rejected by the President'], true);
                        $displayStatus = $isApproved || $isRejected
                            ? \App\Support\RisWorkflow::statusLabel($ris)
                            : \App\Support\ProcurementStatus::SA_FORWARDED_TO_PRESIDENT;
                        $requestNo = trim((string) ($ris->ris_request_no ?? ''));
                        $purpose = trim((string) ($ris->ris_purpose_description ?? ''));
                        $dateLabel = $ris->ris_approved_by_date
                            ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('M d, Y')
                            : ($ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : '—');
                        $formNumber = \App\Support\RisWorkflow::formNumber($ris);
                        $recentDocs = $ris->supportingDocuments ?? [];
                        $rowActions = [];
                        if ($isApproved) {
                            $rowActions[] = [
                                'icon' => 'eye',
                                'label' => 'Open approved RIS',
                                'onclick' => 'openApprovedRisPreviewModal('.(int) $ris->ris_id.')',
                            ];
                        }
                        $rowActions[] = [
                            'icon' => 'printer',
                            'label' => 'Print RIS',
                            'onclick' => 'printRisDocument('.(int) $ris->ris_id.')',
                        ];
                    @endphp
                    <tr class="queue-row {{ !empty($ris->awaiting_notify) ? 'is-awaiting-notify' : '' }}">
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
                        <td class="col-purpose">
                            <span class="truncate-cell" title="{{ $purpose !== '' ? $purpose : '—' }}">{{ $purpose !== '' ? $purpose : '—' }}</span>
                            @if (count($recentDocs) > 0)
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($recentDocs as $file)
                                        <a
                                            href="{{ $file['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-xs text-blue-700 hover:underline"
                                            title="{{ $file['name'] }}"
                                            onclick="event.stopPropagation()"
                                        >{{ $file['name'] }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="col-date">{{ $dateLabel }}</td>
                        <td class="col-status">
                            @include('accounting.partials.status-badge', ['status' => $displayStatus])
                        </td>
                        <td class="col-actions">
                            @include('president.approvals._row-actions', ['actions' => $rowActions])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="empty-note">No recent decisions</p>
@endif
