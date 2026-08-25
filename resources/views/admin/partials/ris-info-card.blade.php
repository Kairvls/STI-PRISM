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
        $title = $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? ($ris->ris_form_number ?? ('RIS-' . $ris->ris_id)));
    }
    $requestor = $ris->ris_requested_by_signature ?? 'Purchaser';
    $dateRaw = $ris->ris_submitted_at ?? $ris->ris_requested_by_date ?? $ris->ris_created_at ?? null;
    $dateLabel = $dateRaw ? \Carbon\Carbon::parse($dateRaw)->format('M d, Y g:i A') : '—';
    $amount = number_format((float) ($ris->ris_calculated_total ?? 0), 2);
    $ref = $ris->ris_form_number ?? ('RIS-' . $ris->ris_id);
    $statusLabel = \App\Support\RisWorkflow::statusLabel($ris);
    $isPending = in_array($status, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true);

    $awaitingSign = $cardMode === 'sign' && \App\Support\RisWorkflow::needsAdminIssuedBy($ris);
    $isPresidentRejected = $cardMode === 'sign' && \App\Support\RisWorkflow::canReturnForRevision($ris);
@endphp

<article class="admin-ris-info-card rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-black shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-sm font-bold text-black" title="{{ $title }}">{{ $title }}</h3>
            <p class="mt-0.5 text-[11px] font-medium text-slate-500">{{ $ref }}</p>
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
            <span class="min-w-0 truncate text-slate-800" title="{{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? '—') }}">
                {{ $ris->ris_purpose_description ?: ($ris->ris_manual_description ?? '—') }}
            </span>
        </div>
        <div class="flex gap-2">
            <span class="w-24 shrink-0 font-semibold text-slate-500">Amount</span>
            <span class="min-w-0 font-semibold text-slate-900">₱{{ $amount }}</span>
        </div>
        <div class="flex gap-2">
            <span class="w-24 shrink-0 font-semibold text-slate-500">Requested</span>
            <span class="min-w-0 text-slate-800">{{ $dateLabel }}</span>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-end gap-1.5">
        @if ($cardMode === 'procurement')
            <button type="button" onclick="window.openRisPreviewModal('{{ $ris->ris_id }}')" title="Preview RIS" aria-label="Preview RIS"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            @include('admin.partials.ris-print-icon-button', ['risId' => $ris->ris_id])
            @if ($isPending)
                <button type="button" onclick="openDirectApproveModal('{{ $ris->ris_id }}', 'forward')" title="Forward to President" aria-label="Forward to President"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
                <button type="button" onclick="openDirectApproveModal('{{ $ris->ris_id }}', 'direct')" title="Admin Approve" aria-label="Admin Approve"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600 text-white hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
                <button type="button" onclick="openAmendModal('{{ $ris->ris_id }}')" title="Amend / return" aria-label="Amend"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            @endif
        @elseif ($cardMode === 'sign')
            <button type="button" onclick="window.openSignRisPreviewModal('{{ $ris->ris_id }}')" title="Preview RIS" aria-label="Preview RIS"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            @include('admin.partials.ris-print-icon-button', ['risId' => $ris->ris_id])
            @if ($awaitingSign)
                <button type="button" onclick="window.openCoSignModal('{{ $ris->ris_id }}')" title="Sign Issued by" aria-label="Sign Issued by"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600 text-white hover:bg-sky-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-3.172-3.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"/></svg>
                </button>
            @endif
            @if ($isPresidentRejected)
                <button type="button" onclick="window.openReturnRevisionModal('{{ $ris->ris_id }}')" title="Return for revision" aria-label="Return for revision"
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
