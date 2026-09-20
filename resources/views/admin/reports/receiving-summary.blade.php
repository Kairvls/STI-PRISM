@extends('layouts.admin-layout')

@section('title', 'Receiving Summary')

@section('content')

<div class="admin-page space-y-6">
    <h1 class="admin-page-title print-only" hidden>Receiving report — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'receiving'])

    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            [
                'label' => 'Delivered',
                'value' => number_format($accepted),
            ],
            [
                'label' => 'Returned',
                'value' => number_format($returned),
            ],
            [
                'label' => 'With OR',
                'value' => number_format($withOr),
            ],
        ],
    ])

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
                        @php
                            $receivedByName = trim((string) ($row->receiving_report_received_by_name ?? ''));
                            $receivedBySignature = (string) ($row->receiving_report_received_by_signature ?? '');
                            $receivedByLabel = $receivedByName !== ''
                                ? $receivedByName
                                : (\App\Support\RisWorkflow::isDrawnSignature($receivedBySignature) ? '—' : ($receivedBySignature !== '' ? $receivedBySignature : '—'));
                        @endphp
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->receiving_report_id }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->receiving_report_invoice_no ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->receiving_report_status }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $receivedByLabel }}</td>
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
