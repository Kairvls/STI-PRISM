@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Receiving Logs</h1>
        <p class="admin-page-subtitle">Audit trail of inspections, accepts, inventory updates, and returns.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    @php
        $filter = 'all';
        $logCards = [
            ['filter' => 'all', 'label' => 'All', 'count' => $allCount ?? $logs->count(), 'color' => 'text-slate-900', 'title' => 'Show all logs'],
            ['filter' => 'accepted', 'label' => 'Delivered', 'count' => $acceptedCount ?? 0, 'color' => 'text-sky-600', 'title' => 'Show deliver actions'],
            ['filter' => 'returned', 'label' => 'Returned', 'count' => $returnedCount ?? 0, 'color' => 'text-slate-600', 'title' => 'Show return actions'],
        ];
        $sliderOptions = [
            ['filter' => 'all', 'label' => 'All'],
            ['filter' => 'accepted', 'label' => 'Delivered'],
            ['filter' => 'returned', 'label' => 'Returned'],
        ];
    @endphp

    <div data-ro-table data-ro-default-filter="all" class="space-y-6">
    @include('layouts.partials.receiving-stat-cards', ['cards' => $logCards, 'current' => $filter])

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Activity log</h2>
                        <p class="mt-1 text-xs text-gray-500">Inspection, accept, return, and inventory actions.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">
                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'roLogsViewSwitcher',
                            'btnClass' => 'ro-logs-view-btn',
                        ])
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'logs'])
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $allCount ?? $logs->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    @include('layouts.partials.receiving-filter-slider', [
                        'sliderId' => 'receivingLogsFilterSlider',
                        'current' => $filter,
                        'ariaLabel' => 'Log filters',
                        'options' => $sliderOptions,
                    ])
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingLogsSearch', 'placeholder' => 'Search action, reference, officer...'])
                </div>
            </div>
        </div>
        <div id="roLogsTable" class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Timestamp</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Reference</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Remarks</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
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
                            $rowSearch = trim(implode(' ', [
                                $log->receiving_log_action ?? '',
                                $statusLabel,
                                $log->receiving_log_remarks ?? '',
                                $log->officer_name ?? '',
                                $log->receiving_report_form_number ?? '',
                                $log->ris_form_number ?? '',
                                $log->authority_purchase_form_number ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}">
                            <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $log->receiving_log_action }}</td>
                            <td class="px-5 py-4">
                                @include('accounting.partials.status-badge', ['status' => $statusLabel])
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->receiving_report_form_number ?: ($log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—')) }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $log->receiving_log_remarks }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-40"
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
                                        <a
                                            href="/receiving/reports/{{ $rrId }}/print"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                            title="Print"
                                            aria-label="Print"
                                        >
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
                    <tr class="receiving-empty-row" @if($logs->count()) style="display:none" @endif>
                        <td colspan="7" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for the first inspection. Logs appear after second count or a return on a Receiving Report.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="roLogsCards" class="hidden space-y-3 px-5 py-4">
            @forelse($logs as $log)
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
                    $rowSearch = trim(implode(' ', [
                        $log->receiving_log_action ?? '',
                        $statusLabel,
                        $log->receiving_log_remarks ?? '',
                        $log->officer_name ?? '',
                        $log->receiving_report_form_number ?? '',
                        $log->ris_form_number ?? '',
                        $log->authority_purchase_form_number ?? '',
                    ]));
                    $ref = $log->receiving_report_form_number ?: ($log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—'));
                    $actionsHtml = '';
                    if ($rrId) {
                        $actionsHtml =
                            '<button type="button" onclick="openReceivingReportPreview(\''.$rrId.'\')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="View" aria-label="View"><i data-lucide="eye" class="h-4 w-4"></i></button>'
                            .'<a href="/receiving/reports/'.$rrId.'/print" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50" title="Print" aria-label="Print"><i data-lucide="printer" class="h-4 w-4"></i></a>';
                    }
                @endphp
                @include('receiving-officer.partials.list-info-card', [
                    'title' => $log->receiving_log_action,
                    'subtitle' => $ref,
                    'status' => $statusLabel,
                    'statusClass' => $rowStatus === 'returned' ? 'border-slate-200 bg-slate-50 text-slate-700' : 'border-sky-200 bg-sky-50 text-sky-700',
                    'roStatus' => $rowStatus,
                    'roSearch' => $rowSearch,
                    'fields' => [
                        ['label' => 'When', 'value' => \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A')],
                        ['label' => 'Status', 'value' => $statusLabel],
                        ['label' => 'Officer', 'value' => $log->officer_name ?: 'Receiving Officer'],
                        ['label' => 'Remarks', 'value' => $log->receiving_log_remarks ?: '—', 'full' => true],
                    ],
                    'actionsHtml' => $actionsHtml,
                ])
            @empty
            @endforelse
            <div class="receiving-empty-cards px-2 py-10 text-center text-sm text-gray-400" @if($logs->count()) style="display:none" @endif>
                Waiting for the first inspection. Logs appear after second count or a return on a Receiving Report.
            </div>
        </div>

        @include('layouts.partials.receiving-table-pager')
    </div>
    </div>
</div>

@include('admin.partials.view-mode-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.bindAdminViewMode === 'function') {
        window.bindAdminViewMode({
            tableId: 'roLogsTable',
            cardsId: 'roLogsCards',
            buttonSelector: '.ro-logs-view-btn',
            storageKey: 'ro_logs_view',
        });
    }
    if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
});
</script>

@endsection
