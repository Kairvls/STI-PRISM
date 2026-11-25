@if ($recentRis->count() > 0)
    @php
        $recentRisIds = collect($recentRis->items() ?? [])->pluck('ris_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $recentAtpEntriesByRis = [];
        if (!empty($recentRisIds) && \Illuminate\Support\Facades\Schema::hasTable('authority_to_purchase_table')) {
            $atpRows = \Illuminate\Support\Facades\DB::table('authority_to_purchase_table as atp')
                ->leftJoin('suppliers_table as sup', 'atp.authority_purchase_supplier_id', '=', 'sup.supplier_id')
                ->leftJoin('physical_suppliers_table as psup', 'sup.supplier_id', '=', 'psup.supplier_id')
                ->leftJoin('online_suppliers_table as osup', 'sup.supplier_id', '=', 'osup.supplier_id')
                ->whereIn('atp.authority_purchase_ris_id', $recentRisIds)
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
                $recentAtpEntriesByRis[$rid][] = ['number' => $num, 'supplier' => $sup];
            }
        }
    @endphp
    <div class="pm-queue-table-wrap">
        <table class="pm-queue-table">
            <thead>
                <tr>
                    <th class="col-ref">RIS &amp; ATP Number</th>
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
                            @include('partials.atp-more-popover', ['atps' => $recentAtpEntriesByRis[(int) $ris->ris_id] ?? []])
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
                            @php
                                $rawStatus = trim((string) ($ris->ris_status ?? ''));
                                $isPresRejected = in_array($rawStatus, ['Rejected by President', 'Rejected by the President'], true);
                                if ($isApproved) {
                                    $pillPrimary = 'Approved';
                                    $pillSub = 'By President · Sent to Purchaser';
                                    $pillStyle = 'background:#ecfdf5;border-color:#a7f3d0;color:#059669;';
                                    $pillDot = '#10b981';
                                } elseif ($isPresRejected) {
                                    $pillPrimary = 'Rejected';
                                    $pillSub = 'By President';
                                    $pillStyle = 'background:#fef2f2;border-color:#fecaca;color:#dc2626;';
                                    $pillDot = '#dc2626';
                                } elseif ($isRejected) {
                                    $pillPrimary = 'Rejected';
                                    $pillSub = 'By School Administrator';
                                    $pillStyle = 'background:#fef2f2;border-color:#fecaca;color:#dc2626;';
                                    $pillDot = '#dc2626';
                                } else {
                                    $pillPrimary = 'Need Approval';
                                    $pillSub = 'Forwarded by SA';
                                    $pillStyle = 'background:#eef2ff;border-color:#c7d2fe;color:#4338ca;';
                                    $pillDot = '#4f46e5';
                                }
                            @endphp
                            <div class="inline-flex flex-col items-start">
                                <span
                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"
                                    style="{{ $pillStyle }}"
                                >
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $pillDot }};"></span>
                                    <span>{{ $pillPrimary }}</span>
                                </span>
                                <span class="mt-1 text-[11px] font-medium leading-tight text-slate-500">{{ $pillSub }}</span>
                            </div>
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
