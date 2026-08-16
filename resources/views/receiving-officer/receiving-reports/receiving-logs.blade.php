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
            ['filter' => 'accepted', 'label' => 'Accepted', 'count' => $acceptedCount ?? 0, 'color' => 'text-emerald-600', 'title' => 'Show accept actions'],
            ['filter' => 'returned', 'label' => 'Returned', 'count' => $returnedCount ?? 0, 'color' => 'text-rose-600', 'title' => 'Show return actions'],
        ];
        $sliderOptions = [
            ['filter' => 'all', 'label' => 'All'],
            ['filter' => 'accepted', 'label' => 'Accepted'],
            ['filter' => 'returned', 'label' => 'Returned'],
        ];
    @endphp

    <div data-ro-table data-ro-default-filter="all" class="space-y-6">
    @include('layouts.partials.receiving-stat-cards', ['cards' => $logCards, 'current' => $filter])

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Activity log</h2>
                        <p class="mt-1 text-xs text-gray-500">Inspection, accept, return, and inventory actions.</p>
                    </div>
                    <div class="flex items-center gap-2">
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
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Timestamp</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Reference</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php
                            $action = strtolower((string) $log->receiving_log_action);
                            $rowStatus = str_contains($action, 'return') ? 'returned' : (str_contains($action, 'accept') ? 'accepted' : 'all');
                            $rowSearch = trim(implode(' ', [
                                $log->receiving_log_action ?? '',
                                $log->receiving_log_remarks ?? '',
                                $log->officer_name ?? '',
                                $log->ris_form_number ?? '',
                                $log->authority_purchase_form_number ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="{{ $rowStatus }}" data-ro-search="{{ $rowSearch }}">
                            <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $log->receiving_log_action }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—') }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $log->officer_name ?: 'Receiving Officer' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $log->receiving_log_remarks }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($logs->count()) style="display:none" @endif>
                        <td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for the first inspection. Logs appear after you accept or return an approved ATP.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
    </div>
</div>

@endsection
