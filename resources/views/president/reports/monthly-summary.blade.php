@extends('layouts.president-layout')

@section('title', 'Monthly Summary')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Monthly Summary</h1>
        <p class="mt-1 text-sm text-gray-500">Summary of approved and rejected procurement decisions.</p>
    </div>

    <div class="flex items-center gap-2">
        <span class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200">
            Incoming data view
        </span>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-sm font-medium text-gray-500">Approved (total)</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $approvedDecisionsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">All-time approved procurement decisions.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-sm font-medium text-gray-500">Rejected (total)</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $rejectedDecisionsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">All-time rejected procurement decisions.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-sm font-medium text-gray-500">Pending (total)</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $pendingApprovalsCount ?? 0 }}</p>
        <p class="mt-2 text-xs text-gray-500">Currently pending RIS approvals.</p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-sm font-medium text-gray-500">This page</p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">Monthly</p>
        <p class="mt-2 text-xs text-gray-500">Breakdown table below.</p>
    </div>
</div>

<div class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Approved vs Rejected (by Month)</h2>
            <p class="mt-1 text-xs text-gray-500">If the controller does not provide monthly aggregates yet, the table will show placeholders.</p>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Month</th>
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Approved</th>
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Rejected</th>
                    <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Net</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $monthlyStats = $monthlyStats ?? null;
                @endphp

                @if(is_array($monthlyStats) && count($monthlyStats) > 0)
                    @foreach($monthlyStats as $row)
                        @php
                            $approved = (int)($row['approved'] ?? 0);
                            $rejected = (int)($row['rejected'] ?? 0);
                            $net = $approved - $rejected;
                            $label = $row['month_label'] ?? ($row['month'] ?? '—');
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-yellow-50/30 transition">
                            <td class="px-2 py-4 text-sm font-semibold text-gray-700">{{ $label }}</td>
                            <td class="px-2 py-4 text-sm text-emerald-700 font-semibold">{{ $approved }}</td>
                            <td class="px-2 py-4 text-sm text-rose-700 font-semibold">{{ $rejected }}</td>
                            <td class="px-2 py-4 text-sm font-semibold text-gray-800">{{ $net }}</td>
                        </tr>
                    @endforeach
                @else
                    @for($i = 0; $i < 6; $i++)
                        <tr class="border-b border-gray-100 hover:bg-yellow-50/30 transition">
                            <td class="px-2 py-4 text-sm font-semibold text-gray-700">—</td>
                            <td class="px-2 py-4 text-sm text-emerald-700 font-semibold">0</td>
                            <td class="px-2 py-4 text-sm text-rose-700 font-semibold">0</td>
                            <td class="px-2 py-4 text-sm font-semibold text-gray-800">0</td>
                        </tr>
                    @endfor
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-xs text-gray-500">
        Note: You may later pass <span class="font-semibold">$monthlyStats</span> from <code>PresidentController@monthlySummary()</code> to populate this table.
    </div>
</div>

@endsection

