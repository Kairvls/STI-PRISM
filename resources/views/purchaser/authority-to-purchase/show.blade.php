@extends('layouts.purchaser-layout')

@section('page-title', 'ATP Details')
@section('page-subtitle', 'Review Authority to Purchase record and approval actions')

@section('content')

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">{{ $atp->authority_purchase_form_number ?? 'ATP #'.$atp->authority_purchase_id }}</h2>
            <p class="text-sm text-slate-600">RIS: {{ $atp->ris_form_number ?? 'RIS-'.$atp->authority_purchase_ris_id }}</p>
        </div>
        <a href="{{ route('purchaser.atp.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Back to list</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">ATP Information</h3>
                    <p class="text-sm text-slate-500">Details and current status.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $atp->authority_purchase_status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($atp->authority_purchase_status === 'Rejected' ? 'bg-red-100 text-red-700' : ($atp->authority_purchase_submitted_at ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700')) }}">
                    @if($atp->authority_purchase_status === 'Approved')
                        Approved
                    @elseif($atp->authority_purchase_status === 'Rejected')
                        Rejected
                    @elseif($atp->authority_purchase_submitted_at)
                        Submitted
                    @else
                        Draft
                    @endif
                </span>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">RIS</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->ris_form_number ?? 'RIS-'.$atp->authority_purchase_ris_id }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Purchase date</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ optional(\Carbon\Carbon::parse($atp->authority_purchase_date))->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Supplier</dt>
                    <dd class="mt-1 text-sm text-slate-700">
                        @if($atp->supplier_store_type === 'Physical Store')
                            {{ $atp->company_name ?? 'Physical supplier' }}
                        @else
                            {{ $atp->shop_name ?? 'Online supplier' }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Received by</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->authority_purchase_received_by_name ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Reference PO / PR</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->authority_purchase_reference_po_no ?? '—' }}</dd>
                </div>
                @if($atp->authority_purchase_rejection_reason)
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-red-500">Rejection reason</dt>
                        <dd class="mt-1 text-sm text-red-700">{{ $atp->authority_purchase_rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">RIS summary</h3>
            <dl class="grid gap-4">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">RIS type</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->ris_request_type ?? '—' }}</dd>
                </div>
                @if($atp->ris_request_type === 'New Procurement')
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Title</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $atp->ris_manual_title ?? '—' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Related equipment</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->equipment_name ?? $atp->report_unlisted_equipment_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Report #</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->report_id ?? '—' }}</dd>
                </div>
                @if($atp->authority_purchase_submitted_at)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ optional(\Carbon\Carbon::parse($atp->authority_purchase_submitted_at))->format('M d, Y h:i A') }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">ATP Items</h3>
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Unit</th>
                        <th class="px-3 py-2 text-right">Unit Price</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($items as $item)
                        <tr>
                            <td class="px-3 py-2">{{ $item->atp_description ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_quantity ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_unit ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_unit_price !== null ? number_format($item->atp_unit_price, 2) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->atp_amount !== null ? number_format($item->atp_amount, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                    @if($items->isEmpty())
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No ATP line items added.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        @if(!$atp->authority_purchase_submitted_at && $atp->authority_purchase_status === 'Pending')
            <a href="{{ route('purchaser.atp.edit', $atp->authority_purchase_id) }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Edit draft</a>
            <form method="POST" action="{{ route('purchaser.atp.submit', $atp->authority_purchase_id) }}">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Submit ATP</button>
            </form>
        @endif

        @if($atp->authority_purchase_status === 'Pending' && $atp->authority_purchase_submitted_at)
            <form method="POST" action="{{ route('purchaser.atp.approve', $atp->authority_purchase_id) }}" class="inline-block">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-emerald-100 px-5 text-sm font-medium text-emerald-700">Approve</button>
            </form>
            <button onclick="document.getElementById('reject-reason').classList.toggle('hidden')" class="h-10 rounded-lg border border-red-300 px-5 text-sm font-medium text-red-700">Reject</button>
        @endif

        @if(!$atp->authority_purchase_is_archived)
            <form method="POST" action="{{ route('purchaser.atp.archive', $atp->authority_purchase_id) }}" class="inline-block">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-gray-100 px-5 text-sm font-medium text-gray-700">Archive</button>
            </form>
        @else
            <form method="POST" action="{{ route('purchaser.atp.restore', $atp->authority_purchase_id) }}" class="inline-block">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-blue-50 px-5 text-sm font-medium text-blue-600">Restore</button>
            </form>
        @endif
    </div>

    <div id="reject-reason" class="hidden rounded-2xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">
        <form method="POST" action="{{ route('purchaser.atp.reject', $atp->authority_purchase_id) }}">
            @csrf
            <label class="text-xs font-medium text-red-700">Rejection reason</label>
            <textarea name="authority_purchase_rejection_reason" rows="3" class="mt-2 w-full rounded-lg border border-red-300 px-3 py-2 text-sm"></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('reject-reason').classList.add('hidden')" class="h-10 rounded-lg border border-red-300 px-5 text-sm font-medium text-red-700">Cancel</button>
                <button type="submit" class="h-10 rounded-lg bg-red-600 px-5 text-sm font-medium text-white">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

@endsection
