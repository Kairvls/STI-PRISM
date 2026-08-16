@extends('layouts.accounting-layout')

@section('title', 'Accounting History')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">History</h1>
        <p class="mt-1 text-sm text-gray-500">Processed ATP, Request Checks, fund releases, and liquidations.</p>
    </div>
    <form method="GET" class="flex gap-2">
        <select name="type" class="h-10 rounded-xl border border-gray-200 bg-white px-3 text-sm">
            <option value="all" @selected($type==='all')>All types</option>
            <option value="atp" @selected($type==='atp')>ATP</option>
            <option value="rfc" @selected($type==='rfc')>Request Check</option>
            <option value="liq" @selected($type==='liq')>Liquidation</option>
        </select>
        <input type="search" name="search" value="{{ $search }}" placeholder="Reference or related document" class="h-10 w-64 rounded-xl border border-gray-200 px-3 text-sm">
        <button class="acc-btn acc-btn-funds">Search</button>
    </form>
</div>
<div class="mt-5 overflow-hidden rounded-xl border border-gray-200 bg-white slide-up">
    <table class="acc-table w-full min-w-[800px] text-sm">
        <thead class="border-b border-gray-100 bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Reference</th>
                <th class="px-4 py-3">Related</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Updated</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($records as $row)
                <tr>
                    <td class="px-4 py-3 text-gray-500">{{ $row->type }}</td>
                    <td class="px-4 py-3 font-semibold"><a class="text-gray-900 hover:text-amber-600" href="{{ $row->url }}">{{ $row->ref }}</a></td>
                    <td class="px-4 py-3 text-gray-600">{{ $row->related ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ $row->amount !== null ? '₱'.number_format((float)$row->amount, 2) : '—' }}</td>
                    <td class="px-4 py-3">@include('accounting.partials.status-badge', ['status' => $row->status])</td>
                    <td class="px-4 py-3 text-gray-500">{{ $row->when ? \Carbon\Carbon::parse($row->when)->format('M d, Y g:i A') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-14 text-center text-sm text-gray-500">No processed records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
