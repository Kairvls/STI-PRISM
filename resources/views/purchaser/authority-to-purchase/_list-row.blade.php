@php
    $rowClass = $rowClass ?? 'transition hover:bg-gray-50/70';
    $rowAttrs = $rowAttrs ?? '';
    $isChildRow = ! empty($isChildRow);
    $groupKey = isset($groupKey) ? (string) $groupKey : '';
@endphp
<tr
    class="{{ $rowClass }}"
    {!! $rowAttrs !!}
>
                            <td class="relative px-5 py-4 {{ $isChildRow ? 'pl-14' : '' }}">
                                @if($isChildRow)
                                    <span class="pointer-events-none absolute left-[37px] top-0 w-px bg-slate-200 {{ !empty($isLastChild) ? 'h-1/2' : 'h-full' }}" aria-hidden="true"></span>
                                    <span class="pointer-events-none absolute left-[37px] top-1/2 h-px w-4 bg-slate-200" aria-hidden="true"></span>
                                @endif
                                <div class="flex items-center gap-3">
                                    @unless($isChildRow)
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                            <i data-lucide="file-check-2" class="h-4 w-4"></i>
                                        </div>
                                    @endunless
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-gray-900">
                                                {{ $atp->authority_purchase_form_number ?: '—' }}
                                            </p>
                                            @include('partials.ris-urgency-badge', [
                                                'urgent' => \App\Support\DocumentUrgency::isUrgent('ATP', $atp),
                                                'size' => 'sm',
                                                'title' => 'Inherited from linked RIS',
                                            ])
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $rowRequestNo = $atp->authority_purchase_request_no
                                        ?? ($fallbackRequestNo ?? null);
                                @endphp
                                @include('partials.procurement-request-no', ['requestNo' => $rowRequestNo, 'inline' => true, 'showLabel' => false])
                                @if(blank($rowRequestNo))
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($isChildRow)
                                    <p class="text-xs text-gray-400">Same RIS</p>
                                @else
                                    @php
                                        $atpLineNames = collect();
                                        $atpEquipmentLabel = trim((string) ($atp->equipment_name ?? $atp->report_unlisted_equipment_name ?? ''));
                                        if ($atpEquipmentLabel !== '') {
                                            $atpLineNames = collect([$atpEquipmentLabel]);
                                        } else {
                                            $atpQtyTotals = [];
                                            foreach (collect(($atpItems ?? collect())->get((int) $atp->authority_purchase_id, collect())) as $line) {
                                                $desc = trim((string) ($line->atp_description ?? ''));
                                                if ($desc === '') {
                                                    continue;
                                                }
                                                $qty = (float) ($line->atp_quantity ?? 0);
                                                if ($qty <= 0) {
                                                    continue;
                                                }
                                                $key = mb_strtolower($desc);
                                                if (! isset($atpQtyTotals[$key])) {
                                                    $atpQtyTotals[$key] = ['name' => $desc, 'qty' => 0.0];
                                                }
                                                $atpQtyTotals[$key]['qty'] += $qty;
                                            }
                                            $atpLineNames = collect($atpQtyTotals)->map(function ($row) {
                                                $qty = (float) $row['qty'];
                                                $qtyLabel = fmod($qty, 1.0) === 0.0
                                                    ? (string) (int) $qty
                                                    : rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');

                                                return $qtyLabel.' '.$row['name'];
                                            })->values();
                                            if ($atpLineNames->isEmpty()) {
                                                $purpose = trim((string) ($atp->ris_purpose_description ?? ''));
                                                $atpLineNames = $purpose !== '' ? collect([$purpose]) : collect();
                                            }
                                        }
                                        $atpLineFirst = $atpLineNames->first();
                                        $atpLineRest = $atpLineNames->slice(1)->values();
                                        $atpLineMore = $atpLineRest->count();
                                    @endphp
                                    <p class="text-sm text-gray-700">{{ \App\Support\RisWorkflow::formNumber($atp, (int) ($atp->authority_purchase_ris_id ?? 0)) }}</p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        @if($atpLineFirst)
                                            <span class="inline-flex flex-wrap items-center gap-1.5">
                                                <span>{{ $atpLineFirst }}</span>
                                                @if($atpLineMore > 0)
                                                    @include('partials.closable-more-popover', [
                                                        'moreCount' => $atpLineMore,
                                                        'heading' => 'ATP items',
                                                        'popoverKey' => 'pur-atp-'.$atp->authority_purchase_id,
                                                        'rows' => $atpLineNames->map(fn ($lineName) => [
                                                            'title' => (string) $lineName,
                                                            'meta' => null,
                                                        ])->values()->all(),
                                                    ])
                                                @endif
                                            </span>
                                        @else
                                            No line items
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                @if($atp->supplier_store_type === 'Physical Store')
                                    {{ $atp->company_name ?? 'Physical supplier' }}
                                @else
                                    {{ $atp->shop_name ?? 'Online supplier' }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                                @if($atp->authority_purchase_date)
                                    {{ \Carbon\Carbon::parse($atp->authority_purchase_date)->format('M d, Y') }}
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $atpStatusLabel = \App\Support\RisWorkflow::atpStatusLabel($atp);
                                @endphp
                                @if($atpStatusLabel === \App\Support\ProcurementStatus::REVIEWED)
                                    <div class="inline-flex flex-col items-start">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                            style="background:#f3e8ff;color:#7e22ce;border-color:#d8b4fe;"
                                        >
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#7e22ce;"></span>
                                            <span>Reviewed</span>
                                        </span>
                                        <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">Awaiting for approval</div>
                                    </div>
                                @elseif($atpStatusLabel === \App\Support\ProcurementStatus::APPROVED)
                                    <div class="inline-flex flex-col items-start">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                            style="background:#ecfdf5;color:#047857;border-color:#a7f3d0;"
                                        >
                                            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:#059669;"></span>
                                            <span>Approved</span>
                                        </span>
                                        @if(!empty($atp->authority_purchase_payment_path))
                                            <div class="mt-1">
                                                @include('partials.payment-method-badge', ['atp' => $atp, 'size' => 'sm'])
                                            </div>
                                            @if(empty($atp->has_rfc) && !\App\Support\ProcurementPortal::isSupplyOfficerContext())
                                                <div class="mt-1 text-[11px] font-medium leading-tight" style="color:#b45309;">Create RFC to proceed</div>
                                            @endif
                                        @elseif(empty($atp->has_rfc) && !\App\Support\ProcurementPortal::isSupplyOfficerContext())
                                            <div class="mt-1 text-[11px] font-medium leading-tight" style="color:#b45309;">Choose payment method</div>
                                        @endif
                                    </div>
                                @else
                                    @include('accounting.partials.status-badge', [
                                        'status' => $atpStatusLabel,
                                        'submitted' => $atp->authority_purchase_submitted_at,
                                        'revision' => $atp->authority_purchase_rejection_reason,
                                    ])
                                    @if(!empty($atp->authority_purchase_payment_path))
                                        <div class="mt-1">
                                            @include('partials.payment-method-badge', ['atp' => $atp, 'size' => 'sm'])
                                        </div>
                                    @endif
                                @endif
                                @if(!empty($atp->linked_rfc_sent_to_accounting))
                                    <div class="mt-1 text-[11px] font-medium leading-tight text-slate-500">
                                        Forwarded to Accounting
                                    </div>
                                @elseif($atp->authority_purchase_status === 'Approved' && !empty($atp->released_to_purchaser) && !empty($atp->has_rfc) && empty($atp->linked_rfc_sent_to_accounting) && $atpPurchaserOwns)
                                    <span class="mt-1 inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                                        Approved record
                                    </span>
                                @endif
                                @if(!empty($atp->purchase_order_id) && !empty($atp->purchase_order_label))
                                    @php
                                        $poIndexRoute = ($pp ?? 'purchaser').'.purchase-orders.index';
                                    @endphp
                                    @if(\Illuminate\Support\Facades\Route::has($poIndexRoute))
                                        <a
                                            href="{{ route($poIndexRoute, ['edit_po' => $atp->purchase_order_id]) }}"
                                            class="mt-1 inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100"
                                        >In {{ $atp->purchase_order_label }}</a>
                                    @else
                                        <span class="mt-1 inline-flex rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">In {{ $atp->purchase_order_label }}</span>
                                    @endif
                                @endif
                                @if($pendingHandover = $handoverOutgoing->get((int) $atp->authority_purchase_id))
                                    <p class="mt-1 flex items-center gap-1 text-[11px] font-medium text-amber-700">
                                        <i data-lucide="hourglass" class="h-3 w-3"></i>
                                        Waiting for {{ $pendingHandover->to_name ?: 'co-worker' }} to accept
                                    </p>
                                @elseif($declinedHandover = $handoverDeclined->get((int) $atp->authority_purchase_id))
                                    <p class="mt-1 flex items-center gap-1 text-[11px] font-medium text-rose-700" title="{{ $declinedHandover->handover_response_note ? 'Reason: '.$declinedHandover->handover_response_note : '' }}">
                                        <i data-lucide="user-x" class="h-3 w-3"></i>
                                        Declined by {{ $declinedHandover->to_name ?: 'co-worker' }}
                                    </p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @php
                                    $atpCanActOnRfc = !$archiveView
                                        && empty($atp->can_release_to_purchaser)
                                        && empty($atp->has_rfc)
                                        && !\App\Support\ProcurementPortal::isSupplyOfficerContext()
                                        && (
                                            ($atpPurchaserOwns && in_array($atp->authority_purchase_status, ['Draft', 'Pending'], true) && empty($atp->authority_purchase_submitted_at))
                                            || ($atp->authority_purchase_status === 'Approved' && ($atpPurchaserOwns || !empty($atp->released_to_purchaser)))
                                        );
                                    $showChoosePaymentBtn = $atpCanActOnRfc && empty($atp->authority_purchase_payment_path);
                                    $showCreateRfcBtn = $atpCanActOnRfc && !empty($atp->authority_purchase_payment_path);
                                @endphp
                                <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                    @unless($showChoosePaymentBtn || $showCreateRfcBtn)
                                        <button
                                            type="button"
                                            @click="openView({{ $atp->authority_purchase_id }})"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                                            title="View"
                                            aria-label="View"
                                        >
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>
                                    @endunless

                                    @if(!$archiveView && !empty($atp->can_release_to_purchaser))
                                        <form method="POST" action="{{ route('supply-officer.atp.release-purchaser', $atp->authority_purchase_id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                data-pur-confirm="Release {{ $atp->authority_purchase_form_number ?: 'ATP #'.$atp->authority_purchase_id }} (with approved Canvass + RIS) back to the Purchaser for Request for Check?"
                                                data-pur-confirm-title="Release to Purchaser"
                                                data-pur-confirm-ok="Release"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white transition hover:bg-emerald-700"
                                                title="Release to Purchaser"
                                                aria-label="Release to Purchaser"
                                            >
                                                <i data-lucide="send" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                @elseif(
                                    !$archiveView
                                    && empty($atp->has_rfc)
                                    && !\App\Support\ProcurementPortal::isSupplyOfficerContext()
                                    && (
                                        ($atpPurchaserOwns && in_array($atp->authority_purchase_status, ['Draft', 'Pending'], true) && empty($atp->authority_purchase_submitted_at))
                                        || ($atp->authority_purchase_status === 'Approved' && ($atpPurchaserOwns || !empty($atp->released_to_purchaser)))
                                    )
                                )
                                    @if(!$atp->has_rfc && empty($atp->authority_purchase_payment_path))
                                        <button
                                            type="button"
                                            @click="openView({{ $atp->authority_purchase_id }})"
                                            class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl transition hover:opacity-85"
                                            style="background:#fffbeb;color:#d97706;"
                                            data-tip="Choose Payment Method"
                                            data-tip-place="above"
                                            aria-label="Choose Payment Method"
                                        >
                                            <i data-lucide="wallet" class="h-4 w-4"></i>
                                            <span class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" style="background:#f59e0b;"></span>
                                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full ring-2 ring-white" style="background:#d97706;"></span>
                                            </span>
                                        </button>
                                    @elseif(!$atp->has_rfc && !empty($atp->authority_purchase_payment_path))
                                        @php
                                            $pathLabel = \App\Support\ProcurementPaymentPath::label($atp->authority_purchase_payment_path);
                                        @endphp
                                        <button
                                            type="button"
                                            @click="openView({{ $atp->authority_purchase_id }})"
                                            class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl transition hover:opacity-90"
                                            style="background:#0025cc;color:#ffffff;"
                                            data-tip="Create RFC ({{ $pathLabel }})"
                                            data-tip-place="above"
                                            aria-label="Create RFC ({{ $pathLabel }})"
                                        >
                                            <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                            <span class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5">
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" style="background:#f59e0b;"></span>
                                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full ring-2 ring-white" style="background:#d97706;"></span>
                                            </span>
                                        </button>
                                    @elseif($atp->has_rfc)
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700" title="RFC Created" aria-label="RFC Created">
                                            <i data-lucide="circle-check" class="h-4 w-4"></i>
                                        </span>
                                    @endif
                                @endif

                                    @php
                                        // Hide archive while Supply must release, or Purchaser must create funding.
                                        $atpNeedsHandoff = ! empty($atp->can_release_to_purchaser)
                                            || (
                                                $atp->authority_purchase_status === 'Approved'
                                                && ! empty($atp->released_to_purchaser)
                                                && empty($atp->has_rfc)
                                            );
                                        $canArchiveAtp = ! $atp->authority_purchase_is_archived
                                            && ! $atpNeedsHandoff
                                            && (
                                                $atp->authority_purchase_status === 'Rejected'
                                                || ($atp->authority_purchase_status === 'Approved' && ! empty($atp->has_rfc))
                                            );
                                    @endphp
                                    @if(!$isChildRow && $archiveView)
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.atp.restore', $atp->authority_purchase_id) }}"
                                            class="shrink-0"
                                            data-pur-confirm="Restore this Authority to Purchase back to Active?"
                                            data-pur-confirm-title="Restore ATP"
                                            data-pur-confirm-ok="Restore"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50"
                                                title="Restore"
                                                aria-label="Restore"
                                            >
                                                <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @elseif(!$isChildRow && $canArchiveAtp)
                                        <form
                                            method="POST"
                                            action="{{ route(($pp ?? 'purchaser').'.atp.archive', $atp->authority_purchase_id) }}"
                                            class="shrink-0"
                                            data-pur-confirm="Archive this Authority to Purchase?"
                                            data-pur-confirm-title="Archive ATP"
                                            data-pur-confirm-ok="Archive"
                                            data-pur-confirm-kind="archive"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50"
                                                title="Archive"
                                                aria-label="Archive"
                                            >
                                                <i data-lucide="archive" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
