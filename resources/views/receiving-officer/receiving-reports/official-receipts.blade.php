@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Official Receipts</h1>
        <p class="admin-page-subtitle">OR numbers recorded when a delivery is accepted.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            @include('layouts.partials.receiving-filters')
        </div>
        <table class="w-full text-left">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">OR</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS / ATP</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->official_receipt }}</td>
                        <td class="px-5 py-4 text-sm text-gray-700">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</td>
                        <td class="px-5 py-4 text-sm text-gray-700">{{ $row->supplier_name }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for an accepted delivery with an official receipt number.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
