@extends('layouts.accounting-layout')

@section('title', 'Accounting History')

@section('content')
<div class="acc-page fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Processed ATP, Request Checks, fund releases, and liquidations.</p>
        </div>
        <form method="GET" class="acc-toolbar">
            <select name="type" class="acc-select">
                <option value="all" @selected($type==='all')>All types</option>
                <option value="atp" @selected($type==='atp')>ATP</option>
                <option value="rfc" @selected($type==='rfc')>Request Check</option>
                <option value="liq" @selected($type==='liq')>Liquidation</option>
            </select>
            <input type="search" name="search" value="{{ $search }}" placeholder="Reference or related document" class="acc-search">
            <button class="acc-btn acc-btn-funds">Search</button>
        </form>
    </div>

    <div class="acc-table-wrap mt-4 slide-up">
        <table class="acc-table min-w-[760px]">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Related</th>
                    <th class="!text-right">Amount</th>
                    <th>Status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $row)
                    <tr>
                        <td class="acc-muted">{{ $row->type }}</td>
                        <td><a class="acc-ref acc-link" href="{{ $row->url }}">{{ $row->ref }}</a></td>
                        <td class="acc-muted">{{ $row->related ?? '—' }}</td>
                        <td class="acc-money">{{ $row->amount !== null ? '₱'.number_format((float)$row->amount, 2) : '—' }}</td>
                        <td>@include('accounting.partials.status-badge', ['status' => $row->status])</td>
                        <td class="acc-muted">{{ $row->when ? \Carbon\Carbon::parse($row->when)->format('M d, Y g:i A') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="acc-empty my-2">No processed records yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
