@extends('layouts.president-layout')

@section('title', 'Disbursement Vouchers')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'incoming' => 'Awaiting signature',
        'approved' => 'Signed / released',
        'revision' => 'Returned to Accounting',
    ];
@endphp

<div class="space-y-6">
    <!--<p class="text-sm text-slate-500">Sign DVs from Accounting after School Administrator approval. Each request includes Canvas + RIS, ATP, and RFC.</p>-->

    <div class="flex flex-wrap gap-x-5 border-b border-gray-100 text-sm">
        @foreach($filters as $key => $label)
            <a
                href="{{ route('president.dv.index', ['status' => $key]) }}"
                class="-mb-px border-b-2 pb-2.5 transition {{ $filter === $key ? 'border-[#0025cc] font-medium text-gray-900' : 'border-transparent text-gray-400 hover:text-gray-600' }}"
            >
                {{ $label }}
                <span class="ml-1 tabular-nums">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-gray-50/70">
                <tr class="border-b border-gray-100">
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">DV</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Request No.</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Payee / RFC</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/70">
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $row->dv_form_number ?: 'Disbursement Voucher' }}</td>
                        <td class="px-5 py-4 text-gray-600">
                            @include('partials.procurement-request-no', [
                                'requestNo' => $row->dv_request_no ?: ($row->rfc_request_no ?: null),
                                'inline' => true,
                                'showLabel' => false,
                            ])
                            @if(blank($row->dv_request_no ?: ($row->rfc_request_no ?? null)))
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            <div>{{ $row->dv_payee ?: ($row->request_check_payee ?: '—') }}</div>
                            <div class="text-xs text-gray-400">{{ $row->request_check_form_number ?: ('RFC #'.$row->dv_request_check_id) }}</div>
                        </td>
                        <td class="px-5 py-4 tabular-nums text-gray-700">
                            {{ $row->dv_amount !== null ? '₱'.number_format((float) $row->dv_amount, 2) : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $row->dv_status }}</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('president.dv.show', $row->dv_id) }}" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white hover:bg-[#001db3]">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-sm text-gray-500">No Disbursement Vouchers in this view.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
