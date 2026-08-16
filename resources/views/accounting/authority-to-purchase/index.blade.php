@extends('layouts.accounting-layout')

@section('title', 'ATP')

@section('content')
@include('accounting.partials.flash')

<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Transactions</p>
            <h1 class="acc-page-title">Authority to Purchase</h1>
            <p class="acc-page-subtitle">Review ATP submitted by Purchaser.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search ATP, RIS, supplier" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="acc-filters slide-up">
        @foreach (['incoming' => 'Needs review ('.$counts['incoming'].')', 'revision' => 'Revision ('.$counts['revision'].')', 'approved' => 'Approved ('.$counts['approved'].')', 'all' => 'All'] as $key => $label)
            <a href="/accounting/authority-to-purchase?status={{ $key }}" class="acc-chip {{ $filter === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="acc-table-wrap slide-up">
        <table class="acc-table min-w-[820px]">
            <thead>
                <tr>
                    <th>ATP</th>
                    <th>Related RIS</th>
                    <th>Supplier</th>
                    <th class="!text-right">Amount</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $row)
                    <tr>
                        <td class="acc-ref">{{ $row->authority_purchase_form_number }}</td>
                        <td class="acc-muted">{{ $row->ris_form_number ?? '—' }}</td>
                        <td class="acc-muted">{{ $row->company_name ?? $row->shop_name ?? '—' }}</td>
                        <td class="acc-money">{{ $row->atp_total !== null ? '₱'.number_format((float)$row->atp_total, 2) : '—' }}</td>
                        <td class="acc-muted">{{ $row->authority_purchase_submitted_at ? \Carbon\Carbon::parse($row->authority_purchase_submitted_at)->format('M d, Y') : '—' }}</td>
                        <td>@include('accounting.partials.status-badge', ['status' => $row->authority_purchase_status, 'submitted' => $row->authority_purchase_submitted_at, 'revision' => $row->authority_purchase_rejection_reason])</td>
                        <td class="text-right"><a href="/accounting/authority-to-purchase/{{ $row->authority_purchase_id }}" class="acc-row-link">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="acc-empty my-2">No ATP records in this queue.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="acc-pagination">{{ $records->links() }}</div>
</div>
@endsection
