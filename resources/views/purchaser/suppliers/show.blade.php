@extends('layouts.purchaser-layout')

@section('page-title', 'Supplier Details')
@section('page-subtitle', 'View supplier information and procurement history')

@section('content')

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="pur-page-title">Supplier #{{ $supplier->supplier_id }}</h2>
            <p class="pur-page-subtitle">{{ $supplier->supplier_store_type }}</p>
        </div>
        <a href="{{ route('purchaser.suppliers.index') }}" class="pur-btn-secondary">Back to suppliers</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="pur-card p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Supplier Information</h3>
                    <p class="text-sm text-slate-500">Core details and contact information.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $supplier->supplier_is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $supplier->supplier_is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Store type</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $supplier->supplier_store_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Created at</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ optional(\Carbon\Carbon::parse($supplier->supplier_created_at))->format('M d, Y h:i A') ?? '—' }}</dd>
                </div>
                @if($supplier->supplier_store_type === 'Physical Store')
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Company name</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->company_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contact person</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->contact_person ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Email address</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->email_address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Contact number</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->contact_number ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Company address</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->company_address ?? '—' }}</dd>
                    </div>
                @else
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Shop name</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->shop_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">App used</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->app_used ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Order ID</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $supplier->order_id ?? '—' }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="pur-card p-6">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Procurement history</h3>
            @if($procurementHistory->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-slate-600">
                    No procurement requests found for this supplier.
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Request #</th>
                                <th class="px-4 py-3">Report</th>
                                <th class="px-4 py-3">Created</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Issue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($procurementHistory as $history)
                                <tr>
                                    <td class="px-4 py-3">#{{ $history->procurement_request_id }}</td>
                                    <td class="px-4 py-3">{{ $history->report_id ? '#'.$history->report_id : '—' }}</td>
                                    <td class="px-4 py-3">{{ optional(\Carbon\Carbon::parse($history->procurement_request_created_at))->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $history->procurement_request_status ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3">{{ $history->report_problem_description ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Notes</h3>
        <p class="text-sm leading-6 text-slate-600">Use this page to confirm supplier profile details and track procurement requests linked to the supplier. If you need to update contact or payment information, use the edit action from the supplier list.</p>
    </div>
</div>

@endsection
