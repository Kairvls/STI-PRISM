{{-- ===================================================== --}}
{{-- SIGN RIS TABLE PARTIAL --}}
{{-- ===================================================== --}}

<table class="pur-table w-full table-fixed">

    <thead>
        <tr>
            <th class="w-[13%]">RIS Number</th>
            <th class="w-[12%]">Request No.</th>
            <th class="w-[24%]">Equipment</th>
            <th class="w-[15%]">Requested By</th>
            <th class="w-[18%]">Status</th>
            <th class="w-[18%] text-center">Actions</th>
        </tr>
    </thead>

    <tbody>
        @forelse($signableRisRecords as $ris)
            @php
                $awaitingSign = \App\Support\RisWorkflow::needsAdminIssuedBy($ris);
                $needsDecision = \App\Support\RisWorkflow::needsSignDecision($ris);
                $allowsDirectApprove = \App\Support\RisWorkflow::allowsDirectApprove($ris);
                $reforwardToPresident = \App\Support\RisWorkflow::wasReturnedByPresidentForRevision($ris);
                $isPresidentRejected = \App\Support\RisWorkflow::canReturnForRevision($ris);
                $ownerPurchaserId = \App\Support\RisWorkflow::ownerPurchaserId($ris);
                $ownerPurchaserName = \App\Support\RisWorkflow::requesterName($ris, '');
                $rowDimmed = !$needsDecision && !$awaitingSign && !$isPresidentRejected;
                $risCanAct = \App\Support\AdminPortal::canActOnRis();
                $risTotalAmount = \App\Support\RisApprovalBudget::risTotal($ris);
                $budgetLimit = \App\Support\RisApprovalBudget::limit();
                $requiresPresident = \App\Support\RisApprovalBudget::requiresPresident($risTotalAmount, $budgetLimit);
                $canForwardByBudget = $reforwardToPresident || $requiresPresident;
                $canDirectApproveByBudget = $allowsDirectApprove && ! $requiresPresident;
            @endphp

            <tr class="transition hover:bg-gray-50/70 {{ $rowDimmed ? 'bg-gray-50/50 text-gray-500' : '' }}">
                <td>
                    @php $risNumberLabel = \App\Support\RisWorkflow::formNumber($ris); @endphp
                    <div class="min-w-0">
                        <div
                            class="truncate text-sm font-semibold {{ $rowDimmed ? 'text-gray-500' : 'text-gray-900' }}"
                            data-tip="{{ $risNumberLabel }}"
                            data-truncate-tip="{{ $risNumberLabel }}"
                        >{{ $risNumberLabel !== '' ? $risNumberLabel : '—' }}</div>
                        <div class="mt-0.5 flex flex-wrap items-center gap-1">
                            @include('partials.ris-urgency-badge', [
                                'urgent' => \App\Support\RisWorkflow::isUrgent($ris),
                                'size' => 'sm',
                            ])
                            @if($risTotalAmount > 0)
                                <span
                                    class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold tabular-nums"
                                    style="{{ $requiresPresident ? 'background:#e0f2fe;color:#0369a1;' : 'background:#ecfdf5;color:#047857;' }}"
                                    title="{{ $requiresPresident ? 'Exceeds '.\App\Support\RisApprovalBudget::formatPeso($budgetLimit).' limit (President approval required)' : 'Within '.\App\Support\RisApprovalBudget::formatPeso($budgetLimit).' limit (Direct Approve)' }}"
                                >{{ \App\Support\RisApprovalBudget::formatPeso($risTotalAmount) }}</span>
                            @endif
                            @if($reforwardToPresident && $needsDecision)
                                @include('partials.ris-reforward-badge', ['size' => 'sm'])
                            @endif
                        </div>
                    </div>
                </td>

                <td>
                    @include('partials.procurement-request-no', ['requestNo' => $ris->ris_request_no ?? null, 'inline' => true, 'showLabel' => false])
                    @if(blank($ris->ris_request_no ?? null))
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>

                <td>
                    @include('admin.partials.ris-equipment-cell', [
                        'ris' => $ris,
                        'muted' => $rowDimmed,
                    ])
                    @include('admin.partials.ris-attachments', ['ris' => $ris])
                </td>

                <td>
                    @php
                        $risSubmittedRaw = $ris->ris_submitted_at ?? $ris->ris_requested_by_date ?? null;
                        $risSubmittedLabel = $risSubmittedRaw
                            ? \Carbon\Carbon::parse($risSubmittedRaw)->format('M j, Y')
                            : null;
                    @endphp
                    @php $requesterLabel = \App\Support\RisWorkflow::requesterName($ris); @endphp
                    <div
                        class="truncate text-sm font-medium {{ $rowDimmed ? 'text-gray-500' : 'text-gray-700' }}"
                        data-tip="{{ $requesterLabel }}"
                        data-truncate-tip="{{ $requesterLabel }}"
                    >
                        {{ $requesterLabel }}
                    </div>
                    <div
                        class="mt-0.5 whitespace-nowrap text-xs font-medium {{ $rowDimmed ? 'text-gray-400' : 'text-gray-600' }}"
                        data-tip="{{ $risSubmittedLabel ? 'Submitted '.$risSubmittedLabel : 'Submit date unavailable' }}"
                    >
                        @if ($risSubmittedLabel)
                            Submitted {{ $risSubmittedLabel }}
                        @else
                            Not submitted
                        @endif
                    </div>
                </td>

                <td class="min-w-0 align-middle">
                    @include('admin.partials.ris-status-badge', ['ris' => $ris, 'wrap' => true])
                </td>

                <td class="text-center">
                    <div class="inline-flex items-center justify-center gap-1.5">
                        @if($awaitingSign && $risCanAct)
                            <button
                                type="button"
                                onclick="window.openCoSignModal('{{ $ris->ris_id }}')"
                                title="Sign Issued by"
                                aria-label="Sign Issued by"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fa8]"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M4 20h4.586a1 1 0 00.707-.293l9.414-9.414a2 2 0 000-2.828l-3.172-3.172a2 2 0 00-2.828 0L4.293 14.707A1 1 0 004 15.414V20z"></path>
                                </svg>
                            </button>
                        @endif

                        @if($needsDecision && $risCanAct)
                            @php
                                $forwardMode = $reforwardToPresident ? 'forward-back' : 'forward';
                                $forwardTip = $canForwardByBudget
                                    ? ($reforwardToPresident ? 'Forward back to President' : 'Forward RIS to President')
                                    : 'Disabled: Total ('.\App\Support\RisApprovalBudget::formatPeso($risTotalAmount).') is within the '.\App\Support\RisApprovalBudget::formatPeso($budgetLimit).' limit — use Approve Directly.';
                                $directApproveTip = $canDirectApproveByBudget
                                    ? 'Approve Directly'
                                    : 'Disabled: Total ('.\App\Support\RisApprovalBudget::formatPeso($risTotalAmount).') exceeds the '.\App\Support\RisApprovalBudget::formatPeso($budgetLimit).' limit — forward to President.';
                            @endphp
                            <button
                                type="button"
                                @if($canForwardByBudget)
                                    onclick="window.openDirectApproveModal('{{ $ris->ris_id }}', '{{ $forwardMode }}')"
                                @else
                                    disabled
                                @endif
                                data-tip="{{ $forwardTip }}"
                                data-tip-place="above"
                                title="{{ $forwardTip }}"
                                aria-label="{{ $forwardTip }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition {{ $canForwardByBudget ? ($reforwardToPresident ? 'border-orange-200 bg-orange-50 text-orange-700 hover:border-orange-300 hover:bg-orange-100 hover:text-orange-800' : 'border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800') : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-50' }}"
                            >
                                @if($reforwardToPresident)
                                    {{-- Return / re-forward indicator (revision RIS) --}}
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2M3 10l4-4M3 10l4 4"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-1.2 7.8"/>
                                    </svg>
                                @else
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                @endif
                            </button>
                            @if($allowsDirectApprove)
                                <button
                                    type="button"
                                    @if($canDirectApproveByBudget)
                                        onclick="window.openDirectApproveModal('{{ $ris->ris_id }}', 'direct')"
                                    @else
                                        disabled
                                    @endif
                                    data-tip="{{ $directApproveTip }}"
                                    data-tip-place="above"
                                    title="{{ $directApproveTip }}"
                                    aria-label="{{ $directApproveTip }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition {{ $canDirectApproveByBudget ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100 hover:text-emerald-800' : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-50' }}"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            @endif
                        @endif

                        @include('partials.share-document-button', [
                            'type' => 'ris',
                            'id' => $ris->ris_id,
                            'label' => \App\Support\RequestExplorer::shareUiLabel('ris', $ris->ris_form_number ?? null, $ris->ris_request_no ?? null),
                        ])

                        @include('admin.partials.ris-president-remarks-button', ['ris' => $ris])

                        @if($isPresidentRejected && $risCanAct)
                            <button
                                type="button"
                                onclick="window.openReturnRevisionModal('{{ $ris->ris_id }}', {{ (int) $ownerPurchaserId }}, @js($ownerPurchaserName))"
                                data-tip="Pass to Purchaser"
                                data-tip-place="above"
                                title="Pass this RIS to the Purchaser for revision"
                                aria-label="Pass to Purchaser"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        @endif

                        <button
                            type="button"
                            onclick="window.openSignRisPreviewModal('{{ $ris->ris_id }}')"
                            title="View RIS"
                            aria-label="View RIS"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="pur-empty">No RIS records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@include('layouts.partials.table-showing-pager', [
    'pager' => $signableRisRecords,
    'linkClass' => 'sign-ris-pagination-link',
    'noun' => 'records',
])
