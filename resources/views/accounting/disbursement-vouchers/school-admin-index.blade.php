@extends(\App\Support\ProcurementReviewPortal::LAYOUT)

@section('title', 'Disbursement Vouchers')

@section('content')
@include('accounting.partials.flash')

@php
    $filters = [
        'incoming' => 'Needs review',
        'revision' => 'Returned',
        'approved' => 'Approved',
        'all' => 'All',
    ];
@endphp

<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Disbursement Vouchers</h1>
        <p class="mt-1 text-sm text-slate-500">Open a request to see the form, approve or return, and view the procurement pipeline. President route and check release come later.</p>
    </div>

    <div class="flex flex-wrap gap-x-5 border-b border-slate-100 text-sm">
        @foreach($filters as $key => $label)
            <a
                href="{{ route('school-admin.dv.index', ['status' => $key]) }}"
                class="-mb-px border-b-2 pb-2.5 {{ $filter === $key ? 'border-[#0025cc] font-medium text-slate-900' : 'border-transparent text-slate-400' }}"
            >
                {{ $label }}
                @if(isset($counts[$key]))
                    <span class="ml-1 tabular-nums">{{ $counts[$key] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                    <th class="px-5 py-3 font-medium">DV</th>
                    <th class="py-3 pr-4 font-medium">Request No.</th>
                    <th class="py-3 pr-4 font-medium">RFC</th>
                    <th class="py-3 pr-4 font-medium">Amount</th>
                    <th class="py-3 pr-4 font-medium">Status</th>
                    <th class="py-3 pr-5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($records as $row)
                    @php
                        $dvRequestNo = trim((string) ($row->dv_request_no ?? ($row->request_check_request_no ?? '')));
                    @endphp
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $row->dv_form_number ?: 'Disbursement Voucher' }}</td>
                        <td class="py-3 pr-4">
                            @include('partials.procurement-request-no', ['requestNo' => $dvRequestNo, 'inline' => true, 'showLabel' => false])
                            @if($dvRequestNo === '')
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-slate-600">{{ $row->request_check_form_number ?: 'Request for Check' }}</td>
                        <td class="py-3 pr-4 tabular-nums">{{ $row->dv_amount !== null ? '₱'.number_format((float) $row->dv_amount, 2) : '—' }}</td>
                        <td class="py-3 pr-4">{{ $row->dv_status }}</td>
                        <td class="py-3 pr-5 text-right">
                            <a href="{{ route('school-admin.dv.show', $row->dv_id) }}" class="font-medium text-[#0025cc] hover:underline">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-500">No Disbursement Vouchers in this view.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($records->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $records->links() }}</div>
        @endif
    </div>
</div>
@endsection
