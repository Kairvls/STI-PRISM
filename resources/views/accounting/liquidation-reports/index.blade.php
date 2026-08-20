@extends('layouts.accounting-layout')

@section('title', 'Liquidation Reports')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'incoming' => 'Needs review ('.$counts['incoming'].')',
        'revision' => 'Revision ('.$counts['revision'].')',
        'approved' => 'Approved ('.$counts['approved'].')',
        'all' => 'All',
    ];
@endphp

<div class="acc-page fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Review expenses, receipts, and close the transaction.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search liquidation or employee" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 slide-up">
        <div class="pm-seg" role="tablist" aria-label="Liquidation status filters" data-active="{{ $filter }}">
            <span class="pm-seg-thumb" aria-hidden="true"></span>
            @foreach ($filters as $key => $label)
                <a
                    href="/accounting/liquidation-reports?status={{ $key }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                    role="tab"
                    class="pm-seg-btn {{ $filter === $key ? 'is-active' : '' }}"
                    data-filter="{{ $key }}"
                    aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="acc-table-wrap mt-4 slide-up">
        <table class="acc-table min-w-[820px]">
            <thead>
                <tr>
                    <th>Liquidation</th>
                    <th>Receiving Report</th>
                    <th>Employee</th>
                    <th class="!text-right">Amount</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $row)
                    @php $when = $row->liquidation_report_submitted_at ?? $row->liquidation_report_date_submitted ?? $row->liquidation_report_created_at; @endphp
                    <tr>
                        <td class="acc-ref">{{ $row->liquidation_report_form_number ?? ('LIQ-'.$row->liquidation_report_id) }}</td>
                        <td class="acc-muted">{{ $row->receiving_report_form_number ?? '—' }}</td>
                        <td class="acc-muted">{{ $row->liquidation_report_employee_name }}</td>
                        <td class="acc-money">{{ $row->liquidation_report_amount_advance !== null ? '₱'.number_format((float)$row->liquidation_report_amount_advance, 2) : '—' }}</td>
                        <td class="acc-muted">{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</td>
                        <td>@include('accounting.partials.status-badge', ['status' => $row->liquidation_report_status])</td>
                        <td class="text-right">
                            <a
                                href="/accounting/liquidation-reports/{{ $row->liquidation_report_id }}"
                                class="icon-btn"
                                data-tip="Review liquidation"
                                aria-label="Review liquidation"
                            >
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="acc-empty my-2">No liquidation reports in this queue.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())
        <div class="acc-pagination mt-3">{{ $records->links('pagination.president') }}</div>
    @endif
</div>
@endsection
