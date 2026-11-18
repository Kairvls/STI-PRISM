@forelse ($recentRis as $ris)
    @php
        $isApproved = !empty($ris->is_president_approved);
        $isRejected = in_array((string) ($ris->ris_status ?? ''), ['Rejected', 'Rejected by President', 'Rejected by the President'], true);
        $displayStatus = $isApproved
            ? (!empty($ris->awaiting_notify) ? 'Notify Administrator' : 'Approved')
            : ($isRejected ? 'Rejected' : 'Pending');
        $statusClass = $isApproved
            ? (!empty($ris->awaiting_notify) ? 'status-notify' : 'status-approved')
            : ($isRejected ? 'status-rejected' : 'status-pending');
        $requestNo = trim((string) ($ris->ris_request_no ?? ''));
        $purpose = trim((string) ($ris->ris_purpose_description ?? ''));
        $dateLabel = $ris->ris_approved_by_date
            ? \Carbon\Carbon::parse($ris->ris_approved_by_date)->format('M d, Y')
            : ($ris->ris_created_at ? \Carbon\Carbon::parse($ris->ris_created_at)->format('M d, Y') : '—');
        $recentDocs = $ris->supportingDocuments ?? [];
    @endphp
    <div class="recent-row recent-row-stack {{ !empty($ris->awaiting_notify) ? 'is-awaiting-notify' : '' }}">
        <div class="recent-row-body min-w-0 flex-1">
            <div class="recent-row-top">
                <p class="recent-row-ref">{{ \App\Support\RisWorkflow::formNumber($ris) }}</p>
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
            <div class="recent-row-status">
                <span class="status-pill {{ $statusClass }}">{{ $displayStatus }}</span>
            </div>
            <p class="recent-row-meta">
                <span>{{ $dateLabel }}</span>
                @if(isset($ris->total_amount))
                    <span aria-hidden="true">·</span>
                    <span>₱{{ number_format((float) $ris->total_amount, 2) }}</span>
                @endif
            </p>
            @if($requestNo !== '')
                <div class="recent-row-reqno">
                    @include('partials.procurement-request-no', ['requestNo' => $requestNo, 'inline' => true, 'showLabel' => false])
                </div>
            @endif
            <p class="recent-row-purpose">{{ $purpose !== '' ? $purpose : '—' }}</p>
            @if (count($recentDocs) > 0)
                <div class="recent-row-docs">
                    @foreach ($recentDocs as $file)
                        <a
                            href="{{ $file['url'] }}"
                            target="_blank"
                            rel="noopener"
                            class="recent-row-doc"
                            title="{{ $file['name'] }}"
                            onclick="event.stopPropagation()"
                        >{{ $file['name'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@empty
    <p class="empty-note">No recent decisions</p>
@endforelse
