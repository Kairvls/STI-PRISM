@extends('layouts.accounting-layout')

@section('title', 'ATP')

@section('content')
@include('accounting.partials.flash')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Authority to Purchase</h1>
        <p class="mt-1 text-sm text-gray-500">Review ATP submitted by Purchaser.</p>
    </div>
    <form method="GET" class="flex gap-2">
        <input type="hidden" name="status" value="{{ $filter }}">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search ATP, RIS, supplier" class="h-10 w-64 rounded-xl border border-gray-200 bg-white px-3 text-sm">
        <button class="acc-btn acc-btn-funds">Search</button>
    </form>
</div>
<div class="mt-5 flex flex-wrap gap-2 slide-up">
    @foreach (['incoming' => 'Needs review ('.$counts['incoming'].')', 'revision' => 'Revision ('.$counts['revision'].')', 'approved' => 'Approved ('.$counts['approved'].')', 'all' => 'All'] as $key => $label)
        <a href="/accounting/authority-to-purchase?status={{ $key }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === $key ? 'bg-gray-900 text-white' : 'border border-gray-200 bg-white text-gray-600' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white slide-up">
    <table class="acc-table w-full min-w-[880px] text-sm">
        <thead class="border-b border-gray-100 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">ATP</th>
                <th class="px-4 py-3">Related RIS</th>
                <th class="px-4 py-3">Supplier</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3">Submitted</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($records as $row)
                <tr>
                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $row->authority_purchase_form_number }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->ris_form_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->company_name ?? $row->shop_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ $row->atp_total !== null ? '₱'.number_format((float)$row->atp_total, 2) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $row->authority_purchase_submitted_at ? \Carbon\Carbon::parse($row->authority_purchase_submitted_at)->format('M d, Y') : '—' }}</td>
                    <td class="px-4 py-3">@include('accounting.partials.status-badge', ['status' => $row->authority_purchase_status, 'submitted' => $row->authority_purchase_submitted_at, 'revision' => $row->authority_purchase_rejection_reason])</td>
                    <td class="px-4 py-3 text-right"><a href="/accounting/authority-to-purchase/{{ $row->authority_purchase_id }}" class="text-xs font-semibold text-gray-900 hover:text-amber-600">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-14 text-center text-sm text-gray-500">No ATP records in this queue.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
