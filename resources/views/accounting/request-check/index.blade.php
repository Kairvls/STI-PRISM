@extends('layouts.accounting-layout')

@section('title', 'Request Checks')

@section('content')
@include('accounting.partials.flash')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Request Checks</h1>
        <p class="mt-1 text-sm text-gray-500">Review submitted checks and mark funds ready for personal collection.</p>
    </div>
    <form method="GET" class="flex gap-2">
        <input type="hidden" name="status" value="{{ $filter }}">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search RFC, ATP, RIS, payee" class="h-10 w-72 rounded-xl border border-gray-200 bg-white px-3 text-sm">
        <button class="acc-btn acc-btn-funds">Search</button>
    </form>
</div>
<div class="mt-5 flex flex-wrap gap-2 slide-up">
    @foreach (['incoming' => 'Needs review ('.$counts['incoming'].')', 'funds' => 'Funds to release ('.$counts['funds'].')', 'released' => 'Released ('.$counts['released'].')', 'revision' => 'Revision', 'approved' => 'Approved', 'all' => 'All'] as $key => $label)
        <a href="/accounting/request-check?status={{ $key }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === $key ? 'bg-gray-900 text-white' : 'border border-gray-200 bg-white text-gray-600' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white slide-up">
    <table class="acc-table w-full min-w-[960px] text-sm">
        <thead class="border-b border-gray-100 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">Request Check</th>
                <th class="px-4 py-3">ATP</th>
                <th class="px-4 py-3">RIS</th>
                <th class="px-4 py-3">Payee</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($records as $row)
                @php
                    $st = $row->request_check_status;
                    if (!empty($row->request_check_funds_released_at)) { $st = 'Released'; }
                    $when = $row->request_check_submitted_at ?? $row->request_check_date ?? $row->request_check_created_at;
                @endphp
                <tr>
                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->authority_purchase_form_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->ris_form_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->request_check_payee }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ $row->request_check_amount_figures !== null ? '₱'.number_format((float)$row->request_check_amount_figures, 2) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</td>
                    <td class="px-4 py-3">@include('accounting.partials.status-badge', ['status' => $st])</td>
                    <td class="px-4 py-3 text-right"><a href="/accounting/request-check/{{ $row->request_check_id }}" class="text-xs font-semibold text-gray-900 hover:text-amber-600">{{ $st === 'Released' || $st === 'Approved' ? 'View' : 'Review' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-14 text-center text-sm text-gray-500">No Request Check records in this queue.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
