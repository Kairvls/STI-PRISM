@extends('layouts.accounting-layout')

@section('title', 'Request Checks')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Transactions</p>
            <h1 class="acc-page-title">Request Checks</h1>
            <p class="acc-page-subtitle">Review submitted checks and mark funds ready for personal collection.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search RFC, ATP, RIS, payee" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="acc-filters slide-up">
        @foreach (['incoming' => 'Needs review ('.$counts['incoming'].')', 'funds' => 'Funds to release ('.$counts['funds'].')', 'released' => 'Released ('.$counts['released'].')', 'revision' => 'Revision', 'approved' => 'Approved', 'all' => 'All'] as $key => $label)
            <a href="/accounting/request-check?status={{ $key }}" class="acc-chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="acc-table-wrap slide-up">
        <table class="acc-table min-w-[900px]">
            <thead>
                <tr>
                    <th>Request Check</th>
                    <th>ATP</th>
                    <th>RIS</th>
                    <th>Payee</th>
                    <th class="!text-right">Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $row)
                    @php
                        $st = $row->request_check_status;
                        if (!empty($row->request_check_funds_released_at)) { $st = 'Released'; }
                        $when = $row->request_check_submitted_at ?? $row->request_check_date ?? $row->request_check_created_at;
                    @endphp
                    <tr>
                        <td class="acc-ref">{{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}</td>
                        <td class="acc-muted">{{ $row->authority_purchase_form_number ?? '—' }}</td>
                        <td class="acc-muted">{{ $row->ris_form_number ?? '—' }}</td>
                        <td class="acc-muted">{{ $row->request_check_payee }}</td>
                        <td class="acc-money">{{ $row->request_check_amount_figures !== null ? '₱'.number_format((float)$row->request_check_amount_figures, 2) : '—' }}</td>
                        <td class="acc-muted">{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</td>
                        <td>@include('accounting.partials.status-badge', ['status' => $st])</td>
                        <td class="text-right"><a href="/accounting/request-check/{{ $row->request_check_id }}" class="acc-row-link">{{ $st === 'Released' || $st === 'Approved' ? 'View' : 'Review' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="acc-empty my-2">No Request Check records in this queue.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="acc-pagination">{{ $records->links() }}</div>
</div>
@endsection
