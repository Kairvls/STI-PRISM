@extends('layouts.admin-layout')

@section('title', 'Approval Logs')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')

@php
    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();

    $decisionClasses = [
        'Approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'Accepted' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'Signed' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'Rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Declined' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
    ];
@endphp

<div class="admin-page">
    <h1 class="admin-page-title print-only" hidden>Approval log — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'approvals'])

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-950">Approvals</h2>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                </div>
                <p class="text-xs text-gray-400 sm:ml-1">Read-only signature and decision log.</p>
            </div>
            @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search officer, status, remarks…'])
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table w-full min-w-[1000px]">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Officer</th>
                        <th>Level</th>
                        <th>Record</th>
                        <th>Decision</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $decision = (string) ($row->approval_log_approval_status ?: '');
                            $decisionClass = $decisionClasses[$decision] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="whitespace-nowrap text-sm text-gray-500">
                                {{ $row->approval_log_approved_at ? \Carbon\Carbon::parse($row->approval_log_approved_at)->format('M j, Y g:i A') : '—' }}
                            </td>
                            <td class="text-sm font-semibold text-gray-900">{{ $row->officer_name ?: '—' }}</td>
                            <td class="text-sm text-gray-600">{{ $row->approval_log_level ?? '—' }}</td>
                            <td class="text-sm text-gray-600">
                                {{ $row->approval_log_reference_type }} #{{ $row->approval_log_reference_id }}
                            </td>
                            <td>
                                @if($decision !== '')
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $decisionClass }}">{{ $decision }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="max-w-md text-sm text-gray-500">{{ $row->approval_log_approval_remarks ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="pur-empty">No records in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('layouts.partials.table-showing-pager', ['pager' => $rows, 'noun' => 'records'])
    </div>
</div>

@endsection
