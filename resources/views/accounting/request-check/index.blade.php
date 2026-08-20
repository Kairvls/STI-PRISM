@extends('layouts.accounting-layout')

@section('title', 'Request Checks')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'incoming' => 'Needs review ('.$counts['incoming'].')',
        'funds' => 'Funds ('.$counts['funds'].')',
        'released' => 'Released ('.$counts['released'].')',
        'revision' => 'Revision',
        'approved' => 'Approved',
        'all' => 'All',
    ];
@endphp

<div class="acc-page fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Review submitted checks and mark funds ready for personal collection.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search RFC, ATP, RIS, payee" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 slide-up">
        <div class="pm-seg" role="tablist" aria-label="Request Check status filters" data-active="{{ $filter }}">
            <span class="pm-seg-thumb" aria-hidden="true"></span>
            @foreach ($filters as $key => $label)
                <a
                    href="/accounting/request-check?status={{ $key }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
                    role="tab"
                    class="pm-seg-btn {{ $filter === $key ? 'is-active' : '' }}"
                    data-filter="{{ $key }}"
                    aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="acc-table-wrap mt-4 slide-up">
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
                        <td class="text-right">
                            @php
                                $reviewTip = ($st === 'Released' || $st === 'Approved') ? 'View request check' : 'Review request check';
                            @endphp
                            <a
                                href="/accounting/request-check/{{ $row->request_check_id }}"
                                class="icon-btn"
                                data-tip="{{ $reviewTip }}"
                                aria-label="{{ $reviewTip }}"
                            >
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="acc-empty my-2">No Request Check records in this queue.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())
        <div class="acc-pagination mt-3">{{ $records->links('pagination.president') }}</div>
    @endif
</div>
@endsection
