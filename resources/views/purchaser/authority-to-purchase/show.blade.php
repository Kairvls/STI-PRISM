@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'ATP Details')
@section('page-subtitle', 'Review Authority to Purchase record and approval actions')

@section('content')

<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">{{ $atp->authority_purchase_form_number ?? 'ATP #'.$atp->authority_purchase_id }}</h2>
            <p class="text-sm text-slate-600">RIS: {{ $atp->ris_form_number ?? 'RIS-'.$atp->authority_purchase_ris_id }}</p>
            @php
                $atpLineage = \App\Support\DocumentLineage::forAtp((int) $atp->authority_purchase_id);
                $atpHint = \App\Support\DocumentLineage::reviewHint(
                    \App\Support\RisWorkflow::atpStatusLabel($atp),
                    null,
                    'atp'
                );
            @endphp
            <div class="mt-3 max-w-3xl">
                @include('partials.document-lineage', [
                    'lineage' => $atpLineage,
                    'currentType' => 'ATP',
                    'statusHint' => $atpHint,
                ])
            </div>
        </div>
        <a href="{{ route(($pp ?? 'purchaser').'.atp.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Back to list</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">ATP Information</h3>
                    <p class="text-sm text-slate-500">Details and current status.</p>
                </div>
                @include('accounting.partials.status-badge', [
                    'status' => \App\Support\RisWorkflow::atpStatusLabel($atp),
                    'submitted' => $atp->authority_purchase_submitted_at,
                    'revision' => $atp->authority_purchase_rejection_reason,
                ])
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
                        <dt class="text-xs uppercase tracking-wide {{ $atp->authority_purchase_status === 'Rejected' ? 'text-red-500' : 'text-amber-600' }}">
                            {{ $atp->authority_purchase_status === 'Rejected' ? 'Rejection reason' : 'Revision requested' }}
                        </dt>
                        <dd class="mt-1 text-sm {{ $atp->authority_purchase_status === 'Rejected' ? 'text-red-700' : 'text-amber-800' }}">{{ $atp->authority_purchase_rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">RIS summary</h3>
            <dl class="grid gap-4">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Purpose</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $atp->ris_purpose_description ?? '—' }}</dd>
                </div>
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
            <a href="{{ route(($pp ?? 'purchaser').'.atp.edit', $atp->authority_purchase_id) }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Edit draft</a>
            <form method="POST" action="{{ route(($pp ?? 'purchaser').'.atp.submit', $atp->authority_purchase_id) }}">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Submit ATP</button>
            </form>
        @endif

        @if($atp->authority_purchase_status === 'Pending' && $atp->authority_purchase_submitted_at)
            <p class="text-sm text-gray-500">Waiting for Accounting to approve or request a revision.</p>
        @endif

        @if($atp->authority_purchase_status === 'Approved' && !$atp->authority_purchase_is_archived)
            <a href="{{ route(($pp ?? 'purchaser').'.rfc.index', ['selected_atp' => $atp->authority_purchase_id]) }}" class="h-10 inline-flex items-center rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Create RFC</a>
        @endif

        @if(!$atp->authority_purchase_is_archived && in_array($atp->authority_purchase_status, ['Approved', 'Rejected'], true))
            <form method="POST" action="{{ route(($pp ?? 'purchaser').'.atp.archive', $atp->authority_purchase_id) }}" class="inline-block">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-gray-100 px-5 text-sm font-medium text-gray-700">Archive</button>
            </form>
        @elseif($atp->authority_purchase_is_archived)
            <form method="POST" action="{{ route(($pp ?? 'purchaser').'.atp.restore', $atp->authority_purchase_id) }}" class="inline-block">
                @csrf
                <button type="submit" class="h-10 rounded-lg bg-blue-50 px-5 text-sm font-medium text-blue-600">Restore</button>
            </form>
        @endif
    </div>
</div>

@endsection
