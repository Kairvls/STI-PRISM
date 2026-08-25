@extends('layouts.admin-layout')

@section('title', 'Receiving Summary')

@section('content')

<div class="admin-page space-y-6">
    <div class="print-hidden">
        <h1 class="admin-page-title">Receiving</h1>
        <p class="admin-page-subtitle">Read-only delivery summary. Accept and return stay on Receiving Officer.</p>
    </div>
    <h1 class="admin-page-title print-only">Receiving report — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'receiving'])

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Delivered</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-slate-700">{{ $accepted }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Returned</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-slate-600">{{ $returned }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">With OR</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-slate-900">{{ $withOr }}</p>
        </div>
        <div class="rounded-[18px] border border-gray-200 bg-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory lines</p>
            <p class="mt-2 font-['Outfit'] text-3xl font-bold text-slate-900">{{ $inventoryLines }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search OR, status, officer...'])
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">ID</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">OR</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Received by</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->receiving_report_id }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->receiving_report_invoice_no ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->receiving_report_status }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->receiving_report_received_by_signature ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->receiving_report_date ?: $row->receiving_report_created_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-400">No records in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('layouts.partials.table-showing-pager', ['pager' => $rows, 'noun' => 'records'])
    </div>
</div>

@endsection
