{{--
  Horizontal RIS info card (Admin mock format).
  Expects: $ris
  Optional: $cardMode = procurement|sign|history
--}}
@php
    $cardMode = $cardMode ?? 'procurement';
    $status = (string) ($ris->ris_status ?? '');
    $title = \App\Support\RisWorkflow::sourceLabel($ris);
    if ($title === '' || $title === 'N/A' || $title === '—') {
        $title = trim((string) ($ris->ris_purpose_description ?? ''))
            ?: trim((string) ($ris->ris_manual_description ?? ''))
            ?: \App\Support\RisWorkflow::formNumber($ris);
    }
    $requestor = \App\Support\RisWorkflow::requesterName($ris);
    $dateRaw = $ris->ris_submitted_at ?? $ris->ris_requested_by_date ?? $ris->ris_created_at ?? null;
    $dateLabel = $dateRaw ? \Carbon\Carbon::parse($dateRaw)->format('M d, Y g:i A') : '—';
    $amount = number_format((float) ($ris->ris_calculated_total ?? 0), 2);
    $ref = \App\Support\RisWorkflow::formNumber($ris);
    $statusLabel = \App\Support\RisWorkflow::statusLabel($ris);
    $risCanAct = \App\Support\AdminPortal::canActOnRis();
    $isPending = $risCanAct && in_array($status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true);
    $passToPurchaser = $risCanAct && \App\Support\RisWorkflow::canReturnForRevision($ris);
    $allowsDirectApprove = \App\Support\RisWorkflow::allowsDirectApprove($ris);
    $reforwardToPresident = \App\Support\RisWorkflow::wasReturnedByPresidentForRevision($ris);
    $ownerPurchaserId = \App\Support\RisWorkflow::ownerPurchaserId($ris);
    $ownerPurchaserName = \App\Support\RisWorkflow::requesterName($ris, '');

    $awaitingSign = $risCanAct && $cardMode === 'sign' && \App\Support\RisWorkflow::needsAdminIssuedBy($ris);
    $isPresidentRejected = $risCanAct && $cardMode === 'sign' && $passToPurchaser;
@endphp

<article class="admin-ris-info-card rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-black shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-start gap-2.5">
            @if ($cardMode === 'procurement' && $isPending)
                <input
                    type="checkbox"
                    class="ris-accept-checkbox mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-[#0025cc] focus:ring-[#0025cc]"
                    value="{{ $ris->ris_id }}"
                    data-ref="{{ $ref }}"
                    title="Select {{ $ref }}"
                    aria-label="Select {{ $ref }}"
                    onchange="typeof window.updateRisAcceptSelection === 'function' && window.updateRisAcceptSelection()"
                >
            @endif
            <div class="min-w-0">
                @include('admin.partials.ris-equipment-cell', ['ris' => $ris])
                <p class="mt-0.5 text-[11px] font-medium text-slate-500">{{ $ref }}</p>
                @if ($cardMode === 'sign' && $reforwardToPresident && \App\Support\RisWorkflow::needsSignDecision($ris))
                    <div class="mt-1.5">
                        @include('partials.ris-reforward-badge', ['size' => 'sm'])
                    </div>
                @endif
            </div>
        </div>
        @include('admin.partials.ris-status-badge', ['ris' => $ris])
    </div>

    <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-1.5 text-xs sm:grid-cols-2">
        <div class="flex gap-2">
            <span class="w-24 shrink-0 font-semibold text-slate-500">Status</span>
            <span class="min-w-0 text-slate-800">{{ $statusLabel }}</span>
        </div>
        <div class="flex gap-2">
            <span class="w-24 shrink-0 font-semibold text-slate-500">Requestor</span>
            <span class="min-w-0 truncate text-slate-800" title="{{ $requestor }}">{{ $requestor }}</span>
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <span class="w-24 shrink-0 font-semibold text-slate-500">Activity</span>
            @php
                $activity = trim((string) ($ris->ris_purpose_description ?? ''))
                    ?: trim((string) ($ris->ris_manual_description ?? ''))
                    ?: '—';
            @endphp
            <span class="min-w-0 truncate text-slate-800" title="{{ $activity }}">
                {{ $activity }}
            </span>
        </div>
        <div class="flex gap-2">
            <span class="w-24 shrink-0 font-semibold text-slate-500">Submitted</span>
            <span class="min-w-0 text-slate-800">{{ $dateLabel }}</span>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-end gap-1.5">
        @if ($cardMode === 'procurement')
            <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')" title="Preview RIS" aria-label="Preview RIS"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            @if ($isPending)
                <button type="button"
                    onclick="window.openAcceptRisModal('{{ $ris->ris_id }}', @js(\App\Support\RisWorkflow::formNumber($ris)), @js($title))"
                    data-tip="Accept"
                    data-tip-place="above"
                    aria-label="Accept"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-[#0025cc] transition hover:border-blue-300 hover:bg-blue-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </button>
                <button type="button"
                    onclick="window.openAmendModal('{{ $ris->ris_id }}')"
                    data-tip="Return for Revision"
                    data-tip-place="above"
                    aria-label="Return for Revision"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5"/></svg>
                </button>
                @include('admin.procurement-review._ris-action-menu', [
                    'risId' => $ris->ris_id,
                    'ris' => $ris,
                    'mode' => 'pending',
                    'shareLabel' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                ])
            @elseif ($passToPurchaser)
                <button type="button"
                    onclick="window.openReturnRevisionModal('{{ $ris->ris_id }}', {{ (int) $ownerPurchaserId }}, @js($ownerPurchaserName))"
                    data-tip="Pass to Purchaser"
                    data-tip-place="above"
                    aria-label="Pass to Purchaser"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v5h5"/></svg>
                </button>
                @include('partials.share-document-button', [
                    'type' => 'ris',
                    'id' => $ris->ris_id,
                    'label' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                ])
            @else
                @include('partials.share-document-button', [
                    'type' => 'ris',
                    'id' => $ris->ris_id,
                    'label' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                ])
            @endif
        @elseif ($cardMode === 'sign')
            <button type="button" onclick="window.openSignRisPreviewModal('{{ $ris->ris_id }}')" title="View RIS" aria-label="View RIS"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            @if ($awaitingSign)
                <button type="button" onclick="window.openCoSignModal('{{ $ris->ris_id }}')" title="Sign Issued by" aria-label="Sign Issued by"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600 text-white hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-3.172-3.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg>
                </button>
            @endif
            @if (\App\Support\RisWorkflow::needsSignDecision($ris) && \App\Support\AdminPortal::canActOnRis())
                @php
                    $cardRisTotal = \App\Support\RisApprovalBudget::risTotal($ris);
                    $cardBudgetLimit = \App\Support\RisApprovalBudget::limit();
                    $cardRequiresPresident = \App\Support\RisApprovalBudget::requiresPresident($cardRisTotal, $cardBudgetLimit);
                    $cardCanForward = $reforwardToPresident || $cardRequiresPresident;
                    $cardCanDirectApprove = $allowsDirectApprove && ! $cardRequiresPresident;
                    $forwardMode = $reforwardToPresident ? 'forward-back' : 'forward';
                    $forwardTip = $cardCanForward
                        ? ($reforwardToPresident ? 'Forward back to President' : 'Forward RIS to President')
                        : 'Disabled: Total ('.\App\Support\RisApprovalBudget::formatPeso($cardRisTotal).') is within the '.\App\Support\RisApprovalBudget::formatPeso($cardBudgetLimit).' limit — use Approve Directly.';
                    $directApproveTip = $cardCanDirectApprove
                        ? 'Approve Directly'
                        : 'Disabled: Total ('.\App\Support\RisApprovalBudget::formatPeso($cardRisTotal).') exceeds the '.\App\Support\RisApprovalBudget::formatPeso($cardBudgetLimit).' limit — forward to President.';
                @endphp
                <button type="button"
                    @if ($cardCanForward)
                        onclick="window.openDirectApproveModal('{{ $ris->ris_id }}', '{{ $forwardMode }}')"
                    @else
                        disabled
                    @endif
                    data-tip="{{ $forwardTip }}"
                    data-tip-place="above"
                    title="{{ $forwardTip }}"
                    aria-label="{{ $forwardTip }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition {{ $cardCanForward ? ($reforwardToPresident ? 'border-orange-200 bg-orange-50 text-orange-700 hover:border-orange-300 hover:bg-orange-100 hover:text-orange-800' : 'border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800') : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-50' }}">
                    @if ($reforwardToPresident)
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2M3 10l4-4M3 10l4 4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-1.2 7.8"/></svg>
                    @else
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    @endif
                </button>
                @if ($allowsDirectApprove)
                    <button type="button"
                        @if ($cardCanDirectApprove)
                            onclick="window.openDirectApproveModal('{{ $ris->ris_id }}', 'direct')"
                        @else
                            disabled
                        @endif
                        data-tip="{{ $directApproveTip }}"
                        data-tip-place="above"
                        title="{{ $directApproveTip }}"
                        aria-label="{{ $directApproveTip }}"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition {{ $cardCanDirectApprove ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100 hover:text-emerald-800' : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-50' }}">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                @endif
            @endif
            @include('admin.partials.ris-president-remarks-button', [
                'ris' => $ris,
                'btnClass' => 'inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900',
            ])
            @if ($isPresidentRejected)
                <button type="button" onclick="window.openReturnRevisionModal('{{ $ris->ris_id }}', {{ (int) $ownerPurchaserId }}, @js($ownerPurchaserName))" data-tip="Pass to Purchaser" data-tip-place="above" title="Pass to Purchaser" aria-label="Pass to Purchaser"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            @endif
        @else
            <button type="button" onclick="window.openSignatureHistoryPreviewModal('{{ $ris->ris_id }}')" title="Preview RIS" aria-label="Preview RIS"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            @include('admin.partials.ris-print-icon-button', ['risId' => $ris->ris_id])
        @endif
    </div>
</article>
