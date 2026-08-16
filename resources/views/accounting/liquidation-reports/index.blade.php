@extends('layouts.accounting-layout')

@section('title', 'Liquidation Reports')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Transactions</p>
            <h1 class="acc-page-title">Liquidation Reports</h1>
            <p class="acc-page-subtitle">Review expenses, receipts, and close the transaction.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search liquidation or employee" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="acc-filters slide-up">
        @foreach (['incoming' => 'Needs review ('.$counts['incoming'].')', 'revision' => 'Revision ('.$counts['revision'].')', 'approved' => 'Approved ('.$counts['approved'].')', 'all' => 'All'] as $key => $label)
            <a href="/accounting/liquidation-reports?status={{ $key }}" class="acc-chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="acc-table-wrap slide-up">
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
                        <td class="text-right"><a href="/accounting/liquidation-reports/{{ $row->liquidation_report_id }}" class="acc-row-link">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="acc-empty my-2">No liquidation reports in this queue.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="acc-pagination">{{ $records->links() }}</div>
</div>
@endsection
