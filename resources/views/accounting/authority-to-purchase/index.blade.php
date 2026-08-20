@extends('layouts.accounting-layout')

@section('title', 'ATP')

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
            <p class="text-sm leading-6 text-gray-500">Review ATP submitted by Purchaser.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search ATP, RIS, supplier" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 slide-up">
        <div class="pm-seg" role="tablist" aria-label="ATP status filters" data-active="{{ $filter }}">
            <span class="pm-seg-thumb" aria-hidden="true"></span>
            @foreach ($filters as $key => $label)
                <a
                    href="/accounting/authority-to-purchase?status={{ $key }}{{ request('search') ? '&search='.urlencode(request('search')) : '' }}"
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
                        <td class="text-right">
                            <a
                                href="/accounting/authority-to-purchase/{{ $row->authority_purchase_id }}"
                                class="icon-btn"
                                data-tip="Review ATP"
                                aria-label="Review ATP"
                            >
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="acc-empty my-2">No ATP records in this queue.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())
        <div class="acc-pagination mt-3">{{ $records->links('pagination.president') }}</div>
    @endif
</div>
@endsection
