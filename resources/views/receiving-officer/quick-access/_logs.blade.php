<div class="space-y-3 p-1" data-ro-table data-ro-default-filter="all">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Inspection, accept, and return audit trail.</p>
                        <a href="/receiving/logs" class="text-xs font-semibold text-sky-600">Open full page</a>
                    </div>
                    <span class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">{{ ($rows ?? collect())->count() }} total</span>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    @include('layouts.partials.receiving-filter-slider', [
                        'sliderId' => 'qaLogsFilterSlider',
                        'current' => 'all',
                        'options' => [
                            ['filter' => 'all', 'label' => 'All'],
                            ['filter' => 'accepted', 'label' => 'Delivered'],
                            ['filter' => 'returned', 'label' => 'Returned'],
                        ],
                    ])
                    @include('layouts.partials.receiving-filters', ['searchId' => 'qaLogsSearch', 'placeholder' => 'Search action, reference...'])
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Timestamp</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Action</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Reference</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Officer</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $log)
                        @php
                            $action = strtolower((string) ($log->receiving_log_action ?? ''));
                            $logStatus = strtolower(trim((string) ($log->receiving_log_status ?? '')));
                            $rrStatus = strtolower(trim((string) ($log->receiving_report_status ?? '')));
                            if ($logStatus === 'returned' || str_contains($action, 'return') || $rrStatus === 'returned') {
                                $rowStatus = 'returned';
                                $statusLabel = 'Returned';
                            } elseif (
                                in_array($logStatus, ['delivered', 'completed', 'accepted'], true)
                                || str_contains($action, 'second count')
                                || str_contains($action, 'deliver')
                                || str_contains($action, 'accept')
                                || str_contains($action, 'inventory')
                                || in_array($rrStatus, ['completed', 'accepted'], true)
                            ) {
                                $rowStatus = 'accepted';
                                $statusLabel = trim((string) ($log->receiving_log_status ?? '')) ?: 'Delivered';
                                if ($statusLabel === 'Completed' || $statusLabel === 'Accepted') {
                                    $statusLabel = 'Delivered';
                                }
                            } else {
                                $rowStatus = 'all';
                                $statusLabel = trim((string) ($log->receiving_log_status ?? $log->receiving_report_status ?? '')) ?: '—';
                            }
                            $rrId = $log->receiving_report_id ?? null;
                            $rowSearch = trim(implode(' ', [$log->receiving_log_action ?? '', $statusLabel, $log->receiving_report_form_number ?? '', $log->ris_form_number ?? '', $log->authority_purchase_form_number ?? '', $log->officer_name ?? '']));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->receiving_log_created_at ? \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-4 py-3 text-sm font-semibold">{{ $log->receiving_log_action }}</td>
                            <td class="px-4 py-3 text-sm">@include('accounting.partials.status-badge', ['status' => $statusLabel])</td>
                            <td class="px-4 py-3 text-sm">{{ $log->receiving_report_form_number ?: ($log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—')) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $log->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        @if($rrId)
                                            onclick="openReceivingReportPreview('{{ $rrId }}')"
                                            title="View"
                                            aria-label="View"
                                        @else
                                            disabled
                                            title="No receiving report attached"
                                            aria-label="View unavailable"
                                        @endif
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    @if($rrId)
                                        <a href="/receiving/reports/{{ $rrId }}/print" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Print" aria-label="Print">
                                            <i data-lucide="printer" class="h-4 w-4"></i>
                                        </a>
                                    @else
                                        <button type="button" disabled class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 opacity-40" title="Print unavailable" aria-label="Print unavailable">
                                            <i data-lucide="printer" class="h-4 w-4"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if(($rows ?? collect())->count()) style="display:none" @endif>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">No receiving logs yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
</div>
